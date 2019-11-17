<?php
namespace LiteSpeed ;
defined( 'WPINC' ) || exit ;
?>

<h3 class="litespeed-title">
	<?php echo __('Debug Log', 'litespeed-cache'); ?>

	<a href="<?php echo Utility::build_url( Core::ACTION_LOG, Log::TYPE_CLEAR_LOG ) ; ?>" class="button button-primary">
		<?php echo __( 'Clear Log', 'litespeed-cache' ) ; ?>
	</a>
</h3>


<?php
	$file = LSCWP_CONTENT_DIR . '/debug.log' ;
	$lines = File::count_lines( $file ) ;
	$start = $lines > 1000 ? $lines - 1000 : 0 ;
	$logs = File::read( $file, $start ) ;
	$logs = implode( "\n", $logs ) ;

	echo nl2br( htmlspecialchars( $logs ) ) ;
?>


