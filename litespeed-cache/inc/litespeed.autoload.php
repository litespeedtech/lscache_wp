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
			'LiteSpeed'							=> 'lib/litespeed/litespeed.cls.php',
			'Litespeed_Crawler'					=> 'lib/litespeed/litespeed-crawler.cls.php',
			'Litespeed_File'					=> 'lib/litespeed/litespeed-file.cls.php',
			'Litespeed_String'					=> 'lib/litespeed/litespeed-string.cls.php',

			'LiteSpeed_Cache'					=> 'inc/litespeed-cache.cls.php',
			'LiteSpeed_Cache_Activation'		=> 'inc/activation.cls.php',
			'LiteSpeed_Cache_API'				=> 'inc/api.cls.php',
			'LiteSpeed_Cache_CDN'				=> 'inc/cdn.cls.php',
			'LiteSpeed_Cache_CDN_Cloudflare'	=> 'inc/cdn/cloudflare.cls.php',
			'LiteSpeed_Cache_CDN_Quic'			=> 'inc/cdn/quic.cls.php',
			'LiteSpeed_Cache_Config'			=> 'inc/config.cls.php',
			'LiteSpeed_Cache_Control'			=> 'inc/control.cls.php',
			'LiteSpeed_Cache_Const'				=> 'inc/const.cls.php',
			'LiteSpeed_Cache_Crawler'			=> 'inc/crawler.cls.php',
			'LiteSpeed_Cache_Crawler_Sitemap'	=> 'inc/crawler-sitemap.cls.php',
			'LiteSpeed_Cache_CSS'				=> 'inc/css.cls.php',
			'LiteSpeed_Cache_Data'				=> 'inc/data.cls.php',
			'LiteSpeed_Cache_Db_Optm'			=> 'inc/db_optm.cls.php',
			'LiteSpeed_Cache_Doc'				=> 'inc/doc.cls.php',
			'LiteSpeed_Cache_ESI'				=> 'inc/esi.cls.php',
			'LiteSpeed_Cache_GUI'				=> 'inc/gui.cls.php',
			'LiteSpeed_Cache_Import'			=> 'inc/import.cls.php',
			'LiteSpeed_Cache_Img_Optm'			=> 'inc/img_optm.cls.php',
			'LiteSpeed_Cache_Lang'				=> 'inc/lang.cls.php',
			'LiteSpeed_Cache_Log'				=> 'inc/log.cls.php',
			'LiteSpeed_Cache_Media'				=> 'inc/media.cls.php',
			'LiteSpeed_Cache_Object'			=> 'inc/object.cls.php',
			'LiteSpeed_Cache_Optimize'			=> 'inc/optimize.cls.php',
			'LiteSpeed_Cache_Optimizer'			=> 'inc/optimizer.cls.php',
			'LiteSpeed_Cache_Purge'				=> 'inc/purge.cls.php',
			'LiteSpeed_Cache_REST'				=> 'inc/rest.cls.php',
			'LiteSpeed_Cache_Router'			=> 'inc/router.cls.php',
			'LiteSpeed_Cache_Tag'				=> 'inc/tag.cls.php',
			'LiteSpeed_Cache_Task'				=> 'inc/task.cls.php',
			'LiteSpeed_Cache_Tool'				=> 'inc/tool.cls.php',
			'LiteSpeed_Cache_Vary'				=> 'inc/vary.cls.php',
			'LiteSpeed_Cache_Utility'			=> 'inc/utility.cls.php',

			'LiteSpeed_Cache_Admin'				=> 'admin/admin.cls.php',
			'LiteSpeed_Cache_Admin_API'			=> 'admin/admin-api.cls.php',
			'LiteSpeed_Cache_Admin_Display'		=> 'admin/admin-display.cls.php',
			'LiteSpeed_Cache_Admin_Error'		=> 'admin/admin-error.cls.php',
			'LiteSpeed_Cache_Admin_Report'		=> 'admin/admin-report.cls.php',
			'LiteSpeed_Cache_Admin_Rules'		=> 'admin/admin-rules.cls.php',
			'LiteSpeed_Cache_Admin_Settings'	=> 'admin/admin-settings.cls.php',

			'LiteSpeed_Cache_Cli_Admin'			=> 'cli/cli-admin.cls.php',
			'LiteSpeed_Cache_CLI_IAPI'			=> 'cli/cli-iapi.cls.php',
			'LiteSpeed_Cache_Cli_Purge'			=> 'cli/cli-purge.cls.php',

			'LiteSpeed_3rd_Lib\Minify_HTML'			=> 'lib/html_min.cls.php',
			'LiteSpeed_3rd_Lib\css_min\Minifier' 	=> 'lib/css_min.cls.php',
			'LiteSpeed_3rd_Lib\css_min\Colors' 		=> 'lib/css_min.colors.cls.php',
			'LiteSpeed_3rd_Lib\css_min\Utils' 		=> 'lib/css_min.utils.cls.php',
			'LiteSpeed_3rd_Lib\css_min\UriRewriter' => 'lib/css_min.url_rewritter.cls.php',
			'LiteSpeed_3rd_Lib\js_min\JSMin' 		=> 'lib/js_min.cls.php',
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
