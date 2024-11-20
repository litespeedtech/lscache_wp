<?php

namespace LiteSpeed;

defined('WPINC') || exit;

$cloud_summary = Cloud::get_summary();

?>

<h3 class="litespeed-title-short">
	<?php echo __('Online Servcies', 'litespeed-cache'); ?>
	<?php Doc::learn_more('https://docs.litespeedtech.com/lscache/lscwp/online/'); ?>
</h3>

<div class="litespeed-callout notice notice-success inline">
	<h4><?php echo __('Current Cloud Nodes in Service', 'litespeed-cache'); ?>
		<a class="litespeed-right litespeed-redetect" href="<?php echo Utility::build_url(Router::ACTION_CLOUD, Cloud::TYPE_CLEAR_CLOUD); ?>" data-balloon-pos="up" data-balloon-break aria-label='<?php echo __('Click to clear all nodes for further redetection.', 'litespeed-cache'); ?>' data-litespeed-cfm="<?php echo __('Are you sure you want to clear all cloud nodes?', 'litespeed-cache'); ?>"><i class='litespeed-quic-icon'></i> <?php echo __('Redetect', 'litespeed-cache'); ?></a>
	</h4>
	<p>
		<?php
		$has_service = false;
		foreach (Cloud::$SERVICES as $svc) {
			if (isset($cloud_summary['server.' . $svc])) {
				$has_service = true;
				echo '<p><b>Service:</b> <code>' . $svc . '</code> <b>Node:</b> <code>' . $cloud_summary['server.' . $svc] . '</code> <b>Connected Date:</b> <code>' . Utility::readable_time($cloud_summary['server_date.' . $svc]) . '</code></p>';
			}
		}
		if (!$has_service) {
			echo __('No cloud services currently in use', 'litespeed-cache');
		}
		?>
	</p>
</div>