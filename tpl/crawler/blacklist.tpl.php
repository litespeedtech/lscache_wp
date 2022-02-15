<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$crawler_summary = Crawler::get_summary();

$__map = Crawler_Map::cls();

$list = $__map->list_blacklist( 30 );
$count = $__map->count_blacklist();
$pagination = Utility::pagination( $count, 30 );

?>
<p class="litespeed-right">
<a href="<?php echo Utility::build_url( Router::ACTION_CRAWLER, Crawler::TYPE_BLACKLIST_EMPTY ); ?>" class="button litespeed-btn-warning" data-litespeed-cfm="<?php echo __( 'Are you sure to delete all existing blocklist items?', 'litespeed-cache' ) ; ?>" >
	<?php echo __( 'Empty blocklist', 'litespeed-cache' ); ?>
</a>
</p>

<h3 class="litespeed-title">
	<?php echo __( 'Blocklist', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/crawler/#blacklist-tab' ); ?>
</h3>

<?php echo __( 'Total', 'litespeed-cache' ) . ': ' . $count; ?>

<?php echo $pagination; ?>
<table class="wp-list-table widefat striped">
	<thead><tr >
		<th scope="col">#</th>
		<th scope="col"><?php echo __( 'URL', 'litespeed-cache' ); ?></th>
		<th scope="col"><?php echo __( 'Status', 'litespeed-cache' ); ?></th>
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
				<?php echo Crawler::cls()->display_status( $v[ 'res' ], $v[ 'reason' ] ); ?>
			</td>
			<td>
				<a href="<?php echo Utility::build_url( Router::ACTION_CRAWLER, Crawler::TYPE_BLACKLIST_DEL, false, null, array( 'id' => $v[ 'id' ] ) ); ?>" class="button button-secondary"><?php echo __( 'Remove from Blocklist', 'litespeed-cache' ); ?></a>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php echo $pagination; ?>

<p>
	<i class="litespeed-dot litespeed-bg-default"></i> = <?php echo __( 'Not blocklisted', 'litespeed-cache' ); ?><br>
	<i class="litespeed-dot litespeed-bg-warning"></i> = <?php echo __( 'Blocklisted due to not cacheable', 'litespeed-cache' ); ?><br>
	<i class="litespeed-dot litespeed-bg-danger"></i> = <?php echo __( 'Blocklisted', 'litespeed-cache' ); ?><br>
</p>
