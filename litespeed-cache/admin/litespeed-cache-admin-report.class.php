<?php
/**
 * The report class
 *
 *
 * @since      1.1.0
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/admin
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */

if ( ! defined( 'WPINC' ) ) {
	die ;
}

class LiteSpeed_Cache_Admin_Report
{
	private static $_instance ;

	const TYPE_SEND_REPORT = 'send_report' ;

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  1.6.5
	 * @access public
	 */
	public static function handler()
	{
		$instance = self::get_instance() ;

		$type = LiteSpeed_Cache_Router::verify_type() ;

		switch ( $type ) {

			case self::TYPE_SEND_REPORT :
				$instance->_post_env() ;
				break ;

			default:
				break ;
		}

		LiteSpeed_Cache_Admin::redirect() ;
	}

	/**
	 * post env report number to ls center server
	 *
	 * @since  1.6.5
	 * @access private
	 */
	private function _post_env()
	{
		$report_con = $this->generate_environment_report() ;

		// Generate link
		$link = ! empty( $_POST[ 'link' ] ) ? $_POST[ 'link' ] : '';

		$data = array(
			'env' => $report_con,
			'link' => $link,
		) ;

		$json = LiteSpeed_Cache_Admin_API::post( LiteSpeed_Cache_Admin_API::IAPI_ACTION_ENV_REPORT, LiteSpeed_Cache_Utility::arr2str( $data ), false, true ) ;

		if ( ! is_array( $json ) ) {
			LiteSpeed_Cache_Log::debug( 'Env: Failed to post to LiteSpeed server ', $json ) ;
			$msg = __( 'Failed to push to LiteSpeed server', 'litespeed-cache' ) . ': ' . $json ;
			LiteSpeed_Cache_Admin_Display::error( $msg ) ;
			return ;
		}

		$data = array(
			'num'	=> ! empty( $json[ 'num' ] ) ? $json[ 'num' ] : '--',
			'dateline'	=> time(),
		) ;

		update_option( LiteSpeed_Cache_Config::ITEM_ENV_REF, $data ) ;

	}

	/**
	 * Get env report number from db
	 *
	 * @since  1.6.4
	 * @access public
	 * @return array
	 */
	public function get_env_ref()
	{
		$info = get_option( LiteSpeed_Cache_Config::ITEM_ENV_REF ) ;

		if ( ! is_array( $info ) ) {
			return array(
				'num'	=> '-',
				'dateline'	=> '-',
			) ;
		}

		$info[ 'dateline' ] = date( 'm/d/Y H:i:s', $info[ 'dateline' ] ) ;

		return $info ;
	}

	/**
	 * Gathers the environment details and creates the report.
	 * Will write to the environment report file.
	 *
	 * @since 1.0.12
	 * @access public
	 * @param mixed $options Array of options to output. If null, will skip
	 * the options section.
	 * @return string The built report.
	 */
	public function generate_environment_report($options = null)
	{
		global $wp_version, $_SERVER ;
		$frontend_htaccess = LiteSpeed_Cache_Admin_Rules::get_frontend_htaccess() ;
		$backend_htaccess = LiteSpeed_Cache_Admin_Rules::get_backend_htaccess() ;
		$paths = array($frontend_htaccess) ;
		if ( $frontend_htaccess != $backend_htaccess ) {
			$paths[] = $backend_htaccess ;
		}

		if ( is_multisite() ) {
			$active_plugins = get_site_option('active_sitewide_plugins') ;
			if ( ! empty($active_plugins) ) {
				$active_plugins = array_keys($active_plugins) ;
			}
		}
		else {
			$active_plugins = get_option('active_plugins') ;
		}

		if ( function_exists('wp_get_theme') ) {
			$theme_obj = wp_get_theme() ;
			$active_theme = $theme_obj->get('Name') ;
		}
		else {
			$active_theme = get_current_theme() ;
		}

		$extras = array(
			'wordpress version' => $wp_version,
			'siteurl' => get_option( 'siteurl' ),
			'home' => get_option( 'home' ),
			'home_url' => home_url(),
			'locale' => get_locale(),
			'active theme' => $active_theme,
		) ;

		$extras[ 'active plugins' ] = $active_plugins ;

		if ( is_null($options) ) {
			$options = LiteSpeed_Cache_Config::get_instance()->get_options() ;
		}

		if ( ! is_null($options) && is_multisite() ) {
			$blogs = LiteSpeed_Cache_Activation::get_network_ids() ;
			if ( ! empty($blogs) ) {
				foreach ( $blogs as $blog_id ) {
					$opts = get_blog_option($blog_id, LiteSpeed_Cache_Config::OPTION_NAME, array()) ;
					if ( isset($opts[LiteSpeed_Cache_Config::OPID_ENABLED_RADIO]) ) {
						$options['blog ' . $blog_id . ' radio select'] = $opts[LiteSpeed_Cache_Config::OPID_ENABLED_RADIO] ;
					}
				}
			}
		}

		// Security: Remove cf key in report
		$secure_fields = array(
			LiteSpeed_Cache_Config::OPT_CDN_QUIC_KEY,
			LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE_KEY,
			LiteSpeed_Cache_Config::OPID_CACHE_OBJECT_PSWD,
		) ;
		foreach ( $secure_fields as $v ) {
			if ( ! empty( $options[ $v ] ) ) {
				$options[ $v ] = str_repeat( '*', strlen( $options[ $v ] ) ) ;
			}
		}

		$item_options = LiteSpeed_Cache_Config::get_instance()->stored_items() ;
		foreach ( $item_options as $v ) {
			// bypass main conf
			if ( $v == LiteSpeed_Cache_Config::OPTION_NAME || $v == LiteSpeed_Cache_Config::ITEM_ENV_REF ) {
				continue ;
			}
			$options[ $v ] = get_option( $v ) ;
		}


		$report = $this->build_environment_report($_SERVER, $options, $extras, $paths) ;
		return $report ;
	}

	/**
	 * Builds the environment report buffer with the given parameters
	 *
	 * @access private
	 * @param array $server - server variables
	 * @param array $options - cms options
	 * @param array $extras - cms specific attributes
	 * @param array $htaccess_paths - htaccess paths to check.
	 * @return string The Environment Report buffer.
	 */
	private function build_environment_report($server, $options, $extras = array(), $htaccess_paths = array())
	{
		$server_keys = array(
			'DOCUMENT_ROOT'=>'',
			'SERVER_SOFTWARE'=>'',
			'X-LSCACHE'=>'',
			'HTTP_X_LSCACHE'=>''
		) ;
		$server_vars = array_intersect_key($server, $server_keys) ;
		$server_vars[] = "LSWCP_TAG_PREFIX = " . LSWCP_TAG_PREFIX ;

		$server_vars = array_merge( $server_vars, LiteSpeed_Cache_Config::get_instance()->server_vars() ) ;

		$buf = $this->format_report_section('Server Variables', $server_vars) ;

		$buf .= $this->format_report_section('Wordpress Specific Extras', $extras) ;

		$buf .= $this->format_report_section('LSCache Plugin Options', $options) ;

		if ( empty($htaccess_paths) ) {
			return $buf ;
		}

		foreach ( $htaccess_paths as $path ) {
			if ( ! file_exists($path) || ! is_readable($path) ) {
				$buf .= $path . " does not exist or is not readable.\n" ;
				continue ;
			}

			$content = file_get_contents($path) ;
			if ( $content === false ) {
				$buf .= $path . " returned false for file_get_contents.\n" ;
				continue ;
			}
			$buf .= $path . " contents:\n" . $content . "\n\n" ;
		}
		return $buf ;
	}

	/**
	 * Creates a part of the environment report based on a section header
	 * and an array for the section parameters.
	 *
	 * @since 1.0.12
	 * @access private
	 * @param string $section_header The section heading
	 * @param array $section An array of information to output
	 * @return string The created report block.
	 */
	private function format_report_section( $section_header, $section )
	{
		$tab = '    ' ; // four spaces

		if ( empty( $section ) ) {
			return 'No matching ' . $section_header . "\n\n" ;
		}
		$buf = $section_header ;

		foreach ( $section as $k => $v ) {
			$buf .= "\n" . $tab ;

			if ( ! is_numeric( $k ) ) {
				$buf .= $k . ' = ' ;
			}

			if ( ! is_string( $v ) ) {
				$v = var_export( $v, true ) ;
			}

			$buf .= $v ;
		}
		return $buf . "\n\n" ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.1.0
	 * @access public
	 * @return Current class instance.
	 */
	public static function get_instance()
	{
		if ( ! isset(self::$_instance) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}
}
