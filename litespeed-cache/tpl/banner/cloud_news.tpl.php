<?php
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

?>

<div class="litespeed-wrap notice notice-success litespeed-banner-promo-full">

	<div class="litespeed-banner-promo-content">
		<h3 class="litespeed-banner-title litespeed-top15"><?php echo $this->_summary[ 'news.title' ] ; ?></h3>
		<div class="litespeed-banner-description">
			<div class="litespeed-banner-description-padding-right-15">
				<p class="litespeed-banner-desciption-content">
					<?php echo $this->_summary[ 'news.content' ]; ?>
				</p>
			</div>
			<div class="litespeed-row-flex litespeed-banner-description">
				<div class="litespeed-banner-description-padding-right-15">
					<?php if ( ! empty( $this->_summary[ 'news.plugin' ] ) ) : ?>
					<?php $install_link = Utility::build_url( Router::ACTION_ACTIVATION, Activation::TYPE_INSTALL_3RD, false, null, array( 'plugin' => $this->_summary[ 'news.plugin' ] ) ); ?>
					<a href="<?php echo $install_link ; ?>" class="litespeed-btn-success litespeed-btn-mini">
						 <?php echo __( 'Install', 'litespeed-cache' ); ?>
						 <?php if ( ! empty( $this->_summary[ 'news.plugin_name' ] ) ) echo $this->_summary[ 'news.plugin_name' ]; ?>
					</a>
					<?php endif; ?>
					<?php if ( ! empty( $this->_summary[ 'news.zip' ] ) ) : ?>
					<?php $install_link = Utility::build_url( Router::ACTION_ACTIVATION, Activation::TYPE_INSTALL_ZIP ); ?>
					<a href="<?php echo $install_link ; ?>" class="litespeed-btn-success litespeed-btn-mini">
						 <?php echo __( 'Install Beta Version', 'litespeed-cache' ); ?>
					</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<div>
		<?php $dismiss_url = Utility::build_url( Router::ACTION_ACTIVATION, Activation::TYPE_DISMISS_RECOMMENDED ) ; ?>
		<span class="screen-reader-text">Dismiss this notice.</span>
		<a href="<?php echo $dismiss_url ; ?>" class="litespeed-notice-dismiss">X</a>
	</div>
</div>
