<?php
/**
 * LiteSpeed Cache Localization Settings
 *
 * Renders the localization settings interface for LiteSpeed Cache, including Gravatar caching and resource localization.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$last_generated = Avatar::get_summary();
$avatar_queue   = Avatar::cls()->queue_count();
?>

<?php if ( $this->cls( 'Avatar' )->need_db() && ! $this->cls( 'Data' )->tb_exist( 'avatar' ) ) : ?>
<div class="litespeed-callout notice notice-error inline">
	<h4><?php esc_html_e( 'WARNING', 'litespeed-cache' ); ?></h4>
	<p><?php printf( esc_html__( 'Failed to create Avatar table. Please follow <a %s>Table Creation guidance from LiteSpeed Wiki</a> to finish setup.', 'litespeed-cache' ), 'href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:installation" target="_blank"' ); ?></p>
</div>
<?php endif; ?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'Localization Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#localization-settings-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>
	<tr>
		<th>
			<?php $option_id = Base::O_DISCUSS_AVATAR_CACHE; ?>
			<?php $this->title( $option_id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $option_id ); ?>
			<div class="litespeed-desc">
				<?php esc_html_e( 'Store Gravatar locally.', 'litespeed-cache' ); ?>
				<?php esc_html_e( 'Accelerates the speed by caching Gravatar (Globally Recognized Avatars).', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left">
			<?php $option_id = Base::O_DISCUSS_AVATAR_CRON; ?>
			<?php $this->title( $option_id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $option_id ); ?>
			<div class="litespeed-desc">
				<?php esc_html_e( 'Refresh Gravatar cache by cron.', 'litespeed-cache' ); ?>
			</div>

			<?php if ( $last_generated ) : ?>
			<div class="litespeed-desc">
				<?php if ( ! empty( $last_generated['last_request'] ) ) : ?>
					<p>
						<?php echo esc_html__( 'Last ran', 'litespeed-cache' ) . ': <code>' . esc_html( Utility::readable_time( $last_generated['last_request'] ) ) . '</code>'; ?>
					</p>
				<?php endif; ?>
				<?php if ( $avatar_queue ) : ?>
					<div class="litespeed-callout notice notice-warning inline">
						<h4>
							<?php echo esc_html__( 'Avatar list in queue waiting for update', 'litespeed-cache' ); ?>:
							<?php echo esc_html( $avatar_queue ); ?>
						</h4>
					</div>
					<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_AVATAR, Avatar::TYPE_GENERATE ) ); ?>" class="button litespeed-btn-success">
						<?php esc_html_e( 'Run Queue Manually', 'litespeed-cache' ); ?>
					</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>

		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left">
			<?php $option_id = Base::O_DISCUSS_AVATAR_CACHE_TTL; ?>
			<?php $this->title( $option_id ); ?>
		</th>
		<td>
			<?php $this->build_input( $option_id ); ?> <?php $this->readable_seconds(); ?>
			<div class="litespeed-desc">
				<?php esc_html_e( 'Specify how long, in seconds, Gravatar files are cached.', 'litespeed-cache' ); ?>
				<?php $this->recommended( $option_id ); ?>
				<?php $this->_validate_ttl( $option_id, 3600 ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $option_id = Base::O_OPTM_LOCALIZE; ?>
			<?php $this->title( $option_id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $option_id ); ?>
			<div class="litespeed-desc">
				<?php esc_html_e( 'Localize external resources.', 'litespeed-cache' ); ?>
				<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#localize' ); ?>

				<br /><font class="litespeed-danger">
					🚨 <?php printf( esc_html__( 'Please thoroughly test all items in %s to ensure they function as expected.', 'litespeed-cache' ), '<code>' . esc_html( Lang::title( Base::O_OPTM_LOCALIZE_DOMAINS ) ) . '</code>' ); ?>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left">
			<?php $option_id = Base::O_OPTM_LOCALIZE_DOMAINS; ?>
			<?php $this->title( $option_id ); ?>
		</th>
		<td>
			<div class="litespeed-textarea-recommended">
				<div>
					<?php $this->build_textarea( $option_id ); ?>
				</div>
				<div>
					<?php $this->recommended( $option_id, true ); ?>
				</div>
			</div>

			<div class="litespeed-desc">
				<?php esc_html_e( 'Resources listed here will be copied and replaced with local URLs.', 'litespeed-cache' ); ?>
				<?php esc_html_e( 'HTTPS sources only.', 'litespeed-cache' ); ?>

				<?php Doc::one_per_line(); ?>

				<br /><?php printf( esc_html__( 'Comments are supported. Start a line with a %s to turn it into a comment line.', 'litespeed-cache' ), '<code>#</code>' ); ?>

				<br /><?php esc_html_e( 'Example', 'litespeed-cache' ); ?>: <code>https://www.example.com/one.js</code>
				<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#localization-files' ); ?>

				<br /><font class="litespeed-danger">
					🚨 <?php esc_html_e( 'Please thoroughly test each JS file you add to ensure it functions as expected.', 'litespeed-cache' ); ?>
				</font>
			</div>
		</td>
	</tr>

</tbody></table>