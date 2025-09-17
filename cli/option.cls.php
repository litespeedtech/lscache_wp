<?php
/**
 * LiteSpeed Cache option Interface CLI.
 *
 * @package LiteSpeed\CLI
 */

namespace LiteSpeed\CLI;

defined( 'WPINC' ) || exit();

use LiteSpeed\Base;
use LiteSpeed\Admin_Settings;
use LiteSpeed\Utility;
use WP_CLI;
use WP_Filesystem;

/**
 * LiteSpeed Cache option Interface
 */
class Option extends Base {

	/**
	 * Set an individual LiteSpeed Cache option.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : The option key to update.
	 *
	 * <newvalue>
	 * : The new value to set the option to.
	 *
	 * ## EXAMPLES
	 *
	 *     # Set to not cache the login page
	 *     $ wp litespeed-option set cache-priv false
	 *     $ wp litespeed-option set 'cdn-mapping[url][0]' https://cdn.EXAMPLE.com
	 *     $ wp litespeed-option set media-lqip_exc $'line1\nline2'
	 *
	 * @param array $args Positional arguments (key, newvalue).
	 * @param array $assoc_args Associative arguments.
	 */
	public function set( $args, $assoc_args ) {
		// Note: If the value is multiple dimensions like cdn-mapping, need to specially handle it both here and in `const.default.json`
		// For CDN/Crawler multi dimension settings, if all children are empty in one line, will delete that line. To delete one line, just set all to empty.
		// E.g. to delete cdn-mapping[0], need to run below:
		// `set cdn-mapping[url][0] ''`
		// `set cdn-mapping[inc_img][0] ''`
		// `set cdn-mapping[inc_css][0] ''`
		// `set cdn-mapping[inc_js][0] ''`
		// `set cdn-mapping[filetype][0] ''`

		$key = $args[0];
		$val = $args[1];

		// For CDN mapping, allow:
		// `set 'cdn-mapping[url][0]' https://the1st_cdn_url`
		// `set 'cdn-mapping[inc_img][0]' true`
		// `set 'cdn-mapping[inc_img][0]' 1`
		//
		// For Crawler cookies:
		// `set 'crawler-cookies[name][0]' my_currency`
		// `set 'crawler-cookies[vals][0]' "USD\nTWD"`
		//
		// For multi lines setting:
		// `set media-lqip_exc $'img1.jpg\nimg2.jpg'`

		// Build raw data
		$raw_data = array(
			Admin_Settings::ENROLL => array( $key ),
		);

		// Contains child set
		if ( false !== strpos( $key, '[' ) ) {
			parse_str( $key . '=' . $val, $key2 );
			$raw_data = array_merge( $raw_data, $key2 );
		} else {
			$raw_data[ $key ] = $val;
		}

		$this->cls( 'Admin_Settings' )->save( $raw_data );
		WP_CLI::line( "$key:" );
		$this->get( $args, $assoc_args );
	}

	/**
	 * Get all plugin options.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format (e.g., json).
	 *
	 * ## EXAMPLES
	 *
	 *     # Get all options
	 *     $ wp litespeed-option all
	 *     $ wp litespeed-option all --json
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function all( $args, $assoc_args ) {
		$options = $this->get_options();

		if ( ! empty( $assoc_args['format'] ) ) {
			WP_CLI::print_value( $options, $assoc_args );
			return;
		}

		$option_out = array();

		$buf = WP_CLI::colorize( '%CThe list of options:%n' );
		WP_CLI::line( $buf );

		foreach ( $options as $k => $v ) {
			if ( self::O_CDN_MAPPING === $k || self::O_CRAWLER_COOKIES === $k ) {
				foreach ( $v as $k2 => $v2 ) {
					// $k2 is numeric
					if ( is_array( $v2 ) ) {
						foreach ( $v2 as $k3 => $v3 ) {
							// $k3 is 'url/inc_img/name/vals'
							if ( is_array( $v3 ) ) {
								$option_out[] = array(
									'key'   => '',
									'value' => '',
								);
								foreach ( $v3 as $k4 => $v4 ) {
									$option_out[] = array(
										'key'   => 0 === $k4 ? "{$k}[$k3][$k2]" : '',
										'value' => $v4,
									);
								}
								$option_out[] = array(
									'key'   => '',
									'value' => '',
								);
							} else {
								$option_out[] = array(
									'key'   => "{$k}[$k3][$k2]",
									'value' => $v3,
								);
							}
						}
					}
				}
				continue;
			} elseif ( is_array( $v ) && $v ) {
				$option_out[] = array(
					'key'   => '',
					'value' => '',
				);
				foreach ( $v as $k2 => $v2 ) {
					$option_out[] = array(
						'key'   => 0 === $k2 ? $k : '',
						'value' => $v2,
					);
				}
				$option_out[] = array(
					'key'   => '',
					'value' => '',
				);
				continue;
			}

			if ( array_key_exists( $k, self::$_default_options ) && is_bool( self::$_default_options[ $k ] ) && ! $v ) {
				$v = 0;
			}

			if ( '' === $v || array() === $v ) {
				$v = "''";
			}

			$option_out[] = array(
				'key'   => $k,
				'value' => $v,
			);
		}

		WP_CLI\Utils\format_items( 'table', $option_out, array( 'key', 'value' ) );
	}

	/**
	 * Get a specific plugin option.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The option ID to retrieve (e.g., cache-priv, cdn-mapping[url][0]).
	 *
	 * ## EXAMPLES
	 *
	 *     # Get one option
	 *     $ wp litespeed-option get cache-priv
	 *     $ wp litespeed-option get 'cdn-mapping[url][0]'
	 *
	 * @param array $args Positional arguments (id).
	 * @param array $assoc_args Associative arguments.
	 */
	public function get( $args, $assoc_args ) {
		$id = $args[0];

		$child = false;
		if ( false !== strpos( $id, '[' ) ) {
			parse_str( $id, $id2 );
			Utility::compatibility();
			$id = array_key_first( $id2 );

			$child = array_key_first( $id2[ $id ] ); // is `url`
			if ( ! $child ) {
				WP_CLI::error( 'Wrong child key' );
				return;
			}
			$numeric = array_key_first( $id2[ $id ][ $child ] ); // `0`
			if ( null === $numeric ) {
				WP_CLI::error( 'Wrong 2nd level numeric key' );
				return;
			}
		}

		if ( ! isset( self::$_default_options[ $id ] ) ) {
			WP_CLI::error( 'ID not exist [id] ' . $id );
			return;
		}

		$v         = $this->conf( $id );
		$default_v = self::$_default_options[ $id ];

		// For CDN_mapping and crawler_cookies
		// Examples of option name:
		// cdn-mapping[url][0]
		// crawler-cookies[name][1]
		if ( self::O_CDN_MAPPING === $id ) {
			if ( ! in_array( $child, array( self::CDN_MAPPING_URL, self::CDN_MAPPING_INC_IMG, self::CDN_MAPPING_INC_CSS, self::CDN_MAPPING_INC_JS, self::CDN_MAPPING_FILETYPE ), true ) ) {
				WP_CLI::error( 'Wrong child key' );
				return;
			}
		}
		if ( self::O_CRAWLER_COOKIES === $id ) {
			if ( ! in_array( $child, array( self::CRWL_COOKIE_NAME, self::CRWL_COOKIE_VALS ), true ) ) {
				WP_CLI::error( 'Wrong child key' );
				return;
			}
		}

		if ( self::O_CDN_MAPPING === $id || self::O_CRAWLER_COOKIES === $id ) {
			if ( ! empty( $v[ $numeric ][ $child ] ) ) {
				$v = $v[ $numeric ][ $child ];
			} elseif ( self::O_CDN_MAPPING === $id ) {
				if ( in_array( $child, array( self::CDN_MAPPING_INC_IMG, self::CDN_MAPPING_INC_CSS, self::CDN_MAPPING_INC_JS ), true ) ) {
					$v = 0;
				} else {
					$v = "''";
				}
			} else {
				$v = "''";
			}
		}

		if ( is_array( $v ) ) {
			$v = implode( PHP_EOL, $v );
		}

		if ( ! $v && self::O_CDN_MAPPING !== $id && self::O_CRAWLER_COOKIES !== $id ) {
			// empty array for CDN/crawler has been handled
			if ( is_bool( $default_v ) ) {
				$v = 0;
			} elseif ( ! is_array( $default_v ) ) {
				$v = "''";
			}
		}

		WP_CLI::line( $v );
	}

	/**
	 * Export plugin options to a file.
	 *
	 * ## OPTIONS
	 *
	 * [--filename=<path>]
	 * : The default path used is CURRENTDIR/lscache_wp_options_DATE-TIME.txt.
	 * To select a different file, use this option.
	 *
	 * ## EXAMPLES
	 *
	 *     # Export options to a file.
	 *     $ wp litespeed-option export
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function export( $args, $assoc_args ) {
		if ( isset( $assoc_args['filename'] ) ) {
			$file = $assoc_args['filename'];
		} else {
			$file = getcwd() . '/litespeed_options_' . gmdate( 'd_m_Y-His' ) . '.data';
		}

		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( ! $wp_filesystem->is_writable( dirname( $file ) ) ) {
			WP_CLI::error( 'Directory not writable.' );
			return;
		}

		$data = $this->cls( 'Import' )->export( true );

		if ( false === $wp_filesystem->put_contents( $file, $data ) ) {
			WP_CLI::error( 'Failed to create file.' );
			return;
		}

		WP_CLI::success( 'Created file ' . $file );
	}

	/**
	 * Import plugin options from a file.
	 *
	 * The file must be formatted as such:
	 * option_key=option_value
	 * One per line.
	 * A semicolon at the beginning of the line indicates a comment and will be skipped.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : The file to import options from.
	 *
	 * ## EXAMPLES
	 *
	 *     # Import options from CURRENTDIR/options.txt
	 *     $ wp litespeed-option import options.txt
	 *
	 * @param array $args Positional arguments (file).
	 * @param array $assoc_args Associative arguments.
	 */
	public function import( $args, $assoc_args ) {
		$file = $args[0];

		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( ! $wp_filesystem->exists( $file ) || ! $wp_filesystem->is_readable( $file ) ) {
			WP_CLI::error( 'File does not exist or is not readable.' );
			return;
		}

		$res = $this->cls( 'Import' )->import( $file );

		if ( ! $res ) {
			WP_CLI::error( 'Failed to parse serialized data from file.' );
			return;
		}

		WP_CLI::success( 'Options imported. [File] ' . $file );
	}

	/**
	 * Import plugin options from a remote file.
	 *
	 * The file must be formatted as such:
	 * option_key=option_value
	 * One per line.
	 * A semicolon at the beginning of the line indicates a comment and will be skipped.
	 *
	 * ## OPTIONS
	 *
	 * <url>
	 * : The URL to import options from.
	 *
	 * ## EXAMPLES
	 *
	 *     # Import options from https://domain.com/options.txt
	 *     $ wp litespeed-option import_remote https://domain.com/options.txt
	 *
	 * @param array $args Positional arguments (url).
	 */
	public function import_remote( $args ) {
		$file = $args[0];

		$tmp_file = download_url( $file );

		if ( is_wp_error( $tmp_file ) ) {
			WP_CLI::error( 'Failed to download file.' );
			return;
		}

		$res = $this->cls( 'Import' )->import( $tmp_file );

		if ( ! $res ) {
			WP_CLI::error( 'Failed to parse serialized data from file.' );
			return;
		}

		WP_CLI::success( 'Options imported. [File] ' . $file );
	}

	/**
	 * Reset all options to default.
	 *
	 * ## EXAMPLES
	 *
	 *     # Reset all options
	 *     $ wp litespeed-option reset
	 */
	public function reset() {
		$this->cls( 'Import' )->reset();
	}
}
