<?php
defined( 'WPINC' ) || exit ;
/**
 * The admin settings handler of the plugin.
 *
 *
 * @since      1.1.0
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/admin
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */

class LiteSpeed_Cache_Admin_Settings extends LiteSpeed_Cache_Const
{
	private static $_instance ;

	const ENROLL = '_settings-enroll' ;

	private $__cfg ;// cfg instance

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
	 * Save settings
	 *
	 * Both $_POST and CLI can use this way
	 *
	 * Import will directly call conf.cls
	 *
	 * @since  3.0
	 * @access public
	 */
	public function save( $raw_data )
	{
		LiteSpeed_Cache_Log::debug( '[Settings] saving' ) ;

		if ( empty( $raw_data[ self::ENROLL ] ) ) {
			exit( 'No fields' ) ;
		}

		// LiteSpeed_Cache_Admin::cleanup_text( $input ) ; todo: check if need to call this

		// Sanitize the fields to save
		$_fields = array() ;
		foreach ( $raw_data[ self::ENROLL ] as $v ) {
			// Drop array format
			if ( strpos( $v, '[' ) !== false ) {

				if ( strpos( $v, self::O_CDN_MAPPING ) === 0 ) { // Separate handler for CDN child settings
					// todo: Need to be compatible with xx[0] way from CLI
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
			if ( $v && ! in_array( $v, $_fields ) ) { // Not allow to set core version
				if ( array_key_exists( $v, $this->_default_options ) || strpos( $v, self::O_CDN_MAPPING ) === 0 || strpos( $v, self::O_CRWL_COOKIES ) === 0 ) {
					$_fields[] = $v ;
				}
			}
		}

		// Convert data to config format
		$the_matrix = array() ;
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
				if ( ! empty( $raw_data[ $id ][ $child ] ) ) {
					$data = $raw_data[ $id ][ $child ] ; // []=xxx
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
				if ( ! empty( $raw_data[ $id ][ $child ] ) ) {
					$data = $raw_data[ $id ][ $child ] ; // []=xxx
				}
			}
			elseif ( ! empty( $raw_data[ $id ] ) ) {
				$data = $raw_data[ $id ] ;
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
					$data2 = $this->__cfg->option( $id ) ;

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
					$data2 = $this->__cfg->option( $id ) ;

					foreach ( $data as $k => $v ) {
						if ( $child == self::CRWL_COOKIE_VALS ) {
							$v = LiteSpeed_Cache_Utility::sanitize_lines( $v ) ;
						}
						$data2[ $k ][ $child ] = $v ;
					}
					$data = $data2 ;
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

			$the_matrix[ $id ] = $data ;

		}

		// id validation will be inside
		$this->__cfg->update_confs( $the_matrix ) ;

	}

	/**
	 * Parses any changes made by the network admin on the network settings.
	 *
	 * @since 3.0
	 * @access public
	 */
	public function network_save( $raw_data )
	{
		LiteSpeed_Cache_Log::debug( '[Settings] network saving' ) ;

		if ( empty( $raw_data[ self::ENROLL ] ) ) {
			exit( 'No fields' ) ;
		}

		// LiteSpeed_Cache_Admin::cleanup_text( $input ) ; todo: check if need to call this

		// Sanitize the fields to save
		$_fields = array() ;
		foreach ( $raw_data[ self::ENROLL ] as $v ) {
			// Append current field to setting save
			if ( $v && ! in_array( $v, $_fields ) && $v != self::_VERSION ) { // Not allow to set core version
				if ( array_key_exists( $v, $this->_default_site_options ) ) {
					$_fields[] = $v ;
				}
			}
		}

		foreach ( $_fields as $id ) {
			$data = '' ;

			if ( ! empty( $raw_data[ $id ] ) ) {
				$data = $raw_data[ $id ] ;
			}

			// id validation will be inside
			$this->__cfg->network_update( $id, $data ) ;
		}

		// Update related files
		LiteSpeed_Cache_Activation::get_instance()->update_files() ;
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
