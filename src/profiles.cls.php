<?php
/**
 * The profiles class.
 *
 * @since  5.1.0
 */
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

class Profiles extends Import {
	protected $_summary;

	const MAX_BACKUPS = 5;

	const TYPE_APPLY = 'apply';
	const TYPE_REVERT = 'revert';

	const PROFILES_DATA_DIR = LSCWP_DIR . 'data/profiles';
	const PROFILES_STATIC_DIR = LITESPEED_STATIC_DIR . '/profiles';

	/**
	 * Get a builtin profile's path from its extensionless basename
	 *
	 * @since  5.1.0
	 * @access public
	 */
	public static function builtin( $name ) {
		return path_join( self::PROFILES_DATA_DIR, $name . '.data' );
	}

	/**
	 * Get a user's backup profile path from its extensionless basename
	 *
	 * @since  5.1.0
	 * @access public
	 */
	public static function user( $name ) {
		return path_join( self::PROFILES_STATIC_DIR, $name . '.data' );
	}

	/**
	 * Init
	 *
	 * @since  5.1.0
	 */
	public function __construct() {
		Debug2::debug( 'Profiles init' );

		$this->_summary = self::get_summary();
	}

	/**
	 * Apply a profile's settings from a file
	 *
	 * @since  5.1.0
	 * @access public
	 */
	public function backup() {
		$timestamp = time();
		$filename = 'backup-' . $timestamp;
		$data = $this->export( true );

		// Update log
		$this->_summary[ 'backup' ] = $filename;
		$this->_summary[ 'backup_time' ] = $timestamp;
		self::save_summary();

		$path = self::user( $filename );
		File::save( $path, $data, true );
		Debug2::debug( 'Profiles: Backup saved to ' . $path );

		$this->prune_backups();
	}	

	/**
	 * Remove extra backup profile files
	 *
	 * @since  5.1.0
	 * @access public
	 */
	public function prune_backups() {
		global $wp_filesystem;
		require_once ( ABSPATH . '/wp-admin/includes/file.php' );
		\WP_Filesystem();
		clearstatcache();

		$backups = array_map(
			function( $file ) { return basename( $file['name'], '.data' ); },
			$wp_filesystem->dirlist( self::PROFILES_STATIC_DIR )
		);
		rsort( $backups );

		array_map(
			function( $file ) {
				global $wp_filesystem;
				$backup = self::user( $file );
				$wp_filesystem->delete( $backup );
				Debug2::debug('Profiles: Deleted old backup from ' . $backup );
			},
			array_slice( $backups, self::MAX_BACKUPS )
		);
	}	

	/**
	 * Apply a profile's settings from a file
	 *
	 * @since  5.1.0
	 * @access public
	 */
	public function apply( $profile = false ) {
		if ( false === $profile ) {
			return;
		}

		$this->backup();

		// Update log
		$this->_summary[ 'profile' ] = $profile;
		$this->_summary[ 'profile_time' ] = time();
		self::save_summary();
	}	

	/**
	 * Revert to the user's previous backup profile
	 *
	 * @since  5.1.0
	 * @access public
	 */
	public function revert() {
		// Update log
		$this->_summary[ 'profile' ] = esc_html__( 'Previous', 'litespeed-cache' );
		$this->_summary[ 'profile_time' ] = time();
		self::save_summary();
	}	

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  5.1.0
	 * @access public
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ( $type ) {
			case self::TYPE_APPLY:
				$this->apply( ! empty( $_GET['profile'] ) ? $_GET['profile'] : false );
				break;

			case self::TYPE_REVERT:
				$this->revert();
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
