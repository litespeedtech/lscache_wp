<?php
if (!defined('WPINC')) die;

$report = LiteSpeed_Cache_Admin_Report::get_instance()->generate_environment_report();
?>

<div class="wrap">
	<h2><?=__('LiteSpeed Cache Report', 'litespeed-cache')?></h2>
</div>
<div class="wrap">
	<div class="litespeed-cache-welcome-panel">
		<ul>
			<li><?=__('The environment report contains detailed information about the WordPress configuration.', 'litespeed-cache')?></li>
			<li><?=__('If you run into any issues, please include the contents of this text area in your support message.', 'litespeed-cache')?></li>
			<li><?=__('To easily grab the content, click the <b>Select All and Copy to Clipboard</b> button, to select and copy to clipboard.', 'litespeed-cache')?></li>
			<li><?=sprintf(__('Alternatively, this information is also saved in %s.', 'litespeed-cache'),
				'wp-content/plugins/litespeed-cache/environment_report.php')?></li>
		</ul>
		<p>
			<b><?=__('The text area below contains the following content:', 'litespeed-cache')?></b>
		</p>
		<p>
			<span style="font-size:11px; font-style:italic">
				<?=__('Server Variables, Plugin Options, WordPress information (version, locale, active plugins, etc.), and .htaccess file content.', 'litespeed-cache')?>
			</span>
		</p>
		<p>
			<button class="litespeed-btn litespeed-btn-primary" id='litespeed_cache_report_copy'>
				<?=__("Select All and Copy to Clipboard", "litespeed-cache")?>
			</button>
			<span class="litespeed-hide litespeed-notice" id="copy_select_all_span">
				<?=__("Environment Report copied to Clipboard!", "litespeed-cache")?>
			</span>
		</p>
		<textarea id="litespeed-report" rows="20" cols="80" readonly><?=$report?></textarea>
	</div>
</div>

