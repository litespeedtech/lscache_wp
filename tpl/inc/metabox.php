<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

wp_nonce_field( self::POST_NONCE_ACTION, Router::NONCE );

$settings = array(
	array( __( 'Disable Cache', 'litespeed-cache' ), 'litespeed_no_cache', true ),
	array( __( 'Disable Image Lazyload', 'litespeed-cache' ), 'litespeed_no_image_lazy', true ),
);

foreach ( $settings as $v ) {
	echo '<div style="display:flex;margin-bottom:10px;align-items: center;gap: 2ch;justify-content: space-between;"><label for="' . $v[ 1 ] . '">' . $v[ 0 ] . '</label>';

	echo '
			<input class="litespeed-tiny-toggle" id="' . $v[ 1 ] . '" type="checkbox" value="1" ' . ( $v[ 2 ] ? 'checked' : '' ) . ' />
		';

	echo '</div>';

}
