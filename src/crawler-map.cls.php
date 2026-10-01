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

			// Failure: bump this crawler position '-' -> '1' -> ... -> B/N at threshold.
			if (Crawler::STATUS_BLACKLIST === $bit || Crawler::STATUS_NOCACHE === $bit) {
				$fail_urls = array_values(array_unique(array_column($ids, 'url')));

				// Find existing rows by URL directly (the crawler map table is transient).
				$placeholders = implode(',', array_fill(0, count($fail_urls), '%s'));
				// phpcs:ignore WordPress.DB
				$existing = $wpdb->get_results($wpdb->prepare("SELECT id, url, res FROM `$this->_tb_blacklist` WHERE url IN ( $placeholders )", $fail_urls), ARRAY_A);

				// Bump tracked rows.
				foreach ($existing as $row) {
					$new_res = self::_bump_blacklist_res($row['res'], $curr_crawler, $total_crawler, $bit);
					if ($new_res !== $row['res']) {
						// phpcs:ignore WordPress.DB
						$wpdb->query($wpdb->prepare("UPDATE `$this->_tb_blacklist` SET res = %s WHERE id = %d", $new_res, (int) $row['id']));
					}
				}

				// Insert first-failure rows for untracked URLs.
				$new_urls = array_values(array_diff($fail_urls, array_column($existing, 'url')));
				if ($new_urls) {
					self::debug('Insert into blacklist [count] ' . count($new_urls));

					$res            = self::_bump_blacklist_res(str_repeat('-', $total_crawler), $curr_crawler, $total_crawler, $bit);
					$default_reason = $total_crawler > 1 ? str_repeat(',', $total_crawler - 1) : ''; // Pre-populate default reason value first, update later
					$q              = "INSERT INTO `$this->_tb_blacklist` ( url, res, reason ) VALUES " . implode(',', array_fill(0, count($new_urls), '( %s, %s, %s )'));
					$data           = [];
					foreach ($new_urls as $url) {
						$data[] = $url;
						$data[] = $res;
						$data[] = $default_reason;
					}
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
					$wpdb->query($wpdb->prepare($q, $data));
				}
			}

			// Success: clear this position; drop the row when no markers remain. 'Man' rows are permanent.
			if (Crawler::STATUS_HIT === $bit || Crawler::STATUS_MISS === $bit) {
				$ok_urls      = array_values(array_unique(array_column($ids, 'url')));
				$placeholders = implode(',', array_fill(0, count($ok_urls), '%s'));
				// phpcs:ignore WordPress.DB
				$existing = $wpdb->get_results($wpdb->prepare("SELECT id, res, reason FROM `$this->_tb_blacklist` WHERE url IN ( $placeholders )", $ok_urls), ARRAY_A);
				foreach ($existing as $row) {
					if (false !== strpos((string) $row['reason'], 'Man')) {
						continue;
					}
					$new_res                = substr( str_pad((string) $row['res'], $total_crawler, '-'), 0, $total_crawler );
					$new_res[$curr_crawler] = '-';
					if ('' === trim($new_res, '-')) {
						// phpcs:ignore WordPress.DB
						$wpdb->query($wpdb->prepare("DELETE FROM `$this->_tb_blacklist` WHERE id = %d", (int) $row['id']));
					} elseif ($new_res !== $row['res']) {
						// phpcs:ignore WordPress.DB
						$wpdb->query($wpdb->prepare("UPDATE `$this->_tb_blacklist` SET res = %s WHERE id = %d", $new_res, (int) $row['id']));
					}
				}
			}

			// Update reason w/ HTTP code: map table by id, blacklist by URL.
			$reason_array = [];
			foreach ( $ids as $row_id => $row ) {
				$code = (int) $row['code'];
				if ( empty( $reason_array[ $code ] ) ) {
					$reason_array[ $code ] = [ 'ids' => [], 'urls' => [] ];
				}
				$reason_array[ $code ]['ids'][]  = (int) $row_id;
				$reason_array[ $code ]['urls'][] = $row['url'];
			}

			foreach ($reason_array as $code => $grp) {
				// Complement comma
				if ($curr_crawler) {
					$code = ',' . $code;
				}
				if ($curr_crawler < $total_crawler_pos) {
					$code .= ',';
				}

				// phpcs:ignore WordPress.DB
				$count = $wpdb->query( "UPDATE `$this->_tb` SET reason=CONCAT(SUBSTRING_INDEX(reason, ',', $curr_crawler), '$code', SUBSTRING_INDEX(reason, ',', -$right_pos)) WHERE id IN (" . implode(',', $grp['ids']) . ')' );

				self::debug("Update map reason [code] $code [pos] left $curr_crawler right -$right_pos [count] $count");

				// Update blacklist reason by URL.
				if (Crawler::STATUS_BLACKLIST === $bit || Crawler::STATUS_NOCACHE === $bit) {
					$b_urls       = array_values(array_unique($grp['urls']));
					$placeholders = implode(',', array_fill(0, count($b_urls), '%s'));
					// phpcs:ignore WordPress.DB
					$count = $wpdb->query( $wpdb->prepare( "UPDATE `$this->_tb_blacklist` SET reason=CONCAT(SUBSTRING_INDEX(reason, ',', $curr_crawler), '$code', SUBSTRING_INDEX(reason, ',', -$right_pos)) WHERE url IN ( $placeholders )", $b_urls ) );

					self::debug("Update blacklist [code] $code [pos] left $curr_crawler right -$right_pos [count] $count");
				}
			}

			// Reset list.
			$items[ $bit ] = [];
		}

		return $items;
	}

	/**
	 * Bump one crawler position's failure marker: '-' -> '1' -> ... -> B/N at threshold.
	 * A position already at B/N is left untouched.
	 *
	 * @since 7.9
	 * @param string $res           Per-crawler marker string.
	 * @param int    $pos           Position to bump.
	 * @param int    $total_crawler Crawler count.
	 * @param string $giveup_char   Char written at threshold (B or N).
	 * @return string Updated marker string.
	 */
	private static function _bump_blacklist_res( $res, $pos, $total_crawler, $giveup_char ) {
		// Pad/truncate to the current crawler count.
		$res = substr( str_pad( (string) $res, $total_crawler, '-' ), 0, $total_crawler );
		$cur = $res[ $pos ];
		if ( Crawler::STATUS_BLACKLIST === $cur || Crawler::STATUS_NOCACHE === $cur ) {
			// Already given up at this position.
			return $res;
		}
		$next        = ctype_digit( $cur ) ? ( (int) $cur + 1 ) : 1;
		$res[ $pos ] = $next >= Crawler::BLACKLIST_THRESHOLD ? $giveup_char : (string) $next;
		return $res;
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
		$wpdb->query($wpdb->prepare($q, [ $res, $reason, $id ]));

		// Manual entries stay permanent via their 'Man' reason.
		if ($row['id']) {
			$q = "UPDATE `$this->_tb_blacklist` SET res = %s, reason = %s WHERE id = %d";
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query($wpdb->prepare($q, [ $res, $reason, $row['id'] ]));
		} else {
			$q = "INSERT INTO `$this->_tb_blacklist` (url, res, reason) VALUES (%s, %s, %s)";
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query($wpdb->prepare($q, [ $row['url'], $res, $reason ]));
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

		$sitemap = Utility::sanitize_lines( $sitemap );

		try {
			foreach ( $sitemap as $this_map ) {
				$this->_parse( $this_map );
			}
		} catch ( \Exception $e ) {
			self::debug( '❌ failed to parse custom sitemap: ' . $e->getMessage() );
		}

		if ( is_array( $this->_urls ) && ! empty( $this->_urls ) ) {
			$crawler_drop_domain = defined( 'LITESPEED_CRAWLER_DROP_DOMAIN' ) && constant( 'LITESPEED_CRAWLER_DROP_DOMAIN' );
			$site                = wp_parse_url( $this->_site_url );
			$site_port           = isset( $site['port'] ) ? (int) $site['port'] : ( 'https' === strtolower( $site['scheme'] ) ? 443 : 80 );
			foreach ( $this->_urls as $k => $v ) {
				$url      = wp_parse_url( $v );
				$url_port = is_array( $url ) && isset( $url['port'] ) ? (int) $url['port'] : ( is_array( $url ) && isset( $url['scheme'] ) && 'https' === strtolower( $url['scheme'] ) ? 443 : 80 );
				if ( ! is_array( $url ) || empty( $url['scheme'] ) || empty( $url['host'] ) || strtolower( $url['scheme'] ) !== strtolower( $site['scheme'] ) || strtolower( $url['host'] ) !== strtolower( $site['host'] ) || $url_port !== $site_port ) {
					unset( $this->_urls[ $k ] );
					continue;
				}
				// Preserve the relative URL contract used by Crawler::_multi_request().
				if ( $crawler_drop_domain ) {
					if ( 0 !== stripos( $v, $this->_site_url ) ) {
						unset( $this->_urls[ $k ] );
						continue;
					}
					$this->_urls[ $k ] = substr( $v, strlen( $this->_site_url ) );
				}
			}

			$this->_urls = array_values( array_unique( $this->_urls ) );
		}

		self::debug( 'Truncate sitemap' );
		$wpdb->query( "TRUNCATE `$this->_tb`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		self::debug( 'Generate sitemap' );

		// Filter URLs in blacklist: drop fully given-up URLs; keep sub-threshold ones (preserving given-up positions) for retry.
		$crawler_count = count( Crawler::cls()->list_crawlers() );
		$blacklist     = $this->list_blacklist();

		$full_blacklisted    = [];
		$partial_blacklisted = [];
		foreach ( $blacklist as $v ) {
			// Pad/truncate to the current crawler count.
			$res       = substr( str_pad( (string) $v['res'], $crawler_count, '-' ), 0, $crawler_count );
			$is_manual = isset( $v['reason'] ) && false !== strpos( $v['reason'], 'Man' );
			// Active (retryable) = '-' or a digit; given-up = 'B'/'N'.
			$has_active = (bool) preg_match( '/[-0-9]/', $res );
			if ( $is_manual || ! $has_active ) {
				// Manual or fully given up -> drop from sitemap.
				$full_blacklisted[] = $v['url'];
				continue;
			}
			// Digits -> '-' so those positions retry; keep 'B'/'N' to stay skipped.
			$restore_res  = preg_replace( '/[0-9]/', '-', $res );
			$reason_parts = [];
			foreach ( str_split( $restore_res ) as $c ) {
				$reason_parts[] = ( Crawler::STATUS_BLACKLIST === $c || Crawler::STATUS_NOCACHE === $c ) ? 'Existed' : '';
			}
			$partial_blacklisted[ $v['url'] ] = [
				'res'    => $restore_res,
				'reason' => implode( ',', $reason_parts ),
			];
		}

		// Drop all fully given-up URLs.
		$this->_urls = array_diff( $this->_urls, $full_blacklisted );

		// Default res & reason.
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
