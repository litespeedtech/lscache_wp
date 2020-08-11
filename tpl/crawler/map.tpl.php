<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$crawler_summary = Crawler::get_summary();

$__map = Crawler_Map::get_instance();

$list = $__map->list_map( 30 );
$count = $__map->count_map();
$pagination = Utility::pagination( $count, 30 );

?>
<p class="litespeed-right">
<a href="<?php echo Utility::build_url( Router::ACTION_CRAWLER, Crawler::TYPE_EMPTY ); ?>" class="button litespeed-btn-warning">
	<?php echo __( 'Clean Crawler Map', 'litespeed-cache' ); ?>
</a>

<a href="<?php echo Utility::build_url( Router::ACTION_CRAWLER, Crawler::TYPE_REFRESH_MAP ); ?>" class="button button-secondary">
	<?php echo __( 'Refresh Crawler Map', 'litespeed-cache' ); ?>
</a>
</p>

<p>
	<?php
		if ( ! empty( $crawler_summary[ 'sitemap_time' ] ) ) {
			echo sprintf( __( 'Generated at %s', 'litespeed-cache' ), Utility::readable_time( $crawler_summary[ 'sitemap_time' ] ) );
		}
	?>
</p>

<h3 class="litespeed-title">
	<?php echo __( 'Sitemap List', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/crawler/#map-tab' ); ?>
</h3>

<?php echo __( 'Sitemap Total', 'litespeed-cache' ) . ': ' . $count; ?>

<?php echo $pagination; ?>
<table class="wp-list-table widefat striped">
	<thead><tr >
		<th scope="col">#</th>
		<th scope="col"><?php echo __( 'URL', 'litespeed-cache' ); ?></th>
		<th scope="col"><?php echo __( 'Crawler Status', 'litespeed-cache' ); ?></th>
		<th scope="col"><?php echo __( 'Operation', 'litespeed-cache' ); ?></th>
	</tr></thead>
	<tbody>
		<?php foreach ( $list as $i => $v ) : ?>
		<tr>
			<td><?php echo $i + 1; ?></td>
			<td>
				<?php echo $v[ 'url' ]; ?>
			</td>
			<td>
				<?php echo Crawler::get_instance()->display_status( $v[ 'res' ], $v[ 'reason' ] ); ?>
			</td>
			<td>
				<a href="<?php echo Utility::build_url( Router::ACTION_CRAWLER, Crawler::TYPE_BLACKLIST_ADD, false, null, array( 'id' => $v[ 'id' ] ) ); ?>" class="button button-secondary"><?php echo __( 'Add to Blacklist', 'litespeed-cache' ); ?></a>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php echo $pagination; ?>

<p>
	<i class="litespeed-dot litespeed-bg-success"></i> = <?php echo __( 'Cache Hit', 'litespeed-cache' ); ?><br>
	<i class="litespeed-dot litespeed-bg-primary"></i> = <?php echo __( 'Cache Miss', 'litespeed-cache' ); ?><br>
	<i class="litespeed-dot litespeed-bg-warning"></i> = <?php echo __( 'Blacklisted due to not cacheable', 'litespeed-cache' ); ?><br>
	<i class="litespeed-dot litespeed-bg-danger"></i> = <?php echo __( 'Blacklisted', 'litespeed-cache' ); ?><br>
</p>