<?php
namespace LiteSpeed ;
defined( 'WPINC' ) || exit ;
?>

<?php $this->form_action( Router::ACTION_LOG, Debug2::TYPE_BETA_TEST ) ; ?>

	<h3 class="litespeed-title"><?php echo __( 'Try GitHub Version', 'litespeed-cache' ) ; ?></h3>

	<input type="text" name="<?php echo Debug2::BETA_TEST_URL; ?>" class="litespeed-input-long">

	<div class="litespeed-desc">Example: https://github.com/litespeedtech/lscache_wp/commit/253715525b1708c25f73460635f7eaf152448821</div>

	<button type="submit" class="button button-primary"><?php echo __('Upgrade', 'litespeed-cache'); ?></button>
</form>
