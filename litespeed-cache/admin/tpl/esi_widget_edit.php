<?php
if ( !defined('WPINC') ) die;
// $widget, $return, $instance

$options = LiteSpeed_Cache_ESI::widget_load_get_options($widget) ;
if ( empty($options) ) {
	$options = array(
		LiteSpeed_Cache_ESI::WIDGET_OPID_ESIENABLE => false,
		LiteSpeed_Cache_ESI::WIDGET_OPID_TTL => '28800'
	) ;
	$options = apply_filters('litespeed_cache_widget_default_options', $options, $widget) ;
}

if ( empty($options) ) {
	$esi = false ;
	$ttl = '28800' ;
}
else {
	$esi = $options[LiteSpeed_Cache_ESI::WIDGET_OPID_ESIENABLE] ;
	$ttl = $options[LiteSpeed_Cache_ESI::WIDGET_OPID_TTL] ;
}

?>
<div class="litespeed-widget-setting">

	<h4>LiteSpeed Cache:</h4>

	<b><?php echo __('Enable ESI', 'litespeed-cache') ; ?>:</b>
	&nbsp;&nbsp;
	<div class="litespeed-inline">
		<?php LiteSpeed_Cache_Admin_Display::get_instance()->build_switch(LiteSpeed_Cache_ESI::WIDGET_OPID_ESIENABLE, false, false, $esi, 'litespeed-cfg-'.$widget->id) ; ?>
	</div>
	<br /><br />

	<b><?php echo __('Widget Cache TTL:', 'litespeed-cache') ; ?></b>
	&nbsp;&nbsp;
	<?php LiteSpeed_Cache_Admin_Display::get_instance()->build_input(LiteSpeed_Cache_ESI::WIDGET_OPID_TTL, 'litespeed-reset', false, false, null, $ttl, 'size="7"') ; ?>
	<?php echo __('seconds', 'litespeed-cache') ; ?>

	<p class="install-help">
		<?php echo __('Recommended value: 28800 seconds (8 hours).', 'litespeed-cache') ; ?>
		<?php echo __('A TTL of 0 indicates do not cache.', 'litespeed-cache') ; ?>
	</p>
</div>

<br />