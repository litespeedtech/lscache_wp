=== LiteSpeed Cache  ===
Contributors: LiteSpeedTech
Tags: caching, cache, performance, optimization, wp-cache, busting, wordpress cache busting, litespeed, http2, varnish, widget, litespeed web server, lsws, availability, pagespeed, woocommerce, bbpress, nextgengallery, wp-polls, wptouch, customization, plugin, rewrite, scalability, speed, multisite, cpanel, openlitespeed, ols, google, optimize, wp-super-cache, w3total cache, w3totalcache, w3 total cache, wp super cache, wp rocket
Requires at least: 4.0
Tested up to: 4.8
Stable tag: 1.1.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html

Quickly and easily implement high-performance page caching on your WordPress site with the ultra-efficient LiteSpeed Cache.

== Description ==

The LiteSpeed Cache Plugin for WordPress (LSCWP) is a PHP-based plugin that communicates with your installation of LiteSpeed Web Server (LSWS) and its built-in page cache, LSCache.

Because LSCache is built directly into LSWS, overhead is significantly reduced and caching can be done more efficiently than with other PHP-based caches.

= Installation =

1. Install `LiteSpeed Web Server Enterprise` or `OpenLiteSeed`[Free].

2. Install `LiteSpeed Cache` and activate.

3. Goto `LiteSpeed Cache` -> `Settings`, set option `Enable LiteSpeed Cache` to `Enable`.

= Instructions for LiteSpeed Web Server Enterprise =

1. Make sure that your license includes the LSCache module enabled. A [2-CPU trial license with LSCache module](https://www.litespeedtech.com/products/litespeed-web-server/download/get-a-trial-license "trial license") is available for free for 15 days.

2. The server must be configured to have caching enabled. If you are the server admin, [click here](https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:common_installation#web_server_configuration). Otherwise request that the server admin configure the cache root for the server.

3. In the .htaccess file for the WordPress installation, add the following:
`<IfModule LiteSpeed>
   CacheLookup public on
</IfModule>`

= Instructions for OpenLiteSpeed =

* This integration utilizes OLS's cache module.

* If it is a fresh OLS installation, the easiest way to integrate is to use [ols1clk](http://open.litespeedtech.com/mediawiki/index.php/Help:1-Click_Install). If using an existing WordPress installation, use the --wordpresspath parameter.

* Else if OLS and WordPress are already installed, please follow the instructions [here](http://open.litespeedtech.com/mediawiki/index.php Help:How_To_Set_Up_LSCache_For_WordPress).

Additional plugin features:

* Automatic page caching greatly improves site performance
* Automatically purge related pages based on certain events
* Support for HTTP/2 & HTTPS out-of-box
* Single Site and Multi Site support
* Supports WooCommerce and bbPress
* Has an API system that enables other plugins to easily integrate with the cache plugin.
* Can cache desktop and mobile views separately
* Allows configuration for do-not-cache by URI, Categories, Tags, Cookies, and User Agents
* Works with LiteSpeed Web ADC in clustered environments.

= Known Compatible Plugins =

* [bbPress](https://wordpress.org/plugins/bbpress/)
* [WooCommerce](https://wordpress.org/plugins/woocommerce/)
* [Contact Form 7](https://wordpress.org/plugins/contact-form-7/)
* [Google XML Sitemaps](https://wordpress.org/plugins/google-sitemap-generator/)
* [Yoast SEO](https://wordpress.org/plugins/wordpress-seo/)
* [Wordfence Security](https://wordpress.org/plugins/wordfence/)
* [NextGen Gallery](https://wordpress.org/plugins/nextgen-gallery/)
* <a href="https://shortpixel.com/h/af/CXNO4OI28044" rel="friend noopener noreferer" target="_blank">ShortPixel</a>
* Aelia CurrencySwitcher
* [Fast Velocity Minify](https://wordpress.org/plugins/fast-velocity-minify/) - Thanks Raul Peixoto!
* [Autoptimize](https://wordpress.org/plugins/autoptimize/)
* [Better WP Minify](https://wordpress.org/plugins/bwp-minify/)
* [WP Touch](https://wordpress.org/plugins/wptouch/)
* [Theme My Login](https://wordpress.org/plugins/theme-my-login/)
* [wpForo](https://wordpress.org/plugins/wpforo/)
* [WPLister](https://www.wplab.com/plugins/wp-lister/)
* [Avada](https://avada.theme-fusion.com/)
* [WP-PostRatings](https://wordpress.org/plugins/wp-postratings/)

= Known Uncompatible Plugins =

* No known uncompatible plugins at this time.

For support visit our [LiteSpeed Forums](https://www.litespeedtech.com/support/forum/ "forums"), [LiteSpeedWiki](https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp "wiki"), or email us at info@litespeedtech.com

= How to test the plugin =

The LiteSpeed Cache Plugin utilizes LiteSpeed specific response headers. Visiting a page for the first time should result in a `X-LiteSpeed-Cache-Control:miss` or `X-LiteSpeed-Cache-Control:no-cache` response header for the page. Subsequent requests should have the `X-LiteSpeed-Cache-Control:hit` response header until the page is updated, expired, or purged. Please visit [this page](https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:installation#testing) for more information.

== Frequently Asked Questions ==

= Is the LiteSpeed Cache Plugin for WordPress free? =

Yes, the plugin itself will remain free and open source. That said, a LiteSpeed server is required (see question 2).

= What server software is required for this plugin? =

A LiteSpeed server is required in order to use this plugin.

1. LiteSPeed Web Server Enterprise with LSCache Module (v5.0.10+)

2. OpenLiteSpeed (v1.4.17+)

3. LiteSpeed WebADC (v2.0+)

Any single server or cluster including a LiteSpeed server will work.

= Does this plugin work in a clustered environment? =

The cache entries are stored at the litespeed server level. The simplest solution is to use LiteSpeed WebADC, as the cache entries will be cached at that level.

If using another load balancer, the cache entries will only be stored at the backend nodes, not at the load balancer.

The purges will also not be synchronized across the nodes, so this is not recommended.

If a customized solution is required, please contact LiteSpeed Technologies at info@litespeedtech.com

NOTICE: The rewrite rules created by this plugin must be copied to the Load Balancer.

= Where are the cached files stored? =

The actual cached pages are stored and managed by LiteSpeed Servers.

Nothing is stored on the PHP side.

= Does LiteSpeed Cache for WordPress work with OpenLiteSpeed? =

Yes it can work well with OpenLiteSpeed. As well, any settings changes that require modifying the .htaccess file requires a server restart.

= Is WooCommerce supported? =

In short, yes. However, for some woocommerce themes, the cart may not be updated correctly.

To test the cart:

1. On a non-logged-in browser, visit and cache a page, then visit and cache a product page.

2. The first page should be accessible from the product page (e.g. the shop).

3. Once both pages are confirmed cached, add the product to your cart.

4. After adding to the cart, visit the first page.

5. The page should still be cached, and the cart should be up to date.

6. If that is not the case, please add woocommerce_items_in_cart to the do not cache cookie list.

Some themes like Storefront and Shop Isle are built such that the cart works without the rule.

However, other themes like the E-Commerce theme, do not, so please verify the theme used.

= My plugin has some pages that are not cacheable. How do I instruct the LiteSpeed Cache Plugin to not cache the page? =

As of version 1.0.10, you may simply add `define('LSCACHE_NO_CACHE', true);` sometime before the shutdown hook, and it should be recognized by the cache.

Alternatively, you may use the function `LiteSpeed_Cache_Tags::set_noncacheable();` for earlier versions (1.0.7+).

If using the function, make sure to check that the class exists prior to using the function.

Please visit the [Other Notes tab](https://wordpress.org/plugins/litespeed-cache/other_notes/) for more information.

= Are my images optimized? =

The cache plugin does not do anything with the images themselves.

We recommend you trying an image optimization plugin like <a href="https://shortpixel.com/h/af/CXNO4OI28044" rel="friend noopener noreferer" target="_blank">ShortPixel</a> to optimize your images. It can reduce your site's images up to 90%.

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

= How do I enable the crawler? =

The crawler is disabled by default, and must be enabled by the server admin first.

Then, you can enable crawler from "LiteSpeed Cache" -> "Crawler".

For more detailed information about crawler setup, please see [our blog post on the subject](https://blog.litespeedtech.com/2017/06/14/wpw-crawl-your-site-make-it-fly/).


== Plugin Developers ==

Any WP plugin that populates front end content that can be publicly cached should work with LSCache.

However if the plugin needs to update some data and the cache does not automatically purge the cached page, you may be required to write an integration script to remedy this. In addition to this section, there is a template file and a few examples of plugins that required integration scripts if additional resources are needed.

= Version 1.0.10+ =

If your plugin needs to set the current page as non cacheable, the simplest way to instruct the cache plugin to not cache the page is `define('LSCACHE_NO_CACHE', true);`

This must be defined prior to the shutdown hook to ensure that it is recognized.

= How It Works =

LSCache works by tagging each cacheable page. In its most basic form, each page is tagged with its Post ID, then sent to the server to be cached. When someone makes a change to the page, that request will notify the server to purge the cached items associated with that page's post id.

This integration framework enables any plugin developer to customize the notifications sent to the server. It is possible to tag the page with identifiers as the page is stored in the cache. Later, if needed, the tag system provides a simple way to purge the cache of a certain subset of pages. Multiple tags can be set on a single page, and a single tag may be used on multiple pages. This many to many mapping provides a flexible system enabling you to group pages in many ways.

For example, a page may be tagged with `MTPP_F.1 (forum), MTPP_G.4 (group), MTPP_S.georgia (state)` because the page is in forum 1, group 4, and related to the state of Georgia. Then another page is tagged `MTPP_F.1, MTPP_G.2, MTPP_S.iowa`. If a change is made where all pages tagged `MTPP_F.1` need to be purged, the tag system makes it easy to purge the specific pages.

A post will automatically be purged if the following events are triggered:

 * edit_post
 * save_post
 * deleted_post
 * trashed_post
 * delete_attachment

These cases cover most situations in which a cache purge is necessary. If all the correct pages are purged, there may be no need to add additional tags.

Another application for creating a third party integration class is to notify LSCache if the plugin generates private/transient data that cannot be cached for certain responses. Below is a list of what is already considered non-cacheable.

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

2. Initiator for the class. Can be in the plugin's own file space or appended to the registry.

= API/Functions =

The following functions may be used at any hook point prior to the 'shutdown' hook point.

* **The $tag parameter**

  This parameter is used to distinguish pages. Generally speaking, there are two components to the tag, the name and the ID. That said, any number of components are allowed. Each component should be separated via a period, '.'.

  The name should be short, but unique. As an example, if the plugin MySlideShowPlugin has a class SlideShow, it might use `MSSP_SS.1`.

* **LiteSpeed_Cache_Tags::add_cache_tag(_$tag_)**

  Adds a single or group of cache tags to the list of cache tags associated with the current page. These will be appended to the LiteSpeed Cache Plugin generated list of cache tags. This may be useful to purge by a custom tag rather than resorting to the WordPress site wide tags.

  * **Parameters**

     *$tag* `String/Array` A (list of) cache tag(s) to associate with the page.

* **LiteSpeed_Cache_Tags::add_purge_tag(_$tag_)**

  Adds a single or group of purge tags to the list of tags to be purged with the request. This may be useful for situations where another plugin needs to purge custom cache tags or associated pages.

  * **Parameters**

     *$tag* `String/Array` A (list of) purge tag(s) to append to the list.

= Hook Points =

These hook points are provided for hooking into the cache's run time functionality. It is not required to hook into any of these hook points; these are provided more for convenience. It is possible that a plugin only needs to hook into its own hook points.

* **Action - litespeed_cache_detect_thirdparty**

  This action may be used to check if it is necessary to add any further functionality to the current request. For example, if a user visits a shopping page, there is no need for the forum plugin to do its extra checks/add its tags because the page is unrelated.

  If, however, the callback determines that it is a forum page, it is recommended to add any needed actions/filters in the callback.

* **Filter - litespeed_cache_is_cacheable($cacheable)**

  Triggered when the cache plugin is checking if the current page is cacheable. This filter will not trigger on admin pages nor regular pages when visited by a logged in user.

  * **Parameters**

     *$cacheable* `boolean` Represents whether a previous filter determined the cache to be cacheable or not. If this is false, it is strongly recommended to return false immediately to optimize performance.

  * **Returns**

     `boolean` True if cacheable, false otherwise. If *$cacheable* was false and true is returned, the result is undefined behavior.

* **Action - litespeed_cache_on_purge_post**

  Triggered when a post is about to be purged. Use this hook point if purging a page should purge other pages related to your plugin.

  An example use case: Suppose a user replies to a forum topic. This will cause the forum topic to be purged. When the plugin is about to notify the server, the forum plugin will see that the cache is about to purge the topic page. The plugin then adds purge tags, notifying the server to purge the forum and forum list as well. This ensures that all related pages are up to date.

* **Action - litespeed_cache_add_purge_tags**

  Called at the end of every request. This hook provides an access point to any plugin that needs to add purge tags to the current request.

* **Action - litespeed_cache_add_cache_tags**

  Called at the end of every cacheable request. This hook provides an access point to any plugin that needs to add cache tags to the current request.

== Screenshots ==

1. Admin Settings - General Settings
2. Admin Settings - Auto Purge Rules
3. Admin Management Page
4. Admin Information Page
5. Admin Settings - Crawler Settings
6. Admin Crawler Status Page
7. Cache Miss Example
8. Cache Hit Example

== Changelog ==

= 1.1.2 - June 20 2017 =
* [BUGFIX] Fixed missing form close tag;
* [UPDATE] Added a wiki link for enabling the crawler.
* [UPDATE] Improved Site IP description.
* [UPDATE] Added an introduction to the crawler on the Information page;
* [REFACTOR] Added more detailed error messages for Site IP and Custom Sitemap settings;

= 1.1.1.1 - June 15 2017 =
* [BUGFIX] Hotfix for insufficient validation of site IP value in crawler settings.

= 1.1.1 - June 15 2017 =
* [NEW] As of LiteSpeed Web Server v.5.1.16, the crawler can now be enabled/disabled at the server level.
* [NEW] Added the ability to provide a custom sitemap for crawling.
* [NEW] Added ability to use site IP address directly in crawler settings.
* [NEW] Crawler performance improved with the use of new custom user agent 'lsrunner'.
* [NEW] "Purge By URLs" now supports full URL paths.
* [NEW] Added thirdparty WP-PostRatings compatibility;
* [BUGFIX] Cache is now cleared when changing post status from published to draft.
* [BUGFIX] WHM activation message no longer continues to reappear after being dismissed.
* [COSMETIC] Display recommended values for settings.

= 1.1.0.1 - June 8 2017 =
* [UPDATE] Improved default crawler interval setting.
* [UPDATE] Tested up to WP 4.8.
* [BUGFIX] Fixed compatibility with plugins that output json data.
* [BUGFIX] Fixed tab switching bug.
* [BUGFIX] Removed occasional duplicated messages on save.
* [COSMETIC] Improved crawler tooltips and descriptions.

= 1.1.0 - June 6 2017 =
* [NEW] Added a crawler - this includes configuration options and a dedicated admin page. Uses wp-cron
* [NEW] Added integration for WPLister
* [NEW] Added integration for Avada
* [UPDATE] General structure of the plugin revamped
* [UPDATE] Improved look of admin pages
* [BUGFIX] Fix any/all wp-content path retrieval issues
* [BUGFIX] Use realpath to clear symbolic link when determining .htaccess paths
* [BUGFIX] Fixed a bug where upgrading multiple plugins did not trigger a purge all
* [BUGFIX] Fixed a bug where cli import_options did not actually update the options.
* [REFACTOR] Most of the files in the code were split into more, smaller files

= 1.0.15 - April 20 2017 =
* [NEW] Added Purge Pages and Purge Recent Posts Widget pages options.
* [NEW] Added wp-cli command for setting and getting options.
* [NEW] Added an import/export options cli command.
* [NEW] Added wpForo integration.
* [NEW] Added Theme My Login integration.
* [UPDATE] Purge adjacent posts when publish a new post.
* [UPDATE] Change environment report file to .php and increase security.
* [UPDATE] Added new purgeby option to wp-cli.
* [UPDATE] Remove nag for multiple sites.
* [UPDATE] Only inject LiteSpeed javascripts in LiteSpeed pages.
* [REFACTOR] Properly check for zero in ttl settings.
* [BUGFIX] Fixed the 404 issue that can be caused by some certain plugins when save the settings.
* [BUGFIX] Fixed mu-plugin compatibility.
* [BUGFIX] Fixed problem with creating zip backup.
* [BUGFIX] Fixed conflict with jetpack.

= 1.0.14.1 - January 31 2017 =
* [UPDATE] Removed Freemius integration due to feedback.

= 1.0.14 - January 30 2017 =
* [NEW] Added error page caching. Currently supports 403, 404, 500s.
* [NEW] Added a purge errors action.
* [NEW] Added wp-cli integration.
* [UPDATE] Added support for multiple varies.
* [UPDATE] Reorganize the admin interface to be less cluttered.
* [UPDATE] Add support for LiteSpeed Web ADC.
* [UPDATE] Add Freemius integration.
* [REFACTOR] Made some changes so that the rewrite rules are a little more consistent.
* [BUGFIX] Check member type before adding purge all button.
* [BUGFIX] Fixed a bug where activating/deactivating the plugin quickly caused the WP_CACHE error to show up.
* [BUGFIX] Handle more characters in the rewrite parser.
* [BUGFIX] Correctly purge posts when they are made public/private.

= 1.0.13.1 - November 30 2016 =
* [BUGFIX] Fixed a bug where a global was being used without checking existence first, causing unnecessary log entries.

= 1.0.13 - November 28 2016 =
* [NEW] Add an Empty Entire Cache button.
* [NEW] Add stale logic to certain purge actions.
* [NEW] Add option to use primary site settings for all subsites in a multisite environment.
* [NEW] Add support for Aelia CurrencySwitcher
* [UPDATE] Add logic to allow third party vary headers
* [UPDATE] Handle password protected pages differently.
* [BUGFIX] Fixed bug caused by saving settings.
* [BUGFIX] FIxed bug when searching for advanced-cache.php

= 1.0.12 - November 14 2016 =
* [NEW] Added logic to generate environment reports.
* [NEW] Created a notice that will be triggered when the WHM Plugin installs this plugin. This will notify users when the plugin is installed by their server admin.
* [NEW] Added the option to cache 404 pages via 404 Page TTL setting.
* [NEW] Reworked log system to be based on selection of yes or no instead of log level.
* [NEW] Added support for Autoptimize.
* [NEW] Added Better WP Minify integration.
* [UPDATE] On plugin disable, clear .htaccess.
* [UPDATE] Introduced URL tag. Changed Purge by URL to use this new tag.
* [BUGFIX] Fixed a bug triggered when .htaccess files were empty.
* [BUGFIX] Correctly determine when to clear files in multisite environments (wp-config, advanced-cache, etc.).
* [BUGFIX] When disabling the cache, settings changed in the same save will now be saved.
* [BUGFIX] Various bugs from setting changes and multisite fixed.
* [BUGFIX] Fixed two bugs with the .htaccess path search.
* [BUGFIX] Do not alter $_GET in add_quick_purge. This may cause issues for functionality occurring later in the same request.
* [BUGFIX] Right to left radio settings were incorrectly displayed. The radio buttons themselves were the opposite direction of the associated text.

= 1.0.11 - October 11 2016 =
* [NEW] The plugin will now set cachelookup public on.
* [NEW] New option - check advanced-cache.php. This enables users to have two caching plugins enabled at the same time as long as the other plugin is not used for caching purposes. For example, using another cache plugin for css/js minification.
* [UPDATE] Rules added by the plugin will now be inserted into an LSCACHE START/END PLUGIN comment block.
* [UPDATE] For woocommerce pages, if a user visits a non-cached page with a non-empty cart, do not cache the page.
* [UPDATE] If woocommerce needs to display any notice, do not cache the page.
* [UPDATE] Single site settings are now in both the litespeed cache submenu and the settings submenu.
* [BUGFIX] Multisite network options were not updated on upgrade. This is now corrected.

= 1.0.10 - September 16 2016 =
* Added a check for LSCACHE_NO_CACHE definition.
* Added a Purge All button to the admin bar.
* Added logic to purge the cache when upgrading a plugin or theme. By default this is enabled on single site installations and disabled on multisite installations.
* Added support for WooCommerce Versions < 2.5.0.
* Added .htaccess backup rotation. Every 10 backups, an .htaccess archive will be created. If one already exists, it will be overwritten.
* Moved some settings to the new Specific Pages tab to reduce clutter in the General tab.
* The .htaccess editor is now disabled if DISALLOW_FILE_EDIT is set.
* After saving the Cache Tag Prefix setting, all cache will be purged.

= 1.0.9.1 - August 26 2016 =
* Fixed a bug where an error displayed on the configuration screen despite not being an error.
* Change logic to check .htaccess file less often.

= 1.0.9 - August 25 2016 =
* [NEW] Added functionality to cache and purge feeds.
* [NEW] Added cache tag prefix setting to avoid conflicts when using LiteSpeed Cache for WordPress with LiteSpeed Cache for XenForo and LiteMage.
* [NEW] Added hooks to allow third party plugins to create config options.
* [NEW] Added WooCommerce config options.
* The plugin now also checks for wp-config in the parent directory.
* Improved WooCommerce support.
* Changed .htaccess backup process. Will create a .htaccess_lscachebak_orig file if one does not exist. If it does already exist, creates a backup using the date and timestamp.
* Fixed a bug where get_home_path() sometimes returned an invalid path.
* Fixed a bug where if the .htaccess was removed from a WordPress subdirectory, it was not handled properly.

= 1.0.8.1 - July 28 2016 =
* Fixed a bug where check cacheable was sometimes not hit.
* Fixed a bug where extra slashes in clear rules were stripped.

= 1.0.8 - July 25 2016 =
* Added purge all on update check to purge by post id logic.
* Added uninstall logic.
* Added configuration for caching favicons.
* Added configuration for caching the login page.
* Added configuration for caching php resources (scripts/stylesheets accessed as .php).
* Set login cookie if user is logged in and it isn’t set.
* Improved NextGenGallery support to include new actions.
* Now displays a notice on the network admin if WP_CACHE is not set.
* Fixed a few php syntax issues.
* Fixed a bug where purge by pid didn’t work.
* Fixed a bug where the Network Admin settings were shown when the plugin was active in a subsite, but not network active.
* Fixed a bug where the Advanced Cache check would sometimes not work.

= 1.0.7.1 - May 26 2016 =
* Fixed a bug where enabling purge all in the auto purge on update settings page did not purge the correct blogs.
* Fixed a bug reported by user wpc on our forums where enabling purge all in the auto purge on update settings page caused nothing to be cached.

= 1.0.7 - May 24 2016 =
* Added login cookie configuration to the Advanced Settings page.
* Added support for WPTouch plugin.
* Added support for WP-Polls plugin.
* Added Like Dislike Counter third party integration.
* Added support for Admin IP Query String Actions.
* Added confirmation pop up for purge all.
* Refactor: LiteSpeed_Cache_Admin is now split into LiteSpeed_Cache_Admin, LiteSpeed_Cache_Admin_Display, and LiteSpeed_Cache_Admin_Rules
* Refactor: Rename functions to accurately represent their functionality
* Fixed a bug that sometimes caused a “no valid header” error message.

= 1.0.6 - May 5 2016 =
* Fixed a bug reported by Knut Sparhell that prevented dashboard widgets from being opened or closed.
* Fixed a bug reported by Knut Sparhell that caused problems with https support for admin pages.

= 1.0.5 - April 26 2016 =
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

= 1.0.4 - April 7 2016 =
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

= 1.0.3 - March 23 2016 =
* Added a Purge Front Page button to the LiteSpeed Cache Management page.
* Added a Default Front Page TTL option to the general settings.
* Added ability to define web application specific cookie names through rewrite rules to handle logged-in cookie conflicts when using multiple web applications. <strong>[Requires LSWS 5.0.15+]</strong>
* Improved WooCommerce handling.
* Fixed a bug where activating lscwp sets the “enable cache” radio button to enabled, but the cache was not enabled by default.
* Refactored code to make it cleaner.
* Updated readme.txt.

= 1.0.2 - March 11 2016 =
* Added a "Use Network Admin Setting" option for "Enable LiteSpeed Cache". For single sites, this choice will default to enabled.
* Added enable/disable all buttons for network admin. This controls the setting of all managed sites with "Use Network Admin Setting" selected for "Enable LiteSpeed Cache".
* Exclude by Category/Tag are now text areas to avoid slow load times on the LiteSpeed Cache Settings page for sites with a large number of categories/tags.
* Added a new line to advanced-cache.php to allow identification as a LiteSpeed Cache file.
* Activation/Deactivation are now better handled in multi-site environments.
* Enable LiteSpeed Cache setting is now a radio button selection instead of a single checkbox.
* Can now add '$' to the end of a URL in Exclude URI to perform an exact match.
* The _lscache_vary cookie will now be deleted upon logout.
* Fixed a bug in multi-site setups that would cause a "function already defined" error.

= 1.0.1 - March 8 2016 =
* Added Do Not Cache by URI, by Category, and by Tag.  URI is a prefix/string equals match.
* Added a help tab for plugin compatibilities.
* Created logic for other plugins to purge a single post if updated.
* Fixed a bug where woocommerce pages that display the cart were cached.
* Fixed a bug where admin menus in multi-site setups were not correctly displayed.
* Fixed a bug where logged in users were served public cached pages.
* Fixed a compatibility bug with bbPress.  If there is a new forum/topic/reply, the parent pages will now be purged as well.
* Fixed a bug that didn't allow cron job to update scheduled posts.

= 1.0.0 - January 20 2016 =
* Initial Release.

