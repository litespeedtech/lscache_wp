<?php
/**
 * The class to operate media data.
 *
 * @package LiteSpeed
 * @since   1.4
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Class Media
 *
 * Handles media-related optimizations like lazy loading, next-gen image replacement, and admin UI.
 */
class Media extends Root {

	const LOG_TAG = '📺';

	const LIB_FILE_IMG_LAZYLOAD = 'assets/js/lazyload.min.js';

	/**
	 * Current page buffer content.
	 *
	 * @var string
	 */
	private $content;

	/**
	 * WordPress uploads directory info.
	 *
	 * @var array
	 */
	private $_wp_upload_dir;

	/**
	 * List of VPI (viewport images) to preload in <head>.
	 *
	 * @var array
	 */
	private $_vpi_preload_list = [];

	/**
	 * The user-level next-gen format supported (''|webp|avif).
	 *
	 * @var string
	 */
	private $_format = '';

	/**
	 * The system-level chosen next-gen format (webp|avif).
	 *
	 * @var string
	 */
	private $_sys_format = '';

	/**
	 * Init.
	 *
	 * @since 1.4
	 */
	public function __construct() {
		self::debug2( 'init' );

		$this->_wp_upload_dir = wp_upload_dir();
		if ( $this->conf( Base::O_IMG_OPTM_WEBP ) ) {
			$this->_sys_format = 'webp';
			$this->_format     = 'webp';
			if ( 2 === $this->conf( Base::O_IMG_OPTM_WEBP ) ) {
				$this->_sys_format = 'avif';
				$this->_format     = 'avif';
			}
			if ( ! $this->_browser_support_next_gen() ) {
				$this->_format = '';
			}
			$this->_format = apply_filters( 'litespeed_next_gen_format', $this->_format );
		}
	}

	/**
	 * Hooks after user init.
	 *
	 * @since 7.2
	 * @since 7.4 Add media replace original with scaled.
	 * @return void
	 */
	public function after_user_init() {
		// Hook to attachment delete action (PR#844, Issue#841) for AJAX del compatibility.
		add_action( 'delete_attachment', array( $this, 'delete_attachment' ), 11, 2 );

		// For big images, allow to replace original with scaled image.
		if ( $this->conf( Base::O_MEDIA_AUTO_RESCALE_ORI ) ) {
			// Added priority 9 to happen before other functions added.
			add_filter( 'wp_update_attachment_metadata', array( $this, 'rescale_ori' ), 9, 2 );
		}
	}

	/**
	 * Init optm features.
	 *
	 * @since  3.0
	 * @access public
	 * @return void
	 */
	public function init() {
		if ( is_admin() ) {
			return;
		}

		// Due to ajax call doesn't send correct accept header, have to limit webp to HTML only.
		if ( $this->webp_support() ) {
			// Hook to srcset.
			if ( function_exists( 'wp_calculate_image_srcset' ) ) {
				add_filter( 'wp_calculate_image_srcset', array( $this, 'webp_srcset' ), 988 );
			}
			// Hook to mime icon
			// add_filter( 'wp_get_attachment_image_src', array( $this, 'webp_attach_img_src' ), 988 );// todo: need to check why not
			// add_filter( 'wp_get_attachment_url', array( $this, 'webp_url' ), 988 ); // disabled to avoid wp-admin display
		}

		if ( $this->conf( Base::O_MEDIA_LAZY ) && ! $this->cls( 'Metabox' )->setting( 'litespeed_no_image_lazy' ) ) {
			self::debug( 'Suppress default WP lazyload' );
			add_filter( 'wp_lazy_loading_enabled', '__return_false' );
		}

		/**
		 * Replace gravatar.
		 *
		 * @since 3.0
		 */
		$this->cls( 'Avatar' );

		add_filter( 'litespeed_buffer_finalize', array( $this, 'finalize' ), 4 );
		add_filter( 'litespeed_optm_html_head', array( $this, 'finalize_head' ) );
	}

	/**
	 * Handle attachment create (rescale original).
	 *
	 * @param array $metadata      Current meta array.
	 * @param int   $attachment_id Attachment ID.
	 * @return array Modified metadata.
	 * @since 7.4
	 */
	public function rescale_ori( $metadata, $attachment_id ) {
		// Test if create and image was resized.
		if ( $metadata && isset( $metadata['original_image'], $metadata['file'] ) && false !== strpos( $metadata['file'], '-scaled' ) ) {
			// Get rescaled file name.
			$path_exploded      = explode( '/', strrev( $metadata['file'] ), 2 );
			$rescaled_file_name = strrev( $path_exploded[0] );

			// Create paths for images: resized and original.
			$base_path     = $this->_wp_upload_dir['basedir'] . $this->_wp_upload_dir['subdir'] . '/';
			$rescaled_path = $base_path . $rescaled_file_name;
			$new_path      = $base_path . $metadata['original_image'];

			// Change array file key.
			$metadata['file'] = $this->_wp_upload_dir['subdir'] . '/' . $metadata['original_image'];
			if ( 0 === strpos( $metadata['file'], '/' ) ) {
				$metadata['file'] = substr( $metadata['file'], 1 );
			}

			// Delete array "original_image" key.
			unset( $metadata['original_image'] );

			if ( file_exists( $rescaled_path ) && file_exists( $new_path ) ) {
				// Move rescaled to original using WP_Filesystem.
				global $wp_filesystem;
				if ( ! $wp_filesystem ) {
					require_once ABSPATH . '/wp-admin/includes/file.php';
					\WP_Filesystem();
				}
				if ( $wp_filesystem ) {
					$wp_filesystem->move( $rescaled_path, $new_path, true );
				}

				// Update meta "_wp_attached_file".
				update_post_meta( $attachment_id, '_wp_attached_file', $metadata['file'] );
			}
		}

		return $metadata;
	}

	/**
	 * Add featured image and VPI preloads to head.
	 *
	 * @param string $content Current head HTML.
	 * @return string Modified head HTML.
	 */
	public function finalize_head( $content ) {
		// <link rel="preload" as="image" href="xx">
		if ( $this->_vpi_preload_list ) {
			foreach ( $this->_vpi_preload_list as $v ) {
				$content .= '<link rel="preload" as="image" href="' . esc_url( Str::trim_quotes( $v ) ) . '">';
			}
		}
		return $content;
	}

	/**
	 * Adjust WP default JPG quality.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @param int $quality Current quality.
	 * @return int Adjusted quality.
	 */
	public function adjust_jpg_quality( $quality ) {
		$v = $this->conf( Base::O_IMG_OPTM_JPG_QUALITY );

		if ( $v ) {
			return $v;
		}

		return $quality;
	}

	/**
	 * Register admin menu.
	 *
	 * @since 1.6.3
	 * @access public
	 * @return void
	 */
	public function after_admin_init() {
		/**
		 * JPG quality control.
		 *
		 * @since 3.0
		 */
		add_filter( 'jpeg_quality', array( $this, 'adjust_jpg_quality' ) );

		add_filter( 'manage_media_columns', array( $this, 'media_row_title' ) );
		add_filter( 'manage_media_custom_column', array( $this, 'media_row_actions' ), 10, 2 );

		add_action( 'litespeed_media_row', array( $this, 'media_row_con' ) );
	}

	/**
	 * Media delete action hook.
	 *
	 * @since  2.4.3
	 * @access public
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function delete_attachment( $post_id ) {
		self::debug( 'delete_attachment [pid] ' . $post_id );
		Img_Optm::cls()->reset_row( $post_id );
	}

	/**
	 * Return media file info if exists.
	 *
	 * This is for remote attachment plugins.
	 *
	 * @since  2.9.8
	 * @access public
	 *
	 * @param string $short_file_path Relative file path under uploads.
	 * @param int    $post_id         Post ID.
	 * @return array|false Array( url, md5, size ) or false.
	 */
	public function info( $short_file_path, $post_id ) {
		$short_file_path = wp_normalize_path( $short_file_path );
		$basedir         = $this->_wp_upload_dir['basedir'] . '/';
		if ( 0 === strpos( $short_file_path, $basedir ) ) {
			$short_file_path = substr( $short_file_path, strlen( $basedir ) );
		}

		$real_file = $basedir . $short_file_path;

		if ( file_exists( $real_file ) ) {
			return array(
				'url'  => $this->_wp_upload_dir['baseurl'] . '/' . $short_file_path,
				'md5'  => md5_file( $real_file ),
				'size' => filesize( $real_file ),
			);
		}

		/**
		 * WP Stateless compatibility #143 https://github.com/litespeedtech/lscache_wp/issues/143
		 *
		 * @since 2.9.8
		 * Should return array( 'url', 'md5', 'size' ).
		 */
		$info = apply_filters( 'litespeed_media_info', [], $short_file_path, $post_id );
		if ( ! empty( $info['url'] ) && ! empty( $info['md5'] ) && ! empty( $info['size'] ) ) {
			return $info;
		}

		return false;
	}

	/**
	 * Delete media file.
	 *
	 * @since  2.9.8
	 * @access public
	 *
	 * @param string $short_file_path Relative file path under uploads.
	 * @param int    $post_id         Post ID.
	 * @return void
	 */
	public function del( $short_file_path, $post_id ) {
		$real_file = $this->_wp_upload_dir['basedir'] . '/' . $short_file_path;

		if ( file_exists( $real_file ) ) {
			wp_delete_file( $real_file );
			self::debug( 'deleted ' . $real_file );
		}

		do_action( 'litespeed_media_del', $short_file_path, $post_id );
	}

	/**
	 * Rename media file.
	 *
	 * @since  2.9.8
	 * @access public
	 *
	 * @param string $short_file_path     Old relative path.
	 * @param string $short_file_path_new New relative path.
	 * @param int    $post_id             Post ID.
	 * @return void
	 */
	public function rename( $short_file_path, $short_file_path_new, $post_id ) {
		$real_file     = $this->_wp_upload_dir['basedir'] . '/' . $short_file_path;
		$real_file_new = $this->_wp_upload_dir['basedir'] . '/' . $short_file_path_new;

		if ( file_exists( $real_file ) ) {
			global $wp_filesystem;
			if ( ! $wp_filesystem ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				\WP_Filesystem();
			}
			if ( $wp_filesystem ) {
				$wp_filesystem->move( $real_file, $real_file_new, true );
			}
			self::debug( 'renamed ' . $real_file . ' to ' . $real_file_new );
		}

		do_action( 'litespeed_media_rename', $short_file_path, $short_file_path_new, $post_id );
	}

	/**
	 * Media Admin Menu -> Image Optimization Column Title.
	 *
	 * @since  1.6.3
	 * @access public
	 *
	 * @param array $posts_columns Existing columns.
	 * @return array Modified columns.
	 */
	public function media_row_title( $posts_columns ) {
		$posts_columns['imgoptm'] = esc_html__( 'LiteSpeed Optimization', 'litespeed-cache' );
		return $posts_columns;
	}

	/**
	 * Media Admin Menu -> Image Optimization Column.
	 *
	 * @since  1.6.2
	 * @access public
	 *
	 * @param string $column_name Current column name.
	 * @param int    $post_id     Post ID.
	 * @return void
	 */
	public function media_row_actions( $column_name, $post_id ) {
		if ( 'imgoptm' !== $column_name ) {
			return;
		}

		do_action( 'litespeed_media_row', $post_id );
	}

	/**
	 * Display image optimization info in the media list row.
	 *
	 * @since 3.0
	 *
	 * @param int $post_id Attachment post ID.
	 * @return void
	 */
	public function media_row_con( $post_id ) {
		$att_info = wp_get_attachment_metadata( $post_id );
		if ( empty( $att_info['file'] ) ) {
			return;
		}

		$short_path = $att_info['file'];

		$size_meta = get_post_meta( $post_id, Img_Optm::DB_SIZE, true );

		echo '<p>';
		// Original image info.
		if ( $size_meta && ! empty( $size_meta['ori_saved'] ) ) {
			$percent = (int) ceil( ( (int) $size_meta['ori_saved'] * 100 ) / max( 1, (int) $size_meta['ori_total'] ) );

			$extension    = pathinfo( $short_path, PATHINFO_EXTENSION );
			$bk_file      = substr( $short_path, 0, -strlen( $extension ) ) . 'bk.' . $extension;
			$bk_optm_file = substr( $short_path, 0, -strlen( $extension ) ) . 'bk.optm.' . $extension;

			$link = Utility::build_url( Router::ACTION_IMG_OPTM, 'orig' . $post_id );
			$desc = false;

			$cls = '';

			if ( $this->info( $bk_file, $post_id ) ) {
				$curr_status = esc_html__( '(optm)', 'litespeed-cache' );
				$desc        = esc_attr__( 'Currently using optimized version of file.', 'litespeed-cache' ) . '&#10;' . esc_attr__( 'Click to switch to original (unoptimized) version.', 'litespeed-cache' );
			} elseif ( $this->info( $bk_optm_file, $post_id ) ) {
				$cls        .= ' litespeed-warning';
				$curr_status = esc_html__( '(non-optm)', 'litespeed-cache' );
				$desc        = esc_attr__( 'Currently using original (unoptimized) version of file.', 'litespeed-cache' ) . '&#10;' . esc_attr__( 'Click to switch to optimized version.', 'litespeed-cache' );
			}

			echo wp_kses_post(
				GUI::pie_tiny(
					$percent,
					24,
					sprintf(
						esc_html__( 'Original file reduced by %1$s (%2$s)', 'litespeed-cache' ),
						$percent . '%',
						Utility::real_size( $size_meta['ori_saved'] )
					),
					'left'
				)
			);

			printf(
				esc_html__( 'Orig saved %s', 'litespeed-cache' ),
				(int) $percent . '%'
			);

			if ( $desc ) {
				printf(
					' <a href="%1$s" class="litespeed-media-href %2$s" data-balloon-pos="left" data-balloon-break aria-label="%3$s">%4$s</a>',
					esc_url( $link ),
					esc_attr( $cls ),
					wp_kses_post( $desc ),
					esc_html( $curr_status )
				);
			} else {
				printf(
					' <span class="litespeed-desc" data-balloon-pos="left" data-balloon-break aria-label="%1$s">%2$s</span>',
					esc_attr__( 'Using optimized version of file. ', 'litespeed-cache' ) . '&#10;' . esc_attr__( 'No backup of original file exists.', 'litespeed-cache' ),
					esc_html__( '(optm)', 'litespeed-cache' )
				);
			}
		} elseif ( $size_meta && 0 === (int) $size_meta['ori_saved'] ) {
			echo wp_kses_post( GUI::pie_tiny( 0, 24, esc_html__( 'Congratulation! Your file was already optimized', 'litespeed-cache' ), 'left' ) );
			printf(
				esc_html__( 'Orig %s', 'litespeed-cache' ),
				'<span class="litespeed-desc">' . esc_html__( '(no savings)', 'litespeed-cache' ) . '</span>'
			);
		} else {
			echo esc_html__( 'Orig', 'litespeed-cache' ) . '<span class="litespeed-left10">—</span>';
		}
		echo '</p>';

		echo '<p>';
		// WebP/AVIF info.
		if ( $size_meta && $this->webp_support( true ) && ! empty( $size_meta[ $this->_sys_format . '_saved' ] ) ) {
			$is_avif         = 'avif' === $this->_sys_format;
			$size_meta_saved = $size_meta[ $this->_sys_format . '_saved' ];
			$size_meta_total = $size_meta[ $this->_sys_format . '_total' ];

			$percent = ceil( ( $size_meta_saved * 100 ) / max( 1, $size_meta_total ) );

			$link = Utility::build_url( Router::ACTION_IMG_OPTM, $this->_sys_format . $post_id );
			$desc = false;

			$cls = '';

			if ( $this->info( $short_path . '.' . $this->_sys_format, $post_id ) ) {
				$curr_status = esc_html__( '(optm)', 'litespeed-cache' );
				$desc        = $is_avif
					? esc_attr__( 'Currently using optimized version of AVIF file.', 'litespeed-cache' )
					: esc_attr__( 'Currently using optimized version of WebP file.', 'litespeed-cache' );
				$desc       .= '&#10;' . esc_attr__( 'Click to switch to original (unoptimized) version.', 'litespeed-cache' );
			} elseif ( $this->info( $short_path . '.optm.' . $this->_sys_format, $post_id ) ) {
				$cls        .= ' litespeed-warning';
				$curr_status = esc_html__( '(non-optm)', 'litespeed-cache' );
				$desc        = $is_avif
					? esc_attr__( 'Currently using original (unoptimized) version of AVIF file.', 'litespeed-cache' )
					: esc_attr__( 'Currently using original (unoptimized) version of WebP file.', 'litespeed-cache' );
				$desc       .= '&#10;' . esc_attr__( 'Click to switch to optimized version.', 'litespeed-cache' );
			}

			echo wp_kses_post(
				GUI::pie_tiny(
					$percent,
					24,
					sprintf(
						$is_avif ? esc_html__( 'AVIF file reduced by %1$s (%2$s)', 'litespeed-cache' ) : esc_html__( 'WebP file reduced by %1$s (%2$s)', 'litespeed-cache' ),
						$percent . '%',
						Utility::real_size( $size_meta_saved )
					),
					'left'
				)
			);
			printf(
				$is_avif ? esc_html__( 'AVIF saved %s', 'litespeed-cache' ) : esc_html__( 'WebP saved %s', 'litespeed-cache' ),
				'<span>' . esc_html( $percent ) . '%</span>'
			);

			if ( $desc ) {
				printf(
					' <a href="%1$s" class="litespeed-media-href %2$s" data-balloon-pos="left" data-balloon-break aria-label="%3$s">%4$s</a>',
					esc_url( $link ),
					esc_attr( $cls ),
					wp_kses_post( $desc ),
					esc_html( $curr_status )
				);
			} else {
				printf(
					' <span class="litespeed-desc" data-balloon-pos="left" data-balloon-break aria-label="%1$s&#10;%2$s">%3$s</span>',
					esc_attr__( 'Using optimized version of file. ', 'litespeed-cache' ),
					$is_avif ? esc_attr__( 'No backup of unoptimized AVIF file exists.', 'litespeed-cache' ) : esc_attr__( 'No backup of unoptimized WebP file exists.', 'litespeed-cache' ),
					esc_html__( '(optm)', 'litespeed-cache' )
				);
			}
		} else {
			echo esc_html( $this->next_gen_image_title() ) . '<span class="litespeed-left10">—</span>';
		}

		echo '</p>';

		// Delete row btn.
		if ( $size_meta ) {
			printf(
				'<div class="row-actions"><span class="delete"><a href="%1$s" class="">%2$s</a></span></div>',
				esc_url( Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_RESET_ROW, false, null, array( 'id' => $post_id ) ) ),
				esc_html__( 'Restore from backup', 'litespeed-cache' )
			);
			echo '</div>';
		}
	}

	/**
	 * Get wp size info.
	 *
	 * NOTE: this is not used because it has to be after admin_init.
	 *
	 * @since 1.6.2
	 * @return array $sizes Data for all currently-registered image sizes.
	 */
	public function get_image_sizes() {
		global $_wp_additional_image_sizes;
		$sizes = [];

		foreach ( get_intermediate_image_sizes() as $_size ) {
			if ( in_array( $_size, array( 'thumbnail', 'medium', 'medium_large', 'large' ), true ) ) {
				$sizes[ $_size ]['width']  = get_option( $_size . '_size_w' );
				$sizes[ $_size ]['height'] = get_option( $_size . '_size_h' );
				$sizes[ $_size ]['crop']   = (bool) get_option( $_size . '_crop' );
			} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
				$sizes[ $_size ] = array(
					'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
					'height' => $_wp_additional_image_sizes[ $_size ]['height'],
					'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
				);
			}
		}

		return $sizes;
	}

	/**
	 * Exclude role from optimization filter.
	 *
	 * @since  1.6.2
	 * @access public
	 *
	 * @param bool $sys_level Return system-level format if true.
	 * @return string Next-gen format name or empty string.
	 */
	public function webp_support( $sys_level = false ) {
		if ( $sys_level ) {
			return $this->_sys_format;
		}
		return $this->_format; // User level next gen support.
	}

	/**
	 * Detect if browser supports next-gen format.
	 *
	 * @return bool
	 */
	private function _browser_support_next_gen() {
		$accept = isset( $_SERVER['HTTP_ACCEPT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ) : '';
		if ( $accept ) {
			if ( false !== strpos( $accept, 'image/' . $this->_sys_format ) ) {
				return true;
			}
		}

		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		if ( $ua ) {
			$user_agents = array( 'chrome-lighthouse', 'googlebot', 'page speed' );
			foreach ( $user_agents as $user_agent ) {
				if ( false !== stripos( $ua, $user_agent ) ) {
					return true;
				}
			}

			if ( preg_match( '/iPhone OS (\d+)_/i', $ua, $matches ) ) {
				if ( $matches[1] >= 14 ) {
					return true;
				}
			}

			if ( preg_match( '/Firefox\/(\d+)/i', $ua, $matches ) ) {
				if ( $matches[1] >= 65 ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get next gen image title.
	 *
	 * @since 7.0
	 * @return string
	 */
	public function next_gen_image_title() {
		$next_gen_img = 'WebP';
		if ( 2 === $this->conf( Base::O_IMG_OPTM_WEBP ) ) {
			$next_gen_img = 'AVIF';
		}
		return $next_gen_img;
	}

	/**
	 * Run lazy load process.
	 * NOTE: As this is after cache finalized, can NOT set any cache control anymore.
	 *
	 * Only do for main page. Do NOT do for esi or dynamic content.
	 *
	 * @since  1.4
	 * @access public
	 *
	 * @param string $content Final buffer.
	 * @return string The buffer.
	 */
	public function finalize( $content ) {
		if ( defined( 'LITESPEED_NO_LAZY' ) ) {
			self::debug2( 'bypass: NO_LAZY const' );
			return $content;
		}

		if ( ! defined( 'LITESPEED_IS_HTML' ) ) {
			self::debug2( 'bypass: Not frontend HTML type' );
			return $content;
		}

		if ( ! Control::is_cacheable() ) {
			self::debug( 'bypass: Not cacheable' );
			return $content;
		}

		self::debug( 'finalize' );

		$this->content = $content;
		$this->_finalize();
		return $this->content;
	}

	/**
	 * Run lazyload replacement for images in buffer.
	 *
	 * @since  1.4
	 * @access private
	 * @return void
	 */
	private function _finalize() {
		/**
		 * Use webp for optimized images.
		 *
		 * @since 1.6.2
		 */
		if ( $this->webp_support() ) {
			$this->content = $this->_replace_buffer_img_webp( $this->content );
		}

		/**
		 * Check if URI is excluded.
		 *
		 * @since 3.0
		 */
		$excludes = $this->conf( Base::O_MEDIA_LAZY_URI_EXC );
		if ( ! defined( 'LITESPEED_GUEST_OPTM' ) ) {
			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$result      = $request_uri ? Utility::str_hit_array( $request_uri, $excludes ) : false;
			if ( $result ) {
				self::debug( 'bypass lazyload: hit URI Excludes setting: ' . $result );
				return;
			}
		}

		$cfg_lazy          = ( defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( Base::O_MEDIA_LAZY ) ) && ! $this->cls( 'Metabox' )->setting( 'litespeed_no_image_lazy' );
		$cfg_iframe_lazy   = defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( Base::O_MEDIA_IFRAME_LAZY );
		$cfg_js_delay      = defined( 'LITESPEED_GUEST_OPTM' ) || 2 === $this->conf( Base::O_OPTM_JS_DEFER );
		$cfg_trim_noscript = defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( Base::O_OPTM_NOSCRIPT_RM );
		$cfg_vpi           = defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( Base::O_MEDIA_VPI );

		// Preload VPI.
		if ( $cfg_vpi ) {
			$this->_parse_img_for_preload();
		}

		if ( $cfg_lazy ) {
			if ( $cfg_vpi ) {
				add_filter( 'litespeed_media_lazy_img_excludes', array( $this->cls( 'Metabox' ), 'lazy_img_excludes' ) );
			}
			list( $src_list, $html_list, $placeholder_list ) = $this->_parse_img();
			$html_list_ori                                   = $html_list;
		} else {
			self::debug( 'lazyload disabled' );
		}

		// image lazy load.
		if ( $cfg_lazy ) {
			$__placeholder = Placeholder::cls();

			foreach ( $html_list as $k => $v ) {
				$size = $placeholder_list[ $k ];
				$src  = $src_list[ $k ];

				$html_list[ $k ] = $__placeholder->replace( $v, $src, $size );
			}
		}

		if ( $cfg_lazy ) {
			$this->content = str_replace( $html_list_ori, $html_list, $this->content );
		}

		// iframe lazy load.
		if ( $cfg_iframe_lazy ) {
			$html_list     = $this->_parse_iframe();
			$html_list_ori = $html_list;

			foreach ( $html_list as $k => $v ) {
				$snippet = $cfg_trim_noscript ? '' : '<noscript>' . $v . '</noscript>';
				if ( $cfg_js_delay ) {
					$v = str_replace( ' src=', ' data-litespeed-src=', $v );
				} else {
					$v = str_replace( ' src=', ' data-src=', $v );
				}
				$v       = str_replace( '<iframe ', '<iframe data-lazyloaded="1" src="about:blank" ', $v );
				$snippet = $v . $snippet;

				$html_list[ $k ] = $snippet;
			}

			$this->content = str_replace( $html_list_ori, $html_list, $this->content );
		}

		// Include lazyload lib js and init lazyload.
		if ( $cfg_lazy || $cfg_iframe_lazy ) {
			$lazy_lib = '<script data-no-optimize="1">window.lazyLoadOptions=Object.assign({},{threshold:' . apply_filters( 'litespeed_lazyload_threshold', 300 ) . '},window.lazyLoadOptions||{});' . File::read( LSCWP_DIR . self::LIB_FILE_IMG_LAZYLOAD ) . '</script>';
			if ( $cfg_js_delay ) {
				// Load JS delay lib.
				if ( ! defined( 'LITESPEED_JS_DELAY_LIB_LOADED' ) ) {
					define( 'LITESPEED_JS_DELAY_LIB_LOADED', true );
					$lazy_lib .= '<script data-no-optimize="1">' . File::read( LSCWP_DIR . Optimize::LIB_FILE_JS_DELAY ) . '</script>';
				}
			}

			$this->content = str_replace( '</body>', $lazy_lib . '</body>', $this->content );
		}
	}

	/**
	 * Parse img src for VPI preload only.
	 * Note: Didn't reuse the _parse_img() because it contains replacement logic which is not needed for preload.
	 *
	 * @since 6.2
	 * @since 7.6 - Added attributes fetchpriority="high" and decode="sync" for VPI images.
	 * @return void
	 */
	private function _parse_img_for_preload() {
		// Load VPI setting.
		$is_mobile = $this->_separate_mobile();
		$vpi_files = $this->cls( 'Metabox' )->setting( $is_mobile ? VPI::POST_META_MOBILE : VPI::POST_META );
		if ( $vpi_files ) {
			$vpi_files = Utility::sanitize_lines( $vpi_files, 'basename' );
		}
		if ( ! $vpi_files ) {
			return;
		}
		if ( ! $this->content ) {
			return;
		}

		$content = preg_replace( array( '#<!--.*-->#sU', '#<noscript([^>]*)>.*</noscript>#isU' ), '', $this->content );
		if ( ! $content ) {
			return;
		}

		$vpi_fp_search  = [];
		$vpi_fp_replace = [];
		preg_match_all('#<img\s+([^>]+)/?>#isU', $content, $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$attrs = Utility::parse_attr($match[1]);

			if ( empty( $attrs['src'] ) ) {
				continue;
			}

			if ( false !== strpos( $attrs['src'], 'base64' ) || 0 === strpos( $attrs['src'], 'data:' ) ) {
				self::debug2( 'lazyload bypassed base64 img' );
				continue;
			}

			if ( false !== strpos( $attrs['src'], '{' ) ) {
				self::debug2( 'image src has {} ' . $attrs['src'] );
				continue;
			}

			// If the src contains VPI filename, then preload it.
			if ( ! Utility::str_hit_array( $attrs['src'], $vpi_files ) ) {
				continue;
			}

			self::debug2( 'VPI preload found and matched: ' . $attrs['src'] );

			$this->_vpi_preload_list[] = $attrs['src'];

			// Add attributes fetchpriority="high" and decode="sync"
			// after WP 6.3.0 use: wp_img_tag_add_loading_optimization_attrs().
			$new_html                 = [];
			$attrs[ 'fetchpriority' ] = 'high';
			$attrs[ 'decoding' ]      = 'sync';
			// create html with new attributes.
			foreach ( $attrs as $k => $attr ) {
				$new_html[] = $k . '="' . $attr . '"';
			}

			if ( $new_html ) {
				$vpi_fp_search[]  = $match[1];
				$vpi_fp_replace[] = implode( ' ', $new_html);
			}
		}

		// if VPI fetchpriority changes, do the replacement
		if ( $vpi_fp_search && $vpi_fp_replace ) {
			$this->content = str_replace( $vpi_fp_search, $vpi_fp_replace, $this->content );
		}
		unset( $vpi_fp_search );
		unset( $vpi_fp_replace );
	}

	/**
	 * Parse img src.
	 *
	 * @since  1.4
	 * @access private
	 * @return array{0:array,1:array,2:array}  All the src & related raw html list with placeholders.
	 */
	private function _parse_img() {
		/**
		 * Exclude list.
		 *
		 * @since 1.5
		 * @since 2.7.1 Changed to array.
		 */
		$excludes = apply_filters( 'litespeed_media_lazy_img_excludes', $this->conf( Base::O_MEDIA_LAZY_EXC ) );

		$cls_excludes   = apply_filters( 'litespeed_media_lazy_img_cls_excludes', $this->conf( Base::O_MEDIA_LAZY_CLS_EXC ) );
		$cls_excludes[] = 'skip-lazy'; // https://core.trac.wordpress.org/ticket/44427

		$src_list         = [];
		$html_list        = [];
		$placeholder_list = [];

		$content = preg_replace(
			array(
				'#<!--.*-->#sU',
				'#<noscript([^>]*)>.*</noscript>#isU',
				'#<script([^>]*)>.*</script>#isU', // Remove script to avoid false matches and warnings, when image size detection is turned ON.
			),
			'',
			$this->content
		);
		/**
		 * Exclude parent classes.
		 *
		 * @since 3.0
		 */
		$parent_cls_exc = apply_filters( 'litespeed_media_lazy_img_parent_cls_excludes', $this->conf( Base::O_MEDIA_LAZY_PARENT_CLS_EXC ) );
		if ( $parent_cls_exc ) {
			self::debug2( 'Lazyload Class excludes', $parent_cls_exc );
			foreach ( $parent_cls_exc as $v ) {
				$content = preg_replace('#<(\w+) [^>]*class=(\'|")[^\'"]*' . preg_quote($v, '#') . '[^\'"]*\2[^>]*>.*</\1>#sU', '', $content);
			}
		}

		preg_match_all( '#<img\s+([^>]+)/?>#isU', $content, $matches, PREG_SET_ORDER );
		foreach ( $matches as $match ) {
			$attrs = Utility::parse_attr( $match[1] );

			if ( empty( $attrs['src'] ) ) {
				continue;
			}

			/**
			 * Add src validation to bypass base64 img src.
			 *
			 * @since 1.6
			 */
			if ( false !== strpos( $attrs['src'], 'base64' ) || 0 === strpos( $attrs['src'], 'data:' ) ) {
				self::debug2( 'lazyload bypassed base64 img' );
				continue;
			}

			self::debug2( 'lazyload found: ' . $attrs['src'] );

			if (
				! empty( $attrs['data-no-lazy'] ) ||
				! empty( $attrs['data-skip-lazy'] ) ||
				! empty( $attrs['data-lazyloaded'] ) ||
				! empty( $attrs['data-src'] ) ||
				! empty( $attrs['data-srcset'] )
			) {
				self::debug2( 'bypassed' );
				continue;
			}

			$hit = ! empty( $attrs['class'] ) ? Utility::str_hit_array( $attrs['class'], $cls_excludes ) : false;
			if ( $hit ) {
				self::debug2( 'lazyload image cls excludes [hit] ' . $hit );
				continue;
			}

			/**
			 * Exclude from lazyload by setting.
			 *
			 * @since 1.5
			 */
			if ( $excludes && Utility::str_hit_array( $attrs['src'], $excludes ) ) {
				self::debug2( 'lazyload image exclude ' . $attrs['src'] );
				continue;
			}

			/**
			 * Excludes invalid image src from buddypress avatar crop.
			 *
			 * @see https://wordpress.org/support/topic/lazy-load-breaking-buddypress-upload-avatar-feature
			 * @since 3.0
			 */
			if ( false !== strpos( $attrs['src'], '{' ) ) {
				self::debug2( 'image src has {} ' . $attrs['src'] );
				continue;
			}

			// to avoid multiple replacement.
			if ( in_array( $match[0], $html_list, true ) ) {
				continue;
			}

			// Add missing dimensions.
			if ( defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( Base::O_MEDIA_ADD_MISSING_SIZES ) ) {
				if ( ! apply_filters( 'litespeed_media_add_missing_sizes', true ) ) {
					self::debug2( 'add_missing_sizes bypassed via litespeed_media_add_missing_sizes filter' );
				} elseif ( empty( $attrs['width'] ) || 'auto' === $attrs['width'] || empty( $attrs['height'] ) || 'auto' === $attrs['height'] ) {
					self::debug( '⚠️ Missing sizes for image [src] ' . $attrs['src'] );
					$dimensions = $this->_detect_dimensions( $attrs['src'] );
					if ( $dimensions ) {
						$ori_width  = $dimensions[0];
						$ori_height = $dimensions[1];
						// Calculate height based on width.
						if ( ! empty( $attrs['width'] ) && 'auto' !== $attrs['width'] ) {
							$ori_height = (int) ( ( $ori_height * (int) $attrs['width'] ) / max( 1, $ori_width ) );
						} elseif ( ! empty( $attrs['height'] ) && 'auto' !== $attrs['height'] ) {
							$ori_width = (int) ( ( $ori_width * (int) $attrs['height'] ) / max( 1, $ori_height ) );
						}

						$attrs['width']  = $ori_width;
						$attrs['height'] = $ori_height;
						$new_html        = preg_replace( '#\s+(width|height)=(["\'])[^\2]*?\2#', '', $match[0] );
						$new_html        = preg_replace(
							'#<img\s+#i',
							'<img width="' . Str::trim_quotes( $attrs['width'] ) . '" height="' . Str::trim_quotes( $attrs['height'] ) . '" ',
							$new_html
						);
						self::debug( 'Add missing sizes ' . $attrs['width'] . 'x' . $attrs['height'] . ' to ' . $attrs['src'] );
						$this->content = str_replace( $match[0], $new_html, $this->content );
						$match[0]      = $new_html;
					}
				}
			}

			$placeholder = false;
			if ( ! empty( $attrs['width'] ) && 'auto' !== $attrs['width'] && ! empty( $attrs['height'] ) && 'auto' !== $attrs['height'] ) {
				$placeholder = (int) $attrs['width'] . 'x' . (int) $attrs['height'];
			}

			$src_list[]         = $attrs['src'];
			$html_list[]        = $match[0];
			$placeholder_list[] = $placeholder;
		}

		return array( $src_list, $html_list, $placeholder_list );
	}

	/**
	 * Detect the original sizes.
	 *
	 * @since 4.0
	 *
	 * @param string $src Source URL/path.
	 * @return array|false getimagesize array or false.
	 */
	private function _detect_dimensions( $src ) {
		$pathinfo = Utility::is_internal_file( $src );
		if ( $pathinfo ) {
			$src = $pathinfo[0];
		} elseif ( apply_filters( 'litespeed_media_ignore_remote_missing_sizes', false ) ) {
			return false;
		}

		if ( 0 === strpos( $src, '//' ) ) {
			$src = 'https:' . $src;
		}

		try {
			$sizes = getimagesize( $src );
		} catch ( \Exception $e ) {
			return false;
		}

		if ( ! empty( $sizes[0] ) && ! empty( $sizes[1] ) ) {
			return $sizes;
		}

		return false;
	}

	/**
	 * Parse iframe src.
	 *
	 * @since  1.4
	 * @access private
	 * @return array All the related raw html list (full <iframe> tags).
	 */
	private function _parse_iframe() {
		$cls_excludes   = apply_filters( 'litespeed_media_iframe_lazy_cls_excludes', $this->conf( Base::O_MEDIA_IFRAME_LAZY_CLS_EXC ) );
		$cls_excludes[] = 'skip-lazy'; // https://core.trac.wordpress.org/ticket/44427

		$html_list = [];

		$content = preg_replace( '#<!--.*-->#sU', '', $this->content );

		/**
		 * Exclude parent classes.
		 *
		 * @since 3.0
		 */
		$parent_cls_exc = apply_filters( 'litespeed_media_iframe_lazy_parent_cls_excludes', $this->conf( Base::O_MEDIA_IFRAME_LAZY_PARENT_CLS_EXC ) );
		if ( $parent_cls_exc ) {
			self::debug2( 'Iframe Lazyload Class excludes', $parent_cls_exc );
			foreach ( $parent_cls_exc as $v ) {
				$content = preg_replace('#<(\w+) [^>]*class=(\'|")[^\'"]*' . preg_quote($v, '#') . '[^\'"]*\2[^>]*>.*</\1>#sU', '', $content);
			}
		}

		preg_match_all( '#<iframe \s*([^>]+)></iframe>#isU', $content, $matches, PREG_SET_ORDER );
		foreach ( $matches as $match ) {
			$attrs = Utility::parse_attr( $match[1] );

			if ( empty( $attrs['src'] ) ) {
				continue;
			}

			self::debug2( 'found iframe: ' . $attrs['src'] );

			if ( ! empty( $attrs['data-no-lazy'] ) || ! empty( $attrs['data-skip-lazy'] ) || ! empty( $attrs['data-lazyloaded'] ) || ! empty( $attrs['data-src'] ) ) {
				self::debug2( 'bypassed' );
				continue;
			}

			$hit = ! empty( $attrs['class'] ) ? Utility::str_hit_array( $attrs['class'], $cls_excludes ) : false;
			if ( $hit ) {
				self::debug2( 'iframe lazyload cls excludes [hit] ' . $hit );
				continue;
			}

			if ( apply_filters( 'litespeed_iframe_lazyload_exc', false, $attrs['src'] ) ) {
				self::debug2( 'bypassed by filter' );
				continue;
			}

			// to avoid multiple replacement.
			if ( in_array( $match[0], $html_list, true ) ) {
				continue;
			}

			$html_list[] = $match[0];
		}

		return $html_list;
	}

	/**
	 * Replace image src to webp/avif in buffer.
	 *
	 * @since  1.6.2
	 * @access private
	 *
	 * @param string $content HTML content.
	 * @return string Modified content.
	 */
	private function _replace_buffer_img_webp( $content ) {
		/**
		 * Added custom element & attribute support.
		 *
		 * @since 2.2.2
		 */
		$webp_ele_to_check = $this->conf( Base::O_IMG_OPTM_WEBP_ATTR );

		foreach ( $webp_ele_to_check as $v ) {
			if ( ! $v || false === strpos( $v, '.' ) ) {
				self::debug2( 'buffer_webp no . attribute ' . $v );
				continue;
			}

			self::debug2( 'buffer_webp attribute ' . $v );

			$v    = explode( '.', $v );
			$attr = preg_quote( $v[1], '#' );
			if ( $v[0] ) {
				$pattern = '#<' . preg_quote( $v[0], '#' ) . '([^>]+)' . $attr . '=([\'"])(.+)\2#iU';
			} else {
				$pattern = '# ' . $attr . '=([\'"])(.+)\1#iU';
			}

			preg_match_all( $pattern, $content, $matches );

			foreach ( $matches[ $v[0] ? 3 : 2 ] as $k2 => $url ) {
				// Check if is a DATA-URI.
				if ( false !== strpos( $url, 'data:image' ) ) {
					continue;
				}

				$url2 = $this->replace_webp( $url );
				if ( ! $url2 ) {
					continue;
				}

				if ( $v[0] ) {
					$html_snippet = sprintf( '<' . $v[0] . '%1$s' . $v[1] . '=%2$s', $matches[1][ $k2 ], $matches[2][ $k2 ] . $url2 . $matches[2][ $k2 ] );
				} else {
					$html_snippet = sprintf( ' ' . $v[1] . '=%1$s', $matches[1][ $k2 ] . $url2 . $matches[1][ $k2 ] );
				}

				$content = str_replace( $matches[0][ $k2 ], $html_snippet, $content );
			}
		}

		// parse srcset.
		// todo: should apply this to cdn too.
		if ( ( defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( Base::O_IMG_OPTM_WEBP_REPLACE_SRCSET ) ) && $this->webp_support() ) {
			$content = Utility::srcset_replace( $content, array( $this, 'replace_webp' ) );
		}

		// Replace background-image.
		if ( ( defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( Base::O_IMG_OPTM_WEBP ) ) && $this->webp_support() ) {
			$content = $this->replace_background_webp( $content );
		}

		return $content;
	}

	/**
	 * Replace background image in inline styles and JSON blobs.
	 *
	 * @since 4.0
	 *
	 * @param string $content HTML content.
	 * @return string Modified content.
	 */
	public function replace_background_webp( $content ) {
		self::debug2( 'Start replacing background WebP/AVIF.' );

		// Handle Elementor's data-settings JSON encoded background-images.
		$content = $this->replace_urls_in_json( $content );

		preg_match_all( '#url\(([^)]+)\)#iU', $content, $matches );
		foreach ( $matches[1] as $k => $url ) {
			// Check if is a DATA-URI.
			if ( false !== strpos( $url, 'data:image' ) ) {
				continue;
			}

			/**
			 * Support quotes in src `background-image: url('src')`.
			 *
			 * @since 2.9.3
			 */
			$url = trim( $url, '\'"' );

			// Fix Elementor's Slideshow unusual background images like  style="background-image: url(&quot;https://xxxx.png&quot;);"
			if ( 0 === strpos( $url, '&quot;' ) && '&quot;' === substr( $url, -6 ) ) {
				$url = substr( $url, 6, -6 );
			}

			$url2 = $this->replace_webp( $url );
			if ( ! $url2 ) {
				continue;
			}

			$html_snippet = str_replace( $url, $url2, $matches[0][ $k ] );
			$content      = str_replace( $matches[0][ $k ], $html_snippet, $content );
		}

		return $content;
	}

	/**
	 * Replace images in json data settings attributes.
	 *
	 * @since 6.2
	 *
	 * @param string $content HTML content to scan and modify.
	 * @return string Modified content with replaced URLs inside JSON attributes.
	 */
	public function replace_urls_in_json( $content ) {
		$pattern      = '/data-settings="(.*?)"/i';
		$parent_class = $this;

		preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER );

		foreach ( $matches as $match ) {
			// Check if the string contains HTML entities.
			$is_encoded = preg_match( '/&quot;|&lt;|&gt;|&amp;|&apos;/', $match[1] );

			// Decode HTML entities in the JSON string.
			$json_string = html_entity_decode( $match[1] );

			$json_data = \json_decode( $json_string, true );

			if ( JSON_ERROR_NONE === json_last_error() && is_array( $json_data ) ) {
				$did_webp_replace = false;

				array_walk_recursive(
					$json_data,
                    /**
                     * Replace URLs in JSON data recursively.
                     *
                     * @param mixed  $item Value (modified in place).
                     * @param string $key  Array key.
                     */
					function ( &$item, $key ) use ( &$did_webp_replace, $parent_class ) {
						if ( 'url' === $key ) {
							$item_image = $parent_class->replace_webp( $item );
							if ( $item_image ) {
								$item             = $item_image;
								$did_webp_replace = true;
							}
						}
					}
				);

				if ( $did_webp_replace ) {
					// Re-encode the modified array back to a JSON string.
					$new_json_string = wp_json_encode( $json_data );

					// Re-encode the JSON string to HTML entities only if it was originally encoded.
					if ( $is_encoded ) {
						$new_json_string = htmlspecialchars( $new_json_string, ENT_QUOTES | 0 ); // ENT_HTML401 for PHP>=5.4.
					}

					// Replace the old JSON string in the content with the new, modified JSON string.
					$content = str_replace( $match[1], $new_json_string, $content );
				}
			}
		}

		return $content;
	}

	/**
	 * Replace internal image src to webp or avif.
	 *
	 * @since  1.6.2
	 * @access public
	 *
	 * @param string $url Image URL.
	 * @return string|false Replaced URL or false if not applicable.
	 */
	public function replace_webp( $url ) {
		if ( ! $this->webp_support() ) {
			self::debug2( 'No next generation format chosen in setting, bypassed' );
			return false;
		}
		self::debug2( $this->_sys_format . ' replacing: ' . substr( $url, 0, 200 ) );

		if ( substr( $url, -5 ) === '.' . $this->_sys_format ) {
			self::debug2( 'already ' . $this->_sys_format );
			return false;
		}

		/**
		 * WebP/AVIF API hook.
		 * NOTE: As $url may contain query strings, check filters which may parse_url before appending format.
		 *
		 * @since  2.9.5
		 * @see  #751737 - API docs for WebP generation
		 */
		$ori_check = apply_filters( 'litespeed_media_check_ori', Utility::is_internal_file( $url ), $url );
		if ( $ori_check ) {
			// check if has webp/avif file.
			$has_next = apply_filters( 'litespeed_media_check_webp', Utility::is_internal_file( $url, $this->_sys_format ), $url );
			if ( $has_next ) {
				$url .= '.' . $this->_sys_format;
			} else {
				self::debug2( '-no WebP or AVIF file, bypassed' );
				return false;
			}
		} else {
			self::debug2( '-no file, bypassed' );
			return false;
		}

		self::debug2( '- replaced to: ' . $url );

		return $url;
	}

	/**
	 * Hook to wp_get_attachment_image_src.
	 *
	 * @since  1.6.2
	 * @access public
	 *
	 * @param  array $img The URL, width, height array.
	 * @return array
	 */
	public function webp_attach_img_src( $img ) {
		self::debug2( 'changing attach src: ' . $img[0] );
		$url = $img ? $this->replace_webp( $img[0] ) : false;
		if ( $url ) {
			$img[0] = $url;
		}
		return $img;
	}

	/**
	 * Try to replace img url.
	 *
	 * @since  1.6.2
	 * @access public
	 *
	 * @param  string $url Image URL.
	 * @return string
	 */
	public function webp_url( $url ) {
		$url2 = $url ? $this->replace_webp( $url ) : false;
		if ( $url2 ) {
			$url = $url2;
		}
		return $url;
	}

	/**
	 * Hook to replace WP responsive images.
	 *
	 * @since  1.6.2
	 * @access public
	 *
	 * @param  array $srcs Srcset array.
	 * @return array
	 */
	public function webp_srcset( $srcs ) {
		if ( $srcs ) {
			foreach ( $srcs as $w => $data ) {
				$url = $this->replace_webp( $data['url'] );
				if ( ! $url ) {
					continue;
				}
				$srcs[ $w ]['url'] = $url;
			}
		}
		return $srcs;
	}
}
