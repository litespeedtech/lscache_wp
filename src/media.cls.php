<?php
/**
 * The class to operate media data.
 *
 * @since 		1.4
 * @since  		1.5 Moved into /inc
 * @package    	Core
 * @subpackage 	Core/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

class Media extends Root {
	const LOG_TAG = '📺';

	const LIB_FILE_IMG_LAZYLOAD = 'assets/js/lazyload.min.js';

	private $content;
	private $_wp_upload_dir;

	/**
	 * Init
	 *
	 * @since  1.4
	 */
	public function __construct() {
		Debug2::debug2( '[Media] init' );

		$this->_wp_upload_dir = wp_upload_dir();
	}

	/**
	 * Init optm features
	 *
	 * @since  3.0
	 * @access public
	 */
	public function init() {
		if ( is_admin() ) {
			return;
		}

		// Due to ajax call doesn't send correct accept header, have to limit webp to HTML only
		if ( defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( Base::O_IMG_OPTM_WEBP_REPLACE ) ) {
			if ( $this->webp_support() ) {
				// Hook to srcset
				if ( function_exists( 'wp_calculate_image_srcset' ) ) {
					add_filter( 'wp_calculate_image_srcset', array( $this, 'webp_srcset' ), 988 );
				}
				// Hook to mime icon
				// add_filter( 'wp_get_attachment_image_src', array( $this, 'webp_attach_img_src' ), 988 );// todo: need to check why not
				// add_filter( 'wp_get_attachment_url', array( $this, 'webp_url' ), 988 ); // disabled to avoid wp-admin display
			}
		}

		if ( $this->conf( Base::O_MEDIA_LAZY ) && ! $this->cls( 'Metabox' )->setting( 'litespeed_no_image_lazy' ) ) {
			self::debug( 'Suppress default WP lazyload' );
			add_filter( 'wp_lazy_loading_enabled', '__return_false' );
		}

		/**
		 * Replace gravatar
		 * @since  3.0
		 */
		$this->cls( 'Avatar' );

		add_filter( 'litespeed_buffer_finalize', array( $this, 'finalize' ), 4 );
	}

	/**
	 * Adjust WP default JPG quality
	 *
	 * @since  3.0
	 * @access public
	 */
	public function adjust_jpg_quality( $quality ) {
		$v = $this->conf( Base::O_IMG_OPTM_JPG_QUALITY );

		if ( $v ) {
			return $v;
		}

		return $quality;
	}

	/**
	 * Register admin menu
	 *
	 * @since 1.6.3
	 * @access public
	 */
	public function after_admin_init() {
		/**
		 * JPG quality control
		 * @since  3.0
		 */
		add_filter( 'jpeg_quality', array( $this, 'adjust_jpg_quality' ) );

		add_filter( 'manage_media_columns', array( $this, 'media_row_title' ) );
		add_filter( 'manage_media_custom_column', array( $this, 'media_row_actions' ), 10, 2 );

		add_action( 'litespeed_media_row', array( $this, 'media_row_con' ) );

		// Hook to attachment delete action
		add_action( 'delete_attachment', __CLASS__ . '::delete_attachment' );
	}

	/**
	 * Media delete action hook
	 *
	 * @since 2.4.3
	 * @access public
	 */
	public static function delete_attachment( $post_id ) {
		if ( ! Data::cls()->tb_exist( 'img_optm' ) ) {
			return;
		}

		self::debug( 'delete_attachment [pid] ' . $post_id );
		Img_Optm::cls()->reset_row( $post_id );
	}

	/**
	 * Return media file info if exists
	 *
	 * This is for remote attachment plugins
	 *
	 * @since 2.9.8
	 * @access public
	 */
	public function info( $short_file_path, $post_id ) {
		$real_file = $this->_wp_upload_dir[ 'basedir' ] . '/' . $short_file_path;

		if ( file_exists( $real_file ) ) {
			return array(
				'url'	=> $this->_wp_upload_dir[ 'baseurl' ] . '/' . $short_file_path,
				'md5'	=> md5_file( $real_file ),
				'size'	=> filesize( $real_file ),
			);
		}

		/**
		 * WP Stateless compatibility #143 https://github.com/litespeedtech/lscache_wp/issues/143
		 * @since 2.9.8
		 * @return array( 'url', 'md5', 'size' )
		 */
		$info = apply_filters( 'litespeed_media_info', array(), $short_file_path, $post_id );
		if ( ! empty( $info[ 'url' ] ) && ! empty( $info[ 'md5' ] ) && ! empty( $info[ 'size' ] ) ) {
			return $info;
		}

		return false;
	}

	/**
	 * Delete media file
	 *
	 * @since 2.9.8
	 * @access public
	 */
	public function del( $short_file_path, $post_id ) {
		$real_file = $this->_wp_upload_dir[ 'basedir' ] . '/' . $short_file_path;

		if ( file_exists( $real_file ) ) {
			unlink( $real_file );
			self::debug( 'deleted ' . $real_file );
		}

		do_action( 'litespeed_media_del', $short_file_path, $post_id );
	}

	/**
	 * Rename media file
	 *
	 * @since 2.9.8
	 * @access public
	 */
	public function rename( $short_file_path, $short_file_path_new, $post_id ) {
		$real_file = $this->_wp_upload_dir[ 'basedir' ] . '/' . $short_file_path;
		$real_file_new = $this->_wp_upload_dir[ 'basedir' ] . '/' . $short_file_path_new;

		if ( file_exists( $real_file ) ) {
			rename( $real_file, $real_file_new );
			self::debug( 'renamed ' . $real_file . ' to ' . $real_file_new );
		}

		do_action( 'litespeed_media_rename', $short_file_path, $short_file_path_new, $post_id );
	}

	/**
	 * Media Admin Menu -> Image Optimization Column Title
	 *
	 * @since 1.6.3
	 * @access public
	 */
	public function media_row_title( $posts_columns ) {
		$posts_columns[ 'imgoptm' ] = __( 'LiteSpeed Optimization', 'litespeed-cache' );

		return $posts_columns;
	}

	/**
	 * Media Admin Menu -> Image Optimization Column
	 *
	 * @since 1.6.2
	 * @access public
	 */
	public function media_row_actions( $column_name, $post_id ) {
		if ( $column_name !== 'imgoptm' ) {
			return;
		}

		do_action( 'litespeed_media_row', $post_id );

	}

	/**
	 * Display image optm info
	 *
	 * @since  3.0
	 */
	public function media_row_con( $post_id ) {
		$att_info = wp_get_attachment_metadata( $post_id );
		if ( empty( $att_info[ 'file' ] ) ) {
			return;
		}

		$short_path = $att_info[ 'file' ];

		$size_meta = get_post_meta( $post_id, Img_Optm::DB_SIZE, true );

		echo '<p>';
		// Original image info
		if ( $size_meta && ! empty ( $size_meta[ 'ori_saved' ] ) ) {
			$percent = ceil( $size_meta[ 'ori_saved' ] * 100 / $size_meta[ 'ori_total' ] );

			$extension = pathinfo( $short_path, PATHINFO_EXTENSION );
			$bk_file = substr( $short_path, 0, -strlen( $extension ) ) . 'bk.' . $extension;
			$bk_optm_file = substr( $short_path, 0, -strlen( $extension ) ) . 'bk.optm.' . $extension;

			$link = Utility::build_url( Router::ACTION_IMG_OPTM, 'orig' . $post_id );
			$desc = false;

			$cls = '';

			if ( $this->info( $bk_file, $post_id ) ) {
				$curr_status = __( '(optm)', 'litespeed-cache' );
				$desc = __( 'Currently using optimized version of file.', 'litespeed-cache' ) . '&#10;' . __( 'Click to switch to original (unoptimized) version.', 'litespeed-cache' );
			}
			elseif ( $this->info( $bk_optm_file, $post_id ) ) {
				$cls .= ' litespeed-warning';
				$curr_status = __( '(non-optm)', 'litespeed-cache' );
				$desc = __( 'Currently using original (unoptimized) version of file.', 'litespeed-cache' ) . '&#10;' . __( 'Click to switch to optimized version.', 'litespeed-cache' );
			}

			echo GUI::pie_tiny( $percent, 24,
				sprintf( __( 'Original file reduced by %1$s (%2$s)', 'litespeed-cache' ),
					$percent . '%',
					Utility::real_size( $size_meta[ 'ori_saved' ] )
				) , 'left'
			);

			echo sprintf( __( 'Orig saved %s', 'litespeed-cache' ), $percent . '%' );

			if ( $desc ) {
				echo sprintf( ' <a href="%1$s" class="litespeed-media-href %2$s" data-balloon-pos="left" data-balloon-break aria-label="%3$s">%4$s</a>', $link, $cls, $desc, $curr_status );
			}
			else {
				echo sprintf(
					' <span class="litespeed-desc" data-balloon-pos="left" data-balloon-break aria-label="%1$s">%2$s</span>',
					__( 'Using optimized version of file. ', 'litespeed-cache' ) . '&#10;' . __( 'No backup of original file exists.', 'litespeed-cache' ),
					__( '(optm)', 'litespeed-cache' )
				);
			}


		}
		elseif ( $size_meta && $size_meta[ 'ori_saved' ] === 0 ){
			echo GUI::pie_tiny( 0, 24,
				__( 'Congratulation! Your file was already optimized', 'litespeed-cache' ),
				'left'
			);
			echo sprintf( __( 'Orig %s', 'litespeed-cache' ), '<span class="litespeed-desc">' . __( '(no savings)', 'litespeed-cache' ) . '</span>' );
		}
		else {
			echo __( 'Orig', 'litespeed-cache' ) . '<span class="litespeed-left10">—</span>';
		}
		echo '</p>';

		echo '<p>';
		// WebP info
		if ( $size_meta && ! empty ( $size_meta[ 'webp_saved' ] ) ) {
			$percent = ceil( $size_meta[ 'webp_saved' ] * 100 / $size_meta[ 'webp_total' ] );

			$link = Utility::build_url( Router::ACTION_IMG_OPTM, 'webp' . $post_id );
			$desc = false;

			$cls = '';

			if ( $this->info( $short_path . '.webp', $post_id ) ) {
				$curr_status = __( '(optm)', 'litespeed-cache' );
				$desc = __( 'Currently using optimized version of WebP file.', 'litespeed-cache' ) . '&#10;' . __( 'Click to switch to original (unoptimized) version.', 'litespeed-cache' );
			}
			elseif ( $this->info( $short_path . '.optm.webp', $post_id ) ) {
				$cls .= ' litespeed-warning';
				$curr_status = __( '(non-optm)', 'litespeed-cache' );
				$desc = __( 'Currently using original (unoptimized) version of WebP file.', 'litespeed-cache' ) . '&#10;' . __( 'Click to switch to optimized version.', 'litespeed-cache' );
			}

			echo GUI::pie_tiny( $percent, 24,
				sprintf( __( 'WebP file reduced by %1$s (%2$s)', 'litespeed-cache' ),
					$percent . '%',
					Utility::real_size( $size_meta[ 'webp_saved' ] )
				) , 'left'
			);
			echo sprintf( __( 'WebP saved %s', 'litespeed-cache' ), $percent . '%' );

			if ( $desc ) {
				echo sprintf( ' <a href="%1$s" class="litespeed-media-href %2$s" data-balloon-pos="left" data-balloon-break aria-label="%3$s">%4$s</a>', $link, $cls, $desc, $curr_status );
			}
			else {
				echo sprintf(
					' <span class="litespeed-desc" data-balloon-pos="left" data-balloon-break aria-label="%1$s">%2$s</span>',
					__( 'Using optimized version of file. ', 'litespeed-cache' ) . '&#10;' . __( 'No backup of unoptimized WebP file exists.', 'litespeed-cache' ),
					__( '(optm)', 'litespeed-cache' )
				);
			}

		} else {
			echo __( 'WebP', 'litespeed-cache' ) . '<span class="litespeed-left10">—</span>';
		}

		echo '</p>';

		// Delete row btn
		if ( $size_meta ) {

			echo sprintf( '<div class="row-actions"><span class="delete"><a href="%1$s" class="">%2$s</a></span></div>',
				Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_RESET_ROW, false, null, array( 'id' => $post_id ) ),
				__( 'Restore from backup', 'litespeed-cache' )
			);
			echo '</div>';
		}
	}

	/**
	 * Get wp size info
	 *
	 * NOTE: this is not used because it has to be after admin_init
	 *
	 * @since 1.6.2
	 * @return array $sizes Data for all currently-registered image sizes.
	 */
	public function get_image_sizes() {
		global $_wp_additional_image_sizes;
		$sizes = array();

		foreach ( get_intermediate_image_sizes() as $_size ) {
			if ( in_array( $_size, array( 'thumbnail', 'medium', 'medium_large', 'large' ) ) ) {
				$sizes[ $_size ][ 'width' ] = get_option( $_size . '_size_w' );
				$sizes[ $_size ][ 'height' ] = get_option( $_size . '_size_h' );
				$sizes[ $_size ][ 'crop' ] = (bool) get_option( $_size . '_crop' );
			} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
				$sizes[ $_size ] = array(
					'width' => $_wp_additional_image_sizes[ $_size ][ 'width' ],
					'height' => $_wp_additional_image_sizes[ $_size ][ 'height' ],
					'crop' =>  $_wp_additional_image_sizes[ $_size ][ 'crop' ]
				);
			}
		}

		return $sizes;
	}


	/**
	 * Exclude role from optimization filter
	 *
	 * @since  1.6.2
	 * @access public
	 */
	public function webp_support() {
		if ( ! empty( $_SERVER[ 'HTTP_ACCEPT' ] ) && strpos( $_SERVER[ 'HTTP_ACCEPT' ], 'image/webp' ) !== false ) {
			return true;
		}

		if ( ! empty( $_SERVER[ 'HTTP_USER_AGENT' ] ) ) {
			if ( strpos( $_SERVER[ 'HTTP_USER_AGENT' ], 'Page Speed' ) !== false ) {
				return true;
			}

			if ( preg_match( "/iPhone OS (\d+)_/i", $_SERVER[ 'HTTP_USER_AGENT' ], $matches ) ) {
				$lscwp_ios_version = $matches[1];
				if ($lscwp_ios_version >= 14){
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Run lazy load process
	 * NOTE: As this is after cache finalized, can NOT set any cache control anymore
	 *
	 * Only do for main page. Do NOT do for esi or dynamic content.
	 *
	 * @since  1.4
	 * @access public
	 * @return  string The buffer
	 */
	public function finalize( $content ) {
		if ( defined( 'LITESPEED_NO_LAZY' ) ) {
			Debug2::debug2( '[Media] bypass: NO_LAZY const' );
			return $content;
		}

		if ( ! defined( 'LITESPEED_IS_HTML' ) ) {
			Debug2::debug2( '[Media] bypass: Not frontend HTML type' );
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
	 * Run lazyload replacement for images in buffer
	 *
	 * @since  1.4
	 * @access private
	 */
	private function _finalize() {
		/**
		 * Use webp for optimized images
		 * @since 1.6.2
		 */
		if ( ( defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( Base::O_IMG_OPTM_WEBP_REPLACE ) ) && $this->webp_support() ) {
			$this->content = $this->_replace_buffer_img_webp( $this->content );
		}

		/**
		 * Check if URI is excluded
		 * @since  3.0
		 */
		$excludes = $this->conf( Base::O_MEDIA_LAZY_URI_EXC );
		if ( ! defined( 'LITESPEED_GUEST_OPTM' ) ) {
			$result = Utility::str_hit_array( $_SERVER[ 'REQUEST_URI' ], $excludes );
			if ( $result ) {
				self::debug( 'bypass lazyload: hit URI Excludes setting: ' . $result );
				return;
			}
		}

		$cfg_lazy = ( defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( Base::O_MEDIA_LAZY ) ) && ! $this->cls( 'Metabox' )->setting( 'litespeed_no_image_lazy' );
		$cfg_iframe_lazy = defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( Base::O_MEDIA_IFRAME_LAZY );
		$cfg_js_delay = defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( Base::O_OPTM_JS_DEFER ) == 2;
		$cfg_trim_noscript = defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( Base::O_OPTM_NOSCRIPT_RM );
		$cfg_vpi = defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( Base::O_MEDIA_VPI );

		if ( $cfg_lazy ) {
			if ( $cfg_vpi ) {
				add_filter( 'litespeed_media_lazy_img_excludes', array( $this->cls( 'Metabox' ), 'lazy_img_excludes' ) );
			}
			list( $src_list, $html_list, $placeholder_list ) = $this->_parse_img();
			$html_list_ori = $html_list;
		}
		else {
			self::debug( 'lazyload disabled' );
		}

		// image lazy load
		if ( $cfg_lazy ) {

			$__placeholder = Placeholder::cls();

			foreach ( $html_list as $k => $v ) {
				$size = $placeholder_list[ $k ];
				$src = $src_list[ $k ];

				$html_list[ $k ] = $__placeholder->replace( $v, $src, $size );
			}
		}

		if ( $cfg_lazy ) {
			$this->content = str_replace( $html_list_ori, $html_list, $this->content );
		}

		// iframe lazy load
		if ( $cfg_iframe_lazy ) {
			$html_list = $this->_parse_iframe();
			$html_list_ori = $html_list;

			foreach ( $html_list as $k => $v ) {
				$snippet = $cfg_trim_noscript ? '' : '<noscript>' . $v . '</noscript>';
				if ( $cfg_js_delay ) {
					$v = str_replace( ' src=', ' data-litespeed-src=', $v );
				}
				else {
					$v = str_replace( ' src=', ' data-src=', $v );
				}
				$v = str_replace( '<iframe ', '<iframe data-lazyloaded="1" src="about:blank" ', $v );
				$snippet = $v . $snippet;

				$html_list[ $k ] = $snippet;
			}

			$this->content = str_replace( $html_list_ori, $html_list, $this->content );
		}

		// Include lazyload lib js and init lazyload
		if ( $cfg_lazy || $cfg_iframe_lazy ) {
			$lazy_lib = '<script data-no-optimize="1" defer>' . File::read( LSCWP_DIR . self::LIB_FILE_IMG_LAZYLOAD ) . '</script>';
			$this->content = str_replace( '</body>', $lazy_lib . '</body>', $this->content );
		}
	}


	/**
	 * Parse img src
	 *
	 * @since  1.4
	 * @access private
	 * @return array  All the src & related raw html list
	 */
	private function _parse_img() {
		/**
		 * Exclude list
		 * @since 1.5
		 * @since  2.7.1 Changed to array
		 */
		$excludes = apply_filters( 'litespeed_media_lazy_img_excludes', $this->conf( Base::O_MEDIA_LAZY_EXC ) );

		$cls_excludes = apply_filters( 'litespeed_media_lazy_img_cls_excludes', $this->conf( Base::O_MEDIA_LAZY_CLS_EXC ) );
		$cls_excludes[] = 'skip-lazy'; // https://core.trac.wordpress.org/ticket/44427

		$src_list = array();
		$html_list = array();
		$placeholder_list = array();

		$content = preg_replace( array( '#<!--.*-->#sU', '#<noscript([^>]*)>.*</noscript>#isU' ), '', $this->content );
		/**
		 * Exclude parent classes
		 * @since  3.0
		 */
		$parent_cls_exc = apply_filters( 'litespeed_media_lazy_img_parent_cls_excludes', $this->conf( Base::O_MEDIA_LAZY_PARENT_CLS_EXC ) );
		if ( $parent_cls_exc ) {
			Debug2::debug2( '[Media] Lazyload Class excludes', $parent_cls_exc );
			foreach ( $parent_cls_exc as $v ) {
				$content = preg_replace( '#<(\w+) [^>]*class=(\'|")[^\'"]*' . preg_quote( $v, '#' ) . '[^\'"]*\2[^>]*>.*</\1>#sU', '', $content );
			}
		}

		preg_match_all( '#<img \s*([^>]+)/?>#isU', $content, $matches, PREG_SET_ORDER );
		foreach ( $matches as $match ) {
			$attrs = Utility::parse_attr( $match[ 1 ] );

			if ( empty( $attrs[ 'src' ] ) ) {
				continue;
			}

			/**
			 * Add src validation to bypass base64 img src
			 * @since  1.6
			 */
			if ( strpos( $attrs[ 'src' ], 'base64' ) !== false || substr( $attrs[ 'src' ], 0, 5 ) === 'data:' ) {
				Debug2::debug2( '[Media] lazyload bypassed base64 img' );
				continue;
			}

			Debug2::debug2( '[Media] lazyload found: ' . $attrs[ 'src' ] );

			if ( ! empty( $attrs[ 'data-no-lazy' ] ) || ! empty( $attrs[ 'data-skip-lazy' ] ) || ! empty( $attrs[ 'data-lazyloaded' ] ) || ! empty( $attrs[ 'data-src' ] ) || ! empty( $attrs[ 'data-srcset' ] ) ) {
				Debug2::debug2( '[Media] bypassed' );
				continue;
			}

			if ( ! empty( $attrs[ 'class' ] ) && $hit = Utility::str_hit_array( $attrs[ 'class' ], $cls_excludes ) ) {
				Debug2::debug2( '[Media] lazyload image cls excludes [hit] ' . $hit );
				continue;
			}

			/**
			 * Exclude from lazyload by setting
			 * @since  1.5
			 */
			if ( $excludes && Utility::str_hit_array( $attrs[ 'src' ], $excludes ) ) {
				Debug2::debug2( '[Media] lazyload image exclude ' . $attrs[ 'src' ] );
				continue;
			}

			/**
			 * Excldues invalid image src from buddypress avatar crop
			 * @see  https://wordpress.org/support/topic/lazy-load-breaking-buddypress-upload-avatar-feature
			 * @since  3.0
			 */
			if ( strpos( $attrs[ 'src' ], '{' ) !== false ) {
				Debug2::debug2( '[Media] image src has {} ' . $attrs[ 'src' ] );
				continue;
			}

			// to avoid multiple replacement
			if ( in_array( $match[ 0 ], $html_list ) ) {
				continue;
			}

			// Add missing dimensions
			if ( defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( Base::O_MEDIA_ADD_MISSING_SIZES ) ) {
				if ( empty( $attrs[ 'width' ] ) || $attrs[ 'width' ] == 'auto' || empty( $attrs[ 'height' ] ) || $attrs[ 'height' ] == 'auto' ) {
					self::debug( '⚠️ Missing sizes for image [src] ' . $attrs[ 'src' ] );
					$dimensions = $this->_detect_dimensions( $attrs[ 'src' ] );
					if ( $dimensions ) {
						$ori_width = $dimensions[ 0 ];
						$ori_height = $dimensions[ 1 ];
						// Calculate height based on width
						if ( ! empty( $attrs[ 'width' ] ) && $attrs[ 'width' ] != 'auto' ) {
							$ori_height = intval( $ori_height * $attrs[ 'width' ] / $ori_width );
						}
						elseif ( ! empty( $attrs[ 'height' ] ) && $attrs[ 'height' ] != 'auto' ) {
							$ori_width = intval( $ori_width * $attrs[ 'height' ] / $ori_height );
						}

						$attrs[ 'width' ] = $ori_width;
						$attrs[ 'height' ] = $ori_height;
						$new_html = preg_replace( '#(width|height)=(["\'])[^\2]*\2#', '', $match[ 0 ] );
						$new_html = str_replace( '<img ', '<img width="' . $attrs[ 'width' ] . '" height="' . $attrs[ 'height' ] . '" ', $new_html );
						self::debug( 'Add missing sizes ' . $attrs[ 'width' ] . 'x' . $attrs[ 'height' ] . ' to ' . $attrs[ 'src' ] );
						$this->content = str_replace( $match[ 0 ], $new_html, $this->content );
						$match[ 0 ] = $new_html;
					}
				}
			}

			$placeholder = false;
			if ( ! empty( $attrs[ 'width' ] ) && $attrs[ 'width' ] != 'auto' && ! empty( $attrs[ 'height' ] ) && $attrs[ 'height' ] != 'auto' ) {
				$placeholder = $attrs[ 'width' ] . 'x' . $attrs[ 'height' ];
			}

			$src_list[] = $attrs[ 'src' ];
			$html_list[] = $match[ 0 ];
			$placeholder_list[] = $placeholder;
		}

		return array( $src_list, $html_list, $placeholder_list );
	}

	/**
	 * Detect the original sizes
	 *
	 * @since  4.0
	 */
	private function _detect_dimensions( $src ) {
		if ( $pathinfo = Utility::is_internal_file( $src ) ) {
			$src = $pathinfo[ 0 ];
		}
		elseif ( apply_filters( 'litespeed_media_ignore_remote_missing_sizes', false ) ) {
			return false;
		}

		if ( substr( $src, 0, 2 ) == '//' ) $src = 'https:' . $src;

		$sizes = getimagesize( $src );

		if ( ! empty( $sizes[ 0 ] ) && ! empty( $sizes[ 1 ] ) ) {
			return $sizes;
		}

		return false;
	}

	/**
	 * Parse iframe src
	 *
	 * @since  1.4
	 * @access private
	 * @return array  All the src & related raw html list
	 */
	private function _parse_iframe() {
		$cls_excludes = apply_filters( 'litespeed_media_iframe_lazy_cls_excludes', $this->conf( Base::O_MEDIA_IFRAME_LAZY_CLS_EXC ) );
		$cls_excludes[] = 'skip-lazy'; // https://core.trac.wordpress.org/ticket/44427

		$html_list = array();

		$content = preg_replace( '#<!--.*-->#sU', '', $this->content );

		/**
		 * Exclude parent classes
		 * @since  3.0
		 */
		$parent_cls_exc = apply_filters( 'litespeed_media_iframe_lazy_parent_cls_excludes', $this->conf( Base::O_MEDIA_IFRAME_LAZY_PARENT_CLS_EXC ) );
		if ( $parent_cls_exc ) {
			Debug2::debug2( '[Media] Iframe Lazyload Class excludes', $parent_cls_exc );
			foreach ( $parent_cls_exc as $v ) {
				$content = preg_replace( '#<(\w+) [^>]*class=(\'|")[^\'"]*' . preg_quote( $v, '#' ) . '[^\'"]*\2[^>]*>.*</\1>#sU', '', $content );
			}
		}

		preg_match_all( '#<iframe \s*([^>]+)></iframe>#isU', $content, $matches, PREG_SET_ORDER );
		foreach ( $matches as $match ) {
			$attrs = Utility::parse_attr( $match[ 1 ] );

			if ( empty( $attrs[ 'src' ] ) ) {
				continue;
			}

			Debug2::debug2( '[Media] found iframe: ' . $attrs[ 'src' ] );

			if ( ! empty( $attrs[ 'data-no-lazy' ] ) ||  ! empty( $attrs[ 'data-skip-lazy' ] ) || ! empty( $attrs[ 'data-lazyloaded' ] ) || ! empty( $attrs[ 'data-src' ] ) ) {
				Debug2::debug2( '[Media] bypassed' );
				continue;
			}

			if ( ! empty( $attrs[ 'class' ] ) && $hit = Utility::str_hit_array( $attrs[ 'class' ], $cls_excludes ) ) {
				Debug2::debug2( '[Media] iframe lazyload cls excludes [hit] ' . $hit );
				continue;
			}

			if ( apply_filters( 'litespeed_iframe_lazyload_exc', false, $attrs[ 'src' ] ) ) {
				Debug2::debug2( '[Media] bypassed by filter' );
				continue;
			}

			// to avoid multiple replacement
			if ( in_array( $match[ 0 ], $html_list ) ) {
				continue;
			}

			$html_list[] = $match[ 0 ];
		}

		return $html_list;
	}

	/**
	 * Replace image src to webp
	 *
	 * @since  1.6.2
	 * @access private
	 */
	private function _replace_buffer_img_webp( $content ) {
		/**
		 * Added custom element & attribute support
		 * @since 2.2.2
		 */
		$webp_ele_to_check = $this->conf( Base::O_IMG_OPTM_WEBP_ATTR );

		foreach ( $webp_ele_to_check as $v ) {
			if ( ! $v || strpos( $v, '.' ) === false ) {
				Debug2::debug2( '[Media] buffer_webp no . attribute ' . $v );
				continue;
			}

			Debug2::debug2( '[Media] buffer_webp attribute ' . $v );

			$v = explode( '.', $v );
			$attr = preg_quote( $v[ 1 ], '#' );
			if ( $v[ 0 ] ) {
				$pattern = '#<' . preg_quote( $v[ 0 ], '#' ) . '([^>]+)' . $attr . '=([\'"])(.+)\2#iU';
			}
			else {
				$pattern = '# ' . $attr . '=([\'"])(.+)\1#iU';
			}

			preg_match_all( $pattern, $content, $matches );

			foreach ( $matches[ $v[ 0 ] ? 3 : 2 ] as $k2 => $url ) {
				// Check if is a DATA-URI
				if ( strpos( $url, 'data:image' ) !== false ) {
					continue;
				}

				if ( ! $url2 = $this->replace_webp( $url ) ) {
					continue;
				}

				if ( $v[ 0 ] ) {
					$html_snippet = sprintf(
						'<' . $v[ 0 ] . '%1$s' . $v[ 1 ] . '=%2$s',
						$matches[ 1 ][ $k2 ],
						$matches[ 2 ][ $k2 ] . $url2 . $matches[ 2 ][ $k2 ]
					);
				}
				else {
					$html_snippet = sprintf(
						' ' . $v[ 1 ] . '=%1$s',
						$matches[ 1 ][ $k2 ] . $url2 . $matches[ 1 ][ $k2 ]
					);
				}

				$content = str_replace( $matches[ 0 ][ $k2 ], $html_snippet, $content );

			}
		}

		// parse srcset
		// todo: should apply this to cdn too
		if ( ( defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( Base::O_IMG_OPTM_WEBP_REPLACE_SRCSET ) ) && $this->webp_support() ) {
			$content = Utility::srcset_replace( $content, array( $this, 'replace_webp' ) );
		}

		// Replace background-image
		if ( ( defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( Base::O_IMG_OPTM_WEBP_REPLACE ) ) && $this->webp_support() ) {
			$content = $this->replace_background_webp( $content );
		}

		return $content;
	}

	/**
	 * Replace background image
	 *
	 * @since  4.0
	 */
	public function replace_background_webp( $content ) {
		Debug2::debug2( '[Media] Start replacing bakcground WebP.' );

		// preg_match_all( '#background-image:(\s*)url\((.*)\)#iU', $content, $matches );
		preg_match_all( '#url\(([^)]+)\)#iU', $content, $matches );
		foreach ( $matches[ 1 ] as $k => $url ) {
			// Check if is a DATA-URI
			if ( strpos( $url, 'data:image' ) !== false ) {
				continue;
			}

			/**
			 * Support quotes in src `background-image: url('src')`
			 * @since 2.9.3
			 */
			$url = trim( $url, '\'"' );

			if ( ! $url2 = $this->replace_webp( $url ) ) {
				continue;
			}

			// $html_snippet = sprintf( 'background-image:%1$surl(%2$s)', $matches[ 1 ][ $k ], $url2 );
			$html_snippet = str_replace( $url, $url2, $matches[ 0 ][ $k ] );
			$content = str_replace( $matches[ 0 ][ $k ], $html_snippet, $content );
		}

		return $content;
	}

	/**
	 * Replace internal image src to webp
	 *
	 * @since  1.6.2
	 * @access public
	 */
	public function replace_webp( $url ) {
		Debug2::debug2( '[Media] webp replacing: ' . substr( $url, 0, 200 ) );

		if ( substr( $url, -5 ) == '.webp' ) {
			Debug2::debug2( '[Media] already webp' );
			return false;
		}

		/**
		 * WebP API hook
		 * NOTE: As $url may contain query strings, WebP check will need to parse_url before appending .webp
		 * @since  2.9.5
		 * @see  #751737 - API docs for WebP generation
		 */
		if ( apply_filters( 'litespeed_media_check_ori', Utility::is_internal_file( $url ), $url ) ) {
			// check if has webp file
			if ( apply_filters( 'litespeed_media_check_webp', Utility::is_internal_file( $url, 'webp' ), $url ) ) {
				$url .= '.webp';
			}
			else {
				Debug2::debug2( '[Media] -no WebP file, bypassed' );
				return false;
			}
		}
		else {
			Debug2::debug2( '[Media] -no file, bypassed' );
			return false;
		}

		Debug2::debug2( '[Media] - replaced to: ' . $url );

		return $url;
	}

	/**
	 * Hook to wp_get_attachment_image_src
	 *
	 * @since  1.6.2
	 * @access public
	 * @param  array $img The URL of the attachment image src, the width, the height
	 * @return array
	 */
	public function webp_attach_img_src( $img ) {
		Debug2::debug2( '[Media] changing attach src: ' . $img[0] );
		if ( $img && $url = $this->replace_webp( $img[ 0 ] ) ) {
			$img[ 0 ] = $url;
		}
		return $img;
	}

	/**
	 * Try to replace img url
	 *
	 * @since  1.6.2
	 * @access public
	 * @param  string $url
	 * @return string
	 */
	public function webp_url( $url ) {
		if ( $url && $url2 = $this->replace_webp( $url ) ) {
			$url = $url2;
		}
		return $url;
	}

	/**
	 * Hook to replace WP responsive images
	 *
	 * @since  1.6.2
	 * @access public
	 * @param  array $srcs
	 * @return array
	 */
	public function webp_srcset( $srcs ) {
		if ( $srcs ) {
			foreach ( $srcs as $w => $data ) {
				if( ! $url = $this->replace_webp( $data[ 'url' ] ) ) {
					continue;
				}
				$srcs[ $w ][ 'url' ] = $url;
			}
		}
		return $srcs;
	}

}
