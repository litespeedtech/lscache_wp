<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

wp_nonce_field( self::POST_NONCE_ACTION, Router::NONCE );

$post_id = get_the_ID();

foreach ( $this->_postmeta_settings as $k => $v ) {
	$checked = get_post_meta( $post_id, $k, true );

	echo '<div style="display:flex;margin-bottom:10px;align-items: center;gap: 2ch;justify-content: space-between;"><label for="' . $k . '">' . $v . '</label>';

	echo '
			<input class="litespeed-tiny-toggle" id="' . $k . '" name="' . $k . '" type="checkbox" value="1" ' . ( $checked ? 'checked' : '' ) . ' />
		';

	echo '</div>';

}
