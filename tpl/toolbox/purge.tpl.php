<?php

namespace LiteSpeed;

defined('WPINC') || exit;

$_panels = array(
	array(
		'title'	=> __('Purge Front Page', 'litespeed-cache'),
		'desc'	=> __('This will Purge Front Page only', 'litespeed-cache'),
		'icon'	=> 'purge-front',
		'append_url'	=> Purge::TYPE_PURGE_FRONTPAGE,
	),
	array(
		'title'	=> __('Purge Pages', 'litespeed-cache'),
		'desc'	=> __('This will Purge Pages only', 'litespeed-cache'),
		'icon'	=> 'purge-pages',
		'append_url'	=> Purge::TYPE_PURGE_PAGES,
	),
);
foreach (Tag::$error_code_tags as $code) {
	$_panels[] = array(
		'title'	=> sprintf(__('Purge %s Error', 'litespeed-cache'), $code),
		'desc'	=> sprintf(__('Purge %s error pages', 'litespeed-cache'), $code),
		'icon'	=> 'purge-' . $code,
		'append_url'	=> Purge::TYPE_PURGE_ERROR . $code,
	);
}
$_panels[] = array(
	'title'	=> __('Purge All', 'litespeed-cache') . ' - LSCache',
	'desc'	=> __('Purge the LiteSpeed cache entries created by this plugin', 'litespeed-cache'),
	'icon'	=> 'purge-all',
	'append_url'	=> Purge::TYPE_PURGE_ALL_LSCACHE,
);
$_panels[] = 	array(
	'title'	=> __('Purge All', 'litespeed-cache') . ' - ' . __('CSS/JS Cache', 'litespeed-cache'),
	'desc'	=> __('This will purge all minified/combined CSS/JS entries only', 'litespeed-cache'),
	'icon'	=> 'purge-cssjs',
	'append_url'	=> Purge::TYPE_PURGE_ALL_CSSJS,
);

if (defined('LSCWP_OBJECT_CACHE')) {
	$_panels[] = array(
		'title'	=> __('Purge All', 'litespeed-cache') . ' - ' . __('Object Cache', 'litespeed-cache'),
		'desc'	=> __('Purge all the object caches', 'litespeed-cache'),
		'icon'	=> 'purge-object',
		'append_url'	=> Purge::TYPE_PURGE_ALL_OBJECT,
	);
}

if (Router::opcache_enabled()) {
	$_panels[] = array(
		'title'	=> __('Purge All', 'litespeed-cache') . ' - ' . __('Opcode Cache', 'litespeed-cache'),
		'desc'	=> __('Reset the entire opcode cache', 'litespeed-cache'),
		'icon'	=> 'purge-opcache',
		'append_url'	=> Purge::TYPE_PURGE_ALL_OPCACHE,
	);
}

if ($this->has_cache_folder('ccss')) {
	$_panels[] = array(
		'title'	=> __('Purge All', 'litespeed-cache') . ' - ' . __('Critical CSS', 'litespeed-cache'),
		'desc'	=> __('This will delete all generated critical CSS files', 'litespeed-cache'),
		'icon'	=> 'purge-cssjs',
		'append_url'	=> Purge::TYPE_PURGE_ALL_CCSS,
	);
}

if ($this->has_cache_folder('ucss')) {
	$_panels[] = array(
		'title'	=> __('Purge All', 'litespeed-cache') . ' - ' . __('Unique CSS', 'litespeed-cache'),
		'desc'	=> __('This will delete all generated unique CSS files', 'litespeed-cache'),
		'icon'	=> 'purge-cssjs',
		'append_url'	=> Purge::TYPE_PURGE_ALL_UCSS,
	);
}

if ($this->has_cache_folder('localres')) {
	$_panels[] = array(
		'title'		=> __('Purge All', 'litespeed-cache') . ' - ' . __('Localized Resources', 'litespeed-cache'),
		'desc'	=> __('This will delete all localized resources', 'litespeed-cache'),
		'icon'	=> 'purge-cssjs',
		'append_url'	=> Purge::TYPE_PURGE_ALL_LOCALRES,
	);
}

if ($this->has_cache_folder('lqip')) {
	$_panels[] = array(
		'title'	=> __('Purge All', 'litespeed-cache') . ' - ' . __('LQIP Cache', 'litespeed-cache'),
		'desc'	=> __('This will delete all generated image LQIP placeholder files', 'litespeed-cache'),
		'icon'	=> 'purge-front',
		'append_url'	=> Purge::TYPE_PURGE_ALL_LQIP,
	);
}

if ($this->has_cache_folder('avatar')) {
	$_panels[] = array(
		'title'	=> __('Purge All', 'litespeed-cache') . ' - ' . __('Gravatar Cache', 'litespeed-cache'),
		'desc'	=> __('This will delete all cached Gravatar files', 'litespeed-cache'),
		'icon'	=> 'purge-cssjs',
		'append_url'	=> Purge::TYPE_PURGE_ALL_AVATAR,
	);
}


$_panels[] = array(
	'title'	=> __('Purge All', 'litespeed-cache'),
	'desc'	=> __('Purge the cache entries created by this plugin except for Critical CSS & Unique CSS & LQIP caches', 'litespeed-cache'),
	'icon'	=> 'purge-all',
	'title_cls'	=> 'litespeed-warning',
	'newline'	=> true,
	'append_url'	=> Purge::TYPE_PURGE_ALL,
);

if (!is_multisite() || is_network_admin()) {
	$_panels[] = array(
		'title'	=> __('Empty Entire Cache', 'litespeed-cache'),
		'desc'	=> __('Clears all cache entries related to this site, <i>including other web applications</i>.', 'litespeed-cache') . ' <b>' .
			__('This action should only be used if things are cached incorrectly.', 'litespeed-cache') . '</b>',
		'tag'	=> Core::ACTION_PURGE_EMPTYCACHE,
		'icon'	=> 'empty-cache',
		'title_cls'	=> 'litespeed-danger',
		'cfm'	=>  esc_html(__('This will clear EVERYTHING inside the cache.', 'litespeed-cache')) . ' ' .
			esc_html(__('This may cause heavy load on the server.', 'litespeed-cache')) . ' ' .
			esc_html(__('If only the WordPress site should be purged, use Purge All.', 'litespeed-cache'))
	);
}

?>

<?php include_once LSCWP_DIR . "tpl/inc/check_cache_disabled.php"; ?>

<h3 class="litespeed-title">
	<?php echo __('Purge', 'litespeed-cache'); ?>
	<?php Doc::learn_more('https://docs.litespeedtech.com/lscache/lscwp/toolbox/#purge-tab'); ?>
</h3>

<div class="litespeed-panel-wrapper litespeed-cards-wrapper">

	<?php foreach ($_panels as $v) : ?>
		<?php $tag = !empty($v['tag']) ? $v['tag'] : Router::ACTION_PURGE; ?>
		<?php $append_url = !empty($v['append_url']) ? $v['append_url'] : false; ?>

		<?php if (!empty($v['newline'])) : ?>
			<div class='litespeed-col-br'></div>
		<?php endif; ?>

		<a class="litespeed-panel postbox" href="<?php echo Utility::build_url($tag, $append_url); ?>" <?php if (!empty($v['cfm'])) echo 'data-litespeed-cfm="' . Str::trim_quotes($v['cfm']) . '"'; ?>>
			<section class="litespeed-panel-wrapper-icon">
				<span class="litespeed-panel-icon-<?php echo $v['icon']; ?>"></span>
			</section>
			<section class="litespeed-panel-content">
				<div class="litespeed-h3 <?php if (!empty($v['title_cls'])) echo $v['title_cls']; ?>">
					<?php echo $v['title']; ?>
				</div>
				<span class="litespeed-panel-para"><?php echo $v['desc']; ?></span>
			</section>
		</a>

	<?php endforeach; ?>

</div>

<?php if (!is_multisite() || !is_network_admin()) : ?>

	<h3 class="litespeed-title"><?php echo __('Purge By...', 'litespeed-cache'); ?></h3>
	<p class="litespeed-description">
		<?php echo __('Select below for "Purge by" options.', 'litespeed-cache'); ?>
		<?php Doc::one_per_line(); ?>
	</p>

	<?php
	$purgeby_option = false;
	$_option_field = Admin_Display::PURGEBYOPT_SELECT;
	if (!empty($_REQUEST[$_option_field])) {
		$purgeby_option = $_REQUEST[$_option_field];
	}
	if (!in_array($purgeby_option, array(
		Admin_Display::PURGEBY_CAT,
		Admin_Display::PURGEBY_PID,
		Admin_Display::PURGEBY_TAG,
		Admin_Display::PURGEBY_URL,
	))) {
		$purgeby_option = Admin_Display::PURGEBY_CAT;
	}
	?>

	<?php $this->form_action(Core::ACTION_PURGE_BY); ?>
	<div class="litespeed-row">
		<div class="litespeed-switch litespeed-mini litespeed-right20 litespeed-margin-bottom10">
			<?php $val = Admin_Display::PURGEBY_CAT; ?>
			<input type="radio" autocomplete="off" name="<?php echo $_option_field; ?>" id="purgeby_option_category" value="<?php echo $val; ?>" <?php if ($purgeby_option == $val) echo 'checked'; ?> />
			<label for="purgeby_option_category"><?php echo __('Category', 'litespeed-cache'); ?></label>

			<?php $val = Admin_Display::PURGEBY_PID; ?>
			<input type="radio" autocomplete="off" name="<?php echo $_option_field; ?>" id="purgeby_option_postid" value="<?php echo $val; ?>" <?php if ($purgeby_option == $val) echo 'checked'; ?> />
			<label for="purgeby_option_postid"><?php echo __('Post ID', 'litespeed-cache'); ?></label>

			<?php $val = Admin_Display::PURGEBY_TAG; ?>
			<input type="radio" autocomplete="off" name="<?php echo $_option_field; ?>" id="purgeby_option_tag" value="<?php echo $val; ?>" <?php if ($purgeby_option == $val) echo 'checked'; ?> />
			<label for="purgeby_option_tag"><?php echo __('Tag', 'litespeed-cache'); ?></label>

			<?php $val = Admin_Display::PURGEBY_URL; ?>
			<input type="radio" autocomplete="off" name="<?php echo $_option_field; ?>" id="purgeby_option_url" value="<?php echo $val; ?>" <?php if ($purgeby_option == $val) echo 'checked'; ?> />
			<label for="purgeby_option_url"><?php echo __('URL', 'litespeed-cache'); ?></label>
		</div>

		<div class="litespeed-cache-purgeby-text litespeed-desc">
			<div class="<?php if ($purgeby_option != Admin_Display::PURGEBY_CAT) echo 'litespeed-hide'; ?>" data-purgeby="<?php echo Admin_Display::PURGEBY_CAT; ?>">
				<?php echo sprintf(
					__('Purge pages by category name - e.g. %2$s should be used for the URL %1$s.', "litespeed-cache"),
					'<code>http://example.com/category/category-name/</code>',
					'<code>category-name</code>'
				); ?>
			</div>
			<div class="<?php if ($purgeby_option != Admin_Display::PURGEBY_PID) echo 'litespeed-hide'; ?>" data-purgeby="<?php echo Admin_Display::PURGEBY_PID; ?>">
				<?php echo __("Purge pages by post ID.", "litespeed-cache"); ?>
			</div>
			<div class="<?php if ($purgeby_option != Admin_Display::PURGEBY_TAG) echo 'litespeed-hide'; ?>" data-purgeby="<?php echo Admin_Display::PURGEBY_TAG; ?>">
				<?php echo sprintf(
					__('Purge pages by tag name - e.g. %2$s should be used for the URL %1$s.', "litespeed-cache"),
					'<code>http://example.com/tag/tag-name/</code>',
					'<code>tag-name</code>'
				); ?>
			</div>
			<div class="<?php if ($purgeby_option != Admin_Display::PURGEBY_URL) echo 'litespeed-hide'; ?>" data-purgeby="<?php echo Admin_Display::PURGEBY_URL; ?>">
				<?php echo __('Purge pages by relative or full URL.', 'litespeed-cache'); ?>
				<?php echo sprintf(
					__('e.g. Use %s or %s.', 'litespeed-cache'),
					'<code>/2016/02/24/hello-world/</code>',
					'<code>http://www.myexamplesite.com/2016/02/24/hello-world/</code>'
				); ?>
			</div>
		</div>

	</div>

	<p>
		<textarea name="<?php echo Admin_Display::PURGEBYOPT_LIST; ?>" rows="5" class="litespeed-textarea"></textarea>
	</p>

	<p>
		<button type="submit" class="button button-primary"><?php echo __('Purge List', 'litespeed-cache'); ?></button>
	</p>
	</form>
<?php endif; ?>