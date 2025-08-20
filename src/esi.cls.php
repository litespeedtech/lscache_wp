<?php
// phpcs:ignoreFile

/**
 * The ESI class.
 *
 * This is used to define all esi related functions.
 *
 * @since       1.1.3
 * @package     LiteSpeed
 */

namespace LiteSpeed;

defined('WPINC') || exit();

class ESI extends Root {

	const LOG_TAG = '⏺';

	private static $has_esi      = false;
	private static $_combine_ids = array();
	private $esi_args            = null;
	private $_esi_preserve_list  = array();
	private $_nonce_actions      = array( -1 => '' ); // val is cache control

	const QS_ACTION = 'lsesi';
	const QS_PARAMS = 'esi';
	const COMBO     = '__combo'; // ESI include combine='main' handler

	const PARAM_ARGS     = 'args';
	const PARAM_ID       = 'id';
	const PARAM_INSTANCE = 'instance';
	const PARAM_NAME     = 'name';

	const WIDGET_O_ESIENABLE = 'widget_esi_enable';
	const WIDGET_O_TTL       = 'widget_ttl';

	/**
	 * Confructor of ESI
	 *
	 * @since  1.2.0
	 * @since  4.0 Change to be after Vary init in hook 'after_setup_theme'
	 */
	public function init() {
		/**
		 * Bypass ESI related funcs if disabled ESI to fix potential DIVI compatibility issue
		 *
		 * @since  2.9.7.2
		 */
		if (Router::is_ajax() || !$this->cls('Router')->esi_enabled()) {
			return;
		}

		// Guest mode, don't need to use ESI
		if (defined('LITESPEED_GUEST') && LITESPEED_GUEST) {
			return;
		}

		if (defined('LITESPEED_ESI_OFF')) {
			return;
		}

		// If page is not cacheable
		if (defined('DONOTCACHEPAGE') && apply_filters('litespeed_const_DONOTCACHEPAGE', DONOTCACHEPAGE)) {
			return;
		}

		// Init ESI in `after_setup_theme` hook after detected if LITESPEED_DISABLE_ALL is ON or not
		$this->_hooks();

		/**
		 * Overwrite wp_create_nonce func
		 *
		 * @since  2.9.5
		 */
		$this->_transform_nonce();

		!defined('LITESPEED_ESI_INITED') && define('LITESPEED_ESI_INITED', true);
	}

	/**
	 * Init ESI related hooks
	 *
	 * Load delayed by hook to give the ability to bypass by LITESPEED_DISABLE_ALL const
	 *
	 * @since 2.9.7.2
	 * @since  4.0 Changed to private from public
	 * @access private
	 */
	private function _hooks() {
		add_filter('template_include', array( $this, 'esi_template' ), 99999);

		add_action('load-widgets.php', __NAMESPACE__ . '\Purge::purge_widget');
		add_action('wp_update_comment_count', __NAMESPACE__ . '\Purge::purge_comment_widget');

		/**
		 * Recover REQUEST_URI
		 *
		 * @since  1.8.1
		 */
		if (!empty($_GET[self::QS_ACTION])) {
			self::debug('ESI req');
			$this->_register_esi_actions();
		}

		/**
		 * Shortcode ESI
		 *
		 * To use it, just change the original shortcode as below:
		 *      old: [someshortcode aa='bb']
		 *      new: [esi someshortcode aa='bb' cache='private,no-vary' ttl='600']
		 *
		 *  1. `cache` attribute is optional, default to 'public,no-vary'.
		 *  2. `ttl` attribute is optional, default is your public TTL setting.
		 *  3. `_ls_silence` attribute is optional, default is false.
		 *
		 * @since  2.8
		 * @since  2.8.1 Check is_admin for Elementor compatibility #726013
		 */
		if (!is_admin()) {
			add_shortcode('esi', array( $this, 'shortcode' ));
		}
	}

	/**
	 * Take over all nonce calls and transform to ESI
	 *
	 * @since  2.9.5
	 */
	private function _transform_nonce() {
		if (is_admin()) {
			return;
		}

		// Load ESI nonces in conf
		$nonces = $this->conf(Base::O_ESI_NONCE);
		add_filter('litespeed_esi_nonces', array( $this->cls('Data'), 'load_esi_nonces' ));
		if ($nonces = apply_filters('litespeed_esi_nonces', $nonces)) {
			foreach ($nonces as $action) {
				$this->nonce_action($action);
			}
		}

		add_action('litespeed_nonce', array( $this, 'nonce_action' ));
	}

	/**
	 * Register a new nonce action to convert it to ESI
	 *
	 * @since  2.9.5
	 */
	public function nonce_action( $action ) {
		// Split the Cache Control
		$action  = explode(' ', $action);
		$control = !empty($action[1]) ? $action[1] : '';
		$action  = $action[0];

		// Wildcard supported
		$action = Utility::wildcard2regex($action);

		if (array_key_exists($action, $this->_nonce_actions)) {
			return;
		}

		$this->_nonce_actions[$action] = $control;

		// Debug2::debug('[ESI] Appended nonce action to nonce list [action] ' . $action);
	}

	/**
	 * Check if an action is registered to replace ESI
	 *
	 * @since 2.9.5
	 */
	public function is_nonce_action( $action ) {
		// If GM not run yet, then ESI not init yet, then ESI nonce will not be allowed even nonce func replaced.
		if (!defined('LITESPEED_ESI_INITED')) {
			return null;
		}

		if (is_admin()) {
			return null;
		}

		if (defined('LITESPEED_ESI_OFF')) {
			return null;
		}

		foreach ($this->_nonce_actions as $k => $v) {
			if (strpos($k, '*') !== false) {
				if (preg_match('#' . $k . '#iU', $action)) {
					return $v;
				}
			} elseif ($k == $action) {
				return $v;
			}
		}

		return null;
	}

	/**
	 * Shortcode ESI
	 *
	 * @since 2.8
	 * @access public
	 */
	public function shortcode( $atts ) {
		if (empty($atts[0])) {
			Debug2::debug('[ESI] ===shortcode wrong format', $atts);
			return 'Wrong shortcode esi format';
		}

		$cache = 'public,no-vary';
		if (!empty($atts['cache'])) {
			$cache = $atts['cache'];
			unset($atts['cache']);
		}

		$silence = false;
		if (!empty($atts['_ls_silence'])) {
			$silence = true;
		}

		do_action('litespeed_esi_shortcode-' . $atts[0]);

		// Show ESI link
		return $this->sub_esi_block('esi', 'esi-shortcode', $atts, $cache, $silence);
	}

	/**
	 * Check if the requested page has esi elements. If so, return esi on
	 * header.
	 *
	 * @since 1.1.3
	 * @access public
	 * @return string Esi On header if request has esi, empty string otherwise.
	 */
	public static function has_esi() {
		return self::$has_esi;
	}

	/**
	 * Sets that the requested page has esi elements.
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function set_has_esi() {
		self::$has_esi = true;
	}

	/**
	 * Register all of the hooks related to the esi logic of the plugin.
	 * Specifically when the page IS an esi page.
	 *
	 * @since    1.1.3
	 * @access   private
	 */
	private function _register_esi_actions() {
		/**
		 * This hook is in `init`
		 * For any plugin need to check if page is ESI, use `LSCACHE_IS_ESI` check after `init` hook
		 */
		!defined('LSCACHE_IS_ESI') && define('LSCACHE_IS_ESI', $_GET[self::QS_ACTION]); // Reused this to ESI block ID

		!empty($_SERVER['ESI_REFERER']) && defined('LSCWP_LOG') && Debug2::debug('[ESI] ESI_REFERER: ' . $_SERVER['ESI_REFERER']);

		/**
		 * Only when ESI's parent is not REST, replace REQUEST_URI to avoid breaking WP5 editor REST call
		 *
		 * @since 2.9.3
		 */
		if (!empty($_SERVER['ESI_REFERER']) && !$this->cls('REST')->is_rest($_SERVER['ESI_REFERER'])) {
			self::debug('overwrite REQUEST_URI to ESI_REFERER [from] ' . $_SERVER['REQUEST_URI'] . ' [to] ' . $_SERVER['ESI_REFERER']);
			if (!empty($_SERVER['ESI_REFERER'])) {
				$_SERVER['REQUEST_URI'] = $_SERVER['ESI_REFERER'];
				if (substr(get_option('permalink_structure'), -1) === '/' && strpos($_SERVER['ESI_REFERER'], '?') === false) {
					$_SERVER['REQUEST_URI'] = trailingslashit($_SERVER['ESI_REFERER']);
				}
			}
			// Prevent from 301 redirecting
			if (!empty($_SERVER['SCRIPT_URI'])) {
				$SCRIPT_URI         = parse_url($_SERVER['SCRIPT_URI']);
				$SCRIPT_URI['path'] = $_SERVER['REQUEST_URI'];
				Utility::compatibility();
				$_SERVER['SCRIPT_URI'] = http_build_url($SCRIPT_URI);
			}
		}

		if (!empty($_SERVER['ESI_CONTENT_TYPE']) && strpos($_SERVER['ESI_CONTENT_TYPE'], 'application/json') === 0) {
			add_filter('litespeed_is_json', '__return_true');
		}

		/**
		 * Make REST call be able to parse ESI
		 * NOTE: Not effective due to ESI req are all to `/` yet
		 *
		 * @since 2.9.4
		 */
		add_action('rest_api_init', array( $this, 'load_esi_block' ), 101);

		// Register ESI blocks
		add_action('litespeed_esi_load-widget', array( $this, 'load_widget_block' ));
		add_action('litespeed_esi_load-admin-bar', array( $this, 'load_admin_bar_block' ));
		add_action('litespeed_esi_load-comment-form', array( $this, 'load_comment_form_block' ));

		add_action('litespeed_esi_load-nonce', array( $this, 'load_nonce_block' ));
		add_action('litespeed_esi_load-esi', array( $this, 'load_esi_shortcode' ));

		add_action('litespeed_esi_load-' . self::COMBO, array( $this, 'load_combo' ));
	}

	/**
	 * Hooked to the template_include action.
	 * Selects the esi template file when the post type is a LiteSpeed ESI page.
	 *
	 * @since 1.1.3
	 * @access public
	 * @param string $template The template path filtered.
	 * @return string The new template path.
	 */
	public function esi_template( $template ) {
		// Check if is an ESI request
		if (defined('LSCACHE_IS_ESI')) {
			self::debug('calling ESI template');

			return LSCWP_DIR . 'tpl/esi.tpl.php';
		}
		self::debug('calling default template');
		$this->_register_not_esi_actions();
		return $template;
	}

	/**
	 * Register all of the hooks related to the esi logic of the plugin.
	 * Specifically when the page is NOT an esi page.
	 *
	 * @since    1.1.3
	 * @access   private
	 */
	private function _register_not_esi_actions() {
		do_action('litespeed_tpl_normal');

		if (!Control::is_cacheable()) {
			return;
		}

		if (Router::is_ajax()) {
			return;
		}

		add_filter('widget_display_callback', array( $this, 'sub_widget_block' ), 0, 3);

		// Add admin_bar esi
		if (Router::is_logged_in()) {
			remove_action('wp_body_open', 'wp_admin_bar_render', 0); // Remove default Admin bar. Fix https://github.com/elementor/elementor/issues/25198
			remove_action('wp_footer', 'wp_admin_bar_render', 1000);
			add_action('wp_footer', array( $this, 'sub_admin_bar_block' ), 1000);
		}

		// Add comment forum esi for logged-in user or commenter
		if (!Router::is_ajax() && Vary::has_vary()) {
			add_filter('comment_form_defaults', array( $this, 'register_comment_form_actions' ));
		}
	}

	/**
	 * Set an ESI to be combine='sub'
	 *
	 * @since  3.4.2
	 */
	public static function combine( $block_id ) {
		if (!isset($_SERVER['X-LSCACHE']) || strpos($_SERVER['X-LSCACHE'], 'combine') === false) {
			return;
		}

		if (in_array($block_id, self::$_combine_ids)) {
			return;
		}

		self::$_combine_ids[] = $block_id;
	}

	/**
	 * Load combined ESI
	 *
	 * @since  3.4.2
	 */
	public function load_combo() {
		Control::set_nocache('ESI combine request');

		if (empty($_POST['esi_include'])) {
			return;
		}

		self::set_has_esi();

		Debug2::debug('[ESI] 🍔 Load combo', $_POST['esi_include']);

		$output = '';
		foreach ($_POST['esi_include'] as $url) {
			$qs = parse_url(htmlspecialchars_decode($url), PHP_URL_QUERY);
			parse_str($qs, $qs);
			if (empty($qs[self::QS_ACTION])) {
				continue;
			}
			$esi_id       = $qs[self::QS_ACTION];
			$esi_param    = !empty($qs[self::QS_PARAMS]) ? $this->_parse_esi_param($qs[self::QS_PARAMS]) : false;
			$inline_param = apply_filters('litespeed_esi_inline-' . $esi_id, array(), $esi_param); // Returned array need to be [ val, control, tag ]
			if ($inline_param) {
				$output .= self::_build_inline($url, $inline_param);
			}
		}

		echo $output;
	}

	/**
	 * Build a whole inline segment
	 *
	 * @since  3.4.2
	 */
	private static function _build_inline( $url, $inline_param ) {
		if (!$url || empty($inline_param['val']) || empty($inline_param['control']) || empty($inline_param['tag'])) {
			return '';
		}

		$url     = esc_attr($url);
		$control = esc_attr($inline_param['control']);
		$tag     = esc_attr($inline_param['tag']);

		return "<esi:inline name='$url' cache-control='" . $control . "' cache-tag='" . $tag . "'>" . $inline_param['val'] . '</esi:inline>';
	}

	/**
	 * Build the esi url. This method will build the html comment wrapper as well as serialize and encode the parameter array.
	 *
	 * The block_id parameter should contain alphanumeric and '-_' only.
	 *
	 * @since 1.1.3
	 * @access private
	 * @param string $block_id     The id to use to display the correct esi block.
	 * @param string $wrapper      The wrapper for the esi comments.
	 * @param array  $params       The esi parameters.
	 * @param string $control      The cache control attribute if any.
	 * @param bool   $silence      If generate wrapper comment or not
	 * @param bool   $preserved    If this ESI block is used in any filter, need to temporarily convert it to a string to avoid the HTML tag being removed/filtered.
	 * @param bool   $svar         If store the value in memory or not, in memory will be faster
	 * @param array  $inline_param If show the current value for current request( this can avoid multiple esi requests in first time cache generating process )
	 */
	public function sub_esi_block(
		$block_id,
		$wrapper,
		$params = array(),
		$control = 'private,no-vary',
		$silence = false,
		$preserved = false,
		$svar = false,
		$inline_param = array()
	) {
		if (empty($block_id) || !is_array($params) || preg_match('/[^\w-]/', $block_id)) {
			return false;
		}

		if (defined('LITESPEED_ESI_OFF')) {
			Debug2::debug('[ESI] ESI OFF so force loading [block_id] ' . $block_id);
			do_action('litespeed_esi_load-' . $block_id, $params);
			return;
		}

		if ($silence) {
			// Don't add comment to esi block ( original for nonce used in tag property data-nonce='esi_block' )
			$params['_ls_silence'] = true;
		}

		if ($this->cls('REST')->is_rest() || $this->cls('REST')->is_internal_rest()) {
			$params['is_json'] = 1;
		}

		$params  = apply_filters('litespeed_esi_params', $params, $block_id);
		$control = apply_filters('litespeed_esi_control', $control, $block_id);

		if (!is_array($params) || !is_string($control)) {
			defined('LSCWP_LOG') && Debug2::debug("[ESI] 🛑 Sub hooks returned Params: \n" . var_export($params, true) . "\ncache control: \n" . var_export($control, true));

			return false;
		}

		// Build params for URL
		$appended_params = array(
			self::QS_ACTION => $block_id,
		);
		if (!empty($control)) {
			$appended_params['_control'] = $control;
		}
		if ($params) {
			$appended_params[self::QS_PARAMS] = base64_encode(\json_encode($params));
			Debug2::debug2('[ESI] param ', $params);
		}

		// Append hash
		$appended_params['_hash'] = $this->_gen_esi_md5($appended_params);

		/**
		 * Escape potential chars
		 *
		 * @since 2.9.4
		 */
		$appended_params = array_map('urlencode', $appended_params);

		// Generate ESI URL
		$url = add_query_arg($appended_params, trailingslashit(wp_make_link_relative(home_url())));

		$output = '';
		if ($inline_param) {
			$output .= self::_build_inline($url, $inline_param);
		}

		$output .= "<esi:include src='$url'";
		if (!empty($control)) {
			$control = esc_attr($control);
			$output .= " cache-control='$control'";
		}
		if ($svar) {
			$output .= " as-var='1'";
		}
		if (in_array($block_id, self::$_combine_ids)) {
			$output .= " combine='sub'";
		}
		if ($block_id == self::COMBO && isset($_SERVER['X-LSCACHE']) && strpos($_SERVER['X-LSCACHE'], 'combine') !== false) {
			$output .= " combine='main'";
		}
		$output .= ' />';

		if (!$silence) {
			$output = "<!-- lscwp $wrapper -->$output<!-- lscwp $wrapper esi end -->";
		}

		self::debug("💕  [BLock_ID] $block_id \t[wrapper] $wrapper \t\t[Control] $control");
		self::debug2($output);

		self::set_has_esi();

		// Convert to string to avoid html chars filter when using
		// Will reverse the buffer when output in self::finalize()
		if ($preserved) {
			$hash                            = md5($output);
			$this->_esi_preserve_list[$hash] = $output;
			self::debug("Preserved to $hash");

			return $hash;
		}

		return $output;
	}

	/**
	 * Generate ESI hash md5
	 *
	 * @since  2.9.6
	 * @access private
	 */
	private function _gen_esi_md5( $params ) {
		$keys = array( self::QS_ACTION, '_control', self::QS_PARAMS );

		$str = '';
		foreach ($keys as $v) {
			if (isset($params[$v]) && is_string($params[$v])) {
				$str .= $params[$v];
			}
		}
		Debug2::debug2('[ESI] md5_string=' . $str);

		return md5($this->conf(Base::HASH) . $str);
	}

	/**
	 * Parses the request parameters on an ESI request
	 *
	 * @since 1.1.3
	 * @access private
	 */
	private function _parse_esi_param( $qs_params = false ) {
		$req_params = false;
		if ($qs_params) {
			$req_params = $qs_params;
		} elseif (isset($_REQUEST[self::QS_PARAMS])) {
			$req_params = $_REQUEST[self::QS_PARAMS];
		}

		if (!$req_params) {
			return false;
		}

		$unencrypted = base64_decode($req_params);
		if ($unencrypted === false) {
			return false;
		}

		Debug2::debug2('[ESI] params', $unencrypted);
		// $unencoded = urldecode($unencrypted); no need to do this as $_GET is already parsed
		$params = \json_decode($unencrypted, true);

		return $params;
	}

	/**
	 * Select the correct esi output based on the parameters in an ESI request.
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public function load_esi_block() {
		/**
		 * Validate if is a legal ESI req
		 *
		 * @since 2.9.6
		 */
		if (empty($_GET['_hash']) || $this->_gen_esi_md5($_GET) != $_GET['_hash']) {
			Debug2::debug('[ESI] ❌ Failed to validate _hash');
			return;
		}

		$params = $this->_parse_esi_param();

		if (defined('LSCWP_LOG')) {
			$logInfo = '[ESI] ⭕ ';
			if (!empty($params[self::PARAM_NAME])) {
				$logInfo .= ' Name: ' . $params[self::PARAM_NAME] . ' ----- ';
			}
			$logInfo .= ' [ID] ' . LSCACHE_IS_ESI;
			Debug2::debug($logInfo);
		}

		if (!empty($params['_ls_silence'])) {
			!defined('LSCACHE_ESI_SILENCE') && define('LSCACHE_ESI_SILENCE', true);
		}

		/**
		 * Buffer needs to be JSON format
		 *
		 * @since  2.9.4
		 */
		if (!empty($params['is_json'])) {
			add_filter('litespeed_is_json', '__return_true');
		}

		Tag::add(rtrim(Tag::TYPE_ESI, '.'));
		Tag::add(Tag::TYPE_ESI . LSCACHE_IS_ESI);

		// Debug2::debug(var_export($params, true ));

		/**
		 * Handle default cache control 'private,no-vary' for sub_esi_block()   @ticket #923505
		 *
		 * @since  2.2.3
		 */
		if (!empty($_GET['_control'])) {
			$control = explode(',', $_GET['_control']);
			if (in_array('private', $control)) {
				Control::set_private();
			}

			if (in_array('no-vary', $control)) {
				Control::set_no_vary();
			}
		}

		do_action('litespeed_esi_load-' . LSCACHE_IS_ESI, $params);
	}

	// The *_sub_* functions are helpers for the sub_* functions.
	// The *_load_* functions are helpers for the load_* functions.

	/**
	 * Loads the default options for default WordPress widgets.
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function widget_default_options( $options, $widget ) {
		if (!is_array($options)) {
			return $options;
		}

		$widget_name = get_class($widget);
		switch ($widget_name) {
			case 'WP_Widget_Recent_Posts':
			case 'WP_Widget_Recent_Comments':
            $options[self::WIDGET_O_ESIENABLE] = Base::VAL_OFF;
            $options[self::WIDGET_O_TTL]       = 86400;
				break;
			default:
				break;
		}
		return $options;
	}

	/**
	 * Hooked to the widget_display_callback filter.
	 * If the admin configured the widget to display via esi, this function
	 * will set up the esi request and cancel the widget display.
	 *
	 * @since 1.1.3
	 * @access public
	 * @param array      $instance Parameter used to build the widget.
	 * @param \WP_Widget $widget The widget to build.
	 * @param array      $args Parameter used to build the widget.
	 * @return mixed Return false if display through esi, instance otherwise.
	 */
	public function sub_widget_block( $instance, $widget, $args ) {
		// #210407
		if (!is_array($instance)) {
			return $instance;
		}

		$name = get_class($widget);
		if (!isset($instance[Base::OPTION_NAME])) {
			return $instance;
		}
		$options = $instance[Base::OPTION_NAME];
		if (!isset($options) || !$options[self::WIDGET_O_ESIENABLE]) {
			defined('LSCWP_LOG') && Debug2::debug('ESI 0 ' . $name . ': ' . (!isset($options) ? 'not set' : 'set off'));

			return $instance;
		}

		$esi_private = $options[self::WIDGET_O_ESIENABLE] == Base::VAL_ON2 ? 'private,' : '';

		$params = array(
			self::PARAM_NAME => $name,
			self::PARAM_ID => $widget->id,
			self::PARAM_INSTANCE => $instance,
			self::PARAM_ARGS => $args,
		);

		echo $this->sub_esi_block('widget', 'widget ' . $name, $params, $esi_private . 'no-vary');

		return false;
	}

	/**
	 * Hooked to the wp_footer action.
	 * Sets up the ESI request for the admin bar.
	 *
	 * @access public
	 * @since 1.1.3
	 * @global type $wp_admin_bar
	 */
	public function sub_admin_bar_block() {
		global $wp_admin_bar;

		if (!is_admin_bar_showing() || !is_object($wp_admin_bar)) {
			return;
		}

		// To make each admin bar ESI request different for `Edit` button different link
		$params = array(
			'ref' => $_SERVER['REQUEST_URI'],
		);

		echo $this->sub_esi_block('admin-bar', 'adminbar', $params);
	}

	/**
	 * Parses the esi input parameters and generates the widget for esi display.
	 *
	 * @access public
	 * @since 1.1.3
	 * @global $wp_widget_factory
	 * @param array $params Input parameters needed to correctly display widget
	 */
	public function load_widget_block( $params ) {
		// global $wp_widget_factory;
		// $widget = $wp_widget_factory->widgets[ $params[ self::PARAM_NAME ] ];
		$option = $params[self::PARAM_INSTANCE];
		$option = $option[Base::OPTION_NAME];

		// Since we only reach here via esi, safe to assume setting exists.
		$ttl = $option[self::WIDGET_O_TTL];
		defined('LSCWP_LOG') && Debug2::debug('ESI widget render: name ' . $params[self::PARAM_NAME] . ', id ' . $params[self::PARAM_ID] . ', ttl ' . $ttl);
		if ($ttl == 0) {
			Control::set_nocache('ESI Widget time to live set to 0');
		} else {
			Control::set_custom_ttl($ttl);

			if ($option[self::WIDGET_O_ESIENABLE] == Base::VAL_ON2) {
				Control::set_private();
			}
			Control::set_no_vary();
			Tag::add(Tag::TYPE_WIDGET . $params[self::PARAM_ID]);
		}
		the_widget($params[self::PARAM_NAME], $params[self::PARAM_INSTANCE], $params[self::PARAM_ARGS]);
	}

	/**
	 * Generates the admin bar for esi display.
	 *
	 * @access public
	 * @since 1.1.3
	 */
	public function load_admin_bar_block( $params ) {
		global $wp_the_query;

		if (!empty($params['ref'])) {
			$ref_qs = parse_url($params['ref'], PHP_URL_QUERY);
			if (!empty($ref_qs)) {
				parse_str($ref_qs, $ref_qs_arr);

				if (!empty($ref_qs_arr)) {
					foreach ($ref_qs_arr as $k => $v) {
						$_GET[$k] = $v;
					}
				}
			}
		}

		// Needed when permalink structure is "Plain"
		if (!isset($wp_the_query)) {
			wp();
		}

		wp_admin_bar_render();
		if (!$this->conf(Base::O_ESI_CACHE_ADMBAR)) {
			Control::set_nocache('build-in set to not cacheable');
		} else {
			Control::set_private();
			Control::set_no_vary();
		}

		defined('LSCWP_LOG') && Debug2::debug('ESI: adminbar ref: ' . $_SERVER['REQUEST_URI']);
	}

	/**
	 * Parses the esi input parameters and generates the comment form for esi display.
	 *
	 * @access public
	 * @since 1.1.3
	 * @param array $params Input parameters needed to correctly display comment form
	 */
	public function load_comment_form_block( $params ) {
		comment_form($params[self::PARAM_ARGS], $params[self::PARAM_ID]);

		if (!$this->conf(Base::O_ESI_CACHE_COMMFORM)) {
			Control::set_nocache('build-in set to not cacheable');
		} else {
			// by default comment form is public
			if (Vary::has_vary()) {
				Control::set_private();
				Control::set_no_vary();
			}
		}
	}

	/**
	 * Generate nonce for certain action
	 *
	 * @access public
	 * @since 2.6
	 */
	public function load_nonce_block( $params ) {
		$action = $params['action'];

		Debug2::debug('[ESI] load_nonce_block [action] ' . $action);

		// set nonce TTL to half day
		Control::set_custom_ttl(43200);

		if (Router::is_logged_in()) {
			Control::set_private();
		}

		if (function_exists('wp_create_nonce_litespeed_esi')) {
			echo wp_create_nonce_litespeed_esi($action);
		} else {
			echo wp_create_nonce($action);
		}
	}

	/**
	 * Show original shortcode
	 *
	 * @access public
	 * @since 2.8
	 */
	public function load_esi_shortcode( $params ) {
		if (isset($params['ttl'])) {
			if (!$params['ttl']) {
				Control::set_nocache('ESI shortcode att ttl=0');
			} else {
				Control::set_custom_ttl($params['ttl']);
			}
			unset($params['ttl']);
		}

		// Replace to original shortcode
		$shortcode = $params[0];
		$atts_ori  = array();
		foreach ($params as $k => $v) {
			if ($k === 0) {
				continue;
			}

			$atts_ori[] = is_string($k) ? "$k='" . addslashes($v) . "'" : $v;
		}

		Tag::add(Tag::TYPE_ESI . "esi.$shortcode");

		// Output original shortcode final content
		echo do_shortcode("[$shortcode " . implode(' ', $atts_ori) . ' ]');
	}

	/**
	 * Hooked to the comment_form_defaults filter.
	 * Stores the default comment form settings.
	 * If sub_comment_form_block is triggered, the output buffer is cleared and an esi block is added. The remaining comment form is also buffered and cleared.
	 * Else there is no need to make the comment form ESI.
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public function register_comment_form_actions( $defaults ) {
		$this->esi_args = $defaults;
		echo GUI::clean_wrapper_begin();
		add_filter('comment_form_submit_button', array( $this, 'sub_comment_form_btn' ), 1000, 2); // To save the params passed in
		add_action('comment_form', array( $this, 'sub_comment_form_block' ), 1000);
		return $defaults;
	}

	/**
	 * Store the args passed in comment_form for the ESI comment param usage in `$this->sub_comment_form_block()`
	 *
	 * @since  3.4
	 * @access public
	 */
	public function sub_comment_form_btn( $unused, $args ) {
		if (empty($args) || empty($this->esi_args)) {
			Debug2::debug('comment form args empty?');
			return $unused;
		}
		$esi_args = array();

		// compare current args with default ones
		foreach ($args as $k => $v) {
			if (!isset($this->esi_args[$k])) {
				$esi_args[$k] = $v;
			} elseif (is_array($v)) {
				$diff = array_diff_assoc($v, $this->esi_args[$k]);
				if (!empty($diff)) {
					$esi_args[$k] = $diff;
				}
			} elseif ($v !== $this->esi_args[$k]) {
				$esi_args[$k] = $v;
			}
		}

		$this->esi_args = $esi_args;

		return $unused;
	}

	/**
	 * Hooked to the comment_form_submit_button filter.
	 *
	 * This method will compare the used comment form args against the default args. The difference will be passed to the esi request.
	 *
	 * @access public
	 * @since 1.1.3
	 */
	public function sub_comment_form_block( $post_id ) {
		echo GUI::clean_wrapper_end();
		$params = array(
			self::PARAM_ID => $post_id,
			self::PARAM_ARGS => $this->esi_args,
		);

		echo $this->sub_esi_block('comment-form', 'comment form', $params);
		echo GUI::clean_wrapper_begin();
		add_action('comment_form_after', array( $this, 'comment_form_sub_clean' ));
	}

	/**
	 * Hooked to the comment_form_after action.
	 * Cleans up the remaining comment form output.
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public function comment_form_sub_clean() {
		echo GUI::clean_wrapper_end();
	}

	/**
	 * Replace preserved blocks
	 *
	 * @since  2.6
	 * @access public
	 */
	public function finalize( $buffer ) {
		// Prepend combo esi block
		if (self::$_combine_ids) {
			Debug2::debug('[ESI] 🍔 Enabled combo');
			$esi_block = $this->sub_esi_block(self::COMBO, '__COMBINE_MAIN__', array(), 'no-cache', true);
			$buffer    = $esi_block . $buffer;
		}

		// Bypass if no preserved list to be replaced
		if (!$this->_esi_preserve_list) {
			return $buffer;
		}

		$keys = array_keys($this->_esi_preserve_list);

		Debug2::debug('[ESI] replacing preserved blocks', $keys);

		$buffer = str_replace($keys, $this->_esi_preserve_list, $buffer);

		return $buffer;
	}

	/**
	 * Check if the content contains preserved list or not
	 *
	 * @since  3.3
	 */
	public function contain_preserve_esi( $content ) {
		$hit_list = array();
		foreach ($this->_esi_preserve_list as $k => $v) {
			if (strpos($content, '"' . $k . '"') !== false) {
				$hit_list[] = '"' . $k . '"';
			}
			if (strpos($content, "'" . $k . "'") !== false) {
				$hit_list[] = "'" . $k . "'";
			}
		}
		return $hit_list;
	}
}
