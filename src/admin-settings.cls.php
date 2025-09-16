<?php
/**
 * The admin settings handler of the plugin.
 *
 * Handles saving and validating settings from the admin UI and network admin.
 *
 * @since      1.1.0
 * @package    LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Class Admin_Settings
 *
 * Saves, sanitizes, and validates LiteSpeed Cache settings.
 */
class Admin_Settings extends Base {
	const LOG_TAG = '[Settings]';

	const ENROLL = '_settings-enroll';

	/**
	 * Save settings (single site).
	 *
	 * Accepts data from $_POST or WP-CLI.
	 * Importers may call the Conf class directly.
	 *
	 * @since 3.0
	 *
	 * @param array<string,mixed> $raw_data Raw data from request/CLI.
	 * @return void
	 */
	public function save( $raw_data ) {
		self::debug( 'saving' );

		if ( empty( $raw_data[ self::ENROLL ] ) ) {
			wp_die( esc_html__( 'No fields', 'litespeed-cache' ) );
		}

		$raw_data = Admin::cleanup_text( $raw_data );

		// Convert data to config format.
		$the_matrix = [];
		foreach ( array_unique( $raw_data[ self::ENROLL ] ) as $id ) {
			$child = false;

			// Drop array format.
			if ( false !== strpos( $id, '[' ) ) {
				if ( 0 === strpos( $id, self::O_CDN_MAPPING ) || 0 === strpos( $id, self::O_CRAWLER_COOKIES ) ) {
					// CDN child | Cookie Crawler settings.
					$child = substr( $id, strpos( $id, '[' ) + 1, strpos( $id, ']' ) - strpos( $id, '[' ) - 1 );
					// Drop ending []; Compatible with xx[0] way from CLI.
					$id = substr( $id, 0, strpos( $id, '[' ) );
				} else {
					// Drop ending [].
					$id = substr( $id, 0, strpos( $id, '[' ) );
				}
			}

			if ( ! array_key_exists( $id, self::$_default_options ) ) {
				continue;
			}

			// Validate $child.
			if ( self::O_CDN_MAPPING === $id ) {
				if ( ! in_array( $child, [ self::CDN_MAPPING_URL, self::CDN_MAPPING_INC_IMG, self::CDN_MAPPING_INC_CSS, self::CDN_MAPPING_INC_JS, self::CDN_MAPPING_FILETYPE ], true ) ) {
					continue;
				}
			}
			if ( self::O_CRAWLER_COOKIES === $id ) {
				if ( ! in_array( $child, [ self::CRWL_COOKIE_NAME, self::CRWL_COOKIE_VALS ], true ) ) {
					continue;
				}
			}

			// Pull value from request.
			if ( $child ) {
				// []=xxx or [0]=xxx
				$data = ! empty( $raw_data[ $id ][ $child ] ) ? $raw_data[ $id ][ $child ] : $this->type_casting(false, $id);
			} else {
				$data = ! empty( $raw_data[ $id ] ) ? $raw_data[ $id ] : $this->type_casting(false, $id);
			}

			// Sanitize/normalize complex fields.
			if ( self::O_CDN_MAPPING === $id || self::O_CRAWLER_COOKIES === $id ) {
				// Use existing queued data if available (only when $child != false).
				$data2 = array_key_exists( $id, $the_matrix )
					? $the_matrix[ $id ]
					: ( defined( 'WP_CLI' ) && WP_CLI ? $this->conf( $id ) : [] );
			}

			switch ( $id ) {
				// Don't allow Editor/admin to be used in crawler role simulator.
				case self::O_CRAWLER_ROLES:
					$data = Utility::sanitize_lines( $data );
					if ( $data ) {
						foreach ( $data as $k => $v ) {
							if ( user_can( $v, 'edit_posts' ) ) {
								/* translators: %s: user id in <code> tags */
								$msg = sprintf(
									esc_html__( 'The user with id %s has editor access, which is not allowed for the role simulator.', 'litespeed-cache' ),
									'<code>' . esc_html( $v ) . '</code>'
								);
								Admin_Display::error( $msg );
								unset( $data[ $k ] );
							}
						}
					}
					break;

				case self::O_CDN_MAPPING:
					/**
					 * CDN setting
					 *
					 * Raw data format:
					 *  cdn-mapping[url][] = 'xxx'
					 *  cdn-mapping[url][2] = 'xxx2'
					 *  cdn-mapping[inc_js][] = 1
					 *
					 * Final format:
					 *  cdn-mapping[0][url] = 'xxx'
					 *  cdn-mapping[2][url] = 'xxx2'
					 */
					if ( $data ) {
						foreach ( $data as $k => $v ) {
							if ( self::CDN_MAPPING_FILETYPE === $child ) {
								$v = Utility::sanitize_lines( $v );
							}

							if ( self::CDN_MAPPING_URL === $child ) {
								// If not a valid URL, turn off CDN.
								if ( 0 !== strpos( $v, 'https://' ) ) {
									self::debug( '❌ CDN mapping set to OFF due to invalid URL' );
									$the_matrix[ self::O_CDN ] = false;
								}
								$v = trailingslashit( $v );
							}

							if ( in_array( $child, [ self::CDN_MAPPING_INC_IMG, self::CDN_MAPPING_INC_CSS, self::CDN_MAPPING_INC_JS ], true ) ) {
								// Because these can't be auto detected in `config->update()`, need to format here.
								$v = 'false' === $v ? 0 : (bool) $v;
							}

							if ( empty( $data2[ $k ] ) ) {
								$data2[ $k ] = [];
							}

							$data2[ $k ][ $child ] = $v;
						}
					}

					$data = $data2;
					break;

				case self::O_CRAWLER_COOKIES:
					/**
					 * Cookie Crawler setting
					 * Raw Format:
					 *  crawler-cookies[name][] = xxx
					 *  crawler-cookies[name][2] = xxx2
					 *  crawler-cookies[vals][] = xxx
					 *
					 * Final format:
					 *  crawler-cookie[0][name] = 'xxx'
					 *  crawler-cookie[0][vals] = 'xxx'
					 *  crawler-cookie[2][name] = 'xxx2'
					 *
					 * Empty line for `vals` uses literal `_null`.
					 */
					if ( $data ) {
						foreach ( $data as $k => $v ) {
							if ( self::CRWL_COOKIE_VALS === $child ) {
								$v = Utility::sanitize_lines( $v );
							}

							if ( empty( $data2[ $k ] ) ) {
								$data2[ $k ] = [];
							}

							$data2[ $k ][ $child ] = $v;
						}
					}

					$data = $data2;
					break;

				// Cache exclude category.
				case self::O_CACHE_EXC_CAT:
					$data2 = [];
					$data  = Utility::sanitize_lines( $data );
					foreach ( $data as $v ) {
						$cat_id = get_cat_ID( $v );
						if ( ! $cat_id ) {
							continue;
						}
						$data2[] = $cat_id;
					}
					$data = $data2;
					break;

				// Cache exclude tag.
				case self::O_CACHE_EXC_TAG:
					$data2 = [];
					$data  = Utility::sanitize_lines( $data );
					foreach ( $data as $v ) {
						$term = get_term_by( 'name', $v, 'post_tag' );
						if ( ! $term ) {
							// Could surface an admin error here if desired.
							continue;
						}
						$data2[] = $term->term_id;
					}
					$data = $data2;
					break;
					
				case self::O_IMG_OPTM_SIZES_SKIPPED: // Skip image sizes
					$image_sizes = Utility::prepare_image_sizes_array();
					$saved_sizes = isset( $raw_data[$id] ) ? $raw_data[$id] : [];
					$data        = array_diff( $image_sizes, $saved_sizes );
					break;

				default:
					break;
			}

			$the_matrix[ $id ] = $data;
		}

		// Special handler for CDN/Crawler 2d list to drop empty rows.
		foreach ( $the_matrix as $id => $data ) {
			/**
			 * Format:
			 *  cdn-mapping[0][url] = 'xxx'
			 *  cdn-mapping[2][url] = 'xxx2'
			 *  crawler-cookie[0][name] = 'xxx'
			 *  crawler-cookie[0][vals] = 'xxx'
			 *  crawler-cookie[2][name] = 'xxx2'
			 */
			if ( self::O_CDN_MAPPING === $id || self::O_CRAWLER_COOKIES === $id ) {
				// Drop row if all children are empty.
				foreach ( $data as $k => $v ) {
					foreach ( $v as $v2 ) {
						if ( $v2 ) {
							continue 2;
						}
					}
					// All empty.
					unset( $the_matrix[ $id ][ $k ] );
				}
			}

			// Don't allow repeated cookie names.
			if ( self::O_CRAWLER_COOKIES === $id ) {
				$existed = [];
				foreach ( $the_matrix[ $id ] as $k => $v ) {
					if ( empty( $v[ self::CRWL_COOKIE_NAME ] ) || in_array( $v[ self::CRWL_COOKIE_NAME ], $existed, true ) ) {
						// Filter repeated or empty name.
						unset( $the_matrix[ $id ][ $k ] );
						continue;
					}

					$existed[] = $v[ self::CRWL_COOKIE_NAME ];
				}
			}

			// tmp fix the 3rd part woo update hook issue when enabling vary cookie.
			if ( 'wc_cart_vary' === $id ) {
				if ( $data ) {
					add_filter(
						'litespeed_vary_cookies',
						function ( $arr ) {
							$arr[] = 'woocommerce_cart_hash';
							return array_unique( $arr );
						}
					);
				} else {
					add_filter(
						'litespeed_vary_cookies',
						function ( $arr ) {
							$key = array_search( 'woocommerce_cart_hash', $arr, true );
							if ( false !== $key ) {
								unset( $arr[ $key ] );
							}
							return array_unique( $arr );
						}
					);
				}
			}
		}

		// id validation will be inside.
		$this->cls( 'Conf' )->update_confs( $the_matrix );

		$msg = __( 'Options saved.', 'litespeed-cache' );
		Admin_Display::success( $msg );
	}

	/**
	 * Parses any changes made by the network admin on the network settings.
	 *
	 * @since 3.0
	 *
	 * @param array<string,mixed> $raw_data Raw data from request/CLI.
	 * @return void
	 */
	public function network_save( $raw_data ) {
		self::debug( 'network saving' );

		if ( empty( $raw_data[ self::ENROLL ] ) ) {
			wp_die( esc_html__( 'No fields', 'litespeed-cache' ) );
		}

		$raw_data = Admin::cleanup_text( $raw_data );

		foreach ( array_unique( $raw_data[ self::ENROLL ] ) as $id ) {
			// Append current field to setting save.
			if ( ! array_key_exists( $id, self::$_default_site_options ) ) {
				continue;
			}

			$data = ! empty( $raw_data[ $id ] ) ? $raw_data[ $id ] : false;

			// id validation will be inside.
			$this->cls( 'Conf' )->network_update( $id, $data );
		}

		// Update related files.
		Activation::cls()->update_files();

		$msg = __( 'Options saved.', 'litespeed-cache' );
		Admin_Display::success( $msg );
	}

	/**
	 * Hooked to the wp_redirect filter when saving widgets fails validation.
	 *
	 * @since 1.1.3
	 *
	 * @param string $location The redirect location.
	 * @return string Updated location string.
	 */
	public static function widget_save_err( $location ) {
		return str_replace( '?message=0', '?error=0', $location );
	}

	/**
	 * Validate the LiteSpeed Cache settings on widget save.
	 *
	 * @since 1.1.3
	 *
	 * @param array      $instance     The new settings.
	 * @param array      $new_instance The raw submitted settings.
	 * @param array      $old_instance The original settings.
	 * @param \WP_Widget $widget       The widget instance.
	 * @return array|false Updated settings on success, false on error.
	 */
	public static function validate_widget_save( $instance, $new_instance, $old_instance, $widget ) {
		if ( empty( $new_instance ) ) {
			return $instance;
		}

		if ( ! isset( $new_instance[ ESI::WIDGET_O_ESIENABLE ], $new_instance[ ESI::WIDGET_O_TTL ] ) ) {
			return $instance;
		}

		$esi = (int) $new_instance[ ESI::WIDGET_O_ESIENABLE ] % 3;
		$ttl = (int) $new_instance[ ESI::WIDGET_O_TTL ];

		if ( 0 !== $ttl && $ttl < 30 ) {
			add_filter( 'wp_redirect', __CLASS__ . '::widget_save_err' );
			return false; // Invalid ttl.
		}

		if ( empty( $instance[ Conf::OPTION_NAME ] ) ) {
			// @todo to be removed.
			$instance[ Conf::OPTION_NAME ] = [];
		}
		$instance[ Conf::OPTION_NAME ][ ESI::WIDGET_O_ESIENABLE ] = $esi;
		$instance[ Conf::OPTION_NAME ][ ESI::WIDGET_O_TTL ]       = $ttl;

		$current = ! empty( $old_instance[ Conf::OPTION_NAME ] ) ? $old_instance[ Conf::OPTION_NAME ] : false;

		// Avoid unsanitized superglobal usage.
		$referrer = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

		// Only purge when not in the Customizer.
		if ( false === strpos( $referrer, '/wp-admin/customize.php' ) ) {
			if ( ! $current || $esi !== (int) $current[ ESI::WIDGET_O_ESIENABLE ] ) {
				Purge::purge_all( 'Widget ESI_enable changed' );
			} elseif ( 0 !== $ttl && $ttl !== (int) $current[ ESI::WIDGET_O_TTL ] ) {
				Purge::add( Tag::TYPE_WIDGET . $widget->id );
			}

			Purge::purge_all( 'Widget saved' );
		}

		return $instance;
	}
}
