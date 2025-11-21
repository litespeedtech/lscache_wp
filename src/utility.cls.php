<?php
/**
 * Utility helpers for LiteSpeed Cache.
 *
 * @since   1.1.5
 * @package LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Miscellaneous utility methods used across the plugin.
 */
class Utility extends Root {

	/**
	 * Cached list of extra internal domains.
	 *
	 * @var array<int,string>|null
	 */
	private static $_internal_domains;

	/**
	 * Validate a list of regex rules by attempting to compile them.
	 *
	 * @since 1.0.9
	 * @since 3.0 Moved here from admin-settings.cls
	 * @param array<int,string> $rules Regex fragments (without delimiters).
	 * @return bool True for valid rules, false otherwise.
	 */
	public static function syntax_checker( $rules ) {
		return false !== preg_match( self::arr2regex( $rules ), '' );
	}

	/**
	 * Combine an array of strings into a single alternation regex.
	 *
	 * @since 3.0
	 *
	 * @param array<int,string> $arr            List of strings.
	 * @param bool              $drop_delimiter When true, return without regex delimiters.
	 * @return string Regex pattern.
	 */
	public static function arr2regex( $arr, $drop_delimiter = false ) {
		$arr = self::sanitize_lines( $arr );

		$new_arr = [];
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
	 * Replace wildcard characters in a string/array with their regex equivalents.
	 *
	 * @since 3.2.2
	 *
	 * @param string|array<int,string> $value String or list of strings.
	 * @return string|array<int,string>
	 */
	public static function wildcard2regex( $value ) {
		if ( is_array( $value ) ) {
			return array_map( __CLASS__ . '::wildcard2regex', $value );
		}

		if ( false !== strpos( $value, '*' ) ) {
			$value = preg_quote( $value, '#' );
			$value = str_replace( '\*', '.*', $value );
		}

		return $value;
	}

	/**
	 * Get current page type string.
	 *
	 * @since 2.9
	 *
	 * @return string Page type.
	 */
	public static function page_type() {
		global $wp_query;
		$page_type = 'default';

		if ( $wp_query->is_page ) {
			$page_type = is_front_page() ? 'front' : 'page';
		} elseif ( $wp_query->is_home ) {
			$page_type = 'home';
		} elseif ( $wp_query->is_single ) {
			$page_type = get_post_type();
		} elseif ( $wp_query->is_category ) {
			$page_type = 'category';
		} elseif ( $wp_query->is_tag ) {
			$page_type = 'tag';
		} elseif ( $wp_query->is_tax ) {
			$page_type = 'tax';
		} elseif ( $wp_query->is_archive ) {
			if ( $wp_query->is_day ) {
				$page_type = 'day';
			} elseif ( $wp_query->is_month ) {
				$page_type = 'month';
			} elseif ( $wp_query->is_year ) {
				$page_type = 'year';
			} elseif ( $wp_query->is_author ) {
				$page_type = 'author';
			} else {
				$page_type = 'archive';
			}
		} elseif ( $wp_query->is_search ) {
			$page_type = 'search';
		} elseif ( $wp_query->is_404 ) {
			$page_type = '404';
		}

		return $page_type;
	}

	/**
	 * Get ping speed to a domain via HTTP HEAD timing.
	 *
	 * @since 2.9
	 *
	 * @param string $domain Domain or URL.
	 * @return int Milliseconds (99999 on error).
	 */
	public static function ping( $domain ) {
		if ( false !== strpos( $domain, ':' ) ) {
			$host   = wp_parse_url( $domain, PHP_URL_HOST );
			$domain = $host ? $host : $domain;
		}
		$starttime = microtime(true);
		$file      = fsockopen($domain, 443, $errno, $errstr, 10); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fsockopen
		$stoptime  = microtime(true);
		$status    = 0;

		if (!$file) {
			$status = 99999;
		} else {
			// Site is up
			fclose($file); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			$status = ($stoptime - $starttime) * 1000;
			$status = floor($status);
		}

		Debug2::debug("[Util] ping [Domain] $domain \t[Speed] $status");

		return $status;
	}

	/**
	 * Convert seconds/timestamp to a readable relative time.
	 *
	 * @since 1.6.5
	 *
	 * @param int  $seconds_or_timestamp Seconds or 10-digit timestamp.
	 * @param int  $timeout              If older than this, show absolute time.
	 * @param bool $forward              When true, omit "ago".
	 * @return string Human readable time.
	 */
	public static function readable_time( $seconds_or_timestamp, $timeout = 3600, $forward = false ) {
		if ( 10 === strlen( (string) $seconds_or_timestamp ) ) {
			$seconds = time() - (int) $seconds_or_timestamp;
			if ( $seconds > $timeout ) {
				return gmdate( 'm/d/Y H:i:s', (int) $seconds_or_timestamp + (int) LITESPEED_TIME_OFFSET );
			}
		} else {
			$seconds = (int) $seconds_or_timestamp;
		}

		$res = '';
		if ( $seconds > 86400 ) {
			$num      = (int) floor( $seconds / 86400 );
			$res     .= $num . 'd';
			$seconds %= 86400;
		}

		if ( $seconds > 3600 ) {
			if ( $res ) {
				$res .= ', ';
			}
			$num      = (int) floor( $seconds / 3600 );
			$res     .= $num . 'h';
			$seconds %= 3600;
		}

		if ( $seconds > 60 ) {
			if ( $res ) {
				$res .= ', ';
			}
			$num      = (int) floor( $seconds / 60 );
			$res     .= $num . 'm';
			$seconds %= 60;
		}

		if ( $seconds > 0 ) {
			if ( $res ) {
				$res .= ' ';
			}
			$res .= $seconds . 's';
		}

		if ( ! $res ) {
			return $forward ? __( 'right now', 'litespeed-cache' ) : __( 'just now', 'litespeed-cache' );
		}

		return $forward ? $res : sprintf( __( ' %s ago', 'litespeed-cache' ), $res );
	}

	/**
	 * Convert array to a compact base64 JSON string.
	 *
	 * @since 1.6
	 *
	 * @param mixed $arr Input array or scalar.
	 * @return string|mixed Encoded string or original value.
	 */
	public static function arr2str( $arr ) {
		if ( ! is_array( $arr ) ) {
			return $arr;
		}

		return base64_encode( wp_json_encode( $arr ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Convert size in bytes to human readable form.
	 *
	 * @since 1.6
	 *
	 * @param int  $filesize Bytes.
	 * @param bool $is_1000  When true, use 1000-based units.
	 * @return string
	 */
	public static function real_size( $filesize, $is_1000 = false ) {
		$unit = $is_1000 ? 1000 : 1024;

		if ( $filesize >= pow( $unit, 3 ) ) {
			$filesize = round( ( $filesize / pow( $unit, 3 ) ) * 100 ) / 100 . 'G';
		} elseif ( $filesize >= pow( $unit, 2 ) ) {
			$filesize = round( ( $filesize / pow( $unit, 2 ) ) * 100 ) / 100 . 'M';
		} elseif ( $filesize >= $unit ) {
			$filesize = round( ( $filesize / $unit ) * 100 ) / 100 . 'K';
		} else {
			$filesize = $filesize . 'B';
		}
		return $filesize;
	}

	/**
	 * Parse HTML attribute string into an array.
	 *
	 * @since 1.2.2
	 * @since 1.4 Moved from optimize to utility
	 * @access private
	 *
	 * @param string $str Raw attribute string.
	 * @return array<string,string> Attributes.
	 */
	public static function parse_attr( $str ) {
		$attrs = [];
		preg_match_all( '#([\w-]+)=(["\'])([^\2]*)\2#isU', $str, $matches, PREG_SET_ORDER );
		foreach ( $matches as $match ) {
			$attrs[ $match[1] ] = trim( $match[3] );
		}
		return $attrs;
	}

	/**
	 * Search for a hit within an array of strings/rules.
	 *
	 * Supports ^prefix, suffix$, ^exact$, and substring.
	 *
	 * @since 1.3
	 * @access private
	 *
	 * @param string $needle   The string to compare.
	 * @param array  $haystack Array of rules/strings.
	 * @param bool   $has_ttl  When true, support "rule TTL" format.
	 * @return bool|string|array False if not found; matched item or [item, ttl] if has_ttl.
	 */
	public static function str_hit_array( $needle, $haystack, $has_ttl = false ) {
		if ( ! $haystack ) {
			return false;
		}
		if ( ! is_array( $haystack ) ) {
			Debug2::debug( '[Util] ❌ bad param in str_hit_array()!' );
			return false;
		}

		$hit      = false;
		$this_ttl = 0;
		foreach ( $haystack as $item ) {
			if ( ! $item ) {
				continue;
			}

			if ( $has_ttl ) {
				$this_ttl = 0;
				$item     = explode( ' ', $item );
				if ( ! empty( $item[1] ) ) {
					$this_ttl = $item[1];
				}
				$item = $item[0];
			}

			if ( '^' === substr( $item, 0, 1 ) && '$' === substr( $item, -1 ) ) {
				if ( substr( $item, 1, -1 ) === $needle ) {
					$hit = $item;
					break;
				}
			} elseif ( '$' === substr( $item, -1 ) ) {
				if ( substr( $item, 0, -1 ) === substr( $needle, -strlen( $item ) + 1 ) ) {
					$hit = $item;
					break;
				}
			} elseif ( '^' === substr( $item, 0, 1 ) ) {
				if ( substr( $item, 1 ) === substr( $needle, 0, strlen( $item ) - 1 ) ) {
					$hit = $item;
					break;
				}
			} elseif ( false !== strpos( $needle, $item ) ) {
				$hit = $item;
				break;
			}
		}

		if ( $hit ) {
			return $has_ttl ? [ $hit, $this_ttl ] : $hit;
		}

		return false;
	}

	/**
	 * Load PHP-compat library.
	 *
	 * @since 1.2.2
	 * @return void
	 */
	public static function compatibility() {
		require_once LSCWP_DIR . 'lib/php-compatibility.func.php';
	}

	/**
	 * Convert URI path to absolute URL.
	 *
	 * @since 1.3
	 *
	 * @param string $uri Relative path `/a/b.html` or `a/b.html`.
	 * @return string Absolute URL.
	 */
	public static function uri2url( $uri ) {
		if ( '/' === substr( $uri, 0, 1 ) ) {
			self::domain_const();
			$url = LSCWP_DOMAIN . $uri;
		} else {
			$url = home_url( '/' ) . $uri;
		}

		return $url;
	}

	/**
	 * Get basename from URL.
	 *
	 * @since 4.7
	 *
	 * @param string $url URL.
	 * @return string Basename.
	 */
	public static function basename( $url ) {
		$url      = trim( $url );
		$uri      = wp_parse_url( $url, PHP_URL_PATH );
		$basename = pathinfo( (string) $uri, PATHINFO_BASENAME );

		return $basename;
	}

	/**
	 * Drop .webp and .avif suffix from a filename.
	 *
	 * @since 4.7
	 *
	 * @param string $filename Filename.
	 * @return string Cleaned filename.
	 */
	public static function drop_webp( $filename ) {
		if ( in_array( substr( $filename, -5 ), [ '.webp', '.avif' ], true ) ) {
			$filename = substr( $filename, 0, -5 );
		}

		return $filename;
	}

	/**
	 * Convert URL to URI (optionally keep query).
	 *
	 * @since 1.2.2
	 * @since 1.6.2.1 Added 2nd param keep_qs
	 *
	 * @param string $url     URL.
	 * @param bool   $keep_qs Keep query string.
	 * @return string URI.
	 */
	public static function url2uri( $url, $keep_qs = false ) {
		$url = trim( $url );
		$uri = wp_parse_url( $url, PHP_URL_PATH );
		$qs  = wp_parse_url( $url, PHP_URL_QUERY );

		if ( ! $keep_qs || ! $qs ) {
			return (string) $uri;
		}

		return (string) $uri . '?' . $qs;
	}

	/**
	 * Get attachment relative path to upload folder.
	 *
	 * @since 3.0
	 *
	 * @param string $url Full attachment URL.
	 * @return string Relative upload path like `2018/08/file.jpg`.
	 */
	public static function att_short_path( $url ) {
		if ( ! defined( 'LITESPEED_UPLOAD_PATH' ) ) {
			$_wp_upload_dir = wp_upload_dir();

			$upload_path = self::url2uri( $_wp_upload_dir['baseurl'] );

			define( 'LITESPEED_UPLOAD_PATH', $upload_path );
		}

		$local_file = self::url2uri( $url );

		$short_path = substr( $local_file, strlen( LITESPEED_UPLOAD_PATH ) + 1 );

		return $short_path;
	}

	/**
	 * Make URL relative to the site root (preserves subdir).
	 *
	 * @param string $url Absolute URL.
	 * @return string Relative URL starting with '/'.
	 */
	public static function make_relative( $url ) {
		self::domain_const();
		if ( 0 === strpos( $url, LSCWP_DOMAIN ) ) {
			$url = substr( $url, strlen( LSCWP_DOMAIN ) );
		}
		return trim( $url );
	}

	/**
	 * Extract just the scheme+host portion from a URL.
	 *
	 * @since 1.7.1
	 *
	 * @param string $url URL.
	 * @return string Host-only URL (with scheme if available).
	 */
	public static function parse_domain( $url ) {
		$parsed = wp_parse_url( $url );
		if ( empty( $parsed['host'] ) ) {
			return '';
		}

		if ( ! empty( $parsed['scheme'] ) ) {
			return $parsed['scheme'] . '://' . $parsed['host'];
		}

		return '//' . $parsed['host'];
	}

	/**
	 * Drop protocol from URL (e.g., https://example.com -> //example.com).
	 *
	 * @since 3.3
	 *
	 * @param string $url URL.
	 * @return string Protocol-relative URL.
	 */
	public static function noprotocol( $url ) {
		$tmp = wp_parse_url( trim( $url ) );
		if ( ! empty( $tmp['scheme'] ) ) {
			$url = str_replace( $tmp['scheme'] . ':', '', $url );
		}

		return $url;
	}

	/**
	 * Validate IPv4 public address.
	 *
	 * @since 5.5
	 *
	 * @param string $ip IP address.
	 * @return string|false IP or false when invalid.
	 */
	public static function valid_ipv4( $ip ) {
		return filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
	}

	/**
	 * Define LSCWP_DOMAIN using the home URL (no trailing slash).
	 *
	 * @since 1.3
	 * @return void
	 */
	public static function domain_const() {
		if ( defined( 'LSCWP_DOMAIN' ) ) {
			return;
		}

		self::compatibility();
		$domain = http_build_url( get_home_url(), [], HTTP_URL_STRIP_ALL ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url

		define( 'LSCWP_DOMAIN', $domain );
	}

	/**
	 * Sanitize lines based on requested transforms.
	 *
	 * @since 1.3
	 *
	 * @param array|string $arr  Lines as array or newline-separated string.
	 * @param string|null  $type Comma-separated transforms: uri,basename,drop_webp,relative,domain,noprotocol,trailingslash,string.
	 * @return array|string Sanitized list or string.
	 */
	public static function sanitize_lines( $arr, $type = null ) {
		$types = $type ? explode( ',', $type ) : [];

		if ( ! $arr ) {
			if ( 'string' === $type ) {
				return '';
			}
			return [];
		}

		if ( ! is_array( $arr ) ) {
			$arr = explode( "\n", $arr );
		}

		$arr     = array_map( 'trim', $arr );
		$changed = false;
		if ( in_array( 'uri', $types, true ) ) {
			$arr     = array_map( __CLASS__ . '::url2uri', $arr );
			$changed = true;
		}
		if ( in_array( 'basename', $types, true ) ) {
			$arr     = array_map( __CLASS__ . '::basename', $arr );
			$changed = true;
		}
		if ( in_array( 'drop_webp', $types, true ) ) {
			$arr     = array_map( __CLASS__ . '::drop_webp', $arr );
			$changed = true;
		}
		if ( in_array( 'relative', $types, true ) ) {
			$arr     = array_map( __CLASS__ . '::make_relative', $arr );
			$changed = true;
		}
		if ( in_array( 'domain', $types, true ) ) {
			$arr     = array_map( __CLASS__ . '::parse_domain', $arr );
			$changed = true;
		}
		if ( in_array( 'noprotocol', $types, true ) ) {
			$arr     = array_map( __CLASS__ . '::noprotocol', $arr );
			$changed = true;
		}
		if ( in_array( 'trailingslash', $types, true ) ) {
			$arr     = array_map( 'trailingslashit', $arr );
			$changed = true;
		}

		if ( $changed ) {
			$arr = array_map( 'trim', $arr );
		}
		$arr = array_unique( $arr );
		$arr = array_filter( $arr );

		if ( in_array( 'string', $types, true ) ) {
			return implode( "\n", $arr );
		}

		return $arr;
	}

	/**
	 * Build an admin URL with action & nonce.
	 *
	 * Assumes user capabilities are already checked.
	 *
	 * @since 1.6 Changed order of 2nd&3rd param, changed 3rd param `append_str` to 2nd `type`
	 *
	 * @param string               $action    Action name.
	 * @param string|false         $type      Optional type query value.
	 * @param bool                 $is_ajax   Whether to build for admin-ajax.php.
	 * @param string|null|bool     $page      Page filename or true for admin.php.
	 * @param array<string,string> $append_arr Extra query parameters.
	 * @param bool                 $unescape  Return unescaped URL.
	 * @return string Built URL.
	 */
	public static function build_url( $action, $type = false, $is_ajax = false, $page = null, $append_arr = [], $unescape = false ) {
		$prefix = '?';

		if ( '_ori' === $page ) {
			$page                         = true;
			$append_arr['_litespeed_ori'] = 1;
		}

		if ( ! $is_ajax ) {
			if ( $page ) {
				if ( true === $page ) {
					$page = 'admin.php';
				} elseif ( false !== strpos( $page, '?' ) ) {
					$prefix = '&';
				}
				$combined = $page . $prefix . Router::ACTION . '=' . $action;
			} else {
				// Current page rebuild URL.
				$params = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				if ( ! empty( $params ) ) {
					if ( isset( $params[ Router::ACTION ] ) ) {
						unset( $params[ Router::ACTION ] );
					}
					if ( isset( $params['_wpnonce'] ) ) {
						unset( $params['_wpnonce'] );
					}
					if ( ! empty( $params ) ) {
						$prefix .= http_build_query( $params ) . '&';
					}
				}
				global $pagenow;
				$combined = $pagenow . $prefix . Router::ACTION . '=' . $action;
			}
		} else {
			$combined = 'admin-ajax.php?action=litespeed_ajax&' . Router::ACTION . '=' . $action;
		}

		$prenonce = is_network_admin() ? network_admin_url( $combined ) : admin_url( $combined );
		$url      = wp_nonce_url( $prenonce, $action, Router::NONCE );

		if ( $type ) {
			// Remove potential param `type` from url.
			$parsed = wp_parse_url( htmlspecialchars_decode( $url ) );
			$query  = [];
			if ( isset( $parsed['query'] ) ) {
				parse_str( $parsed['query'], $query );
			}

			$built_arr       = array_merge( $query, [ Router::TYPE => $type ] );
			$parsed['query'] = http_build_query( $built_arr + (array) $append_arr );
			self::compatibility();
			$url = http_build_url( $parsed ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
			$url = htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
		}

		if ( $unescape ) {
			$url = wp_specialchars_decode( $url );
		}

		return $url;
	}

	/**
	 * Check if a host is internal (same as site host or filtered list).
	 *
	 * @since 1.2.3
	 *
	 * @param string $host Host to test.
	 * @return bool True if internal.
	 */
	public static function internal( $host ) {
		if ( ! defined( 'LITESPEED_FRONTEND_HOST' ) ) {
			if ( defined( 'WP_HOME' ) ) {
				$home_host = constant( 'WP_HOME' );
			} else {
				$home_host = get_option( 'home' );
			}
			define( 'LITESPEED_FRONTEND_HOST', (string) wp_parse_url( $home_host, PHP_URL_HOST ) );
		}

		if ( LITESPEED_FRONTEND_HOST === $host ) {
			return true;
		}

		if ( ! isset( self::$_internal_domains ) ) {
			self::$_internal_domains = apply_filters( 'litespeed_internal_domains', [] );
		}

		if ( self::$_internal_domains ) {
			return in_array( $host, self::$_internal_domains, true );
		}

		return false;
	}

	/**
	 * Check if a URL is an internal existing file and return its real path and size.
	 *
	 * @since 1.2.2
	 * @since 1.6.2 Moved here from optm.cls due to usage of media.cls
	 *
	 * @param string       $url              URL.
	 * @param string|false $addition_postfix Optional postfix to append to path before checking.
	 * @return array{0:string,1:int}|false [realpath, size] or false.
	 */
	public static function is_internal_file( $url, $addition_postfix = false ) {
		if ( 'data:' === substr( $url, 0, 5 ) ) {
			Debug2::debug2( '[Util] data: content not file' );
			return false;
		}
		$url_parsed = wp_parse_url( $url );
		if ( isset( $url_parsed['host'] ) && ! self::internal( $url_parsed['host'] ) ) {
			// Check if is cdn path.
			if ( ! CDN::internal( $url_parsed['host'] ) ) {
				Debug2::debug2( '[Util] external' );
				return false;
			}
		}

		if ( empty( $url_parsed['path'] ) ) {
			return false;
		}

		// Replace child blog path for assets (multisite).
		if ( is_multisite() && defined( 'PATH_CURRENT_SITE' ) ) {
			$pattern            = '#^' . PATH_CURRENT_SITE . '([_0-9a-zA-Z-]+/)(wp-(content|admin|includes))#U';
			$replacement        = PATH_CURRENT_SITE . '$2';
			$url_parsed['path'] = preg_replace( $pattern, $replacement, $url_parsed['path'] );
		}

		// Parse file path.
		if ( '/' === substr( $url_parsed['path'], 0, 1 ) ) {
			$docroot = isset( $_SERVER['DOCUMENT_ROOT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ) : '';
			if ( defined( 'LITESPEED_WP_REALPATH' ) ) {
				$file_path_ori = $docroot . constant( 'LITESPEED_WP_REALPATH' ) . $url_parsed['path'];
			} else {
				$file_path_ori = $docroot . $url_parsed['path'];
			}
		} else {
			$file_path_ori = Router::frontend_path() . '/' . $url_parsed['path'];
		}

		// Optional postfix.
		if ( $addition_postfix ) {
			$file_path_ori .= '.' . $addition_postfix;
		}

		$file_path_ori = apply_filters( 'litespeed_realpath', $file_path_ori );

		$file_path = realpath( $file_path_ori );
		if ( ! is_file( $file_path ) ) {
			Debug2::debug2( '[Util] file not exist: ' . $file_path_ori );
			return false;
		}

		return [ $file_path, (int) filesize( $file_path ) ];
	}

	/**
	 * Safely parse URL and component.
	 *
	 * @since 3.4.3
	 *
	 * @param string $url       URL to parse.
	 * @param int    $component One of the PHP_URL_* constants.
	 * @return mixed
	 */
	public static function parse_url_safe( $url, $component = -1 ) {
		if ( '//' === substr( $url, 0, 2 ) ) {
			$url = 'https:' . $url;
		}

		return wp_parse_url( $url, $component );
	}

	/**
	 * Replace URLs in a srcset attribute using a callback.
	 *
	 * @since 2.2.3
	 *
	 * @param string   $content  HTML content containing srcset.
	 * @param callable $callback Callback that receives old URL and returns new URL or false.
	 * @return string Modified content.
	 */
	public static function srcset_replace( $content, $callback ) {
		preg_match_all( '# srcset=([\'"])(.+)\g{1}#iU', $content, $matches );
		$srcset_ori   = [];
		$srcset_final = [];
		if ( ! empty( $matches[2] ) ) {
			foreach ( $matches[2] as $k => $urls_ori ) {
				$urls_final = explode( ',', $urls_ori );

				$changed = false;

				foreach ( $urls_final as $k2 => $url_info ) {
					$url_info_arr = explode( ' ', trim( $url_info ) );

					$new_url = call_user_func( $callback, $url_info_arr[0] );
					if ( ! $new_url ) {
						continue;
					}

					$changed           = true;
					$urls_final[ $k2 ] = str_replace( $url_info_arr[0], $new_url, $url_info );

					Debug2::debug2( '[Util] - srcset replaced to ' . $new_url . ( ! empty( $url_info_arr[1] ) ? ' ' . $url_info_arr[1] : '' ) );
				}

				if ( ! $changed ) {
					continue;
				}

				$urls_final = implode( ',', $urls_final );

				$srcset_ori[]   = $matches[0][ $k ];
				$srcset_final[] = str_replace( $urls_ori, $urls_final, $matches[0][ $k ] );
			}
		}

		if ( $srcset_ori ) {
			$content = str_replace( $srcset_ori, $srcset_final, $content );
			Debug2::debug2( '[Util] - srcset replaced' );
		}

		return $content;
	}

	/**
	 * Generate pagination HTML or return offset.
	 *
	 * @since 3.0
	 *
	 * @param int  $total         Total items.
	 * @param int  $limit         Items per page.
	 * @param bool $return_offset When true, return numeric offset instead of HTML.
	 * @return int|string
	 */
	public static function pagination( $total, $limit, $return_offset = false ) {
		$pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$offset       = ( $pagenum - 1 ) * $limit;
		$num_of_pages = (int) ceil( $total / $limit );

		if ( $offset > $total ) {
			$offset = $total - $limit;
		}

		if ( $offset < 0 ) {
			$offset = 0;
		}

		if ( $return_offset ) {
			return $offset;
		}

		$page_links = paginate_links(
			[
				'base'      => add_query_arg( 'pagenum', '%#%' ),
				'format'    => '',
				'prev_text' => '&laquo;',
				'next_text' => '&raquo;',
				'total'     => $num_of_pages,
				'current'   => $pagenum,
			]
		);

		return '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div></div>';
	}

	/**
	 * Build a GROUP placeholder like "(%s,%s),(%s,%s)" for a list of rows.
	 *
	 * @since 2.0
	 *
	 * @param array<int,array<int,string>> $data   Data rows (values already prepared).
	 * @param string                       $fields Fields CSV (only used to count columns).
	 * @return string Placeholder string.
	 */
	public static function chunk_placeholder( $data, $fields ) {
		$division = substr_count( $fields, ',' ) + 1;

		$q = implode(
			',',
			array_map(
				function ( $el ) {
					return '(' . implode( ',', $el ) . ')';
				},
				array_chunk( array_fill( 0, count( $data ), '%s' ), $division )
			)
		);

		return $q;
	}

	/**
	 * Prepare image sizes list for optimization UI.
	 *
	 * @since 7.5
	 *
	 * @param bool $detailed When true, return detailed objects; otherwise size names.
	 * @return array<int,string|array<string,int|string>>
	 */
	public static function prepare_image_sizes_array( $detailed = false ) {
		$image_sizes = wp_get_registered_image_subsizes();
		$sizes       = [];

		foreach ( $image_sizes as $current_size_name => $current_size ) {
			if ( empty( $current_size['width'] ) && empty( $current_size['height'] ) ) {
				continue;
			}

			if ( ! $detailed ) {
				$sizes[] = $current_size_name;
			} else {
				$label = $current_size['width'] . 'x' . $current_size['height'];
				if ( $current_size_name !== $label ) {
					$label = ucfirst( $current_size_name ) . ' ( ' . $label . ' )';
				}

				$sizes[] = [
					'label'     => $label,
					'file_size' => $current_size_name,
					'width'     => (int) $current_size['width'],
					'height'    => (int) $current_size['height'],
				];
			}
		}

		return $sizes;
	}
}
