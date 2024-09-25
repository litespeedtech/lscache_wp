<?php

namespace LiteSpeed;

defined('WPINC') || exit;

$logs =
	array(
		array(
			'name' => 'debug',
			'label' => esc_html__('Debug Log', 'litespeed-cache'),
			'accesskey' => 'A',
		),
		array(
			'name' => 'purge',
			'label' => esc_html__('Purge Log', 'litespeed-cache'),
			'accesskey' => 'B',
		),
		array(
			'name' => 'crawler',
			'label' => esc_html__('Crawler Log', 'litespeed-cache'),
			'accesskey' => 'C',
		),
	);

/**
 * Return a subnav button (subtab)
 * @since  4.7
 */
function subnav_link($item)
{
	$class = 'button ';
	$subtab = '';

	if (!isset($item['url'])) {
		$class .= 'button-secondary';
		$subtab_name = "{$item['name']}_log";
		$subtab = "data-litespeed-subtab='{$subtab_name}'";
		$url = "#{$subtab_name}";
	} else {
		$class .= 'button-primary';
		$url = $item['url'];
	}

	$accesskey =
		isset($item['accesskey'])
		? "litespeed-accesskey='{$item['accesskey']}'"
		: '';
	$label = isset($item['label']) ? $item['label'] : $item['name'];

	return "<a href='{$url}' class='{$class}' {$subtab} {$accesskey}>{$label}</a>";
}

/**
 * Print a button to clear all logs
 * @since  4.7
 */
function clear_logs_link($accesskey = null)
{
	$item =
		array(
			'label' => esc_html__('Clear Logs', 'litespeed-cache'),
			'url' => Utility::build_url(Router::ACTION_DEBUG2, Debug2::TYPE_CLEAR_LOG),
		);
	if (null !== $accesskey) {
		$item['accesskey'] = $accesskey;
	}
	echo subnav_link($item);
}

$subnav_links = array();
$log_views = array();

foreach ($logs as $log) {
	$subnav_links[] = subnav_link($log);

	$file = $this->cls('Debug2')->path($log['name']);
	$lines = File::count_lines($file);
	$start = $lines > 1000 ? $lines - 1000 : 0;
	$lines = File::read($file, $start);
	$lines = $lines ? trim(implode("\n", $lines)) : '';

	$log_views[] =
		"<div class='litespeed-log-view-wrapper' data-litespeed-sublayout='{$log['name']}_log'>"
		. "<h3 class='litespeed-title'>{$log['label']}</h3>"
		. '<div class="litespeed-log-body">'
		. nl2br(htmlspecialchars($lines))
		. '</div>'
		. '</div>';
}
?>

<h3 class="litespeed-title">
	<?php esc_html_e('LiteSpeed Logs', 'litespeed-cache'); ?>
	<?php Doc::learn_more('https://docs.litespeedtech.com/lscache/lscwp/toolbox/#log-view-tab'); ?>
</h3>

<div class="litespeed-log-subnav-wrapper">
	<?php echo implode("\n", $subnav_links); ?>
	<?php clear_logs_link('D'); ?>
</div>

<?php echo implode("\n", $log_views); ?>

<?php
clear_logs_link();
