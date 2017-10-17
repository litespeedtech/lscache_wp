<?php
if ( ! defined( 'WPINC' ) ) die ;

$sapi_key = get_option( LiteSpeed_Cache_Admin_API::DB_SAPI_KEY ) ;

?>

<div class="wrap">
	<h2>
		<?php echo __('LiteSpeed Cache Image Optimization', 'litespeed-cache') ; ?>
		<span class="litespeed-desc">
			v<?php echo LiteSpeed_Cache::PLUGIN_VERSION; ?>
		</span>
	</h2>
</div>
<div class="litespeed-wrap">
	<div class="litespeed-body">
		<h3 class="litespeed-title"><?php echo __('Auth Info', 'litespeed-cache') ; ?></h3>
		<?php if ( $sapi_key ) : ?>
			<?php echo __('Your API key is ', 'litespeed-cache') ; ?>
			<?php echo $sapi_key ; ?>
		<?php else : ?>
		<a href="<?php echo LiteSpeed_Cache_Utility::build_url(LiteSpeed_Cache::ACTION_SAPI_PROCEED, false, 'type=' . LiteSpeed_Cache_Admin_API::ACTION_REQUEST_KEY ) ; ?>" class="litespeed-btn-success">
			<?php echo __( 'Initialize Key', 'litespeed-cache' ) ; ?>
		</a>
		<span class="litespeed-desc">
			<?php echo __( 'This will communicate with LiteSpeed server, get a free key, and get the assigned server for optimization requests.', 'litespeed-cache' ) ; ?>
		</span>
		<?php endif ; ?>

		<h3 class="litespeed-title"><?php echo __('Images', 'litespeed-cache') ; ?></h3>

	</div>
</div>
