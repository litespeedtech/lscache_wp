<?php
/**
 * The Crawler Sitemap Class
 *
 * @since      	1.1.0
 */
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

class Crawler_Map extends Instance {
	const BM_MISS = 1;
	const BM_HIT = 2;
	const BM_BLACKLIST = 4;

	protected static $_instance;
	private $_home_url; // Used to simplify urls
	private $_tb;
	private $__data;
	private $_conf_map_timeout;

	protected $_urls = array();

	/**
	 * Instantiate the class
	 *
	 * @since 1.1.0
	 * @access protected
	 */
	protected function __construct() {
		$this->_home_url = get_home_url();
		$this->__data = Data::get_instance();
		$this->_tb = $this->__data->tb( 'crawler' );
		$this->_tb_blacklist = $this->__data->tb( 'crawler_blacklist' );
		$this->_conf_map_timeout = Conf::val( Base::O_CRAWLER_MAP_TIMEOUT );
	}

	/**
	 * Save URLs crawl status into DB
	 *
	 * @since  3.0
	 * @access public
	 */
	public function save_map_status( $list, $curr_crawler ) {
		global $wpdb;
		Utility::compatibility();

		$total_crawler = count( Crawler::get_instance()->list_crawlers() );
		$total_crawler_pos = $total_crawler - 1;

		// Replace current crawler's position
		$curr_crawler = (int) $curr_crawler;
		foreach ( $list as $bit => $ids ) { // $ids = [ id => [ url, code ], ... ]
			if ( ! $ids ) {
				continue;
			}
			Debug2::debug( "🐞🗺️ Update map [crawler] $curr_crawler [bit] $bit [count] " . count( $ids ) );

			// Update res first, then reason
			$right_pos = $total_crawler_pos - $curr_crawler;
			$sql_res = "CONCAT( LEFT( res, $curr_crawler ), '$bit', RIGHT( res, $right_pos ) )";

			$id_all = implode( ',', array_map( 'intval', array_keys( $ids ) ) );

			$wpdb->query( "UPDATE `$this->_tb` SET res = $sql_res WHERE id IN ( $id_all )" );

			// Add blacklist
			if ( $bit == 'B' || $bit == 'N' ) {
				$q = "SELECT a.id, a.url FROM `$this->_tb_blacklist` a LEFT JOIN `$this->_tb` b ON b.url=a.url WHERE b.id IN ( $id_all )";
				$existing = $wpdb->get_results( $q, ARRAY_A );
				// Update current crawler status tag in existing blacklist
				if ( $existing ) {
					$count = $wpdb->query( "UPDATE `$this->_tb_blacklist` SET res = $sql_res WHERE id IN ( " . implode( ',', array_column( $existing, 'id' ) ) . " )" );
					Debug2::debug( '🐞🗺️ Update blacklist [count] ' . $count );
				}

				// Append new blacklist
				if ( count( $ids ) > count( $existing ) ) {
					$new_urls = array_diff( array_column( $ids, 'url' ), array_column( $existing, 'url') );

					Debug2::debug( '🐞🗺️ Insert into blacklist [count] ' . count( $new_urls ) );

					$q = "INSERT INTO `$this->_tb_blacklist` ( url, res, reason ) VALUES " . implode( ',', array_fill( 0, count( $new_urls ), '( %s, %s, %s )' ) );
					$data = array();
					$res = array_fill( 0, $total_crawler, '-' );
					$res[ $curr_crawler ] = $bit;
					$res = implode( '', $res );
					$default_reason = $total_crawler > 1 ? str_repeat( ',', $total_crawler - 1 ) : ''; // Pre-populate default reason value first, update later
					foreach ( $new_urls as $url ) {
						$data[] = $url;
						$data[] = $res;
						$data[] = $default_reason;
					}
					$wpdb->query( $wpdb->prepare( $q, $data ) );
				}
			}

			// Update sitemap reason w/ HTTP code
			$reason_array = array();
			foreach ( $ids as $id => $v2 ) {
				$code = (int)$v2[ 'code' ];
				if ( empty( $reason_array[ $code ] ) ) {
					$reason_array[ $code ] = array();
				}
				$reason_array[ $code ][] = (int)$id;
			}

			foreach ( $reason_array as $code => $v2 ) {
				// Complement comma
				if ( $curr_crawler ) {
					$code = ',' . $code;
				}
				if ( $curr_crawler < $total_crawler_pos ) {
					$code .= ',';
				}

				$count = $wpdb->query( "UPDATE `$this->_tb` SET reason = CONCAT( SUBSTRING_INDEX( reason, ',', $curr_crawler ), '$code', SUBSTRING_INDEX( reason, ',', -$right_pos ) ) WHERE id IN (" . implode( ',', $v2 ) . ")" );

				Debug2::debug( "🐞🗺️ Update map reason [code] $code [pos] left $curr_crawler right -$right_pos [count] $count" );

				// Update blacklist reason
				if ( $bit == 'B' || $bit == 'N' ) {
					$count = $wpdb->query( "UPDATE `$this->_tb_blacklist` a LEFT JOIN `$this->_tb` b ON b.url = a.url SET a.reason = CONCAT( SUBSTRING_INDEX( a.reason, ',', $curr_crawler ), '$code', SUBSTRING_INDEX( a.reason, ',', -$right_pos ) ) WHERE b.id IN (" . implode( ',', $v2 ) . ")" );

					Debug2::debug( "🐞🗺️ Update blacklist [code] $code [pos] left $curr_crawler right -$right_pos [count] $count" );
				}
			}


			// Reset list
			$list[ $bit ] = array();
		}

		return $list;
	}

	/**
	 * Add one record to blacklist
	 * NOTE: $id is sitemap table ID
	 *
	 * @since  3.0
	 * @access public
	 */
	public function blacklist_add( $id ) {
		global $wpdb;

		$id = (int)$id;

		// Build res&reason
		$total_crawler = count( Crawler::get_instance()->list_crawlers() );
		$res = str_repeat( 'B', $total_crawler );
		$reason = implode( ',', array_fill( 0, $total_crawler, 'Man' ) );

		$row = $wpdb->get_row( "SELECT a.url, b.id FROM `$this->_tb` a LEFT JOIN `$this->_tb_blacklist` b ON b.url = a.url WHERE a.id = '$id'", ARRAY_A );

		if ( ! $row ) {
			Debug2::debug( '🐞🗺️ blacklist failed to add [id] ' . $id );
			return;
		}

		Debug2::debug( '🐞🗺️ Add to blacklist [url] ' . $row[ 'url' ] );

		$q = "UPDATE `$this->_tb` SET res = %s, reason = %s WHERE id = %d";
		$wpdb->query( $wpdb->prepare( $q, array( $res, $reason, $id ) ) );

		if ( $row[ 'id' ] ) {
			$q = "UPDATE `$this->_tb_blacklist` SET res = %s, reason = %s WHERE id = %d";
			$wpdb->query( $wpdb->prepare( $q, array( $res, $reason, $row[ 'id' ] ) ) );
		}
		else {
			$q = "INSERT INTO `$this->_tb_blacklist` (url, res, reason) VALUES (%s, %s, %s)";
			$wpdb->query( $wpdb->prepare( $q, array( $row[ 'url' ], $res, $reason ) ) );
		}

	}

	/**
	 * Delete one record from blacklist
	 *
	 * @since  3.0
	 * @access public
	 */
	public function blacklist_del( $id ) {
		global $wpdb;

		if ( ! $this->__data->tb_exist( 'crawler_blacklist' ) ) {
			return;
		}

		$id = (int)$id;

		Debug2::debug( '🐞🗺️ blacklist delete [id] ' . $id );

		$wpdb->query( "UPDATE `$this->_tb` SET res = REPLACE( REPLACE( res, 'N', '-' ), 'B', '-' ) WHERE url = ( SELECT url FROM `$this->_tb_blacklist` WHERE id = '$id' )" );

		$wpdb->query( "DELETE FROM `$this->_tb_blacklist` WHERE id = '$id'" );
	}

	/**
	 * Empty blacklist
	 *
	 * @since  3.0
	 * @access public
	 */
	public function blacklist_empty() {
		global $wpdb;

		if ( ! $this->__data->tb_exist( 'crawler_blacklist' ) ) {
			return;
		}

		Debug2::debug( '🐞🗺️ Truncate blacklist' );

		$wpdb->query( "UPDATE `$this->_tb` SET res = REPLACE( REPLACE( res, 'N', '-' ), 'B', '-' )" );

		$wpdb->query( "TRUNCATE `$this->_tb_blacklist`" );
	}

	/**
	 * List blacklist
	 *
	 * @since  3.0
	 * @access public
	 */
	public function list_blacklist( $limit = false, $offset = false ) {
		global $wpdb;

		if ( ! $this->__data->tb_exist( 'crawler_blacklist' ) ) {
			return array();
		}

		$q = "SELECT * FROM `$this->_tb_blacklist` ORDER BY id DESC";

		if ( $limit !== false ) {
			if ( $offset === false ) {
				$total = $this->count_blacklist();
				$offset = Utility::pagination( $total, $limit, true );
			}
			$q .= " LIMIT %d, %d";
			$q = $wpdb->prepare( $q, $offset, $limit );
		}
		return $wpdb->get_results( $q, ARRAY_A );

	}

	/**
	 * Count blacklist
	 */
	public function count_blacklist() {
		global $wpdb;

		if ( ! $this->__data->tb_exist( 'crawler_blacklist' ) ) {
			return false;
		}

		$q = "SELECT COUNT(*) FROM `$this->_tb_blacklist`";
		return $wpdb->get_var( $q );
	}

	/**
	 * Empty sitemap
	 *
	 * @since  3.0
	 * @access public
	 */
	public function empty_map() {
		Data::get_instance()->tb_del( 'crawler' );

		$msg = __( 'Sitemap cleaned successfully', 'litespeed-cache' );
		Admin_Display::succeed( $msg );
	}

	/**
	 * List generated sitemap
	 *
	 * @since  3.0
	 * @access public
	 */
	public function list_map( $limit, $offset = false ) {
		global $wpdb;

		if ( ! $this->__data->tb_exist( 'crawler' ) ) {
			return array();
		}

		if ( $offset === false ) {
			$total = $this->count_map();
			$offset = Utility::pagination( $total, $limit, true );
		}


		$q = "SELECT * FROM `$this->_tb` ORDER BY id LIMIT %d, %d";
		return $wpdb->get_results( $wpdb->prepare( $q, $offset, $limit ), ARRAY_A );

	}

	/**
	 * Count sitemap
	 */
	public function count_map() {
		global $wpdb;

		if ( ! $this->__data->tb_exist( 'crawler' ) ) {
			return false;
		}

		$q = "SELECT COUNT(*) FROM `$this->_tb`";
		return $wpdb->get_var( $q );
	}

	/**
	 * Generate sitemap
	 *
	 * @since    1.1.0
	 * @access public
	 */
	public function gen() {
		$urls = $this->_gen();

		$msg = sprintf( __( 'Sitemap created successfully: %d items', 'litespeed-cache' ), $urls );
		Admin_Display::succeed( $msg );
	}

	/**
	 * Generate the sitemap
	 *
	 * @since    1.1.0
	 * @access private
	 */
	private function _gen() {
		global $wpdb;

		if ( ! $this->__data->tb_exist( 'crawler' ) ) {
			$this->__data->tb_create( 'crawler' );
		}

		if ( ! $this->__data->tb_exist( 'crawler_blacklist' ) ) {
			$this->__data->tb_create( 'crawler_blacklist' );
		}

		// use custom sitemap
		if ( $sitemap = Conf::val( Base::O_CRAWLER_SITEMAP ) ) {
			$urls = array();
			$offset = strlen( $this->_home_url );
			$sitemap_urls = false;

			try {
				$sitemap_urls = $this->_parse( $sitemap );
			} catch( \Exception $e ) {
				Debug2::debug( '🐞🗺️ ❌ failed to prase custom sitemap: ' . $e->getMessage() );
			}

			if ( is_array( $sitemap_urls ) && ! empty( $sitemap_urls ) ) {
				if ( Conf::val( Base::O_CRAWLER_DROP_DOMAIN ) ) {
					foreach ( $sitemap_urls as $val ) {
						if ( stripos( $val, $this->_home_url ) === 0 ) {
							$urls[] = substr( $val, $offset );
						}
					}
				}
				else {
					$urls = $sitemap_urls;
				}

				$urls = array_unique( $urls );
			}
		}
		else {
			$urls = $this->_build();
		}

		Debug2::debug( '🐞🗺️ Truncate sitemap' );
		$wpdb->query( "TRUNCATE `$this->_tb`" );

		Debug2::debug( '🐞🗺️ Generate sitemap' );

		// Filter URLs in blacklist
		$blacklist = $this->list_blacklist();

		$full_blacklisted = array();
		$partial_blacklisted = array();
		foreach ( $blacklist as $v ) {
			if ( strpos( $v[ 'res' ], '-' ) === false ) { // Full blacklisted
				$full_blacklisted[] = $v[ 'url' ];
			}
			else {
				// Replace existing reason
				$v[ 'reason' ] = explode( ',', $v[ 'reason' ] );
				$v[ 'reason' ] = array_map( function( $element ){ return $element ? 'Existed' : ''; }, $v[ 'reason' ] );
				$v[ 'reason' ] = implode( ',', $v[ 'reason' ] );
				$partial_blacklisted[ $v[ 'url' ] ] = array(
					'res' => $v[ 'res' ],
					'reason' => $v[ 'reason' ],
				);
			}
		}

		// Drop all blacklisted URLs
		$urls = array_diff( $urls, $full_blacklisted );

		// Default res & reason
		$crawler_count = count( Crawler::get_instance()->list_crawlers() );
		$default_res = str_repeat( '-', $crawler_count );
		$default_reason = $crawler_count > 1 ? str_repeat( ',', $crawler_count - 1 ) : '';

		$data = array();
		foreach ( $urls as $url ) {
			$data[] = $url;
			$data[] = array_key_exists( $url, $partial_blacklisted ) ? $partial_blacklisted[ $url ][ 'res' ] : $default_res;
			$data[] = array_key_exists( $url, $partial_blacklisted ) ? $partial_blacklisted[ $url ][ 'reason' ] : $default_reason;
		}

		foreach ( array_chunk( $data, 300 ) as $data2 ) {
			$this->_save( $data2 );
		}

		// Reset crawler
		Crawler::get_instance()->reset_pos();

		return count( $urls );
	}

	/**
	 * Save data to table
	 *
	 * @since 3.0
	 * @access private
	 */
	private function _save( $data, $fields = 'url,res,reason' ) {
		global $wpdb;

		if ( empty( $data ) ) {
			return;
		}

		$q = "INSERT INTO `$this->_tb` ( $fields ) VALUES ";

		// Add placeholder
		$q .= Utility::chunk_placeholder( $data, $fields );

		// Store data
		$wpdb->query( $wpdb->prepare( $q, $data ) );
	}

	/**
	 * Parse custom sitemap and return urls
	 *
	 * @since    1.1.1
	 * @access private
	 */
	private function _parse( $sitemap, $return_detail = true ) {
		/**
		 * Read via wp func to avoid allow_url_fopen = off
		 * @since  2.2.7
		 */
		$response = wp_remote_get( $sitemap, array( 'timeout' => $this->_conf_map_timeout ) );
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			Debug2::debug( '🐞🗺️ failed to read sitemap: ' . $error_message );

			throw new \Exception( 'Failed to remote read' );
		}

		$xml_object = simplexml_load_string( $response[ 'body' ] );
		if ( ! $xml_object ) {
			throw new \Exception( 'Failed to parse xml' );
		}

		if ( ! $return_detail ) {
			return true;
		}
		// start parsing
		$_urls = array();

		$xml_array = (array) $xml_object;
		if ( ! empty( $xml_array[ 'sitemap' ] ) ) { // parse sitemap set
			if ( is_object( $xml_array[ 'sitemap' ] ) ) {
				$xml_array[ 'sitemap' ] = (array) $xml_array[ 'sitemap' ];
			}
			if ( ! empty( $xml_array[ 'sitemap' ][ 'loc' ] ) ) { // is single sitemap
				$urls = $this->_parse( $xml_array[ 'sitemap' ][ 'loc' ] );
				if ( is_array( $urls ) && ! empty( $urls ) ) {
					$_urls = array_merge( $_urls, $urls );
				}
			}
			else {
				// parse multiple sitemaps
				foreach ( $xml_array[ 'sitemap' ] as $val ) {
					$val = (array) $val;
					if ( ! empty( $val[ 'loc' ] ) ) {
						$urls = $this->_parse( $val[ 'loc' ] ); // recursive parse sitemap
						if ( is_array( $urls ) && ! empty( $urls ) ) {
							$_urls = array_merge( $_urls, $urls );
						}
					}
				}
			}
		}
		elseif ( ! empty( $xml_array[ 'url' ] ) ) { // parse url set
			if ( is_object( $xml_array[ 'url' ] ) ) {
				$xml_array[ 'url' ] = (array) $xml_array[ 'url' ];
			}
			// if only 1 element
			if ( ! empty( $xml_array[ 'url' ][ 'loc' ] ) ) {
				$_urls[] = $xml_array[ 'url' ][ 'loc' ];
			}
			else {
				foreach ( $xml_array[ 'url' ] as $val ) {
					$val = (array) $val;
					if ( ! empty( $val[ 'loc' ] ) ) {
						$_urls[] = $val[ 'loc' ];
					}
				}
			}
		}

		return $_urls;
	}

	/**
	 * Generate all urls
	 *
	 * @since 1.1.0
	 * @access private
	 */
	private function _build($blacklist = array()) {
		global $wpdb;

		$show_pages = Conf::val( Base::O_CRAWLER_PAGES );

		$show_posts = Conf::val( Base::O_CRAWLER_POSTS );

		$show_cats = Conf::val( Base::O_CRAWLER_CATS );

		$show_tags = Conf::val( Base::O_CRAWLER_TAGS );

		switch ( Conf::val( Base::O_CRAWLER_ORDER_LINKS ) ) {
			case 1:
				$orderBy = " ORDER BY post_date ASC";
				break;

			case 2:
				$orderBy = " ORDER BY post_title DESC";
				break;

			case 3:
				$orderBy = " ORDER BY post_title ASC";
				break;

			case 0:
			default:
				$orderBy = " ORDER BY post_date DESC";
				break;
		}

		$post_type_array = array();
		if ( isset($show_pages) && $show_pages == 1 ) {
			$post_type_array[] = 'page';
		}

		if ( isset($show_posts) && $show_posts == 1 ) {
			$post_type_array[] = 'post';
		}

		if ( $excludeCptArr = Conf::val( Base::O_CRAWLER_EXC_CPT ) ) {
			$cptArr = get_post_types();
			$cptArr = array_diff($cptArr, array('post', 'page'));
			$cptArr = array_diff($cptArr, $excludeCptArr);
			$post_type_array = array_merge($post_type_array, $cptArr);
		}

		if ( ! empty($post_type_array) ) {
			Debug2::debug( '🐞🗺️ Crawler sitemap log: post_type is ' . implode( ',', $post_type_array ) );

			$q = "SELECT ID, post_date FROM $wpdb->posts where post_type IN (" . implode( ',', array_fill( 0, count( $post_type_array ), '%s' ) ) . ") AND post_status='publish' $orderBy";
			$results = $wpdb->get_results( $wpdb->prepare( $q, $post_type_array ) );

			foreach ( $results as $result ){
				$slug = str_replace( $this->_home_url, '', get_permalink( $result->ID ) );
				if ( ! in_array($slug, $blacklist) ) {
					$this->_urls[] = $slug;
				}
			}
		}

		//Generate Categories Link if option checked
		if ( isset($show_cats) && $show_cats == 1 ) {
			$cats = get_terms("category", array("hide_empty"=>true, "hierarchical"=>false));
			if ( $cats && is_array($cats) && count($cats) > 0 ) {
				foreach ( $cats as $cat ) {
					$slug = str_replace( $this->_home_url, '', get_category_link( $cat->term_id ) );
					if ( ! in_array($slug, $blacklist) ){
						$this->_urls[] = $slug;//var_dump($slug);exit;//todo: check permalink
					}
				}
			}
		}

		//Generate tags Link if option checked
		if ( isset($show_tags) && $show_tags == 1 ) {
			$tags = get_terms("post_tag", array("hide_empty"=>true, "hierarchical"=>false));
			if ( $tags && is_array($tags) && count($tags) > 0 ) {
				foreach ( $tags as $tag ) {
					$slug = str_replace( $this->_home_url, '', get_tag_link( $tag->term_id ) );
					if ( ! in_array($slug, $blacklist) ) {
						$this->_urls[] = $slug;
					}
				}
			}
		}

		return apply_filters('litespeed_crawler_sitemap', $this->_urls);
	}
}
