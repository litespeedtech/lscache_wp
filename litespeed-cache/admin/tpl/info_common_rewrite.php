<?php
if (!defined('WPINC')) die;

$notice_title = '';
$notice_content = '';

if ((is_multisite()) && (!is_network_admin())) {
	$notice_title = __('NOTE:', 'litespeed-cache');
	$notice_content = 
		'<p>'.__('The following configuration can only be changed by the network admin.', 'litespeed-cache').'</p>'.
		'<p>'.__('Please contact the network admin to make any changes.', 'litespeed-cache').'</p>';
}
else {
	$notice_title = __('NOTICE:', 'litespeed-cache');
	$notice_content = 
		'<p>'.
		__('The following rewrite rules can be configured in the LiteSpeed Cache settings page.', 'litespeed-cache').' '.
		__('Please make any needed changes on that page.', 'litespeed-cache').' '.
		__('It will automatically generate the correct rules in the htaccess file.', 'litespeed-cache').
		'</p>';
}
?>

<h3 class="litespeed-title"><?php echo __('LiteSpeed Cache Common Rewrite Rules', 'litespeed-cache'); ?></h3>

<div class="litespeed-callout litespeed-callout-warning">
	<h4><?php echo $notice_title; ?></h4>
	<?php echo $notice_content; ?>
</div>

<h4 class="litespeed-question litespeed-down"><?php echo __('Mobile Views:', 'litespeed-cache'); ?></h4>
<div class="litespeed-answer">
	<p>
		<?php echo __('Some sites have adaptive views, meaning the page sent will adapt to the browser type (desktop vs mobile).', 'litespeed-cache'); ?>
		<?php echo __('This rewrite rule is used for sites that load a different page for each type.', 'litespeed-cache'); ?>
	</p>
	<p>
		<?php echo __('This configuration can be added on the settings page in the General tab.', 'litespeed-cache'); ?>
	</p>
	<textarea id="wpwrap" rows="2" readonly>RewriteCond %{HTTP_USER_AGENT} Mobile|Android|Silk/|Kindle|BlackBerry|Opera\ Mini|Opera\ Mobi [NC]
RewriteRule .* - [E=Cache-Control:vary=ismobile]</textarea>
</div>

<h4 class="litespeed-question litespeed-down"><?php echo __('Do Not Cache Cookies:', 'litespeed-cache'); ?></h4>
<div class="litespeed-answer">
	<p><?php echo __('Another common rewrite rule is to notify the cache not to cache when it sees a specified cookie name.', 'litespeed-cache'); ?></p>
	<p><?php echo __('This configuration can be added on the settings page in the Do Not Cache tab.', 'litespeed-cache'); ?></p>
	<textarea id="wpwrap" rows="2" readonly>RewriteCond %{HTTP_COOKIE} dontcachecookie
RewriteRule .* - [E=Cache-Control:no-cache]</textarea>
</div>

<h4 class="litespeed-question litespeed-down"><?php echo __('Do Not Cache User Agent:', 'litespeed-cache'); ?></h4>
<div class="litespeed-answer">
	<p><?php echo __('A not so commonly used rewrite rule is to notify the cache not to cache when it sees a specified User Agent.', 'litespeed-cache'); ?></p>
	<p><?php echo __('This configuration can be added on the settings page in the Do Not Cache tab.', 'litespeed-cache'); ?></p>
	<textarea id="wpwrap" rows="2" readonly>RewriteCond %{HTTP_USER_AGENT} dontcacheuseragent
RewriteRule .* - [E=Cache-Control:no-cache]</textarea>
</div>
