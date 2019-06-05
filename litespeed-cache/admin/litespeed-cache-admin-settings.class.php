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
	private $_err = array() ;

	private $__cfg ;

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
	}

	/**
	 * Callback function that will validate any changes made in the settings page.
	 *
	 * NOTE: Anytime that validate_plugin_settings is called, `convert_options_to_input` must be done first if not from option page
	 *
	 * @since 1.0.0
	 * @access public
	 * @param array $input The configuration posted from Setting page.
	 * @return array The updated configuration options.
	 */
	public function validate_plugin_settings( $input, $revert_options_to_input = false )
	{
		// Revert options to initial input
		if ( $revert_options_to_input ) {
			$input = LiteSpeed_Cache_Config::convert_options_to_input( $input ) ;
		}

		LiteSpeed_Cache_Log::debug( '[Settings] validate_plugin_settings called' ) ;

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

		$this->_options = apply_filters( 'litespeed_config_save', $this->_options, $input ) ;

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

		$options = $this->__cfg->load_site_options() ;


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
		foreach ( $ids as $id ) {
			$new_options[ $id ] = LiteSpeed_Cache_Utility::sanitize_lines( $id ) ;
		}

		/**
		 * Check if object cache file existing or not
		 */
		if ( ! defined( 'LITESPEED_DISABLE_OBJECT' ) ) {
			if ( $new_options[ LiteSpeed_Cache_Config::O_OBJECT ] ) {
				LiteSpeed_Cache_Log::debug( '[Settings] Update .object_cache.ini and flush object cache' ) ;
				LiteSpeed_Cache_Object::get_instance()->update_file( $new_options ) ;
				/**
				 * Clear object cache
				 */
				LiteSpeed_Cache_Object::get_instance()->reconnect( $new_options ) ;
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
	 * Update one setting
	 *
	 * @since  3.0
	 */
	private function _update( $id_or_ids, $val = null )
	{
		// recursive process
		if ( is_array( $id_or_ids ) ) {
			array_map( array( $this, __FUNCTION__ ), $id_or_ids ) ;
			return ;
		}

		if ( $val === null ) {
			$val = $this->_input[ $id_or_ids ] ;
		}

		$this->__cfg->update( $id_or_ids, $val ) ;
	}

	/**
	 * Validates the general settings.
	 *
	 * @since 1.0.12
	 * @access private
	 */
	private function _validate_general()
	{
		$ids = array(
			LiteSpeed_Cache_Config::O_CACHE,
			// TTL check
			LiteSpeed_Cache_Config::O_CACHE_TTL_PUB,
			LiteSpeed_Cache_Config::O_CACHE_TTL_PRIV,
			LiteSpeed_Cache_Config::O_CACHE_TTL_FRONTPAGE,
			LiteSpeed_Cache_Config::O_CACHE_TTL_FEED,

			LiteSpeed_Cache_Config::O_CACHE_TTL_STATUS,
		) ;
		$this->_update( $ids ) ;

		// Cache enabled setting
		$enabled = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_CACHE ) ;
		// Use network setting
		if( $enabled === LiteSpeed_Cache_Config::VAL_ON2 ) {
			$enabled = is_multisite() ? defined( 'LITESPEED_NETWORK_ON' ) : true ; // Default to true
		}

		// Purge when disabled
		if ( ! $enabled ) {
			LiteSpeed_Cache_Purge::purge_all( 'Not enabled' ) ;
			! defined( 'LITESPEED_NEW_OFF' ) && define( 'LITESPEED_NEW_OFF', true ) ; // Latest status is off
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
			LiteSpeed_Cache_Config::O_CACHE_DROP_QS,
			LiteSpeed_Cache_Config::O_CACHE_PRIV_URI,
			LiteSpeed_Cache_Config::O_CACHE_PAGE_LOGIN,
		);
		$this->_update( $ids ) ;

		if( ! LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_CACHE_PAGE_LOGIN ) ) {
			LiteSpeed_Cache_Purge::add( LiteSpeed_Cache_Tag::TYPE_LOGIN ) ;
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
		$ids = array(
			LiteSpeed_Cache_Config::O_PURGE_ON_UPGRADE,
			// get auto purge rules options
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
			LiteSpeed_Cache_Config::O_PURGE_TIMED_URLS_TIME, // Schduled Purge Time
			LiteSpeed_Cache_Config::O_PURGE_TIMED_URLS, // `Scheduled Purge URLs`
		) ;
		$this->_update( $ids ) ;
	}

	/**
	 * Validates the exclude settings.
	 *
	 * @since 1.0.12
	 * @access private
	 */
	private function _validate_exclude()
	{
		$ids = array(
			LiteSpeed_Cache_Config::O_CACHE_FORCE_URI,
			LiteSpeed_Cache_Config::O_CACHE_EXC,
			LiteSpeed_Cache_Config::O_CACHE_EXC_QS,
			LiteSpeed_Cache_Config::O_CACHE_EXC_ROLES, // `Role Excludes` @since 1.6.2
		) ;
		$this->_update( $ids ) ;

		$id = LiteSpeed_Cache_Config::O_CACHE_EXC_CAT ;
		$excludes = array() ;
		if ( isset( $this->_input[ $id ] ) ) {
			$this->_input[ $id ] = LiteSpeed_Cache_Utility::sanitize_lines( $this->_input[ $id ] ) ;
			foreach ( $this->_input[ $id ] as $v ) {				}
				$cat_id = get_cat_ID( $v ) ;
				if ( $cat_id == 0 ) {
					$this->_err[] = LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_SETTING_CAT, $v ) ;
				}
				else {
					$excludes[] = $cat_id ;
				}
			}
		}
		$this->_update( $id, $excludes ) ;

		$id = LiteSpeed_Cache_Config::O_CACHE_EXC_TAG ;
		$excludes = array() ;
		if ( isset( $this->_input[ $id ] ) ) {
			$this->_input[ $id ] = LiteSpeed_Cache_Utility::sanitize_lines( $this->_input[ $id ] ) ;
			foreach ( $this->_input[ $id ] as $v ) {
				$term = get_term_by( 'name', $v, 'post_tag' ) ;
				if ( $term == 0 ) {
					$this->_err[] = LiteSpeed_Cache_Admin_Display::get_error( LiteSpeed_Cache_Admin_Error::E_SETTING_TAG, $v ) ;
				}
				else {
					$excludes[] = $term->term_id ;
				}
			}
		}
		$this->_update( $id, $excludes ) ;
	}

	/**
	 * Validates the CDN settings.
	 *
	 * @since 1.2.2
	 * @access private
	 */
	private function _validate_cdn()
	{
		$ids = array(
			LiteSpeed_Cache_Config::O_CDN,
			LiteSpeed_Cache_Config::O_CDN_QUIC,
			LiteSpeed_Cache_Config::O_CDN_REMOTE_JQ, // Load jQuery from CDN @since 1.5
			LiteSpeed_Cache_Config::O_CDN_EXC, // `Exclude Path`
			LiteSpeed_Cache_Config::O_CDN_ORI_DIR, // `Included Directories`
			LiteSpeed_Cache_Config::O_CDN_QUIC_EMAIL, // QUIC API @since  2.4.1
			LiteSpeed_Cache_Config::O_CDN_QUIC_KEY,
		) ;
		$this->_update( $ids ) ;

		// `Original URLs`
		$id = LiteSpeed_Cache_Config::O_CDN_ORI ;
		$this->_input[ $id ] = LiteSpeed_Cache_Utility::sanitize_lines( $this->_input[ $id ] ) ;
		// Trip scheme
		if ( $this->_input[ $id ] ) {
			foreach ( $this->_input[ $id ] as $k => $v ) {
				$tmp = parse_url( trim( $v ) ) ;
				if ( ! empty( $tmp[ 'scheme' ] ) ) {
					$v = str_replace( $tmp[ 'scheme' ] . ':', '', $v ) ;
				}
				$this->_input[ $id ][ $k ] = trim( $v ) ;
			}
		}
		$this->_update( $id ) ;

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
		$this->_update( $id, $cdn_mapping ) ;

		/**
		 * CLoudflare API
		 * @since  1.7.2
		 */
		$ids = array(
			LiteSpeed_Cache_Config::O_CDN_CLOUDFLARE,
			LiteSpeed_Cache_Config::O_CDN_CLOUDFLARE_EMAIL,
			LiteSpeed_Cache_Config::O_CDN_CLOUDFLARE_KEY,
			LiteSpeed_Cache_Config::O_CDN_CLOUDFLARE_NAME,
		) ;
		// Check if Cloudflare setting is changed or not
		$cdn_cloudflare_changed = false ;
		foreach ( $ids as $id ) {
			if ( LiteSpeed_Cache::config( $id ) == $this->_input[ $id ] ) {
				continue ;
			}
			$cdn_cloudflare_changed = true ;
			$this->_update( $id ) ;
		}

		// If cloudflare API is on, refresh the zone
		if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_CDN_CLOUDFLARE ) && $cdn_cloudflare_changed ) {
			$zone = LiteSpeed_Cache_CDN_Cloudflare::get_instance()->fetch_zone() ;
			$id = LiteSpeed_Cache_Config::O_CDN_CLOUDFLARE_ZONE ;
			if ( $zone ) {
				$this->_update( LiteSpeed_Cache_Config::O_CDN_CLOUDFLARE_NAME, $zone[ 'name' ] ) ;

				$this->_update( $id, $zone[ 'id' ] ) ;

				LiteSpeed_Cache_Log::debug( "[Settings] Get zone successfully \t\t[ID] $zone[id]" ) ;
			}
			else {
				$this->_update( $id, '' ) ;
				LiteSpeed_Cache_Log::debug( '[Settings] ❌ Get zone failed, clean zone' ) ;
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

			LiteSpeed_Cache_Config::O_MEDIA_LAZY_PLACEHOLDER,
			LiteSpeed_Cache_Config::O_MEDIA_PLACEHOLDER_RESP_COLOR,
			// Update lazyload image classname excludes
			LiteSpeed_Cache_Config::O_MEDIA_LAZY_CLS_EXC,
			LiteSpeed_Cache_Config::O_IMG_OPTM_WEBP_ATTR,
			// Update lazyload image excludes
			LiteSpeed_Cache_Config::O_MEDIA_LAZY_EXC,
		) ;
		$this->_update( $ids ) ;
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
			LiteSpeed_Cache_Config::O_OPTM_CSS_EXC,
			LiteSpeed_Cache_Config::O_OPTM_JS_EXC,
			LiteSpeed_Cache_Config::O_OPTM_TTL,
			LiteSpeed_Cache_Config::O_OPTM_CCSS_CON, // Critical CSS
			LiteSpeed_Cache_Config::O_OPTM_EXC, // Prevent URI from optimization
			LiteSpeed_Cache_Config::O_OPTM_JS_DEFER_EXC, // `JS Deferred Excludes`
			LiteSpeed_Cache_Config::O_OPTM_DNS_PREFETCH, // `DNS prefetch` @since 1.7.1
			LiteSpeed_Cache_Config::O_OPTM_MAX_SIZE, // Combined file max size @since 1.7.1
			LiteSpeed_Cache_Config::O_OPTM_CCSS_SEP_POSTTYPE, // Separate CCSS File Types & URI @since 2.6.1
			LiteSpeed_Cache_Config::O_OPTM_CCSS_SEP_URI,
			LiteSpeed_Cache_Config::O_OPTM_EXC_ROLES, // Role Excludes
		) ;
		$this->_update( $ids ) ;
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
			LiteSpeed_Cache_Config::O_PURGE_HOOK_ALL,
		) ;
		$this->_update( $ids ) ;

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
		// 	$this->__cfg->update( LiteSpeed_Cache_Config::O_FAVICON, array_merge( $cfg_favicon, $new_favicons ) ) ;
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
		$ids = array(
			LiteSpeed_Cache_Config::O_DEBUG_IPS,
			LiteSpeed_Cache_Config::O_DEBUG,
			LiteSpeed_Cache_Config::O_DEBUG_FILESIZE,
			LiteSpeed_Cache_Config::O_DEBUG_DISABLE_ALL,
			LiteSpeed_Cache_Config::O_DEBUG_LEVEL,
			LiteSpeed_Cache_Config::O_UTIL_HEARTBEAT,
			LiteSpeed_Cache_Config::O_DEBUG_COOKIE,
			LiteSpeed_Cache_Config::O_DEBUG_COLLAPS_QS,
			LiteSpeed_Cache_Config::O_DEBUG_LOG_FILTERS,
			LiteSpeed_Cache_Config::O_DEBUG_LOG_NO_FILTERS, // Filters ignored
			LiteSpeed_Cache_Config::O_DEBUG_LOG_NO_PART_FILTERS,
		) ;
		$this->_update( $ids ) ;

		// Remove Object Cache
		if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::O_DEBUG_DISABLE_ALL ) ) {
			// Do a purge all (This is before oc file removal, can purge oc too)
			LiteSpeed_Cache_Purge::purge_all( '[Settings] Debug Disabled ALL' ) ;

			LiteSpeed_Cache_Log::debug( '[Settings] Remove .object_cache.ini due to debug_disable_all' ) ;
			LiteSpeed_Cache_Object::get_instance()->del_file() ;

			// Set a const to avoid regenerating again
			define( 'LITESPEED_DISABLE_OBJECT', true ) ;
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
			LiteSpeed_Cache_Config::O_CRWL_POSTS,
			LiteSpeed_Cache_Config::O_CRWL_PAGES,
			LiteSpeed_Cache_Config::O_CRWL_CATS,
			LiteSpeed_Cache_Config::O_CRWL_TAGS,
			LiteSpeed_Cache_Config::O_CRWL_USLEEP,
			LiteSpeed_Cache_Config::O_CRWL_RUN_DURATION,
			LiteSpeed_Cache_Config::O_CRWL_RUN_INTERVAL,
			LiteSpeed_Cache_Config::O_CRWL_CRAWL_INTERVAL,
			LiteSpeed_Cache_Config::O_CRWL_THREADS,
			LiteSpeed_Cache_Config::O_CRWL_LOAD_LIMIT,
			LiteSpeed_Cache_Config::O_CRWL_DOMAIN_IP,
			LiteSpeed_Cache_Config::O_CRWL_ROLES,
			LiteSpeed_Cache_Config::O_CRWL_CUSTOM_SITEMAP,
			LiteSpeed_Cache_Config::O_CRWL_ORDER_LINKS,
		) ;
		$this->_update( $ids ) ;

		// `Sitemap Generation` -> `Exclude Custom Post Types`
		$id = LiteSpeed_Cache_Config::O_CRWL_EXC_CPT ;
		if ( isset( $this->_input[ $id ] ) ) {
			$arr = LiteSpeed_Cache_Utility::sanitize_lines( $this->_input[ $id ] ) ;
			$ori = array_diff( get_post_types( '', 'names' ), array( 'post', 'page' ) ) ;
			$this->_input[ $id ] = array_intersect( $arr, $ori ) ;
		}
		$this->_update( $id ) ;

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

				$cookie_crawlers[ $v ] = LiteSpeed_Cache_Utility::sanitize_lines( $this->_input[ $id ][ 'vals' ][ $k ] ) ;
			}
		}
		$this->_update( $id, $cookie_crawlers ) ;

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
		$this->_input[ $id ] = LiteSpeed_Cache_Utility::sanitize_lines( $this->_input[ $id ] ) ;
		$new_options[ $id ] = $this->_input[ $id ] ;

		// No cache cookie settings
		$id = LiteSpeed_Cache_Config::O_CACHE_EXC_COOKIES ;
		$this->_input[ $id ] = LiteSpeed_Cache_Utility::sanitize_lines( $this->_input[ $id ] ) ;
		$new_options[ $id ] = $this->_input[ $id ] ;

		// No cache user agent settings
		$id = LiteSpeed_Cache_Config::O_CACHE_EXC_USERAGENTS ;
		$this->_input[ $id ] = LiteSpeed_Cache_Utility::sanitize_lines( $this->_input[ $id ] ) ;
		$new_options[ $id ] = $this->_input[ $id ] ;

		// Login cookie
		$id = LiteSpeed_Cache_Config::O_CACHE_LOGIN_COOKIE ;
		$new_options[ $id ] = $this->_input[ $id ] ;

		return $new_options ;
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
		$id = LiteSpeed_Cache_Config::O_CACHE_VARY_GROUP ;
		$this->_update( $id ) ;
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
		if ( empty( $new_instance ) ) {
			return $instance ;
		}

		if ( ! isset( $new_instance[ LiteSpeed_Cache_ESI::WIDGET_O_ESIENABLE ] ) ) {
			return $instance ;
		}
		if ( ! isset( $new_instance[ LiteSpeed_Cache_ESI::WIDGET_O_TTL ] ) ) {
			return $instance ;
		}
		$esistr = $new_instance[ LiteSpeed_Cache_ESI::WIDGET_O_ESIENABLE ] ;
		$ttlstr = $new_instance[ LiteSpeed_Cache_ESI::WIDGET_O_TTL ] ;

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

		if ( empty( $instance[ LiteSpeed_Cache_Config::OPTION_NAME ] ) ) {// todo: to be removed
			$instance[ LiteSpeed_Cache_Config::OPTION_NAME ] = array() ;
		}
		$instance[ LiteSpeed_Cache_Config::OPTION_NAME ][ LiteSpeed_Cache_ESI::WIDGET_O_ESIENABLE ] = $esi ;
		$instance[ LiteSpeed_Cache_Config::OPTION_NAME ][ LiteSpeed_Cache_ESI::WIDGET_O_TTL ] = $ttl ;

		$current = ! empty( $old_instance[ LiteSpeed_Cache_Config::OPTION_NAME ] ) ? $old_instance[ LiteSpeed_Cache_Config::OPTION_NAME ] : false ;
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
