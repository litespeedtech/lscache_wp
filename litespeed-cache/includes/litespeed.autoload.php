<?php
/**
 * Auto registration for LiteSpeed classes
 *
 * @since      1.0.16
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/includes
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */

if ( ! defined('WPINC') ) {
	die ;
}

spl_autoload_register('_litespeed_autoload');
function _litespeed_autoload($cls){
	$class2fileArr = array(
		'LiteSpeed'							=> 'lib//litespeed/litespeed.class.php',

		'LiteSpeed_Cache'					=> 'includes/class-litespeed-cache.php',
		'LiteSpeed_Cache_Config'			=> 'includes/class-litespeed-cache-config.php',
		'LiteSpeed_Cache_Router'			=> 'includes/class-litespeed-cache-router.php',
		'LiteSpeed_Cache_Tags'				=> 'includes/class-litespeed-cache-tags.php',
		'LiteSpeed_Cache_Crawler_Sitemap'	=> 'includes/class-litespeed-cache-sitemap.php',

		'LiteSpeed_Cache_Admin'				=> 'admin/class-litespeed-cache-admin.php',
		'LiteSpeed_Cache_Admin_Display'		=> 'admin/class-litespeed-cache-admin-display.php',
		'LiteSpeed_Cache_Admin_Error'		=> 'admin/class-litespeed-cache-admin-error.php',
		'LiteSpeed_Cache_Admin_Report'		=> 'admin/class-litespeed-cache-admin-report.php',
		'LiteSpeed_Cache_Admin_Rules'		=> 'admin/class-litespeed-cache-admin-rules.php',
		'LiteSpeed_Cache_Admin_Settings'	=> 'admin/class-litespeed-cache-admin-settings.php',

		'LiteSpeed_Cache_Cli_Admin'			=> 'cli/class-litespeed-cache-cli-admin.php',
		'LiteSpeed_Cache_Cli_Purge'			=> 'cli/class-litespeed-cache-cli-purge.php',
	);
	if(array_key_exists($cls, $class2fileArr) && file_exists(LSWCP_DIR . $class2fileArr[$cls])){
		require_once LSWCP_DIR . $class2fileArr[$cls];
	}
}