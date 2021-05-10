<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$last_generated = Avatar::get_summary();
$avatar_queue = Avatar::cls()->queue_count();
?>

<?php if ( $this->cls( 'Avatar' )->need_db() && ! $this->cls( 'Data' )->tb_exist( 'avatar' ) ) : ?>
<div class="litespeed-callout notice notice-error inline">
	<h4><?php echo __( 'WARNING', 'litespeed-cache' ); ?></h4>
	<p><?php echo sprintf( __( 'Failed to create Avatar table. Please follow <a %s>Table Creation guidance from LiteSpeed Wiki</a> to finish setup.', 'litespeed-cache' ), 'href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:installation" target="_blank"' ); ?></p>
</div>
<?php endif; ?>

<h3 class="litespeed-title-short">
	<?php echo __( 'Localization Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#localization-settings-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>
	<tr>
		<th>
			<?php $id = Base::O_DISCUSS_AVATAR_CACHE; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Store Gravatar locally.', 'litespeed-cache' ); ?>
				<?php echo __( 'Accelerates the speed by caching Gravatar (Globally Recognized Avatars).', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left">
			<?php $id = Base::O_DISCUSS_AVATAR_CRON; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Refresh Gravatar cache by cron.', 'litespeed-cache' ); ?>
			</div>

			<?php if ( $last_generated ) : ?>
			<div class="litespeed-desc">
				<?php if ( ! empty( $last_generated[ 'last_request' ] ) ) : ?>
					<p>
						<?php echo __( 'Last ran', 'litespeed-cache' ) . ': <code>' . Utility::readable_time( $last_generated[ 'last_request' ] ) . '</code>'; ?>
					</p>
				<?php endif; ?>
				<?php if ( $avatar_queue ) : ?>
					<div class="litespeed-callout notice notice-warning inline">
						<h4>
							<?php echo __( 'Avatar list in queue waiting for update','litespeed-cache' ); ?>:
							<?php echo $avatar_queue; ?>
						</h4>
					</div>
					<a href="<?php echo Utility::build_url( Router::ACTION_AVATAR, Avatar::TYPE_GENERATE ); ?>" class="button litespeed-btn-success">
						<?php echo __( 'Run Queue Manually', 'litespeed-cache' ); ?>
					</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>

		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left">
			<?php $id = Base::O_DISCUSS_AVATAR_CACHE_TTL; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input( $id ); ?> <?php $this->readable_seconds(); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Specify how long, in seconds, Gravatar files are cached.', 'litespeed-cache' ); ?>
				<?php $this->recommended( $id ); ?>
				<?php $this->_validate_ttl( $id, 3600 ); ?>
			</div>
		</td>
	</tr>

</tbody></table>
