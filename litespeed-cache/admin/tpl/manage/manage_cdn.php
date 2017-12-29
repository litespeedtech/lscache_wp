<?php
if ( ! defined( 'WPINC' ) ) die ;

$cf_on = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE ) ;
$cf_domain = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE_NAME ) ?: '-' ;
$cf_zone = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_CDN_CLOUDFLARE_ZONE ) ?: '-' ;
?>
<h3 class="litespeed-title"><?php echo __('Cloudflare', 'litespeed-cache'); ?></h3>

<?php if ( ! $cf_on ) : ?>
<div class="litespeed-callout-danger">
	<h4><?php echo __( 'WARNING', 'litespeed-cache' ) ; ?></h4>
	<p>
		<?php echo __('Please go to Settings to turn Cloudflare API on before use the following functionalities.', 'litespeed-cache'); ?>
	</p>
</div>
<?php endif ; ?>

<p><?php echo __('Cloudflare Domain', 'litespeed-cache'); ?>: <code><?php echo $cf_domain ; ?></code></p>
<p><?php echo __('Cloudflare Zone', 'litespeed-cache'); ?>: <code><?php echo $cf_zone ; ?></code></p>

<?php if ( ! $cf_on ) : ?>
<a href="#" class="litespeed-btn-default disabled">
	<?php echo __( 'Purge Everything on Cloudflare', 'litespeed-cache' ) ; ?>
</a>
<?php else : ?>
<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_CDN, LiteSpeed_Cache_CDN::TYPE_CLOUDFLARE_PURGE_ALL ) ; ?>" class="litespeed-btn-warning">
	<?php echo __( 'Purge Everything on Cloudflare', 'litespeed-cache' ) ; ?>
</a>
<?php endif ; ?>
