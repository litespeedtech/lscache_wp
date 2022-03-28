<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

wp_nonce_field( self::POST_NONCE_ACTION, Router::NONCE );

$settings = array(
	array( __( 'Disable Cache', 'litespeed-cache' ), 'litespeed_no_cache', true ),
	array( __( 'Disable Image Lazyload', 'litespeed-cache' ), 'litespeed_no_image_lazy', true ),
);

foreach ( $settings as $v ) {
	echo '<div class="components-panel__row"><label style="margin-right:10px;" for="' . $v[ 1 ] . '">' . $v[ 0 ] . '</label>';

	echo '<span class="components-form-toggle is-checked">
			<input class="components-form-toggle__input" id="' . $v[ 1 ] . '" type="checkbox" ' . ( $v[ 2 ] ? 'checked' : '' ) . ' />
			<span class="components-form-toggle__track"></span>
			<span class="components-form-toggle__thumb"></span>
		</span>';

	echo '</div>';

}
