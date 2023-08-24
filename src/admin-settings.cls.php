<?php

/**
 * The admin settings handler of the plugin.
 *
 *
 * @since      1.1.0
 * @package    LiteSpeed
 * @subpackage LiteSpeed/src
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */

namespace LiteSpeed;

defined('WPINC') || exit;

class Admin_Settings extends Base
{
	const ENROLL = '_settings-enroll';

	/**
	 * Save settings
	 *
	 * Both $_POST and CLI can use this way
	 *
	 * Import will directly call conf.cls
	 *
	 * @since  3.0
	 * @access public
	 */
	public function save($raw_data)
	{
		Debug2::debug('[Settings] saving');

		if (empty($raw_data[self::ENROLL])) {
			exit('No fields');
		}

		$raw_data = Admin::cleanup_text($raw_data);

		// Convert data to config format
		$the_matrix = array();
		foreach (array_unique($raw_data[self::ENROLL]) as $id) {
			$child = false;
			// Drop array format
			if (strpos($id, '[') !== false) {
				if (strpos($id, self::O_CDN_MAPPING) === 0 || strpos($id, self::O_CRAWLER_COOKIES) === 0) { // CDN child | Cookie Crawler settings
					$child = substr($id, strpos($id, '[') + 1, strpos($id, ']') - strpos($id, '[') - 1);
					$id = substr($id, 0, strpos($id, '[')); // Drop ending []; Compatible with xx[0] way from CLI
				} else {
					$id = substr($id, 0, strpos($id, '[')); // Drop ending []
				}
			}

			if (!array_key_exists($id, self::$_default_options)) {
				continue;
			}

			// Validate $child
			if ($id == self::O_CDN_MAPPING) {
				if (!in_array($child, array(
					self::CDN_MAPPING_URL,
					self::CDN_MAPPING_INC_IMG,
					self::CDN_MAPPING_INC_CSS,
					self::CDN_MAPPING_INC_JS,
					self::CDN_MAPPING_FILETYPE,
				))) {
					continue;
				}
			}
			if ($id == self::O_CRAWLER_COOKIES) {
				if (!in_array($child, array(
					self::CRWL_COOKIE_NAME,
					self::CRWL_COOKIE_VALS,
				))) {
					continue;
				}
			}

			$data = false;

			if ($child) {
				$data = !empty($raw_data[$id][$child]) ? $raw_data[$id][$child] : false; // []=xxx or [0]=xxx
			} else {
				$data = !empty($raw_data[$id]) ? $raw_data[$id] : false;
			}

			/**
			 * Sanitize the value
			 */
			if ($id == self::O_CDN_MAPPING || $id == self::O_CRAWLER_COOKIES) {
				// Use existing in queue data if existed (Only available when $child != false)
				$data2 = array_key_exists($id, $the_matrix) ? $the_matrix[$id] : (defined('WP_CLI') && WP_CLI ? $this->conf($id) : array());
			}
			switch ($id) {
				case self::O_CDN_MAPPING:
					/**
					 * CDN setting
					 *
					 * Raw data format:
					 * 		cdn-mapping[url][] = 'xxx'
					 * 		cdn-mapping[url][2] = 'xxx2'
					 * 		cdn-mapping[inc_js][] = 1
					 *
					 * Final format:
					 * 		cdn-mapping[ 0 ][ url ] = 'xxx'
					 * 		cdn-mapping[ 2 ][ url ] = 'xxx2'
					 */
					if ($data) foreach ($data as $k => $v) {
						if ($child == self::CDN_MAPPING_FILETYPE) {
							$v = Utility::sanitize_lines($v);
						}
						if ($child == self::CDN_MAPPING_URL) {
							# If not a valid URL, turn off CDN
							if (strpos($v, 'https://') !== 0) {
								self::debug('❌ CDN mapping set to OFF due to invalid URL');
								$the_matrix[self::O_CDN] = false;
							}
							$v = trailingslashit($v);
						}
						if (in_array($child, array(
							self::CDN_MAPPING_INC_IMG,
							self::CDN_MAPPING_INC_CSS,
							self::CDN_MAPPING_INC_JS,
						))) {
							// Because these can't be auto detected in `config->update()`, need to format here
							$v = $v === 'false' ? 0 : (bool) $v;
						}

						if (empty($data2[$k])) {
							$data2[$k] = array();
						}

						$data2[$k][$child] = $v;
					}

					$data = $data2;
					break;

				case self::O_CRAWLER_COOKIES:
					/**
					 * Cookie Crawler setting
					 * Raw Format:
					 * 		crawler-cookies[name][] = xxx
					 * 		crawler-cookies[name][2] = xxx2
					 * 		crawler-cookies[vals][] = xxx
					 *
					 * todo: need to allow null for values
					 *
					 * Final format:
					 * 		crawler-cookie[ 0 ][ name ] = 'xxx'
					 * 		crawler-cookie[ 0 ][ vals ] = 'xxx'
					 * 		crawler-cookie[ 2 ][ name ] = 'xxx2'
					 *
					 * empty line for `vals` use literal `_null`
					 */
					if ($data) foreach ($data as $k => $v) {
						if ($child == self::CRWL_COOKIE_VALS) {
							$v = Utility::sanitize_lines($v);
						}

						if (empty($data2[$k])) {
							$data2[$k] = array();
						}

						$data2[$k][$child] = $v;
					}

					$data = $data2;
					break;

					// Cache exclude cat
				case self::O_CACHE_EXC_CAT:
					$data2 = array();
					$data = Utility::sanitize_lines($data);
					foreach ($data as $v) {
						$cat_id = get_cat_ID($v);
						if (!$cat_id) {
							continue;
						}

						$data2[] = $cat_id;
					}
					$data = $data2;
					break;

					// Cache exclude tag
				case self::O_CACHE_EXC_TAG:
					$data2 = array();
					$data = Utility::sanitize_lines($data);
					foreach ($data as $v) {
						$term = get_term_by('name', $v, 'post_tag');
						if (!$term) {
							// todo: can show the error in admin error msg
							continue;
						}

						$data2[] = $term->term_id;
					}
					$data = $data2;
					break;

				default:
					break;
			}

			$the_matrix[$id] = $data;
		}

		// Special handler for CDN/Crawler 2d list to drop empty rows
		foreach ($the_matrix as $id => $data) {
			/**
			 * 		cdn-mapping[ 0 ][ url ] = 'xxx'
			 * 		cdn-mapping[ 2 ][ url ] = 'xxx2'
			 *
			 * 		crawler-cookie[ 0 ][ name ] = 'xxx'
			 * 		crawler-cookie[ 0 ][ vals ] = 'xxx'
			 * 		crawler-cookie[ 2 ][ name ] = 'xxx2'
			 */
			if ($id == self::O_CDN_MAPPING || $id == self::O_CRAWLER_COOKIES) {
				// Drop this line if all children elements are empty
				foreach ($data as $k => $v) {
					foreach ($v as $v2) {
						if ($v2) {
							continue 2;
						}
					}
					// If hit here, means all empty
					unset($the_matrix[$id][$k]);
				}
			}

			// Don't allow repeated cookie name
			if ($id == self::O_CRAWLER_COOKIES) {
				$existed = array();
				foreach ($the_matrix[$id] as $k => $v) {
					if (!$v[self::CRWL_COOKIE_NAME] || in_array($v[self::CRWL_COOKIE_NAME], $existed)) { // Filter repeated or empty name
						unset($the_matrix[$id][$k]);
						continue;
					}

					$existed[] = $v[self::CRWL_COOKIE_NAME];
				}
			}

			// CDN mapping allow URL values repeated
			// if ( $id == self::O_CDN_MAPPING ) {}

			// tmp fix the 3rd part woo update hook issue when enabling vary cookie
			if ($id == 'wc_cart_vary') {
				if ($data)
					add_filter('litespeed_vary_cookies', function ($list) {
						$list[] = 'woocommerce_cart_hash';
						return array_unique($list);
					});
				else {
					add_filter('litespeed_vary_cookies', function ($list) {
						if (in_array('woocommerce_cart_hash', $list)) {
							unset($list[array_search('woocommerce_cart_hash', $list)]);
						}
						return array_unique($list);
					});
				}
			}
		}

		// id validation will be inside
		$this->cls('Conf')->update_confs($the_matrix);

		$msg = __('Options saved.', 'litespeed-cache');
		Admin_Display::succeed($msg);
	}

	/**
	 * Parses any changes made by the network admin on the network settings.
	 *
	 * @since 3.0
	 * @access public
	 */
	public function network_save($raw_data)
	{
		Debug2::debug('[Settings] network saving');

		if (empty($raw_data[self::ENROLL])) {
			exit('No fields');
		}

		$raw_data = Admin::cleanup_text($raw_data);

		foreach (array_unique($raw_data[self::ENROLL]) as $id) {
			// Append current field to setting save
			if (!array_key_exists($id, self::$_default_site_options)) {
				continue;
			}

			$data = !empty($raw_data[$id]) ? $raw_data[$id] : false;

			// id validation will be inside
			$this->cls('Conf')->network_update($id, $data);
		}

		// Update related files
		Activation::cls()->update_files();

		$msg = __('Options saved.', 'litespeed-cache');
		Admin_Display::succeed($msg);
	}

	/**
	 * Hooked to the wp_redirect filter.
	 * This will only hook if there was a problem when saving the widget.
	 *
	 * @since 1.1.3
	 * @access public
	 * @param string $location The location string.
	 * @return string the updated location string.
	 */
	public static function widget_save_err($location)
	{
		return str_replace('?message=0', '?error=0', $location);
	}

	/**
	 * Hooked to the widget_update_callback filter.
	 * Validate the LiteSpeed Cache settings on edit widget save.
	 *
	 * @since 1.1.3
	 * @access public
	 * @param array $instance The new settings.
	 * @param array $new_instance
	 * @param array $old_instance The original settings.
	 * @param WP_Widget $widget The widget
	 * @return mixed Updated settings on success, false on error.
	 */
	public static function validate_widget_save($instance, $new_instance, $old_instance, $widget)
	{
		if (empty($new_instance)) {
			return $instance;
		}

		if (!isset($new_instance[ESI::WIDGET_O_ESIENABLE]) || !isset($new_instance[ESI::WIDGET_O_TTL])) {
			return $instance;
		}

		$esi = intval($new_instance[ESI::WIDGET_O_ESIENABLE]) % 3;
		$ttl = (int) $new_instance[ESI::WIDGET_O_TTL];

		if ($ttl != 0 && $ttl < 30) {
			add_filter('wp_redirect', __CLASS__ . '::widget_save_err');
			return false; // invalid ttl.
		}

		if (empty($instance[Conf::OPTION_NAME])) { // todo: to be removed
			$instance[Conf::OPTION_NAME] = array();
		}
		$instance[Conf::OPTION_NAME][ESI::WIDGET_O_ESIENABLE] = $esi;
		$instance[Conf::OPTION_NAME][ESI::WIDGET_O_TTL] = $ttl;

		$current = !empty($old_instance[Conf::OPTION_NAME]) ? $old_instance[Conf::OPTION_NAME] : false;
		if (!strpos($_SERVER['HTTP_REFERER'], '/wp-admin/customize.php')) {
			if (!$current || $esi != $current[ESI::WIDGET_O_ESIENABLE]) {
				Purge::purge_all('Wdiget ESI_enable changed');
			} elseif ($ttl != 0 && $ttl != $current[ESI::WIDGET_O_TTL]) {
				Purge::add(Tag::TYPE_WIDGET . $widget->id);
			}

			Purge::purge_all('Wdiget saved');
		}
		return $instance;
	}
}
