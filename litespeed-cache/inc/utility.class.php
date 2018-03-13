<?php

/**
 * The utility class.
 *
 * @since      	1.1.5
 * @since  		1.5 Moved into /inc
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Utility
{

	/**
	 * Set seconds/timestamp to readable format
	 *
	 * @since  1.6.5
	 * @access public
	 */
	public static function readable_time( $seconds_or_timestamp, $timeout = 3600, $backward = true )
	{

		if ( strlen( $seconds_or_timestamp ) == 10 ) {
			$seconds = time() - $seconds_or_timestamp ;
			if ( $seconds > $timeout ) {
				return date( 'm/d/Y H:i:s', $seconds_or_timestamp + LITESPEED_TIME_OFFSET ) ;
			}
		}
		else {
			$seconds = $seconds_or_timestamp ;
		}

		$res = '';
		if ( $seconds > 86400 ) {
			$num = floor( $seconds / 86400 ) ;
			$res .= $num . 'd' ;
			$seconds %= 86400 ;
		}

		if ( $seconds > 3600 ) {
			if ( $res ) {
				$res .= ', ' ;
			}
			$num = floor( $seconds / 3600 ) ;
			$res .= $num . 'h' ;
			$seconds %= 3600 ;
		}

		if ( $seconds > 60 ) {
			if ( $res ) {
				$res .= ', ' ;
			}
			$num = floor( $seconds / 60 ) ;
			$res .= $num . 'm' ;
			$seconds %= 60 ;
		}

		if ( $seconds > 0 ) {
			if ( $res ) {
				$res .= ' ' ;
			}
			$res .= $seconds . 's' ;
		}

		$res = $backward ? sprintf( __( ' %s ago', 'litespeed-cache' ), $res ) : $res ;

		return $res ;
	}


	/**
	 * Convert array to string
	 *
	 * @since  1.6
	 * @access public
	 * @return string
	 */
	public static function arr2str( $arr )
	{
		if ( ! is_array( $arr ) ) {
			return $arr ;
		}

		return base64_encode( serialize( $arr ) ) ;
	}

	/**
	 * Get human readable size
	 *
	 * @since  1.6
	 * @access public
	 * @return string
	 */
	public static function real_size( $filesize )
	{
		if ( $filesize >= 1073741824 ) {
			$filesize = round( $filesize / 1073741824 * 100 ) / 100 . 'G' ;
		}
		elseif ( $filesize >= 1048576 ) {
			$filesize = round( $filesize / 1048576 * 100 ) / 100 . 'M' ;
		}
		elseif ( $filesize >= 1024 ) {
			$filesize = round( $filesize / 1024 * 100 ) / 100 . 'K' ;
		}
		else {
			$filesize = $filesize . 'B' ;
		}
		return $filesize ;
	}

	/**
	 * Parse attributes from string
	 *
	 * @since  1.2.2
	 * @since  1.4 Moved from optimize to utility
	 * @access private
	 * @param  string $str
	 * @return array  All the attributes
	 */
	public static function parse_attr( $str )
	{
		$attrs = array() ;
		preg_match_all( '#([\w-]+)=["\']([^"\']*)["\']#isU', $str, $matches, PREG_SET_ORDER ) ;
		foreach ( $matches as $match ) {
			$attrs[ $match[ 1 ] ] = trim( $match[ 2 ] ) ;
		}
		return $attrs ;
	}

	/**
	 * Get url based on permalink setting
	 *
	 * @since  1.3
	 * @access public
	 * @return string
	 */
	public static function get_permalink_url( $relative_url )
	{
		return $GLOBALS[ 'wp_rewrite' ]->using_permalinks() ? home_url( $relative_url ) : home_url() . '/?' . $relative_url ;
	}

	/**
	 * Check if an array has a string
	 *
	 * Support $ exact match
	 *
	 * @since 1.3
	 * @access private
	 * @param string $needle The string to search with
	 * @param array $haystack
	 * @return bool|string False if not found, otherwise return the matched string in haystack.
	 */
	public static function str_hit_array( $needle, $haystack )
	{
		foreach( $haystack as $item ) {
			if ( substr( $item, -1 ) === '$' ) {
				// do exact match
				if ( substr( $item, 0, -1 ) === $needle ) {
					return $item ;
				}
			}
			elseif ( substr( $item, 0, 1 ) === '^' ) {
				// match beginning
				if ( substr( $item, 1 ) === substr( $needle, 0, strlen( $item ) - 1 ) ) {
					return $item ;
				}
			}
			else {
				if ( strpos( $needle, $item ) !== false ) {
					return $item ;
				}
			}
		}

		return false ;
	}

	/**
	 * Improve compatibility to PHP old versions
	 *
	 * @since  1.2.2
	 *
	 */
	public static function compatibility()
	{
		require_once LSCWP_DIR . 'lib/litespeed-php-compatibility.func.php' ;
	}

	/**
	 * Convert URI to URL
	 *
	 * @since  1.3
	 * @access public
	 * @param  string $uri `xx/xx.html` or `/subfolder/xx/xx.html`
	 * @return  string http://www.example.com/subfolder/xx/xx.html
	 */
	public static function uri2url( $uri )
	{
		if ( substr( $uri, 0, 1 ) === '/' ) {
			self::domain_const() ;
			$url = LSCWP_DOMAIN . $uri ;
		}
		else {
			$url = home_url( '/' ) . $uri ;
		}

		return $url ;
	}

	/**
	 * Convert URL to URI
	 *
	 * @since  1.2.2
	 * @since  1.6.2.1 Added 2nd param keep_qs
	 * @access public
	 */
	public static function url2uri( $url, $keep_qs = false )
	{
		$url = trim( $url ) ;
		$uri = @parse_url( $url, PHP_URL_PATH ) ;
		$qs = @parse_url( $url, PHP_URL_QUERY ) ;

		if ( ! $keep_qs || ! $qs ) {
			return $uri ;
		}

		return $uri . '?' . $qs ;
	}

	/**
	 * Make URL to be relative
	 *
	 * NOTE: for subfolder home_url, will keep subfolder part (strip nothing but scheme and host)
	 *
	 * @param  string $url
	 * @return string      Relative URL, start with /
	 */
	public static function make_relative( $url )
	{
		// replace home_url if the url is full url
		self::domain_const() ;
		if ( strpos( $url, LSCWP_DOMAIN ) === 0 ) {
			$url = substr( $url, strlen( LSCWP_DOMAIN ) ) ;
		}
		return trim( $url ) ;
	}

	/**
	 * Convert URL to domain only
	 *
	 * @since  1.7.1
	 */
	public static function parse_domain( $url )
	{
		$url = @parse_url( $url ) ;
		if ( empty( $url[ 'host' ] ) ) {
			return '' ;
		}

		if ( ! empty( $url[ 'scheme' ] ) ) {
			return $url[ 'scheme' ] . '://' . $url[ 'host' ] ;
		}

		return '//' . $url[ 'host' ] ;
	}

	/**
	 * Generate domain const
	 *
	 * This will generate http://www.example.com even there is a subfolder in home_url setting
	 *
	 * Const LSCWP_DOMAIN has NO trailing /
	 *
	 * @since  1.3
	 * @access public
	 */
	public static function domain_const()
	{
		if ( defined( 'LSCWP_DOMAIN' ) ) {
			return ;
		}

		$home_url = get_home_url( is_multisite() ? get_current_blog_id() : null ) ;

		self::compatibility() ;
		$domain = http_build_url( $home_url, array(), HTTP_URL_STRIP_ALL ) ;

		define( 'LSCWP_DOMAIN', $domain ) ;
	}

	/**
	 * Array map one textarea to sanitize the url
	 *
	 * @since  1.3
	 * @access public
	 * @param  string $content
	 * @param  bool $type String handler type
	 * @return string
	 */
	public static function sanitize_lines( $content, $type = null )
	{
		if ( ! $content ) {
			return $content ;
		}

		$arr = explode( "\n", $content ) ;
		$arr = array_map( 'trim', $arr ) ;
		if ( $type === 'uri' ) {
			$arr = array_map( 'LiteSpeed_Cache_Utility::url2uri', $arr ) ;
		}
		if ( $type === 'relative' ) {
			$arr = array_map( 'LiteSpeed_Cache_Utility::make_relative', $arr ) ;// Remove domain
		}
		if ( $type === 'domain' ) {
			$arr = array_map( 'LiteSpeed_Cache_Utility::parse_domain', $arr ) ;// Only keep domain
		}
		$arr = array_map( 'trim', $arr ) ;
		$arr = array_unique( $arr ) ;
		$arr = array_filter( $arr ) ;
		if ( $type === 'array' ) {
			return $arr ;
		}
		return implode( "\n", $arr ) ;
	}

	/**
	 * Builds an url with an action and a nonce.
	 *
	 * Assumes user capabilities are already checked.
	 *
	 * @since  1.6 Changed order of 2nd&3rd param, changed 3rd param `append_str` to 2nd `type`
	 * @access public
	 * @param string $action The LSCWP_CTRL action to do in the url.
	 * @param string $is_ajax if is AJAX call or not
	 * @param string $type The appending type to url
	 * @return string The built url.
	 */
	public static function build_url( $action, $type = false, $is_ajax = false, $page = null )
	{
		$prefix = '?' ;

		if ( ! $is_ajax ) {
			if ( $page ) {
				// If use admin url
				if ( $page === true ) {
					$page = 'admin.php' ;
				}
				else {
					if ( strpos( $page, '?' ) !== false ) {
						$prefix = '&' ;
					}
				}
				$combined = $page . $prefix . LiteSpeed_Cache::ACTION_KEY . '=' . $action ;
			}
			else {
				// Current page rebuild URL
				$params = $_GET ;

				if ( ! empty( $params ) ) {
					if ( isset( $params[ 'LSCWP_CTRL' ] ) ) {
						unset( $params[ 'LSCWP_CTRL' ] ) ;
					}
					if ( isset( $params[ '_wpnonce' ] ) ) {
						unset( $params[ '_wpnonce' ] ) ;
					}
					if ( ! empty( $params ) ) {
						$prefix .= http_build_query( $params ) . '&' ;
					}
				}
				global $pagenow ;
				$combined = $pagenow . $prefix . LiteSpeed_Cache::ACTION_KEY . '=' . $action ;
			}
		}
		else {
			$combined = 'admin-ajax.php?action=litespeed_ajax&' . LiteSpeed_Cache::ACTION_KEY . '=' . $action ;
		}

		if ( is_network_admin() ) {
			$prenonce = network_admin_url( $combined ) ;
		}
		else {
			$prenonce = admin_url( $combined ) ;
		}
		$url = wp_nonce_url( $prenonce, $action, LiteSpeed_Cache::NONCE_NAME ) ;

		if ( $type ) {
			// Remove potential param `type` from url
			$url = parse_url( htmlspecialchars_decode( $url ) ) ;
			parse_str( $url[ 'query' ], $query ) ;
			$url[ 'query' ] = http_build_query( array_merge( $query, LiteSpeed_Cache_Router::build_type( $type ) ) ) ;
			self::compatibility() ;
			$url = http_build_url( $url ) ;
			$url = htmlspecialchars( $url ) ;
		}

		return $url ;
	}

	/**
	 * Check if the host is the internal host
	 *
	 * @since  1.2.3
	 *
	 */
	public static function internal( $host )
	{
		if ( ! defined( 'LITESPEED_FRONTEND_HOST' ) ) {
			if ( defined( 'WP_HOME' ) ) {
				$home_host = WP_HOME ;// Also think of `WP_SITEURL`
			}
			else {
				$home_host = get_option( 'home' ) ;
			}
			define( 'LITESPEED_FRONTEND_HOST', parse_url( $home_host, PHP_URL_HOST ) ) ;
		}

		return $host === LITESPEED_FRONTEND_HOST ;
	}

	/**
	 * Check if an URL is a internal existing file
	 *
	 * @since  1.2.2
	 * @since  1.6.2 Moved here from optm.cls due to usage of media.cls
	 * @access public
	 * @return string|bool The real path of file OR false
	 */
	public static function is_internal_file( $url )
	{
		$url_parsed = parse_url( $url ) ;
		if ( isset( $url_parsed[ 'host' ] ) && ! self::internal( $url_parsed[ 'host' ] ) ) {
			// Check if is cdn path
			// Do this to avoid user hardcoded src in tpl
			if ( ! LiteSpeed_Cache_CDN::internal( $url_parsed[ 'host' ] ) ) {
				LiteSpeed_Cache_Log::debug2( 'Utility: external' ) ;
				return false ;
			}
		}

		if ( empty( $url_parsed[ 'path' ] ) ) {
			return false ;
		}

		// Need to replace child blog path for assets, ref: .htaccess
		if ( is_multisite() && defined( 'PATH_CURRENT_SITE' ) ) {
			$pattern = '#^' . PATH_CURRENT_SITE . '([_0-9a-zA-Z-]+/)(wp-(content|admin|includes))#U' ;
			$replacement = PATH_CURRENT_SITE . '$2' ;
			$url_parsed[ 'path' ] = preg_replace( $pattern, $replacement, $url_parsed[ 'path' ] ) ;
			// $current_blog = (int) get_current_blog_id() ;
			// $main_blog_id = (int) get_network()->site_id ;
			// if ( $current_blog === $main_blog_id ) {
			// 	define( 'LITESPEED_IS_MAIN_BLOG', true ) ;
			// }
			// else {
			// 	define( 'LITESPEED_IS_MAIN_BLOG', false ) ;
			// }
		}

		// Parse file path
		/**
		 * Trying to fix pure /.htaccess rewrite to /wordpress case
		 *
		 * Add `define( 'LITESPEED_WP_REALPATH', '/wordpress' ) ;` in wp-config.php in this case
		 *
		 * @internal #611001 - Combine & Minify not working?
		 * @since  1.6.3
		 */
		if ( substr( $url_parsed[ 'path' ], 0, 1 ) === '/' ) {
			if ( defined( 'LITESPEED_WP_REALPATH' ) ) {
				$file_path_ori = $_SERVER[ 'DOCUMENT_ROOT' ] . LITESPEED_WP_REALPATH . $url_parsed[ 'path' ] ;
			}
			else {
				$file_path_ori = $_SERVER[ 'DOCUMENT_ROOT' ] . $url_parsed[ 'path' ] ;
			}
		}
		else {
			$file_path_ori = LiteSpeed_Cache_Router::frontend_path() . '/' . $url_parsed[ 'path' ] ;
		}

		$file_path = realpath( $file_path_ori ) ;
		if ( ! is_file( $file_path ) ) {
			LiteSpeed_Cache_Log::debug2( 'Utility: file not exist: ' . $file_path_ori ) ;
			return false ;
		}

		return array( $file_path, filesize( $file_path ) ) ;
	}






}



