<?php
/**
 * The esi class.
 *
 * This is used to define all esi related functions.
 *
 * @since      	1.1.3
 * @since  		1.5 Moved into /inc
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */

if ( ! defined( 'WPINC' ) ) {
	die ;
}

class LiteSpeed_Cache_ESI
{
	private static $_instance ;

	private static $has_esi = false ;
	private $esi_args = null ;
	private $_esi_preserve_list = array() ;

	const QS_ACTION = 'lsesi' ;
	const POSTTYPE = 'lswcp' ;
	const QS_PARAMS = 'esi' ;

	const PARAM_ARGS = 'args' ;
	const PARAM_BLOCK_ID = 'block_id' ;
	const PARAM_ID = 'id' ;
	const PARAM_INSTANCE = 'instance' ;
	const PARAM_NAME = 'name' ;

	const WIDGET_OPID_ESIENABLE = 'widget_esi_enable' ;
	const WIDGET_OPID_TTL = 'widget_ttl' ;

	/**
	 * Constructor of ESI
	 *
	 * @since    1.2.0
	 * @access private
	 */
	private function __construct()
	{
		add_action( 'template_include', 'LiteSpeed_Cache_ESI::esi_template', 100 ) ;
		add_action( 'load-widgets.php', 'LiteSpeed_Cache_Purge::purge_widget' ) ;
		add_action( 'wp_update_comment_count', 'LiteSpeed_Cache_Purge::purge_comment_widget' ) ;

		// This defination is along with LiteSpeed_Cache_API::nonce() func
		! defined( 'LSCWP_NONCE' ) && define( 'LSCWP_NONCE', true ) ;

		/**
		 * Recover REQUEST_URI
		 * @since  1.8.1
		 */
		if ( ! empty( $_GET[ self::QS_ACTION ] ) && $_GET[ self::QS_ACTION ] == self::POSTTYPE ) {
			define( 'LSCACHE_IS_ESI', true ) ;

			if ( ! empty( $_SERVER[ 'ESI_REFERER' ] ) ) {
				$_SERVER[ 'REQUEST_URI' ] = $_SERVER[ 'ESI_REFERER' ] ;
			}
		}

		/**
		 * Shortcode ESI
		 *
		 * To use it, just change the origianl shortcode as below:
		 * 		old: [someshortcode aa='bb']
		 * 		new: [esi someshortcode aa='bb' cache='private,no-vary' ttl='600']
		 *
		 * 	1. `cache` attribute is optional, default to 'public,no-vary'.
		 * 	2. `ttl` attribute is optional, default is your public TTL setting.
		 *
		 * @since  2.8
		 * @since  2.8.1 Check is_admin for Elementor compatibility #726013
		 */
		if ( ! is_admin() ) {
			add_shortcode( 'esi', array( $this, 'shortcode' ) ) ;
		}
	}

	/**
	 * Shortcode ESI
	 *
	 * @since 2.8
	 * @access public
	 */
	public function shortcode( $atts )
	{
		if ( empty( $atts[ 0 ] ) ) {
			LiteSpeed_Cache_Log::debug( '[ESI] ===shortcode wrong format', $atts ) ;
			return 'Wrong shortcode esi format' ;
		}

		$cache = 'public,no-vary' ;
		if ( ! empty( $atts[ 'cache' ] ) ) {
			$cache = $atts[ 'cache' ] ;
			unset( $atts[ 'cache' ] ) ;
		}

		// Show ESI link
		return self::sub_esi_block( 'esi', 'esi-shortcode', $atts, $cache ) ;
	}

	/**
	 * Check if the requested page has esi elements. If so, return esi on
	 * header.
	 *
	 * @since 1.1.3
	 * @access public
	 * @return string Esi On header if request has esi, empty string otherwise.
	 */
	public static function has_esi()
	{
		return self::$has_esi ;
	}

	/**
	 * Sets that the requested page has esi elements.
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function set_has_esi()
	{
		self::$has_esi = true ;
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
	public static function esi_template($template)
	{
		// Check if is an ESI request
		if ( defined( 'LSCACHE_IS_ESI' ) ) {
			self::get_instance()->register_esi_actions() ;

			return LSCWP_DIR . 'tpl/esi.tpl.php' ;
		}
		self::get_instance()->register_not_esi_actions() ;
		return $template ;
	}

	/**
	 * Register all of the hooks related to the esi logic of the plugin.
	 * Specifically when the page IS an esi page.
	 *
	 * @since    1.1.3
	 * @access   public
	 */
	public function register_esi_actions()
	{
		add_action('litespeed_cache_load_esi_block-widget', array($this, 'load_widget_block')) ;
		add_action('litespeed_cache_load_esi_block-admin-bar', array($this, 'load_admin_bar_block')) ;
		add_action('litespeed_cache_load_esi_block-comment-form', array($this, 'load_comment_form_block')) ;

		add_action('litespeed_cache_load_esi_block-lscwp_nonce_esi', array( $this, 'load_nonce_block' ) ) ;
		add_action('litespeed_cache_load_esi_block-esi', array( $this, 'load_esi_shortcode' ) ) ;
	}

	/**
	 * Register all of the hooks related to the esi logic of the plugin.
	 * Specifically when the page is NOT an esi page.
	 *
	 * @since    1.1.3
	 * @access   public
	 */
	public function register_not_esi_actions()
	{
		do_action('litespeed_cache_is_not_esi_template') ;

		if ( ! LiteSpeed_Cache_Control::is_cacheable() ) {
			return ;
		}

		if ( LiteSpeed_Cache_Router::is_ajax() ) {
			return ;
		}

		add_filter('widget_display_callback', array($this, 'sub_widget_block'), 0, 3) ;

		// Add admin_bar esi
		if ( LiteSpeed_Cache_Router::is_logged_in() ) {
			remove_action('wp_footer', 'wp_admin_bar_render', 1000) ;
			add_action('wp_footer', array($this, 'sub_admin_bar_block'), 1000) ;
		}

		// Add comment forum esi for logged-in user or commenter
		if ( ! LiteSpeed_Cache_Router::is_ajax() && LiteSpeed_Cache_Vary::has_vary() ) {
			add_filter( 'comment_form_defaults', array( $this, 'register_comment_form_actions' ) ) ;
		}

	}

	/**
	 * Build the esi url. This method will build the html comment wrapper as well as serialize and encode the parameter array.
	 *
	 * The block_id parameter should contain alphanumeric and '-_' only.
	 *
	 * @since 1.1.3
	 * @access private
	 * @param string $block_id The id to use to display the correct esi block.
	 * @param string $wrapper The wrapper for the esi comments.
	 * @param array $params The esi parameters.
	 * @param string $control The cache control attribute if any.
	 * @param bool $silence If generate wrapper comment or not
	 * @param bool $preserved 	If this ESI block is used in any filter, need to temporarily convert it to a string to avoid the HTML tag being removed/filtered.
	 * @param bool $svar  		If store the value in memory or not, in memory wil be faster
	 * @param bool $inline_val 	If show the current value for current request( this can avoid multiple esi requests in first time cache generating process ) -- Not used yet
	 * @return bool|string    	False on error, the output otherwise.
	 */
	public static function sub_esi_block( $block_id, $wrapper, $params = array(), $control = 'private,no-vary', $silence = false, $preserved = false, $svar = false, $inline_val = false )
	{
		if ( empty($block_id) || ! is_array($params) || preg_match('/[^\w-]/', $block_id) ) {
			return false ;
		}

		$params[ self::PARAM_BLOCK_ID ] = $block_id ;
		if ( $silence ) {
			// Don't add comment to esi block ( orignal for nonce used in tag property data-nonce='esi_block' )
			$params[ '_ls_silence' ] = true ;
		}

		$params = apply_filters('litespeed_cache_sub_esi_params-' . $block_id, $params) ;
		$control = apply_filters('litespeed_cache_sub_esi_control-' . $block_id, $control) ;
		if ( !is_array($params) || !is_string($control) ) {
			defined( 'LSCWP_LOG' ) && LiteSpeed_Cache_Log::debug( "Sub esi hooks returned Params: \n" . var_export($params, true) . "\ncache control: \n" . var_export($control, true) ) ;

			return false ;
		}

		$url = trailingslashit( wp_make_link_relative( home_url() ) ) . '?' . self::QS_ACTION . '=' . self::POSTTYPE ;
		if ( ! empty( $control ) ) {
			$url .= '&_control=' . $control ;
		}
		$url .= '&' . self::QS_PARAMS . '=' . urlencode(base64_encode(serialize($params))) ;

		$output = "<esi:include src='$url'" ;
		if ( ! empty( $control ) ) {
			$output .= " cache-control='$control'" ;
		}
		if ( $svar ) {
			$output .= " as-var='1'" ;
		}
		$output .= " />" ;

		if ( ! $silence ) {
			$output = "<!-- lscwp $wrapper -->$output<!-- lscwp $wrapper esi end -->" ;
		}

		LiteSpeed_Cache_Log::debug( "ESI: \t\t[block ID] $block_id \t\t\t[wrapper] $wrapper \t\t\t[Control] $control" ) ;
		LiteSpeed_Cache_Log::debug2( $output ) ;

		self::set_has_esi() ;

		// Convert to string to avoid html chars filter when using
		// Will reverse the buffer when output in self::finalize()
		if ( $preserved ) {
			$hash = md5( $output ) ;
			self::get_instance()->_esi_preserve_list[ $hash ] = $output ;
			LiteSpeed_Cache_Log::debug( "[ESI] Preserved to $hash" ) ;

			return $hash ;
		}

		return $output ;
	}

	/**
	 * Parses the request parameters on an ESI request
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function parse_esi_param()
	{
		if ( ! isset($_REQUEST[self::QS_PARAMS]) ) {
			return false ;
		}
		$req_params = $_REQUEST[self::QS_PARAMS] ;
		$unencrypted = base64_decode($req_params) ;
		if ( $unencrypted === false ) {
			return false ;
		}
		$unencoded = urldecode($unencrypted) ;
		$params = unserialize($unencoded) ;
		if ( $params === false || ! isset($params[self::PARAM_BLOCK_ID]) ) {
			return false ;
		}

		return $params ;
	}

	/**
	 * Select the correct esi output based on the parameters in an ESI request.
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public static function load_esi_block()
	{
		$params = self::parse_esi_param() ;
		if ( $params === false ) {
			return ;
		}
		$esi_id = $params[ self::PARAM_BLOCK_ID ] ;
		if ( defined( 'LSCWP_LOG' ) ) {
			$logInfo = '------- ESI ------- ' ;
			if( ! empty( $params[ self::PARAM_NAME ] ) ) {
				$logInfo .= ' Name: ' . $params[ self::PARAM_NAME ] . ' ----- ' ;
			}
			$logInfo .= $esi_id . ' -------' ;
			LiteSpeed_Cache_Log::debug( $logInfo ) ;
		}

		if ( ! empty( $params[ '_ls_silence' ] ) ) {
			define( 'LSCACHE_ESI_SILENCE', true ) ;
		}

		LiteSpeed_Cache_Tag::add( rtrim( LiteSpeed_Cache_Tag::TYPE_ESI, '.' ) ) ;
		LiteSpeed_Cache_Tag::add( LiteSpeed_Cache_Tag::TYPE_ESI . $esi_id ) ;

		// LiteSpeed_Cache_Log::debug(var_export($params, true ));

		/**
		 * Handle default cache control 'private,no-vary' for sub_esi_block() 	@ticket #923505
		 *
		 * @since  2.2.3
		 */
		if ( ! empty( $_GET[ '_control' ] ) ) {
			$control = explode( ',', $_GET[ '_control' ] ) ;
			if ( in_array( 'private', $control ) ) {
				LiteSpeed_Cache_Control::set_private() ;
			}

			if ( in_array( 'no-vary', $control ) ) {
				LiteSpeed_Cache_Control::set_no_vary() ;
			}
		}

		do_action('litespeed_cache_load_esi_block-' . $esi_id, $params) ;
	}

// BEGIN helper functions
// The *_sub_* functions are helpers for the sub_* functions.
// The *_load_* functions are helpers for the load_* functions.

	/**
	 * Get the configuration option for the current widget.
	 *
	 * @since 1.1.3
	 * @access public
	 * @param WP_Widget $widget The widget to get the options for.
	 * @return mixed null if not found, an array of the options otherwise.
	 */
	public static function widget_load_get_options($widget)
	{
		add_filter('litespeed_cache_widget_default_options', 'LiteSpeed_Cache_ESI::widget_default_options', 10, 2) ;

		if ( ! is_numeric($widget->number) ) {
			return null ;
		}

		if ( $widget->updated ) {
			$settings = get_option($widget->option_name) ;
		}
		else {
			$settings = $widget->get_settings() ;
		}

		if ( ! isset($settings) ) {
			return null ;
		}

		$instance = $settings[$widget->number] ;

		if ( ! isset($instance) || ! isset($instance[LiteSpeed_Cache_Config::OPTION_NAME]) ) {
			return null;
		}

		return $instance[LiteSpeed_Cache_Config::OPTION_NAME] ;
	}

	/**
	 * Loads the default options for default WordPress widgets.
	 *
	 * @since 1.1.3
	 * @access public
	 * @param array $options The current options selected.
	 * @param WP_Widget $widget The widget to be configured.
	 * @return array The updated options.
	 */
	public static function widget_default_options($options, $widget)
	{
		if ( ! is_array($options) ) {
			return $options ;
		}

		$widget_name = get_class($widget) ;
		switch ($widget_name) {
			case 'WP_Widget_Recent_Posts' :
			case 'WP_Widget_Recent_Comments' :
				$options[self::WIDGET_OPID_ESIENABLE] = LiteSpeed_Cache_Config::VAL_OFF ;
				$options[self::WIDGET_OPID_TTL] = 86400 ;
				break ;
			default :
				break ;
		}
		return $options ;
	}

// END helper functions.

	/**
	 * Hooked to the widget_display_callback filter.
	 * If the admin configured the widget to display via esi, this function
	 * will set up the esi request and cancel the widget display.
	 *
	 * @since 1.1.3
	 * @access public
	 * @param array $instance Parameter used to build the widget.
	 * @param WP_Widget $widget The widget to build.
	 * @param array $args Parameter used to build the widget.
	 * @return mixed Return false if display through esi, instance otherwise.
	 */
	public function sub_widget_block( array $instance, WP_Widget $widget, array $args )
	{
		$name = get_class( $widget ) ;
		if ( ! isset( $instance[ LiteSpeed_Cache_Config::OPTION_NAME ] ) ) {
			return $instance ;
		}
		$options = $instance[ LiteSpeed_Cache_Config::OPTION_NAME ] ;
		if ( ! isset( $options ) || ! $options[ self::WIDGET_OPID_ESIENABLE ] ) {
			defined( 'LSCWP_LOG' ) && LiteSpeed_Cache_Log::debug( 'ESI 0 ' . $name . ': '. ( ! isset( $options ) ? 'not set' : 'set off' ) ) ;

			return $instance ;
		}

		$esi_private = $options[ self::WIDGET_OPID_ESIENABLE ] === LiteSpeed_Cache_Config::VAL_ON2 ? 'private,' : '' ;

		$params = array(
			self::PARAM_NAME => $name,
			self::PARAM_ID => $widget->id,
			self::PARAM_INSTANCE => $instance,
			self::PARAM_ARGS => $args
		) ;

		echo self::sub_esi_block( 'widget', 'widget ' . $name, $params, $esi_private . 'no-vary' ) ;
		return false ;
	}

	/**
	 * Hooked to the wp_footer action.
	 * Sets up the ESI request for the admin bar.
	 *
	 * @access public
	 * @since 1.1.3
	 * @global type $wp_admin_bar
	 */
	public function sub_admin_bar_block()
	{
		global $wp_admin_bar ;

		if ( ! is_admin_bar_showing() || ! is_object($wp_admin_bar) ) {
			return ;
		}

		// To make each admin bar ESI request different for `Edit` button different link
		$params = array(
			'ref' => $_SERVER[ 'REQUEST_URI' ],
		) ;

		echo self::sub_esi_block( 'admin-bar', 'adminbar', $params ) ;
	}

	/**
	 * Parses the esi input parameters and generates the widget for esi display.
	 *
	 * @access public
	 * @since 1.1.3
	 * @global $wp_widget_factory
	 * @param array $params Input parameters needed to correctly display widget
	 */
	public function load_widget_block( $params )
	{
		global $wp_widget_factory ;
		$widget = $wp_widget_factory->widgets[ $params[ self::PARAM_NAME ] ] ;
		$option = self::widget_load_get_options( $widget ) ;
		// Since we only reach here via esi, safe to assume setting exists.
		$ttl = $option[ self::WIDGET_OPID_TTL ] ;
		defined( 'LSCWP_LOG' ) && LiteSpeed_Cache_Log::debug( 'ESI widget render: name ' . $params[ self::PARAM_NAME ] . ', id ' . $params[ self::PARAM_ID ] . ', ttl ' . $ttl ) ;
		if ( $ttl == 0 ) {
			LiteSpeed_Cache_Control::set_nocache( 'ESI Widget time to live set to 0' ) ;
		}
		else {
			LiteSpeed_Cache_Control::set_custom_ttl( $ttl ) ;

			if ( $option[ self::WIDGET_OPID_ESIENABLE ] === LiteSpeed_Cache_Config::VAL_ON2 ) {
				LiteSpeed_Cache_Control::set_private() ;
			}
			LiteSpeed_Cache_Control::set_no_vary() ;
			LiteSpeed_Cache_Tag::add( LiteSpeed_Cache_Tag::TYPE_WIDGET . $params[ self::PARAM_ID ] ) ;
		}
		the_widget( $params[ self::PARAM_NAME ], $params[ self::PARAM_INSTANCE ], $params[ self::PARAM_ARGS ] ) ;
	}

	/**
	 * Generates the admin bar for esi display.
	 *
	 * @access public
	 * @since 1.1.3
	 */
	public function load_admin_bar_block()
	{
		wp_admin_bar_render() ;
		if ( ! LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_ESI_CACHE_ADMBAR ) ) {
			LiteSpeed_Cache_Control::set_nocache( 'build-in set to not cacheable' ) ;
		}
		else {
			LiteSpeed_Cache_Control::set_private() ;
			LiteSpeed_Cache_Control::set_no_vary() ;
		}

		defined( 'LSCWP_LOG' ) && LiteSpeed_Cache_Log::debug( 'ESI: adminbar ref: ' . $_SERVER[ 'REQUEST_URI' ] ) ;
	}


	/**
	 * Parses the esi input parameters and generates the comment form for esi display.
	 *
	 * @access public
	 * @since 1.1.3
	 * @param array $params Input parameters needed to correctly display comment form
	 */
	public function load_comment_form_block($params)
	{
		ob_start() ;
		comment_form( $params[ self::PARAM_ARGS ], $params[ self::PARAM_ID ] ) ;
		$output = ob_get_contents() ;
		ob_end_clean() ;

		if ( $params[ 'is_json' ] ) {
			$output = json_encode( $output ) ;
			$output = ltrim( $output, '"' ) ;
			$output = rtrim( $output, '"' ) ;
		}

		echo $output ;

		if ( ! LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_ESI_CACHE_COMMFORM ) ) {
			LiteSpeed_Cache_Control::set_nocache( 'build-in set to not cacheable' ) ;
		}
		else {
			// by default comment form is public
			if ( LiteSpeed_Cache_Vary::has_vary() ) {
				LiteSpeed_Cache_Control::set_private() ;
				LiteSpeed_Cache_Control::set_no_vary() ;
			}
		}
	}

	/**
	 * Generate nonce for certain action
	 *
	 * @access public
	 * @since 2.6
	 */
	public function load_nonce_block( $params )
	{
		$action = $params[ 'action' ] ;

		LiteSpeed_Cache_Log::debug( '[ESI] load_nonce_block [action] ' . $action ) ;

		// set nonce TTL to half day
		LiteSpeed_Cache_Control::set_custom_ttl( 43200 ) ;

		if ( LiteSpeed_Cache_Router::is_logged_in() ) {
			LiteSpeed_Cache_Control::set_private() ;
		}

		echo wp_create_nonce( $action ) ;
	}

	/**
	 * Show original shortcode
	 *
	 * @access public
	 * @since 2.8
	 */
	public function load_esi_shortcode( $params )
	{
		unset( $params[ self::PARAM_BLOCK_ID ] ) ;

		if ( isset( $params[ 'ttl' ] ) ) {
			if ( ! $params[ 'ttl' ] ) {
				LiteSpeed_Cache_Control::set_nocache( 'ESI shortcode att ttl=0' ) ;
			}
			else {
				LiteSpeed_Cache_Control::set_custom_ttl( $params[ 'ttl' ] ) ;
			}
			unset( $params[ 'ttl' ] ) ;
		}

		// Replace to original shortcode
		$shortcode = $params[ 0 ] ;
		$atts_ori = array() ;
		foreach ( $params as $k => $v ) {
			if ( $k === 0 ) {
				continue ;
			}

			$atts_ori[] = is_string( $k ) ? "$k='" . addslashes( $v ) . "'" : $v ;
		}

		LiteSpeed_Cache_Tag::add( LiteSpeed_Cache_Tag::TYPE_ESI . "esi.$shortcode" ) ;

		// Output original shortcode final content
		echo do_shortcode( "[$shortcode " . implode( ' ', $atts_ori ) . " ]" ) ;
	}

	/**
	 * Hooked to the comment_form_defaults filter.
	 * Stores the default comment form settings.
	 * If sub_comment_form_block is triggered, the output buffer is cleared and an esi block is added. The remaining comment form is also buffered and cleared.
	 * Else there is no need to make the comment form ESI.
	 *
	 * @since 1.1.3
	 * @access public
	 * @param array $defaults The default comment form settings.
	 * @return array The default comment form settings.
	 */
	public function register_comment_form_actions( $defaults )
	{
		$this->esi_args = $defaults ;
		echo LiteSpeed_Cache_GUI::clean_wrapper_begin() ;
		add_filter( 'comment_form_submit_button', array( $this, 'sub_comment_form_block' ), 1000, 2 ) ;// Needs to get param from this hook and generate esi block
		return $defaults ;
	}

	/**
	 * Hooked to the comment_form_submit_button filter.
	 *
	 * This method will compare the used comment form args against the default args. The difference will be passed to the esi request.
	 *
	 * @access public
	 * @since 1.1.3
	 * @global type $post
	 * @param $unused
	 * @param array $args The used comment form args.
	 * @return unused.
	 */
	public function sub_comment_form_block( $unused, $args )
	{
		if ( empty( $args ) || empty( $this->esi_args ) ) {
			LiteSpeed_Cache_Log::debug( 'comment form args empty?' ) ;
			return $unused ;
		}
		$esi_args = array() ;

		// compare current args with default ones
		foreach ( $args as $k => $v ) {
			if ( ! isset( $this->esi_args[ $k ] ) ) {
				$esi_args[ $k ] = $v ;
			}
			elseif ( is_array( $v ) ) {
				$diff = array_diff_assoc( $v, $this->esi_args[ $k ] ) ;
				if ( ! empty( $diff ) ) {
					$esi_args[ $k ] = $diff ;
				}
			}
			elseif ( $v !== $this->esi_args[ $k ] ) {
				$esi_args[ $k ] = $v ;
			}
		}

		echo LiteSpeed_Cache_GUI::clean_wrapper_end() ;
		global $post ;
		$params = array(
			self::PARAM_ID => $post->ID,
			self::PARAM_ARGS => $esi_args,
		) ;

		echo self::sub_esi_block( 'comment-form', 'comment form', $params ) ;
		echo LiteSpeed_Cache_GUI::clean_wrapper_begin() ;
		add_action( 'comment_form_after', array( $this, 'comment_form_sub_clean' ) ) ;
		return $unused ;
	}

	/**
	 * Hooked to the comment_form_after action.
	 * Cleans up the remaining comment form output.
	 *
	 * @since 1.1.3
	 * @access public
	 */
	public function comment_form_sub_clean()
	{
		echo LiteSpeed_Cache_GUI::clean_wrapper_end() ;
	}

	/**
	 * Replace preseved blocks
	 *
	 * @since  2.6
	 * @access public
	 */
	public static function finalize( $buffer )
	{
		$instance = self::get_instance() ;

		// Bypass if no preserved list to be replaced
		if ( ! $instance->_esi_preserve_list ) {
			return $buffer ;
		}

		$keys = array_keys( $instance->_esi_preserve_list ) ;

		LiteSpeed_Cache_Log::debug( '[ESI] replacing preserved blocks', $keys ) ;

		$buffer = str_replace( $keys , $instance->_esi_preserve_list, $buffer ) ;

		return $buffer ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.1.3
	 * @access public
	 * @return Current class instance.
	 */
	public static function get_instance()
	{
		if ( ! isset(self::$_instance) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}
}
