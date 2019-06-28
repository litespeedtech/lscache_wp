<?php
defined( 'WPINC' ) || exit ;
?>

<h3 class="litespeed-title"><?php echo __('Beta Test', 'litespeed-cache'); ?></h3>

<div class="litespeed-panel-wrapper">
	<form method="post" action="admin.php?page=lscache-debug">
		<?php $this->form_action( LiteSpeed_Cache::ACTION_LOG, LiteSpeed_Cache_Log::TYPE_BETA_TEST ) ; ?>

		<h3 class="litespeed-title"><?php echo __( 'Try GitHub Version', 'litespeed-cache' ) ; ?></h3>

		<input type="text" name="<?php echo LiteSpeed_Cache_Log::BETA_TEST_URL; ?>" class="litespeed-input-long">

		<div class="litespeed-desc">Example: https://github.com/litespeedtech/lscache_wp/commit/253715525b1708c25f73460635f7eaf152448821</div>

		<button type="submit" class="litespeed-btn-primary"><?php echo __('Upgrade', 'litespeed-cache'); ?></button>
	</form>


</div>
