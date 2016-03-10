<?php

/**
 * LiteSpeed Cache Plugin
 */
if (!defined('ABSPATH')) {
    die();
}

define('LSCACHE_ADV_CACHE', true);

/**
 * Because of the way it handles caching, the LiteSpeed Cache plugin for WordPress does not need an advanced-cache.php file.
 * For this reason, there is no real logic in this file. So why include it at all?
 * Setting the WP_CACHE global variable requires that an advanced-cache.php file exists.
 * This variable can help to increase compatibility as other plugins can check it to determine whether or not a cache is currently being used.
 * It can also help to avoid conflicts with other full page caches such as W3 Total Cache, etc.
 *
 */
return;