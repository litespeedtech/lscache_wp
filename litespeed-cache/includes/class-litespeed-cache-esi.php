<?php



/**
 * What new things do I need to add?
 *
 * - Add third party integration API
 * - Create an is_esi hook. In here, I will add the hooks I need.
 *		Perhaps create a define? LSCACHE_IS_ESI
 * - Add a nonce to ensure validity.
 * - Maybe figure out a way to streamline how everything is added?
 *		Right now, my functions are all kind of all over the place.
 *		Separate load_esi_actions to load_is_esi, load_build_esi?
 *
 */











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
	const PARAM_ID = 'id';
	const PARAM_INSTANCE = 'instance';
	const PARAM_NAME = 'name';
	const PARAM_THIRDPARTY_ID = 'tp_id';
	const PARAM_TYPE = 'type';

	const TYPE_WIDGET = 1;
	const TYPE_ADMINBAR = 2;
	const TYPE_COMMENTFORM = 3;
	const TYPE_COMMENT = 4;

	const TYPE_THIRDPARTY = 16;

	const CACHECTRL_PRIV = 'no-vary,private';

	/**
	 *
	 *
	 * @since    1.1.0
	 */
	private function __construct()
	{

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

	public function set_has_esi()
	{
		$this->has_esi = true;
	}

	/**
	 * Build the esi url. This method will build the html comment wrapper
	 * as well as serialize and encode the parameter array.
	 *
	 * If echo is false *HAS_ESI WILL NOT BE SET TO TRUE*!
	 *
	 * @access private
	 * @since 1.1.0
	 * @param array $params The esi parameters.
	 * @param string $wrapper The wrapper for the esi comments.
	 * @param string $cachectrl The cache control attribute if any.
	 * @param boolean $echo Whether to echo the output or return it.
	 * @return mixed Nothing if echo is true, the output otherwise.
	 */
	public static function build_url($params, $wrapper, $cachectrl = '',
		$echo = true)
	{
		$qs = '';
		if (!empty($params)) {
			$qs = '?' . self::QS_ACTION . '&' . self::QS_PARAMS
				. '=' . urlencode(base64_encode(serialize($params)));
		}
		$url = self::URL . $qs;
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
	 * Hooked to the init action.
	 * Registers the LiteSpeed ESI post type.
	 *
	 * @access public
	 * @since 1.1.0
	 */
	public function register_post_type()
	{
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
			return plugin_dir_path(dirname(__FILE__))
				. 'includes/litespeed-cache-esi.php';
		}
		else {
			$this->load_esi_actions();
		}
		return $template;
	}

	/**
	 * Register all of the hooks related to the esi logic of the plugin.
	 *
	 * @since    1.1.0
	 * @access   private
	 */
	private function load_esi_actions()
	{
		$lscache = LiteSpeed_Cache::plugin();
		add_action('load-widgets.php', array($lscache, 'purge_widget'));
		add_action('wp_update_comment_count',
			array($lscache, 'purge_comment_widget'));
		add_filter('comments_array', array($this, 'esi_comments'));

		if ((defined('DOING_AJAX') && DOING_AJAX)) {
			return;
		}

		add_filter('widget_display_callback', array($this, 'esi_widget'), 0, 3);

		if ($this->user_status & LiteSpeed_Cache::LSCOOKIE_VARY_LOGGED_IN) {
			remove_action('wp_footer', 'wp_admin_bar_render', 1000);
			add_action( 'wp_footer', array($this, 'esi_admin_bar'), 1000 );
		}

		if ($this->user_status) {
			add_filter('comment_form_defaults',
				array($this, 'esi_comment_form_check'));
		}
	}

	/**
	 * Parses the request parameters on an ESI request and selects the correct
	 * esi output based on the parameters.
	 *
	 * @access public
	 * @since 1.1.0
	 */
	public static function esi_get()
	{
		if (!isset($_REQUEST[self::QS_PARAMS])) {
			return;
		}
		$esi = self::get_instance();
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

if (defined('lscache_debug')) {
error_log('Got an esi request. Type: ' . $params[self::PARAM_TYPE]);
}

		switch ($params[self::PARAM_TYPE]) {
			case self::TYPE_WIDGET:
				$esi->esi_widget_get($params);
				break;
			case self::TYPE_ADMINBAR:
				wp_admin_bar_render();
				$esi->set_cachectrl(LiteSpeed_Cache::CACHECTRL_PRIVATE);
				break;
			case self::TYPE_COMMENTFORM:
				remove_filter('comment_form_defaults',
					array($esi, 'esi_comment_form_check'));
				comment_form($params[self::PARAM_ARGS],
					$params[self::PARAM_ID]);
				$esi->set_cachectrl(LiteSpeed_Cache::CACHECTRL_PRIVATE);
				break;
			case self::TYPE_COMMENT:
				$esi->esi_comments_get($params);
				break;
			case self::TYPE_THIRDPARTY:
				global $post, $wp_query;
				$post = get_post($params[self::PARAM_ID]);
				$wp_query->setup_postdata($post);
				wc_get_template(
					$params[self::PARAM_NAME], $params['path'],
					$params[self::PARAM_INSTANCE], $params[self::PARAM_ARGS]);
				break;
			default:
				break;
		}
	}

	/**
	 * Get the configuration option for the current widget.
	 *
	 * @access public
	 * @since 1.1.0
	 * @param WP_Widget $widget The widget to get the options for.
	 * @return mixed null if not found, an array of the options otherwise.
	 */
	public static function esi_widget_get_option($widget)
	{
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

		if (!isset($instance)) {
			return null;
		}

		return $instance[LiteSpeed_Cache_Config::OPTION_NAME];
	}

	/**
	 * Parses the esi input parameters and generates the widget for esi display.
	 *
	 * @access private
	 * @since 1.1.0
	 * @param array $params Input parameters needed to correctly display widget
	 */
	public function esi_widget_get($params)
	{
		global $wp_widget_factory;
		$widget = $wp_widget_factory->widgets[$params[self::PARAM_NAME]];
		$option = self::esi_widget_get_option($widget);
		// Since we only reach here via esi, safe to assume setting exists.
		$ttl = $option[LiteSpeed_Cache_Config::WIDGET_OPID_TTL];
if (defined('lscache_debug')) {
error_log('Esi widget render: name ' . $params[self::PARAM_NAME]
	. ', id ' . $params[self::PARAM_ID] . ', ttl ' . $ttl);
}
		if ($ttl == 0) {
			LiteSpeed_Cache::plugin()->no_cache_for(
				__('Widget time to live set to 0.', 'litespeed-cache'));
			LiteSpeed_Cache_Tags::set_noncacheable();
		}
		else {
			LiteSpeed_Cache::plugin()->set_custom_ttl($ttl);
			LiteSpeed_Cache_Tags::add_cache_tag(
				LiteSpeed_Cache_Tags::TYPE_WIDGET . $params[self::PARAM_ID]);
		}
		the_widget($params[self::PARAM_NAME],
			$params[self::PARAM_INSTANCE], $params[self::PARAM_ARGS]);
	}

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
	public function esi_widget(array $instance, WP_Widget $widget, array $args)
	{
		$name = get_class($widget);
		$options = $instance[LiteSpeed_Cache_Config::OPTION_NAME];
		if ((!isset($options)) ||
			($options[LiteSpeed_Cache_Config::WIDGET_OPID_ESIENABLE]
				== LiteSpeed_Cache_Config::OPID_ENABLED_DISABLE)) {
if (defined('lscache_debug')) {
error_log('Do not esi widget ' . $name . ' because '
	. ((!isset($options)) ? 'options not set' : 'esi disabled for widget'));
}
			return $instance;
		}
		$params = array(
			self::PARAM_TYPE => self::TYPE_WIDGET,
			self::PARAM_NAME => $name,
			self::PARAM_ID => $widget->id,
			self::PARAM_INSTANCE => $instance,
			self::PARAM_ARGS => $args
		);

		self::build_url($params, 'widget ' . $name);
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
	public function esi_admin_bar()
	{
		global $wp_admin_bar;

		if ((!is_admin_bar_showing()) || (!is_object($wp_admin_bar))) {
			return;
		}

		$params = array(self::PARAM_TYPE => self::TYPE_ADMINBAR);

		self::build_url($params, 'adminbar', self::CACHECTRL_PRIV);
	}

	/**
	 * Hooked to the comment_form_defaults filter.
	 * Stores the default comment form settings.
	 * This method initializes an output buffer and adds two hook functions
	 * to the WP process.
	 * If esi_comment_form_cancel is triggered, the output buffer is flushed
	 * because there is no need to make the comment form ESI.
	 * Else if esi_comment_form is triggered, the output buffer is cleared
	 * and an esi block is added. The remaining comment form is also buffered
	 * and cleared.
	 *
	 * @access public
	 * @since 1.1.0
	 * @param array $defaults The default comment form settings.
	 * @return array The default comment form settings.
	 */
	public function esi_comment_form_check($defaults)
	{
		$this->esi_args = $defaults;
		ob_start();
		add_action('comment_form_must_log_in_after',
			array($this, 'esi_comment_form_cancel'));
		add_action('comment_form_comments_closed',
			array($this, 'esi_comment_form_cancel'));
		add_filter('comment_form_submit_button',
			array($this, 'esi_comment_form'), 1000, 2);
		return $defaults;
	}

	/**
	 * Hooked to the comment_form_must_log_in_after and
	 * comment_form_comments_closed actions.
	 * @see esi_comment_form_check
	 *
	 * @access public
	 * @since 1.1.0
	 */
	public function esi_comment_form_cancel()
	{
		ob_flush();
	}

	/**
	 * Hooked to the comment_form_submit_button filter.
	 * @see esi_comment_form_check
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
	public function esi_comment_form($unused, $args)
	{
		if (empty($args) || empty($this->esi_args)) {
			error_log('comment form args empty?');
			return $unused;
		}
		$esi_args = array_diff_assoc($args, $this->esi_args);
		ob_clean();
		global $post;
		$params = array(
			self::PARAM_TYPE => self::TYPE_COMMENTFORM,
			self::PARAM_ID => $post->ID,
			self::PARAM_ARGS => $esi_args,
			);

		self::build_url($params, 'comment form', self::CACHECTRL_PRIV);
		ob_start();
		add_action('comment_form_after',
			array($this, 'esi_comment_form_clean'));
		return $unused;
	}

	/**
	 * Hooked to the comment_form_after action.
	 * Cleans up the remaining comment form output.
	 *
	 * @access public
	 * @since 1.1.0
	 */
	public function esi_comment_form_clean()
	{
		ob_clean();
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
	 * @param type $comments The current comments to output
	 * @return array The comments to output.
	 */
	public function esi_comments($comments)
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
			self::PARAM_TYPE => self::TYPE_COMMENT,
			self::PARAM_ID => $post->ID,
			self::PARAM_ARGS => get_query_var( 'cpage' ),
		);
		self::build_url($params, 'comments', self::CACHECTRL_PRIV);
		add_filter('comments_template',
			array($this, 'esi_comments_dummy_template'), 1000);
		return array();
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
	public function esi_comments_dummy_template()
	{
		return plugin_dir_path(dirname(__FILE__)) .
			'includes/litespeed-cache-esi-dummy-template.php';
	}

	public function esi_comments_cache_type($comments)
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

	/**
	 * Outputs the ESI comments block.
	 *
	 * @access private
	 * @since 1.1.0
	 * @global type $post
	 * @global type $wp_query
	 * @param array $params The parameters used to help display the comments.
	 */
	private function esi_comments_get($params)
	{
		global $post, $wp_query;
		$wp_query->is_singular = true;
		$wp_query->is_single = true;
		if (!empty($params[self::PARAM_ARGS])) {
			$wp_query->set('cpage', $params[self::PARAM_ARGS]);
		}
		$post = get_post($params[self::PARAM_ID]);
		$wp_query->setup_postdata($post);
		add_filter('comments_array', array($this, 'esi_comments_cache_type'));
		comments_template();
	}



}




