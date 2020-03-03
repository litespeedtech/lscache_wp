<?php
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

?>

<div class="litespeed-wrap notice notice-warning litespeed-banner-promo-full">
	<div class="litespeed-banner-promo-logo"></div>

	<div class="litespeed-banner-promo-content">
		<h3 class="litespeed-banner-title litespeed-top15"><?php echo __( 'LiteSpeed Cache', 'litespeed-cache' ) ; ?>: <?php echo __( 'New Developer Version Available!', 'litespeed-cache' ) ; ?></h3>
		<div class="litespeed-banner-description">
			<div class="litespeed-banner-description-padding-right-15">
				<p class="litespeed-banner-desciption-content">
					<?php echo sprintf( __( 'New developer version %s is available now.', 'litespeed-cache' ), 'v' . $this->_summary[ 'version.dev' ] ) ; ?>
				</p>
			</div>
			<div class="litespeed-row-flex litespeed-banner-description">
				<div class="litespeed-banner-description-padding-right-15">
				</div>
			</div>
		</div>
	</div>

</div>
