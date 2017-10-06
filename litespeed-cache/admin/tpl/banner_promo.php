<?php
if ( ! defined( 'WPINC' ) ) die ;

if ( ! LiteSpeed_Cache_Router::has_promo_msg() ) {
	return ;
}

?>
<div class="litespeed-wrap notice notice-info litespeed-banner-promo">
	<div class="litespeed-banner-promo-logo"></div>

	<div class="litespeed-banner-promo-content">

		<h2><?php echo __( 'Welcome to LiteSpeed', 'litespeed-cache' ) ; ?></h2>

		<p>
			<?php echo sprintf(
				__( 'Thanks for using LiteSpeed. If there is any question, please don\'t hesitate to let us know. You can ask in <a %s>support forum</a> or <a %s>submit a ticket</a>.', 'litespeed-cache' ),
				'href="https://wordpress.org/support/plugin/litespeed-cache" target="_blank"',
				'href="https://www.litespeedtech.com/support" target="_blank"'
			) ; ?>
		</p>

		<p>
			<?php echo __( 'This plugin is created with love by LiteSpeed. Your rating is the simplest way to support us. We really appreciate it!', 'litespeed-cache' ) ; ?>
		</p>

		<a class="litespeed-btn-success litespeed-btn-xs" href="https://wordpress.org/support/plugin/litespeed-cache/reviews/?filter=5#new-post" target="_blank"><?php echo __( 'Sure I\'d love to!', 'litespeed-cache' ) ; ?></a>
		<button type="button" class="litespeed-btn-primary litespeed-btn-xs" id="litespeed-promo-done"><?php echo __( 'I\'ve already left a review', 'litespeed-cache' ) ; ?></button>
		<button type="button" class="litespeed-btn-warning litespeed-btn-xs" id="litespeed-promo-later"><?php echo __( 'Maybe later', 'litespeed-cache' ) ; ?></button>
	</div>
</div>
