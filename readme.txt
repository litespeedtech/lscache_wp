=== LiteSpeed Cache ===
Contributors: LiteSpeedTech
Tags: caching, optimize, performance, pagespeed, core web vitals, seo, speed, image optimize, compress, object cache, redis, memcached, database cleaner
Requires at least: 4.0
Tested up to: 6.5.2
Stable tag: 6.2.0.1
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html

All-in-one unbeatable acceleration & PageSpeed improvement: caching, image/CSS/JS optimization...

== Description ==

LiteSpeed Cache for WordPress (LSCWP) is an all-in-one site acceleration plugin, featuring an exclusive server-level cache and a collection of optimization features.

LSCWP supports WordPress Multisite and is compatible with most popular plugins, including WooCommerce, bbPress, and Yoast SEO.

LiteSpeed Cache for WordPress is compatible with ClassicPress.

== Requirements ==
**General Features** may be used by anyone with any web server (LiteSpeed, Apache, NGINX, etc.).

**LiteSpeed Exclusive Features** require one of the following: OpenLiteSpeed, commercial LiteSpeed products, LiteSpeed-powered hosting, or QUIC.cloud CDN. [Why?](https://docs.litespeedtech.com/lscache/lscwp/faq/#why-do-the-cache-features-require-a-litespeed-server)

== Plugin Features ==

= General Features =

* Free QUIC.cloud CDN Cache
* Object Cache (Memcached/LSMCD/Redis) Support<sup>+</sup>
* Image Optimization (Lossless/Lossy)
* Minify CSS, JavaScript, and HTML
* Minify inline & external CSS/JS
* Combine CSS/JS
* Automatically generate Critical CSS
* Lazy-load images/iframes
* Responsive Image Placeholders
* Multiple CDN Support<sup>+</sup>
* Load CSS Asynchronously
* Defer/delay JS loading
* Browser Cache Support<sup>+</sup>
* Database Cleaner and Optimizer
* PageSpeed score (including Core Web Vitals) optimization
* OPcode Cache Support<sup>+</sup>
* HTTP/2 Push for CSS/JS (on web servers that support it)
* DNS Prefetch
* Cloudflare API
* Single Site and Multisite (Network) support
* Import/Export settings
* Attractive, easy-to-understand interface
* WebP image format support
* Heartbeat control

<sup>+</sup> This service is not provided by the LSCache plugin, nor is it guaranteed to be installed by your service provider. However, the plugin is compatible with the service if it is in use on your site.

= LiteSpeed Exclusive Features =

* Automatic page caching to greatly improve site performance
* Automatic purge of related pages based on certain events
* Private cache for logged-in users
* Caching of WordPress REST API calls
* Separate caching of desktop and mobile views
* Ability to schedule purge for specified URLs
* WooCommerce and bbPress support
* [WordPress CLI](https://docs.litespeedtech.com/lscache/lscwp/cli/) commands
* API system for easy cache integration
* Exclude from cache by URI, Category, Tag, Cookie, User Agent
* Smart preload crawler with support for SEO-friendly sitemap
* Multiple crawlers for cache varies
* HTTP/2 support
* [HTTP/3 & QUIC](https://www.litespeedtech.com/http3-faq) support
* ESI (Edge Side Includes) support<sup>*</sup>
* Widgets and Shortcodes as ESI blocks<sup>*</sup> (requires Classic Widgets plugin for WP 5.8+)

<sup>*</sup> Feature not available in OpenLiteSpeed

== Screenshots ==

1. Plugin Benchmarks
2. Admin - Dashboard
3. Admin - Image Optimization
4. Admin - Crawler
5. Admin Settings - Cache
6. Admin Settings - Page Optimization
7. Admin Settings - CDN
8. Admin Settings - DB Optimizer
9. Admin Settings - Toolbox
10. Cache Miss Example
11. Cache Hit Example

== LSCWP Resources ==
* [Join our Slack community](https://litespeedtech.com/slack) to connect with other LiteSpeed users.
* [Ask a question on our support forum](https://wordpress.org/support/plugin/litespeed-cache/).
* [View detailed documentation](https://docs.litespeedtech.com/lscache/lscwp/overview/).
* [Read WordPress Wednesday tutorials on our blog](https://blog.litespeedtech.com/tag/wordpress-wednesday/).
* [Help translate LSCWP](https://translate.wordpress.org/projects/wp-plugins/litespeed-cache/).
* [LSCWP GitHub repo](https://github.com/litespeedtech/lscache_wp).

== Installation ==

[View detailed documentation](https://docs.litespeedtech.com/lscache/lscwp/installation/).

= For Optimization Without a LiteSpeed Web Server =
1. Install the LiteSpeed Cache for WordPress plugin and activate it.
1. From the WordPress Dashboard, navigate to **LiteSpeed Cache > Page Optimization**. Enable the available optimization features in the various tabs.

= For Caching and Optimization With a LiteSpeed Web Server =
1. Install [LiteSpeed Web Server Enterprise](https://www.litespeedtech.com/products/litespeed-web-server) with LSCache Module, [LiteSpeed Web ADC](https://www.litespeedtech.com/products/litespeed-web-adc), or [OpenLiteSpeed](https://www.litespeedtech.com/open-source/openlitespeed) with cache module (Free). Or sign up for [QUIC.cloud CDN](https://quic.cloud).
1. Install the LiteSpeed Cache for WordPress plugin and activate it.
1. From the WordPress Dashboard, navigate to **LiteSpeed Cache > Cache**, make sure the option **Enable LiteSpeed Cache** is set to `ON`.
1. Enable any desired caching and optimization features in the various tabs.

= Notes for LiteSpeed Web Server Enterprise =

* Make sure that your license includes the LSCache module. A [2-CPU trial license with LSCache module](https://www.litespeedtech.com/products/litespeed-web-server/download/get-a-trial-license "trial license") is available for free for 15 days.
* The server must be configured to have caching enabled. If you are the server admin, [click here](https://docs.litespeedtech.com/lscache/start/#configure-cache-root-and-cache-policy) for instructions. Otherwise, please request that the server admin configure the cache root for the server.

= Notes for OpenLiteSpeed =

* This integration utilizes OpenLiteSpeed's cache module.
* If it is a fresh OLS installation, the easiest way to integrate is to use [ols1clk](https://openlitespeed.org/kb/1-click-install/). If using an existing WordPress installation, use the `--wordpresspath` parameter.
* If OLS and WordPress are both already installed, please follow the instructions in [How To Set Up LSCache For WordPress](https://openlitespeed.org/kb/how-to-setup-lscache-for-wordpress/).

== Third Party Compatibility ==

The vast majority of plugins and themes are compatible with LSCache. [Our API](https://docs.litespeedtech.com/lscache/lscwp/api/) is available for those that are not. Use the API to customize smart purging, customize cache rules, create cache varies, and make WP nonce cacheable, among other things.

== Privacy ==

This plugin includes some suggested text that you can add to your site's Privacy Policy via the Guide in the WordPress Privacy settings.

**For your own information:** LiteSpeed Cache for WordPress potentially stores a duplicate copy of every web page on display on your site. The pages are stored locally on the system where LiteSpeed server software is installed and are not transferred to or accessed by LiteSpeed employees in any way, except as necessary in providing routine technical support if you request it. All cache files are temporary, and may easily be purged before their natural expiration, if necessary, via a Purge All command. It is up to individual site administrators to come up with their own cache expiration rules.

In addition to caching, our WordPress plugin has online features provided by QUIC.cloud for Image Optimization, CSS Optimization and Low Quality Image Placeholder services. When one of those optimizations are requested, data is transmitted to a remote QUIC.cloud server, processed, and then transmitted back for use on your site. Now if using the QUIC.cloud CDN it uses LSCache technologies to access your site then host your site to others globally and also your data is not transferred to or accessed by QUIC.cloud employees in any way, except as necessary in providing maintenance or technical support. QUIC.cloud keeps copies of that data for up to 7 days and then permanently deletes them. Similarly, the WordPress plugin has a Reporting feature whereby a site owner can transmit an environment report to LiteSpeed so that we may better provide technical support. None of these features collects any visitor data. Only server and site data are involved.

Please see the [QUIC.cloud Privacy Policy](https://quic.cloud/privacy-policy/) for our complete Privacy/GDPR statement.

== Frequently Asked Questions ==

= Why do the cache features require LiteSpeed Server? =
This plugin communicates with your LiteSpeed Web Server and its built-in page cache (LSCache) to deliver superior performance to your WordPress site. The plugin’s cache features indicate to the server that a page is cacheable and for how long, or they invalidate particular cached pages using tags.

LSCache is a server-level cache, so it's faster than PHP-level caches. [Compare with other PHP-based caches](https://www.litespeedtech.com/benchmarks/wordpress).

A page cache allows the server to bypass PHP and database queries altogether. LSCache, in particular, because of its close relationship with the server, can remember things about the cache entries that other plugins cannot, and it can analyze dependencies. It can utilize tags to manage the smart purging of the cache, and it can use vary cookies to serve multiple versions of cached content based on things like mobile vs. desktop, geographic location, and currencies. [See our Caching 101 blog series](https://blog.litespeedtech.com/tag/caching-101/).

If all of that sounds complicated, no need to worry. LSCWP works right out of the box with default settings that are appropriate for most sites. [See the Beginner's Guide](https://docs.litespeedtech.com/lscache/lscwp/beginner/).

**Don't have a LiteSpeed server?** Try our QUIC.cloud CDN service. It allows sites on *any server* (NGINX and Apache included) to experience the power of LiteSpeed caching! [Click here](https://quic.cloud) to learn more or to give QUIC.cloud a try.

= What about the optimization features of LSCache? =

LSCWP includes additional optimization features, such as Database Optimization, Minification and Combination of CSS and JS files, HTTP/2 Push, CDN Support, Browser Cache, Object Cache, Lazy Load for Images, and Image Optimization! These features do not require the use of a LiteSpeed web server.

= Is the LiteSpeed Cache Plugin for WordPress free? =

Yes, LSCWP will always be free and open source. That said, a LiteSpeed server is required for the cache features, and there are fees associated with some LiteSpeed server editions. Some of the premium online services provided through QUIC.cloud (CDN Service, Image Optimization, Critical CSS, Low-Quality Image Placeholder, etc.) require payment at certain usage levels. You can learn more about what these services cost, and what levels of service are free, on [your QUIC.cloud dashboard](https://my.quic.cloud).

= What server software is required for this plugin? =

A LiteSpeed solution is required in order to use the **LiteSpeed Exclusive** features of this plugin. Any one of the following will work:

1. LiteSpeed Web Server Enterprise with LSCache Module (v5.0.10+)
2. OpenLiteSpeed (v1.4.17+)
3. LiteSpeed WebADC (v2.0+)
4. QUIC.cloud CDN

The **General Features** may be used with *any* web server. LiteSpeed is not required.

= Does this plugin work in a clustered environment? =

The cache entries are stored at the LiteSpeed server level. The simplest solution is to use LiteSpeed WebADC, as the cache entries will be stored at that level.

If using another load balancer, the cache entries will only be stored at the backend nodes, not at the load balancer.

The purges will also not be synchronized across the nodes, so this is not recommended.

If a customized solution is required, please contact LiteSpeed Technologies at `info@litespeedtech.com`

NOTICE: The rewrite rules created by this plugin must be copied to the Load Balancer.

= Where are the cached files stored? =

The actual cached pages are stored and managed by LiteSpeed Servers.

Nothing is stored within the WordPress file structure.

= Does LiteSpeed Cache for WordPress work with OpenLiteSpeed? =

Yes it can work well with OpenLiteSpeed, although some features may not be supported. See **Plugin Features** above for details. Any setting changes that require modifying the `.htaccess` file will require a server restart.

= Is WooCommerce supported? =

In short, yes. However, for some WooCommerce themes, the cart may not be updated correctly. Please [visit our blog](https://blog.litespeedtech.com/2017/05/31/wpw-fixing-lscachewoocommerce-conflicts/) for a quick tutorial on how to detect this problem and fix it if necessary.

= Are my images optimized? =

Images are not optimized automatically unless you set **LiteSpeed Cache > Image Optimization > Image Optimization Settings > Auto Request Cron** to `ON`. You may also optimize your images manually. [Learn more](https://docs.litespeedtech.com/lscache/lscwp/imageopt/).

= How do I make a WP nonce cacheable in my third-party plugin? =

Our API includes a function that uses ESI to "punch a hole" in a cached page for a nonce. This allows the nonce to be cached separately, regardless of the TTL of the page it is on. Learn more in [the API documentation](https://docs.litespeedtech.com/lscache/lscwp/api/#esi). We also welcome contributions to our predefined list of known third party plugin nonces that users can optionally include via [the plugin's ESI settings](https://docs.litespeedtech.com/lscache/lscwp/cache/#esi-nonce).

= How do I enable the crawler? =

The crawler is disabled by default, and must be enabled by the server admin first.

Once the crawler is enabled on the server side, navigate to **LiteSpeed Cache > Crawler > General Settings** and set **Crawler** to `ON`.

For more detailed information about crawler setup, please see [the Crawler documentation](https://docs.litespeedtech.com/lscache/lscwp/crawler/).

= What are the known compatible plugins and themes? =

* [WPML](https://wpml.org/)
* [DoLogin Security](https://wordpress.org/plugins/dologin/)
* [bbPress](https://wordpress.org/plugins/bbpress/)
* [WooCommerce](https://wordpress.org/plugins/woocommerce/)
* [Contact Form 7](https://wordpress.org/plugins/contact-form-7/)
* [All in One SEO](https://wordpress.org/plugins/all-in-one-seo-pack/)
* [Google XML Sitemaps](https://wordpress.org/plugins/google-sitemap-generator/)
* [Yoast SEO](https://wordpress.org/plugins/wordpress-seo/)
* [Wordfence Security](https://wordpress.org/plugins/wordfence/)
* [NextGen Gallery](https://wordpress.org/plugins/nextgen-gallery/)
* [ShortPixel](https://shortpixel.com/h/af/CXNO4OI28044/)
* Aelia CurrencySwitcher
* [Fast Velocity Minify](https://wordpress.org/plugins/fast-velocity-minify/) - Thanks Raul Peixoto!
* Autoptimize
* [Better WP Minify](https://wordpress.org/plugins/bwp-minify/)
* [WP Touch](https://wordpress.org/plugins/wptouch/)
* [Theme My Login](https://wordpress.org/plugins/theme-my-login/)
* [WPLister](https://www.wplab.com/plugins/wp-lister/)
* [WP-PostRatings](https://wordpress.org/plugins/wp-postratings/)
* [Avada 5.1 RC1+](https://avada.theme-fusion.com/)
* [Elegant Themes Divi 3.0.67+](https://www.elegantthemes.com/gallery/divi/)
* [Elegant Divi Builder](https://www.elegantthemes.com/plugins/divi-builder/)
* [Caldera Forms](https://wordpress.org/plugins/caldera-forms/) 1.5.6.2+
* Login With Ajax
* [Ninja Forms](https://wordpress.org/plugins/ninja-forms/)
* [Post Types Order 1.9.3.6+](https://wordpress.org/plugins/post-types-order/)
* [BoomBox — Viral Magazine WordPress Theme](https://themeforest.net/item/boombox-viral-buzz-wordpress-theme/16596434?ref=PX-lab)
* FacetWP (LSWS 5.3.6+)
* Beaver Builder
* WpDiscuz
* WP-Stateless
* Elementor
* WS Form
* WP Statistics

The vast majority of plugins and themes are compatible with LiteSpeed Cache. The most up-to-date compatibility information can be found [in our documentation](https://docs.litespeedtech.com/lscache/lscwp/thirdparty/)

== Changelog ==

= 6.2.0.1 - Apr 25 2024 =
* 🔥🐞**Page Optimize** Fixed the image display issue that occurs with Elementor's `data-settings` attribute when the WebP image is not yet ready. (kanten/cbwwebmaster/reedock #132840 #680939 #326525)

= 6.2 - Apr 23 2024 =
* 🌱**Crawler** Added Crawler hit/miss filter. (#328853)
* 🌱**CLI** Image optimization now supports `wp litespeed-image batch_switch orig/optm`. (A2Hosting)
* 🌱**VPI** Auto preload VPI images. (Ankit)
* **Object** Added support for username/password authentication for Redis (PR#616 Donatas Abraitis/hostinger)
* **Page Optimize** Now supporting Elementors data-settings WebP replacement. (Thanks to Ryan D)
* **Cache** Send `Cache-Control: no-cache, no-store, must-revalidate, max-age=0` when page is not cacheable. (asafm7/Ruikai)
* **Cache** Cache control will respect `X-Http-Method-Override` now. (George)
* **Cache** No cache for `X-Http-Method-Override: HEAD`. (George)
* **Cache** Specified LSCWP in adv-cache compatible file.
* **Cache** Fixed redirection loop if query string has tailing ampersand (#389629)
* **Cache** Dropped "Cache Favicon.ico" option as it is redundant with 404 cache. (Lauren)
* **Cache** Fixed deprecated PHP v8 warning in page redirection. (Issue#617 dcx15)
* **Cloud** REST callback used ACL for QC ips validation.
* **Cloud** Fixed a typo in parsing cloud msg which prevented error messages to show.
* **Cloud** Carried on PHP ver for better version detection purpose.
* **Cloud** Escaped token to show correctly in report.
* **Cloud** Fixed a QC cloud ip verification setup failure in PHP 5.3.
* 🐞**Cloud** Fixed a continual new version detection.
* 🐞**Image Optimize** Fixed a summary counter mismatch for finished images. (A2Hosting)
* **CDN** Auto CDN setup compatibility with WP versions less than 5.3.
* 🐞**CDN** Fixed wrong replacement of non image files in image replacement. (Lucas)
* **GUI** Further filtered admin banner messages to prevent from existing danger code in database.
* **REST** Fixed a potential PHP warning in REST check when param is empty. (metikar)

= 6.1 - Feb 1 2024 =
* 🌱**Database** New Clear Orphaned Post Meta optimizer function.
* **Image Optimize** Fixed possible PHP warning for WP requests library response.
* **Image Optimize** Unlocked `noabort` to all async tasks to avoid image optimization timeout. (Peter Wells)
* **Image Optimize** Fixed an issue where images weren't being pulled with older versions of WordPress. (PR#608)
* **Image Optimize** Improved exception handling when node server cert expire.
* 🐞**Image Optimize** The failed to pull images due to 404 expiry will now be able to send the request again.
* **Crawler** CLI will now be able to force crawling even if a crawl was recently initiated within the plugin GUI.
* **Page Optimize** Fixed a dynamic property creation warning in PHP8. (PR#606)
* **Page Optimize** Fixed an issue where getimagesize could cause page optimization to fail. (PR#607)
* **Tag** Fixed an array to string conversion warning. (PR#604)
* **Object Cache** Return false to prevent PHP warning when Redis fails to set a value. (PR#612)
* **Cache Tag** Fixed an issue where $wp_query is null when getting cache tags. (PR#589)

= 6.0.0.1 - Dec 15 2023 =
* 🐞**Image Optimize** Grouped the taken notification to regional center servers to reduce the load after image pulled.

= 6.0 - Dec 12 2023 =
* 🌱**Image Optimize** Parallel pull. (⭐ Contributed by Peter Wells #581)
* 🌱**Cache** CLI Crawler.
* 🌱**Cache** New Vary Cookies option.
* 🌱**Media** New Preload Featured Image option. (Ankit)
* **Core** Codebase safety review. (Special thanks to Rafie Muhammad @ Patchstack)
* **Purge** Purge will not show QC message if no queue is cleared.
* **Purge** Fixed a potential warning when post type is not as expected. (victorzink)
* **Conf** Server IP field may now be emptied. (#111647)
* **Conf** CloudFlare CDN setting vulnerability patch. (Gulshan Kumar #541805)
* **Crawler** Suppressed sitemap generation msg when running by cron.
* **Crawler** PHP v8.2 Dynamic property creation warning fix. (oldrup #586)
* **VPI** VPI can now support non-alphabet filenames.
* **VPI** Fixed PHP8.2 deprecated warning. (Ryan D)
* **ESI** Fixed ESI nonce showing only HTML comment issue. (Giorgos K.)
* 🐞**Page Optimize** Fixed a fatal PHP error caused by the WHM plugin's Mass Enable for services not in use. (Michael)
* 🐞**Network** Fix in-memory options for multisites. (Tynan #588)
* **Network** Correct `Disable All Features` link for Multisite.
* 🐞**Image Optimize** Removing original image will also remove optimized images.
* **Image Optimize** Increased time limit for pull process.
* **Image Optimize** Last pull time and cron tag now included in optimization summary.
* **Image Optimize** Fixed Elementors Slideshow unusal background images. (Ryan D)
* 🐞**Database Optimize** Fix an issue where cleaning post revisions would fail while cleaning postmeta. (Tynan #596)
* **Crawler** Added status updates to CLI. (Lars)
* **3rd** WPML product category purge for WooCommerce. (Tynan #577)

= 5.7.0.1 - Oct 25 2023 =
* **GUI** Improvements to admin banner messaging. (#694622)
* **CDN** Improvements to CDN Setup. (#694622)
* **Image Optimize** Improvements to the process of checking image identification. (#694622)

= 5.7 - Oct 10 2023 =
* 🌱**Page Optimize** New option available: Preconnect. (xguiboy/Mukesh Patel)
* 🌱**3rd** New Vary for Mini Cart option for Woocommerce. (Ruikai)
* **Cloud** Force syncing the configuration to QUIC.cloud if CDN is reenabled.
* **Cloud** Force syncing the configuration to QUIC.cloud if domain key is readded.
* **Cloud** Limit multi-line fields when posting to QC.
* **Cache** Treat HEAD requests as cacheable as GET. (George Wang)
* 🐞**ESI** Patched a possible vulnerability issue. (István Márton@Wordfence #841011)
* 🐞**ESI** Overwrite SCRIPT_URI to prevent ESI sub request resulting in redirections. (Tobolo)
* 🐞**Image Optimize** Bypass unnecessary image processing when images were only partially optimized. (Ruikai)
* 🐞**Guest** Guest mode will not enable WebP directly anymore. (Michael Heymann)
* **CDN** Auto disable CDN if CDN URL is invalid. (Ruikai)
* **CDN** Fixed a null parameter warning for PHP v8.1 (#584)
* **API** Added `litespeed_media_add_missing_sizes` filter to allow bypassing Media's "add missing sizes" option (for Guest Optimization and otherwise). (PR #564)
* **Guest** Fixed soft 404 and robots.txt report for guest.vary.php.
* **Vary** Enabled `litespeed_vary_cookies` for LSWS Enterprise.
* **GUI** Stopped WebP tip from wrongly displaying when Guest Mode is off.
* **GUI** Added QUIC.cloud promotion postbox on dashboard page.
* **3rd** Added `pagespeed ninja` to blocklist due to its bad bahavior.
