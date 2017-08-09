<?php
/**
 * Auto registration for LiteSpeed classes
 *
 * @since      1.1.0
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/includes
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */

if ( ! defined('WPINC') ) {
	die ;
}

if ( !function_exists('_litespeed_autoload') ) {
	function _litespeed_autoload($cls)
	{
		$class2fileArr = array(
			'LiteSpeed'							=> 'lib/litespeed/litespeed.class.php',
			'Litespeed_Crawler'					=> 'lib/litespeed/litespeed-crawler.class.php',
			'Litespeed_File'					=> 'lib/litespeed/litespeed-file.class.php',

			'LiteSpeed_Cache'					=> 'includes/litespeed-cache.class.php',
			'LiteSpeed_Cache_Activation'		=> 'includes/litespeed-cache-activation.class.php',
			'LiteSpeed_Cache_API'				=> 'includes/litespeed-cache-api.class.php',
			'LiteSpeed_Cache_Config'			=> 'includes/litespeed-cache-config.class.php',
			'LiteSpeed_Cache_Control'			=> 'includes/litespeed-cache-control.class.php',
			'LiteSpeed_Cache_Crawler'			=> 'includes/litespeed-cache-crawler.class.php',
			'LiteSpeed_Cache_Crawler_Sitemap'	=> 'includes/litespeed-cache-crawler-sitemap.class.php',
			'LiteSpeed_Cache_ESI'				=> 'includes/litespeed-cache-esi.class.php',
			'LiteSpeed_Cache_Log'				=> 'includes/litespeed-cache-log.class.php',
			'LiteSpeed_Cache_Purge'				=> 'includes/litespeed-cache-purge.class.php',
			'LiteSpeed_Cache_Router'			=> 'includes/litespeed-cache-router.class.php',
			'LiteSpeed_Cache_Tag'				=> 'includes/litespeed-cache-tag.class.php',
			'LiteSpeed_Cache_Task'				=> 'includes/litespeed-cache-task.class.php',
			'LiteSpeed_Cache_Vary'				=> 'includes/litespeed-cache-vary.class.php',
			'LiteSpeed_Cache_Utility'			=> 'includes/litespeed-cache-utility.class.php',

			'LiteSpeed_Cache_Admin'				=> 'admin/litespeed-cache-admin.class.php',
			'LiteSpeed_Cache_Admin_Display'		=> 'admin/litespeed-cache-admin-display.class.php',
			'LiteSpeed_Cache_Admin_Error'		=> 'admin/litespeed-cache-admin-error.class.php',
			'LiteSpeed_Cache_Admin_Report'		=> 'admin/litespeed-cache-admin-report.class.php',
			'LiteSpeed_Cache_Admin_Rules'		=> 'admin/litespeed-cache-admin-rules.class.php',
			'LiteSpeed_Cache_Admin_Settings'	=> 'admin/litespeed-cache-admin-settings.class.php',

			'LiteSpeed_Cache_Cli_Admin'			=> 'cli/litespeed-cache-cli-admin.class.php',
			'LiteSpeed_Cache_Cli_Purge'			=> 'cli/litespeed-cache-cli-purge.class.php',

			'LiteSpeed_Cache_Tags'				=> 'includes/deprecated-litespeed-cache-tags.class.php',
		);
		if( array_key_exists($cls, $class2fileArr) && file_exists(LSWCP_DIR . $class2fileArr[$cls]) ) {
			require_once LSWCP_DIR . $class2fileArr[$cls];
		}
	}
}

spl_autoload_register('_litespeed_autoload');
