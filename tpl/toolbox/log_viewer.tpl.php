<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;
?>

<h3 class="litespeed-title">
	<?php echo __('Debug Log', 'litespeed-cache'); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/toolbox/#log-view-tab' ); ?>

	<a href="<?php echo Utility::build_url( Router::ACTION_DEBUG2, Debug2::TYPE_CLEAR_LOG ); ?>" class="button button-primary" litespeed-accesskey='D'>
		<?php echo __( 'Clear Log', 'litespeed-cache' ); ?>
	</a>
</h3>


<?php
	$file = LSCWP_CONTENT_DIR . '/debug.log';
	$lines = File::count_lines( $file );
	$start = $lines > 1000 ? $lines - 1000 : 0;
	$logs = File::read( $file, $start );
	$logs = $logs ? implode( "\n", $logs ) : '';

	echo nl2br( htmlspecialchars( $logs ) );
?>


	<a href="<?php echo Utility::build_url( Router::ACTION_DEBUG2, Debug2::TYPE_CLEAR_LOG ); ?>" class="button button-primary">
		<?php echo __( 'Clear Log', 'litespeed-cache' ); ?>
	</a>
