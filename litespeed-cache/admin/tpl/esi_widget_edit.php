<?php
if ( !defined('WPINC') ) die;
// $widget, $return, $instance

$options = LiteSpeed_Cache_ESI::widget_load_get_options( $widget ) ;
if ( empty( $options ) ) {
	$options = array(
		LiteSpeed_Cache_ESI::WIDGET_OPID_ESIENABLE => LiteSpeed_Cache_Config::VAL_OFF,
		LiteSpeed_Cache_ESI::WIDGET_OPID_TTL => '28800'
	) ;
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

			echo $this->build_radio(
				$id,
				LiteSpeed_Cache_Config::VAL_ON,
				__( 'Public', 'litespeed-cache' ),
				$esi === LiteSpeed_Cache_Config::VAL_ON,
				'litespeed-cfg-' . $widget->id . '_' . LiteSpeed_Cache_Config::VAL_ON
			);

			echo $this->build_radio(
				$id,
				LiteSpeed_Cache_Config::VAL_ON2,
				__( 'Private', 'litespeed-cache' ),
				$esi === LiteSpeed_Cache_Config::VAL_ON2,
				'litespeed-cfg-' . $widget->id . '_' . LiteSpeed_Cache_Config::VAL_ON2
			);

			echo $this->build_radio(
				$id,
				LiteSpeed_Cache_Config::VAL_OFF,
				__( 'Disable', 'litespeed-cache' ),
				$esi === LiteSpeed_Cache_Config::VAL_OFF,
				'litespeed-cfg-' . $widget->id . '_' . LiteSpeed_Cache_Config::VAL_OFF
			);
		?>

		</div>
	</div>
	<br /><br />

	<b><?php echo __( 'Widget Cache TTL:', 'litespeed-cache' ) ; ?></b>
	&nbsp;&nbsp;
	<?php $display->build_input( LiteSpeed_Cache_ESI::WIDGET_OPID_TTL, 'litespeed-reset', $ttl, null, 'size="7"' ) ; ?>
	<?php echo __( 'seconds', 'litespeed-cache' ) ; ?>

	<p class="install-help">
		<?php echo __( 'Recommended value: 28800 seconds (8 hours).', 'litespeed-cache' ) ; ?>
		<?php echo __( 'A TTL of 0 indicates do not cache.', 'litespeed-cache' ) ; ?>
	</p>
</div>

<br />