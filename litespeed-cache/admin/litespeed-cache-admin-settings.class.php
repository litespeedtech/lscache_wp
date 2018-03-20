<?php
/**
 * The admin settings handler of the plugin.
 *
 *
 * @since      1.1.0
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/admin
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Admin_Settings
{
	private static $_instance ;

	private $_input ;
	private $_options ;
	private $_err ;

	private $_max_int = 2147483647 ;

	/**
	 * Init
	 *
	 * @since  1.3
	 * @access private
	 */
	private function __construct()
	{
	}

	/**
	 * Display err msg for ttl
	 *
	 * @since  2.0
	 * @access private
	 */
	private function _show_ttl_err( $desc, $min, $max )
	{
		if ( ! $max ) {
			return sprintf( __( '%1$s must be an integer larger than %2$d', 'litespeed-cache' ), $desc, $min ) ;
		}

		return sprintf( __( '%1$s must be an integer between %2$d and %3$d', 'litespeed-cache' ), $desc, $min, $max ) ;
	}

	/**
	 * Callback function that will validate any changes made in the settings page.
	 *
	 * NOTE: Anytime that validate_plugin_settings is called, `convert_options_to_input` must be done first if not from option page
	 *
	 * @since 1.0.0
	 * @access public
	 * @param array $input The configuration selected by the admin when clicking save.
	 * @return array The updated configuration options.
	 */
	public function validate_plugin_settings( $input )
	{
		LiteSpeed_Cache_Log::debug( 'Settings: validate_plugin_settings called' ) ;
		$this->_options = LiteSpeed_Cache_Config::get_instance()->get_options() ;

		if ( LiteSpeed_Cache_Admin_Display::get_instance()->get_disable_all() ) {
			add_settings_error( LiteSpeed_Cache_Config::OPTION_NAME, LiteSpeed_Cache_Config::OPTION_NAME, __( '\'Use primary site settings\' set by Network Administrator.', 'litespeed-cache' ) ) ;

			return $this->_options ;
		}

		$this->_input = $input ;

		$this->_validate_general() ;

		$this->_validate_cache() ;

		$this->_validate_purge() ;

		$this->_validate_exclude() ;

		$this->_validate_optimize() ;

		$this->_validate_media() ;

		$this->_validate_cdn() ;

		$this->_validate_adv() ;

		$this->_validate_debug() ;

		$this->_validate_crawler() ; // Network setup doesn't run validate_plugin_settings

		if ( ! is_multisite() ) {
			$this->_validate_singlesite() ;
		}

		if ( LSWCP_ESI_SUPPORT ) {
			$orig_esi_enabled = $this->_options[ LiteSpeed_Cache_Config::OPID_ESI_ENABLE ] ;

			$this->_validate_esi() ;

			$new_esi_enabled = $this->_options[ LiteSpeed_Cache_Config::OPID_ESI_ENABLE ] ;

			if ( $orig_esi_enabled !== $new_esi_enabled ) {
				LiteSpeed_Cache_Purge::purge_all() ;
			}
		}

		if ( ! empty( $this->_err ) ) {
			add_settings_error( LiteSpeed_Cache_Config::OPTION_NAME, LiteSpeed_Cache_Config::OPTION_NAME, implode( '<br />', $this->_err ) ) ;

			return $this->_options ;
		}

		$cron_changed = false ;
		if ( defined( 'LITESPEED_CLI' ) ) {
			$id = LiteSpeed_Cache_Config::CRWL_CRON_ACTIVE ;
			$cron_val = $this->_options[ $id ] ;
			// assign crawler_cron_active to $this->_options if exists in $this->_input separately for CLI
			// This has to be specified cos crawler cron activation is not set in admin setting page
			$this->_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
			if ( $cron_val != $this->_options[ $id ] ) {
				$cron_changed = true ;
			}
		}

		// check if need to enable crawler cron
		if ( defined( 'LITESPEED_ON_CHANGED' ) || $cron_changed ) {
			LiteSpeed_Cache_Task::update( $this->_options ) ;
		}

		$this->_validate_thirdparty( ) ;

		return $this->_options ;
	}

	/**
	 * Validates the single site specific settings.
	 *
	 * @since 1.0.12
	 * @access private
	 */
	private function _validate_singlesite()
	{
		$id = LiteSpeed_Cache_Config::OPID_CHECK_ADVANCEDCACHE ;
		$this->_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;

		// Parse rewrite rule settings
		$new_options = $this->_validate_rewrite_settings() ;
		$this->_options = array_merge( $this->_options, $new_options ) ;

		// Try to update rewrite rules
		$disable_lscache_detail_rules = false ;
		if ( defined( 'LITESPEED_NEW_OFF' ) ) {
			// Clear lscache rules but keep lscache module rules, keep non-lscache rules
			$disable_lscache_detail_rules = true ;
		}
		$res = LiteSpeed_Cache_Admin_Rules::get_instance()->update( $this->_options, $disable_lscache_detail_rules ) ;
		if ( $res !== true ) {
			if ( ! is_array( $res ) ) {
				$this->_err[] = $res ;
			}
			else {
				$this->_err = array_merge( $this->_err, $res ) ;
			}
		}

		/**
		 * Validate Object Cache
		 * @since 1.8
		 */
		$new_options = $this->_validate_object_cache() ;
		$this->_options = array_merge( $this->_options, $new_options ) ;

	}

	/**
	 * Parses any changes made by the network admin on the network settings.
	 *
	 * @since 1.0.4
	 * @access public
	 */
	public function validate_network_settings()
	{
		$input = array_map( 'LiteSpeed_Cache_Admin::cleanup_text', $_POST[ LiteSpeed_Cache_Config::OPTION_NAME ] ) ;
		$this->_input = $input ;

		$options = LiteSpeed_Cache_Config::get_instance()->get_site_options() ;

		$id = LiteSpeed_Cache_Config::NETWORK_OPID_ENABLED ;
		$network_enabled = self::parse_onoff( $this->_input, $id ) ;
		if ( $options[ $id ] != $network_enabled ) {
			$options[ $id ] = $network_enabled ;
			if ( $network_enabled ) {
				$ret = LiteSpeed_Cache_Config::wp_cache_var_setter( true ) ;
				if ( $ret !== true ) {
					$this->_err[] = $ret ;
				}
			}
			else {
				LiteSpeed_Cache_Purge::purge_all() ;
			}
		}

		$id = LiteSpeed_Cache_Config::NETWORK_OPID_USE_PRIMARY ;
		$orig_primary = $options[ $id ] ;
		$options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		if ( $orig_primary != $options[ $id ] ) {
			LiteSpeed_Cache_Purge::purge_all() ;
		}

		$id = LiteSpeed_Cache_Config::OPID_PURGE_ON_UPGRADE ;
		$options[ $id ] = self::parse_onoff( $this->_input, $id ) ;

		$id = LiteSpeed_Cache_Config::OPID_CHECK_ADVANCEDCACHE ;
		$options[ $id ] = self::parse_onoff( $this->_input, $id ) ;

		// Parse rewrite settings from input
		$new_options = $this->_validate_rewrite_settings() ;
		$options = array_merge( $options, $new_options ) ;

		// Update htaccess
		$disable_lscache_detail_rules = false ;
		if ( ! $network_enabled ) {
			// Clear lscache rules but keep lscache module rules, keep non-lscache rules
			// Need to set cachePublicOn in case subblogs turn on cache manually
			$disable_lscache_detail_rules = true ;
		}
		$res = LiteSpeed_Cache_Admin_Rules::get_instance()->update( $options, $disable_lscache_detail_rules ) ;
		if ( $res !== true ) {
			if ( ! is_array( $res ) ) {
				$this->_err[] = $res ;
			}
			else {
				$this->_err = array_merge( $this->_err, $res ) ;
			}
		}

		/**
		 * Validate Object Cache
		 * @since 1.8
		 */
		$new_options = $this->_validate_object_cache() ;
		$options = array_merge( $options, $new_options ) ;

		if ( ! empty( $this->_err ) ) {
			LiteSpeed_Cache_Admin_Display::add_notice( LiteSpeed_Cache_Admin_Display::NOTICE_RED, $this->_err ) ;
			return ;
		}

		LiteSpeed_Cache_Admin_Display::add_notice( LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, __( 'Site options saved.', 'litespeed-cache' ) ) ;
		update_site_option( LiteSpeed_Cache_Config::OPTION_NAME, $options ) ;
		return $options ;
	}

	/**
	 * Validates object cache settings.
	 *
	 * @since 1.8
	 * @access private
	 */
	private function _validate_object_cache()
	{
		$new_options = array() ;

		$ids = array(
			LiteSpeed_Cache_Config::OPID_CACHE_OBJECT,
			LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_KIND,
			LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_ADMIN,
			LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_TRANSIENTS,
			LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_PERSISTENT,
		) ;
		foreach ( $ids as $id ) {
			$new_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		}

		$ids = array(
			LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_HOST,
			LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_PORT,
			LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_LIFE,
			LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_DB_ID,
			LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_USER,
			LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_PSWD,
		);
		foreach ( $ids as $id ) {
			$new_options[ $id ] = $this->_input[ $id ] ;
		}

		$ids = array(
			LiteSpeed_Cache_Config::ITEM_OBJECT_GLOBAL_GROUPS,
			LiteSpeed_Cache_Config::ITEM_OBJECT_NON_PERSISTENT_GROUPS,
		);
		$item_options = array() ;
		foreach ( $ids as $id ) {
			$item_options[ $id ] = ! empty( $this->_input[ $id ] ) ? LiteSpeed_Cache_Utility::sanitize_lines( $this->_input[ $id ] ) : '' ;
			update_option( $id, $item_options[ $id ] ) ;
		}

		/**
		 * Check if object cache file existing or not
		 */
		$id = LiteSpeed_Cache_Config::OPID_CACHE_OBJECT ;
		if ( $new_options[ $id ] ) {
			$all_options = array_merge( $new_options, $item_options ) ;
			LiteSpeed_Cache_Log::debug( 'Settings: Update .object_cache.ini and flush object cache' ) ;
			LiteSpeed_Cache_Object::get_instance()->update_file( true, $all_options ) ;
			/**
			 * Clear object cache
			 */
			LiteSpeed_Cache_Object::get_instance()->reconnect( $all_options ) ;
		}
		else {
			if ( defined( 'LSCWP_OBJECT_CACHE' ) ) {
				LiteSpeed_Cache_Log::debug( 'Settings: Remove .object_cache.ini' ) ;
				LiteSpeed_Cache_Object::get_instance()->update_file( false ) ;
			}
		}

		return $new_options ;

	}

	/**
	 * Validates the general settings.
	 *
	 * @since 1.0.12
	 * @access private
	 */
	private function _validate_general()
	{
		// Cache enabled setting
		$id = LiteSpeed_Cache_Config::OPID_ENABLED_RADIO ;
		$this->_options[ $id ] = self::is_checked_radio( $this->_input[ $id ] ) ;

		$enabled = $this->_options[ $id ] ;
		// Use network setting
		if( $enabled === LiteSpeed_Cache_Config::VAL_ON2 ) {
			$enabled = is_multisite() ? defined( 'LITESPEED_NETWORK_ON' ) : true ; // Default to true
		}

		// Purge when disabled
		if ( ! $enabled ) {
			LiteSpeed_Cache_Purge::purge_all() ;
			! defined( 'LITESPEED_NEW_OFF' ) && define( 'LITESPEED_NEW_OFF', true ) ; // Latest status is off
		}
		else {
			! defined( 'LITESPEED_NEW_ON' ) && define( 'LITESPEED_NEW_ON', true ) ; // Latest status is on
		}

		// Status changed
		if ( defined( 'LITESPEED_ON' ) xor $enabled ) {
			define( 'LITESPEED_ON_CHANGED', true ) ;
		}

		// TTL check
		$ids = array(
			LiteSpeed_Cache_Config::OPID_PUBLIC_TTL 		=> array( __( 'Default Public Cache', 'litespeed-cache' ), 30, $this->_max_int ),
			LiteSpeed_Cache_Config::OPID_PRIVATE_TTL	 	=> array( __( 'Default Private Cache', 'litespeed-cache' ), 60, 3600 ),
			LiteSpeed_Cache_Config::OPID_FRONT_PAGE_TTL 	=> array( __( 'Default Front Page', 'litespeed-cache' ), 30, $this->_max_int ),
			LiteSpeed_Cache_Config::OPID_FEED_TTL		 	=> array( __( 'Feed', 'litespeed-cache' ), 0, $this->_max_int, 30 ),
			LiteSpeed_Cache_Config::OPID_404_TTL		 	=> array( '404', 0, $this->_max_int, 30 ),
			LiteSpeed_Cache_Config::OPID_403_TTL		 	=> array( '403', 0, $this->_max_int, 30 ),
			LiteSpeed_Cache_Config::OPID_500_TTL		 	=> array( '500', 0, $this->_max_int, 30 ),
		) ;
		foreach ( $ids as $id => $v ) {
			list( $desc, $min, $max ) = $v ;
			if ( ! $this->_check_ttl( $this->_input, $id, $min, $max ) ) {
				$this->_err[] = $this->_show_ttl_err( $desc, $min, $max ) ;
			}
			else {
				if ( ! empty( $v[ 3 ] ) && $this->_input[ $id ] < $v[ 3 ] ) {
					$this->_options[ $id ] = 0 ;
				}
				else {
					$this->_options[ $id ] = $this->_input[ $id ] ;
				}
			}
		}

	}

	/**
	 * Validates the cache control settings.
	 *
	 * @since 1.1.6
	 * @access private
	 */
	private function _validate_cache()
	{
		$ids = array(
			LiteSpeed_Cache_Config::OPID_CACHE_PRIV,
			LiteSpeed_Cache_Config::OPID_CACHE_COMMENTER,
			LiteSpeed_Cache_Config::OPID_CACHE_REST,
		);
		foreach ( $ids as $id ) {
			$this->_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		}

		$id = LiteSpeed_Cache_Config::OPID_CACHE_PAGE_LOGIN ;
		$this->_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		if( ! $this->_options[ $id ] ) {
			LiteSpeed_Cache_Purge::add( LiteSpeed_Cache_Tag::TYPE_LOGIN ) ;
		}

		$id = LiteSpeed_Cache_Config::OPID_CACHE_URI_PRIV ;
		if ( isset( $this->_input[ $id ]) ) {
			$this->_options[ $id ] = LiteSpeed_Cache_Utility::sanitize_lines( $this->_input[ $id ], 'relative' ) ;
		}

		$ids = array(
			LiteSpeed_Cache_Config::ITEM_CACHE_DROP_QS, // Update Drop Query String @since 1.7
		);
		foreach ( $ids as $id ) {
			update_option( $id, ! empty( $this->_input[ $id ] ) ? LiteSpeed_Cache_Utility::sanitize_lines( $this->_input[ $id ] ) : '' ) ;
		}

	}

	/**
	 * Validates the purge settings.
	 *
	 * @since 1.0.12
	 * @access private
	 */
	private function _validate_purge()
	{
		$id = LiteSpeed_Cache_Config::OPID_PURGE_ON_UPGRADE ;
		$this->_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;

		// get auto purge rules options
		$pvals = array(
			LiteSpeed_Cache_Config::PURGE_ALL_PAGES,
			LiteSpeed_Cache_Config::PURGE_FRONT_PAGE,
			LiteSpeed_Cache_Config::PURGE_HOME_PAGE,
			LiteSpeed_Cache_Config::PURGE_PAGES,
			LiteSpeed_Cache_Config::PURGE_PAGES_WITH_RECENT_POSTS,
			LiteSpeed_Cache_Config::PURGE_AUTHOR,
			LiteSpeed_Cache_Config::PURGE_YEAR,
			LiteSpeed_Cache_Config::PURGE_MONTH,
			LiteSpeed_Cache_Config::PURGE_DATE,
			LiteSpeed_Cache_Config::PURGE_TERM,
			LiteSpeed_Cache_Config::PURGE_POST_TYPE,
		) ;
		$input_purge_options = array() ;
		foreach ( $pvals as $v) {
			$input_name = 'purge_' . $v ;
			if ( self::parse_onoff( $this->_input, $input_name ) ) {
				$input_purge_options[] = $v ;
			}
		}
		sort( $input_purge_options ) ;
		$purge_by_post = implode( '.', $input_purge_options ) ;
		if ( $purge_by_post !== $this->_options[ LiteSpeed_Cache_Config::OPID_PURGE_BY_POST ] ) {
			$this->_options[ LiteSpeed_Cache_Config::OPID_PURGE_BY_POST ] = $purge_by_post ;
		}

		// Filter scheduled purge URLs
		$id = LiteSpeed_Cache_Config::OPID_TIMED_URLS ;
		if ( isset( $this->_input[ $id ] ) ) {
			$this->_options[ $id ] = LiteSpeed_Cache_Utility::sanitize_lines( $this->_input[ $id ], 'relative' ) ;
		}

		// Schduled Purge Time
		$id = LiteSpeed_Cache_Config::OPID_TIMED_URLS_TIME ;
		$this->_options[ $id ] = $this->_input[ $id ] ;
	}

	/**
	 * Validates the exclude settings.
	 *
	 * @since 1.0.12
	 * @access private
	 */
	private function _validate_exclude()
	{
		$id = LiteSpeed_Cache_Config::OPID_EXCLUDES_URI ;
		if ( isset( $this->_input[ $id ] ) ) {
			$this->_options[ $id ] = LiteSpeed_Cache_Utility::sanitize_lines( $this->_input[ $id ], 'relative' ) ;
		}

		$id = LiteSpeed_Cache_Config::OPID_EXCLUDES_QS ;
		if ( isset( $this->_input[ $id ] ) ) {
			$this->_options[ $id ] = LiteSpeed_Cache_Utility::sanitize_lines( $this->_input[ $id ] ) ;
		}

		$id = LiteSpeed_Cache_Config::OPID_EXCLUDES_CAT ;
		$this->_options[ $id ] = '' ;
		if ( isset( $this->_input[ $id ] ) ) {
			$cat_ids = array() ;
			$cats = explode( "\n", $this->_input[ $id ] ) ;
			foreach ( $cats as $cat ) {
				$cat_name = trim( $cat ) ;
				if ( $cat_name == '' ) {
					continue ;
				}
				$cat_id = get_cat_ID( $cat_name ) ;
				if ( $cat_id == 0 ) {
					$this->_err[] = LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_SETTING_CAT, $cat_name ) ;
				}
				else {
					$cat_ids[] = $cat_id ;
				}
			}
			if ( ! empty( $cat_ids ) ) {
				$this->_options[ $id ] = implode( ',', $cat_ids ) ;
			}
		}

		$id = LiteSpeed_Cache_Config::OPID_EXCLUDES_TAG ;
		$this->_options[ $id ] = '' ;
		if ( isset( $this->_input[ $id ] ) ) {
			$tag_ids = array() ;
			$tags = explode( "\n", $this->_input[ $id ] ) ;
			foreach ( $tags as $tag ) {
				$tag_name = trim( $tag ) ;
				if ( $tag_name == '' ) {
					continue ;
				}
				$term = get_term_by( 'name', $tag_name, 'post_tag' ) ;
				if ( $term == 0 ) {
					$this->_err[] = LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_SETTING_TAG, $tag_name ) ;
				}
				else {
					$tag_ids[] = $term->term_id ;
				}
			}
			if ( ! empty( $tag_ids ) ) {
				$this->_options[ $id ] = implode( ',', $tag_ids ) ;
			}
		}

		/**
		 * Update Role Excludes
		 * @since 1.6.2
		 */
		$id = LiteSpeed_Cache_Config::EXCLUDE_CACHE_ROLES ;
		update_option( $id, ! empty( $this->_input[ $id ] ) ? $this->_input[ $id ] : array() ) ;

	}

	/**
	 * Validates the CDN settings.
	 *
	 * @since 1.2.2
	 * @access private
	 */
	private function _validate_cdn()
	{
		$cdn_cloudflare_changed = false ;
		$ids = array(
			LiteSpeed_Cache_Config::OPID_CDN,
			LiteSpeed_Cache_Config::OPID_CDN_QUIC,
			LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE,
		) ;
		foreach ( $ids as $id ) {
			$v = self::parse_onoff( $this->_input, $id ) ;
			if ( $this->_options[ $id ] === $v ) {
				continue ;
			}

			$this->_options[ $id ] = $v ;

			// Cloudflare setting is changed
			if ( $id == LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE ) {
				$cdn_cloudflare_changed = true ;
			}

		}

		$id = LiteSpeed_Cache_Config::OPID_CDN_ORI ;
		$this->_options[ $id ] = $this->_input[ $id ] ;
		if ( $this->_options[ $id ] ) {
			$ori_list = explode( ',', $this->_options[ $id ] ) ;
			foreach ( $ori_list as $k => $v ) {
				$tmp = parse_url( $v ) ;
				if ( ! empty( $tmp[ 'scheme' ] ) ) {
					$ori_list[ $k ] = str_replace( $tmp[ 'scheme' ] . ':', '', $v ) ;
				}
			}
			$this->_options[ $id ] = implode( ',', $ori_list ) ;
		}

		$ids = array(
			LiteSpeed_Cache_Config::OPID_CDN_EXCLUDE,
		) ;
		foreach ( $ids as $id ) {
			$this->_options[ $id ] = LiteSpeed_Cache_Utility::sanitize_lines( $this->_input[ $id ] ) ;
		}

		/**
		 * Handle multiple CDN setting
		 * @since 1.7
		 */
		$cdn_mapping = array() ;
		$mapping_fields = array(
			LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_URL,
			LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_IMG,
			LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_CSS,
			LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_INC_JS,
			LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_FILETYPE
		) ;
		$id = LiteSpeed_Cache_Config::ITEM_CDN_MAPPING ;
		foreach ( $this->_input[ $id ][ LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_URL ] as $k => $v ) {
			$this_mapping = array() ;
			foreach ( $mapping_fields as $f ) {
				$this_mapping[ $f ] = ! empty( $this->_input[ $id ][ $f ][ $k ] ) ? $this->_input[ $id ][ $f ][ $k ] : false ;
				if ( $f === LiteSpeed_Cache_Config::ITEM_CDN_MAPPING_FILETYPE ) {
					$this_mapping[ $f ] = LiteSpeed_Cache_Utility::sanitize_lines( $this_mapping[ $f ] ) ;
				}
			}

			$cdn_mapping[] = $this_mapping ;
		}
		update_option( LiteSpeed_Cache_Config::ITEM_CDN_MAPPING, $cdn_mapping ) ;

		/**
		 * Load jQuery from cdn
		 * @since 1.5
		 */
		$id = LiteSpeed_Cache_Config::OPID_CDN_REMOTE_JQUERY ;
		$this->_options[ $id ] = self::is_checked_radio( $this->_input[ $id ] ) ;

		/**
		 * Quic API
		 * @since  1.9.1
		 */
		$ids = array(
			LiteSpeed_Cache_Config::OPID_CDN_QUIC_EMAIL,
			LiteSpeed_Cache_Config::OPID_CDN_QUIC_KEY,
			LiteSpeed_Cache_Config::OPID_CDN_QUIC_SITE,
		) ;
		foreach ( $ids as $id ) {
			if ( $this->_options[ $id ] === $this->_input[ $id ] ) {
				continue ;
			}
			$this->_options[ $id ] = $this->_input[ $id ] ;
		}

		/**
		 * CLoudflare API
		 * @since  1.7.2
		 */
		$ids = array(
			LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE_EMAIL,
			LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE_KEY,
			LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE_NAME,
		) ;
		foreach ( $ids as $id ) {
			if ( $this->_options[ $id ] === $this->_input[ $id ] ) {
				continue ;
			}
			$cdn_cloudflare_changed = true ;
			$this->_options[ $id ] = $this->_input[ $id ] ;
		}

		// If cloudflare API is on, refresh the zone
		if ( $this->_options[ LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE ] && $cdn_cloudflare_changed ) {
			$zone = LiteSpeed_Cache_CDN_Cloudflare::get_instance()->fetch_zone( $this->_options ) ;
			if ( $zone ) {
				$this->_options[ LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE_NAME ] = $zone[ 'name' ] ;
				$this->_options[ LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE_ZONE ] = $zone[ 'id' ] ;

				LiteSpeed_Cache_Log::debug( "Settings: Get zone successfully \t\t[ID] $zone[id]" ) ;
			}
			else {
				$this->_options[ LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE_ZONE ] = '' ;
				LiteSpeed_Cache_Log::debug( 'Settings: Get zone failed, clean zone' ) ;
			}
		}
	}

	/**
	 * Validates the media settings.
	 *
	 * @since 1.4
	 * @access private
	 */
	private function _validate_media()
	{
		$ids = array(
			LiteSpeed_Cache_Config::OPID_MEDIA_IMG_LAZY,
			LiteSpeed_Cache_Config::OPID_MEDIA_IFRAME_LAZY,
			LiteSpeed_Cache_Config::OPID_MEDIA_IMG_OPTM_CRON_OFF,
			LiteSpeed_Cache_Config::OPID_MEDIA_IMG_WEBP_ONLY,
			LiteSpeed_Cache_Config::OPID_MEDIA_IMG_EXIF,
			LiteSpeed_Cache_Config::OPID_MEDIA_IMG_WEBP_LOSSLESS,
		) ;
		foreach ( $ids as $id ) {
			$this->_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		}

		$id = LiteSpeed_Cache_Config::OPID_MEDIA_IMG_LAZY_PLACEHOLDER ;
		$this->_options[ $id ] = $this->_input[ $id ] ;

		// Update lazyload image excludes
		$id = LiteSpeed_Cache_Config::ITEM_MEDIA_LAZY_IMG_EXC ;
		update_option( $id, LiteSpeed_Cache_Utility::sanitize_lines( $this->_input[ $id ], 'uri' ) ) ;

	}

	/**
	 * Validates the optimize settings.
	 *
	 * @since 1.2.2
	 * @access private
	 */
	private function _validate_optimize()
	{
		$ids = array(
			LiteSpeed_Cache_Config::OPID_CSS_MINIFY,
			LiteSpeed_Cache_Config::OPID_CSS_INLINE_MINIFY,
			LiteSpeed_Cache_Config::OPID_CSS_COMBINE,
			LiteSpeed_Cache_Config::OPID_CSS_COMBINED_PRIORITY,
			LiteSpeed_Cache_Config::OPID_CSS_HTTP2,
			LiteSpeed_Cache_Config::OPID_JS_MINIFY,
			LiteSpeed_Cache_Config::OPID_JS_INLINE_MINIFY,
			LiteSpeed_Cache_Config::OPID_JS_COMBINE,
			LiteSpeed_Cache_Config::OPID_JS_COMBINED_PRIORITY,
			LiteSpeed_Cache_Config::OPID_JS_HTTP2,
			LiteSpeed_Cache_Config::OPID_HTML_MINIFY,
			LiteSpeed_Cache_Config::OPID_OPTM_QS_RM,
			LiteSpeed_Cache_Config::OPID_OPTM_GGFONTS_RM,
			LiteSpeed_Cache_Config::OPID_OPTM_CSS_ASYNC,
			LiteSpeed_Cache_Config::OPID_OPTM_JS_DEFER,
			LiteSpeed_Cache_Config::OPID_OPTM_EMOJI_RM,
			LiteSpeed_Cache_Config::OPID_OPTM_EXC_JQUERY,
			LiteSpeed_Cache_Config::OPID_OPTM_GGFONTS_ASYNC,
			LiteSpeed_Cache_Config::OPID_OPTM_RM_COMMENT,
		) ;
		foreach ( $ids as $id ) {
			$this->_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		}

		$ids = array(
			LiteSpeed_Cache_Config::OPID_CSS_EXCLUDES,
			LiteSpeed_Cache_Config::OPID_JS_EXCLUDES,
		) ;
		foreach ( $ids as $id ) {
			$this->_options[ $id ] = LiteSpeed_Cache_Utility::sanitize_lines( $this->_input[ $id ], 'uri' ) ;
		}

		$id = LiteSpeed_Cache_Config::OPID_OPTIMIZE_TTL ;
		if ( ! $this->_check_ttl( $this->_input, $id, 3600 ) ) {
			$this->_input[ $id ] = 3600 ;
		}
		$this->_options[ $id ] = $this->_input[ $id ] ;

		// Update critical css
		update_option( LiteSpeed_Cache_Config::ITEM_OPTM_CSS, $this->_input[ LiteSpeed_Cache_Config::ITEM_OPTM_CSS ] ) ;

		// prevent URI from optimization
		$id = LiteSpeed_Cache_Config::OPID_OPTM_EXCLUDES ;
		if ( isset( $this->_input[ $id ]) ) {
			$this->_options[ $id ] = LiteSpeed_Cache_Utility::sanitize_lines( $this->_input[ $id ], 'relative' ) ;
		}

		// Update js deferred excludes
		$id = LiteSpeed_Cache_Config::ITEM_OPTM_JS_DEFER_EXC ;
		update_option( $id, LiteSpeed_Cache_Utility::sanitize_lines( $this->_input[ $id ], 'uri' ) ) ;

		// Update Role Excludes
		$id = LiteSpeed_Cache_Config::EXCLUDE_OPTIMIZATION_ROLES ;
		update_option( $id, ! empty( $this->_input[ $id ] ) ? $this->_input[ $id ] : array() ) ;

		/**
		 * DNS prefetch
		 * @since 1.7.1
		 */
		$id = LiteSpeed_Cache_Config::ITEM_DNS_PREFETCH ;
		update_option( $id, LiteSpeed_Cache_Utility::sanitize_lines( $this->_input[ $id ], 'domain' ) ) ;

		/**
		 * Combined file max size
		 * @since 1.7.1
		 */
		$id = LiteSpeed_Cache_Config::OPID_OPTM_MAX_SIZE ;
		$this->_options[ $id ] = $this->_input[ $id ] ;
	}

	/**
	 * Validate advanced setting
	 *
	 * @since 1.7.1
	 * @access private
	 */
	private function _validate_adv()
	{
		$ids = array(
			LiteSpeed_Cache_Config::OPID_USE_HTTP_FOR_HTTPS_VARY,
			// LiteSpeed_Cache_Config::OPID_ADV_FAVICON,
			LiteSpeed_Cache_Config::OPID_ADV_INSTANT_CLICK,
		) ;
		foreach ( $ids as $id ) {
			$this->_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		}

		/**
		 * Added Favicon
		 * @since  1.7.2
		 */
		// $fav_file_arr = array( 'frontend', 'backend' ) ;
		// $new_favicons = array() ;
		// foreach ( $fav_file_arr as $v ) {
		// 	if ( ! empty( $_FILES[ 'litespeed-file-favicon_' . $v ][ 'name' ] ) ) {
		// 		$file = wp_handle_upload( $_FILES[ 'litespeed-file-favicon_' . $v ], array( 'action' => 'update' ) ) ;
		// 		if ( ! empty( $file[ 'url' ] ) ) {
		// 			LiteSpeed_Cache_Log::debug( 'Settings: Updated favicon [' . $v . '] ' . $file[ 'url' ] ) ;

		// 			$new_favicons[ $v ] = $file[ 'url' ] ;

		// 		}
		// 		elseif ( isset( $file[ 'error' ] ) ) {
		// 			LiteSpeed_Cache_Log::debug( 'Settings: Failed to update favicon: [' . $v . '] ' . $file[ 'error' ] ) ;
		// 		}
		// 		else {
		// 			LiteSpeed_Cache_Log::debug( 'Settings: Failed to update favicon: Unkown err [' . $v . ']' ) ;
		// 		}
		// 	}
		// }

		// if ( $new_favicons ) {
		// 	$cfg_favicon = get_option( LiteSpeed_Cache_Config::ITEM_FAVICON, array() ) ;
		// 	update_option( LiteSpeed_Cache_Config::ITEM_FAVICON, array_merge( $cfg_favicon, $new_favicons ) ) ;
		// }
	}

	/**
	 * Validates the debug settings.
	 *
	 * @since 1.0.12
	 * @access private
	 */
	private function _validate_debug()
	{
		$id = LiteSpeed_Cache_Config::OPID_ADMIN_IPS ;
		if ( isset( $this->_input[ $id ] ) ) {
			$admin_ips = array_map( 'trim', explode( "\n", trim( $this->_input[ $id ] ) ) ) ;
			$admin_ips = array_filter( $admin_ips ) ;
			$has_err = false ;
			if ( $admin_ips ) {
				foreach ( $admin_ips as $ip ) {
					if ( ! WP_Http::is_ip_address( $ip ) ) {
						$has_err = true ;
						break ;
					}
				}
			}
			$admin_ips = implode( "\n", $admin_ips ) ;

			if ( $has_err ) {
				$this->_err[] = LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_SETTING_ADMIN_IP_INV ) ;
			}
			else {
				$this->_options[ $id ] = $admin_ips ;
			}
		}

		$id = LiteSpeed_Cache_Config::OPID_DEBUG ;
		$this->_options[ $id ] = self::is_checked_radio( $this->_input[ $id ] ) ;

		$id = LiteSpeed_Cache_Config::OPID_LOG_FILE_SIZE ;
		if ( ! $this->_check_ttl( $this->_input, $id, 3, 3000 ) ) {
			$this->_err[] = $this->_show_ttl_err( __( 'Log File Size Limit', 'litespeed-cache' ), 3, 3000 ) ;
		}
		else {
			$this->_options[ $id ] = $this->_input[ $id ] ;
		}

		$ids = array(
			LiteSpeed_Cache_Config::OPID_DEBUG_LEVEL,
			LiteSpeed_Cache_Config::OPID_HEARTBEAT,
			LiteSpeed_Cache_Config::OPID_DEBUG_COOKIE,
			LiteSpeed_Cache_Config::OPID_COLLAPS_QS,
			LiteSpeed_Cache_Config::OPID_LOG_FILTERS,
		) ;
		foreach ( $ids as $id ) {
			$this->_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		}

		// Filters ignored
		$ids = array(
			LiteSpeed_Cache_Config::ITEM_LOG_IGNORE_FILTERS,
			LiteSpeed_Cache_Config::ITEM_LOG_IGNORE_PART_FILTERS,
		) ;
		foreach ( $ids as $id ) {
			update_option( $id, ! empty( $this->_input[ $id ] ) ? LiteSpeed_Cache_Utility::sanitize_lines( $this->_input[ $id ] ) : '' ) ;
		}
	}

	/**
	 * Validates the crawler settings.
	 *
	 * @since 1.0.12
	 * @access private
	 */
	private function _validate_crawler()
	{
		$ids = array(
			LiteSpeed_Cache_Config::CRWL_POSTS,
			LiteSpeed_Cache_Config::CRWL_PAGES,
			LiteSpeed_Cache_Config::CRWL_CATS,
			LiteSpeed_Cache_Config::CRWL_TAGS,
			LiteSpeed_Cache_Config::CRWL_HTTP2,
		) ;
		foreach ( $ids as $id ) {
			$this->_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		}

		$id = LiteSpeed_Cache_Config::CRWL_EXCLUDES_CPT ;
		if ( isset( $this->_input[ $id ] ) ) {
			$arr = array_map( 'trim', explode( "\n", $this->_input[ $id ] ) ) ;
			$arr = array_filter( $arr ) ;
			$ori = array_diff( get_post_types( '', 'names' ), array( 'post', 'page' ) ) ;
			$this->_options[ $id ] = implode( "\n", array_intersect( $arr, $ori ) ) ;
		}

		$id = LiteSpeed_Cache_Config::CRWL_ORDER_LINKS ;
		if( ! isset( $this->_input[ $id ] ) || ! in_array( $this->_input[ $id ], array(
				LiteSpeed_Cache_Config::CRWL_DATE_DESC,
				LiteSpeed_Cache_Config::CRWL_DATE_ASC,
				LiteSpeed_Cache_Config::CRWL_ALPHA_DESC,
				LiteSpeed_Cache_Config::CRWL_ALPHA_ASC,
			) )
		) {
			$this->_input[ $id ] = LiteSpeed_Cache_Config::CRWL_DATE_DESC ;
		}
		$this->_options[ $id ] = $this->_input[ $id ] ;

		$usleep_min = 0 ;
		$usleep_max = 30000 ;
		if ( ! empty( $_SERVER[ LiteSpeed_Cache_Config::ENV_CRAWLER_USLEEP ] ) ) {
			$usleep_min = $_SERVER[ LiteSpeed_Cache_Config::ENV_CRAWLER_USLEEP ] ;
			$usleep_max = null ;
		}
		$ids = array(
			LiteSpeed_Cache_Config::CRWL_USLEEP 		=> array( __( 'Delay', 'litespeed-cache' ), $usleep_min, $usleep_max ),
			LiteSpeed_Cache_Config::CRWL_RUN_DURATION 	=> array( __( 'Run Duration', 'litespeed-cache' ), 0, $this->_max_int ),
			LiteSpeed_Cache_Config::CRWL_RUN_INTERVAL 	=> array( __( 'Cron Interval', 'litespeed-cache' ), 60, $this->_max_int ),
			LiteSpeed_Cache_Config::CRWL_CRAWL_INTERVAL => array( __( 'Whole Interval', 'litespeed-cache' ), 0, $this->_max_int ),
			LiteSpeed_Cache_Config::CRWL_THREADS 		=> array( __( 'Threads', 'litespeed-cache' ), 1, 16 ),
		) ;
		foreach ( $ids as $id => $v ) {
			list( $desc, $min, $max ) = $v ;
			if ( ! $this->_check_ttl( $this->_input, $id, $min, $max ) ) {
				$this->_err[] = $this->_show_ttl_err( $desc, $min, $max ) ;
			}
			else {
				$this->_options[ $id ] = $this->_input[ $id ] ;
			}
		}


		$id = LiteSpeed_Cache_Config::CRWL_LOAD_LIMIT ;
		$this->_options[ $id ] = $this->_input[ $id ] ;

		$id = LiteSpeed_Cache_Config::CRWL_DOMAIN_IP ;
		if ( ! empty( $this->_input[ $id ] ) && ! WP_Http::is_ip_address( $this->_input[ $id ] ) ) {
			$this->_err[] = LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_SETTING_SITE_IP, $this->_input[ $id ] ) ;
		}
		else {
			$this->_options[ $id ] = $this->_input[ $id ] ;
		}

		$id = LiteSpeed_Cache_Config::CRWL_CUSTOM_SITEMAP ;
		if ( ! empty( $this->_input[ $id ] ) && ( $err = $this->_validate_custom_sitemap( $this->_input[ $id ] ) ) !== true ) {
			$this->_err[] = LiteSpeed_Cache_Admin_Display::get_error( $err, $this->_input[ $id ] ) ;
		}
		else {
			$this->_options[ $id ] = $this->_input[ $id ] ;
		}

		$id = LiteSpeed_Cache_Config::ITEM_CRWL_AS_UIDS ;
		update_option( $id, ! empty( $this->_input[ $id ] ) ? LiteSpeed_Cache_Utility::sanitize_lines( $this->_input[ $id ] ) : '' ) ;

	}

	/**
	 * Validates the custom sitemap settings.
	 *
	 * @since 1.1.1
	 * @access private
	 * @param string $url The sitemap url
	 */
	private function _validate_custom_sitemap( $url )
	{
		return LiteSpeed_Cache_Crawler::get_instance()->parse_custom_sitemap( $url, false ) ;
	}

	/**
	 * Validates settings related to rewrite rules
	 *
	 * @since 1.3
	 * @access private
	 * @return  array New options related to rewrite rule
	 */
	private function _validate_rewrite_settings()
	{
		$new_options = array() ;

		$ids = array(
			LiteSpeed_Cache_Config::OPID_CACHE_MOBILE,
			LiteSpeed_Cache_Config::OPID_CACHE_FAVICON,
			LiteSpeed_Cache_Config::OPID_CACHE_RES,
			LiteSpeed_Cache_Config::OPID_CACHE_BROWSER,
			LiteSpeed_Cache_Config::OPID_MEDIA_IMG_WEBP,
		) ;
		foreach ( $ids as $id ) {
			$new_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		}

		// TTL check
		$ids = array(
			LiteSpeed_Cache_Config::OPID_CACHE_BROWSER_TTL 		=> array( __( 'Default Public Cache', 'litespeed-cache' ), 30, $this->_max_int ),
		) ;
		foreach ( $ids as $id => $v ) {
			list( $desc, $min, $max ) = $v ;
			if ( ! $this->_check_ttl( $this->_input, $id, $min, $max ) ) {
				$this->_err[] = $this->_show_ttl_err( $desc, $min, $max ) ;
			}
			else {
				$new_options[ $id ] = $this->_input[ $id ] ;
			}
		}


		// check mobile agents
		$id = LiteSpeed_Cache_Config::ID_MOBILEVIEW_LIST ;
		if ( ! $this->_input[ $id ] &&  $new_options[ LiteSpeed_Cache_Config::OPID_CACHE_MOBILE ] ) {
			$this->_err[] = LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_SETTING_REWRITE, array( $id, 'EMPTY' ) ) ;
		}
		elseif ( $this->_input[ $id ] && ! $this->_syntax_checker( $this->_input[ $id ] ) ) {
			$this->_err[] = LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_SETTING_REWRITE, array( $id, esc_html( $this->_input[ $id ] ) ) ) ;
		}
		else {
			$new_options[ $id ] = $this->_input[ $id ] ;
		}

		// No cache cookie settings
		$id = LiteSpeed_Cache_Config::ID_NOCACHE_COOKIES ;
		$this->_input[ $id ] = preg_replace( "/[\r\n]+/", '|', $this->_input[ $id ] ) ;
		if ( $this->_input[ $id ] && ! $this->_syntax_checker( $this->_input[ $id ] ) ) {
			$this->_err[] = LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_SETTING_REWRITE, array( $id, esc_html( $this->_input[ $id ] ) ) ) ;
		}
		else {
			$new_options[ $id ] = $this->_input[ $id ] ;
		}

		// No cache user agent settings
		$id = LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS ;
		if ( $this->_input[ $id ] && ! $this->_syntax_checker( $this->_input[ $id ] ) ) {
			$this->_err[] = LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_SETTING_REWRITE, array( $id, esc_html( $this->_input[ $id ] ) ) ) ;
		}
		else {
			$new_options[ $id ] = $this->_input[ $id ] ;
		}

		// Login cookie
		$id = LiteSpeed_Cache_Config::OPID_LOGIN_COOKIE ;
		if ( $this->_input[ $id ] && preg_match( '#[^\w\-]#', $this->_input[ $id ] ) ) {
			$this->_err[] = LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_SETTING_LC, esc_html( $this->_input[ $id ] ) ) ;
		}
		else {
			$new_options[ $id ] = $this->_input[ $id ] ;
		}

		return $new_options ;
	}

	/**
	 * Validates the third party settings.
	 *
	 * @since 1.0.12
	 * @access private
	 */
	private function _validate_thirdparty()
	{
		$tp_default_options = LiteSpeed_Cache_Config::get_instance()->get_thirdparty_options() ;
		if ( empty( $tp_default_options ) ) {
			return ;
		}

		$tp_input = array_intersect_key( $this->_input, $tp_default_options ) ;
		if ( empty( $tp_input ) ) {
			return ;
		}

		$tp_options = apply_filters( 'litespeed_cache_save_options', array_intersect_key( $this->_options, $tp_default_options ), $tp_input ) ;
		if ( ! empty( $tp_options ) && is_array( $tp_options ) ) {
			$this->_options = array_merge( $this->_options, $tp_options ) ;
		}
	}

	/**
	 * Validates the esi settings.
	 *
	 * @since 1.1.3
	 * @access private
	 */
	private function _validate_esi()
	{
		$ids = array(
			LiteSpeed_Cache_Config::OPID_ESI_ENABLE,
			LiteSpeed_Cache_Config::OPID_ESI_CACHE_ADMBAR,
			LiteSpeed_Cache_Config::OPID_ESI_CACHE_COMMFORM,
		) ;
		foreach ( $ids as $id ) {
			$this->_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		}

		// Save vary group settings
		$vary_groups = $_POST[ LiteSpeed_Cache_Config::VARY_GROUP ] ;
		$vary_groups = array_map( 'trim', $vary_groups ) ;
		$vary_groups = array_filter( $vary_groups ) ;
		update_option( LiteSpeed_Cache_Config::VARY_GROUP, $vary_groups ) ;
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
	public static function widget_save_err( $location )
	{
		return str_replace( '?message=0', '?error=0', $location ) ;
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
	public static function validate_widget_save( $instance, $new_instance, $old_instance, $widget )
	{
		if ( empty( $_POST[ LiteSpeed_Cache_Config::OPTION_NAME ] ) ) {
			return $instance ;
		}
		$current = ! empty( $old_instance[ LiteSpeed_Cache_Config::OPTION_NAME ] ) ? $old_instance[ LiteSpeed_Cache_Config::OPTION_NAME ] : false ;
		$input = $_POST[ LiteSpeed_Cache_Config::OPTION_NAME ] ;
		$esistr = $input[ LiteSpeed_Cache_ESI::WIDGET_OPID_ESIENABLE ] ;
		$ttlstr = $input[ LiteSpeed_Cache_ESI::WIDGET_OPID_TTL ] ;

		if ( ! is_numeric( $ttlstr ) || ! is_numeric( $esistr ) ) {
			add_filter( 'wp_redirect', 'LiteSpeed_Cache_Admin_Settings::widget_save_err' ) ;
			return false ;
		}

		$esi = self::is_checked_radio( $esistr ) ;
		$ttl = intval( $ttlstr ) ;

		if ( $ttl != 0 && $ttl < 30 ) {
			add_filter( 'wp_redirect', 'LiteSpeed_Cache_Admin_Settings::widget_save_err' ) ;
			return false ; // invalid ttl.
		}

		if ( empty( $instance[ LiteSpeed_Cache_Config::OPTION_NAME ] ) ) {
			$instance[ LiteSpeed_Cache_Config::OPTION_NAME ] = array() ;
		}
		$instance[ LiteSpeed_Cache_Config::OPTION_NAME ][ LiteSpeed_Cache_ESI::WIDGET_OPID_ESIENABLE ] = $esi ;
		$instance[ LiteSpeed_Cache_Config::OPTION_NAME ][ LiteSpeed_Cache_ESI::WIDGET_OPID_TTL ] = $ttl ;

		if ( ! $current || $esi != $current[ LiteSpeed_Cache_ESI::WIDGET_OPID_ESIENABLE ] ) {
			LiteSpeed_Cache_Purge::purge_all() ;
		}
		elseif ( $ttl != 0 && $ttl != $current[ LiteSpeed_Cache_ESI::WIDGET_OPID_TTL ] ) {
			LiteSpeed_Cache_Purge::add( LiteSpeed_Cache_Tag::TYPE_WIDGET . $widget->id ) ;
		}

		LiteSpeed_Cache_Purge::purge_all() ;
		return $instance ;
	}

	/**
	 * Parse rewrite input to check for possible issues (e.g. unescaped spaces).
	 *
	 * Issues tracked:
	 * Starts with |
	 * Ends with |
	 * Double |
	 * Unescaped space
	 * Invalid character (NOT \w, -, \, |, \s, /, ., +, *, (, ))
	 *
	 * @since 1.0.9
	 * @access private
	 * @param String $rule Input rewrite rule.
	 * @return bool True for valid rules, false otherwise.
	 */
	private function _syntax_checker( $rule )
	{
		$escaped = str_replace( '@', '\@', $rule ) ;

		$success = true ;

		set_error_handler( 'litespeed_exception_handler' ) ;

		try {
			preg_match( '@' . $escaped . '@', null ) ;
		} catch ( ErrorException $e ) {
			$success = false ;
		}

		restore_error_handler() ;

		return $success ;
	}

	/**
	 * Helper function to validate TTL settings. Will check if it's set, is an integer, and is greater than 0 and less than INT_MAX.
	 *
	 * @since 1.0.12
	 * @access private
	 * @param array $input Input array
	 * @param string $id Option ID
	 * @param number $min Minimum number
	 * @param number $max Maximum number
	 * @return bool True if valid, false otherwise.
	 */
	private function _check_ttl( $input, $id, $min = false, $max = null )
	{
		if ( ! isset( $input[ $id ] ) ) {
			return false ;
		}

		$val = $input[ $id ] ;

		$ival = intval( $val ) ;
		$sval = strval( $val ) ;

		if( $min && $ival < $min ) {
			return false ;
		}

		if ( $max === null ) {
			$max = $this->_max_int ;
		}

		return ctype_digit( $sval ) && $ival >= 0 && $ival <= $max ;
	}

	/**
	 * Filter the value for checkbox via input and id (enabled/disabled)
	 *
	 * @since  1.1.6
	 * @access public
	 * @param int $input The whole input array
	 * @param string $id The ID of the option
	 * @return bool Filtered value
	 */
	public static function parse_onoff( $input, $id )
	{
		return isset( $input[ $id ] ) && self::is_checked( $input[ $id ] ) ;
	}

	/**
	 * Filter the value for checkbox (enabled/disabled)
	 *
	 * @since  1.1.0
	 * @access public
	 * @param int $val The checkbox value
	 * @return bool Filtered value
	 */
	public static function is_checked( $val )
	{
		$val = intval( $val ) ;

		if( $val === LiteSpeed_Cache_Config::VAL_ON ) {
			return true ;
		}

		return false ;
	}

	/**
	 * Filter the value for radio (enabled/disabled/notset)
	 *
	 * @since  1.1.0
	 * @access public
	 * @param int $val The radio value
	 * @return int Filtered value
	 */
	public static function is_checked_radio( $val )
	{
		$val = intval( $val ) ;

		if( $val === LiteSpeed_Cache_Config::VAL_ON ) {
			return LiteSpeed_Cache_Config::VAL_ON ;
		}

		if( $val === LiteSpeed_Cache_Config::VAL_ON2 ) {
			return LiteSpeed_Cache_Config::VAL_ON2 ;
		}

		return LiteSpeed_Cache_Config::VAL_OFF ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.1.0
	 * @access public
	 * @return Current class instance.
	 */
	public static function get_instance()
	{
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}
}
