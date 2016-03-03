=== LiteSpeed Cache  ===
Contributors: LiteSpeedTech
Tags: cache,performance,admin,widget,http2,litespeed
Requires at least: 3.3
Tested up to: 4.4
Stable tag: 1.0.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html
Quickly and easily implement high-performance page caching on your WordPress site with the ultra-efficient LiteSpeed Cache.

== Description ==
The LiteSpeed Cache Plugin for WordPress (LSCWP) is a PHP-based plugin that communicates with your installation of LiteSpeed Web Server(LSWS) and its built-in page cache, LSCache. 
Because LSCache is built directly into LSWS, overhead is significantly reduced and caching can be done more efficiently than with other PHP-based caches.

Additional plugin features:

* Automatic page caching greatly improves site performance
* Automatically purge related pages based on certain events
* Support for HTTP/2 & HTTPS out-of-box

For support visit our [LiteSpeed Forums](https://www.litespeedtech.com/support/forum/ "forums"), [LiteSpeedWiki](https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp "wiki"), or email us at info@litespeedtech.com

== Installation ==
1. In LiteSpeed Web Server - Through the WebAdmin Console, navigate to Configuration > Server > cache and set 'Storage path' under Cache Storage Settings to something like '/tmp/wpcache/' for example. If the directory does not already exist, create it and make it server readable/writable.
2. In LiteSpeed Web Server - Under "Cache Policy" set the following: 'Enable Public Cache' - No, 'Check Public Cache' - Yes, 'Ignore Request Cache-Control' - Yes.
3. In LiteSpeed Web Server - Perform a Graceful Restart.
4. Upload 'litespeed-cache.zip' to the '/wp-content/plugins/' directory
5. Disable any other page caches as these will interfere with the LiteSpeed Cache Plugin.
6. Activate the LiteSpeed Cache plugin through the 'Plugins' screen in WordPress

== Frequently Asked Questions ==
= Is the LiteSpeed Cache Plugin for WordPress free? =
Yes, the plugin itself will remain free and open source, but only works with LiteSpeed Web Server 5.0.10+. You are required to have a LiteSpeed Web Server license with the LSCache module enabled.
= Where are the cached files stored? =
This plugin only instructs LiteSpeed Web Server on what pages to cache and when to purge. The actual cached pages are stored and managed by LiteSpeed Web Server. Nothing is stored on the PHP side.
= Does LiteSpeed Cache for WordPress work with OpenLiteSpeed? =
LiteSpeed Cache for WordPress currently only works for LiteSpeed Web Server enterprise edition, but there are plans to have OpenLiteSpeed support it later down the line.
= How do I get WP-PostViews to display an updating view count? =
1. Use: `<div id="postviews_lscwp"></div>`

    to replace

    `<?php if(function_exists('the_views')) { the_views(); } ?>`

    * NOTE: The id can be changed, but the div id and the ajax function must match.
2. Replace the ajax query in `wp-content/plugins/wp-postviews/postviews-cache.js` with

    ```
    jQuery.ajax({
        type:"GET",
        url:viewsCacheL10n.admin_ajax_url,
        data:"postviews_id="+viewsCacheL10n.post_id+"&action=postviews",
        cache:!1,
        success:function(data) {
            if(data) {
                jQuery('#postviews_lscwp').html(data+' views');
            }
       }
    });
    ```

3. Purge the cache to use the updated pages.

== Changelog ==
= 1.0.0 =
* Initial Release.
