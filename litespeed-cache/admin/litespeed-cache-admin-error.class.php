<?php



/**
 * The admin errors
 *
 *
 * @since      1.0.15
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/admin
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Admin_Error
{
	private static $_instance ;

	const NOTICE_BLUE = 'notice notice-info' ;
	const NOTICE_GREEN = 'notice notice-success' ;
	const NOTICE_RED = 'notice notice-error' ;
	const NOTICE_YELLOW = 'notice notice-warning' ;
	const E_PHP_VER = 1000 ;
	const E_WP_VER = 1010 ;

	const E_PURGE_FORM = 2000 ;
	const E_PURGEBY_EMPTY = 2010 ;
	const E_PURGEBY_BAD = 2020 ;
	const E_PURGEBY_CAT_INV = 2030 ;
	const E_PURGEBY_TAG_INV = 2040 ;
	const E_PURGEBY_URL_BAD = 2050 ;

	const E_PURGEBY_PID_NUM = 2500 ;
	const E_PURGEBY_PID_DNE = 2510 ;
	const E_PURGEBY_URL_INV = 2520 ;
	const E_PURGEBY_CAT_DNE = 2530 ;
	const E_PURGEBY_TAG_DNE = 2540 ;

	const E_SETTING_ADMIN_IP_INV = 3000 ;
	const E_SETTING_TEST_IP_INV = 3010 ;
	const E_SETTING_SITE_IP = 3020 ;
	const E_SETTING_CUSTOM_SITEMAP_READ = 3030 ;
	const E_SETTING_CUSTOM_SITEMAP_PARSE = 3031 ;

	const E_SETTING_CAT = 3510 ;
	const E_SETTING_TAG = 3520 ;
	const E_SETTING_LC = 3530 ; // login cookie setting
	const E_SETTING_REWRITE = 3540 ;

	const E_LC_HTA = 4000 ; // login cookie .htaccess not correct

	const E_HTA_DNF = 4500 ; // .htaccess did not find.

	const E_LC_MISMATCH = 5000 ; // login cookie mismatch

	const E_CONF = 9000 ; // general config failed to write.
	const E_HTA_BU = 9010 ; // backup
	const E_HTA_PUT = 9020 ; // failed to put
	const E_HTA_GET = 9030 ; // failed to get
	const E_HTA_RW = 9040 ; // read write
	const E_HTA_R = 9041 ; // read
	const E_HTA_W = 9042 ; // write
	const E_HTA_ORDER = 9050 ; // prefix found after suffix
	const E_HTA_SAVE = 9060 ;
	const E_CONF_WRITE = 9070 ;
	const E_CONF_FIND = 9080 ;

	/**
	 * Get the error message by code.
	 *
	 * @since 1.0.15
	 * @access public
	 * @param int $err_code The error code to retrieve.
	 * @return string The error message if matching, else an empty string.
	 */
	public function convert_code_to_error($err_code)
	{
		if ( ! is_numeric($err_code) ) {
			return false ;
		}

		switch ($err_code){
			case self::E_PHP_VER:
				return '<strong>'
					. __('The installed PHP version is too old for the LiteSpeed Cache Plugin.', 'litespeed-cache')
					. '</strong><br /> '
					. sprintf(__('The LiteSpeed Cache Plugin requires at least PHP %s.', 'litespeed-cache'), '5.3')
					. ' '
					. sprintf(__('The currently installed version is PHP %s, which is out-dated and insecure.', 'litespeed-cache'), PHP_VERSION)
					. ' '
					. sprintf(wp_kses(__('Please upgrade or go to <a href="%s">active plugins</a> and deactivate the LiteSpeed Cache plugin to hide this message.', 'litespeed-cache'),
						array('a' => array('href' => array()))), 'plugins.php?plugin_status=active') ;

			case self::E_WP_VER:
				return '<strong>'
					. __('The installed WordPress version is too old for the LiteSpeed Cache Plugin.', 'litespeed-cache')
					. '</strong><br />'
					. sprintf(__('The LiteSpeed Cache Plugin requires at least WordPress %s.', 'litespeed-cache'), '3.3')
					. ' '
					. sprintf(wp_kses(__('Please upgrade or go to <a href="%s">active plugins</a> and deactivate the LiteSpeed Cache plugin to hide this message.', 'litespeed-cache'),
						array('a' => array('href' => array()))), 'plugins.php?plugin_status=active') ;

			// Admin action errors.
			case self::E_PURGE_FORM:
				return __('Something went wrong with the form! Please try again.', 'litespeed-cache') ;

			case self::E_PURGEBY_EMPTY:
				return __('Tried to purge list with empty list.', 'litespeed-cache') ;

			case self::E_PURGEBY_BAD:
				return __('Bad Purge By selected value.', 'litespeed-cache') ;

			case self::E_PURGEBY_CAT_INV:
				return __('Failed to purge by category, invalid category slug.', 'litespeed-cache') ;

			case self::E_PURGEBY_TAG_INV:
				return __('Failed to purge by tag, invalid tag slug.', 'litespeed-cache') ;

			case self::E_PURGEBY_URL_BAD:
				return __('Failed to purge by url, contained "<".', 'litespeed-cache') ;

			// Admin actions with expected parameters for message.
			case self::E_PURGEBY_PID_NUM:
				return __('Failed to purge by Post ID, given ID is not numeric: %s', 'litespeed-cache') ;

			case self::E_PURGEBY_PID_DNE:
				return __('Failed to purge by Post ID, given ID does not exist or is not published: %s',
					'litespeed-cache') ;

			case self::E_PURGEBY_URL_INV:
				return __('Failed to purge by url, invalid input: %s.', 'litespeed-cache') ;

			case self::E_PURGEBY_CAT_DNE:
				return __('Failed to purge by category, does not exist: %s', 'litespeed-cache') ;

			case self::E_PURGEBY_TAG_DNE:
				return __('Failed to purge by tag, does not exist: %s', 'litespeed-cache') ;

			// Admin settings errors.
			case self::E_SETTING_ADMIN_IP_INV:
				return __('Invalid data in Admin IPs.', 'litespeed-cache') ;

			case self::E_SETTING_TEST_IP_INV:
				return __('Invalid data in Test IPs.', 'litespeed-cache') ;

			case self::E_SETTING_SITE_IP:
				return __('Invalid Site IP: %s', 'litespeed-cache') ;

			case self::E_SETTING_CUSTOM_SITEMAP_READ:
				return __('Can not fetch Custom Sitemap: %s', 'litespeed-cache') ;

			case self::E_SETTING_CUSTOM_SITEMAP_PARSE:
				return __('Can not parse custom sitemap xml file: %s.', 'litespeed-cache') . ' '
					. sprintf(__('Please make sure the file is xml format and the %s extension is installed on the server.', 'litespeed-cache'), 'php-xml') ;

			case self::E_SETTING_CAT:
				// %s is the category attempted to be added.
				return __('Removed category "%s" from list, ID does not exist.',
					'litespeed-cache') ;

			case self::E_SETTING_TAG:
				// %s is the tag attempted to be added.
				return __('Removed tag "%s" from list, ID does not exist.',
					'litespeed-cache') ;

			case self::E_SETTING_LC:
				return __('Invalid login cookie. Invalid characters found: %s',
					'litespeed-cache') ;

			case self::E_SETTING_REWRITE:
				return __('Invalid Rewrite List.', 'litespeed-cache') . ' '
					. __('Empty or invalid rule.', 'litespeed-cache') . ' '
					. __('Rule: %1$s, list: %2$s', 'litespeed-cache') ;

			// Login cookie in settings did not match .htaccess.
			case self::E_LC_HTA:
				return __('Tried to parse for existing login cookie.', 'litespeed-cache') . ' '
					. sprintf(__('%s file not valid. Please verify contents.', 'litespeed-cache'), '.htaccess') ;

			// Could not find something in the .htaccess file. Expect parameter.
			case self::E_HTA_DNF:
				return __('Could not find %s.', 'litespeed-cache') ;

			// Mismatched login cookie.
			case self::E_LC_MISMATCH:
				return __('This site is a subdirectory install.', 'litespeed-cache') . ' '
					. __('Login cookies do not match.', 'litespeed-cache') . ' '
					. __('Please remove both and set the login cookie in LiteSpeed Cache advanced settings.', 'litespeed-cache') ;

			case self::E_CONF:
				return __('LiteSpeed Cache was unable to write to the wp-config.php file.', 'litespeed-cache') . ' '
					. sprintf(__('Please add the following to the wp-config.php file: %s', 'litespeed-cache'), '<br><pre>define(\'WP_CACHE\', true);</pre>') ;

			// .htaccess problem.
			case self::E_HTA_BU:
				return __('Failed to back up file, aborted changes.', 'litespeed-cache') ;

			case self::E_HTA_PUT:
				return sprintf(__('Failed to put contents into %s', 'litespeed-cache'), '.htaccess') ;

			case self::E_HTA_GET:
				return sprintf(__('Failed to get %s file contents.', 'litespeed-cache'), '.htaccess') ;

			case self::E_HTA_RW:
				return sprintf(__('%s file not readable or not writable.', 'litespeed-cache'), '.htaccess') ;

			case self::E_HTA_R:
				return sprintf(__('%s file not readable.', 'litespeed-cache'), '.htaccess') ;

			case self::E_HTA_W:
				return sprintf(__('%s file not writable.', 'litespeed-cache'), '.htaccess') ;

			case self::E_HTA_SAVE:
				return sprintf(__('Failed to overwrite %s.', 'litespeed-cache'), '.htaccess') ;

			// wp-config problem.
			case self::E_CONF_WRITE:
				$err = sprintf(__('The %1$s file not writable for %2$s', 'litespeed-cache'), 'wp-config', '\'WP_CACHE\'') ;
				break ;

			case self::E_CONF_FIND:
				$err = sprintf(__('%s file did not find a place to insert define.', 'litespeed-cache'), 'wp-config') ;
				break ;

			default:
				return false ;
		}

		return $err . '<br />' . __('LiteSpeed Cache was unable to write to the wp-config.php file.', 'litespeed-cache') . ' '
			. sprintf(__('Please add the following to the wp-config.php file: %s', 'litespeed-cache'), '<br><pre>define(\'WP_CACHE\', true);</pre>') ;
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
