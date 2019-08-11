<?php defined( 'WPINC' ) || exit ; ?>

<h3 class="litespeed-title-short">
	<?php echo __( 'General', 'litespeed-cache' ) ; ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:general', false, 'litespeed-learn-more' ) ; ?>
</h3>

<?php $this->cache_disabled_warning() ; ?>

<table class="wp-list-table striped form-table"><tbody>
	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_CACHE ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<div class="litespeed-switch">
				<?php $this->build_radio( $id, LiteSpeed_Cache_Config::VAL_OFF ) ; ?>
				<?php $this->build_radio( $id, LiteSpeed_Cache_Config::VAL_ON ) ; ?>
				<?php
					if ( is_multisite() ) {
						$this->build_radio( $id, LiteSpeed_Cache_Config::VAL_ON2, __( 'Use Network Admin Setting', 'litespeed-cache' ) ) ;
					}
				?>
			</div>
			<div class="litespeed-desc">
				<?php echo sprintf(__('Please visit the <a %s>Information</a> page on how to test the cache.', 'litespeed-cache'),
					'href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:information:configuration" target="_blank"'); ?>

				<strong><?php echo __('NOTICE', 'litespeed-cache'); ?>: </strong><?php echo __('When disabling the cache, all cached entries for this blog will be purged.', 'litespeed-cache'); ?>
				<?php if ( is_multisite() ): ?>
				<br><?php echo __('The network admin setting can be overridden here.', 'litespeed-cache'); ?>
				<?php endif; ?>
			</div>
		</td>
	</tr>

	<?php if ( ! is_multisite() ) : ?>
		<?php require LSCWP_DIR . 'admin/tpl/cache/settings_inc.auto_upgrade.tpl.php'; ?>
	<?php endif ; ?>

</tbody></table>

