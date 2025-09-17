<?php
/**
 * The admin-panel specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    LiteSpeed_Cache
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Class Admin
 *
 * Wires admin-side hooks, actions, and safe redirects.
 */
class Admin extends Root {

	const LOG_TAG = '👮';

	const PAGE_EDIT_HTACCESS = 'litespeed-edit-htaccess';

	/**
	 * Initialize the class and set its properties.
	 * Runs in hook `after_setup_theme` when is_admin().
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Define LSCWP_MU_PLUGIN if in mu-plugins.
		if ( defined( 'WPMU_PLUGIN_DIR' ) && dirname( LSCWP_DIR ) === WPMU_PLUGIN_DIR && ! defined( 'LSCWP_MU_PLUGIN' ) ) {
			define( 'LSCWP_MU_PLUGIN', true );
		}

		self::debug( 'No cache due to Admin page' );

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		// Additional LiteSpeed assets on admin display (also registers menus).
		$this->cls( 'Admin_Display' );

		// Initialize admin actions.
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		// Add link to plugin list page.
		add_filter(
			'plugin_action_links_' . LSCWP_BASENAME,
			array( $this->cls( 'Admin_Display' ), 'add_plugin_links' )
		);
	}

	/**
	 * Callback that initializes the admin options for LiteSpeed Cache.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_init() {
		// Hook attachment upload auto optimization.
		if ( $this->conf( Base::O_IMG_OPTM_AUTO ) ) {
			add_filter( 'wp_update_attachment_metadata', array( $this, 'wp_update_attachment_metadata' ), 9999, 2 );
		}

		$this->_proceed_admin_action();

		// Terminate if user doesn't have access to settings.
		$capability = is_network_admin() ? 'manage_network_options' : 'manage_options';
		if ( ! current_user_can( $capability ) ) {
			return;
		}

		// Add privacy policy (since 2.2.6).
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			wp_add_privacy_policy_content( Core::NAME, Doc::privacy_policy() );
		}

		$this->cls( 'Media' )->after_admin_init();

		do_action( 'litespeed_after_admin_init' );

		if ( $this->cls( 'Router' )->esi_enabled() ) {
			add_action( 'in_widget_form', array( $this->cls( 'Admin_Display' ), 'show_widget_edit' ), 100, 3 );
			add_filter( 'widget_update_callback', __NAMESPACE__ . '\Admin_Settings::validate_widget_save', 10, 4 );
		}
	}

	/**
	 * Handle attachment metadata update.
	 *
	 * @since 4.0
	 *
	 * @param array $data    Attachment meta.
	 * @param int   $post_id Attachment ID.
	 * @return array Filtered meta.
	 */
	public function wp_update_attachment_metadata( $data, $post_id ) {
		$this->cls( 'Img_Optm' )->wp_update_attachment_metadata( $data, $post_id );
		return $data;
	}

	/**
	 * Run LiteSpeed admin actions routed via Router.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	private function _proceed_admin_action() {
		$action = Router::get_action();

		switch ( $action ) {
			case Router::ACTION_SAVE_SETTINGS:
				$this->cls( 'Admin_Settings' )->save( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				break;

			case Router::ACTION_SAVE_SETTINGS_NETWORK:
				$this->cls( 'Admin_Settings' )->network_save( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				break;

			default:
				break;
		}
	}

	/**
	 * Clean up the input (array or scalar) of any extra slashes/spaces.
	 *
	 * @since 1.0.4
	 *
	 * @param mixed $input The input value to clean.
	 * @return mixed Cleaned value.
	 */
	public static function cleanup_text( $input ) {
		if ( is_array( $input ) ) {
			return array_map( __CLASS__ . '::cleanup_text', $input );
		}

		return stripslashes(trim($input));
	}

	/**
	 * After a LSCWP_CTRL action, redirect back to same page
	 * without nonce and action in the query string.
	 *
	 * If the redirect URL cannot be determined, redirects to the homepage.
	 *
	 * @since 1.0.12
	 *
	 * @param string|false $url Optional destination URL.
	 * @return void
	 */
	public static function redirect( $url = false ) {
		global $pagenow;

		// If originated, go back to referrer or home.
		if ( ! empty( $_GET['_litespeed_ori'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$ref = wp_get_referer();
			wp_safe_redirect( $ref ? $ref : get_home_url() );
			exit;
		}

		if ( ! $url ) {
			$clean = [];

			// Sanitize current query args while removing our internals.
			if ( ! empty( $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				foreach ( $_GET as $k => $v ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					if ( in_array( $k, array( Router::ACTION, Router::NONCE, Router::TYPE, 'litespeed_i' ), true ) ) {
						continue;
					}
					// Normalize to string for URL building.
					$clean[ $k ] = is_array( $v ) ? array_map( 'sanitize_text_field', wp_unslash( $v ) ) : sanitize_text_field( wp_unslash( $v ) );
				}
			}

			$qs = '';
			if ( ! empty( $clean ) ) {
				$qs = '?' . http_build_query( $clean );
			}

			$url = is_network_admin() ? network_admin_url( $pagenow . $qs ) : admin_url( $pagenow . $qs );
		}

		wp_safe_redirect( $url );
		exit;
	}
}
