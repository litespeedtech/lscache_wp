<?php
/**
 * The class to store and manage litespeed db data.
 *
 * @since      	1.3.1
 * @package    	LiteSpeed
 * @subpackage 	LiteSpeed/src
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

class Data extends Instance {
	private $_db_updater = array(
		'3.5.0.3'	=> array(
			'litespeed_update_3_5',
		),
	);

	private $_db_site_updater = array(
		// Example
		// '2.0'	=> array(
		// 	'litespeed_update_site_2_0',
		// ),
	);

	protected static $_instance;

	const TB_CSSJS = 'litespeed_cssjs';
	const TB_IMG_OPTM = 'litespeed_img_optm';
	const TB_IMG_OPTMING = 'litespeed_img_optming'; // working table
	const TB_AVATAR = 'litespeed_avatar';
	const TB_CRAWLER = 'litespeed_crawler';
	const TB_CRAWLER_BLACKLIST = 'litespeed_crawler_blacklist';

	/**
	 * Init
	 *
	 * @since  1.3.1
	 * @access protected
	 */
	protected function __construct() {
	}

	/**
	 * Correct table existance
	 *
	 * Call when activate -> upadte_confs()
	 * Call when upadte_confs()
	 *
	 * @since  3.0
	 * @access public
	 */
	public function correct_tb_existance() {
		// CSS JS optm
		if ( Optimize::need_db() ) {
			$this->tb_create( 'cssjs' );
		}

		// Gravatar
		if ( Conf::val( Base::O_DISCUSS_AVATAR_CACHE ) ) {
			$this->tb_create( 'avatar' );
		}

		// Crawler
		if ( Conf::val( Base::O_CRAWLER ) ) {
			$this->tb_create( 'crawler' );
			$this->tb_create( 'crawler_blacklist' );
		}

		// Image optm is a bit different. Only trigger creation when sending requests. Drop when destroying.
	}

	/**
	 * Upgrade conf to latest format version from previous versions
	 *
	 * NOTE: Only for v3.0+
	 *
	 * @since 3.0
	 * @access public
	 */
	public function conf_upgrade( $ver ) {
		// Skip count check if `Use Primary Site Configurations` is on
		// Deprecated since v3.0 as network primary site didn't override the subsites conf yet
		// if ( ! is_main_site() && ! empty ( $this->_site_options[ self::NETWORK_O_USE_PRIMARY ] ) ) {
		// 	return;
		// }

		if ( $this->_get_upgrade_lock() ) {
			return;
		}

		$this->_set_upgrade_lock( true );

		require_once LSCWP_DIR . 'src/data.upgrade.func.php';

		// Init log manually
		if ( Conf::val( Base::O_DEBUG ) ) {
			Debug2::init();
		}

		foreach ( $this->_db_updater as $k => $v ) {
			if ( version_compare( $ver, $k, '<' ) ) {
				// run each callback
				foreach ( $v as $v2 ) {
					Debug2::debug( "[Data] Updating [ori_v] $ver \t[to] $k \t[func] $v2" );
					call_user_func( $v2 );
				}
			}
		}

		// Reload options
		Conf::get_instance()->load_options();

		$this->correct_tb_existance();

		// Update version to latest
		Conf::delete_option( Base::_VER );
		Conf::add_option( Base::_VER, Core::VER );

		Debug2::debug( '[Data] Updated version to ' . Core::VER );

		$this->_set_upgrade_lock( false );

		! defined( 'LSWCP_EMPTYCACHE') && define( 'LSWCP_EMPTYCACHE', true );// clear all sites caches
		Purge::purge_all();

		Cloud::version_check( 'upgrade' );
	}

	/**
	 * Upgrade site conf to latest format version from previous versions
	 *
	 * NOTE: Only for v3.0+
	 *
	 * @since 3.0
	 * @access public
	 */
	public function conf_site_upgrade( $ver ) {
		if ( $this->_get_upgrade_lock() ) {
			return;
		}

		$this->_set_upgrade_lock( true );

		require_once LSCWP_DIR . 'src/data.upgrade.func.php';

		foreach ( $this->_db_site_updater as $k => $v ) {
			if ( version_compare( $ver, $k, '<' ) ) {
				// run each callback
				foreach ( $v as $v2 ) {
					Debug2::debug( "[Data] Updating site [ori_v] $ver \t[to] $k \t[func] $v2" );
					call_user_func( $v2 );
				}
			}
		}

		// Reload options
		Conf::get_instance()->load_site_options();

		Conf::delete_site_option( Base::_VER );
		Conf::add_site_option( Base::_VER, Core::VER );

		Debug2::debug( '[Data] Updated site_version to ' . Core::VER );

		$this->_set_upgrade_lock( false );

		! defined( 'LSWCP_EMPTYCACHE') && define( 'LSWCP_EMPTYCACHE', true );// clear all sites caches
		Purge::purge_all();
	}

	/**
	 * Check if upgrade script is running or not
	 *
	 * @since 3.0.1
	 */
	private function _get_upgrade_lock() {
		$is_upgrading = get_option( 'litespeed.data.upgrading' );
		if ( $is_upgrading && time() - $is_upgrading < 3600 ) {
			return $is_upgrading;
		}

		return false;
	}

	/**
	 * Show the upgrading banner if upgrade script is running
	 *
	 * @since 3.0.1
	 */
	public function check_upgrading_msg() {
		$is_upgrading = $this->_get_upgrade_lock();
		if ( ! $is_upgrading ) {
			return;
		}

		Admin_Display::info( sprintf( __( 'The database has been upgrading in the background since %s. This message will disappear once upgrade is complete.' ), '<code>' . Utility::readable_time( $is_upgrading ) . '</code>' ) . ' [LiteSpeed]', true );
	}

	/**
	 * Set lock for upgrade process
	 *
	 * @since 3.0.1
	 */
	private function _set_upgrade_lock( $lock ) {
		if ( ! $lock ) {
			delete_option( 'litespeed.data.upgrading' );
		}
		else {
			update_option( 'litespeed.data.upgrading', time() );
		}
	}

	/**
	 * Upgrade the conf to v3.0 from previous v3.0- data
	 *
	 * NOTE: Only for v3.0-
	 *
	 * @since 3.0
	 * @access public
	 */
	public function try_upgrade_conf_3_0() {
		$previous_options = get_option( 'litespeed-cache-conf' );
		if ( ! $previous_options ) {
			Cloud::version_check( 'new' );
			return;
		}

		$ver = $previous_options[ 'version' ];

		! defined( 'LSCWP_CUR_V' ) && define( 'LSCWP_CUR_V', $ver );

		// Init log manually
		if ( Conf::val( Base::O_DEBUG ) ) {
			Debug2::init();
		}
		Debug2::debug( '[Data] Upgrading previous settings [from] ' . $ver . ' [to] v3.0' );

		if ( $this->_get_upgrade_lock() ) {
			return;
		}

		$this->_set_upgrade_lock( true );

		require_once LSCWP_DIR . 'src/data.upgrade.func.php';

		// Here inside will update the version to v3.0
		litespeed_update_3_0( $ver );

		$this->_set_upgrade_lock( false );

		Debug2::debug( '[Data] Upgraded to v3.0' );

		// Upgrade from 3.0 to latest version
		$ver = '3.0';
		if ( Core::VER != $ver ) {
			$this->conf_upgrade( $ver );
		}
		else {
			// Reload options
			Conf::get_instance()->load_options();

			$this->correct_tb_existance();

			! defined( 'LSWCP_EMPTYCACHE') && define( 'LSWCP_EMPTYCACHE', true );// clear all sites caches
			Purge::purge_all();

			Cloud::version_check( 'upgrade' );
		}
	}

	/**
	 * Get the table name
	 *
	 * @since  3.0
	 * @access public
	 */
	public function tb( $tb ) {
		global $wpdb;

		switch ( $tb ) {
			case 'img_optm':
				return $wpdb->prefix . self::TB_IMG_OPTM;
				break;

			case 'img_optming':
				return $wpdb->prefix . self::TB_IMG_OPTMING;
				break;

			case 'cssjs':
				return $wpdb->prefix . self::TB_CSSJS;
				break;

			case 'avatar':
				return $wpdb->prefix . self::TB_AVATAR;
				break;

			case 'crawler':
				return $wpdb->prefix . self::TB_CRAWLER;
				break;

			case 'crawler_blacklist':
				return $wpdb->prefix . self::TB_CRAWLER_BLACKLIST;
				break;

			default:
				break;
		}

	}

	/**
	 * Check if one table exists or not
	 *
	 * @since  3.0
	 * @access public
	 */
	public function tb_exist( $tb ) {
		global $wpdb;
		return $wpdb->get_var( "SHOW TABLES LIKE '" . $this->tb( $tb ) . "'" );
	}

	/**
	 * Get data structure of one table
	 *
	 * @since  2.0
	 * @access private
	 */
	private function _tb_structure( $tb ) {
		return File::read( LSCWP_DIR . 'src/data_structure/' . $tb . '.sql' );
	}

	/**
	 * Create img optm table and sync data from wp_postmeta
	 *
	 * @since  3.0
	 * @access public
	 */
	public function tb_create( $tb ) {
		global $wpdb;

		Debug2::debug2( '[Data] Checking table ' . $tb );

		// Check if table exists first
		if ( $this->tb_exist( $tb ) ) {
			Debug2::debug2( '[Data] Existed' );
			return;
		}

		Debug2::debug( '[Data] Creating ' . $tb );

		$sql = sprintf(
			'CREATE TABLE IF NOT EXISTS `%1$s` (' . $this->_tb_structure( $tb ) . ') %2$s;',
			$this->tb( $tb ),
			$wpdb->get_charset_collate() // 'DEFAULT CHARSET=utf8'
		);

		$res = $wpdb->query( $sql );
		if ( $res !== true ) {
			Debug2::debug( '[Data] Warning! Creating table failed!', $sql );
			Admin_Display::error( Error::msg( 'failed_tb_creation', array( '<code>' . $tb . '</code>', '<code>' . $sql . '</code>' ) ) );
		}
	}

	/**
	 * Drop table
	 *
	 * @since  3.0
	 * @access public
	 */
	public function tb_del( $tb ) {
		global $wpdb;

		if ( ! $this->tb_exist( $tb ) ) {
			return;
		}

		Debug2::debug( '[Data] Deleting table ' . $tb );

		$q = 'DROP TABLE IF EXISTS ' . $this->tb( $tb );
		$wpdb->query( $q );
	}

	/**
	 * Drop generated tables
	 *
	 * @since  3.0
	 * @access public
	 */
	public function tables_del() {
		global $wpdb;

		$this->tb_del( 'cssjs' );
		$this->tb_del( 'avatar' );
		$this->tb_del( 'crawler' );
		$this->tb_del( 'crawler_blacklist' );

		// Deleting img_optm only can be done when destroy all optm images
	}

	/**
	 * save optimizer src to db
	 *
	 * @since  1.3.1
	 * @access public
	 */
	public function optm_save_src( $filename, $src, $request_url ) {
		global $wpdb;

		$src = json_encode( $src );
		$f = array(
			'hash_name'	=> $filename,
			'src'		=> $src,
			'dateline'	=> time(),
			'refer' 	=> $request_url,
		);

		$res = $wpdb->replace( $this->tb( 'cssjs' ), $f );

		return $res;
	}

	/**
	 * Get src set from hash in optimizer
	 *
	 * @since  1.3.1
	 * @access public
	 */
	public function optm_hash2src( $filename ) {
		global $wpdb;

		$res = $wpdb->get_row( $wpdb->prepare( 'SELECT src, refer FROM `' . $this->tb( 'cssjs' ) . '` WHERE `hash_name`=%s', $filename ), ARRAY_A );

		if ( empty( $res[ 'src' ] ) ) {
			return false;
		}

		Debug2::debug2( '[Data] Loaded hash2src ' . $res[ 'src' ] );

		$res[ 'src' ] = json_decode( $res[ 'src' ], true );

		return $res;
	}

	/**
	 * Get list from `data/js_excludes.txt`
	 *
	 * @since  3.5
	 */
	public function load_js_exc( $list ) {
		$data = $this->_load_per_line( 'js_excludes.txt' );
		if ( $data ) {
			$list = array_unique( array_filter( array_merge( $list, $data ) ) );
		}

		return $list;
	}

	/**
	 * Get list from `data/esi.nonces.txt`
	 *
	 * @since  3.5
	 */
	public function load_esi_nonces( $list ) {
		$data = $this->_load_per_line( 'esi.nonces.txt' );
		if ( $data ) {
			$list = array_unique( array_filter( array_merge( $list, $data ) ) );
		}

		return $list;
	}

	/**
	 * Load file per line
	 *
	 * Support two kinds of comments:
	 * 		1. `# this is comment`
	 * 		2. `##this is comment`
	 *
	 * @since  3.5
	 */
	private function _load_per_line( $file ) {
		$data = File::read( LSCWP_DIR . 'data/' . $file );
		$data = explode( PHP_EOL, $data );
		$list = array();
		foreach ( $data as $v ) {
			// Drop two kinds of comments
			if ( strpos( $v, '##' ) !== false ) {
				$v = trim( substr( $v, 0, strpos( $v, '##' ) ) );
			}
			if ( strpos( $v, '# ' ) !== false ) {
				$v = trim( substr( $v, 0, strpos( $v, '# ' ) ) );
			}

			if ( ! $v ) {
				continue;
			}

			$list[] = $v;
		}

		return $list;
	}

}