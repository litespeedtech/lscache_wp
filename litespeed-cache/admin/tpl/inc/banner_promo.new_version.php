<?php
if ( ! defined( 'WPINC' ) ) die ;
?>
<div class="litespeed-wrap notice notice-success litespeed-banner-promo">
	<div class="litespeed-banner-promo-logo"></div>

	<div class="litespeed-banner-promo-content">

		<h2><?php echo __( 'New Version Available!', 'litespeed-cache' ) ; ?></h2>

		<p>
			<?php echo __( 'New release v2.9 is available now.', 'litespeed-cache' ) ; ?>
		</p>

		<a class="litespeed-btn-success litespeed-btn-xs" href="https://wordpress.org/support/plugin/litespeed-cache/reviews/?filter=5#new-post" target="_blank">⬆️ <?php echo __( 'Upgrade', 'litespeed-cache' ) ; ?></a>
		<button type="button" class="litespeed-btn-primary litespeed-btn-xs" id="litespeed-promo-done">♻️ <?php echo __( 'Turn On Auto Upgrade', 'litespeed-cache' ) ; ?></button>

		<?php $dismiss_url = LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_DISMISS, LiteSpeed_Cache_GUI::TYPE_DISMISS_PROMO, false, null, 'promo_tag=banner_promo.new_version' ) ; ?>
	</div>
</div>
