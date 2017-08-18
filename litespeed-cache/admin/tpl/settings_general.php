<?php
if (!defined('WPINC')) die;

?>
<h3 class="litespeed-title"><?php echo __('General', 'litespeed-cache'); ?></h3>

<table class="form-table"><tbody>
	<tr>
		<th><?php echo __('Enable LiteSpeed Cache', 'litespeed-cache'); ?></th>
		<td>
			<?php
				$id = LiteSpeed_Cache_Config::OPID_ENABLED_RADIO;
				//IF multisite: Add 'Use Network Admin' option,
				//ELSE: Change 'Enable LiteSpeed Cache' selection to 'Enabled' if the 'Use Network Admin' option was previously selected.
				//		Selection will not actually be changed unless settings are saved.
				if(!is_multisite() && intval($_options[$id]) === 2){
					$_options[$id] = 1;
				}
			?>
			<div class="litespeed-row">
				<div class="litespeed-switch litespeed-label-info">
					<?php echo $this->build_radio(
						$id,
						LiteSpeed_Cache_Config::VAL_ON,
						__('Enable', 'litespeed-cache')
					); ?>

					<?php echo $this->build_radio(
						$id,
						LiteSpeed_Cache_Config::VAL_OFF,
						__('Disable', 'litespeed-cache')
					); ?>

					<?php
						if ( is_multisite() ){
							echo $this->build_radio(
								$id,
								LiteSpeed_Cache_Config::VAL_NOTSET,
								__('Use Network Admin Setting', 'litespeed-cache')
							);
						}
					?>
				</div>
			</div>
			<div class="litespeed-desc">
				<?php echo sprintf(__('Please visit the <a %s>Information</a> page on how to test the cache.', 'litespeed-cache'),
					'href="'.get_admin_url().'admin.php?page=lscache-info"'); ?>

				<strong><?php echo __('NOTICE', 'litespeed-cache'); ?>: </strong><?php echo __('When disabling the cache, all cached entries for this blog will be purged.', 'litespeed-cache'); ?>
				<?php if ( is_multisite() ): ?>
				<br><?php echo __('The network admin setting can be overridden here.', 'litespeed-cache'); ?>
				<?php endif; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Default Public Cache TTL', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::OPID_PUBLIC_TTL ; ?>
			<?php $this->build_input($id); ?> <?php echo __('seconds', 'litespeed-cache'); ?>
			<div class="litespeed-desc">
				<?php echo __('Specify how long, in seconds, public pages are cached. Minimum is 30 seconds.', 'litespeed-cache'); ?>
				<?php $this->recommended($id) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Default Front Page TTL', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::OPID_FRONT_PAGE_TTL ; ?>
			<?php $this->build_input($id); ?> <?php echo __('seconds', 'litespeed-cache'); ?>
			<div class="litespeed-desc">
				<?php echo __('Specify how long, in seconds, the front page is cached. Minimum is 30 seconds.', 'litespeed-cache'); ?>
				<?php $this->recommended($id) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Default Feed TTL', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::OPID_FEED_TTL ; ?>
			<?php $this->build_input($id); ?> <?php echo __('seconds', 'litespeed-cache'); ?>
			<div class="litespeed-desc">
				<?php echo __('Specify how long, in seconds, feeds are cached.', 'litespeed-cache'); ?>
				<?php echo __('If this is set to a number less than 30, feeds will not be cached.', 'litespeed-cache'); ?>
				<?php $this->recommended($id) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Default 404 Page TTL', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::OPID_404_TTL ; ?>
			<?php $this->build_input($id); ?> <?php echo __('seconds', 'litespeed-cache'); ?>
			<div class="litespeed-desc">
				<?php echo __('Specify how long, in seconds, 404 pages are cached.', 'litespeed-cache'); ?>
				<?php echo __('If this is set to a number less than 30, 404 pages will not be cached.', 'litespeed-cache'); ?>
				<?php $this->recommended($id) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Default 403 Page TTL', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::OPID_403_TTL ; ?>
			<?php $this->build_input($id); ?> <?php echo __('seconds', 'litespeed-cache'); ?>
			<div class="litespeed-desc">
				<?php echo __('Specify how long, in seconds, 403 pages are cached.', 'litespeed-cache'); ?>
				<?php echo __('If this is set to a number less than 30, 403 pages will not be cached.', 'litespeed-cache'); ?>
				<?php $this->recommended($id) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Default 500 Page TTL', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::OPID_500_TTL ; ?>
			<?php $this->build_input($id); ?> <?php echo __('seconds', 'litespeed-cache'); ?>
			<div class="litespeed-desc">
				<?php echo __('Specify how long, in seconds, 500 pages are cached.', 'litespeed-cache'); ?>
				<?php echo __('If this is set to a number less than 30, 500 pages will not be cached.', 'litespeed-cache'); ?>
				<?php $this->recommended($id) ; ?>
			</div>
		</td>
	</tr>

</tbody></table>

