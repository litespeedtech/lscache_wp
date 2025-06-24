<?php
/**
 * LiteSpeed Cache Post Meta Settings
 *
 * Renders the post meta settings interface for LiteSpeed Cache, allowing configuration of post-specific options.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

wp_nonce_field( self::POST_NONCE_ACTION, Router::NONCE );

$pid = get_the_ID();

foreach ( $this->_postmeta_settings as $key => $label ) {
	$existing_val = get_post_meta( $pid, $key, true );

	if ( in_array( $key, array( 'litespeed_vpi_list', 'litespeed_vpi_list_mobile' ), true ) ) {
		if ( is_array( $existing_val ) ) {
			$existing_val = implode( PHP_EOL, $existing_val );
		}
		?>
		<div style="margin-bottom:10px;">
			<label for="<?php echo esc_attr( Str::trim_quotes( $key ) ); ?>"><?php echo esc_html( $label ); ?></label>
			<textarea style="width:100%" rows="5" id="<?php echo esc_attr( Str::trim_quotes( $key ) ); ?>" name="<?php echo esc_attr( Str::trim_quotes( $key ) ); ?>"><?php echo esc_textarea( $existing_val ); ?></textarea>
		</div>
		<?php
	} else {
		?>
		<div style="display:flex;margin-bottom:10px;align-items: center;gap: 2ch;justify-content: space-between;">
			<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
			<input class="litespeed-tiny-toggle" id="<?php echo esc_attr( Str::trim_quotes( $key ) ); ?>" name="<?php echo esc_attr( Str::trim_quotes( $key ) ); ?>" type="checkbox" value="1" <?php echo $existing_val ? 'checked' : ''; ?> />
		</div>
		<?php
	}
}
?>

<div style="text-align:right;">
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/metabox/' ); ?>
</div>