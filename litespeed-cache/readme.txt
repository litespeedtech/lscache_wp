=== LiteSpeed Cache  ===
Contributors: LiteSpeedTech
Tags: cache,performance,admin,widget,http2,litespeed
Requires at least: 4.0
Tested up to: 4.5
Stable tag: 1.0.7.1
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html
Quickly and easily implement high-performance page caching on your WordPress site with the ultra-efficient LiteSpeed Cache.

== Description ==
The LiteSpeed Cache Plugin for WordPress (LSCWP) is a PHP-based plugin that communicates with your installation of LiteSpeed Web Server (LSWS) and its built-in page cache, LSCache. 
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
= Instructions for LiteSpeed Web Server Enterprise =
1. Make sure that your license has the LSCache module enabled. 
You can [try our 2-CPU trial license with LSCache module](https://www.litespeedtech.com/products/litespeed-web-server/download/get-a-trial-license "trial license") 
free for 15-days.
2. In LiteSpeed Web Server - Through the WebAdmin Console, navigate to Configuration > Server > cache and set 'Storage path' under Cache Storage Settings to a fast disk, where the path can be something like '/tmp/wpcache/' for example. If the directory does not already exist, it will be created for you.
3. In LiteSpeed Web Server - Under "Cache Policy" set the following: <br>
Enable Public Cache - No<br> 
Check Public Cache - Yes<br>
Ignore Request Cache-Control - Yes<br>
Ignore Response Cache-Control - Yes
4. In LiteSpeed Web Server - Perform a Graceful Restart.
5. Upload 'litespeed-cache.zip' to the '/wp-content/plugins/' directory.
6. Disable any other page caches as these will interfere with the LiteSpeed Cache Plugin.
7. Activate the LiteSpeed Cache plugin through the 'Plugins' screen in WordPress.
8. For more detailed information, visit our [LSCWP Wiki](https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp "lscwp wiki").

= Instructions for OpenLiteSpeed =
BETA. TO BE COMPLETED IN THE NEAR FUTURE.

== Frequently Asked Questions ==
= Is the LiteSpeed Cache Plugin for WordPress free? =
Yes, the plugin itself will remain free and open source. You are required to 
have a LiteSpeed Web Server Enterprise 5.0.10+ license with the LSCache module 
(included with 2+ CPU license, an addon for 1 CPU and VPS licenses). 
OpenLiteSpeed v 1.4.17+ also works with the plugin, 
but the functionality is currently in beta.
= Where are the cached files stored? =
This plugin only instructs LiteSpeed Web Server on what pages to cache and 
when to purge. The actual cached pages are stored and managed by 
LiteSpeed Web Server. Nothing is stored on the PHP side.
= Does LiteSpeed Cache for WordPress work with OpenLiteSpeed? =
The support is currently in beta. It should work, but is not fully tested.
As well, any settings changes that require modifying the .htaccess file requires a server restart.
= Is WooCommerce supported? =
In short, yes. For WooCommerce versions 1.4.2 and above, this plugin will not 
cache the pages that WooCommerce deems non-cacheable. For versions below 1.4.2, 
we do extra checks to make sure that pages are cacheable. 
We are always looking for feedback, so if you encounter any problems, 
be sure to send us a support question.
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

== Plugin Developers ==

Any WP plugin that populates front end content that can be publicly cached
should work with LSCache.

However if the plugin needs to update some data and the cache does not
automatically purge the cached page, you may be required to write an
integration script to remedy this. In addition to this section, there is a template file and a
few examples of plugins that required integration scripts if additional
resources are needed.

= How It Works =

LSCache works by tagging each cacheable page. In its most basic form,
each page is tagged with its Post ID, then sent to the server to be cached.
When someone makes a change to the page, that request will notify the server
to purge the cached items associated with that page's post id.

This integration framework enables any plugin developer to customize the
notifications sent to the server. It is possible to tag the page with
identifiers as the page is stored in the cache. Later, if needed, the tag
system provides a simple way to purge the cache of a certain subset of pages.
Multiple tags can be set on a single page, and a single tag may be used on
multiple pages. This many to many mapping provides a flexible system enabling
you to group pages in many ways.

For example, a page may be tagged with `MTPP_F.1 (forum), MTPP_G.4 (group),
MTPP_S.georgia (state)` because the page is in forum 1, group 4, and related to
the state of Georgia. Then another page is tagged `MTPP_F.1, MTPP_G.2,
MTPP_S.iowa`. If a change is made where all pages tagged `MTPP_F.1` need to be
purged, the tag system makes it easy to purge the specific pages.

A post will automatically be purged if the following events are triggered:

 * edit_post
 * save_post
 * deleted_post
 * trashed_post
 * delete_attachment

These cases cover most situations in which a cache purge is necessary.
If all the correct pages are purged, there may be no need to add additional
tags.

Another application for creating a third party integration class is to notify
LSCache if the plugin generates private/transient data that cannot be cached
for certain responses. Below is a list of what is already considered
non-cacheable.

A post is considered non cacheable if…

 * It is an admin page
 * The user is logged in
 * It is a post request
 * is_feed() is true
 * is_trackback() is true
 * is_404() is true
 * is_search() is true
 * No theme is used
 * The URI matches any of the do not cache URI config
 * The post has a category matching the do not cache category config
 * The post has a tag matching the do not cache tag config
 * The request has a cookie matching the do not cache cookie config
 * The request has a user agent matching the do not cache user agent config

= Components =

1. A class to handle the compatibility code. A template is available below.
2. Initiator for the class. Can be in the plugin's own file space or appended
to the registry.

= API/Functions =

The following functions may be used at any hook point prior to the 'shutdown'
hook point.

* **The $tag parameter**

  This parameter is used to distinguish pages. Generally speaking, there are two
components to the tag, the name and the ID. That said, any number of components
are allowed. Each component should be separated via a period, '.'.

  The name should be short, but unique. As an example, if the plugin
MySlideShowPlugin has a class SlideShow, it might use `MSSP_SS.1`.


* **LiteSpeed_Cache_Tags::add_cache_tag(_$tag_)**

  Adds a single or group of cache tags to the list of cache tags associated with
the current page. These will be appended to the LiteSpeed Cache Plugin
generated list of cache tags. This may be useful to purge by a custom tag
rather than resorting to the WordPress site wide tags.

  * **Parameters**

     *$tag* `String/Array` A (list of) cache tag(s) to associate with the page.

* **LiteSpeed_Cache_Tags::add_purge_tag(_$tag_)**

  Adds a single or group of purge tags to the list of tags to be purged with the
request. This may be useful for situations where another plugin needs to purge
custom cache tags or associated pages.

  * **Parameters**

     *$tag* `String/Array` A (list of) purge tag(s) to append to the list.

= Hook Points =

These hook points are provided for hooking into the cache's run time
functionality. It is not required to hook into any of these hook points;
these are provided more for convenience. It is possible that a plugin only
needs to hook into its own hook points.

* **Action - litespeed_cache_detect_thirdparty**

  This action may be used to check if it is necessary to add any further
functionality to the current request. For example, if a user visits a shopping
page, there is no need for the forum plugin to do its extra checks/add its tags
because the page is unrelated.

  If, however, the callback determines that it is a forum page,
it is recommended to add any needed actions/filters in the callback.

* **Filter - litespeed_cache_is_cacheable($cacheable)**

  Triggered when the cache plugin is checking if the current page is cacheable.
This filter will not trigger on admin pages nor regular pages when visited by a
logged in user.

  * **Parameters**

     *$cacheable* `boolean` Represents whether a previous filter determined the
cache to be cacheable or not. If this is false, it is strongly recommended to
return false immediately to optimize performance.

  * **Returns**

     `boolean` True if cacheable, false otherwise. If *$cacheable* was false and
true is returned, the result is undefined behavior.

* **Action - litespeed_cache_on_purge_post**

  Triggered when a post is about to be purged. Use this hook point if purging a
page should purge other pages related to your plugin.

  An example use case: Suppose a user replies to a forum topic. This will cause
the forum topic to be purged. When the plugin is about to notify the server,
the forum plugin will see that the cache is about to purge the topic page.
The plugin then adds purge tags, notifying the server to purge the forum and
forum list as well. This ensures that all related pages are up to date.

* **Action - litespeed_cache_add_purge_tags**

  Called at the end of every request. This hook provides an access point to any
plugin that needs to add purge tags to the current request.

* **Action - litespeed_cache_add_cache_tags**

  Called at the end of every cacheable request. This hook provides an access
point to any plugin that needs to add cache tags to the current request.

== Changelog ==
= 1.0.7.1 =
* Fixed a bug where enabling purge all in the auto purge on update settings page did not purge the correct blogs. 
* Fixed a bug reported by user wpc on our forums where enabling purge all in the auto purge on update settings page caused nothing to be cached.

= 1.0.7 =
* Added login cookie configuration to the Advanced Settings page.
* Added support for WPTouch plugin.
* Added support for WP-Polls plugin.
* Added Like Dislike Counter third party integration.
* Added support for Admin IP Query String Actions.
* Added confirmation pop up for purge all.
* Refactor: LiteSpeed_Cache_Admin is now split into LiteSpeed_Cache_Admin, LiteSpeed_Cache_Admin_Display, and LiteSpeed_Cache_Admin_Rules
* Refactor: Rename functions to accurately represent their functionality
* Fixed a bug that sometimes caused a “no valid header” error message.

= 1.0.6 =
* Fixed a bug reported by Knut Sparhell that prevented dashboard widgets from being opened or closed.
* Fixed a bug reported by Knut Sparhell that caused problems with https support for admin pages.

= 1.0.5 =
* [BETA] Added NextGen Gallery plugin support.
* Added third party plugin integration.
* Improved cache tag system.
* Improved formatting for admin settings pages.
* Converted bbPress to use the new third party integration system.
* Converted WooCommerce to use the new third party integration system.
* If .htaccess is not writable, disable separate mobile view and do not cache cookies/user agents.
* Cache is now automatically purged when disabled.
* Fixed a bug where .htaccess was not checked properly when adding common rules.
* Fixed a bug where multisite setups would be completely purged when one site requested a purge all.

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
