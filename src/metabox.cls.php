<?php
/**
 * The class to operate post editor metabox settings
 *
 * @since 		4.7
 * @package    	Core
 * @subpackage 	Core/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

class Metabox extends Root {

	/**
	 * Load setting per post
	 * @since 4.7
	 */
	public function setting( $conf ) {
		// Check if has metabox non-cacheable setting or not
		if ( is_singular() ) {
			$post_id = get_the_ID();
			if ( $post_id && $val = get_post_meta( $post_id, $conf, true ) ) {
				return $val;
			}
		}

		return false;
	}
}
