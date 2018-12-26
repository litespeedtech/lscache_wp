<?php
if ( ! defined( 'WPINC' ) ) die ;
/**
 * NOTE: Only show for single site
 */

?>
<div class="litespeed-wrap notice notice-success litespeed-banner-promo-full">
	<div class="litespeed-banner-promo-logo"></div>

	<div class="litespeed-banner-promo-content">
		<h3 class="litespeed-banner-title litespeed-top15"><?php echo __( 'New Version Available!', 'litespeed-cache' ) ; ?></h3>
		<div class="litespeed-banner-description">
			<div class="litespeed-banner-description-padding-right-15">
				<p class="litespeed-banner-desciption-content">
					<?php echo __( 'New release v2.9 is available now.', 'litespeed-cache' ) ; ?>
				</p>
			</div>
			<div class="litespeed-row-flex litespeed-banner-description">
				<div class="litespeed-banner-description-padding-right-15">
					<a href="https://wordpress.org/support/plugin/litespeed-cache/reviews/?filter=5#new-post" target="_blank" class="litespeed-btn-primary litespeed-btn-mini">
						<i class="dashicons dashicons-image-rotate">&nbsp;</i>
						 <?php echo __( 'Upgrade', 'litespeed-cache' ) ; ?>
					</a>
				</div>
				<div class="litespeed-banner-description-padding-right-15">
					<?php
						$cfg = array( LiteSpeed_Cache_Config::TYPE_SET . '[' . LiteSpeed_Cache_Config::OPT_AUTO_UPGRADE . ']' => 1 ) ;
						$url = LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_CFG, LiteSpeed_Cache_Config::TYPE_SET, false, null, $cfg ) ;
					?>
					<a href="<?php echo $url ; ?>" class="litespeed-btn-success litespeed-btn-mini">
						<i class="dashicons dashicons-update">&nbsp;</i>
						<?php echo __( 'Turn On Auto Upgrade', 'litespeed-cache' ) ; ?>
					</a>
				</div>
			</div>
		</div>
	</div>

	<div>
		<?php $dismiss_url = LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_DISMISS, LiteSpeed_Cache_GUI::TYPE_DISMISS_PROMO, false, null, array( 'promo_tag' => 'banner_promo.new_version' ) ) ; ?>
		<span class="screen-reader-text">Dismiss this notice.</span>
		<a href="<?php echo $dismiss_url ; ?>" class="litespeed-notice-dismiss">
			Dismiss
		</a>
	</div>
</div>