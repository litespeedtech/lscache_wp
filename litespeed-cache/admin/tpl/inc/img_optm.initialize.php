<?php
if ( ! defined( 'WPINC' ) ) {
	die ;
}

?>

	<div class="litespeed-width-7-10">
		<div class="litespeed-empty-space-large"></div>
			<div class="litespeed-text-center">
				<div>
					<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMG_OPTM, LiteSpeed_Cache_Img_Optm::TYPE_SYNC_DATA ) ; ?>" class="litespeed-text-malibu litespeed-btn-success litespeed-btn-large">
						<span class="dashicons dashicons-performance">
						</span>&nbsp;
						<?php echo __( 'Initialize Optimization', 'litespeed-cache' ) ; ?>
					</a>
				</div>
			</div>
		<div class="litespeed-empty-space-medium"></div>
		<div>
			<h2 Class="litespeed-title">
				<?php echo __( 'Notes', 'litespeed-cache' ) ; ?>:
			</h2>

			<?php include_once LSCWP_DIR . "admin/tpl/inc/img_optm.communicate_notify.php" ; ?>

			<?php include_once LSCWP_DIR . "admin/tpl/inc/api_key.php" ; ?>

			<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:image-optimization#image_optimization_in_litespeed_cache_for_wordpress" target="_blank"><?php echo __('Learn More', 'litespeed-cache') ; ?></a>

			<hr class="litespeed-hr-dotted">

		</div>
	</div>

	<div class="litespeed-width-3-10 litespeed-column-java">
		<?php include_once LSCWP_DIR . "admin/tpl/inc/img_optm.percentage_summary.php" ; ?>
	</div>
