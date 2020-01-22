<?php
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

?>

<div class="litespeed-wrap notice notice-success litespeed-banner-promo-full">

	<div class="litespeed-banner-promo-content">
		<h3 class="litespeed-banner-title litespeed-top15"><?php echo __( 'Congratulations! You just unlocked a promotion from QUIC.cloud!', 'litespeed-cache' ) ; ?></h3>
		<div class="litespeed-banner-description">
			<div class="litespeed-banner-description-padding-right-15">
				<p class="litespeed-banner-desciption-content">
					<?php echo $this->_summary[ 'promo' ][ 0 ]; ?>
				</p>
			</div>
		</div>
	</div>

	<div>
		<?php $dismiss_url = Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_CLEAR_PROMO ) ; ?>
		<span class="screen-reader-text">Dismiss this notice.</span>
		<a href="<?php echo $dismiss_url ; ?>" class="litespeed-notice-dismiss">X</a>
	</div>
</div>
