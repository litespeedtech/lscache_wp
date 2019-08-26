<?php
if ( !defined('WPINC') ) die;
// $widget, $return, $instance

$options = ! empty( $instance[ LiteSpeed_Cache_Config::OPTION_NAME ] ) ? $instance[ LiteSpeed_Cache_Config::OPTION_NAME ] : array() ;

if ( empty( $options ) ) {
	$options = array(
		LiteSpeed_Cache_ESI::WIDGET_OPID_ESIENABLE => LiteSpeed_Cache_Config::VAL_OFF,
		LiteSpeed_Cache_ESI::WIDGET_OPID_TTL => '28800'
	) ;

	add_filter('litespeed_cache_widget_default_options', 'LiteSpeed_Cache_ESI::widget_default_options', 10, 2) ;

	$options = apply_filters( 'litespeed_cache_widget_default_options', $options, $widget ) ;
}

if ( empty( $options ) ) {
	$esi = LiteSpeed_Cache_Config::VAL_OFF ;
	$ttl = '28800' ;
}
else {
	$esi = $options[ LiteSpeed_Cache_ESI::WIDGET_OPID_ESIENABLE ] ;
	$ttl = $options[ LiteSpeed_Cache_ESI::WIDGET_OPID_TTL ] ;
}

$display = LiteSpeed_Cache_Admin_Display::get_instance() ;

?>
<div class="litespeed-widget-setting">

	<h4>LiteSpeed Cache:</h4>

	<b><?php echo __( 'Enable ESI', 'litespeed-cache' ) ; ?>:</b>
	&nbsp;&nbsp;
	<div class="litespeed-inline">
		<div class="litespeed-switch litespeed-mini">
		<?php
			$id = LiteSpeed_Cache_ESI::WIDGET_OPID_ESIENABLE ;
			$name = $widget->get_field_name( $id ) ;

			$cache_status_list = array(
				array( LiteSpeed_Cache_Config::VAL_ON, 	__( 'Public', 'litespeed-cache' ) ),
				array( LiteSpeed_Cache_Config::VAL_ON2, __( 'Private', 'litespeed-cache' ) ),
				array( LiteSpeed_Cache_Config::VAL_OFF, __( 'Disable', 'litespeed-cache' ) ),
			) ;

			foreach ( $cache_status_list as $v ) {
				list( $v, $txt ) = $v ;
				$id_attr = $widget->get_field_id( $id ) . '_' . $v ;
				$checked = $esi === $v ? 'checked' : '' ;
				echo "<input type='radio' autocomplete='off' name='$name' id='$id_attr' value='$v' $checked /> <label for='$id_attr'>$txt</label>" ;
			}
		?>

		</div>
	</div>
	<br /><br />

	<b><?php echo __( 'Widget Cache TTL:', 'litespeed-cache' ) ; ?></b>
	&nbsp;&nbsp;
	<?php
		$id = LiteSpeed_Cache_ESI::WIDGET_OPID_TTL ;
		$name = $widget->get_field_name( $id ) ;
		echo "<input type='text' class='litespeed-regular-text litespeed-reset' name='$name' value='$ttl' size='7' />" ;
	?>
	<?php echo __( 'seconds', 'litespeed-cache' ) ; ?>

	<p class="install-help">
		<?php echo __( 'Recommended value: 28800 seconds (8 hours).', 'litespeed-cache' ) ; ?>
		<?php echo __( 'A TTL of 0 indicates do not cache.', 'litespeed-cache' ) ; ?>
	</p>
</div>

<br />