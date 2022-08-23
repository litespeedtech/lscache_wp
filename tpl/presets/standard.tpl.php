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
		__( 'This preset has just the most basic number of features enabled, and is good for any website.', 'litespeed-cache' ),
		__( 'This preset can be utilized without a Domain key, Good for cache-oriented development aswell.', 'litespeed-cache' )
	)
);

$presets['basic'] = array(
	'title' => __( 'Basic', 'litespeed-cache' ),
	'body' => array(
		__( 'Everything in Essentials, Plus', 'litespeed-cache' ),
		__( 'Guest Mode and Optimization', 'litespeed-cache' ),
		__( 'Image Optimization', 'litespeed-cache' ),
		__( 'Mobile Cache', 'litespeed-cache' )
	),
	'footer' => array(
		__( 'This preset enables the very essentials, plus some basic optimizations which will improve Google Pagespeed Score, and also not hamper user experience on website.', 'litespeed-cache' ),
		__( 'It is good for any website, with a LSCWP Domain key.', 'litespeed-cache' )
	)
);

$presets['advanced'] = array(
	'title' => __( 'Advanced', 'litespeed-cache' ),
	'body' => array(
		__( 'Everything in Basic, Plus', 'litespeed-cache' ),
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
		__( 'This preset is the most recommended one, and is enabled with most non-conflicting options, and is good for most websites.' ),
		__( 'Domain Key must be set to use this preset. Also improves your Pagespeed Score.', 'litespeed-cache' )
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
		__( 'This preset while might work out of box in some websites, but might require some excludes in the Page Optimization >> Tuning section. ' ),
		__( 'Domain Key must be set to use this preset. Also improves your Pagespeed Score.', 'litespeed-cache' )
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
		__( 'This preset will most likely require the website owner to work with Lazy Load Exclusions specially for images such as Logo, or HTML based Slider Images on Top.' ),
		__( 'Domain Key must be set to use this preset. Also improves your Pagespeed Score.', 'litespeed-cache' )
	)
);

?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'LiteSpeed Cache Standard Presets', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/presets/#standard-tab' ); ?>
</h3>

<p><?php esc_html_e( 'LiteSpeed Cache Presets can be used for easily optimizing any website using LiteSpeed Cache.', 'litespeed-cache' ); ?></p>

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
				<h4><?php esc_html_e( 'For whom is this preset good?', 'litespeed-cache' ); ?></h4>
				<?php foreach ( $presets[ $name ]['footer'] as $line ) : ?>
				<p><?php echo esc_html( $line ); ?></p>
				<?php endforeach; ?>
			</div>
		</div>
		<div class="litespeed-card-action">
			<a
				href="<?php echo Utility::build_url( Router::ACTION_PRESET, Preset::TYPE_APPLY, false, null, array( 'preset' => $name ) ); ?>"
				class="button <?php echo $button_class; ?>"
				data-litespeed-cfm="<?php printf( esc_html__( 'This will replace your current settings and apply the %1$s preset. Do you want to continue?', 'litespeed-cache' ), $title ); ?>"
			>
				<?php esc_html_e( 'Apply Preset', 'litespeed-cache' ); ?>
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
	<?php esc_html_e( 'History', 'litespeed-cache' ); ?>
</h3>
<?php endif; ?>

<?php if ( ! empty( $summary['preset'] ) ) : ?>
<p>
	<?php
	$name = strtolower( $summary['preset'] );
	$time = trim( Utility::readable_time( $summary['preset_timestamp'] ) );
	if ( 'error' === $name ) {
		printf( esc_html__( 'Error: Failed to apply the settings %1$s', 'litespeed-cache' ), $time );
	} elseif ( 'backup' === $name ) {
		printf( esc_html__( 'Restored backup settings %1$s', 'litespeed-cache' ), $time );
	} else {
		printf(
			esc_html__( 'Applied the %1$s preset %2$s', 'litespeed-cache' ),
			'<strong>' . esc_html( $presets[ $name ]['title'] ) . '</strong>',
			$time
		);
	}
	?>
</p>
<?php endif; ?>

<?php foreach ( $backups as $backup ) : ?>
<p>
	<?php printf( esc_html__( 'Backup created %1$s before applying the %2$s preset', 'litespeed-cache' ), $backup['time'], $backup['title'] ); ?>
	<a
		href="<?php echo Utility::build_url( Router::ACTION_PRESET, Preset::TYPE_RESTORE, false, null, array( 'timestamp' => $backup['timestamp'] ) ); ?>"
		class="button button-secondary"
		data-litespeed-cfm="<?php printf( esc_html__( 'This will restore the backup settings created %1$s before applying the %2$s preset. Any changes made since then will be lost. Do you want to continue?', 'litespeed-cache' ), $backup['time'], $backup['title'] ); ?>"
	>
		<?php esc_html_e( 'Restore Settings', 'litespeed-cache' ); ?>
	</a>
</p>
<?php
endforeach;
