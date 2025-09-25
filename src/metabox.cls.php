<?php
/**
 * The class to operate post editor metabox settings.
 *
 * @since 4.7
 * @package LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Class Metabox
 *
 * Registers and handles LiteSpeed options shown in the post/page edit screen.
 */
class Metabox extends Root {

	const LOG_TAG = '📦';

	const POST_NONCE_ACTION = 'post_nonce_action';

	/**
	 * Map of metabox settings keys to labels.
	 *
	 * @var array
	 */
	private $_postmeta_settings;

	/**
	 * Init the setting list.
	 *
	 * @since 4.7
	 */
	public function __construct() {
		// Append meta box.
		$this->_postmeta_settings = array(
			'litespeed_no_cache'        => __( 'Disable Cache', 'litespeed-cache' ),
			'litespeed_no_image_lazy'   => __( 'Disable Image Lazyload', 'litespeed-cache' ),
			'litespeed_no_vpi'          => __( 'Disable VPI', 'litespeed-cache' ),
			'litespeed_vpi_list'        => __( 'Viewport Images', 'litespeed-cache' ),
			'litespeed_vpi_list_mobile' => __( 'Viewport Images', 'litespeed-cache' ) . ' - ' . __( 'Mobile', 'litespeed-cache' ),
		);
	}

	/**
	 * Register post edit settings.
	 *
	 * @since 4.7
	 * @return void
	 */
	public function register_settings() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_box_settings' ), 15, 2 );
		add_action( 'attachment_updated', array( $this, 'save_meta_box_settings' ), 15, 2 );
	}

	/**
	 * Register meta box.
	 *
	 * @since 4.7
	 *
	 * @param string $post_type Current post type.
	 * @return void
	 */
	public function add_meta_boxes( $post_type ) {
		if ( apply_filters( 'litespeed_bypass_metabox', false, $post_type ) ) {
			return;
		}
		$post_type_obj = get_post_type_object( $post_type );
		if ( ! empty( $post_type_obj ) && ! $post_type_obj->public ) {
			self::debug( 'post type public=false, bypass add_meta_boxes' );
			return;
		}
		add_meta_box( 'litespeed_meta_boxes', 'LiteSpeed', array( $this, 'meta_box_options' ), $post_type, 'side', 'core' );
	}

	/**
	 * Show meta box content.
	 *
	 * @since 4.7
	 * @return void
	 */
	public function meta_box_options() {
		require_once LSCWP_DIR . 'tpl/inc/metabox.php';
	}

	/**
	 * Save settings.
	 *
	 * @since 4.7
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post   Post object.
	 * @return void
	 */
	public function save_meta_box_settings( $post_id, $post ) {
		global $pagenow;

		self::debug( 'Maybe save post2 [post_id] ' . $post_id );

		if ( 'post.php' !== $pagenow || ! $post || ! is_object( $post ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! $this->cls( 'Router' )->verify_nonce( self::POST_NONCE_ACTION ) ) {
			return;
		}

		self::debug( 'Saving post [post_id] ' . $post_id );

		foreach ($this->_postmeta_settings as $k => $v) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
			$val = isset($_POST[$k]) ? $_POST[$k] : false;
			$this->save($post_id, $k, $val);
		}
	}

	/**
	 * Load setting per post.
	 *
	 * @since 4.7
	 *
	 * @param string   $conf    Meta key to load.
	 * @param int|bool $post_id Optional specific post ID, defaults to current query object.
	 * @return mixed|null Meta value or null when not set.
	 */
	public function setting( $conf, $post_id = false ) {
		// Check if has metabox non-cacheable setting or not.
		if ( ! $post_id ) {
			$home_id = (int) get_option( 'page_for_posts' );
			if ( is_singular() ) {
				$post_id = get_the_ID();
			} elseif ( $home_id > 0 && is_home() ) {
				$post_id = $home_id;
			}
		}

		$val = $post_id ? get_post_meta( $post_id, $conf, true ) : null;
		if ( $val ) {
			return $val;
		}

		return null;
	}

	/**
	 * Save a metabox value.
	 *
	 * @since 4.7
	 *
	 * @param int         $post_id  Post ID.
	 * @param string      $name     Meta key name.
	 * @param string|bool $val      Value to save.
	 * @param bool        $is_append If true, append to existing list values.
	 * @return void
	 */
	public function save( $post_id, $name, $val, $is_append = false ) {
		if ( false !== strpos( $name, VPI::POST_META ) ) {
			$val = Utility::sanitize_lines( $val, 'basename,drop_webp' );
		}

		// Load existing data if has set.
		if ( $is_append ) {
			$existing_data = $this->setting( $name, $post_id );
			if ( $existing_data ) {
				$existing_data = Utility::sanitize_lines( $existing_data, 'basename' );
				$val           = array_unique( array_merge( $val, $existing_data ) );
			}
		}

		if ( $val ) {
			update_post_meta( $post_id, $name, $val );
		} else {
			delete_post_meta( $post_id, $name );
		}
	}

	/**
	 * Load exclude images per post.
	 *
	 * @since 4.7
	 *
	 * @param array $exclude_list Current exclude list.
	 * @return array Modified exclude list.
	 */
	public function lazy_img_excludes( $exclude_list ) {
		$is_mobile = $this->_separate_mobile();
		$excludes  = $this->setting( $is_mobile ? VPI::POST_META_MOBILE : VPI::POST_META );
		if ( null !== $excludes ) {
			$excludes = Utility::sanitize_lines( $excludes, 'basename' );
			if ( $excludes ) {
				// Check if contains `data:` (invalid result, need to clear existing result) or not.
				if ( Utility::str_hit_array( 'data:', $excludes ) ) {
					$this->cls( 'VPI' )->add_to_queue();
				} else {
					return array_merge( $exclude_list, $excludes );
				}
			}

			return $exclude_list;
		}

		$this->cls( 'VPI' )->add_to_queue();

		return $exclude_list;
	}
}
