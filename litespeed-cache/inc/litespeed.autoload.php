<?php
/**
 * Auto registration for LiteSpeed classes
 *
 * @since      	1.1.0
 * @since  		1.5 Moved into /inc
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
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
			'Litespeed_String'					=> 'lib/litespeed/litespeed-string.class.php',

			'LiteSpeed_Cache'					=> 'inc/litespeed-cache.class.php',
			'LiteSpeed_Cache_Activation'		=> 'inc/activation.class.php',
			'LiteSpeed_Cache_API'				=> 'inc/api.class.php',
			'LiteSpeed_Cache_CDN'				=> 'inc/cdn.class.php',
			'LiteSpeed_Cache_CDN_Cloudflare'	=> 'inc/cdn/cloudflare.class.php',
			'LiteSpeed_Cache_CDN_Quiccloud'		=> 'inc/cdn/quiccloud.class.php',
			'LiteSpeed_Cache_Config'			=> 'inc/config.class.php',
			'LiteSpeed_Cache_Control'			=> 'inc/control.class.php',
			'LiteSpeed_Cache_Crawler'			=> 'inc/crawler.class.php',
			'LiteSpeed_Cache_Crawler_Sitemap'	=> 'inc/crawler-sitemap.class.php',
			'LiteSpeed_Cache_Data'				=> 'inc/data.class.php',
			'LiteSpeed_Cache_ESI'				=> 'inc/esi.class.php',
			'LiteSpeed_Cache_GUI'				=> 'inc/gui.class.php',
			'LiteSpeed_Cache_Import'			=> 'inc/import.class.php',
			'LiteSpeed_Cache_Img_Optm'			=> 'inc/img_optm.class.php',
			'LiteSpeed_Cache_Log'				=> 'inc/log.class.php',
			'LiteSpeed_Cache_Media'				=> 'inc/media.class.php',
			'LiteSpeed_Cache_Object'			=> 'inc/object.class.php',
			'LiteSpeed_Cache_Optimize'			=> 'inc/optimize.class.php',
			'LiteSpeed_Cache_Optimizer'			=> 'inc/optimizer.class.php',
			'LiteSpeed_Cache_Purge'				=> 'inc/purge.class.php',
			'LiteSpeed_Cache_Router'			=> 'inc/router.class.php',
			'LiteSpeed_Cache_Tag'				=> 'inc/tag.class.php',
			'LiteSpeed_Cache_Task'				=> 'inc/task.class.php',
			'LiteSpeed_Cache_Vary'				=> 'inc/vary.class.php',
			'LiteSpeed_Cache_Utility'			=> 'inc/utility.class.php',

			'LiteSpeed_Cache_Admin'				=> 'admin/litespeed-cache-admin.class.php',
			'LiteSpeed_Cache_Admin_API'			=> 'admin/admin-api.class.php',
			'LiteSpeed_Cache_Admin_Display'		=> 'admin/litespeed-cache-admin-display.class.php',
			'LiteSpeed_Cache_Admin_Error'		=> 'admin/litespeed-cache-admin-error.class.php',
			'LiteSpeed_Cache_Admin_Optimize'	=> 'admin/litespeed-cache-admin-optimize.class.php',
			'LiteSpeed_Cache_Admin_Report'		=> 'admin/litespeed-cache-admin-report.class.php',
			'LiteSpeed_Cache_Admin_Rules'		=> 'admin/litespeed-cache-admin-rules.class.php',
			'LiteSpeed_Cache_Admin_Settings'	=> 'admin/litespeed-cache-admin-settings.class.php',

			'LiteSpeed_Cache_Cli_Admin'			=> 'cli/litespeed-cache-cli-admin.class.php',
			'LiteSpeed_Cache_Cli_Purge'			=> 'cli/litespeed-cache-cli-purge.class.php',

			'LiteSpeed_Cache_Tags'				=> 'includes/deprecated-litespeed-cache-tags.class.php',

			'Minify_HTML'				=> 'lib/html_min.class.php',
		);
		if( array_key_exists($cls, $class2fileArr) && file_exists(LSCWP_DIR . $class2fileArr[$cls]) ) {
			require_once LSCWP_DIR . $class2fileArr[$cls];
		}
	}
}

spl_autoload_register('_litespeed_autoload');

/**
 * Load vendor loader
 *
 * @since  1.2.2
 */
if ( !function_exists('litespeed_load_vendor') ) {
	function litespeed_load_vendor()
	{
		require_once LSCWP_DIR.'lib/vendor/autoload.php';
	}
}
