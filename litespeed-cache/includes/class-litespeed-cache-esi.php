<?php

/**
 * The esi class.
 *
 * This is used to define all esi related functions.
 *
 * @since      1.1.0
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/includes
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Esi
{
	private static $instance;

	private $has_esi = false;
	private $esi_args = null;

	const URL = '/lscacheesi/';
	const POSTTYPE = 'lscacheesi';

	const QS_ACTION = 'action=lscache';
	const QS_PARAMS = 'lscache';

	const PARAM_ARGS = 'args';
	const PARAM_BLOCK_ID = 'block_id';
	const PARAM_ID = 'id';
	const PARAM_INSTANCE = 'instance';
	const PARAM_NAME = 'name';

	const CACHECTRL_PRIV = 'no-vary,private';

	const WIDGET_OPID_ESIENABLE = 'widget_esi_enable';
	const WIDGET_OPID_TTL = 'widget_ttl';

	/**
	 *
	 *
	 * @since    1.1.0
	 */
	private function __construct()
	{
		add_action('litespeed_cache_is_esi_template',
			array($this, 'register_esi_actions'));
		add_action('litespeed_cache_is_not_esi_template',
			array($this, 'register_not_esi_actions'));
	}


	public static function get_instance()
	{
		if (!isset(self::$instance)) {
			self::$instance = new LiteSpeed_Cache_Esi();
		}
		return self::$instance;
	}

	/**
	 * Check if the requested page has esi elements. If so, return esi on
	 * header.
	 *
	 * @access private
	 * @since 1.1.0
	 * @return string Esi On header if request has esi, empty string otherwise.
	 */
	public function has_esi()
	{
		return $this->has_esi;
	}

	/**
	 * Sets that the requested page has esi elements.
	 *
	 * @access public
	 * @since 1.1.0
	 */
	public function set_has_esi()
	{
		$this->has_esi = true;
	}

	/**
	 * Registers the LiteSpeed ESI post type.
	 * ONLY for frontend
	 *
	 * @access public
	 * @since 1.1.0
	 */
	public static function register_post_type()
	{
		if (post_type_exists(self::POSTTYPE)) return;

		register_post_type(
			self::POSTTYPE,
			array(
				'labels' => array(
					'name' => __('Lscacheesi', 'litespeed-cache')
				),
				'description' => __('Description of post type', 'litespeed-cache'),
				'public' => false,
				'publicly_queryable' => true,
				'supports' => false,
				'rewrite' => array('slug' => 'lscacheesi'),
				'query_var' => true
			)
		);
	}

	/**
	 * Convert esi requests to esi post type
	 * ONLY for backend
	 *
	 * @access public
	 * @since 1.1.0
	 */
	public function add_rewrite_rule_esi(){
		add_rewrite_rule('lscacheesi/?',
			'index.php?post_type=lscacheesi', 'top');
	}

	/**
	 * Hooked to the template_include action.
	 * Selects the esi template file when the post type is a LiteSpeed ESI page.
	 *
	 * @access public
	 * @since 1.1.0
	 * @global type $post_type
	 * @param string $template The template path filtered.
	 * @return string The new template path.
	 */
	public function esi_template($template)
	{
		global $post_type;

		if ($post_type == self::POSTTYPE) {
			define('LSCACHE_IS_ESI', true);
			do_action('litespeed_cache_is_esi_template');
			$cache = LiteSpeed_Cache::plugin();
			if (!$cache->config(LiteSpeed_Cache_Config::OPID_ESI_CACHE)) {
				LiteSpeed_Cache_Tags::set_noncacheable();
			}
			return plugin_dir_path(dirname(__FILE__))
				. 'includes/litespeed-cache-esi.php';
		}
		do_action('litespeed_cache_is_not_esi_template');
		return $template;
	}

	/**
	 * Register all of the hooks related to the esi logic of the plugin.
	 * Specifically when the page IS an esi page.
	 *
	 * @since    1.1.0
	 * @access   public
	 */
	public function register_esi_actions()
	{
		add_action('litespeed_cache_load_esi_block-widget',
			array($this, 'load_widget_block'));
		add_action('litespeed_cache_load_esi_block-admin-bar',
			array($this, 'load_admin_bar_block'));
		add_action('litespeed_cache_load_esi_block-comment-form',
			array($this, 'load_comment_form_block'));
		add_action('litespeed_cache_load_esi_block-comments',
			array($this, 'load_comments_block'));

		if ((defined('DOING_AJAX') && DOING_AJAX)) {
			return;
		}

		if (LiteSpeed_Cache::plugin()->get_user_status()) {
			add_filter('comment_form_defaults',
				array($this, 'register_comment_form_actions'));
		}
	}

	/**
	 * Register all of the hooks related to the esi logic of the plugin.
	 * Specifically when the page is NOT an esi page.
	 *
	 * @since    1.1.0
	 * @access   public
	 */
	public function register_not_esi_actions()
	{
		$lscache = LiteSpeed_Cache::plugin();
		add_filter('comments_array', array($this, 'sub_comments_block'));

		if ((defined('DOING_AJAX') && constant(DOING_AJAX))) {
			return;
		}

		add_filter('widget_display_callback',
			array($this, 'sub_widget_block'), 0, 3);

		if ($lscache->get_user_status() & LiteSpeed_Cache::LSCOOKIE_VARY_LOGGED_IN) {
			remove_action('wp_footer', 'wp_admin_bar_render', 1000);
			add_action('wp_footer', array($this, 'sub_admin_bar_block'), 1000);
		}

		if ($lscache->get_user_status()) {
			add_filter('comment_form_defaults',
				array($this, 'register_comment_form_actions'));
		}
	}

	/**
	 * Hooked to the comment_form_defaults filter.
	 * Stores the default comment form settings.
	 * This method initializes an output buffer and adds two hook functions
	 * to the WP process.
	 * If comment_form_sub_cancel is triggered, the output buffer is flushed
	 * because there is no need to make the comment form ESI.
	 * Else if sub_comment_form_block is triggered, the output buffer is cleared
	 * and an esi block is added. The remaining comment form is also buffered
	 * and cleared.
	 *
	 * @access public
	 * @since 1.1.0
	 * @param array $defaults The default comment form settings.
	 * @return array The default comment form settings.
	 */
	public function register_comment_form_actions($defaults)
	{
		$this->esi_args = $defaults;
		ob_start();
		add_action('comment_form_must_log_in_after',
			array($this, 'comment_form_sub_cancel'));
		add_action('comment_form_comments_closed',
			array($this, 'comment_form_sub_cancel'));
		add_filter('comment_form_submit_button',
			array($this, 'sub_comment_form_block'), 1000, 2);
		return $defaults;
	}

	/**
	 * Build the esi url. This method will build the html comment wrapper
	 * as well as serialize and encode the parameter array.
	 *
	 * The block_id parameter should contain alphanumeric and '-_' only.
	 *
	 * If echo is false *HAS_ESI WILL NOT BE SET TO TRUE*!
	 *
	 * @access private
	 * @since 1.1.0
	 * @param string $block_id The id to use to display the correct esi block.
	 * @param string $wrapper The wrapper for the esi comments.
	 * @param array $params The esi parameters.
	 * @param string $cachectrl The cache control attribute if any.
	 * @param boolean $echo Whether to echo the output or return it.
	 * @return mixed False on error, nothing if echo is true, the output otherwise.
	 */
	public static function sub_esi_block($block_id, $wrapper,
                             $params = array(), $cachectrl = '', $echo = true)
	{
		if ((empty($block_id)) || (!is_array($params))
			|| (preg_match('/[^\w-]/', $block_id))) {
			return false;
		}
		$params[self::PARAM_BLOCK_ID] = $block_id;
		$params = apply_filters('litespeed_cache_sub_esi_params-' . $block_id,
			$params);
		$cachectrl = apply_filters(
			'litespeed_cache_sub_esi_cachectrl-' . $block_id, $cachectrl);
		if ((!is_array($params)) || (!is_string($cachectrl))) {
			if (defined('LSCWP_LOG')) {
				LiteSpeed_Cache::debug_log("Sub esi hooks returned Params: \n"
					. print_r($params, true) . "\ncache control: \n"
					. print_r($cachectrl, true));
			}
			return false;
		}

		$relative_base_url = wp_make_link_relative(home_url());
		$qs = '?' . self::QS_ACTION . '&' . self::QS_PARAMS
			. '=' . urlencode(base64_encode(serialize($params)));
		$url = $relative_base_url . self::URL . $qs;
		$output = '<!-- lscwp ' . $wrapper . ' -->'
			. '<esi:include src="' . $url . '"';
		if (!empty($cachectrl)) {
			$output .= ' cache-control="' . $cachectrl . '"';
		}
		$output .= ' />'
			. '<!-- lscwp ' . $wrapper . ' esi end -->';
		if ($echo == false) {
			return $output;
		}
		echo $output;
		self::get_instance()->has_esi = true;
	}

	/**
	 * Parses the request parameters on an ESI request and selects the correct
	 * esi output based on the parameters.
	 *
	 * @access public
	 * @since 1.1.0
	 */
	public static function load_esi_block()
	{
		if (!isset($_REQUEST[self::QS_PARAMS])) {
			return;
		}
		$req_params = $_REQUEST[self::QS_PARAMS];
		$unencrypted = base64_decode($req_params);
		if ($unencrypted === false) {
			return;
		}
		$unencoded = urldecode($unencrypted);
		$params = unserialize($unencoded);
		if ($params === false) {
			return;
		}

		if (defined('LSCWP_LOG')) {
			$logInfo = 'Got an esi request.';
			if(!empty($params[self::PARAM_NAME])) {
				$logInfo .= ' Name: ' . $params[self::PARAM_NAME] . ', ';
			}
			$logInfo .= ' Block ID: ' . $params[self::PARAM_BLOCK_ID];
			LiteSpeed_Cache::debug_log($logInfo);
		}
		global $_SERVER;
		$orig = $_SERVER['REQUEST_URI'];
		$_SERVER['REQUEST_URI'] = $_SERVER['ESI_REFERER'];
		if (isset($params[self::PARAM_BLOCK_ID])) {
			do_action('litespeed_cache_load_esi_block-'
				. $params[self::PARAM_BLOCK_ID], $params);
		}
		$_SERVER['REQUEST_URI'] = $orig;
	}

// BEGIN helper functions
// The *_sub_* functions are helpers for the sub_* functions.
// The *_load_* functions are helpers for the load_* functions.

	/**
	 * Get the configuration option for the current widget.
	 *
	 * @access public
	 * @since 1.1.0
	 * @param WP_Widget $widget The widget to get the options for.
	 * @return mixed null if not found, an array of the options otherwise.
	 */
	public static function widget_load_get_options($widget)
	{
		add_filter('litespeed_cache_widget_default_options',
			'LiteSpeed_Cache_Esi::widget_default_options', 10, 2);

		if (!is_numeric($widget->number)) {
			return null;
		}

		if ($widget->updated) {
			$settings = get_option($widget->option_name);
		}
		else {
			$settings = $widget->get_settings();
		}

		if (!isset($settings)) {
			return null;
		}

		$instance = $settings[$widget->number];

		if ((!isset($instance))
			|| (!isset($instance[LiteSpeed_Cache_Config::OPTION_NAME]))) {
			return null;
		}

		return $instance[LiteSpeed_Cache_Config::OPTION_NAME];
	}

	/**
	 * Loads the default options for default WordPress widgets.
	 *
	 * @since 1.1.0
	 * @access public
	 * @param array $options The current options selected.
	 * @param WP_Widget $widget The widget to be configured.
	 * @return array The updated options.
	 */
	public static function widget_default_options($options, $widget)
	{
		if (!is_array($options)) {
			return $options;
		}

		$widget_name = get_class($widget);
		switch ($widget_name) {
		case 'WP_Widget_Recent_Posts':
		case 'WP_Widget_Recent_Comments':
			$options[LiteSpeed_Cache_Esi::WIDGET_OPID_ESIENABLE] = true;
			$options[LiteSpeed_Cache_Esi::WIDGET_OPID_TTL] = 86400;
			break;
		default:
			break;
		}
		return $options;
	}

	/**
	 * Hooked to the comment_form_must_log_in_after and
	 * comment_form_comments_closed actions.
	 * @see register_comment_form_actions
	 *
	 * @access public
	 * @since 1.1.0
	 */
	public function comment_form_sub_cancel()
	{
		ob_flush();
	}

	/**
	 * Hooked to the comment_form_after action.
	 * Cleans up the remaining comment form output.
	 *
	 * @access public
	 * @since 1.1.0
	 */
	public function comment_form_sub_clean()
	{
		ob_clean();
	}

	/**
	 * Hooked to the comments_template filter.
	 * Loads a dummy comments template file so that no extra processing is done.
	 * This will only be used if the comments section are to be displayed
	 * via ESI.
	 *
	 * @access public
	 * @since 1.1.0
	 * @return string Dummy template file.
	 */
	public function comments_sub_dummy_template()
	{
		return plugin_dir_path(dirname(__FILE__)) .
			'includes/litespeed-cache-esi-dummy-template.php';
	}

	/**
	 * Hooked to the comments_array filter.
	 * Parses the comments array to determine the types of comments associated
	 * with the post. If there are any unapproved comments, the comments block
	 * should be a private cache. Else use shared.
	 *
	 * @param array $comments The comments to be displayed.
	 * @return array Returns input array
	 */
	public function comments_load_cache_type($comments)
	{
		$cache = LiteSpeed_Cache::plugin();
		if (empty($comments)) {
			$cache->set_cachectrl(LiteSpeed_Cache::CACHECTRL_SHARED);
			return $comments;
		}

		foreach ($comments as $comment) {
			if (!$comment->comment_approved) {
				$cache->set_cachectrl(LiteSpeed_Cache::CACHECTRL_PRIVATE);
				return $comments;
			}
		}
		$cache->set_cachectrl(LiteSpeed_Cache::CACHECTRL_SHARED);
		return $comments;
	}

// END helper functions.

	/**
	 * Hooked to the widget_display_callback filter.
	 * If the admin configured the widget to display via esi, this function
	 * will set up the esi request and cancel the widget display.
	 *
	 * @access public
	 * @since 1.1.0
	 * @param array $instance Parameter used to build the widget.
	 * @param WP_Widget $widget The widget to build.
	 * @param array $args Parameter used to build the widget.
	 * @return mixed Return false if display through esi, instance otherwise.
	 */
	public function sub_widget_block(array $instance, WP_Widget $widget,
		array $args)
	{
		$name = get_class($widget);
		if (!isset($instance[LiteSpeed_Cache_Config::OPTION_NAME])) {
			return $instance;
		}
		$options = $instance[LiteSpeed_Cache_Config::OPTION_NAME];
		if ((!isset($options)) ||
			($options[self::WIDGET_OPID_ESIENABLE]
				== LiteSpeed_Cache_Config::OPID_ENABLED_DISABLE)) {
if (defined('lscache_debug')) {
error_log('Do not esi widget ' . $name . ' because '
	. ((!isset($options)) ? 'options not set' : 'esi disabled for widget'));
}
			return $instance;
		}
		$params = array(
			self::PARAM_NAME => $name,
			self::PARAM_ID => $widget->id,
			self::PARAM_INSTANCE => $instance,
			self::PARAM_ARGS => $args
		);

		self::sub_esi_block('widget', 'widget ' . $name, $params, 'no-vary');
		return false;
	}

	/**
	 * Hooked to the wp_footer action.
	 * Sets up the ESI request for the admin bar.
	 *
	 * @access public
	 * @since 1.1.0
	 * @global type $wp_admin_bar
	 */
	public function sub_admin_bar_block()
	{
		global $wp_admin_bar;

		if ((!is_admin_bar_showing()) || (!is_object($wp_admin_bar))) {
			return;
		}

		self::sub_esi_block('admin-bar', 'adminbar', array(), self::CACHECTRL_PRIV);
	}

	/**
	 * Hooked to the comment_form_submit_button filter.
	 * @see register_comment_form_actions
	 * This method will compare the used comment form args against the default
	 * args. The difference will be passed to the esi request.
	 *
	 * @access public
	 * @since 1.1.0
	 * @global type $post
	 * @param $unused
	 * @param array $args The used comment form args.
	 * @return unused.
	 */
	public function sub_comment_form_block($unused, $args)
	{
		if (empty($args) || empty($this->esi_args)) {
			error_log('comment form args empty?');
			return $unused;
		}
		$cachectrl = '';
		$esi_args = array();

		foreach ($args as $key => $val) {
			if (!isset($this->esi_args[$key])) {
				$esi_args[$key] = $val;
			}
			elseif (is_array($val)) {
				$diff = array_diff_assoc($val, $this->esi_args[$key]);
				if (!empty($diff)) {
					$esi_args[$key] = $diff;
				}
			}
			elseif ($val !== $this->esi_args[$key]) {
				$esi_args[$key] = $val;
			}
		}

		ob_clean();
		global $post;
		$params = array(
			self::PARAM_ID => $post->ID,
			self::PARAM_ARGS => $esi_args,
			);

		if (LiteSpeed_Cache::plugin()->get_user_status()) {
			$cachectrl = self::CACHECTRL_PRIV;
		}
		self::sub_esi_block('comment-form', 'comment form', $params, $cachectrl);
		ob_start();
		add_action('comment_form_after',
			array($this, 'comment_form_sub_clean'));
		return $unused;
	}

	/**
	 * Hooked to the comments_array filter.
	 * If there are pending comments, the whole comments section should be an
	 * ESI block.
	 * Else the comments do not need to be ESI.
	 *
	 * @access public
	 * @since 1.1.0
	 * @global type $post
	 * @param array $comments The current comments to output
	 * @return array The comments to output.
	 */
	public function sub_comments_block($comments)
	{
		global $post;
		$args = array(
			'status' => 'hold',
			'number' => '1',
			'post_id' => $post->ID,
		);

		$on_hold = get_comments($args);

		if (empty($on_hold)) {
			// No comments on hold, comments section can be skipped
			return $comments;
		}
		// Else need to ESI comments.

		$params = array(
			self::PARAM_ID => $post->ID,
			self::PARAM_ARGS => get_query_var( 'cpage' ),
		);
		self::sub_esi_block('comments', 'comments', $params, self::CACHECTRL_PRIV);
		add_filter('comments_template',
			array($this, 'comments_sub_dummy_template'), 1000);
		return array();
	}

	/**
	 * Parses the esi input parameters and generates the widget for esi display.
	 *
	 * @access public
	 * @since 1.1.0
	 * @global $wp_widget_factory
	 * @param array $params Input parameters needed to correctly display widget
	 */
	public function load_widget_block($params)
	{
		global $wp_widget_factory;
		$widget = $wp_widget_factory->widgets[$params[self::PARAM_NAME]];
		$option = self::widget_load_get_options($widget);
		$plugin = LiteSpeed_Cache::plugin();
		// Since we only reach here via esi, safe to assume setting exists.
		$ttl = $option[self::WIDGET_OPID_TTL];
if (defined('lscache_debug')) {
error_log('Esi widget render: name ' . $params[self::PARAM_NAME]
	. ', id ' . $params[self::PARAM_ID] . ', ttl ' . $ttl);
}
		if ($ttl == 0) {
			$plugin->no_cache_for(
				__('Widget time to live set to 0.', 'litespeed-cache'));
			LiteSpeed_Cache_Tags::set_noncacheable();
		}
		else {
			$plugin->set_custom_ttl($ttl);
			LiteSpeed_Cache_Tags::add_cache_tag(
				LiteSpeed_Cache_Tags::TYPE_WIDGET . $params[self::PARAM_ID]);
			LiteSpeed_Cache::plugin()->set_cachectrl(
				LiteSpeed_Cache::CACHECTRL_PUBLIC, true);
		}
		the_widget($params[self::PARAM_NAME],
			$params[self::PARAM_INSTANCE], $params[self::PARAM_ARGS]);
	}

	/**
	 * Generates the admin bar for esi display.
	 *
	 * @access public
	 * @since 1.1.0
	 */
	public function load_admin_bar_block()
	{
		wp_admin_bar_render();
		LiteSpeed_Cache::plugin()->set_cachectrl(
			LiteSpeed_Cache::CACHECTRL_PRIVATE, true);
	}


	/**
	 * Parses the esi input parameters and generates the comment form for
	 * esi display.
	 *
	 * @access public
	 * @since 1.1.0
	 * @param array $params Input parameters needed to correctly display comment form
	 */
	public function load_comment_form_block($params)
	{
		remove_filter('comment_form_defaults',
			array(self::get_instance(), 'register_comment_form_actions'));
		comment_form($params[self::PARAM_ARGS],
			$params[self::PARAM_ID]);
		$plugin = LiteSpeed_Cache::plugin();
		if ($plugin->get_user_status()) {
			LiteSpeed_Cache::plugin()->set_cachectrl(
				LiteSpeed_Cache::CACHECTRL_PRIVATE, true);
		}
		else {
			LiteSpeed_Cache::plugin()->set_cachectrl(
				LiteSpeed_Cache::CACHECTRL_PUBLIC);
		}

	}

	/**
	 * Outputs the ESI comments block.
	 *
	 * @access public
	 * @since 1.1.0
	 * @global type $post
	 * @global type $wp_query
	 * @param array $params The parameters used to help display the comments.
	 */
	public function load_comments_block($params)
	{
		global $post, $wp_query;
		$wp_query->is_singular = true;
		$wp_query->is_single = true;
		if (!empty($params[self::PARAM_ARGS])) {
			$wp_query->set('cpage', $params[self::PARAM_ARGS]);
		}
		$post = get_post($params[self::PARAM_ID]);
		$wp_query->setup_postdata($post);
		add_filter('comments_array', array($this, 'comments_load_cache_type'));
		comments_template();
		LiteSpeed_Cache::plugin()->set_cachectrl(
			LiteSpeed_Cache::CACHECTRL_PRIVATE, true);
	}
}




