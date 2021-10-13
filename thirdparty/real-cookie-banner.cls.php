<?php
/**
 * The Third Party integration with the Real-Cookie-Banner plugin.
 *
 * @since        4.4.3
 */
namespace LiteSpeed\Thirdparty;

defined( 'WPINC' ) || exit;

class Real_Cookie_Banner
{
	/**
	 * Detects if Real-Cookie-Banner are installed.
	 *
	 * @since 4.4.3
	 * @access public
	 */
	public static function detect()
	{
		if ( ! defined( 'RCB_FILE' ) ) {
			return;
		}

		add_filter('RCB/Blocker/Enabled', function($isEnabled) {
			if ( $isEnabled && isset( $_GET[ 'ucss_ccss_comp' ] ) && $_GET[ 'ucss_ccss_comp' ] === '1') {
				return false;
			}
			return $isEnabled;
		});
	}

}