<?php
if (!defined('WPINC')) die;

?>
<h3 class="litespeed-title"><?php echo __('Network Do Not Cache Rules', 'litespeed-cache'); ?></h3>

<!-- User Agent List -->
<?php require LSWCP_DIR . 'admin/tpl/settings_inc.exclude_useragent.php'; ?>

<!-- Cookie List -->
<?php require LSWCP_DIR . 'admin/tpl/settings_inc.exclude_cookies.php'; ?>
