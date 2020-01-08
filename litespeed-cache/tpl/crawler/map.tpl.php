<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$crawler_summary = Crawler::get_summary();

$__map = Crawler_Map::get_instance();

$list = $__map->list( 30 );
$pagination = Utility::pagination( $__map->count(), 30 );

?>
<h3 class="litespeed-title"><?php echo __( 'Crawler Stats', 'litespeed-cache' ); ?></h3>

Sitemap Total: <?php echo $__map->count(); ?> | Hit: <?php echo $__map->count( Crawler_Map::BM_HIT ); ?>  | Miss: <?php echo $__map->count( Crawler_Map::BM_MISS ); ?> | Blacklist: <?php echo $__map->count( Crawler_Map::BM_BLACKLIST ); ?>
<br />

<p>
<a href="<?php echo Utility::build_url( Router::ACTION_CRAWLER, Crawler::TYPE_REFRESH_MAP ); ?>" class="button button-primary litespeed-right10">
	<?php echo __( 'Refresh Crawler Map', 'litespeed-cache' ); ?>
</a>
</p>

<div class="litespeed-desc">
	<p><?php echo __('All Urls which returned no-cache tags will be added here, after the initial crawling.', 'litespeed-cache'); ?></p>
</div>
<p>
	<?php
		if ( ! empty( $crawler_summary[ 'sitemap_time' ] ) ) {
			echo sprintf( __( 'Generated at %s', 'litespeed-cache' ), Utility::readable_time( $crawler_summary[ 'sitemap_time' ] ) );
		}
	?>
</p>

<h3 class="litespeed-title"><?php echo __( 'Sitemap List', 'litespeed-cache' ); ?></h3>

<?php echo $pagination; ?>
<table class="wp-list-table widefat striped">
	<thead><tr >
		<th scope="col">#</th>
		<th scope="col"><?php echo __('URL', 'litespeed-cache'); ?></th>
		<th scope="col"><?php echo __('Status', 'litespeed-cache'); ?></th>
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
				<?php echo Crawler::get_instance()->display_status( $v[ 'status' ] ); ?>
			</td>
			<td>
				<a href="<?php echo Utility::build_url( Router::ACTION_CRAWLER, Crawler::TYPE_ADD_BLACKLIST ); ?>" class="button button-primary"><?php echo __( 'Add to Blacklist', 'litespeed-cache' ); ?></a>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php echo $pagination; ?>