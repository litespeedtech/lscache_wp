<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;
?>

<h3 class="litespeed-title-short">
	<?php echo __( 'Cache Control Settings', 'litespeed-cache' ); ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:cache', false, 'litespeed-learn-more' ); ?>
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

	<tr>
		<th><?php echo __('Use Primary Site Configuration', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch( Base::NETWORK_O_USE_PRIMARY ); ?>
			<div class="litespeed-desc">
				<?php echo __("Check this option to use the primary site's configuration for all subsites.", 'litespeed-cache'); ?>
				<?php echo __('This will disable the settings page on all subsites.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

<?php
require LSCWP_DIR . 'tpl/cache/settings_inc.cache_favicon.tpl.php';
require LSCWP_DIR . 'tpl/cache/settings_inc.cache_resources.tpl.php';
require LSCWP_DIR . 'tpl/cache/settings_inc.cache_mobile.tpl.php';
?>

</tbody></table>

