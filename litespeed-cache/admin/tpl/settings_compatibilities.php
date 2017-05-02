<?php
if (!defined('WPINC')) die;

if (!LiteSpeed_Cache_Admin_Display::get_instance()->show_compatibility_tab()) return;
?>

<h3 class="litespeed-title"><?php echo __('Compatibility with WP-PostViews', 'litespeed-cache'); ?></h3>

<p><?php echo __('To make LiteSpeed Cache compatible with WP-PostViews:', 'litespeed-cache'); ?></p>

<ol>
	<li>
		<p><?php echo __('Replace the following calls in the active theme\'s template files with a div or span with a unique ID.', 'litespeed-cache'); ?></p>
		<p><?php echo sprintf(__('e.g. Replace <br> <pre>%1$s</pre> with<br> <pre>%2$s</pre>', 'litespeed-cache'),
				htmlspecialchars('<?php if(function_exists(\'the_views\' )) { the_views(); } ?>'),
				htmlspecialchars('<div id="postviews_lscwp" > </div>')
			); ?>
		</p>
	</li>
	<li>
		<p><?php echo __('Update the ajax request to output the results to that div.', 'litespeed-cache'); ?></p>
		<p><?php echo __('Example:', 'litespeed-cache'); ?></p>
		<pre>jQuery.ajax({
	type:"GET",
	url:viewsCacheL10n.admin_ajax_url,
	data:"postviews_id="+viewsCacheL10n.post_id+"&action=postviews",
	cache:!1,
	success:function(data) {
		if(data) {
			jQuery(\'#postviews_lscwp\').html(data+\' views\');
		}
	}
});</pre>
		<p><?php echo __('The ajax code can be found at', 'litespeed-cache'); ?></p>
		<pre>/wp-content/plugins/wp-postviews/postviews-cache.js</pre>
	</li>
	<li><?php echo __('After purging the cache, the view count should be updating.', 'litespeed-cache'); ?></li>
</ol>
