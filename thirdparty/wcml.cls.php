<?php
/**
 * The Third Party integration with WCML.
 *
 * @since 3.0
 * @package LiteSpeed
 * @subpackage LiteSpeed_Cache\Thirdparty
 */

namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

/**
 * Provides compatibility with WCML for currency handling.
 */
class WCML {

	/**
	 * Holds the current WCML currency.
	 *
	 * @var string
	 */
	private static $_currency = '';

	/**
	 * Cached vary-hash-to-currency map.
	 *
	 * @var array|null
	 */
	private static $_currency_map;

	/**
	 * Detect if WCML is active and register hooks.
	 *
	 * @since 3.0
	 * @access public
	 * @return void
	 */
	public static function detect() {
		if (!defined('WCML_VERSION')) {
			return;
		}

		add_filter('wcml_client_currency', __CLASS__ . '::apply_client_currency');
		add_action('wcml_set_client_currency', __CLASS__ . '::set_client_currency');

		// Always register vary so the _lscache_vary cookie is not deleted during
		// requests where WCML does not resolve the currency (e.g. wc-ajax).
		add_filter('litespeed_vary', __CLASS__ . '::apply_vary');

		// Crawler: reverse-lookup currency from _lscache_vary cookie.
		self::_detect_crawler_currency();

		// Crawler: user-controlled multi-currency crawling.
		if (is_admin()) {
			add_action('litespeed_crawler_cookies_after', __CLASS__ . '::render_crawler_multicurrency');
			self::_handle_crawler_save();
		}
		if (is_admin() || defined('WP_CLI') || defined('LITESPEED_CLI')) {
			if (get_option('lscwp_wcml_crawler_multicurrency', false)) {
				self::_sync_crawler_cookies();
			}
		}
	}

	/**
	 * Sets the client currency and triggers vary updates.
	 *
	 * @since 3.0
	 * @access public
	 * @param string $currency The currency code to set.
	 * @return void
	 */
	public static function set_client_currency( $currency ) {
		self::apply_client_currency($currency);
		do_action('litespeed_vary_ajax_force');
	}

	/**
	 * Applies the client currency and adjusts vary accordingly.
	 *
	 * @since 3.0
	 * @access public
	 * @param string $currency The currency code to apply.
	 * @return string The applied currency.
	 */
	public static function apply_client_currency( $currency ) {
		self::$_currency = $currency;
		add_filter('litespeed_vary', __CLASS__ . '::apply_vary');

		return $currency;
	}

	/**
	 * Appends WCML currency to vary list.
	 *
	 * @since 3.0
	 * @access public
	 * @param array $vary_list The existing vary list.
	 * @return array The updated vary list including WCML currency.
	 */
	public static function apply_vary( $vary_list ) {
		if (empty(self::$_currency)) {
			global $woocommerce_wpml;
			if (is_object($woocommerce_wpml)
				&& isset($woocommerce_wpml->multi_currency)
				&& method_exists($woocommerce_wpml->multi_currency, 'get_client_currency')
			) {
				self::$_currency = $woocommerce_wpml->multi_currency->get_client_currency();
			}
			if (empty(self::$_currency)) {
				self::$_currency = get_option('woocommerce_currency', 'USD');
			}
		}

		$vary_list['wcml_currency'] = self::$_currency;

		return $vary_list;
	}

	/**
	 * Detect crawler currency from the _lscache_vary cookie.
	 *
	 * @since 7.9
	 * @access private
	 * @return void
	 */
	private static function _detect_crawler_currency() {
		if (empty($_COOKIE['_lscache_vary'])) {
			return;
		}

		$map = self::_get_currency_map();
		if (empty($map)) {
			return;
		}

		$vary_val = wp_unslash($_COOKIE['_lscache_vary']);
		if (!isset($map[$vary_val])) {
			return;
		}

		$currency = $map[$vary_val];

		add_filter('wcml_client_currency', function () use ($currency) {
			return $currency;
		}, 0);

		self::apply_client_currency($currency);
	}

	/**
	 * Sync crawler-cookies with WCML currency vary hashes.
	 *
	 * @since 7.9
	 * @access private
	 * @return void
	 */
	private static function _sync_crawler_cookies() {
		$currencies = self::_get_currencies();
		if (count($currencies) < 2) {
			return;
		}

		$hash       = get_option('litespeed.conf.hash', '');
		$guest_mode = (bool) get_option('litespeed.conf.guest', false);

		$vary_name    = '_lscache_vary';
		$vals         = [];
		$currency_map = [];

		foreach ($currencies as $code) {
			$vary_hash                = self::_compute_vary_hash($code, $hash, $guest_mode);
			$vals[]                   = $vary_hash;
			$currency_map[$vary_hash] = $code;
		}

		$stored_map = get_option('lscwp_wcml_crawler_currency_map', []);
		if ($stored_map === $currency_map) {
			return;
		}

		update_option('lscwp_wcml_crawler_currency_map', $currency_map, false);

		$crawler_cookies = get_option('litespeed.conf.crawler-cookies', []);
		if (!is_array($crawler_cookies)) {
			$crawler_cookies = [];
		}

		$crawler_cookies = array_values(array_filter(
			$crawler_cookies,
			function ($c) use ($vary_name) {
				return is_array($c) && isset($c['name']) && $c['name'] !== $vary_name;
			}
		));

		$crawler_cookies[] = [
			'name' => $vary_name,
			'vals' => $vals,
		];

		update_option('litespeed.conf.crawler-cookies', $crawler_cookies);
	}

	/**
	 * Render the WCML multi-currency crawler checkbox.
	 *
	 * @since 7.9
	 * @access public
	 * @return void
	 */
	public static function render_crawler_multicurrency() {
		if (!defined('WCML_VERSION')) {
			return;
		}

		$currencies = self::_get_currencies();
		if (count($currencies) < 2) {
			return;
		}

		$hash       = get_option('litespeed.conf.hash', '');
		$guest_mode = (bool) get_option('litespeed.conf.guest', false);

		$vals = [];
		foreach ($currencies as $code) {
			$vals[] = self::_compute_vary_hash($code, $hash, $guest_mode);
		}

		$enabled = (bool) get_option('lscwp_wcml_crawler_multicurrency', false);
		$currency_list = esc_html(implode(', ', $currencies));
		$vals_str = implode("\n", $vals);
		?>
		<div style="margin-top: 10px;">
			<input type="hidden" name="lscwp_wcml_crawler_present" value="1" />
			<label>
				<input type="checkbox" name="lscwp_wcml_crawler_multicurrency" value="1"
					id="lscwp_wcml_crawler_multicurrency" <?php checked($enabled); ?> />
				<?php echo esc_html__('Crawl for WCML multi-currency', 'litespeed-cache'); ?>
			</label>
			<p class="litespeed-desc">
				<?php echo sprintf(esc_html__('Detected currencies: %s', 'litespeed-cache'), '<code>' . $currency_list . '</code>'); ?>
			</p>
		</div>
		<script>
		(function() {
			var checkbox = document.getElementById('lscwp_wcml_crawler_multicurrency');
			if (!checkbox) return;

			var varyName  = '_lscache_vary';
			var varyVals  = <?php echo wp_json_encode($vals_str); ?>;
			var cookieIds = <?php echo wp_json_encode(\LiteSpeed\Base::O_CRAWLER_COOKIES); ?>;
			var container = document.getElementById('litespeed_crawler_simulation_div');

			function findVaryBlock() {
				if (!container) return null;
				var inputs = container.querySelectorAll('input[name="' + cookieIds + '[name][]"]');
				for (var i = 0; i < inputs.length; i++) {
					if (inputs[i].value === varyName) {
						return inputs[i].closest('.litespeed-block');
					}
				}
				return null;
			}

			function addVaryBlock() {
				if (findVaryBlock()) return;
				/* Simulate React "Add new" click, then fill the last row. */
				var addBtn = container ? container.querySelector('.litespeed-form-action') : null;
				if (addBtn) {
					addBtn.click();
					var inputs = container.querySelectorAll('input[name="' + cookieIds + '[name][]"]');
					var texts  = container.querySelectorAll('textarea[name="' + cookieIds + '[vals][]"]');
					if (inputs.length && texts.length) {
						var last   = inputs[inputs.length - 1];
						var lastTA = texts[texts.length - 1];
						/* Trigger React-compatible change events. */
						var nativeSet = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set;
						nativeSet.call(last, varyName);
						last.dispatchEvent(new Event('input', {bubbles: true}));
						var taSet = Object.getOwnPropertyDescriptor(HTMLTextAreaElement.prototype, 'value').set;
						taSet.call(lastTA, varyVals);
						lastTA.dispatchEvent(new Event('input', {bubbles: true}));
					}
				}
			}

			function removeVaryBlock() {
				var block = findVaryBlock();
				if (!block) return;
				var delBtn = block.querySelector('.litespeed-danger');
				if (delBtn) delBtn.click();
			}

			checkbox.addEventListener('change', function() {
				if (this.checked) {
					addVaryBlock();
				} else {
					removeVaryBlock();
				}
			});
		})();
		</script>
		<?php
	}

	/**
	 * Handle save of the WCML multi-currency crawler checkbox.
	 *
	 * @since 7.9
	 * @access private
	 * @return void
	 */
	private static function _handle_crawler_save() {
		if (empty($_POST['LSCWP_CTRL']) || 'save-settings' !== $_POST['LSCWP_CTRL']) {
			return;
		}
		if (!isset($_POST['lscwp_wcml_crawler_present'])) {
			return;
		}

		$enabled = !empty($_POST['lscwp_wcml_crawler_multicurrency']);
		update_option('lscwp_wcml_crawler_multicurrency', $enabled ? 1 : 0, false);

		if (!$enabled) {
			self::_remove_crawler_cookies();
		}
	}

	/**
	 * Remove WCML crawler cookies and currency map.
	 *
	 * @since 7.9
	 * @access private
	 * @return void
	 */
	private static function _remove_crawler_cookies() {
		delete_option('lscwp_wcml_crawler_currency_map');

		$crawler_cookies = get_option('litespeed.conf.crawler-cookies', []);
		if (!is_array($crawler_cookies)) {
			return;
		}

		$vary_name = '_lscache_vary';
		$crawler_cookies = array_values(array_filter(
			$crawler_cookies,
			function ($c) use ($vary_name) {
				return is_array($c) && isset($c['name']) && $c['name'] !== $vary_name;
			}
		));

		update_option('litespeed.conf.crawler-cookies', $crawler_cookies);
	}

	/**
	 * Get all active WCML currency codes.
	 *
	 * @since 7.9
	 * @access private
	 * @return string[]
	 */
	private static function _get_currencies() {
		global $woocommerce_wpml;
		if (is_object($woocommerce_wpml)
			&& isset($woocommerce_wpml->multi_currency)
			&& method_exists($woocommerce_wpml->multi_currency, 'get_currency_codes')
		) {
			return $woocommerce_wpml->multi_currency->get_currency_codes();
		}

		$settings = get_option('_wcml_settings', []);
		if (!empty($settings['currency_options']) && is_array($settings['currency_options'])) {
			return array_keys($settings['currency_options']);
		}

		return [];
	}

	/**
	 * Compute the _lscache_vary value for a given currency.
	 *
	 * @since 7.9
	 * @access private
	 * @param string $currency   Currency code.
	 * @param string $hash       LiteSpeed conf hash.
	 * @param bool   $guest_mode Whether guest mode is enabled.
	 * @return string
	 */
	private static function _compute_vary_hash( $currency, $hash, $guest_mode = false ) {
		$vary = ['wcml_currency' => $currency];

		if ($guest_mode) {
			$vary['guest_mode'] = 1;
		}

		ksort($vary);

		$parts = [];
		foreach ($vary as $k => $v) {
			$parts[] = $k . ':' . $v;
		}
		$res = implode(';', $parts);

		if (defined('LSCWP_LOG')) {
			return $res;
		}

		return md5($hash . $res);
	}

	/**
	 * Get the vary-hash to currency map.
	 *
	 * @since 7.9
	 * @access private
	 * @return array
	 */
	private static function _get_currency_map() {
		if (self::$_currency_map === null) {
			self::$_currency_map = get_option('lscwp_wcml_crawler_currency_map', []);
		}

		return self::$_currency_map;
	}
}
