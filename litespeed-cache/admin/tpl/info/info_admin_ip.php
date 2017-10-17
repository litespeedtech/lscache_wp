<?php
if (!defined('WPINC')) die;

$nocache_desc =
	__('This is used to display a page without caching it.', 'litespeed-cache').' '.
	__('An example use case is to compare a cached version of a page with an uncached version.', 'litespeed-cache');

$purge_desc =
	__('This is used to purge most cache tags associated with the page.', 'litespeed-cache').' '.
	__('The lone exception is the blog ID tag.', 'litespeed-cache').' '.
	__('Note that this means that pages with the same cache tag will be purged as well.', 'litespeed-cache');

$showheaders_desc =
	__('This is used to show all the cache headers associated with a page.', 'litespeed-cache').' '.
	__('This may be useful for debugging purposes.', 'litespeed-cache');

?>

<h3 class="litespeed-title"><?php echo __('Admin IP Query String Actions', 'litespeed-cache'); ?></h3>

<h4><?php echo __('The following commands are available to the admin and do not require log-in, providing quick access to actions on the various pages.', 'litespeed-cache'); ?></h4>

<h4><?php echo __('Action List:', 'litespeed-cache'); ?></h4>

<ul>
	<li><?php echo LiteSpeed_Cache::ACTION_QS_NOCACHE; ?> - <?php echo $nocache_desc; ?></li>
	<li><?php echo LiteSpeed_Cache::ACTION_QS_PURGE; ?> - <?php echo $purge_desc; ?></li>
	<li><?php echo LiteSpeed_Cache::ACTION_QS_PURGE_SINGLE; ?> - <?php echo __('This is used to purge the first cache tag associated with the page.', 'litespeed-cache'); ?></li>
	<li><?php echo LiteSpeed_Cache::ACTION_QS_SHOW_HEADERS; ?> - <?php echo $showheaders_desc; ?></li>
</ul>

<h5><?php echo sprintf(__('To trigger the action for a page, access the page with the query string %s', 'litespeed-cache'),
		'<code>?'.LiteSpeed_Cache::ACTION_KEY.'=ACTION</code>'); ?></h5>
