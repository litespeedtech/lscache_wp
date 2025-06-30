<?php
/**
 * LiteSpeed Cache Widget Settings
 *
 * Configures ESI settings for widgets in LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$options = ! empty( $instance[ Base::OPTION_NAME ] ) ? $instance[ Base::OPTION_NAME ] : array();

if ( empty( $options ) ) {
	$options = array(
		ESI::WIDGET_O_ESIENABLE => Base::VAL_OFF,
		ESI::WIDGET_O_TTL       => '28800',
	);

	add_filter( 'litespeed_widget_default_options', 'LiteSpeed\ESI::widget_default_options', 10, 2 );

	$options = apply_filters( 'litespeed_widget_default_options', $options, $widget );
}

if ( empty( $options ) ) {
	$esi = Base::VAL_OFF;
	$ttl = '28800';
} else {
	$esi = $options[ ESI::WIDGET_O_ESIENABLE ];
	$ttl = $options[ ESI::WIDGET_O_TTL ];
}

$display = Admin_Display::cls();

?>
<div class="litespeed-widget-setting">

	<h4><?php esc_html_e( 'LiteSpeed Cache', 'litespeed-cache' ); ?>:</h4>

	<b><?php esc_html_e( 'Enable ESI', 'litespeed-cache' ); ?>:</b>
	&nbsp;
	<div class="litespeed-inline">
		<div class="litespeed-switch litespeed-mini">
		<?php
			$esi_option = ESI::WIDGET_O_ESIENABLE;
			$name       = $widget->get_field_name( $esi_option );

			$cache_status_list = array(
				array( Base::VAL_ON, esc_html__( 'Public', 'litespeed-cache' ) ),
				array( Base::VAL_ON2, esc_html__( 'Private', 'litespeed-cache' ) ),
				array( Base::VAL_OFF, esc_html__( 'Disable', 'litespeed-cache' ) ),
			);

			foreach ( $cache_status_list as $v ) {
				list( $value, $label ) = $v;
				$id_attr               = $widget->get_field_id( $esi_option ) . '_' . $value;
				$checked               = $esi === $value ? 'checked' : '';
				?>
				<input type="radio" autocomplete="off" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($id_attr); ?>" value="<?php echo esc_attr( $value ); ?>" <?php echo esc_attr($checked); ?> />
				<label for="<?php echo esc_attr($id_attr); ?>"><?php echo esc_html( $label ); ?></label>
				<?php
			}
		?>
		</div>
	</div>
	<br /><br />

	<b><?php esc_html_e( 'Widget Cache TTL', 'litespeed-cache' ); ?>:</b>
	&nbsp;
	<?php
		$ttl_option = ESI::WIDGET_O_TTL;
		$name       = $widget->get_field_name( $ttl_option );
		?>
		<input type="text" class="regular-text litespeed-reset" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($ttl); ?>" size="7" />
	<?php esc_html_e( 'seconds', 'litespeed-cache' ); ?>

	<p class="install-help">
		<?php esc_html_e( 'Recommended value: 28800 seconds (8 hours).', 'litespeed-cache' ); ?>
		<?php esc_html_e( 'A TTL of 0 indicates do not cache.', 'litespeed-cache' ); ?>
	</p>
</div>

<br />