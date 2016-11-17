<?php

/**
 * The registry for Third Party Plugins Integration files.
 *
 * This file is only used to include the integration files/classes.
 * This works as an entry point for the initial add_action for the
 * detect function.
 *
 * It is not required to add all integration files here, this just provides
 * a common place for plugin authors to append their file to.
 *
 */
if (!defined('ABSPATH')) {
    die();
}

include_once(dirname(__FILE__) . '/class-litespeed-cache-thirdparty-aelia-currencyswitcher.php');
include_once(dirname(__FILE__) . '/class-litespeed-cache-thirdparty-autoptimize.php');
include_once(dirname(__FILE__) . '/class-litespeed-cache-thirdparty-bbpress.php');
include_once(dirname(__FILE__) . '/class-litespeed-cache-thirdparty-betterwp-minify.php');
include_once(dirname(__FILE__) . '/class-litespeed-cache-thirdparty-nextgengallery.php');
include_once(dirname(__FILE__) . '/class-litespeed-cache-thirdparty-woocommerce.php');
include_once(dirname(__FILE__) . '/class-litespeed-cache-thirdparty-wp-polls.php');
include_once(dirname(__FILE__) . '/class-litespeed-cache-thirdparty-wptouch.php');

// This plugin's integration is sub optimal. Commented out until they use
// DOING_AJAX or provide a better alternative.
//include_once(dirname(__FILE__) . '/class-litespeed-cache-thirdparty-like-dislike-counter.php');



