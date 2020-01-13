<?php
/**
 * The Crawler Sitemap Class
 *
 * @since      	1.1.0
 */
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

class Crawler_Map extends Instance
{
	const BM_MISS = 1;
	const BM_HIT = 2;
	const BM_BLACKLIST = 4;

	protected static $_instance;
	private $_home_url; // Used to simplify urls
	private $_tb;
	private $__data;

	protected $_urls = array();

	/**
	 * Instantiate the class
	 *
	 * @since 1.1.0
	 * @access protected
	 */
	protected function __construct()
	{
		$this->_home_url = get_home_url();
		$this->__data = Data::get_instance();
		$this->_tb = $this->__data->tb( 'crawler' );
		$this->_tb_blacklist = $this->__data->tb( 'crawler_blacklist' );
	}

	/**
	 * Save URLs crawl status into DB
	 *
	 * @since  3.0
	 * @access public
	 */
	public function save_map_status( $list, $curr_crawler )
	{
		global $wpdb;

		// Replace current crawler's position
		$curr_crawler = (int) $curr_crawler;
		foreach ( $list as $bit => $ids ) {
			if ( ! $ids ) {
				continue;
			}
			Log::debug( "🐞🗺️ [Crawler_map] Update list [crawler] $curr_crawler [bit] $bit [count] " . count( $ids ) );
			$res = "CONCAT( LEFT( res, $curr_crawler ), '$bit', RIGHT( res, LENGTH( res )-1-$curr_crawler ) )";
			$wpdb->query( "UPDATE `$this->_tb` SET res = $res WHERE id IN ( " . implode( ',', array_map( 'intval', array_keys( $ids ) ) ) . " )" );

			// Add blacklist
			if ( $bit == 'B' ) {
				$q = "SELECT id, url FROM `$this->_tb_blacklist` WHERE url IN (" . implode( ',', array_fill( 0, count( $ids ), '%s' ) ) . ")";
				$existing = $wpdb->get_results( $wpdb->prepare( $q, $ids ), ARRAY_A );
				// Update current crawler status tag in existing blacklist
				if ( $existing ) {
					Utility::compatibility();
					$wpdb->query( "UPDATE `$this->_tb_blacklist` SET res = $res WHERE id IN ( " . implode( ',', array_column( $existing, 'id' ) ) . " )" );
				}

				// Append new blacklist
				if ( count( $ids ) > count( $existing ) ) {
					$new_urls = array_diff( $ids, array_column( $existing, 'url') );

					$q = "INSERT INTO `$this->_tb_blacklist` ( url, res ) VALUES " . implode( ',', array_fill( 0, count( $new_urls ), '(%s,%s)' ) );
					$data = array();
					$str = array_fill( 0, count( Crawler::get_instance()->list_crawlers() ), '-' );
					$str[ $curr_crawler ] = 'B';
					$str = implode( '', $str );
					foreach ( $new_urls as $v ) {
						$data[] = $v;
						$data[] = $str;
					}
					$wpdb->query( $wpdb->prepare( $q, $data ) );
				}
			}
			$list[ $bit ] = array();
		}

		return $list;
	}

	/**
	 * List blacklist
	 *
	 * @since  3.0
	 * @access public
	 */
	public function list_blacklist( $limit, $offset = false )
	{
		global $wpdb;

		if ( ! $this->__data->tb_exist( 'crawler_blacklist' ) ) {
			return array();
		}

		if ( $offset === false ) {
			$total = $this->count_blacklist();
			$offset = Utility::pagination( $total, $limit, true );
		}


		$q = "SELECT * FROM `$this->_tb_blacklist` ORDER BY id DESC LIMIT %d, %d";
		return $wpdb->get_results( $wpdb->prepare( $q, $offset, $limit ), ARRAY_A );

	}

	/**
	 * Count blacklist
	 */
	public function count_blacklist()
	{
		global $wpdb;

		if ( ! $this->__data->tb_exist( 'crawler_blacklist' ) ) {
			return false;
		}

		$q = "SELECT COUNT(*) FROM `$this->_tb_blacklist`";
		return $wpdb->get_var( $q );
	}

	/**
	 * List generated sitemap
	 *
	 * @since  3.0
	 * @access public
	 */
	public function list( $limit, $offset = false )
	{
		global $wpdb;

		if ( ! $this->__data->tb_exist( 'crawler' ) ) {
			return array();
		}

		if ( $offset === false ) {
			$total = $this->count();
			$offset = Utility::pagination( $total, $limit, true );
		}


		$q = "SELECT * FROM `$this->_tb` ORDER BY id LIMIT %d, %d";
		return $wpdb->get_results( $wpdb->prepare( $q, $offset, $limit ), ARRAY_A );

	}

	/**
	 * Count sitemap
	 */
	public function count()
	{
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
	public function gen()
	{
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
	private function _gen()
	{
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
				Log::debug( '🐞🗺️ ❌ failed to prase custom sitemap: ' . $e->getMessage() );
			}

			if ( is_array( $sitemap_urls ) && ! empty( $sitemap_urls ) ) {
				foreach ( $sitemap_urls as $val ) {
					if ( stripos( $val, $this->_home_url ) === 0 ) {
						$urls[] = substr( $val, $offset );
					}
				}

				$urls = array_unique( $urls );
			}
		}
		else {
			$urls = $this->_build();
		}

		Log::debug( '🐞🗺️ Truncate sitemap' );
		$wpdb->query( "TRUNCATE `$this->_tb`" );

		Log::debug( '🐞🗺️ Generate sitemap' );

		foreach ( array_chunk( $urls, 100 ) as $urls2 ) {
			$this->_save( $urls2 );
		}

		// Rest all status
		$res = str_repeat( '-', count( Crawler::get_instance()->list_crawlers() ) );
		$wpdb->query( "UPDATE `$this->_tb` SET res='$res'" );

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
	private function _save( $data, $fields = 'url' )
	{
		global $wpdb;

		if ( empty( $data ) ) {
			return;
		}

		$division = substr_count( $fields, ',' ) + 1;

		$q = "INSERT INTO `$this->_tb` ( $fields ) VALUES ";

		// Add placeholder
		$q .= Utility::chunk_placeholder( $data, $division );

		// Store data
		$wpdb->query( $wpdb->prepare( $q, $data ) );
	}

	/**
	 * Parse custom sitemap and return urls
	 *
	 * @since    1.1.1
	 * @access private
	 */
	private function _parse( $sitemap, $return_detail = true )
	{
		/**
		 * Read via wp func to avoid allow_url_fopen = off
		 * @since  2.2.7
		 */
		$response = wp_remote_get( $sitemap, array( 'timeout' => 15 ) );
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			Log::debug( '🐞🗺️ failed to read sitemap: ' . $error_message );

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
	private function _build($blacklist = array())
	{
		global $wpdb;

		$optionOrderBy = Conf::val( Base::O_CRAWLER_ORDER_LINKS );

		$show_pages = Conf::val( Base::O_CRAWLER_PAGES );

		$show_posts = Conf::val( Base::O_CRAWLER_POSTS );

		$show_cats = Conf::val( Base::O_CRAWLER_CATS );

		$show_tags = Conf::val( Base::O_CRAWLER_TAGS );

		switch ( $optionOrderBy ) {
			case 'date_asc':
				$orderBy = " ORDER BY post_date ASC";
				break;

			case 'alpha_desc':
				$orderBy = " ORDER BY post_title DESC";
				break;

			case 'alpha_asc':
				$orderBy = " ORDER BY post_title ASC";
				break;

			case 'date_desc':
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
			Log::debug( '🐞🗺️ Crawler sitemap log: post_type is ' . implode( ',', $post_type_array ) );

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
