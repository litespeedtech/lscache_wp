<?php
/**
 * The plugin purge class for X-LiteSpeed-Purge
 *
 * @since      	1.1.3
 * @since  		1.5 Moved into /inc
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Purge
{
	private static $_instance ;
	protected static $_pub_purge = array() ;
	protected static $_priv_purge = array() ;
	protected static $_purge_related = false ;
	protected static $_purge_single = false ;

	const X_HEADER = 'X-LiteSpeed-Purge' ;
	const PURGE_QUEUE = 'litespeed-cache-purge-queue' ;

	const TYPE_OBJECT_PURGE_ALL = 'object_purge_all' ;
	const TYPE_OPCACHE_PURGE_ALL = 'opcache_purge_all' ;

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  1.8
	 * @access public
	 */
	public static function handler()
	{
		$instance = self::get_instance() ;

		$type = LiteSpeed_Cache_Router::verify_type() ;

		switch ( $type ) {
			case self::TYPE_OBJECT_PURGE_ALL :
				$instance->_object_purge_all() ;
				break ;

			case self::TYPE_OPCACHE_PURGE_ALL :
				$instance->_opcache_purge_all() ;
				break ;

			default:
				break ;
		}

		LiteSpeed_Cache_Admin::redirect() ;
	}

	/**
	 * Purge opcode cache
	 *
	 * @since  1.8.2
	 * @access private
	 */
	private function _opcache_purge_all()
	{
		if ( ! LiteSpeed_Cache_Router::opcache_enabled() ) {
			LiteSpeed_Cache_Log::debug( 'Purge: Failed to reset opcode cache due to opcache not enabled' ) ;

			$msg = __( 'Opcode cache is not enabled.', 'litespeed-cache' ) ;
			LiteSpeed_Cache_Admin_Display::error( $msg ) ;

			return false ;
		}

		// Purge opcode cache
		opcache_reset() ;
		LiteSpeed_Cache_Log::debug( 'Purge: Reset opcode cache' ) ;

		$msg = __( 'Reset the entire opcode cache successfully.', 'litespeed-cache' ) ;
		LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;

		return true ;
	}

	/**
	 * Purge object cache
	 *
	 * @since  1.8
	 * @access private
	 */
	private function _object_purge_all()
	{
		if ( ! defined( 'LSCWP_OBJECT_CACHE' ) ) {
			LiteSpeed_Cache_Log::debug( 'Purge: Failed to flush object cache due to object cache not enabled' ) ;

			$msg = __( 'Object cache is not enabled.', 'litespeed-cache' ) ;
			LiteSpeed_Cache_Admin_Display::error( $msg ) ;

			return false ;
		}
		LiteSpeed_Cache_Object::get_instance()->flush() ;
		LiteSpeed_Cache_Log::debug( 'Purge: Flushed object cache' ) ;

		$msg = __( 'Purge all object caches successfully.', 'litespeed-cache' ) ;
		LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;

		return true ;
	}

	/**
	 * Adds new public purge tags to the array of purge tags for the request.
	 *
	 * @since 1.1.3
	 * @access public
	 * @param mixed $tags Tags to add to the list.
	 */
	public static function add( $tags )
	{
		if ( ! is_array( $tags ) ) {
			$tags = array( $tags ) ;
		}
		if ( ! array_diff( $tags, self::$_pub_purge ) ) {
			return ;
		}

		self::$_pub_purge = array_merge( self::$_pub_purge, $tags ) ;
		LiteSpeed_Cache_Log::debug( 'Purge: added ' . implode( ',', $tags ), 3 ) ;

		// Send purge header immediately
		$curr_built = self::_build() ;
		if ( defined( 'LITESPEED_DID_send_headers' ) ) {
			// Can't send, already has output, need to save and wait for next run
			update_option( self::PURGE_QUEUE, $curr_built ) ;
			LiteSpeed_Cache_Log::debug( 'Output existed, Purge queue stored: ' . $curr_built ) ;
		}
		else {
			@header( $curr_built ) ;
			LiteSpeed_Cache_Log::debug( $curr_built ) ;
		}
	}

	/**
	 * Adds new private purge tags to the array of purge tags for the request.
	 *
	 * @since 1.1.3
	 * @access public
	 * @param mixed $tags Tags to add to the list.
	 */
	public static function add_private( $tags )
	{
		if ( ! is_array( $tags ) ) {
			$tags = array( $tags ) ;
		}
		if ( ! array_diff( $tags, self::$_priv_purge ) ) {
			return ;
		}

		LiteSpeed_Cache_Log::debug( 'Purge: added [private] ' . implode( ',', $tags ), 3 ) ;

		self::$_priv_purge = array_merge( self::$_priv_purge, $tags ) ;

		// Send purge header immediately
		@header( self::_build() ) ;
	}

	/**
	 * Activate `purge related tags` for Admin QS.
	 *
	 * @since    1.1.3
	 * @access   public
	 */
	public static function set_purge_related()
	{
		self::$_purge_related = true ;
	}

	/**
	 * Activate `purge single url tag` for Admin QS.
	 *
	 * @since    1.1.3
	 * @access   public
	 */
	public static function set_purge_single()
	{
		self::$_purge_single = true ;
	}

	/**
	 * Check qs purge status
	 *
	 * @since    1.1.3
	 * @access   public
	 */
	public static function get_qs_purge()
	{
		return self::$_purge_single || self::$_purge_related ;
	}

	/**
	 * Alerts LiteSpeed Web Server to purge all pages.
	 *
	 * For multisite installs, if this is called by a site admin (not network admin),
	 * it will only purge all posts associated with that site.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public static function purge_all()
	{
		self::add( '*' ) ;

		// check if need to reset crawler
		if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::CRWL_CRON_ACTIVE ) ) {
			LiteSpeed_Cache_Crawler::get_instance()->reset_pos() ;
		}
	}

	/**
	 * Alerts LiteSpeed Web Server to purge the front page.
	 *
	 * @since    1.0.3
	 * @access   public
	 */
	public static function purge_front()
	{
		self::add( LiteSpeed_Cache_Tag::TYPE_FRONTPAGE ) ;
		if ( LITESPEED_SERVER_TYPE !== 'LITESPEED_SERVER_OLS' ) {
			self::add_private( LiteSpeed_Cache_Tag::TYPE_FRONTPAGE ) ;
		}
	}

	/**
	 * Alerts LiteSpeed Web Server to purge pages.
	 *
	 * @since    1.0.15
	 * @access   public
	 */
	public static function purge_pages()
	{
		self::add( LiteSpeed_Cache_Tag::TYPE_PAGES ) ;
	}

	/**
	 * Alerts LiteSpeed Web Server to purge pages.
	 *
	 * @since    1.2.2
	 * @access   public
	 */
	public static function purge_cssjs()
	{
		self::add( LiteSpeed_Cache_Tag::TYPE_MIN ) ;

		// For non-ls users
		LiteSpeed_Cache_Optimize::get_instance()->rm_cache_folder() ;
	}

	/**
	 * Alerts LiteSpeed Web Server to purge error pages.
	 *
	 * @since    1.0.14
	 * @access   public
	 */
	public static function purge_errors()
	{
		self::add( LiteSpeed_Cache_Tag::TYPE_ERROR ) ;

		$type = LiteSpeed_Cache_Router::verify_type() ;
		if ( ! $type || ! in_array( $type, array( '403', '404', '500' ) ) ) {
			return ;
		}

		self::add( LiteSpeed_Cache_Tag::TYPE_ERROR . $type ) ;
	}

	/**
	 * Callback to add purge tags if admin selects to purge selected category pages.
	 *
	 * @since 1.0.7
	 * @access public
	 * @param string $value The category slug.
	 * @param string $key Unused.
	 */
	public function purgeby_cat_cb( $value, $key )
	{
		$val = trim( $value ) ;
		if ( empty( $val ) ) {
			return ;
		}
		if ( preg_match( '/^[a-zA-Z0-9-]+$/', $val ) == 0 ) {
			LiteSpeed_Cache_Admin_Display::add_error( LiteSpeed_Cache_Admin_Error::E_PURGEBY_CAT_INV ) ;
			return ;
		}
		$cat = get_category_by_slug( $val ) ;
		if ( $cat == false ) {
			LiteSpeed_Cache_Admin_Display::add_error( LiteSpeed_Cache_Admin_Error::E_PURGEBY_CAT_DNE, $val ) ;
			return ;
		}

		LiteSpeed_Cache_Admin_Display::add_notice( LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, sprintf( __( 'Purge category %s', 'litespeed-cache' ), $val ) ) ;

		self::add( LiteSpeed_Cache_Tag::TYPE_ARCHIVE_TERM . $cat->term_id ) ;
	}

	/**
	 * Callback to add purge tags if admin selects to purge selected post IDs.
	 *
	 * @since 1.0.7
	 * @access public
	 * @param string $value The post ID.
	 * @param string $key Unused.
	 */
	public function purgeby_pid_cb( $value, $key )
	{
		$val = trim( $value ) ;
		if ( empty( $val ) ) {
			return ;
		}
		if ( ! is_numeric( $val ) ) {
			LiteSpeed_Cache_Admin_Display::add_error( LiteSpeed_Cache_Admin_Error::E_PURGEBY_PID_NUM, $val ) ;
			return ;
		}
		elseif ( get_post_status( $val ) !== 'publish' ) {
			LiteSpeed_Cache_Admin_Display::add_error( LiteSpeed_Cache_Admin_Error::E_PURGEBY_PID_DNE, $val ) ;
			return ;
		}
		LiteSpeed_Cache_Admin_Display::add_notice( LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, sprintf( __( 'Purge Post ID %s', 'litespeed-cache' ), $val ) ) ;

		self::add( LiteSpeed_Cache_Tag::TYPE_POST . $val ) ;
	}

	/**
	 * Callback to add purge tags if admin selects to purge selected tag pages.
	 *
	 * @since 1.0.7
	 * @access public
	 * @param string $value The tag slug.
	 * @param string $key Unused.
	 */
	public function purgeby_tag_cb( $value, $key )
	{
		$val = trim( $value ) ;
		if ( empty( $val ) ) {
			return ;
		}
		if ( preg_match( '/^[a-zA-Z0-9-]+$/', $val ) == 0 ) {
			LiteSpeed_Cache_Admin_Display::add_error( LiteSpeed_Cache_Admin_Error::E_PURGEBY_TAG_INV ) ;
			return ;
		}
		$term = get_term_by( 'slug', $val, 'post_tag' ) ;
		if ( $term == 0 ) {
			LiteSpeed_Cache_Admin_Display::add_error( LiteSpeed_Cache_Admin_Error::E_PURGEBY_TAG_DNE, $val ) ;
			return ;
		}

		LiteSpeed_Cache_Admin_Display::add_notice( LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, sprintf( __( 'Purge tag %s', 'litespeed-cache' ), $val ) ) ;

		self::add( LiteSpeed_Cache_Tag::TYPE_ARCHIVE_TERM . $term->term_id ) ;
	}

	/**
	 * Callback to add purge tags if admin selects to purge selected urls.
	 *
	 * @since 1.0.7
	 * @access public
	 * @param string $value A url to purge.
	 * @param string $key Unused.
	 */
	public function purgeby_url_cb( $value, $key = false )
	{
		$val = trim( $value ) ;
		if ( empty( $val ) ) {
			return ;
		}

		if ( strpos( $val, '<' ) !== false ) {
			LiteSpeed_Cache_Admin_Display::add_error( LiteSpeed_Cache_Admin_Error::E_PURGEBY_URL_BAD ) ;
			return ;
		}

		$val = LiteSpeed_Cache_Utility::make_relative( $val ) ;

		$hash = LiteSpeed_Cache_Tag::get_uri_tag( $val ) ;

		if ( $hash === false ) {
			LiteSpeed_Cache_Admin_Display::add_error( LiteSpeed_Cache_Admin_Error::E_PURGEBY_URL_INV, $val ) ;
			return ;
		}

		LiteSpeed_Cache_Admin_Display::add_notice( LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, sprintf( __( 'Purge url %s', 'litespeed-cache' ), $val ) ) ;

		self::add( $hash ) ;
		return ;
	}

	/**
	 * Purge a list of pages when selected by admin. This method will
	 * look at the post arguments to determine how and what to purge.
	 *
	 * @since 1.0.7
	 * @access public
	 */
	public function purge_list()
	{
		if ( ! isset($_REQUEST[LiteSpeed_Cache_Admin_Display::PURGEBYOPT_SELECT]) || ! isset($_REQUEST[LiteSpeed_Cache_Admin_Display::PURGEBYOPT_LIST]) ) {
			LiteSpeed_Cache_Admin_Display::add_error(LiteSpeed_Cache_Admin_Error::E_PURGE_FORM) ;
			return ;
		}
		$sel = $_REQUEST[LiteSpeed_Cache_Admin_Display::PURGEBYOPT_SELECT] ;
		$list_buf = $_REQUEST[LiteSpeed_Cache_Admin_Display::PURGEBYOPT_LIST] ;
		if ( empty($list_buf) ) {
			LiteSpeed_Cache_Admin_Display::add_error(LiteSpeed_Cache_Admin_Error::E_PURGEBY_EMPTY) ;
			return ;
		}
		$list_buf = str_replace(",", "\n", $list_buf) ;// for cli
		$list = explode("\n", $list_buf) ;
		switch($sel) {
			case LiteSpeed_Cache_Admin_Display::PURGEBY_CAT:
				$cb = 'purgeby_cat_cb' ;
				break ;
			case LiteSpeed_Cache_Admin_Display::PURGEBY_PID:
				$cb = 'purgeby_pid_cb' ;
				break ;
			case LiteSpeed_Cache_Admin_Display::PURGEBY_TAG:
				$cb = 'purgeby_tag_cb' ;
				break ;
			case LiteSpeed_Cache_Admin_Display::PURGEBY_URL:
				$cb = 'purgeby_url_cb' ;
				break ;
			default:
				LiteSpeed_Cache_Admin_Display::add_error(LiteSpeed_Cache_Admin_Error::E_PURGEBY_BAD) ;
				return ;
		}
		array_walk($list, Array($this, $cb)) ;

		// for redirection
		$_GET[LiteSpeed_Cache_Admin_Display::PURGEBYOPT_SELECT] = $sel ;
	}

	/**
	 * Purge frontend url
	 *
	 * @since 1.3
	 * @access public
	 */
	public static function frontend_purge()
	{
		if ( empty( $_SERVER[ 'HTTP_REFERER' ] ) ) {
			exit( 'no referer' ) ;
		}
		$instance = self::get_instance() ;

		$instance->purgeby_url_cb( $_SERVER[ 'HTTP_REFERER' ] ) ;

		wp_redirect( $_SERVER[ 'HTTP_REFERER' ] ) ;
		exit() ;
	}

	/**
	 * Purge a post on update.
	 *
	 * This function will get the relevant purge tags to add to the response
	 * as well.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param integer $id The post id to purge.
	 */
	public static function purge_post( $id )
	{
		$post_id = intval($id) ;
		// ignore the status we don't care
		if ( ! in_array(get_post_status($post_id), array( 'publish', 'trash', 'private', 'draft' )) ) {
			return ;
		}

		$purge_tags = self::get_purge_tags_by_post($post_id) ;
		if ( empty($purge_tags) ) {
			return ;
		}
		if ( in_array( '*', $purge_tags ) ) {
			self::purge_all() ;
		}
		else {
			self::add( $purge_tags ) ;
			if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CACHE_REST ) ) {
				self::add( LiteSpeed_Cache_Tag::TYPE_REST ) ;
			}
		}
		LiteSpeed_Cache_Control::set_stale() ;
	}

	/**
	 * Hooked to the load-widgets.php action.
	 * Attempts to purge a single widget from cache.
	 * If no widget id is passed in, the method will attempt to find the widget id.
	 *
	 * @since 1.1.3
	 * @access public
	 * @param type $widget_id The id of the widget to purge.
	 */
	public static function purge_widget($widget_id = null)
	{
		if ( is_null($widget_id) ) {
			$widget_id = $_POST['widget-id'] ;
			if ( is_null($widget_id) ) {
				return ;
			}
		}
		self::add(LiteSpeed_Cache_Tag::TYPE_WIDGET . $widget_id) ;
		self::add_private(LiteSpeed_Cache_Tag::TYPE_WIDGET . $widget_id) ;
	}

	/**
	 * Hooked to the wp_update_comment_count action.
	 * Purges the comment widget when the count is updated.
	 *
	 * @access public
	 * @since 1.1.3
	 * @global type $wp_widget_factory
	 */
	public static function purge_comment_widget()
	{
		global $wp_widget_factory ;
		$recent_comments = $wp_widget_factory->widgets['WP_Widget_Recent_Comments'] ;
		if ( !is_null($recent_comments) ) {
			self::add(LiteSpeed_Cache_Tag::TYPE_WIDGET . $recent_comments->id) ;
			self::add_private(LiteSpeed_Cache_Tag::TYPE_WIDGET . $recent_comments->id) ;
		}
	}

	/**
	 * Purges feeds on comment count update.
	 *
	 * @since 1.0.9
	 * @access public
	 */
	public static function purge_feeds()
	{
		if ( LiteSpeed_Cache::config(LiteSpeed_Cache_Config::OPID_FEED_TTL) > 0 ) {
			self::add(LiteSpeed_Cache_Tag::TYPE_FEED) ;
		}
	}

	/**
	 * Purges all private cache entries when the user logs out.
	 *
	 * @access public
	 * @since 1.1.3
	 */
	public static function purge_on_logout()
	{
		self::add_private('*') ;
	}

	/**
	 * Generate all purge tags before output
	 *
	 * @access private
	 * @since 1.1.3
	 */
	private static function _finalize()
	{
		// Make sure header output only run once
		if ( ! defined( 'LITESPEED_DID_' . __FUNCTION__ ) ) {
			define( 'LITESPEED_DID_' . __FUNCTION__, true ) ;
		}
		else {
			return ;
		}

		do_action('litespeed_cache_api_purge') ;

		// Append unique uri purge tags if Admin QS is `PURGESINGLE`
		if ( self::$_purge_single ) {
			self::$_pub_purge[] = LiteSpeed_Cache_Tag::build_uri_tag() ; // TODO: add private tag too
		}
		// Append related purge tags if Admin QS is `PURGE`
		if ( self::$_purge_related ) {
			// Before this, tags need to be finalized
			$tags_related = LiteSpeed_Cache_Tag::output_tags() ;
			// NOTE: need to remove the empty item `B1_` to avoid purging all
			$tags_related = array_filter($tags_related) ;
			if ( $tags_related ) {
				self::$_pub_purge = array_merge(self::$_pub_purge, $tags_related) ;
			}
		}

		if ( ! empty(self::$_pub_purge) ) {
			self::$_pub_purge = array_unique(self::$_pub_purge) ;
		}

		if ( ! empty(self::$_priv_purge) ) {
			self::$_priv_purge = array_unique(self::$_priv_purge) ;
		}
	}

	/**
	 * Gathers all the purge headers.
	 *
	 * This will collect all site wide purge tags as well as third party plugin defined purge tags.
	 *
	 * @since 1.1.0
	 * @access public
	 * @return string the built purge header
	 */
	public static function output()
	{
		self::_finalize() ;

		return self::_build() ;
	}

	/**
	 * Build the current purge headers.
	 *
	 * @since 1.1.5
	 * @access private
	 * @return string the built purge header
	 */
	private static function _build()
	{
		if ( empty( self::$_pub_purge ) && empty( self::$_priv_purge ) ) {
			return ;
		}

		$purge_header = '' ;
		$private_prefix = self::X_HEADER . ': private,' ;

		if ( ! empty( self::$_pub_purge ) ) {
			$public_tags = self::_append_prefix( self::$_pub_purge ) ;
			if ( empty( $public_tags ) ) {
				// If this ends up empty, private will also end up empty
				return ;
			}
			$purge_header = self::X_HEADER . ': public,' ;
			if ( LiteSpeed_Cache_Control::is_stale() ) {
				$purge_header .= 'stale,' ;
			}
			$purge_header .= implode( ',', $public_tags ) ;
			$private_prefix = ';private,' ;
		}

		// Handle priv purge tags
		if ( ! empty( self::$_priv_purge ) ) {
			$private_tags = self::_append_prefix( self::$_priv_purge, true ) ;
			$purge_header .= $private_prefix . implode( ',', $private_tags ) ;
		}

		return $purge_header ;
	}

	/**
	 * Append prefix to an array of purge headers
	 *
	 * @since 1.1.0
	 * @access private
	 * @param array $purge_tags The purge tags to apply the prefix to.
	 * @param  boolean $is_private If is private tags or not.
	 * @return array The array of built purge tags.
	 */
	private static function _append_prefix( $purge_tags, $is_private = false )
	{
		$curr_bid = get_current_blog_id() ;

		if ( ! in_array('*', $purge_tags) ) {
			$tags = array() ;
			foreach ($purge_tags as $val) {
				$tags[] = LSWCP_TAG_PREFIX . $curr_bid . '_' . $val ;
			}
			return $tags ;
		}

		if ( defined('LSWCP_EMPTYCACHE') || $is_private ) {
			return array('*') ;
		}

		// Would only use multisite and network admin except is_network_admin
		// is false for ajax calls, which is used by wordpress updates v4.6+
		if ( is_multisite() && (is_network_admin() || (
				LiteSpeed_Cache_Router::is_ajax() && (check_ajax_referer('updates', false, false) || check_ajax_referer('litespeed-purgeall-network', false, false))
				)) ) {
			$blogs = LiteSpeed_Cache_Activation::get_network_ids() ;
			if ( empty($blogs) ) {
				LiteSpeed_Cache_Log::debug('build_purge_headers: blog list is empty') ;
				return '' ;
			}
			$tags = array() ;
			foreach ($blogs as $blog_id) {
				$tags[] = LSWCP_TAG_PREFIX . $blog_id . '_' ;
			}
			return $tags ;
		}
		else {
			return array(LSWCP_TAG_PREFIX . $curr_bid . '_') ;
		}
	}

	/**
	 * Gets all the purge tags correlated with the post about to be purged.
	 *
	 * If the purge all pages configuration is set, all pages will be purged.
	 *
	 * This includes site wide post types (e.g. front page) as well as
	 * any third party plugin specific post tags.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param integer $post_id The id of the post about to be purged.
	 * @return array The list of purge tags correlated with the post.
	 */
	public static function get_purge_tags_by_post( $post_id )
	{
		// If this is a valid post we want to purge the post, the home page and any associated tags & cats
		// If not, purge everything on the site.

		$purge_tags = array() ;
		$config = LiteSpeed_Cache_Config::get_instance() ;

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_ALL_PAGES) ) {
			// ignore the rest if purge all
			return array( '*' ) ;
		}

		// now do API hook action for post purge
		do_action('litespeed_cache_api_purge_post', $post_id) ;

		// post
		$purge_tags[] = LiteSpeed_Cache_Tag::TYPE_POST . $post_id ;
		$purge_tags[] = LiteSpeed_Cache_Tag::get_uri_tag(wp_make_link_relative(get_permalink($post_id))) ;

		// for archive of categories|tags|custom tax
		global $post ;
		$post = get_post($post_id) ;
		$post_type = $post->post_type ;

		global $wp_widget_factory ;
		$recent_posts = $wp_widget_factory->widgets['WP_Widget_Recent_Posts'] ;
		if ( ! is_null($recent_posts) ) {
			$purge_tags[] = LiteSpeed_Cache_Tag::TYPE_WIDGET . $recent_posts->id ;
		}

		// get adjacent posts id as related post tag
		if( $post_type == 'post' ){
			$prev_post = get_previous_post() ;
			$next_post = get_next_post() ;
			if( ! empty($prev_post->ID) ) {
				$purge_tags[] = LiteSpeed_Cache_Tag::TYPE_POST . $prev_post->ID ;
				LiteSpeed_Cache_Log::debug('--------purge_tags prev is: '.$prev_post->ID) ;
			}
			if( ! empty($next_post->ID) ) {
				$purge_tags[] = LiteSpeed_Cache_Tag::TYPE_POST . $next_post->ID ;
				LiteSpeed_Cache_Log::debug('--------purge_tags next is: '.$next_post->ID) ;
			}
		}

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_TERM) ) {
			$taxonomies = get_object_taxonomies($post_type) ;
			//LiteSpeed_Cache_Log::debug('purge by post, check tax = ' . var_export($taxonomies, true)) ;
			foreach ( $taxonomies as $tax ) {
				$terms = get_the_terms($post_id, $tax) ;
				if ( ! empty($terms) ) {
					foreach ( $terms as $term ) {
						$purge_tags[] = LiteSpeed_Cache_Tag::TYPE_ARCHIVE_TERM . $term->term_id ;
					}
				}
			}
		}

		if ( $config->get_option(LiteSpeed_Cache_Config::OPID_FEED_TTL) > 0 ) {
			$purge_tags[] = LiteSpeed_Cache_Tag::TYPE_FEED ;
		}

		// author, for author posts and feed list
		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_AUTHOR) ) {
			$purge_tags[] = LiteSpeed_Cache_Tag::TYPE_AUTHOR . get_post_field('post_author', $post_id) ;
		}

		// archive and feed of post type
		// todo: check if type contains space
		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_POST_TYPE) ) {
			if ( get_post_type_archive_link($post_type) ) {
				$purge_tags[] = LiteSpeed_Cache_Tag::TYPE_ARCHIVE_POSTTYPE . $post_type ;
			}
		}

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_FRONT_PAGE) ) {
			$purge_tags[] = LiteSpeed_Cache_Tag::TYPE_FRONTPAGE ;
		}

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_HOME_PAGE) ) {
			$purge_tags[] = LiteSpeed_Cache_Tag::TYPE_HOME ;
		}

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_PAGES) ) {
			$purge_tags[] = LiteSpeed_Cache_Tag::TYPE_PAGES ;
		}

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_PAGES_WITH_RECENT_POSTS) ) {
			$purge_tags[] = LiteSpeed_Cache_Tag::TYPE_PAGES_WITH_RECENT_POSTS ;
		}

		// if configured to have archived by date
		$date = $post->post_date ;
		$date = strtotime($date) ;

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_DATE) ) {
			$purge_tags[] = LiteSpeed_Cache_Tag::TYPE_ARCHIVE_DATE . date('Ymd', $date) ;
		}

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_MONTH) ) {
			$purge_tags[] = LiteSpeed_Cache_Tag::TYPE_ARCHIVE_DATE . date('Ym', $date) ;
		}

		if ( $config->purge_by_post(LiteSpeed_Cache_Config::PURGE_YEAR) ) {
			$purge_tags[] = LiteSpeed_Cache_Tag::TYPE_ARCHIVE_DATE . date('Y', $date) ;
		}

		return array_unique($purge_tags) ;
	}

	/**
	 * The dummy filter for purge all
	 *
	 * @since 1.1.5
	 * @access public
	 * @param string $val The filter value
	 * @return string     The filter value
	 */
	public static function filter_with_purge_all( $val )
	{
		self::purge_all() ;
		return $val ;
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
