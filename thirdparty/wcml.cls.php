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
 * Provides compatibility with WCML for currency handling and crawler currency simulation.
 */
class WCML {

	const OPT_PICK = 'litespeed_wcml_crawler_currencies';

	/**
	 * Currently resolved client currency.
	 *
	 * @var string
	 */
	private static $_currency = '';

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
		add_filter('litespeed_vary', __CLASS__ . '::apply_vary');
		add_filter('litespeed_crawler_cookies', __CLASS__ . '::inject_vary_row');

		self::_detect_crawler_currency();

		// `litespeed_update_confs` piggybacks on LSCWP's nonce + cap verification.
		if (is_admin()) {
			add_action('litespeed_crawler_cookies_after', __CLASS__ . '::render_picker');
			add_action('litespeed_update_confs', __CLASS__ . '::_handle_save');
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
	 * Applies the client currency.
	 *
	 * @since 3.0
	 * @access public
	 * @param string $currency The currency code to apply.
	 * @return string The applied currency.
	 */
	public static function apply_client_currency( $currency ) {
		self::$_currency = $currency;
		return $currency;
	}

	/**
	 * Appends WCML currency to the vary list.
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
	 * Inject the WCML vary row into the crawler cookie list.
	 *
	 * @since 7.9
	 * @access public
	 * @param mixed $cookies Crawler cookie list.
	 * @return array
	 */
	public static function inject_vary_row( $cookies ) {
		if (!is_array($cookies)) {
			$cookies = [];
		}

		$currencies = self::_currencies();
		$selected   = count($currencies) >= 2 ? self::_selected($currencies) : [];
		if (empty($selected)) {
			return $cookies;
		}

		$vals = [];
		foreach ($selected as $code) {
			$h = self::_vary_hash($code);
			if (!empty($h)) {
				$vals[] = $h;
			}
		}
		if (empty($vals)) {
			return $cookies;
		}

		$vary_name = \LiteSpeed\Vary::cls()->get_vary_name();

		// Merge with admin's same-name rows
		$existing_vals = [];
		$other_rows    = [];
		foreach ($cookies as $c) {
			if (is_array($c) && isset($c['name']) && $vary_name === $c['name']) {
				if (!empty($c['vals']) && is_array($c['vals'])) {
					$existing_vals = array_merge($existing_vals, $c['vals']);
				}
			} else {
				$other_rows[] = $c;
			}
		}
		$other_rows[] = [
			'name' => $vary_name,
			'vals' => array_values(array_unique(array_merge($existing_vals, $vals))),
		];
		return $other_rows;
	}

	/**
	 * Render the WCML multi-currency crawler picker.
	 *
	 * @since 7.9
	 * @access public
	 * @return void
	 */
	public static function render_picker() {
		$currencies = self::_currencies();
		if (count($currencies) < 2) {
			return;
		}

		$selected = self::_selected($currencies);
		?>
		<div style="margin-top:10px;">
			<input type="hidden" name="litespeed_wcml_crawler_present" value="1" />
			<h4><?php echo esc_html__('WCML Multi-currency Crawl', 'litespeed-cache'); ?></h4>
			<p class="litespeed-desc">
				<?php echo esc_html__('Pick currencies to crawl. The matching _lscache_vary cookie is appended to the crawler cookie list automatically; crawler hits are reverse-mapped back to the selected currency at request time.', 'litespeed-cache'); ?>
				<br />
				<?php echo esc_html__('Note: applies to guest crawls only. Role-simulated crawls use the role vary cookie, which overrides the per-currency value.', 'litespeed-cache'); ?>
			</p>
			<?php foreach ($currencies as $code) : ?>
				<label style="display:inline-block; margin: 0 14px 6px 0;">
					<input type="checkbox" name="litespeed_wcml_crawler_currencies[]"
						value="<?php echo esc_attr($code); ?>"
						<?php checked(in_array($code, $selected, true)); ?> />
					<code><?php echo esc_html($code); ?></code>
				</label>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Persist user pick. Vary row itself is computed on the fly (see inject_vary_row()).
	 *
	 * @since 7.9
	 * @access public
	 * @return void
	 */
	public static function _handle_save() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- LSCWP verified nonce before firing this action.
		if (!isset($_POST['litespeed_wcml_crawler_present'])) {
			return;
		}

		$posted = [];
		if (isset($_POST['litespeed_wcml_crawler_currencies']) && is_array($_POST['litespeed_wcml_crawler_currencies'])) {
			$posted = array_map('sanitize_text_field', wp_unslash($_POST['litespeed_wcml_crawler_currencies']));
		}
		// phpcs:enable

		$selected = array_values(array_intersect(self::_currencies(), $posted));
		update_option(self::OPT_PICK, $selected, false);
	}

	/**
	 * Reverse-lookup currency from the vary cookie. Hash map computed on the fly.
	 *
	 * @since 7.9
	 * @access private
	 * @return void
	 */
	private static function _detect_crawler_currency() {
		// Crawler-only: real visitors resolve currency via WCML state, not via cookie reverse-lookup.
		if (
			empty($_SERVER['HTTP_USER_AGENT'])
			|| 0 !== strpos(wp_unslash((string) $_SERVER['HTTP_USER_AGENT']), \LiteSpeed\Crawler::FAST_USER_AGENT) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		) {
			return;
		}

		$vary_name = \LiteSpeed\Vary::cls()->get_vary_name();
		if (empty($_COOKIE[ $vary_name ])) {
			return;
		}

		$currencies = self::_currencies();
		$selected   = count($currencies) >= 2 ? self::_selected($currencies) : [];
		if (empty($selected)) {
			return;
		}

		$val = sanitize_text_field(wp_unslash($_COOKIE[ $vary_name ]));
		foreach ($selected as $code) {
			if (self::_vary_hash($code) === $val) {
				add_filter('wcml_client_currency', function () use ( $code ) {
					return $code;
				}, 0);
				self::apply_client_currency($code);
				return;
			}
		}
	}

	/**
	 * User-selected currencies, intersected with what is currently available.
	 *
	 * @since 7.9
	 * @access private
	 * @param string[] $available Available WCML currencies.
	 * @return string[]
	 */
	private static function _selected( $available ) {
		$stored = get_option(self::OPT_PICK, []);
		if (!is_array($stored)) {
			return [];
		}
		return array_values(array_intersect($available, $stored));
	}

	/**
	 * All active WCML currency codes.
	 *
	 * @since 7.9
	 * @access private
	 * @return string[]
	 */
	private static function _currencies() {
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
	 * Compute the vary cookie value for a guest under the given currency.
	 *
	 * @since 7.9
	 * @access private
	 * @param string $currency Currency code.
	 * @return string|false Vary value, or false if Vary cannot finalize (LITESPEED_GUEST etc.).
	 */
	private static function _vary_hash( $currency ) {
		$prev            = self::$_currency;
		self::$_currency = $currency;
		$hash            = \LiteSpeed\Vary::cls()->finalize_default_vary(-1);
		self::$_currency = $prev;
		return $hash;
	}
}
