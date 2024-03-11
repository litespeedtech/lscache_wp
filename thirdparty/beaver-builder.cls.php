<?php
/**
 * The Third Party integration with the Beaver Builder plugin.
 *
 * @since		3.0
 */
namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

class Beaver_Builder
{
	/**
	 * Detects if Beaver_Builder is active.
	 *
	 *@since 3.0
	 *@access public
	 */
	public static function detect()
	{
		if (!defined('FL_BUILDER_VERSION')) {
			return;
		}

		/**
		 * Purge All hooks
		 * @see  beaver-builder/extensions/fi-builder-cache-helper/classes/class-fi-builder-cache-helper.php
		 */
		$actions = array('fl_builder_cache_cleared', 'fl_builder_after_save_layout', 'fl_builder_after_save_user_template', 'upgrader_process_complete');

		foreach ($actions as $val) {
			add_action($val, __CLASS__ . '::purge');
		}
	}

	/**
	 * Purges the cache when Beaver_Builder's cache is purged.
	 *
	 * @since 3.0
	 * @access public
	 */
	public static function purge()
	{
		do_action('litespeed_purge_all', '3rd Beaver_Builder');
	}
}
