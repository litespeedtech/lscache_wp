<?php
/**
 * LiteSpeed Cache Log Viewer
 *
 * Renders the log viewer interface for LiteSpeed Cache, displaying debug, purge, and crawler logs with options to copy or clear logs.
 *
 * @package LiteSpeed
 * @since 4.7
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$logs = array(
	array(
		'name'      => 'debug',
		'label'     => esc_html__( 'Debug Log', 'litespeed-cache' ),
		'accesskey' => 'A',
	),
	array(
		'name'      => 'purge',
		'label'     => esc_html__( 'Purge Log', 'litespeed-cache' ),
		'accesskey' => 'B',
	),
	array(
		'name'      => 'crawler',
		'label'     => esc_html__( 'Crawler Log', 'litespeed-cache' ),
		'accesskey' => 'C',
	),
);
?>

<h3 class="litespeed-title">
	<?php esc_html_e( 'LiteSpeed Logs', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/toolbox/#log-view-tab' ); ?>
</h3>

<div class="litespeed-log-subnav-wrapper">
	<?php foreach ( $logs as $log ) : ?>
		<a href="#<?php echo esc_attr( $log['name'] ); ?>_log" class="button button-secondary" data-litespeed-subtab="<?php echo esc_attr( $log['name'] ); ?>_log" litespeed-accesskey="<?php echo esc_attr( $log['accesskey'] ); ?>">
			<?php echo esc_html( $log['label'] ); ?>
		</a>
	<?php endforeach; ?>
	<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_DEBUG2, Debug2::TYPE_CLEAR_LOG ) ); ?>" class="button button-primary" litespeed-accesskey="D">
		<?php esc_html_e( 'Clear Logs', 'litespeed-cache' ); ?>
	</a>
</div>

<?php
foreach ( $logs as $log ) :
	$file      = $this->cls( 'Debug2' )->path( $log['name'] );
	$lines     = File::count_lines( $file );
	$max_lines = apply_filters( 'litespeed_debug_show_max_lines', 1000 );
	$start     = $lines > $max_lines ? $lines - $max_lines : 0;
	$lines     = File::read( $file, $start );
	$lines     = $lines ? trim( implode( "\n", $lines ) ) : '';

	$log_body_id = 'litespeed-log-' . esc_attr( $log['name'] );
?>
	<div class="litespeed-log-view-wrapper" data-litespeed-sublayout="<?php echo esc_attr( $log['name'] ); ?>_log">
		<h3 class="litespeed-title">
			<?php echo esc_html( $log['label'] ); ?>
			<a href="#<?php echo esc_attr( $log['name'] ); ?>_log" class="button litespeed-info-button litespeed-wrap" onClick="litespeed_copy_to_clipboard('<?php echo esc_js( $log_body_id ); ?>', this)" aria-label="<?php esc_attr_e( 'Click to copy', 'litespeed-cache' ); ?>" data-balloon-pos="down">
				<?php esc_html_e( 'Copy Log', 'litespeed-cache' ); ?>
			</a>
		</h3>
		<div class="litespeed-log-body" id="<?php echo esc_attr( $log_body_id ); ?>">
			<?php echo nl2br( esc_html( $lines ) ); ?>
		</div>
	</div>
<?php endforeach; ?>

<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_DEBUG2, Debug2::TYPE_CLEAR_LOG ) ); ?>" class="button button-primary">
	<?php esc_html_e( 'Clear Logs', 'litespeed-cache' ); ?>
</a>