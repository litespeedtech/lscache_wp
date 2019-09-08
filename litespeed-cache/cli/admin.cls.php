<?php
namespace LiteSpeed\CLI ;

defined( 'WPINC' ) || exit ;

use LiteSpeed\Config ;
use LiteSpeed\Conf ;
use LiteSpeed\Admin_Settings ;
use LiteSpeed\Import ;
use WP_CLI ;

/**
 * LiteSpeed Cache Admin Interface
 */
class Admin
{
	private $__cfg ;

	public function __construct()
	{
		$this->__cfg = Config::get_instance() ;
	}

	/**
	 * Set an individual LiteSpeed Cache option.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : The option key to update.
	 *
	 * <newvalue>
	 * : The new value to set the option to.
	 *
	 * ## EXAMPLES
	 *
	 *     # Set to not cache the login page
	 *     $ wp lscache-admin set_option cache-priv false
	 *
	 */
	public function set_option( $args, $assoc_args )
	{
		/**
		 * Note: If the value is multiple dimensions like cdn-mapping, need to specially handle it both here and in `const.default.ini`
		 */
		$key = $args[ 0 ] ;
		$val = $args[ 1 ] ;

		/**
		 * For CDN mapping, allow:
		 * 		`set_option cdn-mapping[url][0] https://the1st_cdn_url`
		 * 		`set_option cdn-mapping[inc_img][0] true`
		 * @since  2.7.1
		 */

		// Build raw data
		$raw_data = array(
			Admin_Settings::ENROLL	=> array( $key ),
			$key 	=> $val,
		) ;

		Admin_Settings::get_instance()->save( $raw_data ) ;

	}

	/**
	 * Get the plugin options.
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     # Get all options
	 *     $ wp lscache-admin get_options
	 *
	 */
	public function get_options($args, $assoc_args)
	{
		$options = Config::get_instance()->get_options() ;
		$option_out = array() ;

		$buf = WP_CLI::colorize("%CThe list of options:%n\n") ;
		WP_CLI::line($buf) ;

		foreach($options as $key => $value) {
			if ( in_array($key, self::$checkboxes) ) {
				if ( $value ) {
					$value = 'true' ;
				}
				else {
					$value = 'false' ;
				}
			}
			elseif ( $value === '' ) {
				$value = "''" ;
			}
			$option_out[] = array('key' => $key, 'value' => $value) ;
		}

		WP_CLI\Utils\format_items('table', $option_out, array('key', 'value')) ;
	}

	/**
	 * Export plugin options to a file.
	 *
	 * ## OPTIONS
	 *
	 * [--filename=<path>]
	 * : The default path used is CURRENTDIR/lscache_wp_options_DATE-TIME.txt.
	 * To select a different file, use this option.
	 *
	 * ## EXAMPLES
	 *
	 *     # Export options to a file.
	 *     $ wp lscache-admin export_options
	 *
	 */
	public function export_options($args, $assoc_args)
	{
		if ( isset($assoc_args['filename']) ) {
			$file = $assoc_args['filename'] ;
		}
		else {
			$file = getcwd() . '/lscache_wp_options_' . date('d_m_Y-His') . '.data' ;
		}

		if ( ! is_writable(dirname($file)) ) {
			WP_CLI::error('Directory not writable.') ;
			return ;
		}

		$data = Import::get_instance()->export() ;

		if ( file_put_contents( $file, $data ) === false ) {
			WP_CLI::error( 'Failed to create file.' ) ;
		}
		else {
			WP_CLI::success('Created file ' . $file) ;
		}
	}

	/**
	 * Import plugin options from a file.
	 *
	 * The file must be formatted as such:
	 * option_key=option_value
	 * One per line.
	 * A Semicolon at the beginning of the line indicates a comment and will be skipped.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : The file to import options from.
	 *
	 * ## EXAMPLES
	 *
	 *     # Import options from CURRENTDIR/options.txt
	 *     $ wp lscache-admin import_options options.txt
	 *
	 */
	public function import_options($args, $assoc_args)
	{
		$file = $args[0] ;
		if ( ! file_exists($file) || ! is_readable($file) ) {
			WP_CLI::error('File does not exist or is not readable.') ;
		}

		$res = Import::get_instance()->import( $file ) ;

		if ( ! $res ) {
			WP_CLI::error( 'Failed to parse serialized data from file.' ) ;
		}

		WP_CLI::success( 'Options imported. [File] ' . $file ) ;
	}

	/**
	 * Reset all options to default.
	 *
	 * ## EXAMPLES
	 *
	 *     # Reset all options
	 *     $ wp lscache-admin reset_options
	 *
	 */
	public function reset_options()
	{
		$res = Import::get_instance()->reset( $file ) ;

		if ( ! $res ) {
			WP_CLI::error( 'Failed to reset options.' ) ;
		}

		WP_CLI::success( 'Options reset.' ) ;
	}

	/**
	 * Update options
	 *
	 * @access private
	 * @since 1.1.0
	 * @param array $options The options array to store
	 */
	private function _update_options($options)
	{
		$output = Admin_Settings::get_instance()->validate_plugin_settings($options) ;

		global $wp_settings_errors ;

		if ( ! empty($wp_settings_errors) ) {
			foreach ($wp_settings_errors as $err) {
				WP_CLI::error($err['message']) ;
			}
			return ;
		}

		$ret = Config::get_instance()->update_options($output) ;

		WP_CLI::success('Options/Terms updated. Please purge the cache. New options: ' . var_export($options, true)) ;
		// if ( $ret ) {
		// }
		// else {
		// 	WP_CLI::error('No options updated.') ;
		// }
	}
}

