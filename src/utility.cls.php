<?php
/**
 * The utility class.
 *
 * @since      	1.1.5
 * @since  		1.5 Moved into /inc
 */
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

class Utility extends Root {
	private static $_internal_domains;

	/**
	 * Validate regex
	 *
	 * @since 1.0.9
	 * @since  3.0 Moved here from admin-settings.cls
	 * @access public
	 * @return bool True for valid rules, false otherwise.
	 */
	public static function syntax_checker( $rules ) {
		return preg_match( self::arr2regex( $rules ), '' ) !== false;
	}

	/**
	 * Combine regex array to regex rule
	 *
	 * @since  3.0
	 */
	public static function arr2regex( $arr, $drop_delimiter = false ) {
		$arr = self::sanitize_lines( $arr );

		$new_arr = array();
		foreach ( $arr as $v ) {
			$new_arr[] = preg_quote( $v, '#' );
		}

		$regex = implode( '|', $new_arr );
		$regex = str_replace( ' ', '\\ ', $regex );

		if ( $drop_delimiter ) {
			return $regex;
		}

		return '#' . $regex . '#';
	}

	/**
	 * Replace wildcard to regex
	 *
	 * @since  3.2.2
	 */
	public static function wildcard2regex( $string ) {
		if ( is_array( $string ) ) {
			return array_map( __CLASS__ . '::wildcard2regex', $string );
		}

		if ( strpos( $string, '*' ) !== false ) {
			$string = preg_quote( $string, '#' );
			$string = str_replace( '\*', '.*', $string );
		}

		return $string;
	}

	/**
	 * Check if an URL or current page is REST req or not
	 *
	 * @since  2.9.3
	 * @deprecated 2.9.4 Moved to REST class
	 * @access public
	 */
	public static function is_rest( $url = false ) {
		return false;
	}

	/**
	 * Get current page type
	 *
	 * @since  2.9
	 */
	public static function page_type() {
		global $wp_query;
		$page_type = 'default';

		if ( $wp_query->is_page ) {
			$page_type = is_front_page() ? 'front' : 'page';
		}
		elseif ( $wp_query->is_home ) {
			$page_type = 'home';
		}
		elseif ( $wp_query->is_single ) {
			// $page_type = $wp_query->is_attachment ? 'attachment' : 'single';
			$page_type = get_post_type();
		}
		elseif ( $wp_query->is_category ) {
			$page_type = 'category';
		}
		elseif ( $wp_query->is_tag ) {
			$page_type = 'tag';
		}
		elseif ( $wp_query->is_tax ) {
			$page_type = 'tax';
			// $page_type = get_queried_object()->taxonomy;
		}
		elseif ( $wp_query->is_archive ) {
			if ( $wp_query->is_day ) {
				$page_type = 'day';
			}
			elseif ( $wp_query->is_month ) {
				$page_type = 'month';
			}
			elseif ( $wp_query->is_year ) {
				$page_type = 'year';
			}
			elseif ( $wp_query->is_author ) {
				$page_type = 'author';
			}
			else {
				$page_type = 'archive';
			}
		}
		elseif ( $wp_query->is_search ) {
			$page_type = 'search';
		}
		elseif ( $wp_query->is_404 ) {
			$page_type = '404';
		}

		return $page_type;

		// if ( is_404() ) {
		// 	$page_type = '404';
		// }
		// elseif ( is_singular() ) {
		// 	$page_type = get_post_type();
		// }
		// elseif ( is_home() && get_option( 'show_on_front' ) == 'page' ) {
		// 	$page_type = 'home';
		// }
		// elseif ( is_front_page() ) {
		// 	$page_type = 'front';
		// }
		// elseif ( is_tax() ) {
		// 	$page_type = get_queried_object()->taxonomy;
		// }
		// elseif ( is_category() ) {
		// 	$page_type = 'category';
		// }
		// elseif ( is_tag() ) {
		// 	$page_type = 'tag';
		// }

		// return $page_type;
	}

	/**
	 * Get ping speed
	 *
	 * @since  2.9
	 */
	public static function ping( $domain ) {
		if ( strpos( $domain, ':' ) ) {
			$domain = parse_url( $domain, PHP_URL_HOST );
		}
		$starttime	= microtime( true );
		$file		= fsockopen( $domain, 443, $errno, $errstr, 10 );
		$stoptime	= microtime( true );
		$status		= 0;

		if ( ! $file ) $status = 99999;// Site is down
		else {
			fclose( $file );
			$status = ( $stoptime - $starttime ) * 1000;
			$status = floor( $status );
		}

		Debug2::debug( "[Util] ping [Domain] $domain \t[Speed] $status" );

		return $status;
	}

	/**
	 * Set seconds/timestamp to readable format
	 *
	 * @since  1.6.5
	 * @access public
	 */
	public static function readable_time( $seconds_or_timestamp, $timeout = 3600, $forword = false ) {

		if ( strlen( $seconds_or_timestamp ) == 10 ) {
			$seconds = time() - $seconds_or_timestamp;
			if ( $seconds > $timeout ) {
				return date( 'm/d/Y H:i:s', $seconds_or_timestamp + LITESPEED_TIME_OFFSET );
			}
		}
		else {
			$seconds = $seconds_or_timestamp;
		}

		$res = '';
		if ( $seconds > 86400 ) {
			$num = floor( $seconds / 86400 );
			$res .= $num . 'd';
			$seconds %= 86400;
		}

		if ( $seconds > 3600 ) {
			if ( $res ) {
				$res .= ', ';
			}
			$num = floor( $seconds / 3600 );
			$res .= $num . 'h';
			$seconds %= 3600;
		}

		if ( $seconds > 60 ) {
			if ( $res ) {
				$res .= ', ';
			}
			$num = floor( $seconds / 60 );
			$res .= $num . 'm';
			$seconds %= 60;
		}

		if ( $seconds > 0 ) {
			if ( $res ) {
				$res .= ' ';
			}
			$res .= $seconds . 's';
		}

		if ( ! $res ) {
			return $forword ? __( 'right now', 'litespeed-cache' ) : __( 'just now', 'litespeed-cache' );
		}

		$res = $forword ? $res : sprintf( __( ' %s ago', 'litespeed-cache' ), $res );

		return $res;
	}


	/**
	 * Convert array to string
	 *
	 * @since  1.6
	 * @access public
	 */
	public static function arr2str( $arr ) {
		if ( ! is_array( $arr ) ) {
			return $arr;
		}

		return base64_encode( json_encode( $arr ) );
	}

	/**
	 * Get human readable size
	 *
	 * @since  1.6
	 * @access public
	 */
	public static function real_size( $filesize, $is_1000 = false ) {
		$unit = $is_1000 ? 1000 : 1024;

		if ( $filesize >= pow( $unit, 3 ) ) {
			$filesize = round( $filesize / pow( $unit, 3 ) * 100 ) / 100 . 'G';
		}
		elseif ( $filesize >= pow( $unit, 2 ) ) {
			$filesize = round( $filesize / pow( $unit, 2 ) * 100 ) / 100 . 'M';
		}
		elseif ( $filesize >= $unit ) {
			$filesize = round( $filesize / $unit * 100 ) / 100 . 'K';
		}
		else {
			$filesize = $filesize . 'B';
		}
		return $filesize;
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
	public static function parse_attr( $str ) {
		$attrs = array();
		preg_match_all( '#([\w-]+)=(["\'])([^\2]*)\2#isU', $str, $matches, PREG_SET_ORDER );
		foreach ( $matches as $match ) {
			$attrs[ $match[ 1 ] ] = trim( $match[ 3 ] );
		}
		return $attrs;
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
	public static function str_hit_array( $needle, $haystack, $has_ttl = false ) {
		if ( ! $haystack ) {
			return false;
		}
		/**
		 * Safety check to avoid PHP warning
		 * @see  https://github.com/litespeedtech/lscache_wp/pull/131/commits/45fc03af308c7d6b5583d1664fad68f75fb6d017
		 */
		if ( ! is_array( $haystack ) ) {
			Debug2::debug( "[Util] ❌ bad param in str_hit_array()!" );

			return false;
		}

		$hit = false;
		$this_ttl = 0;
		foreach( $haystack as $item ) {
			if ( ! $item ) {
				continue;
			}

			if ( $has_ttl ) {
				$this_ttl = 0;
				$item = explode( ' ', $item );
				if ( ! empty( $item[ 1 ] ) ) {
					$this_ttl = $item[ 1 ];
				}
				$item = $item[ 0 ];
			}

			if ( substr( $item, 0, 1 ) === '^' && substr( $item, -1 ) === '$' ) {
				// do exact match
				if ( substr( $item, 1, -1 ) === $needle ) {
					$hit = $item;
					break;
				}
			}
			elseif ( substr( $item, -1 ) === '$' ) {
				// match end
				if ( substr( $item, 0, -1 ) === substr($needle, -strlen( $item ) + 1 ) ) {
					$hit = $item;
					break;
				}
			}
			elseif ( substr( $item, 0, 1 ) === '^' ) {
				// match beginning
				if ( substr( $item, 1 ) === substr( $needle, 0, strlen( $item ) - 1 ) ) {
					$hit = $item;
					break;
				}
			}
			else {
				if ( strpos( $needle, $item ) !== false ) {
					$hit = $item;
					break;
				}
			}
		}

		if ( $hit ) {
			if ( $has_ttl ) {
				return array( $hit, $this_ttl );
			}

			return $hit;
		}

		return false;
	}

	/**
	 * Improve compatibility to PHP old versions
	 *
	 * @since  1.2.2
	 *
	 */
	public static function compatibility() {
		require_once LSCWP_DIR . 'lib/php-compatibility.func.php';
	}

	/**
	 * Convert URI to URL
	 *
	 * @since  1.3
	 * @access public
	 * @param  string $uri `xx/xx.html` or `/subfolder/xx/xx.html`
	 * @return  string http://www.example.com/subfolder/xx/xx.html
	 */
	public static function uri2url( $uri ) {
		if ( substr( $uri, 0, 1 ) === '/' ) {
			self::domain_const();
			$url = LSCWP_DOMAIN . $uri;
		}
		else {
			$url = home_url( '/' ) . $uri;
		}

		return $url;
	}

	/**
	 * Convert URL to basename (filename)
	 *
	 * @since  4.7
	 */
	public static function basename( $url ) {
		$url = trim( $url );
		$uri = @parse_url( $url, PHP_URL_PATH );
		$basename = pathinfo( $uri, PATHINFO_BASENAME );

		return $basename;
	}

	/**
	 * Drop .webp if existed in filename
	 *
	 * @since  4.7
	 */
	public static function drop_webp( $filename ) {
		if ( substr($filename, -5 ) === '.webp' ) $filename = substr( $filename, 0, -5 );

		return $filename;
	}

	/**
	 * Convert URL to URI
	 *
	 * @since  1.2.2
	 * @since  1.6.2.1 Added 2nd param keep_qs
	 * @access public
	 */
	public static function url2uri( $url, $keep_qs = false ) {
		$url = trim( $url );
		$uri = @parse_url( $url, PHP_URL_PATH );
		$qs = @parse_url( $url, PHP_URL_QUERY );

		if ( ! $keep_qs || ! $qs ) {
			return $uri;
		}

		return $uri . '?' . $qs;
	}

	/**
	 * Get attachment relative path to upload folder
	 *
	 * @since 3.0
	 * @access public
	 * @param  string 	`https://aa.com/bbb/wp-content/upload/2018/08/test.jpg` or `/bbb/wp-content/upload/2018/08/test.jpg`
	 * @return string 	`2018/08/test.jpg`
	 */
	public static function att_short_path( $url ) {
		if ( ! defined( 'LITESPEED_UPLOAD_PATH' ) ) {
			$_wp_upload_dir = wp_upload_dir();

			$upload_path = self::url2uri( $_wp_upload_dir[ 'baseurl' ] );

			define( 'LITESPEED_UPLOAD_PATH', $upload_path );
		}

		$local_file = self::url2uri( $url );

		$short_path = substr( $local_file, strlen( LITESPEED_UPLOAD_PATH ) + 1 );

		return $short_path;
	}

	/**
	 * Make URL to be relative
	 *
	 * NOTE: for subfolder home_url, will keep subfolder part (strip nothing but scheme and host)
	 *
	 * @param  string $url
	 * @return string      Relative URL, start with /
	 */
	public static function make_relative( $url ) {
		// replace home_url if the url is full url
		self::domain_const();
		if ( strpos( $url, LSCWP_DOMAIN ) === 0 ) {
			$url = substr( $url, strlen( LSCWP_DOMAIN ) );
		}
		return trim( $url );
	}

	/**
	 * Convert URL to domain only
	 *
	 * @since  1.7.1
	 */
	public static function parse_domain( $url ) {
		$url = @parse_url( $url );
		if ( empty( $url[ 'host' ] ) ) {
			return '';
		}

		if ( ! empty( $url[ 'scheme' ] ) ) {
			return $url[ 'scheme' ] . '://' . $url[ 'host' ];
		}

		return '//' . $url[ 'host' ];
	}

	/**
	 * Drop protocol `https:` from https://example.com
	 *
	 * @since  3.3
	 */
	public static function noprotocol( $url ) {
		$tmp = parse_url( trim( $url ) );
		if ( ! empty( $tmp[ 'scheme' ] ) ) {
			$url = str_replace( $tmp[ 'scheme' ] . ':', '', $url );
		}

		return $url;
	}

	/**
	 * Validate ip v4
	 * @since 5.5
	 */
	public static function valid_ipv4($ip) {
		return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
	}

	/**
	 * Generate domain const
	 *
	 * This will generate http://www.example.com even there is a subfolder in home_url setting
	 *
	 * Conf LSCWP_DOMAIN has NO trailing /
	 *
	 * @since  1.3
	 * @access public
	 */
	public static function domain_const() {
		if ( defined( 'LSCWP_DOMAIN' ) ) {
			return;
		}

		self::compatibility();
		$domain = http_build_url( get_home_url(), array(), HTTP_URL_STRIP_ALL );

		define( 'LSCWP_DOMAIN', $domain );
	}

	/**
	 * Array map one textarea to sanitize the url
	 *
	 * @since  1.3
	 * @access public
	 * @param  string $content
	 * @param  bool $type String handler type
	 * @return string|array
	 */
	public static function sanitize_lines( $arr, $type = null ) {
		$types = $type ? explode( ',', $type ) : array();

		if ( ! $arr ) {
			if ( $type === 'string' ) {
				return '';
			}
			return array();
		}

		if ( ! is_array( $arr ) ) {
			$arr = explode( "\n", $arr );
		}

		$arr = array_map( 'trim', $arr );
		$changed = false;
		if ( in_array( 'uri', $types ) ) {
			$arr = array_map( __CLASS__ . '::url2uri', $arr );
			$changed = true;
		}
		if ( in_array( 'basename', $types ) ) {
			$arr = array_map( __CLASS__ . '::basename', $arr );
			$changed = true;
		}
		if ( in_array( 'drop_webp', $types ) ) {
			$arr = array_map( __CLASS__ . '::drop_webp', $arr );
			$changed = true;
		}
		if ( in_array( 'relative', $types ) ) {
			$arr = array_map( __CLASS__ . '::make_relative', $arr );// Remove domain
			$changed = true;
		}
		if ( in_array( 'domain', $types ) ) {
			$arr = array_map( __CLASS__ . '::parse_domain', $arr );// Only keep domain
			$changed = true;
		}

		if ( in_array( 'noprotocol', $types ) ) {
			$arr = array_map( __CLASS__ . '::noprotocol', $arr ); // Drop protocol, `https://example.com` -> `//example.com`
			$changed = true;
		}

		if ( in_array( 'trailingslash', $types ) ) {
			$arr = array_map( 'trailingslashit', $arr ); // Append trailing slach, `https://example.com` -> `https://example.com/`
			$changed = true;
		}

		if ( $changed ) {
			$arr = array_map( 'trim', $arr );
		}
		$arr = array_unique( $arr );
		$arr = array_filter( $arr );

		if ( in_array( 'string', $types ) ) {
			return implode( "\n", $arr );
		}

		return $arr;
	}

	/**
	 * Builds an url with an action and a nonce.
	 *
	 * Assumes user capabilities are already checked.
	 *
	 * @since  1.6 Changed order of 2nd&3rd param, changed 3rd param `append_str` to 2nd `type`
	 * @access public
	 * @return string The built url.
	 */
	public static function build_url( $action, $type = false, $is_ajax = false, $page = null, $append_arr = array() ) {
		$prefix = '?';

		if ( $page === '_ori' ) {
			$page = true;
			$append_arr[ '_litespeed_ori' ] = 1;
		}

		if ( ! $is_ajax ) {
			if ( $page ) {
				// If use admin url
				if ( $page === true ) {
					$page = 'admin.php';
				}
				else {
					if ( strpos( $page, '?' ) !== false ) {
						$prefix = '&';
					}
				}
				$combined = $page . $prefix . Router::ACTION . '=' . $action;
			}
			else {
				// Current page rebuild URL
				$params = $_GET;

				if ( ! empty( $params ) ) {
					if ( isset( $params[ Router::ACTION ] ) ) {
						unset( $params[ Router::ACTION ] );
					}
					if ( isset( $params[ '_wpnonce' ] ) ) {
						unset( $params[ '_wpnonce' ] );
					}
					if ( ! empty( $params ) ) {
						$prefix .= http_build_query( $params ) . '&';
					}
				}
				global $pagenow;
				$combined = $pagenow . $prefix . Router::ACTION . '=' . $action;
			}
		}
		else {
			$combined = 'admin-ajax.php?action=litespeed_ajax&' . Router::ACTION . '=' . $action;
		}

		if ( is_network_admin() ) {
			$prenonce = network_admin_url( $combined );
		}
		else {
			$prenonce = admin_url( $combined );
		}
		$url = wp_nonce_url( $prenonce, $action, Router::NONCE );

		if ( $type ) {
			// Remove potential param `type` from url
			$url = parse_url( htmlspecialchars_decode( $url ) );
			parse_str( $url[ 'query' ], $query );

			$built_arr = array_merge( $query, array( Router::TYPE => $type ) );
			if ( $append_arr ) {
				$built_arr = array_merge( $built_arr, $append_arr );
			}
			$url[ 'query' ] = http_build_query( $built_arr );
			self::compatibility();
			$url = http_build_url( $url );
			$url = htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
		}

		return $url;
	}

	/**
	 * Check if the host is the internal host
	 *
	 * @since  1.2.3
	 *
	 */
	public static function internal( $host ) {
		if ( ! defined( 'LITESPEED_FRONTEND_HOST' ) ) {
			if ( defined( 'WP_HOME' ) ) {
				$home_host = WP_HOME;// Also think of `WP_SITEURL`
			}
			else {
				$home_host = get_option( 'home' );
			}
			define( 'LITESPEED_FRONTEND_HOST', parse_url( $home_host, PHP_URL_HOST ) );
		}

		if ( $host === LITESPEED_FRONTEND_HOST ) {
			return true;
		}

		/**
		 * Filter for multiple domains
		 * @since 2.9.4
		 */
		if ( ! isset( self::$_internal_domains ) ) {
			self::$_internal_domains = apply_filters( 'litespeed_internal_domains', array() );
		}

		if ( self::$_internal_domains ) {
			return in_array( $host, self::$_internal_domains );
		}

		return false;
	}

	/**
	 * Check if an URL is a internal existing file
	 *
	 * @since  1.2.2
	 * @since  1.6.2 Moved here from optm.cls due to usage of media.cls
	 * @access public
	 * @return string|bool The real path of file OR false
	 */
	public static function is_internal_file( $url, $addition_postfix = false ) {
		if ( substr( $url, 0, 5 ) == 'data:' ) {
			Debug2::debug2( '[Util] data: content not file' );
			return false;
		}
		$url_parsed = parse_url( $url );
		if ( isset( $url_parsed[ 'host' ] ) && ! self::internal( $url_parsed[ 'host' ] ) ) {
			// Check if is cdn path
			// Do this to avoid user hardcoded src in tpl
			if ( ! CDN::internal( $url_parsed[ 'host' ] ) ) {
				Debug2::debug2( '[Util] external' );
				return false;
			}
		}

		if ( empty( $url_parsed[ 'path' ] ) ) {
			return false;
		}

		// Need to replace child blog path for assets, ref: .htaccess
		if ( is_multisite() && defined( 'PATH_CURRENT_SITE' ) ) {
			$pattern = '#^' . PATH_CURRENT_SITE . '([_0-9a-zA-Z-]+/)(wp-(content|admin|includes))#U';
			$replacement = PATH_CURRENT_SITE . '$2';
			$url_parsed[ 'path' ] = preg_replace( $pattern, $replacement, $url_parsed[ 'path' ] );
			// $current_blog = (int) get_current_blog_id();
			// $main_blog_id = (int) get_network()->site_id;
			// if ( $current_blog === $main_blog_id ) {
			// 	define( 'LITESPEED_IS_MAIN_BLOG', true );
			// }
			// else {
			// 	define( 'LITESPEED_IS_MAIN_BLOG', false );
			// }
		}

		// Parse file path
		/**
		 * Trying to fix pure /.htaccess rewrite to /wordpress case
		 *
		 * Add `define( 'LITESPEED_WP_REALPATH', '/wordpress' );` in wp-config.php in this case
		 *
		 * @internal #611001 - Combine & Minify not working?
		 * @since  1.6.3
		 */
		if ( substr( $url_parsed[ 'path' ], 0, 1 ) === '/' ) {
			if ( defined( 'LITESPEED_WP_REALPATH' ) ) {
				$file_path_ori = $_SERVER[ 'DOCUMENT_ROOT' ] . LITESPEED_WP_REALPATH . $url_parsed[ 'path' ];
			}
			else {
				$file_path_ori = $_SERVER[ 'DOCUMENT_ROOT' ] . $url_parsed[ 'path' ];
			}
		}
		else {
			$file_path_ori = Router::frontend_path() . '/' . $url_parsed[ 'path' ];
		}

		/**
		 * Added new file postfix to be check if passed in
		 * @since 2.2.4
		 */
		if ( $addition_postfix ) {
			$file_path_ori .= '.' . $addition_postfix;
		}

		/**
		 * Added this filter for those plugins which overwrite the filepath
		 * @see #101091 plugin `Hide My WordPress`
		 * @since 2.2.3
		 */
		$file_path_ori = apply_filters( 'litespeed_realpath', $file_path_ori );

		$file_path = realpath( $file_path_ori );
		if ( ! is_file( $file_path ) ) {
			Debug2::debug2( '[Util] file not exist: ' . $file_path_ori );
			return false;
		}

		return array( $file_path, filesize( $file_path ) );
	}

	/**
	 * Safely parse URL for v5.3 compatibility
	 *
	 * @since  3.4.3
	 */
	public static function parse_url_safe( $url, $component = -1 ) {
		if ( substr( $url, 0, 2 ) == '//' ) {
			$url = 'https:' . $url;
		}

		return parse_url( $url, $component );
	}

	/**
	 * Replace url in srcset to new value
	 *
	 * @since  2.2.3
	 */
	public static function srcset_replace( $content, $callback ) {
		preg_match_all( '# srcset=([\'"])(.+)\g{1}#iU', $content, $matches );
		$srcset_ori = array();
		$srcset_final = array();
		foreach ( $matches[ 2 ] as $k => $urls_ori ) {

			$urls_final = explode( ',', $urls_ori );

			$changed = false;

			foreach ( $urls_final as $k2 => $url_info ) {
				$url_info_arr = explode( ' ', trim( $url_info ) );

				if ( ! $url2 = call_user_func( $callback, $url_info_arr[ 0 ] ) ) {
					continue;
				}

				$changed = true;

				$urls_final[ $k2 ] = str_replace( $url_info_arr[ 0 ], $url2, $url_info );

				Debug2::debug2( '[Util] - srcset replaced to ' . $url2 . ( ! empty( $url_info_arr[ 1 ] ) ? ' ' . $url_info_arr[ 1 ] : '' ) );
			}

			if ( ! $changed ) {
				continue;
			}

			$urls_final = implode( ',', $urls_final );

			$srcset_ori[] = $matches[ 0 ][ $k ];

			$srcset_final[] = str_replace( $urls_ori, $urls_final, $matches[ 0 ][ $k ] );
		}

		if ( $srcset_ori ) {
			$content = str_replace( $srcset_ori, $srcset_final, $content );
			Debug2::debug2( '[Util] - srcset replaced' );
		}

		return $content;

	}

	/**
	 * Generate pagination
	 *
	 * @since 3.0
	 * @access public
	 */
	public static function pagination( $total, $limit, $return_offset = false ) {
		$pagenum = isset( $_GET[ 'pagenum' ] ) ? absint( $_GET[ 'pagenum' ] ) : 1;

		$offset = ( $pagenum - 1 ) * $limit;
		$num_of_pages = ceil( $total / $limit );

		if ( $offset > $total ) {
			$offset = $total - $limit;
		}

		if ( $offset < 0 ) {
			$offset = 0;
		}

		if ( $return_offset ) {
			return $offset;
		}

		$page_links = paginate_links( array(
			'base' => add_query_arg( 'pagenum', '%#%' ),
			'format' => '',
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'total' => $num_of_pages,
			'current' => $pagenum,
		) );

		return '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div></div>';
	}

	/**
	 * Generate placeholder for an array to query
	 *
	 * @since 2.0
	 * @access public
	 */
	public static function chunk_placeholder( $data, $fields ) {
		$division = substr_count( $fields, ',' ) + 1;

		$q = implode( ',', array_map(
			function( $el ) { return '(' . implode( ',', $el ) . ')'; },
			array_chunk( array_fill( 0, count( $data ), '%s' ), $division )
		) );

		return $q;
	}

}
