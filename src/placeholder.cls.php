<?php
/**
 * The PlaceHolder class
 *
 * @since 		3.0
 * @package    	LiteSpeed
 * @subpackage 	LiteSpeed/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

class Placeholder extends Base {
	protected static $_instance;

	const TYPE_GENERATE = 'generate';
	const TYPE_CLEAR_Q = 'clear_q';

	private $_conf_placeholder_resp;
	private $_conf_placeholder_resp_svg;
	private $_conf_lqip;
	private $_conf_lqip_qual;
	private $_conf_lqip_min_w;
	private $_conf_lqip_min_h;
	private $_conf_placeholder_resp_color;
	private $_conf_placeholder_resp_async;
	private $_placeholder_resp_dict = array();
	private $_ph_queue = array();

	protected $_summary;

	/**
	 * Init
	 *
	 * @since  3.0
	 * @access protected
	 */
	protected function __construct() {
		$this->_conf_placeholder_resp = Conf::val( Base::O_MEDIA_PLACEHOLDER_RESP );
		$this->_conf_placeholder_resp_svg 	= Conf::val( Base::O_MEDIA_PLACEHOLDER_RESP_SVG );
		$this->_conf_lqip 		= Conf::val( Base::O_MEDIA_LQIP );
		$this->_conf_lqip_qual	= Conf::val( Base::O_MEDIA_LQIP_QUAL );
		$this->_conf_lqip_min_w	= Conf::val( Base::O_MEDIA_LQIP_MIN_W );
		$this->_conf_lqip_min_h	= Conf::val( Base::O_MEDIA_LQIP_MIN_H );
		$this->_conf_placeholder_resp_async = Conf::val( Base::O_MEDIA_PLACEHOLDER_RESP_ASYNC );
		$this->_conf_placeholder_resp_color = Conf::val( Base::O_MEDIA_PLACEHOLDER_RESP_COLOR );
		$this->_conf_ph_default = Conf::val( Base::O_MEDIA_LAZY_PLACEHOLDER ) ?: LITESPEED_PLACEHOLDER;

		$this->_summary = self::get_summary();
	}

	/**
	 * Init Placeholder
	 */
	public function init() {
		Debug2::debug2( '[LQIP] init' );

		add_action( 'litspeed_after_admin_init', array( $this, 'after_admin_init' ) );
	}

	/**
	 * Display column in Media
	 *
	 * @since  3.0
	 * @access public
	 */
	public function after_admin_init() {
		if ( $this->_conf_lqip ) {
			add_filter( 'manage_media_columns', array( $this, 'media_row_title' ) );
			add_filter( 'manage_media_custom_column', array( $this, 'media_row_actions' ), 10, 2 );
			add_action( 'litespeed_media_row_lqip', array( $this, 'media_row_con' ) );
		}
	}

	/**
	 * Media Admin Menu -> LQIP col
	 *
	 * @since 3.0
	 * @access public
	 */
	public function media_row_title( $posts_columns ) {
		$posts_columns[ 'lqip' ] = __( 'LQIP', 'litespeed-cache' );

		return $posts_columns;
	}

	/**
	 * Media Admin Menu -> LQIP Column
	 *
	 * @since 3.0
	 * @access public
	 */
	public function media_row_actions( $column_name, $post_id ) {
		if ( $column_name !== 'lqip' ) {
			return;
		}

		do_action( 'litespeed_media_row_lqip', $post_id );

	}


	/**
	 * Display LQIP column
	 *
	 * @since  3.0
	 * @access public
	 */
	public function media_row_con( $post_id ) {
		$meta_value = wp_get_attachment_metadata( $post_id );

		if ( empty( $meta_value[ 'file' ] ) ) {
			return;
		}

		$total_files = 0;

		// List all sizes
		$all_sizes = array( $meta_value[ 'file' ] );
		$size_path = pathinfo( $meta_value[ 'file' ], PATHINFO_DIRNAME ) . '/';
		foreach ( $meta_value[ 'sizes' ] as $v ) {
			$all_sizes[] = $size_path . $v[ 'file' ];
		}

		foreach ( $all_sizes as $short_path ) {
			$lqip_folder = LITESPEED_STATIC_DIR . '/lqip/' . $short_path;

			if ( is_dir( $lqip_folder ) ) {
				Debug2::debug( '[LQIP] Found folder: ' . $short_path );



				// List all files
				foreach ( scandir( $lqip_folder ) as $v ) {
					if ( $v == '.' || $v == '..' ) {
						continue;
					}

					if ( $total_files == 0 ) {
						echo '<div class="litespeed-media-lqip"><img src="' . File::read( $lqip_folder . '/' . $v ) . '" alt="' . sprintf( __( 'LQIP image preview for size %s', 'litespeed-cache' ), $v ) .'"></div>';
					}

					echo '<div class="litespeed-media-size"><a href="' . File::read( $lqip_folder . '/' . $v ) . '" target="_blank">' . $v . '</a></div>';

					$total_files++;
				}

			}
		}

		if ( $total_files == 0 ) {
			echo '—';
		}

	}

	/**
	 * Replace image with placeholder
	 *
	 * @since  3.0
	 * @access public
	 */
	public function replace( $html, $src, $size ) {
		// Check if need to enable responsive placeholder or not
		$this_placeholder = $this->_placeholder( $src, $size ) ?: $this->_conf_ph_default;

		$additional_attr = '';
		if ( $this->_conf_lqip && $this_placeholder != $this->_conf_ph_default ) {
			Debug2::debug2( '[LQIP] Use resp LQIP [size] ' . $size );
			$additional_attr = ' data-placeholder-resp="' . $size . '"';
		}

		$snippet = Conf::val( Base::O_OPTM_NOSCRIPT_RM ) ? '' : '<noscript>' . $html . '</noscript>';
		$html = str_replace( array( ' src=', ' srcset=', ' sizes=' ), array( ' data-src=', ' data-srcset=', ' data-sizes=' ), $html );
		$html = str_replace( '<img ', '<img data-lazyloaded="1"' . $additional_attr . ' src="' . $this_placeholder . '" ', $html );
		$snippet = $html . $snippet;

		return $snippet;
	}

	/**
	 * Generate responsive placeholder
	 *
	 * @since  2.5.1
	 * @access private
	 */
	private function _placeholder( $src, $size ) {
		// Low Quality Image Placeholders
		if ( ! $size ) {
			Debug2::debug2( '[LQIP] no size ' . $src );
			return false;
		}

		if ( ! $this->_conf_placeholder_resp ) {
			return false;
		}

		// If use local generator
		if ( ! $this->_conf_lqip || ! $this->_lqip_size_check( $size ) ) {
			return $this->_generate_placeholder_locally( $size );
		}

		Debug2::debug2( '[LQIP] Resp LQIP process [src] ' . $src . ' [size] ' . $size );

		$arr_key = $size . ' ' . $src;

		// Check if its already in dict or not
		if ( ! empty( $this->_placeholder_resp_dict[ $arr_key ] ) ) {
			Debug2::debug2( '[LQIP] already in dict' );

			return $this->_placeholder_resp_dict[ $arr_key ];
		}

		// Need to generate the responsive placeholder
		$placeholder_realpath = $this->_placeholder_realpath( $src, $size ); // todo: give offload API
		if ( file_exists( $placeholder_realpath ) ) {
			Debug2::debug2( '[LQIP] file exists' );
			$this->_placeholder_resp_dict[ $arr_key ] = File::read( $placeholder_realpath );

			return $this->_placeholder_resp_dict[ $arr_key ];
		}

		// Add to cron queue

		// Prevent repeated requests
		if ( in_array( $arr_key, $this->_ph_queue ) ) {
			Debug2::debug2( '[LQIP] file bypass generating due to in queue' );
			return $this->_generate_placeholder_locally( $size );
		}

		if ( $hit = Utility::str_hit_array( $src, Conf::val( Base::O_MEDIA_LQIP_EXC ) ) ) {
			Debug2::debug2( '[LQIP] file bypass generating due to exclude setting [hit] ' . $hit );
			return $this->_generate_placeholder_locally( $size );
		}

		$this->_ph_queue[] = $arr_key;

		// Send request to generate placeholder
		if ( ! $this->_conf_placeholder_resp_async ) {
			// If requested recently, bypass
			if ( $this->_summary && ! empty( $this->_summary[ 'curr_request' ] ) && time() - $this->_summary[ 'curr_request' ] < 300 ) {
				Debug2::debug2( '[LQIP] file bypass generating due to interval limit' );
				return false;
			}
			// Generate immediately
			$this->_placeholder_resp_dict[ $arr_key ] = $this->_generate_placeholder( $arr_key );

			return $this->_placeholder_resp_dict[ $arr_key ];
		}

		// Prepare default svg placeholder as tmp placeholder
		$tmp_placeholder = $this->_generate_placeholder_locally( $size );

		// Store it to prepare for cron
		if ( empty( $this->_summary[ 'queue' ] ) ) {
			$this->_summary[ 'queue' ] = array();
		}
		if ( in_array( $arr_key, $this->_summary[ 'queue' ] ) ) {
			Debug2::debug2( '[LQIP] already in queue' );

			return $tmp_placeholder;
		}

		if ( count( $this->_summary[ 'queue' ] ) > 100 ) {
			Debug2::debug2( '[LQIP] queue is full' );

			return $tmp_placeholder;
		}

		$this->_summary[ 'queue' ][] = $arr_key;

		Debug2::debug( '[LQIP] Added placeholder queue' );

		self::save_summary();
		return $tmp_placeholder;

	}

	/**
	 * Check if there is a LQIP cache folder
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function has_lqip_cache() {
		return is_dir( LITESPEED_STATIC_DIR . '/lqip' );
	}

	/**
	 * Generate realpath of placeholder file
	 *
	 * @since  2.5.1
	 * @access private
	 */
	private function _placeholder_realpath( $src, $size ) {
		// Use LQIP Cloud generator, each image placeholder will be separately stored

		// Compatibility with WebP
		if ( substr( $src, -5 ) === '.webp' ) {
			$src = substr( $src, 0, -5 );
		}

		// External images will use cache folder directly
		$domain = parse_url( $src, PHP_URL_HOST );
		if ( $domain && ! Utility::internal( $domain ) ) { // todo: need to improve `util:internal()` to include `CDN::internal()`
			$md5 = md5( $src );

			return LITESPEED_STATIC_DIR . '/lqip/remote/' . substr( $md5, 0, 1 ) . '/' . substr( $md5, 1, 1 ) . '/' . $md5 . '.' . $size;
		}

		// Drop domain
		$short_path = Utility::att_short_path( $src );

		return LITESPEED_STATIC_DIR . '/lqip/' . $short_path . '/' . $size;

	}

	/**
	 * Delete file-based cache folder for LQIP
	 *
	 * @since  3.0
	 * @access public
	 */
	public function rm_lqip_cache_folder() {
		if ( self::has_lqip_cache() ) {
			File::rrmdir( LITESPEED_STATIC_DIR . '/lqip' );
		}

		// Clear LQIP in queue too
		self::save_summary( array() );

		Debug2::debug( '[LQIP] Cleared LQIP queue' );
	}

	/**
	 * Cron placeholder generation
	 *
	 * @since  2.5.1
	 * @access public
	 */
	public static function cron( $continue = false ) {
		$_instance = self::get_instance();
		if ( empty( $_instance->_summary[ 'queue' ] ) ) {
			return;
		}

		// For cron, need to check request interval too
		if ( ! $continue ) {
			if ( ! empty( $_instance->_summary[ 'curr_request' ] ) && time() - $_instance->_summary[ 'curr_request' ] < 300 ) {
				Debug2::debug( '[LQIP] Last request not done' );
				return;
			}
		}

		foreach ( $_instance->_summary[ 'queue' ] as $v ) {
			Debug2::debug( '[LQIP] cron job [size] ' . $v );

			$_instance->_generate_placeholder( $v );

			// only request first one
			if ( ! $continue ) {
				return;
			}
		}
	}

	/**
	 * Generate placeholder locally
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _generate_placeholder_locally( $size ) {
		Debug2::debug2( '[LQIP] _generate_placeholder local [size] ' . $size );

		$size = explode( 'x', $size );

		$svg = str_replace( array( '{width}', '{height}', '{color}' ), array( $size[ 0 ], $size[ 1 ], $this->_conf_placeholder_resp_color ), $this->_conf_placeholder_resp_svg );

		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	/**
	 * Send to LiteSpeed API to generate placeholder
	 *
	 * @since  2.5.1
	 * @access private
	 */
	private function _generate_placeholder( $raw_size_and_src ) {
		// Parse containing size and src info
		$size_and_src = explode( ' ', $raw_size_and_src, 2 );
		$size = $size_and_src[ 0 ];

		if ( empty( $size_and_src[ 1 ] ) ) {
			$this->_popup_and_save( $raw_size_and_src );
			Debug2::debug( '[LQIP] ❌ No src [raw] ' . $raw_size_and_src );
			return $this->_generate_placeholder_locally( $size );
		}

		$src = $size_and_src[ 1 ];

		$file = $this->_placeholder_realpath( $src, $size );

		// Local generate SVG to serve ( Repeatly doing this here to remove stored cron queue in case the setting _conf_lqip is changed )
		if ( ! $this->_conf_lqip || ! $this->_lqip_size_check( $size ) ) {
			$data = $this->_generate_placeholder_locally( $size );
		}
		else {
			$allowance = Cloud::get_instance()->allowance( Cloud::SVC_LQIP );
			if ( ! $allowance ) {
				Debug2::debug( '[LQIP] ❌ No credit' );
				Admin_Display::error( Error::msg( 'lack_of_quota' ) );
				return $this->_generate_placeholder_locally( $size );
			}

			// Generate LQIP
			list( $width, $height ) = explode( 'x', $size );
			$req_data = array(
				'width'		=> $width,
				'height'	=> $height,
				'url'		=> substr( $src, -5 ) === '.webp' ? substr( $src, 0, -5 ) : $src,
				'quality'	=> $this->_conf_lqip_qual,
			);

			// CHeck if the image is 404 first
			if ( File::is_404( $req_data[ 'url' ] ) ) {
				$this->_popup_and_save( $raw_size_and_src );
				$this->_append_exc( $src );
				Debug2::debug( '[LQIP] 404 before request [src] ' . $req_data[ 'url' ] );
				return $this->_generate_placeholder_locally( $size );
			}

			// Update request status
			$this->_summary[ 'curr_request' ] = time();
			self::save_summary();

			$json = Cloud::post( Cloud::SVC_LQIP, $req_data, 120 );
			if ( ! is_array( $json ) ) {
				return $this->_generate_placeholder_locally( $size );
			}

			if ( empty( $json[ 'lqip' ] ) || strpos( $json[ 'lqip' ], 'data:image/svg+xml' ) !== 0 ) {
				// image error, pop up the current queue
				$this->_popup_and_save( $raw_size_and_src );
				$this->_append_exc( $src );
				Debug2::debug( '[LQIP] wrong response format', $json );

				return $this->_generate_placeholder_locally( $size );
			}

			$data = $json[ 'lqip' ];

			Debug2::debug( '[LQIP] _generate_placeholder LQIP' );
		}

		// Write to file
		File::save( $file, $data, true );

		// Save summary data
		$this->_summary[ 'last_spent' ] = time() - $this->_summary[ 'curr_request' ];
		$this->_summary[ 'last_request' ] = $this->_summary[ 'curr_request' ];
		$this->_summary[ 'curr_request' ] = 0;
		$this->_popup_and_save( $raw_size_and_src );

		Debug2::debug( '[LQIP] saved LQIP ' . $file );

		return $data;
	}

	/**
	 * Check if the size is valid to send LQIP request or not
	 *
	 * @since  3.0
	 */
	private function _lqip_size_check( $size ) {
		$size = explode( 'x', $size );
		if ( $size[ 0 ] >= $this->_conf_lqip_min_w || $size[ 1 ] >= $this->_conf_lqip_min_h ) {
			return true;
		}

		return false;
	}

	/**
	 * Add to LQIP exclude list
	 *
	 * @since  3.4
	 */
	private function _append_exc( $src ) {
		$val = Conf::val( Base::O_MEDIA_LQIP_EXC );
		$val[] = $src;
		Conf::get_instance()->update( Base::O_MEDIA_LQIP_EXC, $val );
		Debug2::debug( '[LQIP] Appended to LQIP Excludes [URL] ' . $src );

		if ( ! empty( $this->_summary[ 'queue' ] ) ) {
			$changed = false;
			foreach ( $this->_summary[ 'queue' ] as $k => $raw_size_and_src ) {
				$size_and_src = explode( ' ', $raw_size_and_src, 2 );
				if ( empty( $size_and_src[ 1 ] ) ) {
					continue;
				}

				if ( $size_and_src[ 1 ] == $src ) {
					unset( $this->_summary[ 'queue' ][ $k ] );
					$changed = true;
				}
			}

			if ( $changed ) {
				self::save_summary();
			}
		}
	}

	/**
	 * Pop up the current request and save
	 *
	 * @since  3.0
	 */
	private function _popup_and_save( $raw_size_and_src ) {
		if ( ! empty( $this->_summary[ 'queue' ] ) && in_array( $raw_size_and_src, $this->_summary[ 'queue' ] ) ) {
			unset( $this->_summary[ 'queue' ][ array_search( $raw_size_and_src, $this->_summary[ 'queue' ] ) ] );
		}

		self::save_summary();
	}

	/**
	 * Clear all waiting queues
	 *
	 * @since  3.4
	 */
	public function clear_q() {
		if ( empty( $this->_summary[ 'queue' ] ) ) {
			return;
		}

		$this->_summary[ 'queue' ] = array();
		self::save_summary();

		$msg = __( 'Queue cleared successfully.', 'litespeed-cache' );
		Admin_Display::succeed( $msg );
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  2.5.1
	 * @access public
	 */
	public static function handler() {
		$instance = self::get_instance();

		$type = Router::verify_type();

		switch ( $type ) {
			case self::TYPE_GENERATE :
				self::cron( true );
				break;

			case self::TYPE_CLEAR_Q :
				$instance->clear_q();
				break;

			default:
				break;
		}

		Admin::redirect();
	}

}