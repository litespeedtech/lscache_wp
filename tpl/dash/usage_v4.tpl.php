<?php
/**
 * QUIC.cloud usage boxes for the v4 usage format: optimization units and CDN bandwidth.
 *
 * Expects `$cloud_summary` from the including dashboard template.
 *
 * @package LiteSpeed
 * @since 7.9.4
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$usage_titles = [
	'optm' => __( 'Optimization', 'litespeed-cache' ),
	'cdn'  => __( 'CDN Bandwidth', 'litespeed-cache' ),
];
$sub_titles   = [
	'img_optm' => __( 'Image Optimization', 'litespeed-cache' ),
	'optimax'  => 'OptimaX',
];
// Each sub service keeps its own color slot; an unknown one uses the neutral slot 0.
$sub_colors = [
	'ccss'     => 1,
	'ucss'     => 2,
	'vpi'      => 3,
	'lqip'     => 4,
	'img_optm' => 5,
	'optimax'  => 6,
];

// The plan description is semicolon separated text, one item per line; it may carry bold tags, and the semicolon ending an HTML entity is not a separator.
$plan_lines = ! empty( $cloud_summary['usage.plan_desc'] ) && is_string( $cloud_summary['usage.plan_desc'] ) ? array_filter( array_map( 'trim', preg_split( '/&#?\w+;(*SKIP)(*F)|;/', $cloud_summary['usage.plan_desc'] ) ), 'strlen' ) : [];
?>
<?php if ( ! empty( $cloud_summary['usage.plan'] ) ) : ?>
	<?php // The plan leads the usage boxes: its name on the left, what it includes on the right. ?>
	<div class="postbox litespeed-postbox litespeed-postbox-plan">
		<div class="inside">
			<h3 class="litespeed-title"><?php esc_html_e( 'Plan', 'litespeed-cache' ); ?></h3>
			<div class="litespeed-flex-container litespeed-usage-plan">
				<div>
					<strong class="litespeed-primary"><?php echo esc_html( $cloud_summary['usage.plan'] ); ?></strong>
					<?php if ( ! empty( $cloud_summary['usage.cycle_end'] ) ) : ?>
						<p class="litespeed-desc"><?php echo esc_html( sprintf( __( 'Resets on %s', 'litespeed-cache' ), wp_date( get_option( 'date_format' ), (int) $cloud_summary['usage.cycle_end'] ) ) ); ?></p>
					<?php endif; ?>
				</div>
				<div>
					<?php foreach ( $plan_lines as $plan_line ) : ?>
						<p><?php echo wp_kses( $plan_line, [ 'b' => [], 'strong' => [] ] ); ?></p>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>
<?php
foreach ( $usage_titles as $svc => $svc_title ) :
	$usage = ! empty( $cloud_summary[ 'usage.' . $svc ] ) && is_array( $cloud_summary[ 'usage.' . $svc ] ) ? $cloud_summary[ 'usage.' . $svc ] : [];
	$nums  = [];
	$shown = [];
	foreach ( [ 'used', 'quota', 'pag_used', 'pag_bal', 'overage_used', 'overage_cap' ] as $field ) {
		$nums[ $field ] = isset( $usage[ $field ] ) ? (int) $usage[ $field ] : 0;
		if ( -1 === $nums[ $field ] ) {
			$shown[ $field ] = '∞';
		} elseif ( 'cdn' !== $svc ) {
			$shown[ $field ] = $nums[ $field ];
		} else {
			// CDN traffic is reported in units of 100MB.
			$gb              = $nums[ $field ] * 100 / 1024;
			$shown[ $field ] = $gb >= 1024 ? round( $gb / 1024, 2 ) . ' TB' : round( $gb, 2 ) . ' GB';
		}
	}

	// The pie only reflects plan units; PAYG and overage are listed separately.
	$finished_percentage = $nums['quota'] > 0 ? (int) floor( $nums['used'] * 100 / $nums['quota'] ) : 0;
	$percentage_bg       = 'success';
	if ( $finished_percentage > 95 ) {
		$percentage_bg = 'danger';
	} elseif ( $finished_percentage > 85 ) {
		$percentage_bg = 'warning';
	}

	// An unlimited quota has no percentage: the ring shows the used amount itself (the unit is in the text beside it).
	$pie_label = false;
	if ( $finished_percentage > 100 ) {
		// Over quota: the ring stays full while the center keeps the real percentage.
		$pie_label           = $finished_percentage . '%';
		$finished_percentage = 100;
	} elseif ( -1 === $nums['quota'] ) {
		$finished_percentage = 100;
		$pie_label           = (float) $shown['used'];
		$pie_label           = (string) round( $pie_label, $pie_label < 100 ? 1 : 0 );
	}
	?>
	<div class="postbox litespeed-postbox">
		<div class="inside">
			<h3 class="litespeed-title"><?php echo esc_html( $svc_title ); ?></h3>
			<div class="litespeed-flex-container">
				<div class="litespeed-icon-vertical-middle litespeed-pie-<?php echo esc_attr( $percentage_bg ); ?>">
					<?php echo wp_kses( GUI::pie( $finished_percentage, 60, false, false, false, $pie_label ), GUI::allowed_svg_tags() ); ?>
				</div>
				<div>
					<div class="litespeed-dashboard-stats">
						<h3><?php esc_html_e( 'Usage', 'litespeed-cache' ); ?></h3>
						<p>
							<strong><?php echo esc_html( $shown['used'] ); ?></strong>
							<?php if ( 0 !== $nums['quota'] ) : ?>
								<span class="litespeed-desc"> / <?php echo esc_html( $shown['quota'] ); ?></span>
							<?php endif; ?>
						</p>
					</div>
				</div>
			</div>
			<?php if ( 0 !== $nums['pag_bal'] || $nums['pag_used'] > 0 ) : ?>
				<p class="litespeed-dashboard-stats-payg">
					<?php esc_html_e( 'PAYG Balance', 'litespeed-cache' ); ?>: <strong><?php echo esc_html( $shown['pag_bal'] ); ?></strong>
					<button class="litespeed-info-button" data-balloon-pos="up" aria-label="<?php echo esc_attr( sprintf( __( 'PAYG used this month: %s. PAYG balance and usage not included in above quota calculation.', 'litespeed-cache' ), $shown['pag_used'] ) ); ?>">
						<span class="dashicons dashicons-info"></span>
						<span class="screen-reader-text"><?php esc_html_e( 'Pay as You Go Usage Statistics', 'litespeed-cache' ); ?></span>
					</button>
				</p>
			<?php endif; ?>
			<?php if ( 0 !== $nums['overage_cap'] || $nums['overage_used'] > 0 ) : ?>
				<p class="litespeed-dashboard-stats-total">
					<?php esc_html_e( 'Overage', 'litespeed-cache' ); ?>: <strong><?php echo esc_html( $shown['overage_used'] ); ?> / <?php echo esc_html( $shown['overage_cap'] ); ?></strong>
				</p>
			<?php endif; ?>
			<?php
			// Sub services without units are left out.
			$sub_list = [];
			if ( 'optm' === $svc && ! empty( $usage['sub_svc'] ) && is_array( $usage['sub_svc'] ) ) {
				foreach ( $usage['sub_svc'] as $sub_svc => $sub_usage ) {
					if ( isset( $sub_usage['units'] ) && (int) $sub_usage['units'] > 0 ) {
						$sub_list[ $sub_svc ] = $sub_usage;
					}
				}
			}
			?>
			<?php if ( $sub_list ) : ?>
				<?php // One stacked bar of the units per sub service with a row of labels below; the request count is the label's hover text. ?>
				<div class="litespeed-usage-sub">
					<div class="litespeed-usage-bar">
						<?php foreach ( $sub_list as $sub_svc => $sub_usage ) : ?>
							<i class="litespeed-usage-c<?php echo isset( $sub_colors[ $sub_svc ] ) ? (int) $sub_colors[ $sub_svc ] : 0; ?>" style="flex-grow:<?php echo (int) $sub_usage['units']; ?>"></i>
						<?php endforeach; ?>
					</div>
					<?php foreach ( $sub_list as $sub_svc => $sub_usage ) : ?>
						<span class="litespeed-label-usage litespeed-usage-c<?php echo isset( $sub_colors[ $sub_svc ] ) ? (int) $sub_colors[ $sub_svc ] : 0; ?>" data-balloon-pos="up" aria-label="<?php echo esc_attr( sprintf( __( '%s requests', 'litespeed-cache' ), isset( $sub_usage['req'] ) ? (int) $sub_usage['req'] : 0 ) ); ?>">
							<?php echo esc_html( isset( $sub_titles[ $sub_svc ] ) ? $sub_titles[ $sub_svc ] : strtoupper( $sub_svc ) ); ?>
							<strong><?php echo (int) $sub_usage['units']; ?></strong>
						</span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
<?php endforeach; ?>
