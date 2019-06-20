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

class LiteSpeed_Cache_Admin_Settings extends LiteSpeed_Cache_Config
{
	private static $_instance ;

	private $_input ;
	private $_err = array() ;

	private $_max_int = 2147483647 ;

	const ENROLL = '_settings-enroll' ;

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
	 * Save settings
	 *
	 * @since  3.0
	 * @access public
	 */
	public function save()
	{
		LiteSpeed_Cache_Log::debug( '[Settings] saving' ) ;

		if ( empty( $_POST[ self::ENROLL ] ) ) {
			exit( 'No fields' ) ;
		}

		// Sanitize the fields to save
		$_fields = array() ;
		foreach ( $_POST[ self::ENROLL ] as $v ) {
			// Drop array format
			if ( strpos( $v, '[' ) !== false ) {

				if ( strpos( $v, self::O_CDN_MAPPING ) === 0 ) { // Separate handler for CDN child settings
					$v = substr( $v, 0, -2 ) ;// Drop ending []
				}
				elseif ( strpos( $v, self::O_CRWL_COOKIES ) === 0 ) { // Separate handler for Cookie Crawler settings
					$v = substr( $v, 0, -2 ) ;// Drop ending []
				}
				else {
					$v = substr( $v, 0, strpos( $v, '[' ) ) ;
				}
			}

			// Append current field to setting save
			if ( $v && ! in_array( $v, $_fields ) ) {
				if ( array_key_exists( $v, $this->_default_options ) || strpos( $v, self::O_CDN_MAPPING ) === 0 || strpos( $v, self::O_CRWL_COOKIES ) === 0 ) {
					$_fields[] = $v ;
				}
			}
		}

		// Check if Cloudflare setting is changed or not
		$cdn_cloudflare_changed = false ;

		foreach ( $_fields as $id ) {
			$data = '' ;

			/**
			 * Pass in data
			 */
			if ( strpos( $id, self::O_CDN_MAPPING ) === 0 ) { // CDN data
				/**
				 * Check if the child key is correct
				 * Raw data format:
				 * 		cdn-mapping[url][] = 'xxx'
				 * 		cdn-mapping[inc_js][] = 1
				 */
				$child = str_replace( array( self::O_CDN_MAPPING, '[', ']' ), '', $id ) ;
				if ( ! in_array( $child, array(
					self::CDN_MAPPING_URL,
					self::CDN_MAPPING_INC_IMG,
					self::CDN_MAPPING_INC_CSS,
					self::CDN_MAPPING_INC_JS,
					self::CDN_MAPPING_FILETYPE,
				) ) ) {
					continue ;
				}

				$id = self::O_CDN_MAPPING ;
				if ( ! empty( $_POST[ $id ][ $child ] ) ) {
					$data = $_POST[ $id ][ $child ] ; // []=xxx
				}
			}
			elseif ( strpos( $id, self::O_CRWL_COOKIES ) === 0 ) { // Cookie Crawler data
				/**
				 * Save cookie crawler
				 * Raw Format:
				 * 		crawler-cookies[name][] = xx
				 * 		crawler-cookies[vals][] = xx
				 *
				 * todo: need to allow null for values
				 */
				$child = str_replace( array( self::O_CRWL_COOKIES, '[', ']' ), '', $id ) ;
				if ( ! in_array( $child, array(
					self::CRWL_COOKIE_NAME,
					self::CRWL_COOKIE_VALS,
				) ) ) {
					continue ;
				}

				$id = self::O_CRWL_COOKIES ;
				if ( ! empty( $_POST[ $id ][ $child ] ) ) {
					$data = $_POST[ $id ][ $child ] ; // []=xxx
				}
			}
			elseif ( ! empty( $_POST[ $id ] ) ) {
				$data = $_POST[ $id ] ;
			}

			// Sanitize the value
			switch ( $id ) {
				// Cache exclude cat
				case self::O_CACHE_EXC_CAT :
					$data2 = array() ;
					$data = LiteSpeed_Cache_Utility::sanitize_lines( $data ) ;
					foreach ( $data as $v ) {
						$cat_id = get_cat_ID( $v ) ;
						if ( ! $cat_id ) {
							continue ;
						}

						$data2[] = $cat_id ;
					}
					$data = $data2 ;
					break ;

				// Cache exclude tag
				case self::O_CACHE_EXC_TAG :
					$data2 = array() ;
					$data = LiteSpeed_Cache_Utility::sanitize_lines( $data ) ;
					foreach ( $data as $v ) {
						$term = get_term_by( 'name', $v, 'post_tag' ) ;
						if ( ! $term ) {
							// todo: can show the error in admin error msg
							continue ;
						}

						$data2[] = $term->term_id ;
					}
					$data = $data2 ;
					break ;

				// `Original URLs`
				case self::O_CDN_ORI :
					$data = LiteSpeed_Cache_Utility::sanitize_lines( $data ) ;
					// Trip scheme
					foreach ( $data as $k => $v ) {
						$tmp = parse_url( trim( $v ) ) ;
						if ( ! empty( $tmp[ 'scheme' ] ) ) {
							$v = str_replace( $tmp[ 'scheme' ] . ':', '', $v ) ;
						}
						$data[ $k ] = trim( $v ) ;
					}
					break ;

				/**
				 * Handle multiple CDN setting
				 * Final format:
				 * 		cdn-mapping[ 0 ][ url ] = 'xxx'
				 */
				case self::O_CDN_MAPPING :
					$data2 = $this->option( $id ) ;

					foreach ( $data as $k => $v ) {
						if ( $child == self::CDN_MAPPING_FILETYPE ) {
							$v = LiteSpeed_Cache_Utility::sanitize_lines( $v ) ;
						}
						$data2[ $k ][ $child ] = $v ;
					}
					$data = $data2 ;
					break ;

				/**
				 * Handle Cookie Crawler setting
				 * Final format:
				 * 		crawler-cookie[ 0 ][ name ] = 'xxx'
				 * 		crawler-cookie[ 0 ][ vals ] = 'xxx'
				 *
				 * empty line for `vals` use literal `_null`
				 */
				case self::O_CRWL_COOKIES :
					$data2 = $this->option( $id ) ;

					foreach ( $data as $k => $v ) {
						if ( $child == self::CRWL_COOKIE_VALS ) {
							$v = LiteSpeed_Cache_Utility::sanitize_lines( $v ) ;
						}
						$data2[ $k ][ $child ] = $v ;
					}
					$data = $data2 ;
					break ;

				/**
				 * Handle Cloudflare API
				 */
				case self::O_CDN_CLOUDFLARE :
				case self::O_CDN_CLOUDFLARE_EMAIL :
				case self::O_CDN_CLOUDFLARE_KEY :
				case self::O_CDN_CLOUDFLARE_NAME :
					if ( $this->option( $id ) != $data ) {
						$cdn_cloudflare_changed = true ;
					}
					break ;

				// `Sitemap Generation` -> `Exclude Custom Post Types`
				case self::O_CRWL_EXC_CPT :
					if ( $data ) {
						$data = LiteSpeed_Cache_Utility::sanitize_lines( $data ) ;
						$ori = array_diff( get_post_types( '', 'names' ), array( 'post', 'page' ) ) ;
						$data = array_intersect( $data, $ori ) ;
					}
					break ;

				default:
					break ;
			}

			// id validation will be inside
			$this->update( $id, $data ) ;
		}

		/**
		 * CDN related actions
		 */

		/**
		 * Cloudflare
		 * If cloudflare API is on, refresh the zone
		 */
		if ( $cdn_cloudflare_changed && $this->option( self::O_CDN_CLOUDFLARE ) ) {
			$zone = LiteSpeed_Cache_CDN_Cloudflare::get_instance()->fetch_zone() ;
			if ( $zone ) {
				$this->update( self::O_CDN_CLOUDFLARE_NAME, $zone[ 'name' ] ) ;

				$this->update( self::O_CDN_CLOUDFLARE_ZONE, $zone[ 'id' ] ) ;

				LiteSpeed_Cache_Log::debug( "[Settings] Get zone successfully \t\t[ID] $zone[id]" ) ;
			}
			else {
				$this->update( self::O_CDN_CLOUDFLARE_ZONE, '' ) ;
				LiteSpeed_Cache_Log::debug( '[Settings] ❌ Get zone failed, clean zone' ) ;
			}
		}

		/**
		 * QUIC.cloud
		 * Check if need to send cfg to CDN or not
		 * @since 2.3
		 */
		if ( $this->option( self::O_CDN_QUIC ) ) {
			// Send to Quic CDN
			LiteSpeed_Cache_CDN_Quic::sync_config() ;
		}

		// Cache enabled setting
		$enabled = $this->option( self::O_CACHE ) ;
		// Use network setting
		if( $enabled === self::VAL_ON2 ) {
			$enabled = is_multisite() ? defined( 'LITESPEED_NETWORK_ON' ) : true ; // Default to true
		}
		// Purge when disabled
		if ( ! $enabled ) {
			LiteSpeed_Cache_Purge::purge_all( 'Not enabled' ) ;
			! defined( 'LITESPEED_NEW_OFF' ) && define( 'LITESPEED_NEW_OFF', true ) ; // Latest status is off
		}

		// Update related files
		LiteSpeed_Cache_Activation::get_instance()->update_files() ;


	}

	/**
	 * Parses any changes made by the network admin on the network settings.
	 *
	 * @since 1.0.4
	 * @access public
	 */
	public function validate_network_settings( $input, $revert_options_to_input = false )
	{
		$this->_input = LiteSpeed_Cache_Admin::cleanup_text( $input ) ;

		$options = $this->load_site_options() ;


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

		LiteSpeed_Cache_Admin_Display::add_notice( LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, __( 'Site options saved.', 'litespeed-cache' ) ) ;
		update_site_option( LiteSpeed_Cache_Config::OPTION_NAME, $options ) ;
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
