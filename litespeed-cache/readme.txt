=== LiteSpeed Cache  ===
Contributors: LiteSpeedTech
Tags: cache,performance,admin,widget,http2,litespeed
Requires at least: 4.0
Tested up to: 4.5
Stable tag: 1.0.4
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
* Single Site and Multi Site support
* Supports WooCommerce and bbPress
* Can cache desktop and mobile views separately
* Allows configuration for do-not-cache by URI, Categories, Tags, Cookies, and User Agents

For support visit our [LiteSpeed Forums](https://www.litespeedtech.com/support/forum/ "forums"), [LiteSpeedWiki](https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp "wiki"), or email us at info@litespeedtech.com

== Installation ==
1. Make sure that your license has the LSCache module enabled. You can [try our 2-CPU trial license with LSCache module](https://www.litespeedtech.com/products/litespeed-web-server/download/get-a-trial-license "trial license") free for 15-days.
2. In LiteSpeed Web Server - Through the WebAdmin Console, navigate to Configuration > Server > cache and set 'Storage path' under Cache Storage Settings to a fast disk, where the path can be something like '/tmp/wpcache/' for example. If the directory does not already exist, it will be created for you.
3. In LiteSpeed Web Server - Under "Cache Policy" set the following: <br>
Enable Public Cache - No<br> 
Check Public Cache - Yes<br>
Cache Request with Query String - Yes<br>
Cache Request with Cookie - Yes<br>
Cache Response with Cookie - Yes<br>
Ignore Request Cache-Control - Yes<br>
Ignore Response Cache-Control - Yes
4. In LiteSpeed Web Server - Perform a Graceful Restart.
5. Upload 'litespeed-cache.zip' to the '/wp-content/plugins/' directory.
6. Disable any other page caches as these will interfere with the LiteSpeed Cache Plugin.
7. Activate the LiteSpeed Cache plugin through the 'Plugins' screen in WordPress.
8. For more detailed information, visit our [LSCWP Wiki](https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp "lscwp wiki").

== Frequently Asked Questions ==
= Is the LiteSpeed Cache Plugin for WordPress free? =
Yes, the plugin itself will remain free and open source, but only works with LiteSpeed Web Server 5.0.10+. You are required to have a LiteSpeed Web Server license with the LSCache module enabled.
= Where are the cached files stored? =
This plugin only instructs LiteSpeed Web Server on what pages to cache and when to purge. The actual cached pages are stored and managed by LiteSpeed Web Server. Nothing is stored on the PHP side.
= Does LiteSpeed Cache for WordPress work with OpenLiteSpeed? =
LiteSpeed Cache for WordPress currently only works for LiteSpeed Web Server enterprise edition, but there are plans to have OpenLiteSpeed support it later down the line.
= Is WooCommerce supported? =
In short, yes. For WooCommerce versions 1.4.2 and above, this plugin will not cache the pages that WooCommerce deems non-cacheable. For versions below 1.4.2, we do extra checks to make sure that 
pages are cacheable. We are always looking for feedback, so if you encounter any problems, be sure to send us a support question.
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
= 1.0.4 =
* Added logic to cache commenters.
* Added htaccess backup to the install script.
* Added an htaccess editor in the wp-admin dashboard.
* Added do not cache user agents.
* Added do not cache cookies.
* Created new LiteSpeed Cache Settings submenu entries.
* Implemented Separate Mobile View.
* Modified WP_CACHE not defined message to only show up for users who can manage options.
* Moved enabled all/disable all from network management to network settings.
* Fixed a bug where WP_CACHE was not defined on activation if it was commented out.

= 1.0.3 =
* Added a Purge Front Page button to the LiteSpeed Cache Management page.
* Added a Default Front Page TTL option to the general settings.
* Added ability to define web application specific cookie names through rewrite rules to handle logged-in cookie conflicts when using multiple web applications. <strong>[Requires LSWS 5.0.15+]</strong>
* Improved WooCommerce handling.
* Fixed a bug where activating lscwp sets the “enable cache” radio button to enabled, but the cache was not enabled by default.
* Refactored code to make it cleaner.
* Updated readme.txt.

= 1.0.2 =
* Added a "Use Network Admin Setting" option for "Enable LiteSpeed Cache". For single sites, this choice will default to enabled.
* Added enable/disable all buttons for network admin. This controls the setting of all managed sites with "Use Network Admin Setting" selected for "Enable LiteSpeed Cache".
* Exclude by Category/Tag are now text areas to avoid slow load times on the LiteSpeed Cache Settings page for sites with a large number of categories/tags.
* Added a new line to advanced-cache.php to allow identification as a LiteSpeed Cache file.
* Activation/Deactivation are now better handled in multi-site environments.
* Enable LiteSpeed Cache setting is now a radio button selection instead of a single checkbox.
* Can now add '$' to the end of a URL in Exclude URI to perform an exact match.
* The _lscache_vary cookie will now be deleted upon logout.
* Fixed a bug in multi-site setups that would cause a "function already defined" error.

= 1.0.1 =
* Added Do Not Cache by URI, by Category, and by Tag.  URI is a prefix/string equals match.
* Added a help tab for plugin compatibilities.
* Created logic for other plugins to purge a single post if updated.
* Fixed a bug where woocommerce pages that display the cart were cached.
* Fixed a bug where admin menus in multi-site setups were not correctly displayed.
* Fixed a bug where logged in users were served public cached pages.
* Fixed a compatibility bug with bbPress.  If there is a new forum/topic/reply, the parent pages will now be purged as well.
* Fixed a bug that didn't allow cron job to update scheduled posts.

= 1.0.0 =
* Initial Release.
