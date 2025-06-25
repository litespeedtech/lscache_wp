<?php

/**
 * The Crawler Sitemap Class
 *
 * @since       1.1.0
 */

namespace LiteSpeed;

defined('WPINC') || exit();

class Crawler_Map extends Root {

	const LOG_TAG = '🐞🗺️';

	const BM_MISS      = 1;
	const BM_HIT       = 2;
	const BM_BLACKLIST = 4;

	private $_site_url; // Used to simplify urls
	private $_tb;
	private $_tb_blacklist;
	private $__data;
	private $_conf_map_timeout;
	private $_urls = array();

	/**
	 * Instantiate the class
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->_site_url         = get_site_url();
		$this->__data            = Data::cls();
		$this->_tb               = $this->__data->tb('crawler');
		$this->_tb_blacklist     = $this->__data->tb('crawler_blacklist');
		$this->_conf_map_timeout = defined('LITESPEED_CRAWLER_MAP_TIMEOUT') ? LITESPEED_CRAWLER_MAP_TIMEOUT : 180; // Specify the timeout while parsing the sitemap
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

		$total_crawler     = count(Crawler::cls()->list_crawlers());
		$total_crawler_pos = $total_crawler - 1;

		// Replace current crawler's position
		$curr_crawler = (int) $curr_crawler;
		foreach ($list as $bit => $ids) {
			// $ids = [ id => [ url, code ], ... ]
			if (!$ids) {
				continue;
			}
			self::debug("Update map [crawler] $curr_crawler [bit] $bit [count] " . count($ids));

			// Update res first, then reason
			$right_pos = $total_crawler_pos - $curr_crawler;
			$sql_res   = "CONCAT( LEFT( res, $curr_crawler ), '$bit', RIGHT( res, $right_pos ) )";

			$id_all = implode(',', array_map('intval', array_keys($ids)));

			$wpdb->query("UPDATE `$this->_tb` SET res = $sql_res WHERE id IN ( $id_all )");

			// Add blacklist
			if ($bit == Crawler::STATUS_BLACKLIST || $bit == Crawler::STATUS_NOCACHE) {
				$q        = "SELECT a.id, a.url FROM `$this->_tb_blacklist` a LEFT JOIN `$this->_tb` b ON b.url=a.url WHERE b.id IN ( $id_all )";
				$existing = $wpdb->get_results($q, ARRAY_A);
				// Update current crawler status tag in existing blacklist
				if ($existing) {
					$count = $wpdb->query("UPDATE `$this->_tb_blacklist` SET res = $sql_res WHERE id IN ( " . implode(',', array_column($existing, 'id')) . ' )');
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
					$wpdb->query($wpdb->prepare($q, $data));
				}
			}

			// Update sitemap reason w/ HTTP code
			$reason_array = array();
			foreach ($ids as $id => $v2) {
				$code = (int) $v2['code'];
				if (empty($reason_array[$code])) {
					$reason_array[$code] = array();
				}
				$reason_array[$code][] = (int) $id;
			}

			foreach ($reason_array as $code => $v2) {
				// Complement comma
				if ($curr_crawler) {
					$code = ',' . $code;
				}
				if ($curr_crawler < $total_crawler_pos) {
					$code .= ',';
				}

				$count = $wpdb->query(
					"UPDATE `$this->_tb` SET reason=CONCAT(SUBSTRING_INDEX(reason, ',', $curr_crawler), '$code', SUBSTRING_INDEX(reason, ',', -$right_pos)) WHERE id IN (" .
						implode(',', $v2) .
						')'
				);

				self::debug("Update map reason [code] $code [pos] left $curr_crawler right -$right_pos [count] $count");

				// Update blacklist reason
				if ($bit == Crawler::STATUS_BLACKLIST || $bit == Crawler::STATUS_NOCACHE) {
					$count = $wpdb->query(
						"UPDATE `$this->_tb_blacklist` a LEFT JOIN `$this->_tb` b ON b.url = a.url SET a.reason=CONCAT(SUBSTRING_INDEX(a.reason, ',', $curr_crawler), '$code', SUBSTRING_INDEX(a.reason, ',', -$right_pos)) WHERE b.id IN (" .
							implode(',', $v2) .
							')'
					);

					self::debug("Update blacklist [code] $code [pos] left $curr_crawler right -$right_pos [count] $count");
				}
			}
			// Reset list
			$list[$bit] = array();
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

		$id = (int) $id;

		// Build res&reason
		$total_crawler = count(Crawler::cls()->list_crawlers());
		$res           = str_repeat(Crawler::STATUS_BLACKLIST, $total_crawler);
		$reason        = implode(',', array_fill(0, $total_crawler, 'Man'));

		$row = $wpdb->get_row("SELECT a.url, b.id FROM `$this->_tb` a LEFT JOIN `$this->_tb_blacklist` b ON b.url = a.url WHERE a.id = '$id'", ARRAY_A);
		if (!$row) {
			self::debug('blacklist failed to add [id] ' . $id);
			return;
		}

		self::debug('Add to blacklist [url] ' . $row['url']);

		$q = "UPDATE `$this->_tb` SET res = %s, reason = %s WHERE id = %d";
		$wpdb->query($wpdb->prepare($q, array( $res, $reason, $id )));

		if ($row['id']) {
			$q = "UPDATE `$this->_tb_blacklist` SET res = %s, reason = %s WHERE id = %d";
			$wpdb->query($wpdb->prepare($q, array( $res, $reason, $row['id'] )));
		} else {
			$q = "INSERT INTO `$this->_tb_blacklist` (url, res, reason) VALUES (%s, %s, %s)";
			$wpdb->query($wpdb->prepare($q, array( $row['url'], $res, $reason )));
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
		if (!$this->__data->tb_exist('crawler_blacklist')) {
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
		$wpdb->query($sql);
		$wpdb->query("DELETE FROM `$this->_tb_blacklist` WHERE id='$id'");
	}

	/**
	 * Empty blacklist
	 *
	 * @since  3.0
	 * @access public
	 */
	public function blacklist_empty() {
		global $wpdb;

		if (!$this->__data->tb_exist('crawler_blacklist')) {
			return;
		}

		self::debug('Truncate blacklist');
		$sql = sprintf("UPDATE `%s` SET res=REPLACE(REPLACE(res, '%s', '-'), '%s', '-')", $this->_tb, Crawler::STATUS_NOCACHE, Crawler::STATUS_BLACKLIST);
		$wpdb->query($sql);
		$wpdb->query("TRUNCATE `$this->_tb_blacklist`");
	}

	/**
	 * List blacklist
	 *
	 * @since  3.0
	 * @access public
	 */
	public function list_blacklist( $limit = false, $offset = false ) {
		global $wpdb;

		if (!$this->__data->tb_exist('crawler_blacklist')) {
			return array();
		}

		$q = "SELECT * FROM `$this->_tb_blacklist` ORDER BY id DESC";

		if ($limit !== false) {
			if ($offset === false) {
				$total  = $this->count_blacklist();
				$offset = Utility::pagination($total, $limit, true);
			}
			$q .= ' LIMIT %d, %d';
			$q  = $wpdb->prepare($q, $offset, $limit);
		}
		return $wpdb->get_results($q, ARRAY_A);
	}

	/**
	 * Count blacklist
	 */
	public function count_blacklist() {
		global $wpdb;

		if (!$this->__data->tb_exist('crawler_blacklist')) {
			return false;
		}

		$q = "SELECT COUNT(*) FROM `$this->_tb_blacklist`";
		return $wpdb->get_var($q);
	}

	/**
	 * Empty sitemap
	 *
	 * @since  3.0
	 * @access public
	 */
	public function empty_map() {
		Data::cls()->tb_del('crawler');

		$msg = __('Sitemap cleaned successfully', 'litespeed-cache');
		Admin_Display::success($msg);
	}

	/**
	 * List generated sitemap
	 *
	 * @since  3.0
	 * @access public
	 */
	public function list_map( $limit, $offset = false ) {
		global $wpdb;

		if (!$this->__data->tb_exist('crawler')) {
			return array();
		}

		if ($offset === false) {
			$total  = $this->count_map();
			$offset = Utility::pagination($total, $limit, true);
		}

		$type = Router::verify_type();

		$where = '';
		if (!empty($_POST['kw'])) {
			$q = "SELECT * FROM `$this->_tb` WHERE url LIKE %s";
			if ($type == 'hit') {
				$q .= " AND res LIKE '%" . Crawler::STATUS_HIT . "%'";
			}
			if ($type == 'miss') {
				$q .= " AND res LIKE '%" . Crawler::STATUS_MISS . "%'";
			}
			if ($type == 'blacklisted') {
				$q .= " AND res LIKE '%" . Crawler::STATUS_BLACKLIST . "%'";
			}
			$q    .= ' ORDER BY id LIMIT %d, %d';
			$where = '%' . $wpdb->esc_like($_POST['kw']) . '%';
			return $wpdb->get_results($wpdb->prepare($q, $where, $offset, $limit), ARRAY_A);
		}

		$q = "SELECT * FROM `$this->_tb`";
		if ($type == 'hit') {
			$q .= " WHERE res LIKE '%" . Crawler::STATUS_HIT . "%'";
		}
		if ($type == 'miss') {
			$q .= " WHERE res LIKE '%" . Crawler::STATUS_MISS . "%'";
		}
		if ($type == 'blacklisted') {
			$q .= " WHERE res LIKE '%" . Crawler::STATUS_BLACKLIST . "%'";
		}
		$q .= ' ORDER BY id LIMIT %d, %d';
		// self::debug("q=$q offset=$offset, limit=$limit");
		return $wpdb->get_results($wpdb->prepare($q, $offset, $limit), ARRAY_A);
	}

	/**
	 * Count sitemap
	 */
	public function count_map() {
		global $wpdb;

		if (!$this->__data->tb_exist('crawler')) {
			return false;
		}

		$q = "SELECT COUNT(*) FROM `$this->_tb`";

		$type = Router::verify_type();
		if ($type == 'hit') {
			$q .= " WHERE res LIKE '%" . Crawler::STATUS_HIT . "%'";
		}
		if ($type == 'miss') {
			$q .= " WHERE res LIKE '%" . Crawler::STATUS_MISS . "%'";
		}
		if ($type == 'blacklisted') {
			$q .= " WHERE res LIKE '%" . Crawler::STATUS_BLACKLIST . "%'";
		}

		return $wpdb->get_var($q);
	}

	/**
	 * Generate sitemap
	 *
	 * @since    1.1.0
	 * @access public
	 */
	public function gen( $manual = false ) {
		$count = $this->_gen();

		if (!$count) {
			Admin_Display::error(__('No valid sitemap parsed for crawler.', 'litespeed-cache'));
			return;
		}

		if (!wp_doing_cron() && $manual) {
			$msg = sprintf(__('Sitemap created successfully: %d items', 'litespeed-cache'), $count);
			Admin_Display::success($msg);
		}
	}

	/**
	 * Generate the sitemap
	 *
	 * @since    1.1.0
	 * @access private
	 */
	private function _gen() {
		global $wpdb;

		if (!$this->__data->tb_exist('crawler')) {
			$this->__data->tb_create('crawler');
		}

		if (!$this->__data->tb_exist('crawler_blacklist')) {
			$this->__data->tb_create('crawler_blacklist');
		}

		// use custom sitemap
		if (!($sitemap = $this->conf(Base::O_CRAWLER_SITEMAP))) {
			return false;
		}

		$offset  = strlen($this->_site_url);
		$sitemap = Utility::sanitize_lines($sitemap);

		try {
			foreach ($sitemap as $this_map) {
				$this->_parse($this_map);
			}
		} catch (\Exception $e) {
			self::debug('❌ failed to parse custom sitemap: ' . $e->getMessage());
		}

		if (is_array($this->_urls) && !empty($this->_urls)) {
			if (defined('LITESPEED_CRAWLER_DROP_DOMAIN') && LITESPEED_CRAWLER_DROP_DOMAIN) {
				foreach ($this->_urls as $k => $v) {
					if (stripos($v, $this->_site_url) !== 0) {
						unset($this->_urls[$k]);
						continue;
					}
					$this->_urls[$k] = substr($v, $offset);
				}
			}

			$this->_urls = array_unique($this->_urls);
		}

		self::debug('Truncate sitemap');
		$wpdb->query("TRUNCATE `$this->_tb`");

		self::debug('Generate sitemap');

		// Filter URLs in blacklist
		$blacklist = $this->list_blacklist();

		$full_blacklisted    = array();
		$partial_blacklisted = array();
		foreach ($blacklist as $v) {
			if (strpos($v['res'], '-') === false) {
				// Full blacklisted
				$full_blacklisted[] = $v['url'];
			} else {
				// Replace existing reason
				$v['reason']                    = explode(',', $v['reason']);
				$v['reason']                    = array_map(function ( $element ) {
					return $element ? 'Existed' : '';
				}, $v['reason']);
				$v['reason']                    = implode(',', $v['reason']);
				$partial_blacklisted[$v['url']] = array(
					'res' => $v['res'],
					'reason' => $v['reason'],
				);
			}
		}

		// Drop all blacklisted URLs
		$this->_urls = array_diff($this->_urls, $full_blacklisted);

		// Default res & reason
		$crawler_count  = count(Crawler::cls()->list_crawlers());
		$default_res    = str_repeat('-', $crawler_count);
		$default_reason = $crawler_count > 1 ? str_repeat(',', $crawler_count - 1) : '';

		$data = array();
		foreach ($this->_urls as $url) {
			$data[] = $url;
			$data[] = array_key_exists($url, $partial_blacklisted) ? $partial_blacklisted[$url]['res'] : $default_res;
			$data[] = array_key_exists($url, $partial_blacklisted) ? $partial_blacklisted[$url]['reason'] : $default_reason;
		}

		foreach (array_chunk($data, 300) as $data2) {
			$this->_save($data2);
		}

		// Reset crawler
		Crawler::cls()->reset_pos();

		return count($this->_urls);
	}

	/**
	 * Save data to table
	 *
	 * @since 3.0
	 * @access private
	 */
	private function _save( $data, $fields = 'url,res,reason' ) {
		global $wpdb;

		if (empty($data)) {
			return;
		}

		$q = "INSERT INTO `$this->_tb` ( $fields ) VALUES ";

		// Add placeholder
		$q .= Utility::chunk_placeholder($data, $fields);

		// Store data
		$wpdb->query($wpdb->prepare($q, $data));
	}

	/**
	 * Parse custom sitemap and return urls
	 *
	 * @since    1.1.1
	 * @access private
	 */
	private function _parse( $sitemap ) {
		/**
		 * Read via wp func to avoid allow_url_fopen = off
		 *
		 * @since  2.2.7
		 */
		$response = wp_safe_remote_get($sitemap, array(
			'timeout' => $this->_conf_map_timeout,
			'sslverify' => false,
		));
		if (is_wp_error($response)) {
			$error_message = $response->get_error_message();
			self::debug('failed to read sitemap: ' . $error_message);
			throw new \Exception('Failed to remote read ' . $sitemap);
		}

		$xml_object = simplexml_load_string($response['body'], null, LIBXML_NOCDATA);
		if (!$xml_object) {
			if ($this->_urls) {
				return;
			}
			throw new \Exception('Failed to parse xml ' . $sitemap);
		}

		// start parsing
		$xml_array = (array) $xml_object;
		if (!empty($xml_array['sitemap'])) {
			// parse sitemap set
			if (is_object($xml_array['sitemap'])) {
				$xml_array['sitemap'] = (array) $xml_array['sitemap'];
			}

			if (!empty($xml_array['sitemap']['loc'])) {
				// is single sitemap
				$this->_parse($xml_array['sitemap']['loc']);
			} else {
				// parse multiple sitemaps
				foreach ($xml_array['sitemap'] as $val) {
					$val = (array) $val;
					if (!empty($val['loc'])) {
						$this->_parse($val['loc']); // recursive parse sitemap
					}
				}
			}
		} elseif (!empty($xml_array['url'])) {
			// parse url set
			if (is_object($xml_array['url'])) {
				$xml_array['url'] = (array) $xml_array['url'];
			}
			// if only 1 element
			if (!empty($xml_array['url']['loc'])) {
				$this->_urls[] = $xml_array['url']['loc'];
			} else {
				foreach ($xml_array['url'] as $val) {
					$val = (array) $val;
					if (!empty($val['loc'])) {
						$this->_urls[] = $val['loc'];
					}
				}
			}
		}
	}
}
