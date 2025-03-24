<?php

namespace LiteSpeed;

defined('WPINC') || exit;

$logs =
	array(
		array(
			'name' => 'debug',
			'label' => __('Debug Log', 'litespeed-cache'),
			'accesskey' => 'A',
		),
		array(
			'name' => 'purge',
			'label' => __('Purge Log', 'litespeed-cache'),
			'accesskey' => 'B',
		),
		array(
			'name' => 'crawler',
			'label' => __('Crawler Log', 'litespeed-cache'),
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

	$on_click = isset($item['onClick']) ? ' onClick="' . $item['onClick'].'"' : '';

	return "<a href='{$url}' class='{$class}' {$subtab} {$accesskey} {$on_click}>{$label}</a>";
}

/**
 * Print a button to clear all logs
 * @since  4.7
 */
function clear_logs_link($accesskey = null)
{
	$item =
		array(
			'label' => __('Clear Logs', 'litespeed-cache'),
			'url' => Utility::build_url(Router::ACTION_DEBUG2, Debug2::TYPE_CLEAR_LOG),
		);
	if (null !== $accesskey) {
		$item['accesskey'] = $accesskey;
	}
	echo subnav_link($item);
}

/**
 * Print a button to copy current log
 * @since  7.0
 */
function copy_logs_link($id_to_copy)
{
	$item = array(
			'name' => 'copy_links',
			'label' => __('Copy Log', 'litespeed-cache'),
			'cssClass' => 'litespeed-info-button',
			'onClick' => "litespeed_copy_to_clipboard('".$id_to_copy."')"
		);
	return subnav_link($item);
}

$subnav_links = array();
$log_views = array();

foreach ($logs as $log) {
	$subnav_links[] = subnav_link($log);

	$file = $this->cls('Debug2')->path($log['name']);
	$lines = File::count_lines($file);
	$max_lines = apply_filters('litespeed_debug_show_max_lines', 1000);
	$start = $lines > $max_lines ? $lines - $max_lines : 0;
	$lines = File::read($file, $start);
	$lines = $lines ? trim(implode("\n", $lines)) : '';
	
	$log_body_id = 'litespeed-log-' . $log['name'];

	$log_views[] =
		"<div class='litespeed-log-view-wrapper' data-litespeed-sublayout='{$log['name']}_log'>"
		. "<h3 class='litespeed-title'>{$log['label']}" . copy_logs_link($log_body_id) ."</h3>"
		. '<div class="litespeed-log-body" id="' . $log_body_id . '">'
		. nl2br(htmlspecialchars($lines))
		. '</div>'
		. '</div>';
}
?>

<h3 class="litespeed-title">
	<?php _e('LiteSpeed Logs', 'litespeed-cache'); ?>
	<?php Doc::learn_more('https://docs.litespeedtech.com/lscache/lscwp/toolbox/#log-view-tab'); ?>
</h3>

<div class="litespeed-log-subnav-wrapper">
	<?php echo implode("\n", $subnav_links); ?>
	<?php clear_logs_link('D'); ?>
</div>

<?php echo implode("\n", $log_views); ?>

<?php
clear_logs_link();
