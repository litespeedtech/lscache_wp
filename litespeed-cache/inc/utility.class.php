<?php
/**
 * The utility class.
 *
 * @since      	1.1.5
 * @since  		1.5 Moved into /inc
 */

if ( ! defined( 'WPINC' ) ) {
	die ;
}

class LiteSpeed_Cache_Utility
{
	private static $_instance ;
	private static $_internal_domains ;

	const TYPE_SCORE_CHK = 'score_chk' ;

	/**
	 * Check if an URL or current page is REST req or not
	 *
	 * @since  2.9.3
	 * @deprecated 2.9.4 Moved to REST class
	 * @access public
	 */
	public static function is_rest( $url = false )
	{
		return false ;
	}

	/**
	 * Check page score
	 *
	 * @since  2.9
	 * @access private
	 */
	private function _score_check()
	{
		$_gui = LiteSpeed_Cache_GUI::get_instance() ;

		$_summary = $_gui->get_summary() ;

		$_summary[ 'score.last_check' ] = time() ;
		$_gui->save_summary( $_summary ) ;

		$score = LiteSpeed_Cache_Admin_API::post( LiteSpeed_Cache_Admin_API::IAPI_ACTION_PAGESCORE, false, true, true, 600 ) ;
		$_summary[ 'score.data' ] = $score ;
		$_gui->save_summary( $_summary ) ;

		LiteSpeed_Cache_Log::debug( '[Util] Saved page score ', $score ) ;

		exit() ;
	}

	/**
	 * Check latest version
	 *
	 * @since  2.9
	 * @access public
	 */
	public static function version_check( $src = false )
	{
		// Check latest stable version allowed to upgrade
		$url = 'https://wp.api.litespeedtech.com/auto_upgrade_v?v=' . LiteSpeed_Cache::PLUGIN_VERSION . '&src=' . $src ;

		if ( defined( 'LITESPEED_ERR' ) ) {
			$url .= '&err=' . base64_encode( ! is_string( LITESPEED_ERR ) ? json_encode( LITESPEED_ERR ) : LITESPEED_ERR ) ;
		}

		$response = wp_remote_get( $url, array( 'timeout' => 15 ) ) ;
		if ( ! is_array( $response ) || empty( $response[ 'body' ] ) ) {
			return false ;
		}

		return $response[ 'body' ] ;
	}

	/**
	 * Get current page type
	 *
	 * @since  2.9
	 */
	public static function page_type()
	{
		global $wp_query ;
		$page_type = 'default' ;

		if ( $wp_query->is_page ) {
			$page_type = is_front_page() ? 'front' : 'page' ;
		}
		elseif ( $wp_query->is_home ) {
			$page_type = 'home' ;
		}
		elseif ( $wp_query->is_single ) {
			// $page_type = $wp_query->is_attachment ? 'attachment' : 'single' ;
			$page_type = get_post_type() ;
		}
		elseif ( $wp_query->is_category ) {
			$page_type = 'category' ;
		}
		elseif ( $wp_query->is_tag ) {
			$page_type = 'tag' ;
		}
		elseif ( $wp_query->is_tax ) {
			$page_type = 'tax' ;
			// $page_type = get_queried_object()->taxonomy ;
		}
		elseif ( $wp_query->is_archive ) {
			if ( $wp_query->is_day ) {
				$page_type = 'day' ;
			}
			elseif ( $wp_query->is_month ) {
				$page_type = 'month' ;
			}
			elseif ( $wp_query->is_year ) {
				$page_type = 'year' ;
			}
			elseif ( $wp_query->is_author ) {
				$page_type = 'author' ;
			}
			else {
				$page_type = 'archive' ;
			}
		}
		elseif ( $wp_query->is_search ) {
			$page_type = 'search' ;
		}
		elseif ( $wp_query->is_404 ) {
			$page_type = '404' ;
		}

		return $page_type;

		// if ( is_404() ) {
		// 	$page_type = '404' ;
		// }
		// elseif ( is_singular() ) {
		// 	$page_type = get_post_type() ;
		// }
		// elseif ( is_home() && get_option( 'show_on_front' ) == 'page' ) {
		// 	$page_type = 'home' ;
		// }
		// elseif ( is_front_page() ) {
		// 	$page_type = 'front' ;
		// }
		// elseif ( is_tax() ) {
		// 	$page_type = get_queried_object()->taxonomy ;
		// }
		// elseif ( is_category() ) {
		// 	$page_type = 'category' ;
		// }
		// elseif ( is_tag() ) {
		// 	$page_type = 'tag' ;
		// }

		// return $page_type ;
	}

	/**
	 * Get ping speed
	 *
	 * @since  2.9
	 */
	public static function ping( $domain )
	{
		if ( strpos( $domain, ':' ) ) {
			$domain = parse_url( $domain, PHP_URL_HOST ) ;
		}
		$starttime	= microtime( true ) ;
		$file		= fsockopen( $domain, 80, $errno, $errstr, 10 ) ;
		$stoptime	= microtime( true ) ;
		$status		= 0 ;

		if ( ! $file ) $status = 99999 ;// Site is down
		else {
			fclose( $file ) ;
			$status = ( $stoptime - $starttime ) * 1000 ;
			$status = floor( $status ) ;
		}

		LiteSpeed_Cache_Log::debug( "[Util] ping [Domain] $domain \t[Speed] $status" ) ;

		return $status ;
	}

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

		if ( ! $res ) {
			return $backward ? __( 'just now', 'litespeed-cache' ) : __( 'right now', 'litespeed-cache' ) ;
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

		return base64_encode( json_encode( $arr ) ) ;
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
	public static function str_hit_array( $needle, $haystack, $has_ttl = false )
	{
		/**
		 * Safety check to avoid PHP warning
		 * @see  https://github.com/litespeedtech/lscache_wp/pull/131/commits/45fc03af308c7d6b5583d1664fad68f75fb6d017
		 */
		if ( ! is_array( $haystack ) ) {
			LiteSpeed_Cache_Log::debug( "[Util] ❌ bad param in str_hit_array()!" ) ;

			return false ;
		}

		$hit = false ;
		$this_ttl = 0 ;
		foreach( $haystack as $item ) {
			if ( ! $item ) {
				continue ;
			}

			if ( $has_ttl ) {
				$this_ttl = 0 ;
				$item = explode( ' ', $item ) ;
				if ( ! empty( $item[ 1 ] ) ) {
					$this_ttl = $item[ 1 ] ;
				}
				$item = $item[ 0 ] ;
			}

			if ( substr( $item, -1 ) === '$' ) {
				// do exact match
				if ( substr( $item, 0, -1 ) === $needle ) {
					$hit = $item ;
					break ;
				}
			}
			elseif ( substr( $item, 0, 1 ) === '^' ) {
				// match beginning
				if ( substr( $item, 1 ) === substr( $needle, 0, strlen( $item ) - 1 ) ) {
					$hit = $item ;
					break ;
				}
			}
			else {
				if ( strpos( $needle, $item ) !== false ) {
					$hit = $item ;
					break ;
				}
			}
		}

		if ( $hit ) {
			if ( $has_ttl ) {
				return array( $hit, $this_ttl ) ;
			}

			return $hit ;
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
	public static function sanitize_lines( $arr, $type = null )
	{
		if ( ! $arr ) {
			return $arr ;
		}

		if ( ! is_array( $arr ) ) {
			$arr = explode( "\n", $arr ) ;
		}

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
	public static function build_url( $action, $type = false, $is_ajax = false, $page = null, $append_arr = null )
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

			$built_arr = array_merge( $query, LiteSpeed_Cache_Router::build_type( $type ) ) ;
			if ( $append_arr ) {
				$built_arr = array_merge( $built_arr, $append_arr ) ;
			}
			$url[ 'query' ] = http_build_query( $built_arr ) ;
			self::compatibility() ;
			$url = http_build_url( $url ) ;
			$url = htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' ) ;
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

		if ( $host === LITESPEED_FRONTEND_HOST ) {
			return true ;
		}

		/**
		 * Filter for multiple domains
		 * @since 2.9.4
		 */
		if ( ! isset( self::$_internal_domains ) ) {
			self::$_internal_domains = apply_filters( 'litespeed_internal_domains', array() ) ;
		}

		if ( self::$_internal_domains ) {
			return in_array( $host, self::$_internal_domains ) ;
		}

		return false ;
	}

	/**
	 * Check if an URL is a internal existing file
	 *
	 * @since  1.2.2
	 * @since  1.6.2 Moved here from optm.cls due to usage of media.cls
	 * @access public
	 * @return string|bool The real path of file OR false
	 */
	public static function is_internal_file( $url, $addition_postfix = false )
	{
		$url_parsed = parse_url( $url ) ;
		if ( isset( $url_parsed[ 'host' ] ) && ! self::internal( $url_parsed[ 'host' ] ) ) {
			// Check if is cdn path
			// Do this to avoid user hardcoded src in tpl
			if ( ! LiteSpeed_Cache_CDN::internal( $url_parsed[ 'host' ] ) ) {
				LiteSpeed_Cache_Log::debug2( '[Util] external' ) ;
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

		/**
		 * Added new file postfix to be check if passed in
		 * @since 2.2.4
		 */
		if ( $addition_postfix ) {
			$file_path_ori .= '.' . $addition_postfix ;
		}

		/**
		 * Added this filter for those plugins which overwrite the filepath
		 * @see #101091 plugin `Hide My WordPress`
		 * @since 2.2.3
		 */
		$file_path_ori = apply_filters( 'litespeed_realpath', $file_path_ori ) ;

		$file_path = realpath( $file_path_ori ) ;
		if ( ! is_file( $file_path ) ) {
			LiteSpeed_Cache_Log::debug2( '[Util] file not exist: ' . $file_path_ori ) ;
			return false ;
		}

		return array( $file_path, filesize( $file_path ) ) ;
	}

	/**
	 * Replace url in srcset to new value
	 *
	 * @since  2.2.3
	 */
	public static function srcset_replace( $content, $callback )
	{
		preg_match_all( '# srcset=([\'"])(.+)\g{1}#iU', $content, $matches ) ;
		$srcset_ori = array() ;
		$srcset_final = array() ;
		foreach ( $matches[ 2 ] as $k => $urls_ori ) {

			$urls_final = explode( ',', $urls_ori ) ;

			$changed = false ;

			foreach ( $urls_final as $k2 => $url_info ) {
				list( $url, $size ) = explode( ' ', trim( $url_info ) ) ;

				if ( ! $url2 = call_user_func( $callback, $url ) ) {
					continue ;
				}

				$changed = true ;

				$urls_final[ $k2 ] = str_replace( $url, $url2, $url_info ) ;

				LiteSpeed_Cache_Log::debug2( '[Util] - srcset replaced to ' . $url2 . ' ' . $size ) ;
			}

			if ( ! $changed ) {
				continue ;
			}

			$urls_final = implode( ',', $urls_final ) ;

			$srcset_ori[] = $matches[ 0 ][ $k ] ;

			$srcset_final[] = str_replace( $urls_ori, $urls_final, $matches[ 0 ][ $k ] ) ;
		}

		if ( $srcset_ori ) {
			$content = str_replace( $srcset_ori, $srcset_final, $content ) ;
			LiteSpeed_Cache_Log::debug2( '[Util] - srcset replaced' ) ;
		}

		return $content ;

	}




	/**
	 * Handle all request actions from main cls
	 *
	 * @since  2.9
	 * @access public
	 */
	public static function handler()
	{
		$instance = self::get_instance() ;

		$type = LiteSpeed_Cache_Router::verify_type() ;

		switch ( $type ) {
			case self::TYPE_SCORE_CHK :
				$instance->_score_check() ;
				break ;

			default:
				break ;
		}

		LiteSpeed_Cache_Admin::redirect() ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 2.9
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
