<?php
/**
 * The Placeholder class.
 *
 * Handles responsive placeholders (LQIP), admin column rendering,
 * queueing, and generation logic (local and cloud).
 *
 * @since   3.0
 * @package LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Class Placeholder
 */
class Placeholder extends Base {

	/**
	 * Action type: generate.
	 *
	 * @var string
	 */
	const TYPE_GENERATE = 'generate';

	/**
	 * Action type: clear queue.
	 *
	 * @var string
	 */
	const TYPE_CLEAR_Q = 'clear_q';

	/**
	 * Whether responsive placeholders are enabled.
	 *
	 * @var bool
	 */
	private $_conf_placeholder_resp;

	/**
	 * SVG template for responsive placeholders.
	 *
	 * @var string
	 */
	private $_conf_placeholder_resp_svg;

	/**
	 * Whether LQIP generation via cloud is enabled.
	 *
	 * @var bool
	 */
	private $_conf_lqip;

	/**
	 * LQIP JPEG quality.
	 *
	 * @var int
	 */
	private $_conf_lqip_qual;

	/**
	 * Minimum width for LQIP generation.
	 *
	 * @var int
	 */
	private $_conf_lqip_min_w;

	/**
	 * Minimum height for LQIP generation.
	 *
	 * @var int
	 */
	private $_conf_lqip_min_h;

	/**
	 * Background color for SVG placeholders.
	 *
	 * @var string
	 */
	private $_conf_placeholder_resp_color;

	/**
	 * Whether LQIP generation is async (queued).
	 *
	 * @var bool
	 */
	private $_conf_placeholder_resp_async;

	/**
	 * Default placeholder data (fallback).
	 *
	 * @var string
	 */
	private $_conf_ph_default;

	/**
	 * In-memory map of generated placeholders for current request.
	 *
	 * @var array<string,string>
	 */
	private $_placeholder_resp_dict = [];

	/**
	 * Keys currently queued within this request.
	 *
	 * @var array<int,string>
	 */
	private $_ph_queue = [];

	/**
	 * Stats & request summary for throttling.
	 *
	 * @var array<string,mixed>
	 */
	protected $_summary;

	/**
	 * Init
	 *
	 * @since 3.0
	 */
	public function __construct() {
		$this->_conf_placeholder_resp       = defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( self::O_MEDIA_PLACEHOLDER_RESP );
		$this->_conf_placeholder_resp_svg   = $this->conf( self::O_MEDIA_PLACEHOLDER_RESP_SVG );
		$this->_conf_lqip                   = ! defined( 'LITESPEED_GUEST_OPTM' ) && $this->conf( self::O_MEDIA_LQIP );
		$this->_conf_lqip_qual              = $this->conf( self::O_MEDIA_LQIP_QUAL );
		$this->_conf_lqip_min_w             = $this->conf( self::O_MEDIA_LQIP_MIN_W );
		$this->_conf_lqip_min_h             = $this->conf( self::O_MEDIA_LQIP_MIN_H );
		$this->_conf_placeholder_resp_async = $this->conf( self::O_MEDIA_PLACEHOLDER_RESP_ASYNC );
		$this->_conf_placeholder_resp_color = $this->conf( self::O_MEDIA_PLACEHOLDER_RESP_COLOR );
		$this->_conf_ph_default             = $this->conf(self::O_MEDIA_LAZY_PLACEHOLDER) ? $this->conf(self::O_MEDIA_LAZY_PLACEHOLDER) : LITESPEED_PLACEHOLDER;

		$this->_summary = self::get_summary();
	}

	/**
	 * Init Placeholder.
	 */
	public function init() {
		Debug2::debug2( '[LQIP] init' );

		add_action( 'litespeed_after_admin_init', [ $this, 'after_admin_init' ] );
	}

	/**
	 * Display column in Media.
	 *
	 * @since 3.0
	 * @access public
	 */
	public function after_admin_init() {
		if ( $this->_conf_lqip ) {
			add_filter( 'manage_media_columns', [ $this, 'media_row_title' ] );
			add_filter( 'manage_media_custom_column', [ $this, 'media_row_actions' ], 10, 2 );
			add_action( 'litespeed_media_row_lqip', [ $this, 'media_row_con' ] );
		}
	}

	/**
	 * Media Admin Menu -> LQIP column header.
	 *
	 * @since 3.0
	 * @param array<string,string> $posts_columns Columns.
	 * @return array<string,string>
	 */
	public function media_row_title( $posts_columns ) {
		$posts_columns['lqip'] = __( 'LQIP', 'litespeed-cache' );

		return $posts_columns;
	}

	/**
	 * Media Admin Menu -> LQIP Column renderer trigger.
	 *
	 * @since 3.0
	 * @param string $column_name Column name.
	 * @param int    $post_id     Attachment ID.
	 * @return void
	 */
	public function media_row_actions( $column_name, $post_id ) {
		if ( 'lqip' !== $column_name ) {
			return;
		}

		do_action( 'litespeed_media_row_lqip', $post_id );
	}

	/**
	 * Display LQIP column.
	 *
	 * @since 3.0
	 * @param int $post_id Attachment ID.
	 * @return void
	 */
	public function media_row_con( $post_id ) {
		$meta_value = wp_get_attachment_metadata( $post_id );

		if ( empty( $meta_value['file'] ) ) {
			return;
		}

		$total_files = 0;

		// List all sizes.
		$all_sizes = [ $meta_value['file'] ];
		$size_path = pathinfo( $meta_value['file'], PATHINFO_DIRNAME ) . '/';
		if ( ! empty( $meta_value['sizes'] ) && is_array( $meta_value['sizes'] ) ) {
			foreach ( $meta_value['sizes'] as $v ) {
				if ( ! empty( $v['file'] ) ) {
					$all_sizes[] = $size_path . $v['file'];
				}
			}
		}

		foreach ( $all_sizes as $short_path ) {
			$lqip_folder = LITESPEED_STATIC_DIR . '/lqip/' . $short_path;

			if ( is_dir( $lqip_folder ) ) {
				Debug2::debug( '[LQIP] Found folder: ' . $short_path );

				// List all files.
				foreach ( scandir( $lqip_folder ) as $v ) {
					if ( '.' === $v || '..' === $v ) {
						continue;
					}

					if ( 0 === $total_files ) {
						echo '<div class="litespeed-media-lqip"><img src="' .
							esc_url( Str::trim_quotes( File::read( $lqip_folder . '/' . $v ) ) ) .
							'" alt="' .
							esc_attr( sprintf( __( 'LQIP image preview for size %s', 'litespeed-cache' ), $v ) ) .
							'"></div>';
					}

					echo '<div class="litespeed-media-size"><a href="' . esc_url( Str::trim_quotes( File::read( $lqip_folder . '/' . $v ) ) ) . '" target="_blank">' . esc_html( $v ) . '</a></div>';

					++$total_files;
				}
			}
		}

		if ( 0 === $total_files ) {
			echo '—';
		}
	}

	/**
	 * Replace image HTML with placeholder-based lazy version.
	 *
	 * @since 3.0
	 * @param string $html Original <img> HTML.
	 * @param string $src  Image source URL.
	 * @param string $size Requested size (e.g. "300x200").
	 * @return string Modified HTML.
	 */
	public function replace( $html, $src, $size ) {
		// Check if need to enable responsive placeholder or not.
		$ph_candidate     = $this->_placeholder( $src, $size );
		$this_placeholder = $ph_candidate ? $ph_candidate : $this->_conf_ph_default;

		$additional_attr = '';
		if ( $this->_conf_lqip && $this_placeholder !== $this->_conf_ph_default ) {
			Debug2::debug2( '[LQIP] Use resp LQIP [size] ' . $size );
			$additional_attr = ' data-placeholder-resp="' . esc_attr( Str::trim_quotes( $size ) ) . '"';
		}

		$snippet = ( defined( 'LITESPEED_GUEST_OPTM' ) || $this->conf( self::O_OPTM_NOSCRIPT_RM ) ) ? '' : '<noscript>' . $html . '</noscript>';

		$html = preg_replace(
			[
				'/\s+src=/i',
				'/\s+srcset=/i',
				'/\s+sizes=/i',
			],
			[
				' data-src=',
				' data-srcset=',
				' data-sizes=',
			],
			$html
		);
		$html = preg_replace(
			'/<img\s+/i',
			'<img data-lazyloaded="1"' . $additional_attr . ' src="' . Str::trim_quotes($this_placeholder) . '" ',
			$html
		);

		// $html    = str_replace( array( ' src=', ' srcset=', ' sizes=' ), array( ' data-src=', ' data-srcset=', ' data-sizes=' ), $html );
		// $html    = str_replace( '<img ', '<img data-lazyloaded="1"' . $additional_attr . ' src="' . esc_url( Str::trim_quotes( $this_placeholder ) ) . '" ', $html );
		$snippet = $html . $snippet;

		return $snippet;
	}

	/**
	 * Generate responsive placeholder (or schedule generation).
	 *
	 * @since 2.5.1
	 * @access private
	 * @param string $src  Image source URL.
	 * @param string $size Size string "WIDTHxHEIGHT".
	 * @return string|false Data URL placeholder or false.
	 */
	private function _placeholder( $src, $size ) {
		// Low Quality Image Placeholders.
		if ( ! $size ) {
			Debug2::debug2( '[LQIP] no size ' . $src );
			return false;
		}

		if ( ! $this->_conf_placeholder_resp ) {
			return false;
		}

		// If use local generator.
		if ( ! $this->_conf_lqip || ! $this->_lqip_size_check( $size ) ) {
			return $this->_generate_placeholder_locally( $size );
		}

		Debug2::debug2( '[LQIP] Resp LQIP process [src] ' . $src . ' [size] ' . $size );

		$arr_key = $size . ' ' . $src;

		// Check if its already in dict or not.
		if ( ! empty( $this->_placeholder_resp_dict[ $arr_key ] ) ) {
			Debug2::debug2( '[LQIP] already in dict' );

			return $this->_placeholder_resp_dict[ $arr_key ];
		}

		// Need to generate the responsive placeholder.
		$placeholder_realpath = $this->_placeholder_realpath( $src, $size ); // todo: give offload API.
		if ( file_exists( $placeholder_realpath ) ) {
			Debug2::debug2( '[LQIP] file exists' );
			$this->_placeholder_resp_dict[ $arr_key ] = File::read( $placeholder_realpath );

			return $this->_placeholder_resp_dict[ $arr_key ];
		}

		// Prevent repeated requests in same request.
		if ( in_array( $arr_key, $this->_ph_queue, true ) ) {
			Debug2::debug2( '[LQIP] file bypass generating due to in queue' );
			return $this->_generate_placeholder_locally( $size );
		}

		$hit = Utility::str_hit_array( $src, $this->conf( self::O_MEDIA_LQIP_EXC ) );
		if ( $hit ) {
			Debug2::debug2( '[LQIP] file bypass generating due to exclude setting [hit] ' . $hit );
			return $this->_generate_placeholder_locally( $size );
		}

		$this->_ph_queue[] = $arr_key;

		// Send request to generate placeholder.
		if ( ! $this->_conf_placeholder_resp_async ) {
			// If requested recently, bypass.
			if ( $this->_summary && ! empty( $this->_summary['curr_request'] ) && ( time() - (int) $this->_summary['curr_request'] ) < 300 ) {
				Debug2::debug2( '[LQIP] file bypass generating due to interval limit' );
				return false;
			}
			// Generate immediately.
			$this->_placeholder_resp_dict[ $arr_key ] = $this->_generate_placeholder( $arr_key );

			return $this->_placeholder_resp_dict[ $arr_key ];
		}

		// Prepare default svg placeholder as tmp placeholder.
		$tmp_placeholder = $this->_generate_placeholder_locally( $size );

		// Store it to prepare for cron.
		$queue = $this->load_queue( 'lqip' );
		if ( in_array( $arr_key, $queue, true ) ) {
			Debug2::debug2( '[LQIP] already in queue' );

			return $tmp_placeholder;
		}

		if ( count( $queue ) > 500 ) {
			Debug2::debug2( '[LQIP] queue is full' );

			return $tmp_placeholder;
		}

		$queue[] = $arr_key;
		$this->save_queue( 'lqip', $queue );
		Debug2::debug( '[LQIP] Added placeholder queue' );

		return $tmp_placeholder;
	}

	/**
	 * Generate realpath of placeholder file.
	 *
	 * @since 2.5.1
	 * @access private
	 * @param string $src  Image source URL.
	 * @param string $size Size string "WIDTHxHEIGHT".
	 * @return string Absolute file path.
	 */
	private function _placeholder_realpath( $src, $size ) {
		// Use LQIP Cloud generator, each image placeholder will be separately stored.

		// Compatibility with WebP and AVIF.
		$src = Utility::drop_webp( $src );

		$filepath_prefix = $this->_build_filepath_prefix( 'lqip' );

		// External images will use cache folder directly.
		$domain = wp_parse_url( $src, PHP_URL_HOST );
		if ( $domain && ! Utility::internal( $domain ) ) {
			// todo: need to improve `util:internal()` to include `CDN::internal()`
			$md5 = md5($src);

			return LITESPEED_STATIC_DIR . $filepath_prefix . 'remote/' . substr( $md5, 0, 1 ) . '/' . substr( $md5, 1, 1 ) . '/' . $md5 . '.' . $size;
		}

		// Drop domain.
		$short_path = Utility::att_short_path( $src );

		return LITESPEED_STATIC_DIR . $filepath_prefix . $short_path . '/' . $size;
	}

	/**
	 * Cron placeholder generation.
	 *
	 * @since 2.5.1
	 * @param bool $do_continue If true, process full queue in one run.
	 * @return void
	 */
	public static function cron( $do_continue = false ) {
		$_instance = self::cls();

		$queue = $_instance->load_queue( 'lqip' );

		if ( empty( $queue ) ) {
			return;
		}

		// For cron, need to check request interval too.
		if ( ! $do_continue ) {
			if ( ! empty( $_instance->_summary['curr_request'] ) && ( time() - (int) $_instance->_summary['curr_request'] ) < 300 ) {
				Debug2::debug( '[LQIP] Last request not done' );
				return;
			}
		}

		foreach ( $queue as $v ) {
			Debug2::debug( '[LQIP] cron job [size] ' . $v );

			$res = $_instance->_generate_placeholder( $v, true );

			// Exit queue if out of quota.
			if ( 'out_of_quota' === $res ) {
				return;
			}

			// Only request first one unless continuing.
			if ( ! $do_continue ) {
				return;
			}
		}
	}

	/**
	 * Generate placeholder locally (SVG).
	 *
	 * @since 3.0
	 * @access private
	 * @param string $size Size string "WIDTHxHEIGHT".
	 * @return string Data URL for SVG placeholder.
	 */
	private function _generate_placeholder_locally( $size ) {
		Debug2::debug2( '[LQIP] _generate_placeholder local [size] ' . $size );

		$size = explode( 'x', $size );

		$svg = str_replace(
			[ '{width}', '{height}', '{color}' ],
			[ (int) $size[0], (int) $size[1], $this->_conf_placeholder_resp_color ],
			$this->_conf_placeholder_resp_svg
		);

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	/**
	 * Send to LiteSpeed API to generate placeholder (and persist).
	 *
	 * @since 2.5.1
	 * @access private
	 * @param string $raw_size_and_src Concatenated "SIZE SRC".
	 * @param bool   $from_cron        If true, called from cron context.
	 * @return string Data URL placeholder.
	 */
	private function _generate_placeholder( $raw_size_and_src, $from_cron = false ) {
		// Parse containing size and src info.
		$size_and_src = explode( ' ', $raw_size_and_src, 2 );
		$size         = $size_and_src[0];

		if ( empty( $size_and_src[1] ) ) {
			$this->_popup_and_save( $raw_size_and_src );
			Debug2::debug( '[LQIP] ❌ No src [raw] ' . $raw_size_and_src );
			return $this->_generate_placeholder_locally( $size );
		}

		$src = $size_and_src[1];

		$file = $this->_placeholder_realpath( $src, $size );

		// Local generate SVG to serve (repeated here to clear queue if settings changed).
		if ( ! $this->_conf_lqip || ! $this->_lqip_size_check( $size ) ) {
			$data = $this->_generate_placeholder_locally( $size );
		} else {
			$err       = false;
			$allowance = Cloud::cls()->allowance( Cloud::SVC_LQIP, $err );
			if ( ! $allowance ) {
				Debug2::debug( '[LQIP] ❌ No credit: ' . $err );
				$err && Admin_Display::error( Error::msg( $err ) );

				if ( $from_cron ) {
					return 'out_of_quota';
				}

				return $this->_generate_placeholder_locally( $size );
			}

			// Generate LQIP.
			list( $width, $height ) = explode( 'x', $size );
			$req_data               = [
				'width'   => (int) $width,
				'height'  => (int) $height,
				'url'     => Utility::drop_webp( $src ),
				'quality' => (int) $this->_conf_lqip_qual,
			];

			// Check if the image is 404 first.
			if ( File::is_404( $req_data['url'] ) ) {
				$this->_popup_and_save( $raw_size_and_src, true );
				$this->_append_exc( $src );
				Debug2::debug( '[LQIP] 404 before request [src] ' . $req_data['url'] );
				return $this->_generate_placeholder_locally( $size );
			}

			// Update request status.
			$this->_summary['curr_request'] = time();
			self::save_summary();

			$json = Cloud::post( Cloud::SVC_LQIP, $req_data, 120 );
			if ( ! is_array( $json ) ) {
				return $this->_generate_placeholder_locally( $size );
			}

			if ( empty( $json['lqip'] ) || 0 !== strpos( $json['lqip'], 'data:image/svg+xml' ) ) {
				// Image error, pop up the current queue.
				$this->_popup_and_save( $raw_size_and_src, true );
				$this->_append_exc( $src );
				Debug2::debug( '[LQIP] wrong response format', $json );

				return $this->_generate_placeholder_locally( $size );
			}

			$data = $json['lqip'];

			Debug2::debug( '[LQIP] _generate_placeholder LQIP' );
		}

		// Write to file.
		File::save( $file, $data, true );

		// Save summary data.
		$this->_summary['last_spent']   = time() - (int) $this->_summary['curr_request'];
		$this->_summary['last_request'] = $this->_summary['curr_request'];
		$this->_summary['curr_request'] = 0;
		self::save_summary();
		$this->_popup_and_save( $raw_size_and_src );

		Debug2::debug( '[LQIP] saved LQIP ' . $file );

		return $data;
	}

	/**
	 * Check if the size is valid to send LQIP request or not.
	 *
	 * @since 3.0
	 * @param string $size Size string "WIDTHxHEIGHT".
	 * @return bool True if meets minimums.
	 */
	private function _lqip_size_check( $size ) {
		$size = explode( 'x', $size );
		if ( ( (int) $size[0] >= (int) $this->_conf_lqip_min_w ) || ( (int) $size[1] >= (int) $this->_conf_lqip_min_h ) ) {
			return true;
		}

		Debug2::debug2( '[LQIP] Size too small' );

		return false;
	}

	/**
	 * Add to LQIP exclude list.
	 *
	 * @since 3.4
	 * @param string $src Image URL.
	 * @return void
	 */
	private function _append_exc( $src ) {
		$val   = $this->conf( self::O_MEDIA_LQIP_EXC );
		$val[] = $src;
		$this->cls( 'Conf' )->update( self::O_MEDIA_LQIP_EXC, $val );
		Debug2::debug( '[LQIP] Appended to LQIP Excludes [URL] ' . $src );
	}

	/**
	 * Pop up the current request from queue and save.
	 *
	 * @since 3.0
	 * @param string $raw_size_and_src Concatenated "SIZE SRC".
	 * @param bool   $append_to_exc    If true, also add to exclusion list.
	 * @return void
	 */
	private function _popup_and_save( $raw_size_and_src, $append_to_exc = false ) {
		$queue = $this->load_queue( 'lqip' );
		if ( ! empty( $queue ) && in_array( $raw_size_and_src, $queue, true ) ) {
			$idx = array_search( $raw_size_and_src, $queue, true );
			if ( false !== $idx ) {
				unset( $queue[ $idx ] );
			}
		}

		if ( $append_to_exc ) {
			$size_and_src = explode( ' ', $raw_size_and_src, 2 );
			if (isset( $size_and_src[1] ) && $size_and_src[1]) {
				$this_src = $size_and_src[1];
				// Append to lqip exc setting first.
				$this->_append_exc( $this_src );

				// Check if other queues contain this src or not.
				if ( $queue ) {
					foreach ( $queue as $k => $raw_item ) {
						$parsed = explode( ' ', $raw_item, 2 );
						if ( empty( $parsed[1] ) ) {
							continue;
						}

						if ( $parsed[1] === $this_src ) {
							unset( $queue[ $k ] );
						}
					}
				}
			}
		}

		$this->save_queue( 'lqip', $queue );
	}

	/**
	 * Handle all request actions from main cls.
	 *
	 * @since 2.5.1
	 * @access public
	 * @return void
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ( $type ) {
			case self::TYPE_GENERATE:
            self::cron( true );
				break;

			case self::TYPE_CLEAR_Q:
            $this->clear_q( 'lqip' );
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
