<?php
if ( ! defined( 'WPINC' ) ) die ;
?>
<div class="litespeed-wrap notice notice-info litespeed-banner-promo-full is-dismissible" id="litespeed-banner-promo-slack">
	<div class="litespeed-banner-promo-logo"></div>

	<div class="litespeed-banner-promo-content">
		<h3><?php echo __( 'Welcome to LiteSpeed', 'litespeed-cache' ) ; ?></h3>

		<div class="litespeed-banner-promo">

			<div class="litespeed-banner-promo-slacklogo"></div>

			<div class="litespeed-banner-promo-content">
				<p class="litespeed-banner-promo-slack-line1">
					<?php echo __( 'Want to connect with other LiteSpeed users?', 'litespeed-cache' ) ; ?>
					<?php echo sprintf( __( 'Join the %s community.', 'litespeed-cache' ), '<a href="https://goo.gl/mrKuTw" target="_blank" class="litespeed-banner-promo-slack-textlink">LiteSpeed Slack</a>' ) ; ?>
				</p>

				<p class="litespeed-banner-promo-slack-line2">
					<span class="litespeed-banner-promo-slack-link">golitespeed.slack.com</span>
					<a href="https://goo.gl/mrKuTw" target="_blank" class="litespeed-btn-success litespeed-btn-xs litespeed-banner-promo-slack-btn"><?php echo __( 'Join Us on Slack', 'litespeed-cache' ) ; ?></a>
				</p>
			</div>

			<?php $dismiss_url = LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_DISMISS, LiteSpeed_Cache_GUI::TYPE_DISMISS_PROMO, false, null, array( 'promo_tag' => 'banner_promo.slack' ) ) ; ?>
		</div>
	</div>
</div>
