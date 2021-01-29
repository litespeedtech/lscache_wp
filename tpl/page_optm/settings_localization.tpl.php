<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$last_generated = Avatar::get_summary();
$avatar_queue = Avatar::get_instance()->queue_count();
?>

<?php if ( Avatar::need_db() && ! Data::get_instance()->tb_exist( 'avatar' ) ) : ?>
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

	<tr>
		<th>
			<?php $id = Base::O_OPTM_LOCALIZE; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Localize external resources.', 'litespeed-cache' ); ?>
				<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#localize' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left">
			<?php $id = Base::O_OPTM_LOCALIZE_DOMAINS; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<div class="litespeed-textarea-recommended">
				<div>
					<?php $this->build_textarea( $id ); ?>
				</div>
				<div>
					<?php $this->recommended( $id, true ); ?>
				</div>
			</div>

			<div class="litespeed-desc">
				<?php echo __( 'Resources listed here will be copied and replaced with local URLs.', 'litespeed-cache' ); ?>
				<?php echo __( 'HTTPS sources only.', 'litespeed-cache' ); ?>

				<?php Doc::one_per_line(); ?>

				<br /><?php echo sprintf( __( 'Comments are supported. Start a line with a %s to turn it into a comment line.', 'litespeed-cache' ), '<code>#</code>' ); ?>

				<br /><?php echo __( 'Non-`.js` file extensions will be ignored.', 'litespeed-cache' ); ?>
				<?php echo __( 'Example', 'litespeed-cache' ); ?>: <code>https://www.example.com/one.js</code>
				<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#localization-files' ); ?>

			</div>
		</td>
	</tr>

</tbody></table>
