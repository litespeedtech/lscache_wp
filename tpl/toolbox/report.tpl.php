<?php

namespace LiteSpeed;

defined('WPINC') || exit;

$_report = Report::cls();
$report = $_report->generate_environment_report();

$env_ref = Report::get_summary();

// Detect password less plugin
$link = '';
$has_pswdless_plugin = false;
if (function_exists('dologin_gen_link')) {
	$has_pswdless_plugin = true;
	if (!empty($_GET['dologin_gen_link'])) {
		unset($_GET['dologin_gen_link']);
		$link = dologin_gen_link('Litespeed Report');
?>
		<script>
			window.history.pushState('remove_gen_link', document.title, window.location.href.replace('&dologin_gen_link=1', ''));
		</script>
<?php
	}
}

$install_link = Utility::build_url(Router::ACTION_ACTIVATION, Activation::TYPE_INSTALL_3RD, false, null, array('plugin' => 'dologin'));

$btn_title = __('Send to LiteSpeed', 'litespeed-cache');
if (!empty($env_ref['num'])) {
	$btn_title = __('Regenerate and Send a New Report', 'litespeed-cache');
}
?>

<?php if (!$has_pswdless_plugin) : ?>
	<div class="litespeed-callout notice notice-warning inline">
		<h4><?php echo __('NOTICE:', 'litespeed-cache'); ?></h4>
		<p>
			<?php echo sprintf(__('To generate a passwordless link for LiteSpeed Support Team access, you must install %s.', 'litespeed-cache'), '<a href="https://wordpress.org/plugins/dologin/" target="_blank">DoLogin Security</a>'); ?>
		</p>
		<p>
			<a href="<?php echo $install_link; ?>" class="button litespeed-btn litespeed-right20"><?php echo __('Install DoLogin Security', 'litespeed-cache'); ?></a>
			<a href="plugin-install.php?s=dologin+security&tab=search&type=term" target="_blank"><?php echo __('Go to plugins list', 'litespeed-cache'); ?></a>
		</p>
	</div>
<?php endif; ?>

<h3 class="litespeed-title">
	<?php echo __('LiteSpeed Report', 'litespeed-cache'); ?>
	<?php Doc::learn_more('https://docs.litespeedtech.com/lscache/lscwp/toolbox/#report-tab'); ?>
</h3>

<p><?php echo __('Last Report Number', 'litespeed-cache'); ?>: <b><?php echo !empty($env_ref['num']) ? $env_ref['num'] : '-'; ?></b></p>
<p><?php echo __('Last Report Date', 'litespeed-cache'); ?>: <b><?php echo !empty($env_ref['dateline']) ? date('m/d/Y H:i:s', $env_ref['dateline']) : '-'; ?></b></p>

<p class="litespeed-desc">
	<?php echo __('The environment report contains detailed information about the WordPress configuration.', 'litespeed-cache'); ?>
	<br />
	<?php echo __('If you run into any issues, please refer to the report number in your support message.', 'litespeed-cache'); ?>
</p>

<form action="<?php echo Utility::build_url(Router::ACTION_REPORT, Report::TYPE_SEND_REPORT); ?>" method="post" class="litespeed-relative">
	<table class="wp-list-table striped litespeed-table">
		<tbody>
			<tr>
				<th><?php echo __('System Information', 'litespeed-cache'); ?></th>
				<td>
					<textarea id="litespeed-report" rows="20" cols="100" readonly><?php echo esc_textarea($report); ?></textarea>
				</td>
			</tr>
			<tr>
				<th>&nbsp;</th>
				<td>
					<?php
					$this->build_checkbox(
						'attach_php',
						sprintf(
							__(
								'Attach PHP info to report. Check this box to insert relevant data from %s.',
								'litespeed-cache'
							),
							'<a href="https://www.php.net/manual/en/function.phpinfo.php" target="__blank">phpinfo()</a>'
						),
						false
					);
					?>
				</td>
			</tr>
			<tr>
				<th><?php echo __('Passwordless Link', 'litespeed-cache'); ?></th>
				<td>
					<input type="text" class="litespeed-regular-text" id="litespeed-report-link" name="link" value="<?php echo $link; ?>" style="width:500px;" />
					<?php if ($has_pswdless_plugin) : ?>
						<a href="<?php echo admin_url('admin.php?page=litespeed-toolbox&dologin_gen_link=1'); ?>" class="button button-secondary"><?php echo __('Generate Link for Current User', 'litespeed-cache'); ?></a>
					<?php else : ?>
						<button type="button" class="button button-secondary" disabled><?php echo __('Generate Link for Current User', 'litespeed-cache'); ?></button>
					<?php endif; ?>
					<div class="litespeed-desc">
						<?php echo __('To grant wp-admin access to the LiteSpeed Support Team, please generate a passwordless link for the current logged-in user to be sent with the report.', 'litespeed-cache'); ?>
						<?php if ($link) : ?>
							<br /><strong>🚨 <?php echo __('Please do NOT share the above passwordless link with anyone.', 'litespeed-cache'); ?></strong>
							<strong><?php echo sprintf(__('Generated links may be managed under <a %s>Settings</a>.', 'litespeed-cache'), 'href="' . menu_page_url('dologin', 0) . '"'); ?></strong>
						<?php endif; ?>
					</div>
				</td>
			</tr>
			<tr>
				<th><?php echo __('Notes', 'litespeed-cache'); ?></th>
				<td>
					<textarea name="notes" rows="10" cols="100"></textarea>
					<div class="litespeed-desc">
						<?php echo __('Optional', 'litespeed-cache'); ?>:
						<?php echo __('provide more information here to assist the LiteSpeed team with debugging.', 'litespeed-cache'); ?>
					</div>
				</td>
			</tr>
		</tbody>
	</table>

	<div class='litespeed-top20'></div>
	<button class="button button-primary" type="submit"><?php echo $btn_title; ?></button>
	<button class="button button-primary litespeed-float-submit" type="submit"><?php echo $btn_title; ?></button>

	<p class="litespeed-top30 litespeed-left10 litespeed-desc">
		<?php echo __('Send this report to LiteSpeed. Refer to this report number when posting in the WordPress support forum.', 'litespeed-cache'); ?>
	</p>
</form>