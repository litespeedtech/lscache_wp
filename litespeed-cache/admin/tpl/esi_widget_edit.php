<?php
if ( !defined('WPINC') ) die;
// $widget, $return, $instance

$options = LiteSpeed_Cache_Esi::widget_load_get_options($widget) ;
if ( empty($options) ) {
	$options = array(
		LiteSpeed_Cache_Esi::WIDGET_OPID_ESIENABLE => false,
		LiteSpeed_Cache_Esi::WIDGET_OPID_TTL => '300'
	) ;
	$options = apply_filters('litespeed_cache_widget_default_options', $options, $widget) ;
}

if ( empty($options) ) {
	$esi = false ;
	$ttl = '300' ;
}
else {
	$esi = $options[LiteSpeed_Cache_Esi::WIDGET_OPID_ESIENABLE] ;
	$ttl = $options[LiteSpeed_Cache_Esi::WIDGET_OPID_TTL] ;
}

?>
<h4>LiteSpeed Cache:</h4>

<?php echo __('Enable ESI for this Widget:', 'litespeed-cache') ; ?>
&nbsp;&nbsp;&nbsp;
<?php LiteSpeed_Cache_Admin_Display::get_instance()->build_switch(LiteSpeed_Cache_Esi::WIDGET_OPID_ESIENABLE, false, false, $esi) ; ?>
<br /><br />

<?php echo __('Widget Cache TTL:', 'litespeed-cache') ; ?>
&nbsp;&nbsp;&nbsp;
<?php LiteSpeed_Cache_Admin_Display::get_instance()->build_input(LiteSpeed_Cache_Esi::WIDGET_OPID_TTL, false, false, false, null, $ttl, 'size="7"') ; ?>
<?php echo __('seconds', 'litespeed-cache') ; ?>

<p class="install-help">
	<?php echo __('Default value 300 seconds (5 minutes).', 'litespeed-cache') ; ?>
	<?php echo __('A TTL of 0 indicates do not cache.', 'litespeed-cache') ; ?>
</p>

<br /><br />