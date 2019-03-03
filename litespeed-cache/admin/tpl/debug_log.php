<?php
if ( ! defined( 'WPINC' ) ) die ;

?>

<div class="wrap">
	<h1 class="litespeed-h1">
		<?php echo __( 'LiteSpeed Cache Debug Log Viewer', 'litespeed-cache' ) ; ?>
	</h1>
	<span class="litespeed-desc">
		v<?php echo LiteSpeed_Cache::PLUGIN_VERSION ; ?>
	</span>
	<hr class="wp-header-end">
</div>

<div class="wrap">
	<form method="post" action="admin.php?page=lscache-debug">
		<?php $this->form_action( LiteSpeed_Cache::ACTION_LOG, LiteSpeed_Cache_Log::TYPE_BETA_TEST ) ; ?>

		<h3 class="litespeed-title"><?php echo __( 'Try GitHub Version', 'litespeed-cache' ) ; ?></h3>

		<input type="text" name="<?php echo LiteSpeed_Cache_Log::BETA_TEST_URL; ?>" class="litespeed-input-long">

		<div class="litespeed-desc">Example: https://github.com/litespeedtech/lscache_wp/commit/253715525b1708c25f73460635f7eaf152448821</div>

		<button type="submit" class="litespeed-btn-primary"><?php echo __('Upgrade', 'litespeed-cache'); ?></button>
	</form>

	<?php

		$file = LSCWP_CONTENT_DIR . '/debug.log' ;
		$lines = Litespeed_File::count_lines( $file ) ;
		$start = $lines > 1000 ? $lines - 1000 : 0 ;
		$logs = Litespeed_File::read( $file, $start ) ;
		$logs = implode( "\n", $logs ) ;

		echo nl2br( htmlspecialchars( $logs ) ) ;

	?>

	<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_LOG, LiteSpeed_Cache_Log::TYPE_CLEAR_LOG ) ; ?>" class="litespeed-btn-success">
		<?php echo __( 'Clear Log', 'litespeed-cache' ) ; ?>
	</a>

</div>
