<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;
?>

<h3 class="litespeed-title-short">
	<?php echo __( 'Cache Control Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cache/' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>
	<tr>
		<th><?php echo __( 'Network Enable Cache', 'litespeed-cache' ); ?></th>
		<td>
			<?php $this->build_switch( Base::O_CACHE ); ?>
			<div class="litespeed-desc">
				<?php echo __('Enabling LiteSpeed Cache for WordPress here enables the cache for the network.', 'litespeed-cache'); ?><br />
				<?php echo __('It is <b>STRONGLY</b> recommend that the compatibility with other plugins on a single/few sites is tested first.', 'litespeed-cache'); ?>
				<?php echo __('This is to ensure compatibility prior to enabling the cache for all sites.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

<?php
require LSCWP_DIR . 'tpl/cache/settings_inc.cache_favicon.tpl.php';
require LSCWP_DIR . 'tpl/cache/settings_inc.cache_resources.tpl.php';
require LSCWP_DIR . 'tpl/cache/settings_inc.cache_mobile.tpl.php';
require LSCWP_DIR . 'tpl/cache/settings_inc.cache_dropquery.tpl.php';
?>

</tbody></table>

