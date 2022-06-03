<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$log_list = [
	'debug' => [
		'label' => __( 'Debug Log', 'litespeed-cache' ),
		'accesskey' => 'A',
	],
	'debug.purge' => [
		'label' => __( 'Purge Log', 'litespeed-cache' ),
		'accesskey' => 'B',
	],
	'crawler' => [
		'label' => __( 'Crawler Log', 'litespeed-cache' ),
		'accesskey' => 'C',
	],
];

foreach ( $log_list as $log => $meta ) :
?>

<div class="litespeed-log-view-wrap">
	<h3 class="litespeed-title">
		<?php echo $meta['label']; ?>
		<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/toolbox/#log-view-tab' ); ?>

		<a href="<?php echo Utility::build_url( Router::ACTION_DEBUG2, Debug2::TYPE_CLEAR_LOG ); ?>" class="button button-primary" litespeed-accesskey="D">
			<?php echo __( 'Clear Log', 'litespeed-cache' ); ?>
		</a>

		<div class="litespeed-log-tab-wrapper">
			<?php
				foreach ( $log_list as $inner_log => $inner_meta ) :
			?>
				<a href="<?php echo Utility::build_url( Router::ACTION_DEBUG2, Debug2::TYPE_CLEAR_LOG ); ?>" class="litespeed-log-tab button button-secondary" litespeed-accesskey="<?php echo $inner_meta['accesskey']; ?>">
					<?php echo $inner_meta['label']; ?>
				</a>
			<?php
				endforeach;
			?>
		</div>
	</h3>


	<div class="litespeed-log-body">
		<?php
			$file = LSCWP_CONTENT_DIR . '/' . $log . '.log';
			$lines = File::count_lines( $file );
			$start = $lines > 1000 ? $lines - 1000 : 0;
			$logs = File::read( $file, $start );
			$logs = $logs ? implode( "\n", $logs ) : '';

			echo nl2br( htmlspecialchars( $logs ) );
		?>
	</div>


	<a href="<?php echo Utility::build_url( Router::ACTION_DEBUG2, Debug2::TYPE_CLEAR_LOG ); ?>" class="button button-primary">
		<?php echo __( 'Clear Log', 'litespeed-cache' ); ?>
	</a>
</div>

<?php
endforeach;
