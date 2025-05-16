<?php
namespace LiteSpeed;

defined('WPINC') || exit();

$msg = __('LiteSpeed cache plugin upgraded. Please refresh the page to complete the configuration data upgrade.', 'litespeed-cache');

echo self::build_notice(self::NOTICE_BLUE, $msg);
