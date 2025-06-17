<?php
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

if ( ! is_multisite() ) {
	return;
}

if ( get_current_blog_id() === BLOG_ID_CURRENT_SITE ) {
	return;
}

if ( ! $this->network_conf( Base::NETWORK_O_USE_PRIMARY ) ) {
	return;
}
?>
		<div class="litespeed-callout notice notice-error inline">
			<h4><?php echo __( 'WARNING', 'litespeed-cache' ); ?></h4>
			<p>
				<?php echo __( 'The network admin selected use primary site configs for all subsites.', 'litespeed-cache' ); ?>
				<?php echo __( 'The following options are selected, but are not editable in this settings page.', 'litespeed-cache' ); ?>
			</p>
		</div>
