<?php
defined( 'WPINC' ) || exit ;
?>

<h3 class="litespeed-title"><?php echo __('Purge', 'litespeed-cache'); ?></h3>

<div class="litespeed-panel-wrapper">

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
