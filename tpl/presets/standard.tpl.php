<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$presets = array();

$presets['essentials'] = array(
	'title' => __( 'Essentials', 'litespeed-cache' ),
	'body' => array(
		__( 'Default Cache', 'litespeed-cache' ),
		__( 'Higher TTL', 'litespeed-cache' ),
		__( 'Browser Cache', 'litespeed-cache' )
	),
	'footer' => array(
		__( 'This no-risk preset is appropriate for all websites. Good for new users, simple websites, or cache-oriented development.', 'litespeed-cache' ),
		__( 'A Domain Key is not required to use this preset. Only basic caching features are enabled.', 'litespeed-cache' )
	)
);

$presets['basic'] = array(
	'title' => __( 'Basic', 'litespeed-cache' ),
	'body' => array(
		__( 'Everything in Essentials, Plus', 'litespeed-cache' ),
		__( 'Image Optimization', 'litespeed-cache' ),
		__( 'Mobile Cache', 'litespeed-cache' )
	),
	'footer' => array(
		__( 'This low-risk preset introduces basic optimizations for speed and user experience. Appropriate for enthusiastic beginners.', 'litespeed-cache' ),
		__( 'A Domain Key is required to use this preset. Includes optimizations known to improve site score in page speed measurement tools.', 'litespeed-cache' )
	)
);

$presets['advanced'] = array(
	'title' => __( 'Advanced (Recommended)', 'litespeed-cache' ),
	'body' => array(
		__( 'Everything in Basic, Plus', 'litespeed-cache' ),
		__( 'Guest Mode and Guest Optimization', 'litespeed-cache' ),
		__( 'CSS, JS and HTML Minification', 'litespeed-cache' ),
		__( 'Font Display Optimization', 'litespeed-cache' ),
		__( 'JS Defer for both external and inline JS', 'litespeed-cache' ),
		__( 'DNS Prefetch for static files', 'litespeed-cache' ),
		__( 'Gravatar Cache', 'litespeed-cache' ),
		__( 'Remove Query Strings from Static Files', 'litespeed-cache' ),
		__( 'Remove WordPress Emoji', 'litespeed-cache' ),
		__( 'Remove Noscript Tags', 'litespeed-cache' )
	),
	'footer' => array(
		__( 'This preset is good for most websites, and is unlikely to cause conflicts. Any CSS or JS conflicts may be resolved with Page Optimization > Tuning tools.', 'litespeed-cache' ),
		__( 'A Domain Key is required to use this preset. Includes many optimizations known to improve page speed scores.', 'litespeed-cache' )
	)
);

$presets['aggressive'] = array(
	'title' => __( 'Aggressive', 'litespeed-cache' ),
	'body' => array(
		__( 'Everything in Advanced, Plus', 'litespeed-cache' ),
		__( 'CSS & JS Combine', 'litespeed-cache' ),
		__( 'Asynchronous CSS Loading with Critical CSS', 'litespeed-cache' ),
		__( 'Removed Unused CSS for Users', 'litespeed-cache' ),
		__( 'Lazy Load for Iframes', 'litespeed-cache' )
	),
	'footer' => array(
		__( 'This preset might work out of the box for some websites, but be sure to test! Some CSS or JS exclusions may be necessary in Page Optimization > Tuning.', 'litespeed-cache' ),
		__( 'A Domain Key is required to use this preset. Includes many optimizations known to improve page speed scores.', 'litespeed-cache' )
	)
);

$presets['extreme'] = array(
	'title' => __( 'Extreme', 'litespeed-cache' ),
	'body' => array(
		__( 'Everything in Aggressive, Plus', 'litespeed-cache' ),
		__( 'Lazy Load for Images', 'litespeed-cache' ),
		__( 'Viewport Image Generation', 'litespeed-cache' ),
		__( 'JS Delayed', 'litespeed-cache' ),
		__( 'Inline JS added to Combine', 'litespeed-cache' ),
		__( 'Inline CSS added to Combine', 'litespeed-cache' )
	),
	'footer' => array(
		__( 'This preset almost certainly will require testing and exclusions for some CSS, JS and Lazy Loaded images. Pay special attention to logos, or HTML-based slider images.', 'litespeed-cache' ),
		__( 'A Domain Key is required to use this preset. Enables the maximum level of optimizations for improved page speed scores.', 'litespeed-cache' )
	)
);

?>

<h3 class="litespeed-title-short">
	<?php _e( 'LiteSpeed Cache Standard Presets', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/presets/#standard-tab' ); ?>
</h3>

<p><?php _e( 'Use an official LiteSpeed-designed Preset to configure your site in one click. Try no-risk caching essentials, extreme optimization, or something in between.', 'litespeed-cache' ); ?></p>

<div class="litespeed-comparison-cards">
	<?php
	foreach ( array_keys( $presets ) as $name ) :
		$title = $presets[ $name ]['title'];
		$recommend = 'advanced' === $name;
		$card_class = $recommend ? 'litespeed-comparison-card-rec' : '';
		$button_class = $recommend ? 'button-primary' : 'button-secondary';
	?>
	<div class="litespeed-comparison-card postbox <?php echo $card_class; ?>">
		<div class="litespeed-card-content">
			<div class="litespeed-card-header">
				<h3 class="litespeed-h3">
					<?php echo esc_html( $title ); ?>
				</h3>
			</div>
			<div class="litespeed-card-body">
				<ul>
					<?php foreach ( $presets[ $name ]['body'] as $line ) : ?>
					<li><?php echo esc_html( $line ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="litespeed-card-footer">
				<h4><?php _e( 'Who should use this preset?', 'litespeed-cache' ); ?></h4>
				<?php foreach ( $presets[ $name ]['footer'] as $line ) : ?>
				<p><?php echo esc_html( $line ); ?></p>
				<?php endforeach; ?>
			</div>
		</div>
		<div class="litespeed-card-action">
			<a
				href="<?php echo Utility::build_url( Router::ACTION_PRESET, Preset::TYPE_APPLY, false, null, array( 'preset' => $name ) ); ?>"
				class="button <?php echo $button_class; ?>"
				data-litespeed-cfm="<?php printf( __( 'This will back up your current settings and replace them with the %1$s preset settings. Do you want to continue?', 'litespeed-cache' ), $title ); ?>"
			>
				<?php _e( 'Apply Preset', 'litespeed-cache' ); ?>
			</a>
		</div>
	</div>
	<?php endforeach; ?>
</div>

<?php
$summary = Preset::get_summary();
$backups = array();
foreach ( Preset::get_backups() as $backup ) {
	$backup = explode( '-', $backup );
	if ( empty( $backup[1] ) ) {
		continue;
	}
	$timestamp = $backup[1];
	$time = trim( Utility::readable_time( $timestamp ) );
	$name = empty( $backup[3] ) ? null : $backup[3];
	$title = empty( $presets[ $name ]['title'] ) ? $name : $presets[ $name ]['title'];
	$title = null === $title ? __( 'unknown', 'litespeed-cache' ) : $title;
	$backups[] = array(
		'timestamp' => $timestamp,
		'time' => $time,
		'title' => $title
	);
}

if ( ! empty( $summary['preset'] ) || ! empty( $backups ) ) :
?>
<h3 class="litespeed-title-short">
	<?php _e( 'History', 'litespeed-cache' ); ?>
</h3>
<?php endif; ?>

<?php if ( ! empty( $summary['preset'] ) ) : ?>
<p>
	<?php
	$name = strtolower( $summary['preset'] );
	$time = trim( Utility::readable_time( $summary['preset_timestamp'] ) );
	if ( 'error' === $name ) {
		printf( __( 'Error: Failed to apply the settings %1$s', 'litespeed-cache' ), $time );
	} elseif ( 'backup' === $name ) {
		printf( __( 'Restored backup settings %1$s', 'litespeed-cache' ), $time );
	} else {
		printf(
			__( 'Applied the %1$s preset %2$s', 'litespeed-cache' ),
			'<strong>' . esc_html( $presets[ $name ]['title'] ) . '</strong>',
			$time
		);
	}
	?>
</p>
<?php endif; ?>

<?php foreach ( $backups as $backup ) : ?>
<p>
	<?php printf( __( 'Backup created %1$s before applying the %2$s preset', 'litespeed-cache' ), $backup['time'], $backup['title'] ); ?>
	<a
		href="<?php echo Utility::build_url( Router::ACTION_PRESET, Preset::TYPE_RESTORE, false, null, array( 'timestamp' => $backup['timestamp'] ) ); ?>"
		class="litespeed-left10"
		data-litespeed-cfm="<?php printf( __( 'This will restore the backup settings created %1$s before applying the %2$s preset. Any changes made since then will be lost. Do you want to continue?', 'litespeed-cache' ), $backup['time'], $backup['title'] ); ?>"
	>
		<?php _e( 'Restore Settings', 'litespeed-cache' ); ?>
	</a>
</p>
<?php
endforeach;
