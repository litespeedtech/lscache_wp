<?php
/**
 * LiteSpeed Cache Cloudflare Settings
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$this->form_action();
?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'Cloudflare Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cdn/' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table">
	<tbody>
		<tr>
			<th>
				<?php $option_id = Base::O_CDN_CLOUDFLARE; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php printf( esc_html__( 'Use %s API functionality.', 'litespeed-cache' ), 'Cloudflare' ); ?>
				</div>
				<div class="litespeed-block">
					<div class='litespeed-col'>
						<label class="litespeed-form-label"><?php esc_html_e( 'Global API Key / API Token', 'litespeed-cache' ); ?></label>
						<?php $this->build_input( Base::O_CDN_CLOUDFLARE_KEY ); ?>
						<div class="litespeed-desc">
							<?php printf( esc_html__( 'Your API key / token is used to access %s APIs.', 'litespeed-cache' ), 'Cloudflare' ); ?>
							<?php printf( esc_html__( 'Get it from %s.', 'litespeed-cache' ), '<a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank" rel="noopener">Cloudflare</a>' ); ?>
							<?php esc_html_e( 'Recommended to generate the token from Cloudflare API token template "WordPress".', 'litespeed-cache' ); ?>
						</div>
					</div>
					<div class='litespeed-col'>
						<label class="litespeed-form-label"><?php esc_html_e( 'Email Address', 'litespeed-cache' ); ?></label>
						<?php $this->build_input( Base::O_CDN_CLOUDFLARE_EMAIL ); ?>
						<div class="litespeed-desc">
							<?php printf( esc_html__( 'Your Email address on %s.', 'litespeed-cache' ), 'Cloudflare' ); ?>
							<?php esc_html_e( 'Optional when API token used.', 'litespeed-cache' ); ?>
						</div>
					</div>
					<div class='litespeed-col'>
						<label class="litespeed-form-label"><?php esc_html_e( 'Domain', 'litespeed-cache' ); ?></label>
						<?php
						$cf_zone = $this->conf( Base::O_CDN_CLOUDFLARE_ZONE );
						$cls     = $cf_zone ? ' litespeed-input-success' : ' litespeed-input-warning';
						$this->build_input( Base::O_CDN_CLOUDFLARE_NAME, $cls );
						?>
						<div class="litespeed-desc">
							<?php esc_html_e( 'You can just type part of the domain.', 'litespeed-cache' ); ?>
							<?php esc_html_e( 'Once saved, it will be matched with the current list and completed automatically.', 'litespeed-cache' ); ?>
						</div>
					</div>
				</div>
			</td>
		</tr>
		<tr>
			<th>
				<?php $option_id = Base::O_CDN_CLOUDFLARE_CLEAR; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php printf( esc_html__( 'Clear %s cache when "Purge All" is run.', 'litespeed-cache' ), 'Cloudflare' ); ?>
				</div>
			</td>
		</tr>
	</tbody>
</table>

<?php
$this->form_end();
$cf_on     = $this->conf( Base::O_CDN_CLOUDFLARE );
$cf_domain = $this->conf( Base::O_CDN_CLOUDFLARE_NAME );
$cf_zone   = $this->conf( Base::O_CDN_CLOUDFLARE_ZONE );
if ( ! $cf_domain ) {
	$cf_domain = '-';
}
if ( ! $cf_zone ) {
	$cf_zone = '-';
}

$curr_status = CDN\Cloudflare::get_option( CDN\Cloudflare::ITEM_STATUS, array() );
?>

<h3 class="litespeed-title"><?php esc_html_e( 'Cloudflare', 'litespeed-cache' ); ?></h3>

<?php if ( ! $cf_on ) : ?>
	<div class="litespeed-callout notice notice-error inline">
		<h4><?php esc_html_e( 'WARNING', 'litespeed-cache' ); ?></h4>
		<p>
			<?php esc_html_e( 'To enable the following functionality, turn ON Cloudflare API in CDN Settings.', 'litespeed-cache' ); ?>
		</p>
	</div>
<?php endif; ?>

<p><?php esc_html_e( 'Cloudflare Domain', 'litespeed-cache' ); ?>: <code><?php echo esc_textarea( $cf_domain ); ?></code></p>
<p><?php esc_html_e( 'Cloudflare Zone', 'litespeed-cache' ); ?>: <code><?php echo esc_textarea( $cf_zone ); ?></code></p>

<p>
	<b><?php esc_html_e( 'Development Mode', 'litespeed-cache' ); ?>:</b>
	<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_CDN_CLOUDFLARE, CDN\Cloudflare::TYPE_SET_DEVMODE_ON ) ); ?>" class="button litespeed-btn-warning">
		<?php esc_html_e( 'Turn ON', 'litespeed-cache' ); ?>
	</a>
	<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_CDN_CLOUDFLARE, CDN\Cloudflare::TYPE_SET_DEVMODE_OFF ) ); ?>" class="button litespeed-btn-warning">
		<?php esc_html_e( 'Turn OFF', 'litespeed-cache' ); ?>
	</a>
	<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_CDN_CLOUDFLARE, CDN\Cloudflare::TYPE_GET_DEVMODE ) ); ?>" class="button litespeed-btn-success">
		<?php esc_html_e( 'Check Status', 'litespeed-cache' ); ?>
	</a>

	<?php if ( $curr_status ) : ?>
		<span class="litespeed-desc">
			<?php
			if ( time() >= $curr_status['devmode_expired'] ) {
				$expired_at             = gmdate( 'm/d/Y H:i:s', $curr_status['devmode_expired'] + LITESPEED_TIME_OFFSET );
				$curr_status['devmode'] = 'OFF';
				printf(
					esc_html__( 'Current status is %1$s since %2$s.', 'litespeed-cache' ),
					'<code>' . esc_html( strtoupper( $curr_status['devmode'] ) ) . '</code>',
					'<code>' . esc_html( $expired_at ) . '</code>'
				);
			} else {
				$expired_at = $curr_status['devmode_expired'] - time();
				$expired_at = Utility::readable_time( $expired_at, 3600 * 3, true );
				printf(
					esc_html__( 'Current status is %s.', 'litespeed-cache' ),
					'<code>' . esc_html( strtoupper( $curr_status['devmode'] ) ) . '</code>'
				);
				printf(
					esc_html__( 'Development mode will be automatically turned off in %s.', 'litespeed-cache' ),
					'<code>' . esc_html( $expired_at ) . '</code>'
				);
			}
			?>
		</span>
	<?php endif; ?>
	<br>
	<?php esc_html_e( 'Temporarily bypass Cloudflare cache. This allows changes to the origin server to be seen in realtime.', 'litespeed-cache' ); ?>
	<br>
	<?php esc_html_e( 'Development Mode will be turned off automatically after three hours.', 'litespeed-cache' ); ?>
	<?php printf( esc_html__( '%1$sLearn More%2$s', 'litespeed-cache' ), '<a href="https://support.cloudflare.com/hc/en-us/articles/200168246" target="_blank" rel="noopener">', '</a>' ); ?>
</p>

<p>
	<b><?php esc_html_e( 'Cloudflare Cache', 'litespeed-cache' ); ?>:</b>
	<?php if ( ! $cf_on ) : ?>
		<a href="#" class="button button-secondary disabled">
	<?php else : ?>
		<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_CDN_CLOUDFLARE, CDN\Cloudflare::TYPE_PURGE_ALL ) ); ?>" class="button litespeed-btn-danger">
	<?php endif; ?>
		<?php esc_html_e( 'Purge Everything', 'litespeed-cache' ); ?>
	</a>
</p>