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
	 * True while a crawler vary hash is computed: the current language is then unrelated to the target URL.
	 *
	 * @var bool
	 */
	private static $_lang_neutral = false;

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
		add_filter('litespeed_guest_vary_url', __CLASS__ . '::guest_vary_url');

		self::_detect_crawler_currency();

		// This check covers Guest Mode requests, which skip apply_vary().
		// WCML shows the "Switching currency?" cart dialog when a language switch changes the currency. WCML fires its action at init:2 to wp_loaded:2, before third parties load, so read the HTML WCML stored once cache headers are decided. The page is personalized: never cache it. (Not run for a force-public URI: apply_vary() covers it.)
		add_action('litespeed_control_finalize', function () {
			if (self::_dialog_shown()) {
				\LiteSpeed\Control::set_nocache_hard('wcml currency switching dialog');
			}
		});

		// With WCML or WPML gone, pages cached under the default vary of a one-currency-per-language site would hit with the wrong currency.
		add_action('deactivated_plugin', function ( $plugin ) {
			self::_purge_for_plugin($plugin);
		}, 10, 2);

		// `litespeed_update_confs` piggybacks on LSCWP's nonce + cap verification.
		if (is_admin()) {
			add_action('litespeed_crawler_cookies_after', __CLASS__ . '::render_picker');
			add_action('litespeed_update_confs', __CLASS__ . '::_handle_save');
		}
	}

	/**
	 * Register the hooks that must exist before WCML's own init.
	 *
	 * WCML's settings form writes `_wcml_settings` at init:2, earlier than detect() runs (wp_loaded:2). When a plugin is activated, WCML is not yet loaded at `litespeed_init`, so that listener comes before the WCML_VERSION check.
	 *
	 * @since 7.9.4
	 * @access public
	 * @return void
	 */
	public static function preload() {
		// Pages cached while WCML or WPML was off would hit with the wrong currency once the one-currency-per-language vary applies.
		add_action('activated_plugin', function ( $plugin ) {
			self::_purge_for_plugin($plugin);
		}, 10, 2);
		// An upgrade of WCML or WPML can change how currencies resolve.
		add_action('upgrader_process_complete', function ( $upgrader, $extra ) {
			if (!is_array($extra) || !isset($extra['type']) || 'plugin' !== $extra['type']) {
				return;
			}
			// A bulk update lists `plugins`, a single update (also auto-updates) passes `plugin`, an install that overwrites a plugin passes neither: the upgrader knows it.
			if (!empty($extra['plugins']) && is_array($extra['plugins'])) {
				$plugins = $extra['plugins'];
			} elseif (!empty($extra['plugin']) && is_string($extra['plugin'])) {
				$plugins = [ $extra['plugin'] ];
			} else {
				$info    = is_object($upgrader) && method_exists($upgrader, 'plugin_info') ? $upgrader->plugin_info() : '';
				$plugins = is_string($info) && '' !== $info ? [ $info ] : [];
			}
			foreach ($plugins as $plugin) {
				self::_purge_for_plugin($plugin);
			}
		}, 10, 2);

		if (!defined('WCML_VERSION')) {
			return;
		}

		// Pages of a site that serves one currency per language are cached under the default vary: when the language-to-currency setup changes, old pages hit for everyone. Exchange-rate refreshes rewrite the same option but change none of the compared fields.
		// WCML also rewrites this option on anonymous admin-ajax requests (init_currencies()), where WPML hides hidden languages: their `default_currencies` key goes missing and an admin request writes it back as 0. Both mean "keep the stored currency", so they must compare equal, or anyone could trigger a purge on every request.
		add_action('update_option__wcml_settings', function ( $previous, $current ) {
			$project = function ( $settings ) {
				if (!is_array($settings)) {
					return $settings;
				}
				$defaults = isset($settings['default_currencies']) ? $settings['default_currencies'] : null;
				if (is_array($defaults)) {
					$defaults = array_filter($defaults);
					ksort($defaults);
				}
				// The currency order is kept: the first available currency of a language depends on it. Only the key order of each language list is ignored.
				$langs = [];
				$opts  = isset($settings['currency_options']) && is_array($settings['currency_options']) ? $settings['currency_options'] : [];
				foreach ($opts as $code => $opt) {
					$langs[ $code ] = is_array($opt) && isset($opt['languages']) ? $opt['languages'] : null;
					if (is_array($langs[ $code ])) {
						ksort($langs[ $code ]);
					}
				}
				return [
					isset($settings['currency_mode']) ? $settings['currency_mode'] : null,
					$defaults,
					// WCML reads the switch as (int) (wcml_is_multi_currency_on()).
					isset($settings['enable_multi_currency']) ? (int) $settings['enable_multi_currency'] : null,
					// Whether a cart is kept (sync) or asks first (clear) on a currency switch decides if the site qualifies for the default vary.
					isset($settings['cart_sync']['currency_switch']) ? (int) $settings['cart_sync']['currency_switch'] : null,
					$langs,
				];
			};
			if ($project($previous) !== $project($current)) {
				// Only cached pages carry the currency identity, so the page cache is enough.
				do_action('litespeed_purge', '*');
			}
		}, 10, 2);
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
	 * Appends WCML currency to the vary list, unless it is the shop currency.
	 *
	 * Guests in the shop currency keep the default vary: no vary cookie is set for
	 * them, so their first visit can be served from cache. Not when WCML can pick
	 * the currency from the visitor's location: one URL then serves visitors in
	 * different currencies, and only PHP can tell them apart.
	 *
	 * When every language of the site has exactly one currency (see
	 * _one_currency_per_language()), that currency is not part of the vary. Any other
	 * currency, the shop currency among them, keeps the vary: WCML can still resolve
	 * it for a request through the order referer or a third-party filter, and that
	 * must not land in the language's default bucket.
	 *
	 * Side effect: when WCML has produced its "Switching currency?" cart dialog for this
	 * request, the response is marked as a hard no-cache (the page is personalized).
	 *
	 * @since 3.0
	 * @access public
	 * @param array $vary_list The existing vary list.
	 * @return array The updated vary list, with WCML currency when it differs from the shop currency or can follow the visitor's location, or, on a one-currency-per-language site, from the language's currency.
	 */
	public static function apply_vary( $vary_list ) {
		global $woocommerce_wpml;
		// Control::finalize() returns before `litespeed_control_finalize` on a force-public URI, but Vary::finalize() still gets here. Not while a crawler hash is computed: that runs in the background.
		if (!self::$_lang_neutral && self::_dialog_shown()) {
			\LiteSpeed\Control::set_nocache_hard('wcml currency switching dialog');
		}

		if (empty(self::$_currency)) {
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

		$single = self::_lang_currency();
		// On a qualifying site with a language unknown for this request ($single is ''), every currency keeps the vary: the shop-currency exemption of the old rule does not apply there.
		if ('' !== $single || (!self::$_lang_neutral && [] !== self::_one_currency_per_language())) {
			// WCML re-applies its filters on every call: priority above 10 can change the currency after LSC noted it.
			$final = self::$_currency;
			if (is_object($woocommerce_wpml)
				&& isset($woocommerce_wpml->multi_currency)
				&& method_exists($woocommerce_wpml->multi_currency, 'get_client_currency')
			) {
				$filtered = $woocommerce_wpml->multi_currency->get_client_currency();
				if (is_string($filtered) && '' !== $filtered) {
					$final = $filtered;
				}
			}
			if ($final !== $single) {
				$vary_list['wcml_currency'] = $final;
			}
			return $vary_list;
		}

		if (self::_by_location() || get_option('woocommerce_currency', 'USD') !== self::$_currency) {
			$vary_list['wcml_currency'] = self::$_currency;
		}
		return $vary_list;
	}

	/**
	 * Point the Guest Mode script at the WordPress-based vary endpoint when the rendered currency needs a vary cookie.
	 *
	 * The lightweight guest.vary.php cannot know the currency, so it would issue the plain guest vary and the reload would hit a vary mismatch. The WordPress endpoint runs WCML and issues the full vary of a normal page request. The currency is resolved by WCML, never passed in the URL.
	 *
	 * @since 7.9.4
	 * @access public
	 * @param string $url Current guest vary endpoint.
	 * @return string The endpoint to use: the current language's home path and query, plus `litespeed_guest=1`.
	 */
	public static function guest_vary_url( $url ) {
		if (!function_exists('wcml_is_multi_currency_on') || !wcml_is_multi_currency_on()) {
			return $url;
		}
		if (empty(self::apply_vary([]))) {
			return $url;
		}
		// Path and query only: the request stays on the current host, even behind a scheme or alias mismatch.
		$home  = (string) apply_filters('wpml_home_url', home_url('/'));
		$path  = wp_parse_url($home, PHP_URL_PATH);
		$query = wp_parse_url($home, PHP_URL_QUERY);
		$path  = $path ? $path : '/';
		// A directory path needs its slash: without it a real directory answers the POST with a 301, which the browser retries as GET. A last segment with a dot is a file (index.php) and stays.
		if (false === strpos(substr($path, (int) strrpos($path, '/')), '.')) {
			$path = trailingslashit($path);
		}
		return add_query_arg('litespeed_guest', 1, $path . ($query ? '?' . $query : ''));
	}

	/**
	 * Inject the WCML vary row into the crawler cookie list.
	 *
	 * A currency that needs no vary cookie (the shop currency without location mode, Guest Mode off) maps to `_null`, so the crawler also runs it cookie-less.
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

		// One currency per language: visitors of every page carry the default vary, so a currency cookie can only miss. Crawl the default once.
		if ([] !== self::_one_currency_per_language()) {
			$selected = [ get_option('woocommerce_currency', 'USD') ];
		}

		$vals = [];
		foreach ($selected as $code) {
			if (self::_is_cookieless($code)) {
				$vals[] = '_null';
				continue;
			}
			// A false hash here is a HASH failure or LITESPEED_GUEST bypass, never proof of no vary: skip it.
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
		$vals = array_values(array_unique(array_merge($existing_vals, $vals)));
		// Without an existing or selected hash, the default guest crawl is already cookie-less.
		if (!array_diff($vals, [ '_null' ])) {
			return $cookies;
		}
		$other_rows[] = [
			'name' => $vary_name,
			'vals' => $vals,
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
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
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
				// On a one-currency-per-language site the crawler visits pages as any visitor does and must not force a currency.
				add_filter('wcml_client_currency', function ( $currency ) use ( $code ) {
					return [] !== self::_one_currency_per_language() ? $currency : $code;
				}, 0);
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
	 * Whether WCML can pick the visitor's currency from their location.
	 *
	 * True in currency-by-location mode (which WCML also forces when it runs without
	 * WPML) and in currency-by-language mode when a language's default currency is
	 * the visitor's location.
	 *
	 * @since 7.9.2
	 * @access private
	 * @return bool
	 */
	private static function _by_location() {
		// WCML 4.12.1+ covers both cases, and only while multi-currency is on.
		if (class_exists('WCML\MultiCurrency\Geolocation') && method_exists('WCML\MultiCurrency\Geolocation', 'isUsed')) {
			return \WCML\MultiCurrency\Geolocation::isUsed();
		}

		// Older WCML: the same two cases, read from its settings option.
		$settings = get_option('_wcml_settings', []);
		if (!is_array($settings)) {
			return false;
		}
		if (isset($settings['currency_mode']) && 'by_location' === $settings['currency_mode']) {
			return true;
		}
		return !empty($settings['default_currencies']) && is_array($settings['default_currencies']) && in_array('location', $settings['default_currencies'], true);
	}

	/**
	 * The currency of each language, when every language of the site has exactly one.
	 *
	 * Whether a URL's currency can be left out of the vary depends on the cookie a visitor brings to the next URL, so it is decided for the whole site: a single language that allows two currencies (WCML then keeps the stored one across a language switch) turns this off everywhere. So does a cart setting other than "keep the cart on a currency switch", because WCML's confirmation dialog needs PHP to run. Anything missing or of an unexpected type turns it off too, and the vary is then what it was before this check. The answer does not change within a request, so it is computed once per request.
	 *
	 * @since 7.9.4
	 * @access private
	 * @return array Language code => its only currency; empty when the site does not qualify.
	 */
	private static function _one_currency_per_language() {
		static $memo = null;
		if (null !== $memo) {
			return $memo;
		}
		$memo = [];

		if (!function_exists('wcml_is_multi_currency_on') || !wcml_is_multi_currency_on()) {
			return $memo;
		}
		// The effective mode: WCML runs by-language settings as by-location without WPML, so the stored `currency_mode` is not enough.
		$cls = 'WCML\MultiCurrency\Settings';
		if (!class_exists($cls)
			|| !method_exists($cls, 'isModeByLanguage')
			|| !method_exists($cls, 'getCurrenciesOptions')
			|| !method_exists($cls, 'getDefaultCurrencies')
			|| !$cls::isModeByLanguage()
			|| self::_by_location()
		) {
			return $memo;
		}
		// WCML's "Switching currency?" cart dialog (cart setting not SYNC) is built by PHP, which a cache hit skips: the cart would be converted silently.
		global $woocommerce_wpml;
		$switch = is_object($woocommerce_wpml) && isset($woocommerce_wpml->settings['cart_sync']['currency_switch']) ? $woocommerce_wpml->settings['cart_sync']['currency_switch'] : null;
		if (!defined('WCML_CART_SYNC') || !is_numeric($switch) || (int) WCML_CART_SYNC !== (int) $switch) {
			return $memo;
		}

		$options  = $cls::getCurrenciesOptions();
		$defaults = $cls::getDefaultCurrencies();
		if (!is_array($options) || !$options || !is_array($defaults)) {
			return $memo;
		}
		$langs = [];
		foreach ($options as $opt) {
			if (!is_array($opt) || !isset($opt['languages']) || !is_array($opt['languages'])) {
				return $memo;
			}
			$langs += $opt['languages'];
		}

		$map = [];
		foreach (array_keys($langs) as $lang) {
			$valid = [];
			foreach ($options as $code => $opt) {
				if (!array_key_exists($lang, $opt['languages'])) {
					return $memo;
				}
				$valid[ $code ] = (bool) $opt['languages'][ $lang ];
			}
			$only    = array_keys(array_filter($valid));
			$initial = isset($defaults[ $lang ]) ? $defaults[ $lang ] : '';
			// Exactly one valid currency, and the language's default (when set; `location` and `0` = keep the stored one) must not pick another: WCML returns a default without checking it.
			if (1 !== count($only) || (!empty($initial) && (!is_string($initial) || (string) $only[0] !== $initial))) {
				return [];
			}
			$map[ $lang ] = (string) $only[0];
		}

		// Last, as the cheap checks above end most sites. The languages WCML fills into every currency's settings in the admin: one missing there means the data does not match the runtime.
		global $sitepress;
		if (!is_object($sitepress) || !method_exists($sitepress, 'get_active_languages')) {
			return $memo;
		}
		$active = $sitepress->get_active_languages();
		if (!is_array($active) || !$active || array_diff_key($active, $langs)) {
			return $memo;
		}

		$memo = $map;
		return $memo;
	}

	/**
	 * Whether WCML has produced its "Switching currency?" cart dialog for this request.
	 *
	 * WCML keeps the HTML on its multi_currency object and prints it in wp_footer.
	 *
	 * @since 7.9.4
	 * @access private
	 * @return bool
	 */
	private static function _dialog_shown() {
		global $woocommerce_wpml;
		return is_object($woocommerce_wpml)
			&& isset($woocommerce_wpml->multi_currency)
			&& is_object($woocommerce_wpml->multi_currency)
			&& !empty($woocommerce_wpml->multi_currency->switching_currency_html);
	}

	/**
	 * Purge the page cache when WCML or WPML is activated, deactivated or upgraded.
	 *
	 * Only cached pages carry the currency identity, so the page cache is enough.
	 *
	 * @since 7.9.4
	 * @access private
	 * @param string $plugin Plugin basename from the activation, deactivation or upgrade hook.
	 * @return void
	 */
	private static function _purge_for_plugin( $plugin ) {
		if (
			(defined('WCML_PLUGIN_PATH') && plugin_basename(WCML_PLUGIN_PATH . '/wpml-woocommerce.php') === $plugin)
			|| (defined('WPML_PLUGIN_BASENAME') && WPML_PLUGIN_BASENAME === $plugin)
		) {
			do_action('litespeed_purge', '*');
		}
	}

	/**
	 * The only currency of the current language on a one-currency-per-language site.
	 *
	 * @since 7.9.4
	 * @access private
	 * @return string The currency code, or an empty string when the site does not qualify, the language is unknown, or a crawler vary hash is computed (its language is not the target URL's).
	 */
	private static function _lang_currency() {
		if (self::$_lang_neutral) {
			return '';
		}
		$map = self::_one_currency_per_language();
		if (!$map) {
			return '';
		}
		$lang = apply_filters('wpml_current_language', null);
		if (!is_string($lang) || '' === $lang || 'all' === $lang) {
			// As WCML does in the admin and AJAX.
			$lang = apply_filters('wpml_default_language', null);
		}
		return is_string($lang) && isset($map[ $lang ]) ? $map[ $lang ] : '';
	}

	/**
	 * Whether a guest in the given currency needs no vary cookie at all.
	 *
	 * Mirrors the guest vary list of Vary::finalize_default_vary(-1) with Guest Mode off. With Guest Mode on the crawler already presets a `_null` run.
	 *
	 * @since 7.9.2
	 * @access private
	 * @param string $currency Currency code.
	 * @return bool
	 */
	private static function _is_cookieless( $currency ) {
		if (apply_filters('litespeed_conf', \LiteSpeed\Base::O_GUEST)) {
			return false;
		}

		$prev                = self::$_currency;
		$prev_neutral        = self::$_lang_neutral;
		self::$_currency     = $currency;
		self::$_lang_neutral = true;
		try {
			$vary = apply_filters('litespeed_vary', []);
		} finally {
			self::$_currency     = $prev;
			self::$_lang_neutral = $prev_neutral;
		}
		return empty($vary);
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
		$prev                = self::$_currency;
		$prev_neutral        = self::$_lang_neutral;
		self::$_currency     = $currency;
		self::$_lang_neutral = true;
		try {
			$hash = \LiteSpeed\Vary::cls()->finalize_default_vary(-1);
		} finally {
			self::$_currency     = $prev;
			self::$_lang_neutral = $prev_neutral;
		}
		return $hash;
	}
}
