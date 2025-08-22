<?php
/**
 * LiteSpeed Cache Crawler Blocklist
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$crawler_summary = Crawler::get_summary();
$__map           = Crawler_Map::cls();
$__admin_display = Admin_Display::cls();
$list            = $__map->list_blacklist( 30 );
$count           = $__map->count_blacklist();
$pagination      = Utility::pagination( $count, 30 );
?>

<p class="litespeed-right">
	<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_CRAWLER, Crawler::TYPE_BLACKLIST_EMPTY ) ); ?>" class="button litespeed-btn-warning" data-litespeed-cfm="<?php esc_attr_e( 'Are you sure to delete all existing blocklist items?', 'litespeed-cache' ); ?>">
		<?php esc_html_e( 'Empty blocklist', 'litespeed-cache' ); ?>
	</a>
</p>

<h3 class="litespeed-title">
	<?php esc_html_e( 'Blocklist', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/crawler/#blocklist-tab' ); ?>
</h3>

<?php echo esc_html__( 'Total', 'litespeed-cache' ) . ': ' . esc_html( $count ); ?>

<?php echo wp_kses_post( $pagination ); ?>

<div class="litespeed-table-responsive">
	<table class="wp-list-table widefat striped">
		<thead>
			<tr>
				<th scope="col">#</th>
				<th scope="col"><?php esc_html_e( 'URL', 'litespeed-cache' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Status', 'litespeed-cache' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Operation', 'litespeed-cache' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $list as $i => $v ) : ?>
			<tr>
				<td><?php echo esc_html( $i + 1 ); ?></td>
				<td><?php echo esc_html( $v['url'] ); ?></td>
				<td><?php echo wp_kses_post( Crawler::cls()->display_status( $v['res'], $v['reason'] ) ); ?></td>
				<td>
					<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_CRAWLER, Crawler::TYPE_BLACKLIST_DEL, false, null, array( 'id' => $v['id'] ) ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Remove from Blocklist', 'litespeed-cache' ); ?>
					</a>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<?php echo wp_kses_post( $pagination ); ?>

<p>
	<span class="litespeed-success">
		<?php
		printf(
			esc_html__( 'API: PHP Constant %s available to disable blocklist.', 'litespeed-cache' ),
			'<code>LITESPEED_CRAWLER_DISABLE_BLOCKLIST</code>'
		);
		?>
	</span>
</p>
<p>
	<span class="litespeed-success">
		<?php
		printf(
			esc_html__( 'API: Filter %s available to disable blocklist.', 'litespeed-cache' ),
			'<code>add_filter( \'litespeed_crawler_disable_blocklist\', \'__return_true\' );</code>'
		);
		?>
	</span>
</p>
<?php $__admin_display->_check_overwritten( 'crawler-blocklist' ); ?>
<p>
	<i class="litespeed-dot litespeed-bg-default"></i> = <?php esc_html_e( 'Not blocklisted', 'litespeed-cache' ); ?><br>
	<i class="litespeed-dot litespeed-bg-warning"></i> = <?php esc_html_e( 'Blocklisted due to not cacheable', 'litespeed-cache' ); ?><br>
	<i class="litespeed-dot litespeed-bg-danger"></i> = <?php esc_html_e( 'Blocklisted', 'litespeed-cache' ); ?><br>
</p>
