<?php defined( 'WPINC' ) || exit ; ?>

<h3 class="litespeed-title-short">
	<?php echo __( 'General', 'litespeed-cache' ) ; ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:general', false, 'litespeed-learn-more' ) ; ?>
</h3>

<?php $this->cache_disabled_warning() ; ?>

<table><tbody>
	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_CACHE ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php
				//IF multisite: Add 'Use Network Admin' option,
				//ELSE: Change 'Enable LiteSpeed Cache' selection to 'Enabled' if the 'Use Network Admin' option was previously selected.
				//		Selection will not actually be changed unless settings are saved.
				if ( ! is_multisite() && intval( $this->__options[ $id ] ) === LiteSpeed_Cache_Config::VAL_ON2 ) {
					$this->__options[ $id ] = LiteSpeed_Cache_Config::VAL_ON ;
				}
			?>
			<div class="litespeed-switch">
				<?php echo $this->build_radio( $id, LiteSpeed_Cache_Config::VAL_OFF ) ; ?>
				<?php echo $this->build_radio( $id, LiteSpeed_Cache_Config::VAL_ON ) ; ?>
				<?php
					if ( is_multisite() ) {
						echo $this->build_radio( $id, LiteSpeed_Cache_Config::VAL_ON2, __( 'Use Network Admin Setting', 'litespeed-cache' ) ) ;
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

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_CACHE_TTL_PUB ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_input( $id ) ; ?> <?php echo __( 'seconds', 'litespeed-cache' ) ; ?>
			<div class="litespeed-desc">
				<?php echo __('Specify how long, in seconds, public pages are cached.', 'litespeed-cache'); ?>
				<?php $this->recommended( $id ) ; ?>
				<?php $this->_validate_ttl( $id, 30 ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_CACHE_TTL_PRIV ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_input( $id ) ; ?> <?php echo __( 'seconds', 'litespeed-cache' ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Specify how long, in seconds, private pages are cached.', 'litespeed-cache' ) ; ?>
				<?php $this->recommended( $id ) ; ?>
				<?php $this->_validate_ttl( $id, 60, 3600 ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_CACHE_TTL_FRONTPAGE ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_input( $id ) ; ?> <?php echo __( 'seconds', 'litespeed-cache' ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Specify how long, in seconds, the front page is cached.', 'litespeed-cache' ) ; ?>
				<?php $this->recommended( $id ) ; ?>
				<?php $this->_validate_ttl( $id, 30 ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_CACHE_TTL_FEED ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_input( $id ) ; ?> <?php echo __( 'seconds', 'litespeed-cache' ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Specify how long, in seconds, feeds are cached.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'If this is set to a number less than 30, feeds will not be cached.', 'litespeed-cache' ) ; ?>
				<?php $this->recommended( $id ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_CACHE_TTL_STATUS ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_textarea( $id, 30 ) ; ?>
			<?php $this->recommended( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Specify how long, in seconds, these HTTP status pages are cached.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<?php if ( ! is_multisite() ) : ?>
		<?php require LSCWP_DIR . 'admin/tpl/setting/settings_inc.auto_upgrade.php'; ?>
	<?php endif ; ?>

</tbody></table>

