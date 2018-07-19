<?php
/**
 * The admin optimize tool
 *
 *
 * @since      1.2.1
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/admin
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */

if ( ! defined( 'WPINC' ) ) {
	die ;
}

class LiteSpeed_Cache_Admin_Optimize
{
	private static $_types = array( 'revision', 'auto_draft', 'trash_post', 'spam_comment', 'trash_comment', 'trackback-pingback', 'expired_transient', 'all_transients' ) ;

	/**
	 * Generate operation URL
	 *
	 * @since  1.2.1
	 * @access public
	 * @param  string $type The type to proceed
	 * @return  string The final URL
	 */
	public static function generate_url( $type )
	{
		$url = LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_DB_OPTIMIZE, $type ) ;
		return $url ;
	}

	/**
	 * Run DB Cleaner
	 *
	 * @since  1.2.1
	 * @access public
	 */
	public static function run_db_clean()
	{
		if( ! $type = LiteSpeed_Cache_Router::verify_type() ) {
			return ;
		}

		$res = '' ;

		if ( is_multisite() && is_network_admin() ) {
			$blogs = LiteSpeed_Cache_Activation::get_network_ids() ;
			foreach ( $blogs as $blog_id ) {
				switch_to_blog( $blog_id ) ;
				$res = self::db_clean( $type ) ;
				restore_current_blog() ;
			}
		}
		else {
			$res = self::db_clean( $type ) ;
		}

		return $res ;

	}

	/**
	 * Clean/Optimize WP tables
	 *
	 * @since  1.2.1
	 * @access public
	 * @param  string $type The type to clean
	 * @param  bool $ignore_multisite If ignore multisite check
	 * @return  int The rows that will be affected
	 */
	public static function db_count( $type, $ignore_multisite = false )
	{
		if ( $type === 'all' ) {
			$num = 0 ;
			foreach ( self::$_types as $val ) {
				$num += self::db_count( $val ) ;
			}
			return $num ;
		}

		if ( ! $ignore_multisite ) {
			if ( is_multisite() && is_network_admin() ) {
				$num = 0 ;
				$blogs = LiteSpeed_Cache_Activation::get_network_ids() ;
				foreach ( $blogs as $blog_id ) {
					switch_to_blog( $blog_id ) ;
					$num += self::db_count( $type, true ) ;
					restore_current_blog() ;
				}
				return $num ;
			}
		}

		global $wpdb ;

		switch ( $type ) {
			case 'revision':
				return $wpdb->get_var( "SELECT COUNT(*) FROM `$wpdb->posts` WHERE post_type = 'revision'" ) ;

			case 'auto_draft':
				return $wpdb->get_var( "SELECT COUNT(*) FROM `$wpdb->posts` WHERE post_status = 'auto-draft'" ) ;

			case 'trash_post':
				return $wpdb->get_var( "SELECT COUNT(*) FROM `$wpdb->posts` WHERE post_status = 'trash'" ) ;

			case 'spam_comment':
				return $wpdb->get_var( "SELECT COUNT(*) FROM `$wpdb->comments` WHERE comment_approved = 'spam'" ) ;

			case 'trash_comment':
				return $wpdb->get_var( "SELECT COUNT(*) FROM `$wpdb->comments` WHERE comment_approved = 'trash'" ) ;

			case 'trackback-pingback':
				return $wpdb->get_var( "SELECT COUNT(*) FROM `$wpdb->comments` WHERE comment_type = 'trackback' OR comment_type = 'pingback'" ) ;

			case 'expired_transient':
				return $wpdb->get_var( "SELECT COUNT(*) FROM `$wpdb->options` WHERE option_name LIKE '_transient_timeout%' AND option_value < " . time() ) ;

			case 'all_transients':
				return $wpdb->get_var( "SELECT COUNT(*) FROM `$wpdb->options` WHERE option_name LIKE '%_transient_%'" ) ;

			case 'optimize_tables':
				return $wpdb->get_var( "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "' and Engine <> 'InnoDB' and data_free > 0" ) ;

			case 'all_cssjs' :
				return $wpdb->get_var( "SELECT COUNT(*) FROM `" . LiteSpeed_Cache_Data::get_optm_table() . "`" ) ;
		}

		return '-' ;
	}

	/**
	 * Clean/Optimize WP tables
	 *
	 * @since  1.2.1
	 * @access public
	 * @param  string $type The type to clean
	 */
	public static function db_clean( $type )
	{
		if ( $type === 'all' ) {
			foreach ( self::$_types as $val ) {
				self::db_clean( $val ) ;
			}
			return __( 'Clean all successfully.', 'litespeed-cache' ) ;
		}

		global $wpdb ;
		switch ( $type ) {
			case 'revision':
				$wpdb->query( "DELETE FROM `$wpdb->posts` WHERE post_type = 'revision'" ) ;
				return __( 'Clean post revisions successfully.', 'litespeed-cache' ) ;

			case 'auto_draft':
				$wpdb->query( "DELETE FROM `$wpdb->posts` WHERE post_status = 'auto-draft'" ) ;
				return __( 'Clean auto drafts successfully.', 'litespeed-cache' ) ;

			case 'trash_post':
				$wpdb->query( "DELETE FROM `$wpdb->posts` WHERE post_status = 'trash'" ) ;
				return __( 'Clean trashed posts and pages successfully.', 'litespeed-cache' ) ;

			case 'spam_comment':
				$wpdb->query( "DELETE FROM `$wpdb->comments` WHERE comment_approved = 'spam'" ) ;
				return __( 'Clean spam comments successfully.', 'litespeed-cache' ) ;

			case 'trash_comment':
				$wpdb->query( "DELETE FROM `$wpdb->comments` WHERE comment_approved = 'trash'" ) ;
				return __( 'Clean trashed comments successfully.', 'litespeed-cache' ) ;

			case 'trackback-pingback':
				$wpdb->query( "DELETE FROM `$wpdb->comments` WHERE comment_type = 'trackback' OR comment_type = 'pingback'" ) ;
				return __( 'Clean trackbacks and pingbacks successfully.', 'litespeed-cache' ) ;

			case 'expired_transient':
				$wpdb->query( "DELETE FROM `$wpdb->options` WHERE option_name LIKE '_transient_timeout%' AND option_value < " . time() ) ;
				return __( 'Clean expired transients successfully.', 'litespeed-cache' ) ;

			case 'all_transients':
				$wpdb->query( "DELETE FROM `$wpdb->options` WHERE option_name LIKE '%_transient_%'" ) ;
				return __( 'Clean all transients successfully.', 'litespeed-cache' ) ;

			case 'optimize_tables':
				$sql = "SELECT table_name, data_free FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "' and Engine <> 'InnoDB' and data_free > 0" ;
				$result = $wpdb->get_results( $sql ) ;
				if ( $result ) {
					foreach ( $result as $row ) {
						$wpdb->query( 'OPTIMIZE TABLE ' . $row->table_name ) ;
					}
				}
				return __( 'Optimized all tables.', 'litespeed-cache' ) ;

			case 'all_cssjs' :
				LiteSpeed_Cache_Purge::purge_all() ;
				$wpdb->query( "TRUNCATE `" . LiteSpeed_Cache_Data::get_optm_table() . "`" ) ;
				return __( 'Clean all CSS/JS optimizer data successfully.', 'litespeed-cache' ) ;

		}

	}
}