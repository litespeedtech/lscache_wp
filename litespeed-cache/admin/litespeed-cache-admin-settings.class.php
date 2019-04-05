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

if ( ! defined( 'WPINC' ) ) {
	die ;
}

class LiteSpeed_Cache_Admin_Settings
{
	private static $_instance ;

	private $_input ;
	private $_options ;
	private $_err = array() ;

	private $__cfg ;
	private $__purge ;

	private $_max_int = 2147483647 ;

	/**
	 * Init
	 *
	 * @since  1.3
	 * @access private
	 */
	private function __construct()
	{
		$this->__cfg = LiteSpeed_Cache_Config::get_instance() ;
		$this->__purge = LiteSpeed_Cache_Purge::get_instance() ;
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
	public function validate_plugin_settings( $input, $revert_options_to_input = false )
	{
		// Revert options to initial input
		if ( $revert_options_to_input ) {
			$input = LiteSpeed_Cache_Config::convert_options_to_input( $input ) ;
		}

		LiteSpeed_Cache_Log::debug( '[Settings] validate_plugin_settings called' ) ;
		$this->_options = $this->__cfg->get_options() ;

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
			$orig_esi_enabled = $this->_options[ LiteSpeed_Cache_Config::O_ESI ] ;

			$this->_validate_esi() ;

			$new_esi_enabled = $this->_options[ LiteSpeed_Cache_Config::O_ESI ] ;

			if ( $orig_esi_enabled !== $new_esi_enabled ) {
				LiteSpeed_Cache_Purge::purge_all( 'ESI changed' ) ;
			}
		}

		if ( ! empty( $this->_err ) ) {
			add_settings_error( LiteSpeed_Cache_Config::OPTION_NAME, LiteSpeed_Cache_Config::OPTION_NAME, implode( '<br />', $this->_err ) ) ;

			return $this->_options ;
		}

		if ( defined( 'LITESPEED_CLI' ) ) {
			$id = LiteSpeed_Cache_Config::O_CRWL ;
			$cron_val = $this->_options[ $id ] ;
			// assign crawler_cron_active to $this->_options if exists in $this->_input separately for CLI
			// This has to be specified cos crawler cron activation is not set in admin setting page
			$this->_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
			if ( $cron_val != $this->_options[ $id ] ) {
				// check if need to enable crawler cron
				LiteSpeed_Cache_Task::update( $this->_options ) ;
			}
		}

		$this->_validate_thirdparty() ;

		/**
		 * Check if need to send cfg to CDN or not
		 * @since 2.3
		 */
		$id = LiteSpeed_Cache_Config::O_CDN_QUIC ;
		if ( $this->_options[ $id ] ) {
			// Send to Quic CDN
			LiteSpeed_Cache_CDN_Quic::sync_config( $this->_options ) ;
		}

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
		/**
		 * Handle files:
		 * 		1) wp-config.php;
		 * 		2) adv-cache.php;
		 * 		3) object-cache.php;
		 * 		4) .htaccess;
		 */

		/* 1) wp-config.php; */
		$id = LiteSpeed_Cache_Config::O_CACHE ;
		if ( $this->_options[ $id ] ) {// todo: If not enabled, may need to remove cache var?
			$ret = LiteSpeed_Cache_Config::wp_cache_var_setter( true ) ;
			if ( $ret !== true ) {
				$this->_err[] = $ret ;
			}
		}

		/* 2) adv-cache.php; */

		$id = LiteSpeed_Cache_Config::O_UTIL_CHECK_ADVCACHE ;
		$this->_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		if ( $this->_options[ $id ] ) {
			LiteSpeed_Cache_Activation::try_copy_advanced_cache() ;
		}

		/* 3) object-cache.php; */

		/**
		 * Validate Object Cache
		 * @since 1.8
		 */
		$new_options = $this->_validate_object_cache() ;
		$this->_options = array_merge( $this->_options, $new_options ) ;

		/* 4) .htaccess; */

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
		 * Keep self up-to-date
		 * @since  2.7.2
		 */
		$id = LiteSpeed_Cache_Config::O_AUTO_UPGRADE ;
		$this->_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;

	}

	/**
	 * Parses any changes made by the network admin on the network settings.
	 *
	 * @since 1.0.4
	 * @access public
	 */
	public function validate_network_settings( $input, $revert_options_to_input = false )
	{
		// Revert options to initial input
		if ( $revert_options_to_input ) {
			$input = LiteSpeed_Cache_Config::convert_options_to_input( $input ) ;
		}

		$this->_input = LiteSpeed_Cache_Admin::cleanup_text( $input ) ;

		$options = $this->__cfg->get_site_options() ;


		/**
		 * Handle files:
		 * 		1) wp-config.php;
		 * 		2) adv-cache.php;
		 * 		3) object-cache.php;
		 * 		4) .htaccess;
		 */

		/* 1) wp-config.php; */

		$id = LiteSpeed_Cache_Config::NETWORK_O_ENABLED ;
		$network_enabled = self::parse_onoff( $this->_input, $id ) ;
		if ( $network_enabled ) {
			$ret = LiteSpeed_Cache_Config::wp_cache_var_setter( true ) ;
			if ( $ret !== true ) {
				$this->_err[] = $ret ;
			}
		}
		elseif ( $options[ $id ] != $network_enabled ) {
			LiteSpeed_Cache_Purge::purge_all( 'Network enable changed' ) ;
		}

		$options[ $id ] = $network_enabled ;

		/* 2) adv-cache.php; */

		$id = LiteSpeed_Cache_Config::O_UTIL_CHECK_ADVCACHE ;
		$options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		if ( $options[ $id ] ) {
			LiteSpeed_Cache_Activation::try_copy_advanced_cache() ;
		}

		/* 3) object-cache.php; */

		/**
		 * Validate Object Cache
		 * @since 1.8
		 */
		$new_options = $this->_validate_object_cache() ;
		$options = array_merge( $options, $new_options ) ;

		/* 4) .htaccess; */

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
		// NOTE: Network admin still need to make a lscache wrapper to avoid subblogs cache not work
		$res = LiteSpeed_Cache_Admin_Rules::get_instance()->update( $options, $disable_lscache_detail_rules ) ;
		if ( $res !== true ) {
			if ( ! is_array( $res ) ) {
				$this->_err[] = $res ;
			}
			else {
				$this->_err = array_merge( $this->_err, $res ) ;
			}
		}

		$id = LiteSpeed_Cache_Config::NETWORK_O_USE_PRIMARY ;
		$orig_primary = $options[ $id ] ;
		$options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		if ( $orig_primary != $options[ $id ] ) {
			LiteSpeed_Cache_Purge::purge_all( 'Network use_primary changed' ) ;
		}

		$id = LiteSpeed_Cache_Config::O_PURGE_ON_UPGRADE ;
		$options[ $id ] = self::parse_onoff( $this->_input, $id ) ;

		$id = LiteSpeed_Cache_Config::O_AUTO_UPGRADE ;
		$options[ $id ] = self::parse_onoff( $this->_input, $id ) ;

		if ( ! empty( $this->_err ) ) {
			LiteSpeed_Cache_Admin_Display::add_notice( LiteSpeed_Cache_Admin_Display::NOTICE_RED, $this->_err ) ;
			return ;
		}

		LiteSpeed_Cache_Admin_Display::add_notice( LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, __( 'Site options saved.', 'litespeed-cache' ) ) ;
		update_site_option( LiteSpeed_Cache_Config::OPTION_NAME, $options ) ;
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
			LiteSpeed_Cache_Config::O_OBJECT,
			LiteSpeed_Cache_Config::O_OBJECT_KIND,
			LiteSpeed_Cache_Config::O_OBJECT_ADMIN,
			LiteSpeed_Cache_Config::O_OBJECT_TRANSIENTS,
			LiteSpeed_Cache_Config::O_OBJECT_PERSISTENT,
		) ;
		foreach ( $ids as $id ) {
			$new_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		}

		$ids = array(
			LiteSpeed_Cache_Config::O_OBJECT_HOST,
			LiteSpeed_Cache_Config::O_OBJECT_PORT,
			LiteSpeed_Cache_Config::O_OBJECT_LIFE,
			LiteSpeed_Cache_Config::O_OBJECT_DB_ID,
			LiteSpeed_Cache_Config::O_OBJECT_USER,
			LiteSpeed_Cache_Config::O_OBJECT_PSWD,
		);
		foreach ( $ids as $id ) {
			$new_options[ $id ] = $this->_input[ $id ] ;
		}

		$ids = array(
			LiteSpeed_Cache_Config::O_OBJECT_GLOBAL_GROUPS,
			LiteSpeed_Cache_Config::O_OBJECT_NON_PERSISTENT_GROUPS,
		);
		$item_options = array() ;
		foreach ( $ids as $id ) {
			$item_options[ $id ] = LiteSpeed_Cache_Utility::sanitize_lines( $id ) ; xx// here needs to save separate settings OR if site_conf use separate items
		}

		/**
		 * Check if object cache file existing or not
		 */
		if ( ! defined( 'LITESPEED_DISABLE_OBJECT' ) ) {
			$id = LiteSpeed_Cache_Config::O_OBJECT ;
			if ( $new_options[ $id ] ) {
				$all_options = array_merge( $new_options, $item_options ) ;
				LiteSpeed_Cache_Log::debug( '[Settings] Update .object_cache.ini and flush object cache' ) ;
				LiteSpeed_Cache_Object::get_instance()->update_file( $all_options ) ;
				/**
				 * Clear object cache
				 */
				LiteSpeed_Cache_Object::get_instance()->reconnect( $all_options ) ;
			}
			else {
				if ( defined( 'LSCWP_OBJECT_CACHE' ) ) {
					LiteSpeed_Cache_Log::debug( '[Settings] Remove .object_cache.ini' ) ;
					LiteSpeed_Cache_Object::get_instance()->del_file() ;
				}
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
		$id = LiteSpeed_Cache_Config::O_CACHE ;
		$this->_options[ $id ] = self::is_checked_radio( $this->_input[ $id ] ) ;

		$enabled = $this->_options[ $id ] ;
		// Use network setting
		if( $enabled === LiteSpeed_Cache_Config::VAL_ON2 ) {
			$enabled = is_multisite() ? defined( 'LITESPEED_NETWORK_ON' ) : true ; // Default to true
		}

		// Purge when disabled
		if ( ! $enabled ) {
			LiteSpeed_Cache_Purge::purge_all( 'Not enabled' ) ;
			! defined( 'LITESPEED_NEW_OFF' ) && define( 'LITESPEED_NEW_OFF', true ) ; // Latest status is off
		}

		// TTL check
		$ids = array(
			LiteSpeed_Cache_Config::O_CACHE_TTL_PUB 		=> array( 30, 	null ),
			LiteSpeed_Cache_Config::O_CACHE_TTL_PRIV	 	=> array( 60, 	3600 ),
			LiteSpeed_Cache_Config::O_CACHE_TTL_FRONTPAGE 	=> array( 30, 	null ),
			LiteSpeed_Cache_Config::O_CACHE_TTL_FEED	 	=> array( 0, 	null, 30 ),
			LiteSpeed_Cache_Config::O_CACHE_TTL_403		 	=> array( 0, 	null, 30 ),xx
			LiteSpeed_Cache_Config::O_CACHE_TTL_404		 	=> array( 0, 	null, 30 ),xx
			LiteSpeed_Cache_Config::O_CACHE_TTL_500		 	=> array( 0, 	null, 30 ),xx
		) ;
		foreach ( $ids as $id => $v ) {
			list( $min, $max ) = $v ;

			$this->_options[ $id ] = $this->_check_ttl( $this->_input, $id, $min, $max ) ;

			if ( ! empty( $v[ 2 ] ) && $this->_options[ $id ] < $v[ 2 ] ) {
				$this->_options[ $id ] = 0 ;
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
			LiteSpeed_Cache_Config::O_CACHE_PRIV,
			LiteSpeed_Cache_Config::O_CACHE_COMMENTER,
			LiteSpeed_Cache_Config::O_CACHE_REST,
		);
		foreach ( $ids as $id ) {
			$this->_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		}

		$id = LiteSpeed_Cache_Config::O_CACHE_PAGE_LOGIN ;
		$this->_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		if( ! $this->_options[ $id ] ) {
			LiteSpeed_Cache_Purge::add( LiteSpeed_Cache_Tag::TYPE_LOGIN ) ;
		}

		$id = LiteSpeed_Cache_Config::O_CACHE_PRIV_URI ;
		$this->_sanitize_lines( $id, 'relative', true ) ;

		$id = LiteSpeed_Cache_Config::O_CACHE_DROP_QS ;
		$this->_sanitize_lines( $id ) ;

	}

	/**
	 * Validates the purge settings.
	 *
	 * @since 1.0.12
	 * @access private
	 */
	private function _validate_purge()
	{
		$id = LiteSpeed_Cache_Config::O_PURGE_ON_UPGRADE ;
		$this->_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;

		// get auto purge rules options
		$ids = array(
			LiteSpeed_Cache_Config::O_PURGE_POST_ALL,
			LiteSpeed_Cache_Config::O_PURGE_POST_FRONTPAGE,
			LiteSpeed_Cache_Config::O_PURGE_POST_HOMEPAGE,
			LiteSpeed_Cache_Config::O_PURGE_POST_PAGES,
			LiteSpeed_Cache_Config::O_PURGE_POST_PAGES_WITH_RECENT_POSTS,
			LiteSpeed_Cache_Config::O_PURGE_POST_AUTHOR,
			LiteSpeed_Cache_Config::O_PURGE_POST_YEAR,
			LiteSpeed_Cache_Config::O_PURGE_POST_MONTH,
			LiteSpeed_Cache_Config::O_PURGE_POST_DATE,
			LiteSpeed_Cache_Config::O_PURGE_POST_TERM,
			LiteSpeed_Cache_Config::O_PURGE_POST_POSTTYPE,
		) ;
		foreach ( $ids as $id ) {
			$this->_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		}

		// Filter scheduled purge URLs
		$id = LiteSpeed_Cache_Config::O_PURGE_TIMED_URLS ;
		$this->_sanitize_lines( $id, 'relative', true ) ;

		// Schduled Purge Time
		$id = LiteSpeed_Cache_Config::O_PURGE_TIMED_URLS_TIME ;
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
		$id = LiteSpeed_Cache_Config::O_CACHE_FORCE_URI ;
		$this->_sanitize_lines( $id, 'relative', true ) ;

		$id = LiteSpeed_Cache_Config::O_CACHE_EXC ;
		$this->_sanitize_lines( $id, 'relative', true ) ;

		$id = LiteSpeed_Cache_Config::O_CACHE_EXC_QS ;
		$this->_sanitize_lines( $id ) ;

		$id = LiteSpeed_Cache_Config::O_CACHE_EXC_CAT ;
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

		$id = LiteSpeed_Cache_Config::O_CACHE_EXC_TAG ;
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
		$id = LiteSpeed_Cache_Config::O_CACHE_EXC_ROLES ;
		$this->_options[ $id ] = $this->_input[ $id ] ;
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
			LiteSpeed_Cache_Config::O_CDN,
			LiteSpeed_Cache_Config::O_CDN_QUIC,
			LiteSpeed_Cache_Config::O_CDN_CLOUDFLARE,
		) ;
		foreach ( $ids as $id ) {
			$v = self::parse_onoff( $this->_input, $id ) ;
			if ( $this->_options[ $id ] === $v ) {
				continue ;
			}

			$this->_options[ $id ] = $v ;

			// Cloudflare setting is changed
			if ( $id == LiteSpeed_Cache_Config::O_CDN_CLOUDFLARE ) {
				$cdn_cloudflare_changed = true ;
			}

		}

		$id = LiteSpeed_Cache_Config::O_CDN_ORI ;
		$this->_options[ $id ] = $this->_input[ $id ] ;
		if ( $this->_options[ $id ] ) {
			$ori_list = explode( "\n", $this->_options[ $id ] ) ;
			foreach ( $ori_list as $k => $v ) {
				$tmp = parse_url( trim( $v ) ) ;
				if ( ! empty( $tmp[ 'scheme' ] ) ) {
					$v = str_replace( $tmp[ 'scheme' ] . ':', '', $v ) ;
				}
				$ori_list[ $k ] = trim( $v ) ;
			}
			$this->_options[ $id ] = $ori_list ;
		}

		$id = LiteSpeed_Cache_Config::O_CDN_EXC ;
		$this->_sanitize_lines( $id ) ;

		$id = LiteSpeed_Cache_Config::O_CDN_ORI_DIR ;
		$this->_sanitize_lines( $id ) ;

		/**
		 * Handle multiple CDN setting
		 * @since 1.7
		 */
		$cdn_mapping = array() ;
		$mapping_fields = array(
			LiteSpeed_Cache_Config::CDN_MAPPING_URL,
			LiteSpeed_Cache_Config::CDN_MAPPING_INC_IMG,
			LiteSpeed_Cache_Config::CDN_MAPPING_INC_CSS,
			LiteSpeed_Cache_Config::CDN_MAPPING_INC_JS,
			LiteSpeed_Cache_Config::CDN_MAPPING_FILETYPE
		) ;
		$id = LiteSpeed_Cache_Config::O_CDN_MAPPING ;
		foreach ( $this->_input[ $id ][ LiteSpeed_Cache_Config::CDN_MAPPING_URL ] as $k => $v ) {
			$this_mapping = array() ;
			foreach ( $mapping_fields as $f ) {
				$this_mapping[ $f ] = ! empty( $this->_input[ $id ][ $f ][ $k ] ) ? $this->_input[ $id ][ $f ][ $k ] : false ;
				if ( $f === LiteSpeed_Cache_Config::CDN_MAPPING_FILETYPE ) {
					$this_mapping[ $f ] = LiteSpeed_Cache_Utility::sanitize_lines( $this_mapping[ $f ] ) ;
				}
			}

			$cdn_mapping[] = $this_mapping ;
		}
		$this->_options[ $id ] = $cdn_mapping ;

		/**
		 * Load jQuery from cdn
		 * @since 1.5
		 */
		$id = LiteSpeed_Cache_Config::O_CDN_REMOTE_JQ ;
		$this->_options[ $id ] = self::is_checked_radio( $this->_input[ $id ] ) ;

		/**
		 * Quic API
		 * @since  2.4.1
		 */
		$ids = array(
			LiteSpeed_Cache_Config::O_CDN_QUIC_EMAIL,
			LiteSpeed_Cache_Config::O_CDN_QUIC_KEY,
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
			LiteSpeed_Cache_Config::O_CDN_CLOUDFLARE_EMAIL,
			LiteSpeed_Cache_Config::O_CDN_CLOUDFLARE_KEY,
			LiteSpeed_Cache_Config::O_CDN_CLOUDFLARE_NAME,
		) ;
		foreach ( $ids as $id ) {
			if ( $this->_options[ $id ] === $this->_input[ $id ] ) {
				continue ;
			}
			$cdn_cloudflare_changed = true ;
			$this->_options[ $id ] = $this->_input[ $id ] ;
		}

		// If cloudflare API is on, refresh the zone
		if ( $this->_options[ LiteSpeed_Cache_Config::O_CDN_CLOUDFLARE ] && $cdn_cloudflare_changed ) {
			$zone = LiteSpeed_Cache_CDN_Cloudflare::get_instance()->fetch_zone( $this->_options ) ;
			if ( $zone ) {
				$this->_options[ LiteSpeed_Cache_Config::O_CDN_CLOUDFLARE_NAME ] = $zone[ 'name' ] ;
				$this->_options[ LiteSpeed_Cache_Config::O_CDN_CLOUDFLARE_ZONE ] = $zone[ 'id' ] ;

				LiteSpeed_Cache_Log::debug( "Settings: Get zone successfully \t\t[ID] $zone[id]" ) ;
			}
			else {
				$this->_options[ LiteSpeed_Cache_Config::O_CDN_CLOUDFLARE_ZONE ] = '' ;
				LiteSpeed_Cache_Log::debug( '[Settings] Get zone failed, clean zone' ) ;
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
			LiteSpeed_Cache_Config::O_MEDIA_LAZY,
			LiteSpeed_Cache_Config::O_MEDIA_PLACEHOLDER_RESP,
			LiteSpeed_Cache_Config::O_MEDIA_PLACEHOLDER_RESP_ASYNC,
			LiteSpeed_Cache_Config::O_MEDIA_IFRAME_LAZY,
			LiteSpeed_Cache_Config::O_MEDIA_LAZYJS_INLINE,
			LiteSpeed_Cache_Config::O_IMG_OPTM_AUTO,
			LiteSpeed_Cache_Config::O_IMG_OPTM_CRON,
			LiteSpeed_Cache_Config::O_IMG_OPTM_ORI,
			LiteSpeed_Cache_Config::O_IMG_OPTM_RM_BKUP,
			LiteSpeed_Cache_Config::O_IMG_OPTM_WEBP,
			LiteSpeed_Cache_Config::O_IMG_OPTM_LOSSLESS,
			LiteSpeed_Cache_Config::O_IMG_OPTM_EXIF,
			LiteSpeed_Cache_Config::O_IMG_OPTM_WEBP_REPLACE_SRCSET,
		) ;
		foreach ( $ids as $id ) {
			$this->_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		}

		$ids = array(
			LiteSpeed_Cache_Config::O_MEDIA_LAZY_PLACEHOLDER,
			LiteSpeed_Cache_Config::O_MEDIA_PLACEHOLDER_RESP_COLOR,
		) ;
		foreach ( $ids as $id ) {
			$this->_options[ $id ] = $this->_input[ $id ] ;
		}

		// Update lazyload image excludes
		$id = LiteSpeed_Cache_Config::O_MEDIA_LAZY_EXC ;
		$this->_sanitize_lines( $id, 'uri' ) ;

		// Update lazyload image classname excludes
		$id = LiteSpeed_Cache_Config::O_MEDIA_LAZY_CLS_EXC ;
		$this->_sanitize_lines( $id ) ;

		$id = LiteSpeed_Cache_Config::O_IMG_OPTM_WEBP_ATTR ;
		$this->_sanitize_lines( $id ) ;
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
			LiteSpeed_Cache_Config::O_OPTM_CSS_MIN,
			LiteSpeed_Cache_Config::O_OPTM_CSS_INLINE_MIN,
			LiteSpeed_Cache_Config::O_OPTM_CSS_COMB,
			LiteSpeed_Cache_Config::O_OPTM_CSS_COMB_PRIO,
			LiteSpeed_Cache_Config::O_OPTM_CSS_HTTP2,
			LiteSpeed_Cache_Config::O_OPTM_JS_MIN,
			LiteSpeed_Cache_Config::O_OPTM_JS_INLINE_MIN,
			LiteSpeed_Cache_Config::O_OPTM_JS_COMB,
			LiteSpeed_Cache_Config::O_OPTM_JS_COMB_PRIO,
			LiteSpeed_Cache_Config::O_OPTM_JS_HTTP2,
			LiteSpeed_Cache_Config::O_OPTM_HTML_MIN,
			LiteSpeed_Cache_Config::O_OPTM_QS_RM,
			LiteSpeed_Cache_Config::O_OPTM_GGFONTS_RM,
			LiteSpeed_Cache_Config::O_OPTM_CSS_ASYNC,
			LiteSpeed_Cache_Config::O_OPTM_CCSS_GEN,
			LiteSpeed_Cache_Config::O_OPTM_CCSS_ASYNC,
			LiteSpeed_Cache_Config::O_OPTM_CSS_ASYNC_INLINE,
			LiteSpeed_Cache_Config::O_OPTM_JS_DEFER,
			LiteSpeed_Cache_Config::O_OPTM_EMOJI_RM,
			LiteSpeed_Cache_Config::O_OPTM_EXC_JQ,
			LiteSpeed_Cache_Config::O_OPTM_GGFONTS_ASYNC,
			LiteSpeed_Cache_Config::O_OPTM_RM_COMMENT,
		) ;
		foreach ( $ids as $id ) {
			$this->_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		}

		$ids = array(
			LiteSpeed_Cache_Config::O_OPTM_CSS_EXC,
			LiteSpeed_Cache_Config::O_OPTM_JS_EXC,
		) ;
		$this->_sanitize_lines( $ids, 'uri' ) ;

		$id = LiteSpeed_Cache_Config::O_OPTM_TTL ;
		$this->_options[ $id ] = $this->_check_ttl( $this->_input, $id, 3600 ) ;

		// Critical CSS
		$id = LiteSpeed_Cache_Config::O_OPTM_CCSS_CON ;
		$this->_options[ $id ] = $this->_input[ $id ] ;

		// Prevent URI from optimization
		$id = LiteSpeed_Cache_Config::O_OPTM_EXC ;
		$this->_sanitize_lines( $id, 'relative', true ) ;

		// Update js deferred excludes
		$id = LiteSpeed_Cache_Config::O_OPTM_JS_DEFER_EXC ;
		$this->_sanitize_lines( $id, 'uri' ) ;

		// Update Role Excludes
		$id = LiteSpeed_Cache_Config::EXC_OPTM_ROLES ;
		$this->_options[ $id ] = ! empty( $this->_input[ $id ] ) ? $this->_input[ $id ] : array() ;

		/**
		 * DNS prefetch
		 * @since 1.7.1
		 */
		$id = LiteSpeed_Cache_Config::O_OPTM_DNS_PREFETCH ;
		$this->_sanitize_lines( $id, 'domain' ) ;

		/**
		 * Combined file max size
		 * @since 1.7.1
		 */
		$id = LiteSpeed_Cache_Config::O_OPTM_MAX_SIZE ;
		$this->_options[ $id ] = $this->_input[ $id ] ;

		/**
		 * Separate CCSS File Types & URI
		 * @since 2.6.1
		 */
		$id = LiteSpeed_Cache_Config::O_OPTM_CCSS_SEP_POSTTYPE ;
		$this->_sanitize_lines( $id ) ;
		$id = LiteSpeed_Cache_Config::O_OPTM_CCSS_SEP_URI ;
		$this->_sanitize_lines( $id, 'uri' ) ;

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
			LiteSpeed_Cache_Config::O_UTIL_NO_HTTPS_VARY,
			LiteSpeed_Cache_Config::O_UTIL_INSTANT_CLICK,
		) ;
		foreach ( $ids as $id ) {
			$this->_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		}

		$ids = array(
			LiteSpeed_Cache_Config::O_PURGE_HOOK_ALL,
		) ;
		$this->_sanitize_lines( $ids ) ;

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
		// 			LiteSpeed_Cache_Log::debug( '[Settings] Updated favicon [' . $v . '] ' . $file[ 'url' ] ) ;

		// 			$new_favicons[ $v ] = $file[ 'url' ] ;

		// 		}
		// 		elseif ( isset( $file[ 'error' ] ) ) {
		// 			LiteSpeed_Cache_Log::debug( '[Settings] Failed to update favicon: [' . $v . '] ' . $file[ 'error' ] ) ;
		// 		}
		// 		else {
		// 			LiteSpeed_Cache_Log::debug( '[Settings] Failed to update favicon: Unkown err [' . $v . ']' ) ;
		// 		}
		// 	}
		// }

		// if ( $new_favicons ) {
		// 	$cfg_favicon = get_option( LiteSpeed_Cache_Config::O_FAVICON, array() ) ;
		// 	update_option( LiteSpeed_Cache_Config::O_FAVICON, array_merge( $cfg_favicon, $new_favicons ) ) ;
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
		$id = LiteSpeed_Cache_Config::O_DEBUG_IPS ;
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

		$id = LiteSpeed_Cache_Config::O_DEBUG ;
		$this->_options[ $id ] = self::is_checked_radio( $this->_input[ $id ] ) ;

		$id = LiteSpeed_Cache_Config::O_DEBUG_FILESIZE ;
		$this->_options[ $id ] = $this->_check_ttl( $this->_input, $id, 3, 3000 ) ;

		$ids = array(
			LiteSpeed_Cache_Config::O_DEBUG_DISABLE_ALL,
			LiteSpeed_Cache_Config::O_DEBUG_LEVEL,
			LiteSpeed_Cache_Config::O_UTIL_HEARTBEAT,
			LiteSpeed_Cache_Config::O_DEBUG_COOKIE,
			LiteSpeed_Cache_Config::O_DEBUG_COLLAPS_QS,
			LiteSpeed_Cache_Config::O_DEBUG_LOG_FILTERS,
		) ;
		foreach ( $ids as $id ) {
			$this->_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		}

		// Remove Object Cache
		if ( $this->_options[ LiteSpeed_Cache_Config::O_DEBUG_DISABLE_ALL ] ) {
			// Do a purge all (This is before oc file removal, can purge oc too)
			LiteSpeed_Cache_Purge::purge_all( '[Settings] Debug Disabled ALL' ) ;

			LiteSpeed_Cache_Log::debug( '[Settings] Remove .object_cache.ini due to debug_disable_all' ) ;
			LiteSpeed_Cache_Object::get_instance()->del_file() ;

			// Set a const to avoid regenerating again
			define( 'LITESPEED_DISABLE_OBJECT', true ) ;
		}

		// Filters ignored
		$ids = array(
			LiteSpeed_Cache_Config::O_DEBUG_LOG_NO_FILTERS,
			LiteSpeed_Cache_Config::O_DEBUG_LOG_NO_PART_FILTERS,
		) ;
		$this->_sanitize_lines( $ids ) ;
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
			LiteSpeed_Cache_Config::O_CRWL_POSTS,
			LiteSpeed_Cache_Config::O_CRWL_PAGES,
			LiteSpeed_Cache_Config::O_CRWL_CATS,
			LiteSpeed_Cache_Config::O_CRWL_TAGS,
		) ;
		foreach ( $ids as $id ) {
			$this->_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		}

		$id = LiteSpeed_Cache_Config::O_CRWL_EXC_CPT ;
		if ( isset( $this->_input[ $id ] ) ) {
			$arr = array_map( 'trim', explode( "\n", $this->_input[ $id ] ) ) ;
			$arr = array_filter( $arr ) ;
			$ori = array_diff( get_post_types( '', 'names' ), array( 'post', 'page' ) ) ;
			$this->_options[ $id ] = implode( "\n", array_intersect( $arr, $ori ) ) ;
		}

		$id = LiteSpeed_Cache_Config::O_CRWL_ORDER_LINKS ;
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
			LiteSpeed_Cache_Config::O_CRWL_USLEEP 		=> array( $usleep_min, $usleep_max ),
			LiteSpeed_Cache_Config::O_CRWL_RUN_DURATION 	=> array( 0,	null ),
			LiteSpeed_Cache_Config::O_CRWL_RUN_INTERVAL 	=> array( 60,	null ),
			LiteSpeed_Cache_Config::O_CRWL_CRAWL_INTERVAL => array( 0,	null ),
			LiteSpeed_Cache_Config::O_CRWL_THREADS 		=> array( 1,	16 ),
		) ;
		foreach ( $ids as $id => $v ) {
			list( $min, $max ) = $v ;

			$this->_options[ $id ] = $this->_check_ttl( $this->_input, $id, $min, $max ) ;
		}


		$id = LiteSpeed_Cache_Config::O_CRWL_LOAD_LIMIT ;
		$this->_options[ $id ] = $this->_input[ $id ] ;

		$id = LiteSpeed_Cache_Config::O_CRWL_DOMAIN_IP ;
		if ( ! empty( $this->_input[ $id ] ) && ! WP_Http::is_ip_address( $this->_input[ $id ] ) ) {
			$this->_err[] = LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_SETTING_SITE_IP, $this->_input[ $id ] ) ;
		}
		else {
			$this->_options[ $id ] = $this->_input[ $id ] ;
		}

		$id = LiteSpeed_Cache_Config::O_CRWL_CUSTOM_SITEMAP ;
		if ( ! empty( $this->_input[ $id ] ) ) {
			// Validate sitemap
			try{
				LiteSpeed_Cache_Crawler::get_instance()->parse_custom_sitemap( $this->_input[ $id ], false ) ;

				$this->_options[ $id ] = $this->_input[ $id ] ;

			} catch ( \Exception $e ) {
				$this->_err[] = LiteSpeed_Cache_Admin_Display::get_error( $e->getMessage(), $this->_input[ $id ] ) ;

				LiteSpeed_Cache_Log::debug( '[Crawler] ❌ failed to prase custom sitemap: ' . $e->getMessage() ) ;
			}

		}
		else {
			$this->_options[ $id ] = $this->_input[ $id ] ;
		}

		$id = LiteSpeed_Cache_Config::O_CRWL_ROLES ;
		$this->_sanitize_lines( $id ) ;

		/**
		 * Save cookie crawler
		 * @since 2.8
		 */
		$id = LiteSpeed_Cache_Config::O_CRWL_COOKIES ;
		$cookie_crawlers = array() ;
		if ( ! empty( $this->_input[ $id ][ 'name' ] ) ) {
			foreach ( $this->_input[ $id ][ 'name' ] as $k => $v ) {
				if ( ! $v ) {
					continue ;
				}

				$cookie_crawlers[ $v ] = $this->_input[ $id ][ 'vals' ][ $k ] ;
			}
		}
		$this->_options[ $id ] = $cookie_crawlers ;

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
			LiteSpeed_Cache_Config::O_CACHE_MOBILE,
			LiteSpeed_Cache_Config::O_CACHE_FAVICON,
			LiteSpeed_Cache_Config::O_CACHE_RES,
			LiteSpeed_Cache_Config::O_UTIL_BROWSER_CACHE,
			LiteSpeed_Cache_Config::O_IMG_OPTM_WEBP_REPLACE,
		) ;
		foreach ( $ids as $id ) {
			$new_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		}

		// TTL check
		$id = LiteSpeed_Cache_Config::O_UTIL_BROWSER_CACHE_TTL ;
		$new_options[ $id ] = $this->_check_ttl( $this->_input, $id, 30 ) ;

		// check mobile agents
		$id = LiteSpeed_Cache_Config::O_CACHE_MOBILE_RULES ;
		if ( ! $this->_input[ $id ] &&  $new_options[ LiteSpeed_Cache_Config::O_CACHE_MOBILE ] ) {
			$this->_err[] = LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_SETTING_REWRITE, array( $id, 'EMPTY' ) ) ;
		}
		elseif ( $this->_input[ $id ] && ! $this->_syntax_checker( $this->_input[ $id ] ) ) {
			$this->_err[] = LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_SETTING_REWRITE, array( $id, esc_html( $this->_input[ $id ] ) ) ) ;
		}
		else {
			$new_options[ $id ] = $this->_input[ $id ] ;
		}

		// No cache cookie settings
		$id = LiteSpeed_Cache_Config::O_CACHE_EXC_COOKIES ;
		$this->_input[ $id ] = preg_replace( "/[\r\n]+/", '|', $this->_input[ $id ] ) ;
		if ( $this->_input[ $id ] && ! $this->_syntax_checker( $this->_input[ $id ] ) ) {
			$this->_err[] = LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_SETTING_REWRITE, array( $id, esc_html( $this->_input[ $id ] ) ) ) ;
		}
		else {
			$new_options[ $id ] = $this->_input[ $id ] ;
		}

		// No cache user agent settings
		$id = LiteSpeed_Cache_Config::O_CACHE_EXC_USERAGENTS ;
		if ( $this->_input[ $id ] && ! $this->_syntax_checker( $this->_input[ $id ] ) ) {
			$this->_err[] = LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_SETTING_REWRITE, array( $id, esc_html( $this->_input[ $id ] ) ) ) ;
		}
		else {
			$new_options[ $id ] = $this->_input[ $id ] ;
		}

		// Login cookie
		$id = LiteSpeed_Cache_Config::O_CACHE_LOGIN_COOKIE ;
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
		$tp_default_options = $this->__cfg->get_thirdparty_options() ;
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
			LiteSpeed_Cache_Config::O_ESI,
			LiteSpeed_Cache_Config::O_ESI_CACHE_ADMBAR,
			LiteSpeed_Cache_Config::O_ESI_CACHE_COMMFORM,
		) ;
		foreach ( $ids as $id ) {
			$this->_options[ $id ] = self::parse_onoff( $this->_input, $id ) ;
		}

		// Save vary group settings
		$id = LiteSpeed_Cache_Config::O_CACHE_VARY_GROUP ;xx check if input is correct or not
		$this->_sanitize_lines( $id ) ;
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
		$esistr = $input[ LiteSpeed_Cache_ESI::WIDGET_O_ESIENABLE ] ;
		$ttlstr = $input[ LiteSpeed_Cache_ESI::WIDGET_O_TTL ] ;

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
		$instance[ LiteSpeed_Cache_Config::OPTION_NAME ][ LiteSpeed_Cache_ESI::WIDGET_O_ESIENABLE ] = $esi ;
		$instance[ LiteSpeed_Cache_Config::OPTION_NAME ][ LiteSpeed_Cache_ESI::WIDGET_O_TTL ] = $ttl ;

		if ( ! $current || $esi != $current[ LiteSpeed_Cache_ESI::WIDGET_O_ESIENABLE ] ) {
			LiteSpeed_Cache_Purge::purge_all( 'Wdiget ESI_enable changed' ) ;
		}
		elseif ( $ttl != 0 && $ttl != $current[ LiteSpeed_Cache_ESI::WIDGET_O_TTL ] ) {
			LiteSpeed_Cache_Purge::add( LiteSpeed_Cache_Tag::TYPE_WIDGET . $widget->id ) ;
		}

		LiteSpeed_Cache_Purge::purge_all( 'Wdiget saved' ) ;
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
	 * @since 2.6.2 Automatically correct number
	 * @access private
	 * @param array $input Input array
	 * @param string $id Option ID
	 * @param number $min Minimum number
	 * @param number $max Maximum number
	 * @return bool True if valid, false otherwise.
	 */
	private function _check_ttl( $input, $id, $min = false, $max = null )
	{
		$v = isset( $input[ $id ] ) ? (int) $input[ $id ] : 0 ;

		if ( $min && $v < $min ) {
			return $min ;
		}

		if ( $v < 0 ) {
			return 0 ;
		}

		if ( $max === null ) {
			$max = $this->_max_int ;
		}

		if ( $v > $max ) {
			return $max ;
		}

		return $v ;
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
	 * Filter multiple lines with sanitizer before saving
	 *
	 * @since 3.0
	 * @access private
	 */
	private function _sanitize_lines( $id, $sanitize_filter = false, $purge_diff = false )
	{
		if ( is_array( $id ) ) {
			foreach ( $id as $v ) {
				$this->_sanitize_lines( $v, $sanitize_filter, $purge_diff ) ;
			}

			return ;
		}

		$options = LiteSpeed_Cache_Utility::sanitize_lines( $this->_input[ $id ], $sanitize_filter ) ;

		// If purge difference
		if ( $purge_diff ) {
			$diff = array_diff( $options, $this->_options[ $id ] ) ;
			$diff2 = array_diff( $this->_options[ $id ], $options ) ;
			$diff = array_merge( $diff, $diff2 ) ;
			// If has difference
			if ( $diff ) {
				foreach ( $diff as $v ) {
					$v = ltrim( $v, '^' ) ;
					$v = rtrim( $v, '$' ) ;
					$this->__purge->purgeby_url_cb( $v ) ;
				}
			}
		}

		$this->_options[ $id ] = $options ;
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
