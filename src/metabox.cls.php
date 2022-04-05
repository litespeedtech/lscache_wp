<?php
/**
 * The class to operate post editor metabox settings
 *
 * @since 		4.7
 * @package    	Core
 * @subpackage 	Core/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

class Metabox extends Root {
	const LOG_TAG = '📦';

	const POST_NONCE_ACTION = 'post_nonce_action';

	private $_postmeta_settings;

	/**
	 * Get the setting list
	 * @since 4.7
	 */
	public function __construct() {
		// Append meta box
		$this->_postmeta_settings = array(
			'litespeed_no_cache' => __( 'Disable Cache', 'litespeed-cache' ),
			'litespeed_no_image_lazy' => __( 'Disable Image Lazyload', 'litespeed-cache' ),
			'litespeed_no_vpi' => __( 'Disable VPI', 'litespeed-cache' ),
			'litespeed_vpi_list' => __( 'Viewport Images', 'litespeed-cache' ),
			'litespeed_vpi_list_mobile' => __( 'Viewport Images', 'litespeed-cache' ) . ' - ' . __( 'Mobile', 'litespeed-cache' ),
		);
	}

	/**
	 * Register post edit settings
	 * @since 4.7
	 */
	public function register_settings() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_box_settings' ), 15, 2 );
	}

	/**
	 * Regsiter meta box
	 * @since 4.7
	 */
	public function add_meta_boxes( $post_type ) {
		if ( apply_filters( 'litespeed_bypass_metabox', false, $post_type ) ) {
			return;
		}
		add_meta_box( 'litespeed_meta_boxes', __( 'LiteSpeed Options', 'litespeed-cache' ), array( $this, 'meta_box_options' ), $post_type, 'side', 'core' );
	}

	/**
	 * Show meta box content
	 * @since 4.7
	 */
	public function meta_box_options() {
		require_once LSCWP_DIR . 'tpl/inc/metabox.php';
	}

	/**
	 * Save settings
	 * @since 4.7
	 */
	public function save_meta_box_settings( $post_id, $post ) {
		global $pagenow;

		self::debug( 'Maybe save post [post_id] ' . $post_id );

		if ( $pagenow != 'post.php' || ! $post || ! is_object( $post ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! Router::verify_nonce( self::POST_NONCE_ACTION ) ) {
			return;
		}

		self::debug( 'Saving post [post_id] ' . $post_id );

		foreach ( $this->_postmeta_settings as $k => $v ) {
			if ( isset( $_POST[ $k ] ) )
				update_post_meta( $post_id, $k, $_POST[ $k ] );
			else
				delete_post_meta( $post_id, $k );
		}
	}

	/**
	 * Load setting per post
	 * @since 4.7
	 */
	public function setting( $conf ) {
		// Check if has metabox non-cacheable setting or not
		if ( is_singular() ) {
			$post_id = get_the_ID();
			if ( $post_id && $val = get_post_meta( $post_id, $conf, true ) ) {
				return $val;
			}
		}

		return false;
	}

	/**
	 * Load exclude images per post
	 * @since 4.7
	 */
	public function lazy_img_excludes( $list ) {
		$is_mobile = wp_is_mobile() || apply_filters( 'litespeed_is_mobile', false );
		$excludes = $this->setting( $is_mobile ? 'litespeed_vpi_list_mobile' : 'litespeed_vpi_list' );
		$excludes = Utility::sanitize_lines( $excludes, 'uri' );

		if ( ! $excludes ) {
			return $list;
		}

		return array_merge( $list, $excludes );
	}
}
