<?php
/**
 * The Crawler Sitemap Class.
 *
 * @package     LiteSpeed
 * @since       1.1.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Class Crawler_Map
 *
 * Maintains and persists crawler sitemap/blacklist state, parses custom sitemaps,
 * and exposes helpers to query & mutate crawler results.
 */
class Crawler_Map extends Root {

	const LOG_TAG = '🐞🗺️';

	const BM_MISS      = 1;
	const BM_HIT       = 2;
	const BM_BLACKLIST = 4;

	/**
	 * Site URL used to simplify URLs.
	 *
	 * @var string
	 */
	private $_site_url;

	/**
	 * Main crawler table name.
	 *
	 * @var string
	 */
	private $_tb;

	/**
	 * Crawler blacklist table name.
	 *
	 * @var string
	 */
	private $_tb_blacklist;

	/**
	 * Data service instance.
	 *
	 * @var \LiteSpeed\Data
	 */
	private $__data;

	/**
	 * Timeout (seconds) when fetching sitemaps.
	 *
	 * @var int
	 */
	private $_conf_map_timeout;

	/**
	 * Collected URLs from parsed sitemaps.
	 *
	 * @var array<int,string>
	 */
	private $_urls = [];

	/**
	 * Instantiate the class.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->_site_url     = get_site_url();
		$this->__data        = Data::cls();
		$this->_tb           = $this->__data->tb( 'crawler' );
		$this->_tb_blacklist = $this->__data->tb( 'crawler_blacklist' );
		// Specify the timeout while parsing the sitemap.
		$this->_conf_map_timeout = defined( 'LITESPEED_CRAWLER_MAP_TIMEOUT' ) ? constant( 'LITESPEED_CRAWLER_MAP_TIMEOUT' ) : 180;
	}

	/**
	 * Save URLs crawl status into DB.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @param array<int,array<int,array{url:string,code:int}>> $items         Map of bit => [ id => [url, code] ].
	 * @param int                                              $curr_crawler  Current crawler index (0-based).
	 * @return array<int,array>
	 */
	public function save_map_status( $items, $curr_crawler ) {
		global $wpdb;
		Utility::compatibility();

		$total_crawler     = count( Crawler::cls()->list_crawlers() );
		$total_crawler_pos = $total_crawler - 1;

		// Replace current crawler's position.
		$curr_crawler = (int) $curr_crawler;
		foreach ( $items as $bit => $ids ) {
			// $ids = [ id => [ url, code ], ... ].
			if ( ! $ids ) {
				continue;
			}
			self::debug( 'Update map [crawler] ' . $curr_crawler . ' [bit] ' . $bit . ' [count] ' . count( $ids ) );

			// Update res first, then reason
			$right_pos = $total_crawler_pos - $curr_crawler;
			$id_all    = implode(',', array_map('intval', array_keys($ids)));

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query("UPDATE `$this->_tb` SET res = CONCAT( LEFT( res, $curr_crawler ), '$bit', RIGHT( res, $right_pos ) ) WHERE id IN ( $id_all )");

			// Add blacklist
			if (Crawler::STATUS_BLACKLIST === $bit || Crawler::STATUS_NOCACHE === $bit) {
				$q = "SELECT a.id, a.url FROM `$this->_tb_blacklist` a LEFT JOIN `$this->_tb` b ON b.url=a.url WHERE b.id IN ( $id_all )";
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
				$existing = $wpdb->get_results($q, ARRAY_A);
				// Update current crawler status tag in existing blacklist
				if ($existing) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
					$count = $wpdb->query("UPDATE `$this->_tb_blacklist` SET res = CONCAT( LEFT( res, $curr_crawler ), '$bit', RIGHT( res, $right_pos ) ) WHERE id IN ( " . implode(',', array_column($existing, 'id')) . ' )');
					self::debug('Update blacklist [count] ' . $count);
				}

				// Append new blacklist
				if (count($ids) > count($existing)) {
					$new_urls = array_diff(array_column($ids, 'url'), array_column($existing, 'url'));

					self::debug('Insert into blacklist [count] ' . count($new_urls));

					$q                  = "INSERT INTO `$this->_tb_blacklist` ( url, res, reason ) VALUES " . implode(',', array_fill(0, count($new_urls), '( %s, %s, %s )'));
					$data               = array();
					$res                = array_fill(0, $total_crawler, '-');
					$res[$curr_crawler] = $bit;
					$res                = implode('', $res);
					$default_reason     = $total_crawler > 1 ? str_repeat(',', $total_crawler - 1) : ''; // Pre-populate default reason value first, update later
					foreach ($new_urls as $url) {
						$data[] = $url;
						$data[] = $res;
						$data[] = $default_reason;
					}
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
					$wpdb->query($wpdb->prepare($q, $data));
				}
			}

			// Update sitemap reason w/ HTTP code.
			$reason_array = [];
			foreach ( $ids as $row_id => $row ) {
				$code = (int) $row['code'];
				if ( empty( $reason_array[ $code ] ) ) {
					$reason_array[ $code ] = [];
				}
				$reason_array[ $code ][] = (int) $row_id;
			}

			foreach ($reason_array as $code => $v2) {
				// Complement comma
				if ($curr_crawler) {
					$code = ',' . $code;
				}
				if ($curr_crawler < $total_crawler_pos) {
					$code .= ',';
				}

				// phpcs:ignore WordPress.DB
				$count = $wpdb->query( "UPDATE `$this->_tb` SET reason=CONCAT(SUBSTRING_INDEX(reason, ',', $curr_crawler), '$code', SUBSTRING_INDEX(reason, ',', -$right_pos)) WHERE id IN (" . implode(',', $v2) . ')' );

				self::debug("Update map reason [code] $code [pos] left $curr_crawler right -$right_pos [count] $count");

				// Update blacklist reason
				if (Crawler::STATUS_BLACKLIST === $bit || Crawler::STATUS_NOCACHE === $bit) {
					// phpcs:ignore WordPress.DB
					$count = $wpdb->query( "UPDATE `$this->_tb_blacklist` a LEFT JOIN `$this->_tb` b ON b.url = a.url SET a.reason=CONCAT(SUBSTRING_INDEX(a.reason, ',', $curr_crawler), '$code', SUBSTRING_INDEX(a.reason, ',', -$right_pos)) WHERE b.id IN (" . implode(',', $v2) . ')' );

					self::debug("Update blacklist [code] $code [pos] left $curr_crawler right -$right_pos [count] $count");
				}
			}

			// Reset list.
			$items[ $bit ] = [];
		}

		return $items;
	}

	/**
	 * Add one record to blacklist.
	 * NOTE: $id is sitemap table ID.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @param int $id Sitemap row ID.
	 * @return void
	 */
	public function blacklist_add( $id ) {
		global $wpdb;

		$id = (int) $id;

		// Build res&reason.
		$total_crawler = count( Crawler::cls()->list_crawlers() );
		$res           = str_repeat(Crawler::STATUS_BLACKLIST, $total_crawler);
		$reason        = implode(',', array_fill(0, $total_crawler, 'Man'));

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row("SELECT a.url, b.id FROM `$this->_tb` a LEFT JOIN `$this->_tb_blacklist` b ON b.url = a.url WHERE a.id = '$id'", ARRAY_A);
		if (!$row) {
			self::debug('blacklist failed to add [id] ' . $id);
			return;
		}

		self::debug('Add to blacklist [url] ' . $row['url']);

		$q = "UPDATE `$this->_tb` SET res = %s, reason = %s WHERE id = %d";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query($wpdb->prepare($q, array( $res, $reason, $id )));

		if ($row['id']) {
			$q = "UPDATE `$this->_tb_blacklist` SET res = %s, reason = %s WHERE id = %d";
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query($wpdb->prepare($q, array( $res, $reason, $row['id'] )));
		} else {
			$q = "INSERT INTO `$this->_tb_blacklist` (url, res, reason) VALUES (%s, %s, %s)";
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query($wpdb->prepare($q, array( $row['url'], $res, $reason )));
		}
	}

	/**
	 * Delete one record from blacklist.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @param int $id Blacklist row ID.
	 * @return void
	 */
	public function blacklist_del( $id ) {
		global $wpdb;
		if ( ! $this->__data->tb_exist( 'crawler_blacklist' ) ) {
			return;
		}

		$id = (int) $id;
		self::debug('blacklist delete [id] ' . $id);

		$sql = sprintf(
			"UPDATE `%s` SET res=REPLACE(REPLACE(res, '%s', '-'), '%s', '-') WHERE url=(SELECT url FROM `%s` WHERE id=%d)",
			$this->_tb,
			Crawler::STATUS_NOCACHE,
			Crawler::STATUS_BLACKLIST,
			$this->_tb_blacklist,
			$id
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query($sql);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query("DELETE FROM `$this->_tb_blacklist` WHERE id='$id'");
	}

	/**
	 * Empty blacklist.
	 *
	 * @since  3.0
	 * @access public
	 * @return void
	 */
	public function blacklist_empty() {
		global $wpdb;

		if ( ! $this->__data->tb_exist( 'crawler_blacklist' ) ) {
			return;
		}

		self::debug('Truncate blacklist');
		$sql = sprintf("UPDATE `%s` SET res=REPLACE(REPLACE(res, '%s', '-'), '%s', '-')", $this->_tb, Crawler::STATUS_NOCACHE, Crawler::STATUS_BLACKLIST);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query($sql);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query("TRUNCATE `$this->_tb_blacklist`");
	}

	/**
	 * List blacklist.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @param int|false $limit  Number of rows to fetch, or false for all.
	 * @param int|false $offset Offset for pagination, or false to auto-calc.
	 * @return array<int,array<string,mixed>>
	 */
	public function list_blacklist( $limit = false, $offset = false ) {
		global $wpdb;

		if ( ! $this->__data->tb_exist( 'crawler_blacklist' ) ) {
			return [];
		}

		$q = "SELECT * FROM `$this->_tb_blacklist` ORDER BY id DESC";

		if ( false !== $limit ) {
			if ( false === $offset ) {
				$total  = $this->count_blacklist();
				$offset = Utility::pagination($total, $limit, true);
			}
			$q .= ' LIMIT %d, %d';
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$q = $wpdb->prepare($q, $offset, $limit);
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results($q, ARRAY_A);
	}

	/**
	 * Count blacklist.
	 *
	 * @return int|false
	 */
	public function count_blacklist() {
		global $wpdb;

		if ( ! $this->__data->tb_exist( 'crawler_blacklist' ) ) {
			return false;
		}

		$q = "SELECT COUNT(*) FROM `$this->_tb_blacklist`";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_var($q);
	}

	/**
	 * Empty sitemap.
	 *
	 * @since  3.0
	 * @access public
	 * @return void
	 */
	public function empty_map() {
		Data::cls()->tb_del( 'crawler' );

		$msg = __( 'Sitemap cleaned successfully', 'litespeed-cache' );
		Admin_Display::success( $msg );
	}

	/**
	 * List generated sitemap.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @param int      $limit  Number of rows per page.
	 * @param int|bool $offset Offset for pagination, or false to auto-calc.
	 * @return array<int,array<string,mixed>>
	 */
	public function list_map( $limit, $offset = false ) {
		global $wpdb;

		if ( ! $this->__data->tb_exist( 'crawler' ) ) {
			return [];
		}

		if ( false === $offset ) {
			$total  = $this->count_map();
			$offset = Utility::pagination($total, $limit, true);
		}

		$type = Router::verify_type();

		$req_uri_like = '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST['kw'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$kw = sanitize_text_field( wp_unslash( $_POST['kw'] ) );
			$q  = "SELECT * FROM `$this->_tb` WHERE url LIKE %s";
			if ( 'hit' === $type ) {
				$q .= " AND res LIKE '%" . Crawler::STATUS_HIT . "%'";
			}
			if ( 'miss' === $type ) {
				$q .= " AND res LIKE '%" . Crawler::STATUS_MISS . "%'";
			}
			if ( 'blacklisted' === $type ) {
				$q .= " AND res LIKE '%" . Crawler::STATUS_BLACKLIST . "%'";
			}
			$q           .= ' ORDER BY id LIMIT %d, %d';
			$req_uri_like = '%' . $wpdb->esc_like( $kw ) . '%';
			return $wpdb->get_results( $wpdb->prepare( $q, $req_uri_like, $offset, $limit ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		}

		$q = "SELECT * FROM `$this->_tb`";
		if ( 'hit' === $type ) {
			$q .= " WHERE res LIKE '%" . Crawler::STATUS_HIT . "%'";
		}
		if ( 'miss' === $type ) {
			$q .= " WHERE res LIKE '%" . Crawler::STATUS_MISS . "%'";
		}
		if ( 'blacklisted' === $type ) {
			$q .= " WHERE res LIKE '%" . Crawler::STATUS_BLACKLIST . "%'";
		}
		$q .= ' ORDER BY id LIMIT %d, %d';

		return $wpdb->get_results( $wpdb->prepare( $q, $offset, $limit ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Count sitemap.
	 *
	 * @return int|false
	 */
	public function count_map() {
		global $wpdb;

		if ( ! $this->__data->tb_exist( 'crawler' ) ) {
			return false;
		}

		$q = "SELECT COUNT(*) FROM `$this->_tb`";

		$type = Router::verify_type();
		if ( 'hit' === $type ) {
			$q .= " WHERE res LIKE '%" . Crawler::STATUS_HIT . "%'";
		}
		if ( 'miss' === $type ) {
			$q .= " WHERE res LIKE '%" . Crawler::STATUS_MISS . "%'";
		}
		if ( 'blacklisted' === $type ) {
			$q .= " WHERE res LIKE '%" . Crawler::STATUS_BLACKLIST . "%'";
		}

		return $wpdb->get_var( $q ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Generate sitemap.
	 *
	 * @since    1.1.0
	 * @access public
	 *
	 * @param bool $manual Whether triggered manually from UI.
	 * @return void
	 */
	public function gen( $manual = false ) {
		$count = $this->_gen();

		if ( ! $count ) {
			Admin_Display::error( __( 'No valid sitemap parsed for crawler.', 'litespeed-cache' ) );
			return;
		}

		if ( ! wp_doing_cron() && $manual ) {
			$msg = sprintf( __( 'Sitemap created successfully: %d items', 'litespeed-cache' ), $count );
			Admin_Display::success( $msg );
		}
	}

	/**
	 * Generate the sitemap.
	 *
	 * @since    1.1.0
	 * @access private
	 * @return int|false Number of URLs generated or false on failure.
	 */
	private function _gen() {
		global $wpdb;

		if ( ! $this->__data->tb_exist( 'crawler' ) ) {
			$this->__data->tb_create( 'crawler' );
		}

		if ( ! $this->__data->tb_exist( 'crawler_blacklist' ) ) {
			$this->__data->tb_create( 'crawler_blacklist' );
		}

		// Use custom sitemap.
		$sitemap = $this->conf( Base::O_CRAWLER_SITEMAP );
		if ( ! $sitemap ) {
			return false;
		}

		$offset  = strlen( $this->_site_url );
		$sitemap = Utility::sanitize_lines( $sitemap );

		try {
			foreach ( $sitemap as $this_map ) {
				$this->_parse( $this_map );
			}
		} catch ( \Exception $e ) {
			self::debug( '❌ failed to parse custom sitemap: ' . $e->getMessage() );
		}

		if ( is_array( $this->_urls ) && ! empty( $this->_urls ) ) {
			if ( defined( 'LITESPEED_CRAWLER_DROP_DOMAIN' ) && constant( 'LITESPEED_CRAWLER_DROP_DOMAIN' ) ) {
				foreach ( $this->_urls as $k => $v ) {
					if ( 0 !== stripos( $v, $this->_site_url ) ) {
						unset( $this->_urls[ $k ] );
						continue;
					}
					$this->_urls[ $k ] = substr( $v, $offset );
				}
			}

			$this->_urls = array_values( array_unique( $this->_urls ) );
		}

		self::debug( 'Truncate sitemap' );
		$wpdb->query( "TRUNCATE `$this->_tb`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		self::debug( 'Generate sitemap' );

		// Filter URLs in blacklist.
		$blacklist = $this->list_blacklist();

		$full_blacklisted    = [];
		$partial_blacklisted = [];
		foreach ( $blacklist as $v ) {
			if ( false === strpos( $v['res'], '-' ) ) {
				// Full blacklisted.
				$full_blacklisted[] = $v['url'];
			} else {
				// Replace existing reason.
				$v['reason']                      = explode( ',', $v['reason'] );
				$v['reason']                      = array_map(
					function ( $element ) {
						return $element ? 'Existed' : '';
					},
					$v['reason']
				);
				$v['reason']                      = implode( ',', $v['reason'] );
				$partial_blacklisted[ $v['url'] ] = [
					'res'    => $v['res'],
					'reason' => $v['reason'],
				];
			}
		}

		// Drop all blacklisted URLs.
		$this->_urls = array_diff( $this->_urls, $full_blacklisted );

		// Default res & reason.
		$crawler_count  = count( Crawler::cls()->list_crawlers() );
		$default_res    = str_repeat( '-', $crawler_count );
		$default_reason = $crawler_count > 1 ? str_repeat( ',', $crawler_count - 1 ) : '';

		$data = [];
		foreach ( $this->_urls as $url ) {
			$data[] = $url;
			$data[] = array_key_exists( $url, $partial_blacklisted ) ? $partial_blacklisted[ $url ]['res'] : $default_res;
			$data[] = array_key_exists( $url, $partial_blacklisted ) ? $partial_blacklisted[ $url ]['reason'] : $default_reason;
		}

		foreach ( array_chunk( $data, 300 ) as $data2 ) {
			$this->_save( $data2 );
		}

		// Reset crawler.
		Crawler::cls()->reset_pos();

		return count( $this->_urls );
	}

	/**
	 * Save data to table.
	 *
	 * @since 3.0
	 * @access private
	 *
	 * @param array<int,string> $data   Flat array (url,res,reason, url,res,reason, ...).
	 * @param string            $fields Fields list for insert (default url,res,reason).
	 * @return void
	 */
	private function _save( $data, $fields = 'url,res,reason' ) {
		global $wpdb;

		if ( empty( $data ) ) {
			return;
		}

		$q = "INSERT INTO `$this->_tb` ( {$fields} ) VALUES ";

		// Add placeholder.
		$q .= Utility::chunk_placeholder( $data, $fields );

		// Store data.
		$wpdb->query( $wpdb->prepare( $q, $data ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Parse custom sitemap and collect urls.
	 *
	 * @since    1.1.1
	 * @access private
	 *
	 * @param string $sitemap Absolute sitemap URL.
	 * @return void
	 * @throws \Exception If remote read or parsing fails.
	 */
	private function _parse( $sitemap ) {
		/**
		 * Read via wp func to avoid allow_url_fopen = off
		 *
		 * @since  2.2.7
		 */
		$response = wp_safe_remote_get(
			$sitemap,
			[
				'timeout'   => $this->_conf_map_timeout,
				'sslverify' => false,
			]
		);
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			self::debug( 'failed to read sitemap: ' . $error_message );
			throw new \Exception( 'Failed to remote read ' . esc_url( $sitemap ) );
		}

		$xml_object = simplexml_load_string($response['body'], null, LIBXML_NOCDATA);
		if (!$xml_object) {
			if ($this->_urls) {
				return;
			}
			throw new \Exception('Failed to parse xml ' . esc_url( $sitemap ));
		}

		// start parsing.
		$xml_array = (array) $xml_object;
		if ( ! empty( $xml_array['sitemap'] ) ) {
			// parse sitemap set.
			if ( is_object( $xml_array['sitemap'] ) ) {
				$xml_array['sitemap'] = (array) $xml_array['sitemap'];
			}

			if ( ! empty( $xml_array['sitemap']['loc'] ) ) {
				// is single sitemap.
				$this->_parse( (string) $xml_array['sitemap']['loc'] );
			} else {
				// parse multiple sitemaps.
				foreach ( (array) $xml_array['sitemap'] as $val ) {
					$val = (array) $val;
					if ( ! empty( $val['loc'] ) ) {
						$this->_parse( (string) $val['loc'] ); // recursive parse sitemap.
					}
				}
			}
		} elseif ( ! empty( $xml_array['url'] ) ) {
			// parse url set.
			if ( is_object( $xml_array['url'] ) ) {
				$xml_array['url'] = (array) $xml_array['url'];
			}
			// if only 1 element.
			if ( ! empty( $xml_array['url']['loc'] ) ) {
				$this->_urls[] = (string) $xml_array['url']['loc'];
			} else {
				foreach ( (array) $xml_array['url'] as $val ) {
					$val = (array) $val;
					if ( ! empty( $val['loc'] ) ) {
						$this->_urls[] = (string) $val['loc'];
					}
				}
			}
		}
	}
}
