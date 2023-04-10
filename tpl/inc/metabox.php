<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

wp_nonce_field( self::POST_NONCE_ACTION, Router::NONCE );

$post_id = get_the_ID();

foreach ( $this->_postmeta_settings as $k => $v ) {
	$existing_val = get_post_meta( $post_id, $k, true );


	if ( in_array( $k, array( 'litespeed_vpi_list', 'litespeed_vpi_list_mobile' ) ) ) {
		if ( is_array( $existing_val ) ) $existing_val = implode( PHP_EOL, $existing_val );
		echo '<div style="margin-bottom:10px;"><label for="' . $k . '">' . $v . '</label>';
		echo '<textarea style="width:100%" rows="5" id="' . $k . '" name="' . $k . '">' . $existing_val . '</textarea>';
		echo '</div>';
	}
	else {
		echo '<div style="display:flex;margin-bottom:10px;align-items: center;gap: 2ch;justify-content: space-between;"><label for="' . $k . '">' . $v . '</label>';
		echo '<input class="litespeed-tiny-toggle" id="' . $k . '" name="' . $k . '" type="checkbox" value="1" ' . ( $existing_val ? 'checked' : '' ) . ' />';
		echo '</div>';
	}
}

echo '<div style="text-align:right;">';
Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/metabox/' );
echo '</div>';