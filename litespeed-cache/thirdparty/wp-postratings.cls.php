<?php
/**
 * The Third Party integration with the WP-PostRatings plugin.
 *
 * @since		1.1.1
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed\Thirdparty ;

defined( 'WPINC' ) || exit ;

use \LiteSpeed\API ;

class WP_PostRatings
{

	/**
	 * Detects if plugin is installed.
	 *
	 * @since 1.1.1
	 * @access public
	 */
	public static function detect()
	{
		if ( defined( 'WP_POSTRATINGS_VERSION' ) ) {
			add_action( 'rate_post', __CLASS__ . '::flush', 10, 3 ) ;
		}
	}

	/**
	 * Purges the cache
	 *
	 * @since 1.1.1
	 * @access public
	 */
	public static function flush( $uid, $post_id, $post_ratings_score )
	{
		API::purge( API::TYPE_POST . $post_id ) ;
	}

}
