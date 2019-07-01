<?php defined( 'WPINC' ) || exit ; ?>

<div class="wrap">
	<h1 class="litespeed-h1">
		<?php echo __('LiteSpeed Cache Crawler', 'litespeed-cache') ; ?>
	</h1>
	<span class="litespeed-desc">
		v<?php echo LiteSpeed_Cache::PLUGIN_VERSION; ?>
	</span>
	<hr class="wp-header-end">

</div>

<div class="litespeed-wrap">
	<h2 class="litespeed-header">
		<a class='litespeed-tab' href='#general' data-litespeed-tab='general' litespeed-accesskey='1'>General</a>
		<a class='litespeed-tab' href='#settings' data-litespeed-tab='settings' litespeed-accesskey='2'>Settings</a>
	</h2>
	<div class="litespeed-body">
		<div data-litespeed-layout='general'>
			<?php require LSCWP_DIR . "admin/tpl/crawler/crawler_general.inc.php" ; ?>
		</div>

		<div data-litespeed-layout='settings'>
			<?php require LSCWP_DIR . "admin/tpl/crawler/crawler_settings.inc.php" ; ?>
		</div>

	</div>
</div>

<iframe name="litespeedHiddenIframe" src="" width="0" height="0" frameborder="0"></iframe>
