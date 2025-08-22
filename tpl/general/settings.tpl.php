<?php
/**
 * LiteSpeed Cache General Settings
 *
 * Manages general settings for LiteSpeed Cache, including Guest Mode optimization, server IP, and news settings.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$cloud_instance = Cloud::cls();
$cloud_summary  = Cloud::get_summary();

$ajax_url_get_ip = function_exists('get_rest_url') ? get_rest_url(null, 'litespeed/v1/tool/check_ip') : '/';

$this->form_action();
?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'General Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/general/' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table">
	<tbody>
		<?php if ( ! $this->_is_multisite ) : ?>
			<?php require LSCWP_DIR . 'tpl/general/settings_inc.auto_upgrade.tpl.php'; ?>
		<?php endif; ?>

		<?php if ( ! $this->_is_multisite ) : ?>
			<?php require LSCWP_DIR . 'tpl/general/settings_inc.guest.tpl.php'; ?>
		<?php endif; ?>

		<tr>
			<th>
				<?php $option_id = Base::O_GUEST_OPTM; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<span class="litespeed-danger litespeed-text-bold">
						🚨
						<?php esc_html_e( 'This option enables maximum optimization for Guest Mode visitors.', 'litespeed-cache' ); ?>
						<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/general/#guest-optimization', esc_html__( 'Please read all warnings before enabling this option.', 'litespeed-cache' ), false, 'litespeed-danger' ); ?>
					</span>

					<?php
					$type_list = array();
					if ( $this->conf( Base::O_GUEST ) && ! $this->conf( Base::O_OPTM_UCSS ) ) {
						$type_list[] = 'UCSS';
					}
					if ( $this->conf( Base::O_GUEST ) && ! $this->conf( Base::O_OPTM_CSS_ASYNC ) ) {
						$type_list[] = 'CCSS';
					}
					if ( ! empty( $type_list ) ) {
						$the_type = implode( '/', $type_list );
						echo '<br />';
						echo '<font class="litespeed-info">';
						echo '⚠️ ' . sprintf( esc_html__( 'Your %1$s quota on %2$s will still be in use.', 'litespeed-cache' ), esc_html( $the_type ), 'QUIC.cloud' );
						echo '</font>';
					}
					?>

					<?php if ( ! $this->conf( Base::O_GUEST ) ) : ?>
						<br />
						<font class="litespeed-warning litespeed-left10">
							⚠️ <?php esc_html_e( 'Notice', 'litespeed-cache' ); ?>: <?php printf( esc_html__( '%s must be turned ON for this setting to work.', 'litespeed-cache' ), '<code>' . esc_html( Lang::title( Base::O_GUEST ) ) . '</code>' ); ?>
						</font>
					<?php endif; ?>

					<?php if ( ! $this->conf( Base::O_CACHE_MOBILE ) ) : ?>
						<br />
						<font class="litespeed-primary litespeed-left10">
							⚠️ <?php esc_html_e( 'Notice', 'litespeed-cache' ); ?>: <?php printf( esc_html__( 'You need to turn %s on to get maximum result.', 'litespeed-cache' ), '<code>' . esc_html( Lang::title( Base::O_CACHE_MOBILE ) ) . '</code>' ); ?>
						</font>
					<?php endif; ?>

					<?php if ( ! $this->conf( Base::O_IMG_OPTM_WEBP ) ) : ?>
						<br />
						<font class="litespeed-primary litespeed-left10">
							⚠️ <?php esc_html_e( 'Notice', 'litespeed-cache' ); ?>: <?php printf( esc_html__( 'You need to turn %s on and finish all WebP generation to get maximum result.', 'litespeed-cache' ), '<code>' . esc_html( Lang::title( Base::O_IMG_OPTM_WEBP ) ) . '</code>' ); ?>
						</font>
					<?php endif; ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_SERVER_IP; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_input( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( "Enter this site's IP address to allow cloud services directly call IP instead of domain name. This eliminates the overhead of DNS and CDN lookups.", 'litespeed-cache' ); ?>
					<br /><?php esc_html_e( 'Your server IP', 'litespeed-cache' ); ?>: <code id='litespeed_server_ip'>-</code> <a href="javascript:;" class="button button-link" id="litespeed_get_ip"><?php esc_html_e( 'Check my public IP from', 'litespeed-cache' ); ?> CyberPanel.sh</a>
					⚠️ <?php esc_html_e( 'Notice', 'litespeed-cache' ); ?>: <?php esc_html_e( 'the auto-detected IP may not be accurate if you have an additional outgoing IP set, or you have multiple IPs configured on your server.', 'litespeed-cache' ); ?>
					<br /><?php esc_html_e( 'Please make sure this IP is the correct one for visiting your site.', 'litespeed-cache' ); ?>

					<?php $this->_validate_ip( $option_id ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_NEWS; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Turn this option ON to show latest news automatically, including hotfixes, new releases, available beta versions, and promotions.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

	</tbody>
</table>

<?php $this->form_end(); ?>

<script>
(function ($) {
	jQuery(document).ready(function () {
		/**
		 * Get server IP
		 * @since  3.0
		 */
		$('#litespeed_get_ip').on('click', function (e) {
			console.log('[litespeed] get server IP');
			$.ajax({
				url: '<?php echo $ajax_url_get_ip; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>',
				dataType: 'json',
				beforeSend: function (xhr) {
					xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_js(wp_create_nonce('wp_rest')); ?>');
					$('#litespeed_server_ip').html('Detecting...');
				},
				success: function (data) {
					$('#litespeed_server_ip').html('Done');
					console.log('[litespeed] get server IP response: ' + data);
					$('#litespeed_server_ip').html(data);
				},
				error: function (xhr, error) {
					console.log('[litespeed] get server IP error', error);
					$('#litespeed_server_ip').html('Failed to detect IP');
				},
				complete: function (xhr, status) {
					console.log('[litespeed] AJAX complete', status, xhr);
				},
			});
		});
	});
})(jQuery);
</script>