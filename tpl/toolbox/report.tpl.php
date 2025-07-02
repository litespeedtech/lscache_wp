<?php
/**
 * LiteSpeed Cache Report Interface
 *
 * Renders the report interface for LiteSpeed Cache, allowing users to generate and send environment reports to LiteSpeed Support.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$_report = Report::cls();
$report  = $_report->generate_environment_report();

$env_ref = Report::get_summary();

// Detect passwordless plugin
$dologin_link        = '';
$has_pswdless_plugin = false;
if ( function_exists( 'dologin_gen_link' ) ) {
	$has_pswdless_plugin = true;
	if ( ! empty( $_GET['dologin_gen_link'] ) && ! empty( $_GET['litespeed_purge_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['litespeed_purge_nonce'] ) ), 'litespeed_purge_action' ) ) {
		unset( $_GET['dologin_gen_link'] );
		$dologin_link = dologin_gen_link( 'Litespeed Report' );
		?>
		<script>
			window.history.pushState('remove_gen_link', document.title, window.location.href.replace('&dologin_gen_link=1', ''));
		</script>
		<?php
	}
}

$install_link = Utility::build_url( Router::ACTION_ACTIVATION, Activation::TYPE_INSTALL_3RD, false, null, array( 'plugin' => 'dologin' ) );

$btn_title = esc_html__( 'Send to LiteSpeed', 'litespeed-cache' );
if ( ! empty( $env_ref['num'] ) ) {
	$btn_title = esc_html__( 'Regenerate and Send a New Report', 'litespeed-cache' );
}
?>

<?php if ( ! $has_pswdless_plugin ) : ?>
	<div class="litespeed-callout notice notice-warning inline">
		<h4><?php esc_html_e( 'NOTICE:', 'litespeed-cache' ); ?></h4>
		<p>
			<?php printf( esc_html__( 'To generate a passwordless link for LiteSpeed Support Team access, you must install %s.', 'litespeed-cache' ), '<a href="https://wordpress.org/plugins/dologin/" target="_blank">DoLogin Security</a>' ); ?>
		</p>
		<p>
			<a href="<?php echo esc_url( $install_link ); ?>" class="button litespeed-btn litespeed-right20"><?php esc_html_e( 'Install DoLogin Security', 'litespeed-cache' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=dologin+security&tab=search&type=term' ) ); ?>" target="_blank"><?php esc_html_e( 'Go to plugins list', 'litespeed-cache' ); ?></a>
		</p>
	</div>
<?php endif; ?>

<h3 class="litespeed-title">
	<?php esc_html_e( 'LiteSpeed Report', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/toolbox/#report-tab' ); ?>
</h3>

<p><?php esc_html_e( 'Last Report Number', 'litespeed-cache' ); ?>: <b><?php echo ! empty( $env_ref['num'] ) ? '<span id="report_span" style="cursor: pointer;" onClick="litespeed_copy_to_clipboard(\'report_span\', this)" aria-label="' . esc_attr__( 'Click to copy', 'litespeed-cache' ) . '" data-balloon-pos="down" class="litespeed-wrap">' . esc_html( $env_ref['num'] ) . '</span>' : '-'; ?></b></p>
<p><?php esc_html_e( 'Last Report Date', 'litespeed-cache' ); ?>: <b><?php echo ! empty( $env_ref['dateline'] ) ? esc_html( gmdate( 'm/d/Y H:i:s', $env_ref['dateline'] ) ) : '-'; ?></b></p>

<p class="litespeed-desc">
	<?php esc_html_e( 'The environment report contains detailed information about the WordPress configuration.', 'litespeed-cache' ); ?>
	<br />
	<?php esc_html_e( 'If you run into any issues, please refer to the report number in your support message.', 'litespeed-cache' ); ?>
</p>

<?php $this->form_action( Router::ACTION_REPORT, Report::TYPE_SEND_REPORT ); ?>
	<table class="wp-list-table striped litespeed-table">
		<tbody>
			<tr>
				<th><?php esc_html_e( 'System Information', 'litespeed-cache' ); ?></th>
				<td>
					<textarea id="litespeed-report" rows="20" cols="100" readonly><?php echo esc_textarea( $report ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th></th>
				<td>
					<?php
					$this->build_checkbox(
						'attach_php',
						sprintf(
							esc_html__( 'Attach PHP info to report. Check this box to insert relevant data from %s.', 'litespeed-cache' ),
							'<a href="https://www.php.net/manual/en/function.phpinfo.php" target="_blank">phpinfo()</a>'
						),
						false
					);
					?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Passwordless Link', 'litespeed-cache' ); ?></th>
				<td>
					<input type="text" class="litespeed-regular-text" id="litespeed-report-link" name="link" value="<?php echo esc_attr( $dologin_link ); ?>" style="width:500px;" />
					<?php if ( $has_pswdless_plugin ) : ?>
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=litespeed-toolbox&dologin_gen_link=1' ), 'litespeed_purge_action', 'litespeed_purge_nonce' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Generate Link for Current User', 'litespeed-cache' ); ?></a>
					<?php else : ?>
						<button type="button" class="button button-secondary" disabled><?php esc_html_e( 'Generate Link for Current User', 'litespeed-cache' ); ?></button>
					<?php endif; ?>
					<div class="litespeed-desc">
						<?php esc_html_e( 'To grant wp-admin access to the LiteSpeed Support Team, please generate a passwordless link for the current logged-in user to be sent with the report.', 'litespeed-cache' ); ?>
						<?php if ( $dologin_link ) : ?>
							<br /><strong>🚨 <?php esc_html_e( 'Please do NOT share the above passwordless link with anyone.', 'litespeed-cache' ); ?></strong>
							<strong>
								<?php
								printf(
									/* translators: %s: Link tags */
									esc_html__( 'Generated links may be managed under %sSettings%s.', 'litespeed-cache' ),
									'<a href="' . esc_url( menu_page_url( 'dologin', false ) ) . '#pswdless">',
									'</a>' );
								?>
							</strong>
						<?php endif; ?>
					</div>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Notes', 'litespeed-cache' ); ?></th>
				<td>
					<textarea name="notes" rows="10" cols="100"></textarea>
					<div class="litespeed-desc">
						<?php esc_html_e( 'Optional', 'litespeed-cache' ); ?>:
						<?php esc_html_e( 'provide more information here to assist the LiteSpeed team with debugging.', 'litespeed-cache' ); ?>
					</div>
				</td>
			</tr>
		</tbody>
	</table>

	<div class="litespeed-top20"></div>
	<button class="button button-primary" type="submit"><?php echo esc_html( $btn_title ); ?></button>
	<button class="button button-primary litespeed-float-submit" type="submit"><?php echo esc_html( $btn_title ); ?></button>

	<p class="litespeed-top30 litespeed-left10 litespeed-desc">
		<?php esc_html_e( 'Send this report to LiteSpeed. Refer to this report number when posting in the WordPress support forum.', 'litespeed-cache' ); ?>
	</p>
</form>