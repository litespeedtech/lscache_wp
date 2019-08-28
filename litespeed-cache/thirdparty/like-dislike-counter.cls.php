<?php
/**
 * The Third Party integration with the Like Dislike Counter plugin.
 *
 * @since		1.0.7
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed\Thirdparty ;

defined( 'WPINC' ) || exit ;

use \LiteSpeed\API ;


class Like_Dislike_Counter
{
	/**
	 * @since  3.0
	 */
	public static function detect()
	{
		/**
		 * NOTICE: Since this plugin doesn't set the DOING_AJAX global during its
		 * Ajax request, we do not know when the request is an ajax request.
		 *
		 * This solution works for now, but is not the optimal way of handling this.
		 */
		if ( function_exists('ldclite_get_version') && $_POST && isset($_POST['up_type']) ) {
			API::hook_purge( __CLASS__ . '::purge' ) ;
		}
	}

	/**
	 * Need to purge the post after someone 'likes' or 'dislikes' the post.
	 */
	public static function purge()
	{
		if ( isset($_POST['post_id']) ) {
			API::purge( API::TYPE_POST . $_POST['post_id'] ) ;
		}
	}

}

