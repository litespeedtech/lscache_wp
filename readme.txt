=== LiteSpeed Cache ===
Contributors: LiteSpeedTech
Tags: caching, optimize, performance, pagespeed, core web vitals, seo, speed, image optimize, compress, object cache, redis, memcached, database cleaner
Requires at least: 4.0
Tested up to: 6.3.2
Stable tag: 5.7.0.1
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

= 5.6 - Aug 1 2023 =
* 🌱**Page Optimize** New JS Delay Includes option. (Mitchell Krog/Gerard Reches/Ignacy Hołoga)
* **Crawler** Sitemap can use search for URL now.
* **GUI** Restrict the scope of balloon CSS rules to avoid conflicts. (#567)
* **Object Cache** Detect Memcached in more situations. (#568)
* **API** Support `litespeed_purged_front` hook. (Umberto Fiorelli)

= 5.5.1 - Jul 19 2023 =
* 🐞**Image Optimization** Fixed a bug where WebP replacements couldn't be pulled without optimizing the original images.
* 🐞**Image Optimization** Invalid images will now be removed when sending requests to the server. (#138993)
* **Cloud** Added support for error codes `unpulled_images` and `blocklisted`. (Tynan)

= 5.5 - Jun 20 2023 =
* 🌱**Crawler** Can now use multiple sitemaps. (Tobolo/Tim Nolte)
* 🌱**Crawler** Now runs asynchronously when manually invoked.
* 🌱**Crawler** Now runs asynchronously when invoked from cron.
* 🐞**Crawler** Fixed the realtime status bug when crawling.
* **Crawler** Summary page now displays server load. (Ruikai)
* 🐞**Page Optimize** Fixed an issue where UCSS could not be generated for error pages. (james58899) #556
* 🌱**Image Optimize** Now pulls images asynchronously.
* **Image Optimize** Now prevents concurrent requests via a locking mechanism.
* **Image Optimize** The process can now bypass invalid image records and continue.
* 🐞**Image Optimize** Fixed an issue where images ready for optimization might have to wait for new images to be added before sending the request.
* **Cloud** Replaced dashboard links with login/link to my.quic.cloud actions.
* **GUI** Added indicators to show when certain options are passively enabled by Guest Mode.
* **Htaccess** Added a noabort rule to support asynchronous crawling.
* **Htaccess** The "Do Not Cache User Agents" option is now case-insensitive. (Ellen Dabo)
* **General** The "Server IP" option now allows IPv4 format only. (Ruikai)
* **Misc** Every page's closing HTML comments now displays UCSS/CCSS status.
* **Object** Fixed a warning for null get_post_type_object.
* **Object** Object_Cache::delete now always returns a boolean value.
* **Cache** Fixed advanced-cache.php file warnings for WordPress versions less than 5.3.
* **Debug** Added debug logging to record the plugin's total processing time.
* **API** HTML minification can now be bypassed via the litespeed_html_min filter.

= 5.4 - Apr 19 2023 =
* **Image Optimize** Refactored DB storage for this feature.
* **Image Optimize** Reduced DB table size.
* **Image Optimize** Existing `img_optm` DB tables will have their data gradually transitioned to the new storage format with this update. Once an `img_optm` table is empty, it won't be used anymore.
* **Page Optimize** Enabled WebP support for Googlebot User Agent.

= 5.3.3 - Feb 22 2023 =
* **Page Optimize** Excluded Jetpack stats JS.
* **DB Optimize** Fixed DB Optm SQL for revision postmeta.
* **Cache** Fixed an undefined array key warning.
* **Purge** Prevented undefined array key warning when widgets are disabled.
* **Object** Fixed dynamic property deprecation warnings.
* **Admin** Safely redirect to homepage if referer is unknown.
* **Activation** Check that item slug exists first.
* **Cache** Prevented cache header to send globally if header part already closed.
* **CSS** Improved string handling for CSS minifier.
* **Debug** Fixed undefined array key warnings.
* **Misc** Fixed implicit conversion in random string generation function `Str::rrand`.

= 5.3.2 - Jan 10 2023 =
* **Object** Fixed object cache lib incr, decr functions (thanks bdrbros/DANIEL) #516
* **Database Optimize** Database optimizer now handles postmeta when cleaning revisions #515
* **Cache** Made nocache the default for 4xx/5xx response codes.
* **Cache** Default cache TTL settings removed for 403 response code, changed to 10 mins for 500 response code.
* **GUI** Added a description for the redetect nodes function.
* **GUI** Added a description for the refresh button sync function.

= 5.3.1 - Dec 12 2022 =
* **CLI** Presets feature is now usable from the CLI. (xLukii)
* **CLI** Added 'import_remote' for litespeed-option to enable importing options from URLs. (xLukii)
* **Cache** Added LiteSpeed headers to site health check for full page cache.
* **Crawler* Fixed unauthorized crawler toggle operation. (#CVE-2022-46800)
* **UCSS** Fixed a bug where items weren't added back to the UCSS queue after purging.
* **Page Optimize** Fixed a bug where generated CSS would return 404 after upgrading via CLI.
* **3rd** Fixed a bug where a WooCommerce session doesn't exist when checking cart, notices (Jason Levy/Gilles)
* **GUI** Made LiteSpeed admin notice icon grayscale to avoid distraction. (martinsauter)
* **GUI** Fixed RTL style for notification icon.
* **API** Added a new hook `litespeed_optm_uri_exc` to exclude URI from page optimization.
* **API** Excluded `.well-known` path from page optimization.

= 5.3 - Oct 31 2022 =
* 🌱**Presets** New `Presets` feature and menu item.
* 🌱**UCSS** New option `UCSS File Excludes and Inline` to increase page score. (Ankit)
* **UCSS** When UCSS is purged, automatically append URL to UCSS generation queue. (Ankit)
* **Page Optimize** Removed a redundant `defer` attribute from Lazy Load image library usage. (#928019)
* **Image Optimize** Dropped `Create WebP Versions` setting. Will automatically enable when `Image WebP Replacement` is activated.
* **Cloud** Fixed a bug where internal updates were delayed for API keys.
* **Cloud** Improved auto alias feature by waiting for second request from alias domain validation before removing a pending alias.
* **Purge** Automatically Purge All when plugin auto update is done.
* **Purge** Fixed a potential PHP8 error that occurred when removing unused widgets. (acsnaterse)
* **Cache** Fixed an infinite 301 redirection caused by UTM-encoded link.
* **CLI** Added syntax examples for values that include line breaks (xLukii)
* **CLI** Purge requests will now be included with the original request to avoid potential CSS/JS 404 issues.
* **ESI** Check all user roles for cache vary and page optimization excludes.
* **GUI** Added a LiteSpeed icon to admin message banners to indicate the banners are from our plugin. (Michael D)
* **Crawler** Fixed a cache-miss issue that occurred when Guest Mode was ON and WebP Replacement was OFF.
* **3rd** Remove WooCommerce private cache.
* **3rd** Removed LiteSpeed metabox from ACF field group edit page. (keepmovingdk)

= 5.2.1 - Sep 7 2022 =
* 🐞**Core** Fixed a fatal error that occurred when uninstalling. (#894556 Hostinger)
* **Dash** Show partner info on the dashboard for partner-tier QC accounts.
* **UCSS** Auto-purge UCSS on post update. (Ankit)
* 🕸️**Crawler** Respect the `LITESPEED_CRAWLER_DISABLE_BLOCKLIST` constant for unexpected results too. (Abe)

= 5.2 - Aug 17 2022 =
* 🌱**UCSS** Added UCSS message queue to improve service quality and reliability
* 🐞**VPI** Fixed conflict w/ image lazyload; used HTML before image lazyload to avoid invalid `data:base64` results.
* **VPI** Changed VPI Cron default setting to OFF.
* **VPI** Automatically resend requests when VPI result contains invalid `data:` image value.
* **Conf** Fixed an issue with URI Excludes, where paths using both ^ and $ were not correctly excluded (Eric/Abe)
* **Conf** Auto corrected `WP_CONTENT_URL` protocol if it was explicitly set to `http://`.
* **Cloud** No longer sync the configuration to QUIC.cloud if configuration is unchanged.
* **Cloud** Appended home_url value into synced configuration data for wp-content folder path correction.
* 🕸️**Crawler** Improved compatibility with server `open_basedir` PHP setting limit when detecting load before crawling. (Tom Robak/mmieszalski)

= 5.1 - Aug 1 2022 =
* 🌱**Toolbox** Debug log can now show Purge/Crawler logs as well. (Tynan)
* **UCSS** Prepared for future message queue.
* **UCSS** Moved UCSS class to its own file.
* **3rd** Added 3rd-party support for WC PDF Product Vouchers. (Tynan)
* **Core** Fixed potential PHP warning when saving summary data. (Sarah Richardson)
* **Purge** Purge can now clear the summary correctly. (Kevin)
* **VPI** Added `queue_k` to API notification.

= 5.0.1 - Jul 27 2022 =
* 🐞**Cloud** Fixed a potential PHP error that could occur with the cloud service summary. (Bruno Cantuaria)
* **3rd** Added Autoptimize back to compatibility list.

= 5.0.0.1 - Jul 26 2022 =
* 🔥🐞**Cloud** Fixed an issue with the cloud request timestamp update which causes a usage sync failure. (great thanks to Kevin)

= 5.0 - Jul 25 2022 =
* 🌱**VPI** Added Viewport Images feature to LiteSpeed Options metabox on Post Edit page.
* 🌱**CDN** Added Auto CDN Setup feature for simple QUIC.cloud CDN setup. (Kevin)
* 🌱**Page Optimize** Automatically cache remote CSS/JS files when fetching for optimization (Lauren)
* 🌱**Cache** Added LiteSpeed Options for page-level cache control on Post Edit page. (denisgomesfranco)
* 🌱**Cloud** Auto Alias feature.
* 🌱**Debug** Added `Debug String Excludes` option. (Hanna)
* 🌱**UCSS** Added `Purge this page - UCSS` option to Admin Bar dropdown menu. (Ankit)
* 🌱**Guest** Added `litespeed_guest_off=1` URL query string parameter to bypass Guest Mode. (cbdfactum)
* 🐞**Page Optimize** Fixed an issue where CSS anchors could be wrongly converted to a full path when minifying. (Tynan)
* **Page Optimize** Bypass CCSS/UCSS generation when a self-crawled CSS resource returns a 404 code. (Abe)
* **Object** Allow `LSCWP_OBJECT_CACHE` predefined to turn off Object Cache. (knutsp)
* **Data** Fixed an issue where empty version tags in the database repeatedly toggled the upgrade banner and reset settings to default.
* **Purge** Fixed an issue where the site's index page could be purged upon deletion of an unviewable post. (Kevin)
* **Toolbox** Added `View site before optimization` button under `Debug` tab. (Ryan D)
* **Admin** Switch to using the `DONOTCACHEPAGE` constant to indicated WP-Admin pages are not cacheable.
* **Admin** Moved no-cache header to very beginning to avoid caching unexpected exits.
* **Cloud** Added message queue service for VPI. (Abe)
* **Cloud** Bypassed 503 error nodes from node redetection process. (Abe)
* **Cloud** Fixed a failure to detect `out_of_quota`. (Lauren)
* **Cloud** Added ability to display dismissable banners generated by QUIC.cloud.
* 🕸️**Crawler** Added realtime load detection before crawl.
* 🕸️**Crawler** Adjusted crawler behavior for Divi pages to allow for Divi's CCSS generation process. (miketemby)
* 🕸️**API** PHP constant `LITESPEED_CRAWLER_DISABLE_BLOCKLIST` and filter `litespeed_crawler_disable_blocklist` to disable blocklist. (Tobolo)
* **CDN** Automatically add a trailing slash to `CDN URL` and `Original URLs` if user didn't provide one. (Lucas)
* **Cache** When a URL redirects to a URL with a query string, consider these as different for caching purposes. (Shivam)
* **Media** Added ability to disable lazyload from the LiteSpeed Options metabox on Post Edit page.
* **Media** Added new default values to `WebP Attribute to Replace` setting for WPBakery and Slider Revolution. (JibsouX)
* **Image Optimize** Dropped redundant `Page Speed` user agent when serving WebP images. (serpentdriver)
* **GUI** Fixed an issue where manually dismissable admin messages were instead being treated as one-time messages. (Tynan Beatty)
* **GUI** Fixed an issue where subsequent admin alerts would overwrite existing alerts in the queue. (Kevin/Tynan)
* **GUI** Updated time offset in log. (Ruikai #PR444 #PR445)
* **GUI** Added `litespeed_media_ignore_remote_missing_sizes` API description.
* **CCSS** Fixed an issue where CCSS was unexpectedly bypassed if `CSS Combine` was OFF and `UCSS Inline` was ON. (Ruikai)
* **Debug** Added resposne headers to debug log. (Kevin)

= 4.6 - Mar 29 2022 =
* **Page Optimize** Improved compatibility for JS Delay.
* 🐞**Page Optimize** Fixed an issue for network subsites that occurred when only CSS/JS Minify are enabled.
* **Localization** Added query string compatibility for Resource URLs.
* **Vary** Fixed a potential PHP warning when server variable `REQUEST_METHOD` is not detected.
* **Cache** Guest Mode now respects Cache Excludes settings.
* **GUI** Added warning notice when enabling `Localize Resources` feature; each localized JS resource requires thorough testing!
* **GUI** Fixed a PHP Deprecated warning that occurred with the Mobile Cache User Agent setting on PHP v8.1+. (jrmora)
* **Conf** Removed Google related scripts from default `Localization Files` value.
* **Media** WordPress core Lazy Load feature is now automatically disabled when LiteSpeed Lazy Load Images option is enabled. (VR51 #Issue440)
* 🐞**API** Filter `litespeed_ucss_per_pagetype` for UCSS now also applies to CSS Combine to avoid UCSS failure. (Ankit)
* **API** Added a filter `litespeed_media_ignore_remote_missing_sizes` to disable auto detection for remote images that are missing dimensions. (Lucas)

= 4.5.0.1 - Feb 24 2022 =
* 🔥🐞**Media** Fixed an issue where lazy-loaded images would disappear when using custom CSS image loading effects.

= 4.5 - Feb 23 2022 =
* 🌱**Page Optimize** Localization is back.
* **Guest** Fixed organic traffic issue as different browsers may fail to set `document.referrer`.
* **Image Optimize** Improved wp_postmeta table compatibility when gathering images. (Thanks to Thomas Stroemme)
* 🐞**Page Optimize** Fixed a potential CSS/JS 404 issue for existing records that have been marked as expired.
* **ESI** `LITESPEED_ESI_OFF` now affects `litespeed_esi_url` API filter too.
* **Guest** Added a check to determine if Guest Mode is blocked by a third-party, and display warning if it is (Ruikai)
* **Guest** To support WP sites with multiple domains, Guest Mode detection URL no longer uses domain.
* **Report** Network now shows Toolbox page when having a large number of subsites.
* **DB Optimize** Reduced default subsites count from 10 to 3 under Network Admin -> DB Optimize page to avoid timeout.
* **Cloud** Fixed potential `lack_of_token` error when requesting domain key for cases where local summary value was not historically included in the array.
* **Cloud** Fixed a PHP fatal error that occurred when encountering a frequency issue under CLI. (Dean Taylor #Issue410)
* **Avatar** Force gravatar cache refresh in browsers and on CDN (rafaucau #PR430)
* **API** New filter `litespeed_purge_ucss` to purge a single page UCSS. (#376681)
* **API** New filter `litespeed_ucss_per_pagetype` for UCSS per page type generation. (Ankit)
* **GUI** Replaced some GUI text and settings with more inclusive language  (kebbet #PR437 #PR435)
* **3rd** Excluded `WP Statistics` from inline JS optimize. (Ryan D)
* **3rd** Added API filter `litespeed_3rd_aelia_cookies` for Aelia CurrencySwitcher.
* **Media** Updated image lazyload library to 17.5.0.

= 4.4.7 - Jan 11 2022 =
* **Page Optimize** Dropped `Inline Lazy Load Images Library` option. Now will always inline lazyload library. (Ankit)
* **3rd** Prevented JavaScript files from being appended to Rank Math SEO sitemap.
* **Purge** Dropped default stale purge when purging a post.
* **Cloud** Dropped unused API calls.
* **Cloud** Dropped redundant IP validation in API calls.

= 4.4.6 - Dec 27 2022 =
* **Guest** Restored `document.referrer` for organic traffic purposes when Guest Mode is enabled. (michelefns)
* **Image Optimize** Fixed a potential PHP notice when uploading images in WP w/ PHP7.4+. (titsmaker)
* **ESI** Fixed an issue where ESI settings were not updated on customized widgets(#422 Abe)
* **3rd** Reverted ESI Adminbar change on Elementor front pages for backward compatibility (#423 Abe)
* **3rd** Fixed an issue where disabling ESI potential caused a PHP warning when using `Perfmatters`. (Jeffrey Zhang)
* **Misc** Check whether HTTP_REFERER is set or not before using it in Router class. (#425 Abe)

= 4.4.5 - Dec 1 2021 =
* **Data** Fixed potential PHP notice when generating CSS/JS optimized files w/ PHP v7.4+. (Sarah Richardson/silencedgd/slr1979)
* **API** Added `LITESPEED_ESI_OFF` constant to disable ESI, when defined before the WP `init` hook.
* **API** Added `LSCWP_DEBUG_PATH` constant to specify debug log path. (khanh-nt)
* 🐞**GUI** Fixed an issue where admin messages were not displayed. (Daniel McD)
* **CDN** Used WP remote function to communicate w/ Cloudflare per WP guidance.
* **3rd** Added compatibility for Perfmatters plugin's script manager (#417 Abe)
* **3rd** Added compatibility for Elementor's Editor button when ESI is on (#418 Abe)

= 4.4.4 - Nov 23 2021 =
* **Page Optimize** Delay deletion of outdated CSS/JS files for a default of 20 days to avoid 404 errors with cached search engine copies.
* **Cache** When caching, no longer send a purge request for CSS/JS removal to avoid cache engine conflicts.
* 🐞**Core** Optimized SQL queries while autoloading if expected options are missing; reduced by 7 and 3 queries on backend and frontend respectively. (#396425 Jackson)
* **Page Optimize** Fixed a 404 issue that occured when upgrading the plugin manually, with a package upload or through the plugin manager. (Tobolo/Małgorzata/Abe)
* **API** Added `litespeed_ccss_url` and `litespeed_ucss_url` API to manipulate the request URL for CCSS and UCSS.
* **REST** Fixed a potential warning when detecting cacheable status on REST call. (rafaucau)
* **OLS** Fixed an issue where the `COOKIEHASH` constant was undefined when used with OpenLiteSpeed as an MU plugin or with network activation.
* **3rd** Sanitized POST data for nextgengallery.
* **Cloud** Sanitized GET data when linking to QUIC.cloud. (#591762 WPScan)

= 4.4.3 - Oct 13 2021 =
* 🐞**Media** Fixed an issue where WebP is served erroneously under Guest Mode on older versions of Safari. (hash73)
* 🐞**Media** Reverted regex change to fix `Lazy Load Image Parent Class Name Excludes` failure. (thpstock)
* **Purge** Disabled `Purge Delay` in the optimization process by default.
* **Conf** Dropped `.htaccess Path Settings` options for security concern. (WP)
* **Conf** Dropped `CSS HTTP/2 Push`/`JS HTTP/2 Push` options. (Kevin)
* **Conf** Set `Guest Optimization` default to OFF.
* **Conf** Set `CCSS Per URL` default to OFF to avoid consuming more quota than intended after upgrade to v4. (n111)
* **Object** Fixed an issue with Object Cache warnings during upgrade, when Guest Mode is enabled.
* ☁️**Cloud** Fixed an issue with PHP notices when inquiring about quota usage for a service not currently in use.
* **GUI** Added GO detail warning. (n111)
* **GUI** Moved "quota wil be still in use" warning from Guest Mode to Guest Optimization section.
* **API** Added `LITESPEED_CFG_HTACCESS` PHP Constant to specify .htaccess path.
* **API** Added `litespeed_qs_forbidden` hook to bypass `?LSCWP_CTRL=` query string. (minhduc)
* **API** Added `litespeed_delay_purge` hook to delay the following Purge header until the next request.
* **API** Added `litespeed_wpconfig_readonly` hook to disable `WP_CACHE` constant update based on the wp-config.php file. (#633545)

= 4.4.2 - Sep 23 2021 =
* **Purge** In order to clear pages containing 404 CSS/JS, the purge header will always be sent even in cases where purge must be delayed.
* 🐞**Purge** Fixed a potential PHP warning caused when generating different optimized filenames.
* **Cron** Dropped unnecessary HTML response in cron which sometimes resulted in wp-cron report email. (Gilles)
* **Page Optimize** Purge caused by CSS/JS file deletion will now be silent.
* **Page Optimize** Fixed an issue where the homepage failed to purge when addressing the 404 CSS/JS issue.
* **Avatar** Fixed potential localized Avatar folder creation warning. (mattk0220/josebab)
* **API** Added filter `litespeed_optm_html_after_head` to move all optimized code(UCSS/CCSS/Combined CSS/Combined JS) to be right before the `</head>` tag. (ducpl/Kris Regmi)
* **Debug** Under debug mode, cache/purge tags will be plaintext.

= 4.4.1 - Sep 16 2021 =
* 🐞**ESI** Fixed ESI failure on non-cached pages caused by `DONOTCACHEPAGE` constant.
* 🐞**Page Optimize** Fixed an issue where the minified CSS/JS file failed to update when the file was changed. (ceap80)
* 🐞**Page Optimize** Fixed an issue where the combined CSS/JS file randomly returned a 404 error when visiting the same URL with different query strings. (Abe)
* **API** Added `litespeed_const_DONOTCACHEPAGE` hook to control the cache-or-not result of the `DONOTCACHEPAGE` constant.

= 4.4 - Sep 8 2021 =
* 🌱**Crawler** Added the ability to enable or disable specific crawlers. (⭐ Contributed by Astrid Wang #PR390)
* 🌱**UCSS** Added `UCSS Inline` option. (Ankit).
* 🌱**UCSS** Added `UCSS URI Excludes` option. (RC Verma).
* 🐞**Page Optimize** Fixed an issue where combined CSS/JS files would potentially return 404 errors after a Purge All. (Special thanks to Abe & Ruikai)
* **Page Optimize** Minimized the potential for 404 errors by query string when Purging All.
* **Page Optimize** Dropped redundant query strings for minified CSS/JS files.
* **Conf** Ugrade configuration safely to avoid the issue of new functions not being found in old codebase.
* **Conf** Configuration upgrade process now adds a notification to admin pages and disables configuration save until upgrade is complete. (Lisa)
* **JS** Fixed an issue where JS Defer caused a `litespeed_var_1_ is not defined` error when enabled w/ ESI options. (Tobolo)
* 🐞**JS** Fixed an issue where `JS Delay` doesn't work for combined JS when `JS Combine` is enabled. (Special thanks to Joshua & Ankit)
* **JS** `JS Delay` now will continue loading JS, even if there is an error in the current JS loading process.
* 🐞**CCSS** If CCSS fails to generate, Load CSS Asynchronously will now be disabled. (Stars #54074166)
* 🐞**UCSS** If UCSS generation fails the generated error will no longer be served inside the file. (Ryan D)
* **Log** Updated the Debug log to use less code for prefix.
* **3rd** Always respect `DONOTCACHEPAGE` constant defination to fix DIVI dynamic css calculation process.

= 4.3 - Aug 16 2021 =
* **UCSS** Separated UCSS Purge from CCSS Purge. (⭐ Contributed by Alice Tang #PR388)
* 🐞**Cloud** Fixed an issue where CCSS/UCSS quota data failed to update locally.
* **Cloud** Added server load as a factor when detecting node availability.
* **Cloud** Improved the speed of checking daily quota and showing the related error message.
* **Cloud** Added ability to re-detect node availability if the current node is responding w/ a heavy load code.
* **Cloud** CCSS/UCSS/LQIP queue now exits immediately when quota is depleted.
* **Cloud** Replaced separate `d/regionnodes` with a single `d/nodes` in the node list API for image optimization.
* **LQIP** Fixed an issue with LQIP network compatibility. (⭐ Contributed by Alice Tang #PR387)
* **GUEST** JS no longer preloads for Guest Optimization. (Ankit)
* 🐞**Data** Fixed an issue where deleting the `cssjs` data folder causes a failure in the upgrade process. (Joshua #PR391)
* **GUI** Fixed a potential dashboard PHP warning when no queue existed. (jrmora)
* **GUI** Added daily quota on dashboard.
* **GUI** Added downgrade warning to Toolbox -> Beta Test.
* **GUI** Tuned `.litespeed-desc` class to full width in CSS.
* **Conf** `Preserve EXIF/XMP data` now defaults to ON due to copyright concerns. (Tobolo)
* 🐞**3rd** Fixed a PHP warning when using Google AMP w/ /amp as structure. (thanhstran98)

= 4.2 - Jul 29 2021 =
* **Cloud** Auto redirect to a new node if the current node is not available anymore.
* **Cloud** Combined CCSS/UCSS to sub services of Page Optimization.
* **Cloud** Added a daily quota rate limit to help mitigate the heavy service load at the beginning of the month.
* **Cloud** Cached the node IP list in order to speed up security check. (Lucas)
* 🐞**GUEST** Fixed an issue where Guest Mode remained enabled even when the UA setting is empty. (Stars)
* **GUEST** Guest Mode will no longer cache POST requests.
* **UCSS** Purging CSS/JS now purges the UCSS queue as well, to avoid failure when generating UCSS.
* **UCSS** Separated service entry `UCSS` from `CCSS`.
* **CCSS** Simplified `load_queue/save_queue/build_filepath_prefix` functions. (⭐ Contributed by Alice Tang #PR373)
* **CCSS** If CCSS request fails, details are now saved in the CSS file.
* **CCSS** Renamed CCSS ID in inline HTML from `litespeed-optm-css-rules` to `litespeed-ccss`. (Alice)
* **Page Optimize** CCSS/UCSS now supports Cloud queue/notify for asynchronous generation.
* **Page Optimize** Simplified CCSS/UCSS generation function.
* **Page Optimize** Added the ability to cancel CCSS/UCSS Cloud requests.
* **Page Optimize** Unnecessary quesry strings will now be dropped from CSS/JS combined files.
* **Crawler** Reset position now resets crawler running status too.
* **REST** Cloud request to REST will now detect whether an IP in in the Cloud IP list for security reasons.
* **Object** Enhanced Object Cache compatibility for `CONF_FILE` constant detection.
* **API** Added shorter alias `litespeed_tag` and other similar aliases for Cache Tag API.
* **API** Renamed `LITESPEED_BYPASS_OPTM` to `LITESPEED_NO_OPTM` for Page Optimization.
* **Toolbox** Dropped v3.6.4- versions in Beta Test as they will cause a fatal error in downgrade.
* **GUI** Added shortcut links to each section on the Dashboard.
* **GUI** Added UCSS whitelist usage description. (wyb)
* **GUI** Showed the default recommended values for Guest Mode UA/IPs.
* **3rd** Fixed AMP plugin compatibility. (⭐ Contributed by Alice Tang #PR368)
* **3rd** Bypassed all page optimization including CDN/WebP for AMP pages.
* **3rd** Improved compatibility with All in One SEO plugin sitemap. (arnaudbroes/flschaves #Issue372)
* **3rd** Added wsform nonce. (#365 cstrouse)
* **3rd** Added Easy Digital Download (EDD) & WP Menu Cart nonce. (#PR366 AkramiPro)
* **3rd** Improved compatibility w/ Restrict Content Pro. (Abe #PR370)
* **3rd** Improved compatibility w/ Gravity Forms. (Ruikai #371)

= 4.1 - Jun 25 2021 =
* 🌱**UCSS/CCSS/LQIP** Moved queue storage to file system from database wp-options table to lessen the IO load. (#633504)
* 🌱**3rd** Added an option to disable ESI for the WooCommerce Cart. (#358 Anna Feng/Astrid Wang)
* **ESI** Fixed an ESI nonce issue introduced in v4.0. (Andrew Choi)
* **Object** Used new `.litespeed_conf.dat` instead of `.object-cache.ini` for object cache configuration storage.
* **Conf** Now updating related files after plugin upgrade and not just after activation.
* 🌱**Guest** Added a Guest Mode JS Excludes option. (Ankit/Mamac/Rcverma)
* **Guest** Guest Mode now uses a lightweight script to update guest vary for reduced server load.
* **Guest** Guest Mode now adds missing image dimensions.
* **Guest** Guest vary will no longer update if there's already a vary in place to address the infinite loop caused by CloudFlare's incorrect cache control setting for PHP.
* **Guest** Guest vary update request will no longer be sent if `lscache_vary` is already set.
* **Guest** Added a Configurable Guest Mode UA/IP under the Tuning tab in the General menu.
* **Guest** Guest Mode now allows cron to be hooked, even when UCSS/CCSS options are off. (#338437 Stars)
* **Guest** Simplified the vary generation process under Guest Mode.
* **Guest** Added a Guest Mode HTML comment for easier debugging. (Ruikai)
* **Guest** Guest vary update ajax now bypasses potential POST cache.
* **CCSS** Added back the options `Separate CCSS Cache Post Types` and `Separate CCSS Cache URIs`. (Joshua/Ankit)
* **CCSS** CCSS/UCSS queue is now limited to a maximum of 500 entries.
* **Control** The cache control constant `LSCACHE_NO_CACHE` will now have a higher priority than the Forced Public Cache setting.
* **Crawler** The Crawler can now crawl Guest Mode pages.
* **Crawler** Fixed a potential XSS vulnerability in the Crawler settings. (#927355)
* **Crawler** The Crawler now supports a cookie value of `_null`. (Tobolo)
* **Media** Updated the default value for the Responsive Placeholder SVG to be transparent.
* **Media** WebP images in the background may now be served in Guest Mode.
* **Media** WebP images in CSS may now be bypassed if the requesting Guest Mode client doesn't support WebP.
* **Media** Fixed empty default image placeholder under Guest Mode.
* 🐞**Image Optimize** Changed the missing `$_POST` to `$post_data` so the database status is properly updated. (#345 Lucas)
* **Import** Export file is now readable to allow importing of partial configurations. (Ryan D/Joshua)
* **Page Optimize** Fixed W3 validator errors in Guest Mode. (#61393817)
* **3rd** A fatal WooCommerce error is no longer triggered by a custom theme reusing a previous LSCWP cache detection tag.
* **3rd** AMP may now bypass Guest Mode automatically.
* **Localize** Dropped the `Localize Resources` option as Guest Mode is a sufficient replacement. (Note: Due to user feedback during the development period, we have decided to reinstate this option in a future version.)
* **Cloud** Changed the WP API url.
* **Lang** Corrected a missing language folder.
* **GUI** Added a CCSS/UCSS loading page visualization. (⭐ Contributed by Astrid Wang & Anna Feng #PR360)
* **GUI** Added a warning to indicate when Guest Mode CCSS/UCSS quota is in use. (Contributed by Astrid Wang & Anna Feng #PR361)
* **GUI** Added a `litespeed-info` text color. (Astrid Wang)
* **GUI** Implemented various UI/UX improvements. (Joshua/Lisa)
* **GUI** Duplicate cloud service messages with the same content will only display once now. (Marc Dahl)
* **GUI** Added a WebP replacement warning for Guest Mode Optimization if WebP replacement is off.
* **Misc** Dropped `wp_assets` from distribution to reduce the package size. (lowwebtech)
* **Misc** Increased the new version and score detection intervals.
* **Misc** Optimized WP Assets images. (#352 lowwebtech)
* **Debug** Dropped the redundant error_log debug info.

= 4.0 - Apr 30 2021 =
* 🌱🌱🌱**Guest** Introduced `Guest Mode` for instantly cacheable content on the first visit.
* 🌱**UCSS** Added a new service: `Unique CSS`, to drop unused CSS from elements from combined CSS
* 🌱**CCSS** Added `HTML Lazyload` option. (Ankit)
* 🌱**CCSS** Added `CCSS Per URL` option to allow Critical CSS to be generated for each page instead of for each Post Type.
* 🌱**Media** Added `Add Missing Sizes` setting for improving Cumulative Layout Shift. (Fahim)
* 🌱**JS** Switched to new JS minification library for better compression and compatibility w/ template literals. (LuminSol)
* **Media** WebP may now be replaced in CSS.
* **Media** Can now drop image tags in noscript to avoid lazyload. (Abe #314 /mattthomas-photography)
* **Media** Bypass optimization if a page is not cacheable.
* **Image Optimize** Auto hook to `wp_update_attachment_metadata` to automate image gathering process, and to handle the new thumbnail generation after images are uploaded. (smerriman).
* **Image Optimize** Repeated image thumbnails won't be gathered anymore.
* **Image Optimize** Simplified the rescan/gather/upload_hook for existing image detection.
* **Image Optimize** Fixed the duplicated optimize size records in the postmeta table. (Abe #315)
* **Image Optimize** Allow either JSON POST request or normal form request in `notify_img`. (Lucas #313)
* **Image Optimize** Optimized SQL query for better efficiency. (lucas/Lauren)
* **Image Optimize** Fixed issue where rescan mass created duplicate images. (#954399)
* **Image Optimize** Image optimization pie will not show 100% anymore if there is still a small amount in the unfinished queue.
* **Image Optimize** WebP generation defaults to ON for Guest Mode.
* **Image Optimize** `Priority Line` package now can have smaller request interval.
* **ESI** Disable ESI when page is not cacheable. (titsmaker)
* **ESI** Fixed an issue where Divi was disabling all in edit mode, but couldn't disable ESI. (Abe)
* **ESI** ESI init moved under `init` hook from `plugin_loaded` hook.
* **CDN** Add basic support for CloudFlare API Tokens (Abe #320)
* **CSS** Simplified `Font Display Optimization` option.
* **CSS** Fixed manual cron timeout issue. (jesse Distad)
* **CSS** Inline CSS may now use `data-no-optimize` to be excluded from optimization. (popaionut)
* **JS** Combined `Load JS Defer` and `Load Inline JS Defer` options.
* **JS** Forced async to defer.
* **JS** Moved Google Analytics JS from constant default to setting default for removal.
* **JS** Fixed potential JS parsing issue caused by JS src being changed to data-src by other plugins. (ankit)
* **JS** Excluded spotlight from JS optimize. (tobolo)
* **CCSS** Fixed CCSS/UCSS manual cron timeout issue.
* **CCSS** Only 10 items will be kept for CCSS history.
* **CCSS** The appearance of CCSS Purge in the topbar menu will be determined by the existance of CCSS cache, and not the setting only.
* **CCSS** To avoid stuck queues when the current request keeps failing, the CCSS queue will always drop once requested.
* **CCSS** CCSS will no longer hide adminbar.
* **CCSS** CCSS may now be separate for network subsites. (Joshua)
* **CCSS** Gave CCSS a unique filename per URL per user role per subsite.
* **CCSS** Dropped `Separate CCSS Cache Post Types` option.
* **CCSS** Dropped `Separate CCSS Cache URIs` option.
* **CCSS** Subsites purge Avatar/CSS/JS/CCSS will not affect the whole network anymore.
* **CCSS** Implemented a better queue list for CCSS that auto collapses if there are more than 20 entries, and shows the total on top.
* **CSSJS** Now using separate CSS and JS folders instead of `cssjs`.
* **CSSJS** Automatically purge cache after CCSS is generated.
* **Network** Dropped network CSS/JS rewrite rules.
* **Cache** Send cache tag header whenever adding a tag to make it effective in the page optimization process.
* **Core** Used hook for buffer optimization; Used `init()` instead of `constructor`.
* **Object** Used `cls` instead of `get_instance` for init.
* **Cloud** Replaced one-time message with a dismissible-only message when the domain key has been automatically cleared due to domain/key dismatch.
* **API** Dropped function `hook_vary_add()`.
* **API** Dropped function `vary_add()`.
* **API** Dropped function `filter_vary_cookies()`.
* **API** Dropped function `hook_vary()`.
* **API** Dropped action `litespeed_vary_add`.
* **API** Dropped filter `litespeed_api_vary`.
* **API** Use `litespeed_vary_curr_cookies` and `litespeed_vary_cookies` for Vary cookie operations instead.
* **API** Dropped action `litespeed_vary_append`.
* **Vary** 3rd party vary cookies will not append into .htaccess anymore but only present in response vary header if in use.
* **Vary** Dropped function `append()`.
* **Vary** Commenter cookie is now considered cacheable.
* **Crawler** Minor update to crawler user agent to accommodate mobile_detect.php (Abe #304)
* **Data** Added a table truncate function.
* **Data** Added new tables url & url_file.
* **Data** Dropped cssjs table.
* **Data** Options/Summary data is now stored in JSON format to speed up backend visit. (#233250)
* **Data** Default `CSS Combine External and Inline` and `JS Combine External and Inline` to On for new installations for better compatibility.
* **Purge** Fixed potential purge warning for certain themes.
* **Purge** Purge will be stored for next valid visit to trigger if it is initially generated by CLI.
* **Page Optimize** `CSS Combine`/`JS Combine` will now share the same file if the contents are the same. Limited disk usage for better file usage and fewer issues with random string problems.
* **Page Optimize** Dropped option CSS/JS Cache TTL.
* **Page Optimize** Bypass optimization if page not cacheable.
* **Page Optimize** Purge CSS/JS will purge the `url_file` table too.
* **Page Optimize** Optionally store a vary with a shorter value.
* **Page Optimize** Removing query strings will no longer affect external assets. (ankit)
* **Page Optimize** Better regex for optimization parsing.
* **Page Optimize** Eliminated w3 validator for DNS prefetch and duplicated ID errors. (sumit Pandey)
* **Page Optimize** New Optimization for Guest Only option under Tuning.
* **Page Optimize** Now forbidding external link redirection for localization.
* **Debug** Implemented a better debug format for the 2nd parameter in the log.
* **GUI** Bypass page score banner when score is not detected (both 0). (ankit)
* **GUI** Fixed deprecated JQuery function warning in WP-Admin. (krzxsiek)

= 3.6.4 - Mar 15 2021 =
* **Toolbox** Fixed Beta Test upgrade error when upgrading to v3.7+.

= 3.6.3 - Mar 10 2021 =
* **Core** Fixed potential upgrade failure when new versions have changes in activation related functions.
* **Core** Upgrade process won't get deactivated anymore on Network setup.

= 3.6.2 - Feb 1 2021 =
* **Page Optimize** Fixed an issue where network purge CSS/JS caused 404 errors for subsites.
* **Page Optimize** Fixed an issue where purge CSS/JS only caused 404 errors.
* **Page Optimize** Added a notice for CSS/JS data detection and potential random string issue.
* **Page Optimize** Limited localization resources to specified .js only. (closte #292/ormonk)
* **JS** Data src may now be bypassed from JS Combine. (ankit)
* **CLI** Fixed a message typo in Purge. (flixwatchsupport)
* **Browser** Added font/otf to Browser Cache expire list. (ruikai)
* **Data** Updated data files to accept PR from dev branch only.
* **3rd** Add data-view-breakpoint-pointer to js_excludes.txt for the Events Calendar plugin. (therealgilles)
* **Cloud** Bypassed invalid requests.
* **Doc** CDN Mapping description improvement. (mihai A.)

= 3.6.1 - Dec 21 2020 =
* **WP** Tested up to WP v5.6.
* **WebP** Reverted WebP support on Safari Big Sur and Safari v14.0.1+ due to an inability to detect MacOS versions from UA. (@antomal)
* **CDN** Dropped the option `Load JQuery Remotely`.
* **CDN** Fixed CDN URL replacement issue in optimized CSS files. (@ankit)
* **CDN** Fixed an issue where CDN CLI wouldn't set mapping image/CSS/JS to OFF when `false` was the value.
* **CDN** Started using React for CDN Mapping settings.
* **GUI** Secured Server IP setting from potential XSS issues. (@WonTae Jang)
* **Toolbox** Supported both dev and master branches for Beta Test. Latest version updated to v3.6.1.
* **Purge** Purge Pages now can purge non-archive pages too.
* **Admin** Simplified the admin JS.
* **Admin** Limited crawler-related react JS to crawler page only.

= 3.6 - Dec 14 2020 =
* 🌱**WebP** Added WebP support on Safari Big Sur or Safari v14.0.1+. (@ruikai)
* 🐞**Config** Fixed an issue where new installations were not getting the correct default .htaccess content.
* **Crawler** Will auto bypass empty sub-sitemap instead of throwing an exception. (@nanoprobes @Tobolo)
* **Crawler** Now using React for Cookie Simulation settings instead of Vue.js. Dropped Vue.js.
* **Crawler** Dropped `Sitemap Generation` (will only use 3rd party sitemap for crawler).
* **CSS** Added `CSS Combine External and Inline` option for backward compatibility. (@lisa)
* **Object** Forbid .object-cache.ini visits. (@Tarik)
* **Page Optimize** Dropped `Remove Comments` option to avoid combine error.
* **CSS** Added a predefined CSS exclude file `data/css_excludes.txt`.
* **CSS** Excluded Flatsome theme random inline CSS from combine.
* **CSS** Excluded WoodMart theme from combine. (@moemauphie)
* **Page Optimize** Excluded tagDiv.com Newspaper theme dynamic CSS/JS from CSS/JS Combine.
* **CSS** Added predefined JS defer excludes list. (@Shivam)
* **JS** `data-no-defer` option now supports inline JS. (@rafaucau)
* **Media** Lazyload inline library is now bypassed by JS Combine.
* **Admin** Fixed WP-Admin console ID duplicate warnings.
* **Cloud** Dropped QUIC.cloud sync options that have long been unused.
* **CSS** Dropped `Unique CSS File` option (UCSS will always generate unique file, will use whitelist to group post type to one CSS).
* **GUI** Dropped Help tab.
* **Toolbox** Added 3.5.2 to version list.

= 3.5.2 - Oct 27 2020 =
* **CSS** `CSS Combine` is now compatible w/ inline noscript CSS. (@galbaras)
* **GUI** Added ability to manually dismiss the JS option reset message in v3.5.1 upgrade process. (#473917)
* 🐞**CSS** `CSS Excludes` setting will no longer lose items beginning w/ `#`. (@ankit)
* **API** New `litespeed_media_reset` API function for image editing purposes. (@Andro)

= 3.5.1 - Oct 20 2020 =
* **JS** Inline JS containing nonces can now be combined.
* **JS** Reset JS Combine/Defer to OFF when upgrading to avoid breaking sites.
* **JS** Added new option JS Combine External and Inline to allow backwards compatibility.
* **JS** Added Inline JS Defer option back. (@ankit)
* **Page Optimize** Dropped Inline JS Minify option and merged the feature into JS Minify.
* **JS** Pre-added jQuery to the default JS excludes/defer list for better layout compatibility for new users.
* **JS** Excluded Stripe/PayPal/Google Map from JS optimization. (@FPCSJames)
* **JS** Allowed excluded JS to still be HTTP2 pushed. (@joshua)
* **CCSS** Critical CSS now can avoid network pollution from other sites. (@ankit)
* **Toolbox** Beta Test now displays recent public versions so it is easier to revert to an older version
* **Vary** Server environment variable Vary can now be passed to original server from QUIC.cloud for non-LiteSpeed servers.
* **ESI** Improved backward compatibility for ESI nonce list. (@zach E)
* 🐞**Misc** Fixed failure of upgrade button on plugin news banner and made cosmetic improvements.
* **Doc** Added note that LSCWP works with ClassicPress.

= 3.5.0.2 - Sep 30 2020 =
* This is a temporary revert fix. Code is SAME as v3.4.2.

= 3.5.0.1 - Sep 29 2020 =
* 🔥🐞**CSS** Fixed print media query issue when having CSS Combine. (@paddy-duncan)

= 3.5 - Sep 29 2020 =
* **Page Optimize** Refactored CSS/JS optimization.
* **Page Optimize** CSS and JS Combine now each save to a single file without memory usage issues.
* **CSS** Inline CSS Minify is now a part of CSS Minify, and will respect thr original priorities. (thanks to @galbaras)
* **JS** JS Combine now generates a single JS file in the footer. (Special thanks to @ankit)
* **JS** JS Combine now combines external JS files, too. (Thanks to @ankit)
* **JS** JS Deferred Excludes now uses the original path/filename as keywords instead of the minified path/filename, when JS Minify is enabled.
* **JS** JS Combine now combines inline JS, too.
* **JS** JS Excludes may now be used for inline JS snippet.
* **Page Optimize** Inline CSS Minify and Max Combined File Size retired due to changes listed above.
* **CSS** Combined CSS Priority retired due to changes listed above.
* **JS** Exclude JQuery, Combined JS Priority, Load Inline JS Deferred, and Inline JS Deferred Excludes retired due to changes listed above.
* **JS** Predefined data file data/js_excludes.txt now available for JS Excludes.
* **ESI** Predefined data file data/esi.nonces.txt now available for ESI Nonces.
* **ESI** Remote Fetch ESI Nonces functionality retired.
* **API** Added support for new litespeed_esi_nonces filter.
* **Object** Object Cache will not try to reconnect after failure to connect in a single process.
* **CCSS** Remote read CSS will add the scheme if it is missing from the URL.
* **CCSS** CSS will no longer be prepared for a URL if 404 result is detected.
* **CCSS** Fixed most failures caused by third party CSS syntax errors.
* **CCSS** Remote read CSS will fix the scheme if the URL doesn't have it.
* **CCSS** Excluded 404 when preparing CSS before request.
* **CCSS** Adjusted CCSS timeout from 180 seconds to 30 seconds.
* **Image Optimize** Fixed the delete attachment database error that occurred when not using the image optimization service yet.
* **Media** Added iOS 14 WebP support.
* **Data** Fixed database creation failure for MySQL v8.
* **Cloud** Error code err_key will clear the domain key in order to avoid duplicate invalid requests.
* **Network** Fixed issue with object cache password file storage that occurred when resaving the settings. (#302358)
* **Misc** Fixed IP detect compatibility w/ Apache.
* **GUI** Fixed the description for Do Not Cache Categories.
* **Preload** Upgraded Instant Click to a new stable preload library. (@stasonua0)

= 3.4.2 - Sep 8 2020 =
* **CCSS** Corrected the issue that wrongly appended non-CSS files to CSS in links before sending request.
* **3rd** YITH wishlist now sends a combined single sub request for all widgets contained in one page. (LSWS v5.4.9 build 3+ required)
* **ESI** Added support for ESI combine feature.
* **GUI** Dropped banner notification for missing domain key when domain key is not initialized.
* **Log** When QC whitelist check fails, a detailed failure log is now appended.

= 3.4.1 - Sep 2 2020 =
* 🐞**CCSS** Fixed an issue where dynamically generated CSS failed with `TypeError: Cannot read property type of undefined`.
* 🐞**Page Optimize** Fixed CSS optimization compatibility for CSS dynamically generated with PHP.
* **Page Optimize** Added the ability to defer JS even when the resource is excluded from other JS optimizations. (@slr1979)
* **ESI** Added support for ESI last paramater inline value.
* **3rd** YITH Wishlist, when cached for the first time, will no longer send sub requests.

= 3.4 - Aug 26 2020 =
* 🌱**LQIP** New setting **LQIP Excludes**.
* 🌱**LQIP** Added a Clear LQIP Queue button.
* 🌱**CCSS** Added a Clear CCSS Queue button.
* **CCSS** Fixed an issue which wrongly included preloaded images in CCSS. (@pixtweaks)
* **Network** Primary site and subsite settings now display correctly.
* **Page Optimize** Noscript tags generated by LSCWP will only be dropped when the corresponding option is enabled. (@ankit)
* **DB Optimize** Fixed database optimizer conflicts w/ object cache transient setting. (#752931)
* **3rd** Fixed an issue with WooCommerce product purge when order is placed.
* **3rd** Improved WooCommerce product comment compatibility with **WooCommerce Photo Reviews Premium** plugin when using ESI.
* **CDN** Fixed Remote jQuery compatibility with WordPress v5.5. (@pixtweaks)
* **API** New API `litespeed_purge_all_object` and `litespeed_purged_all_object` action hooks.

= 3.3.1 - Aug 12 2020 =
* 🌱**Page Optimize** New option to Remove Noscript Tags. (@phuc88bmt)
* 🐞**LQIP** Fixed a critical bug that bypassed all requests in v3.3.
* **LQIP** Requests are now bypassed if domain has no credit left.
* **Page Optimize** Inline defer will be bypassed if document listener is detected in the code. (@ssurfer)
* **CCSS** Print-only styles will no longer be included in Critical CSS.
* **API** Added hooks to Purge action to handle file deletions. (@biati)
* **Cloud** Plain permalinks are no longer required for use of cloud services.
* **Data** Added an access denial to work with OpenLiteSpeed. (@spenweb #PR228)
* **GUI** Spelling and grammar adjustments. (@blastoise186 #PR253)

= 3.3 - Aug 6 2020 =
* 🌱**Page Optimize** Added a new setting, Inline JS Deferred Excludes. (@ankit)
* **Page Optimize** CSS/JS Combine/Minify file versions will be differentiated by query string hash instead of new filename to reduce DB/file system storage.
* **Page Optimize** Added the ability to use local copies of external JS files for better control over page score impacts.
* **Page Optimize** Improved combination of CSS media queries. (@galbaras)
* **Page Optimize** Reprioritized Inline JS Defer to be optimized before encoding, for a significantly smaller result.
* **LQIP** Detect if the file exists before sending LQIP request to QUIC.cloud.
* **CCSS** Sped up CCSS process significantly by sending HTML and CSS in request.
* **CCSS** Improvements to mobile CSS support in CCSS.
* **CCSS** Minimize CCSS failures by attempting to automatically fix CSS syntax errors.
* **Cloud** Domain Key will be deleted after QUIC.cloud site_not_registered error to avoid endless repeated requests.
* **CDN** CDN Original URL will default to WP Site URL if not set. (@ruikai)
* **CLI** Global output format `--format=json/yaml/dump` and `--json` support in CLI. (@alya1992)
* **CDN** Improved handling of non-image CSS `url()` sources in CDN. (@daniel McD)
* 🐞**CDN** Fixed CDN replacement conflict w/ JS/CSS Optimize. (@ankit)
* **Crawler** Only reset Crawler waiting queues when crawling begins. (@ruikai)
* **Network** Network Enable Cache is no longer reset to ON Use Network Settings in enabled. (@RavanH)
* 🐞**Activation** Fixed a PHP warning that appeared during uninstall. (@RavanH)
* **Debug** Automatically omit long strings when dumping an array to debug log.
* **Report** Subsites report now shows overwritten values along w/ original values. (#52593959)
* **REST** Improved WP5.5 REST compatibility. (@oldrup)
* **GUI** Server IP setting moved from Crawler menu to General menu.
* **GUI** Localize resources moved to Localization tab.
* **Config** News option now defaults to ON.

= 3.2.4 - Jul 8 2020 =
* **Object** New installations no longer get custom data.ini reset, as this could cause lost configuration. (@Eric)
* **ESI** Now using `svar` to load nonces more quickly. (@Lauren)
* **ESI** Fixed the conflicts between nonces in inline JS and ESI Nonces when Inline JS Deferred is enabled. (@JesseDistad)
* 🐞**ESI** Fixed Fetch Latest Predefined Nonce button.
* 🐞**Cache** Fixed an issue where mobile visits were not being cached when Cache Mobile was disabled.
* **CDN** Bypass CDN constant `LITESPEED_BYPASS_CDN` now will apply to all CDN replacements.
* **Router** Dropped `Router::get_uid()` function.
* **Crawler** Updated role simulator function for future UCSS usage.
* **GUI** Textarea will now automatically adjust the height based on the number of rows input.
* **CLI** Fixed an issue that caused WP-Cron to exit when a task errored out. (@DovidLevine @MatthewJohnson)
* **Cloud** No longer communcate with QUIC.cloud when Domain Key is not set and Debug is enabled.
* **Cloud** Score banner no longer automatically fetches a new score. (@LucasRolff)

= 3.2.3.2 - Jun 19 2020 =
* 🔥🐞**Page Optimize** Hotfix for CSS/JS minify/combine. (@jdelgadoesteban @martin_bailey)

= 3.2.3.1 - Jun 18 2020 =
* **API** New filter `litespeed_buffer_before` and `litespeed_buffer_after`. (#PR243 @joejordanbrown)

= 3.2.3 - Jun 18 2020 =
* 🌱**Page Optimize** Added Unique CSS option for future removal of unused CSS per page. (@moongear)
* **Page Optimize** Fixed an issue where Font Optimization could fail when having Load JS Deferred and Load Inline JS Deferred. (#PR241 @joejordanbrown)
* 🐞**Page Optimize** Fixed an issue with Font Display Optimization which caused Google Fonts to load incorrectly. (#PR240 @joejordanbrown @haidan)
* 🐞**Network** Use Primary Site Configuration setting for network sites now works properly with Object Cache and Browser Cache. (#56175101)
* **API** Added filter `litespeed_is_from_cloud` to detect if the current request is from QC or not. (@lechon)
* **ESI** ESI Nonce now can fetch latest list with one click.
* **GUI** Updated remaining documentation links & some minor UI tweaks. (@Joshua Reynolds)

= 3.2.2 - Jun 10 2020 =
* 🌱**Purge** Scheduled Purge URLs now supports wildcard. (#427338)
* 🌱**ESI** ESI Nonce supports wildcard match now.
* **Network** Use Primary Site Settings now can support Domain Key, and override mechanism improved. (@alican532 #96266273)
* **Cloud** Debug mode will now have no interval limit for most cloud requests. (@ruikai)
* **Conf** Default Purge Stale to OFF.
* **GUI** Purge Stale renamed to Serve Stale.
* **Data** Predefined nonce list located in `/litespeed-cache/data/esi.nonce.txt`. Pull requests welcome.
* **Debug** Limited parameter log length.
* 🐞**CDN** Fixed an issue where upgrading lost value of CDN switch setting. (#888668)
* **3rd** Caldera Forms ESI Nonce enhancement. (@paconarud16 @marketingsweet)
* **3rd** Elementor now purges correctly after post/page updates.
* **3rd** Disabled Page Optimization features on AMP to avoid webfont JS inject. (@rahulgupta1985)

= 3.2.1 - Jun 1 2020 =
* **Cloud** LQIP/CCSS rate limit tweaks. (@ianpegg)
* **Admin** Improved frontend Admin Bar menu functionality. (#708642)
* **Crawler** Fixed an issue where cleaning up a crawler map with a leftover page number would cause a MySQL error. (@saowp)
* **Image Optimize** Added WP default thumbnails to image optimization summary list. (@johnny Nguyen)
* **REST** Improved REST compatibility w/ WP4.4-. (#767203)
* **GUI** Moved Use Primary Site Configuration to General menu. (@joshua)

= 3.2 - May 27 2020 =
* **Image Optimize** Major improvements in queue management, scalability, and speed. (@LucasRolff)
* **Cloud** Implemented a series of communication enhancements. (@Lucas Rolff)
* **Crawler** Enhanced PHP 5.3 compatibility. (@JTS-FIN #230)
* **Page Optimize** Appended image template in wpDiscuz script into default lazyload image exclude list. (@philipfaster @szmigieldesign)
* **Page Optimize** Eliminated the 404 issue for CSS/JS in server environments with missing SCRIPT_URI. (@ankit)
* **Data** ENhanced summary data storage typecasting.

= 3.1 - May 20 2020 =
* 🌱**Network** Added Debug settings to network level when on network.
* 🐞**Purge** Network now can purge all.
* 🐞**Network** Fixed issue where saving the network primary site settings failed.
* **Network** Moved Beta Test to network level when on network.
* 🐞**Cache** Fixed issue in admin where new post editor was wrongly cached for non-admin roles. (@TEKFused)
* 🐞**Data** Fixed issue with crawler & img_optm table creation failure. (@berdini @piercand)
* 🐞**Core** Improved plugin activation compatibility on Windows 10 #224 (@greenphp)
* **Core** Improved compatibility for .htaccess path search.
* **Object** Catch RedisException. (@elparts)
* Fixed Script URI issue in 3.0.9 #223 (@aonsyed)
* **Image Optimize** Show thumbnail size set list in image optimization summary. (@Johnny Nguyen)
* **Debug** Parameters will now be logged.

= 3.0.9 - May 13 2020 =
* **Purge** Comment cache can be successfully purged now.
* **Data** Better MySQL charset support for crawler/image optimize table creation. (@Roshan Jonah)
* **API** New hook to fire after Purge All. (@salvatorefresta)
* **Crawler** Resolve IP for crawler.
* **Task** PHP5.3 Cron compatibility fix.
* **3rd** Elementor edit mode compatibility.
* **Page Optimize** Fixed an issue where Purge Stale returned 404 for next visitor on CSS/JS.
* **Page Optimize** Fixed the PHP warning when srcset doesn't have size info inside. (@gvidano)
* **Cloud** Fixed the potential PHP warning when applying for the domain key.
* **Core** PHP __DIR__ const replacement. (@MathiasReker)

= 3.0.8.6 - May 4 2020 =
* **CCSS** Bypassed CCSS functionality on frontend when domain key isn't setup yet.
* **Cloud** Fixed WP node redetection bug when node expired. (@Joshua Reynolds)
* **Crawler** Fixed an issue where URL is wrongly blacklisted when using ADC.

= 3.0.8.5 - May 1 2020 =
* 🔥🐞**3rd** Hotfix for WPLister critical error due to v3.0.8.4 changes.
* **Image Optimize** Unfinished queue now will get more detailed info to indicate the proceeding status on node.
* **CLI** Options can now use true/false as value for bool. (@gavin)
* **CLI** Detect error if the ID does not exist when get/set an option value.
* **Doc** An API comment typo for `litespeed_esi_load-` is fixed.

= 3.0.8.4 - Apr 30 2020 =
* 🌱**Crawler** New setting: Sitemap timeout. (#364607)
* **Image Optimize** Images that fail to optimize are now counted to increase next request limit.
* **Cloud** Redetect fastest node every 3 days.
* **Cloud** Suppressed auto upgrade version detection error. (@marc Dahl)
* **3rd** 3rd party namespace compatibility. (#366352)

= 3.0.8.3 - Apr 28 2020 =
* **Cloud** Better compatibility for the Link to QUIC.cloud operation. (@Ronei de Sousa Almeida)
* **Image Optimize** Automatically clear invalid image sources before sending requests. (@Richard Hordern)

= 3.0.8.2 - Apr 27 2020 =
* **GUI** Corrected the Request Domain Key wording.

= 3.0.8.1 - Apr 27 2020 =
* **Object** Object cache compatibility for upgrade from v2.9.9- versions.

= 3.0.8 - Apr 27 2020 =
* Released v3 on WordPress officially.

= 3.0.4 - Apr 23 2020 =
* **Cloud** Apply Domain Key now receives error info in next apply action if failed to generate.
* **GUI** Apply Domain Key timeout now displays troubleshooting guidance.
* **REST** Added /ping and /token to REST GET for easier debug.
* **Cache** Dropped `advanced-cache.php` file detection and usage.

= 3.0.3 - Apr 21 2020 =
* **Conf** Settings from all options (data ini, defined constant, and forced) will be filtered and cast to expected type.
* **Upgrade** CDN mapping and other multiple line settings will now migrate correctly when upgrading from v2 to v3.

= 3.0.2 - Apr 17 2020 =
* **GUI** More guidance on domain key setting page.
* **Cloud** Now Apply Domain Key will append the server IP if it exists in Crawler Server IP setting.

= 3.0.1 - Apr 16 2020 =
* **Data** Increased timeout for database upgrade related to version upgrade. Display a banner while update in progress.
* **Page Optimize** All appended HTML attributes now will use double quotes to reduce the conflicts when the optimized resources are in JS snippets.

= 3.0 - Apr 15 2020 =
* 🌱**Media** LQIP (Low Quality Image Placeholder).
* 🌱**Page Optimize** Load Inline JS Deferred Compatibility Mode. (Special thanks to @joe B - AppsON)
* 🌱**Cloud** New QUIC.cloud API key setting.
* 🌱**ESI** New ESI nonce setting.
* 🌱**Media** JPG quality control. (@geckomist)
* 🌱**Media** Responsive local SVG placeholder.
* 🌱**Discussion** Gravatar warmup cron.
* 🌱**DB** Table Engine Converter tool. (@johnny Nguyen)
* 🌱**DB** Database summary: Autoload size. (@JohnnyNguyen)
* 🌱**DB** Database summary: Autoload entries list.
* 🌱**DB** Revisions older than. (@thememasterguru)
* 🌱**Cache** Forced public cache setting. (#308207)
* 🌱**Crawler** New timeout setting to avoid incorrect blacklist addition. (#900171)
* 🌱**Htaccess** Frontend & backend .htaccess path customize. (@jon81)
* 🌱**Toolbox** Detailed Heartbeat Control (@K9Heaven)
* 🌱**Purge** Purge Stale setting.
* 🌱**Page Optimize** Font display optimization. (@Joeee)
* 🌱**Page Optimize** Google font URL display optimization.
* 🌱**Page Optimize** Load Inline JS deferred.
* 🌱**Page Optimize** Store gravatar locally. (@zzTaLaNo1zz @JohnnyNguyen)
* 🌱**Page Optimize** DNS prefetch control setting.
* 🌱**Page Optimize** Lazy Load Image Parent Class Name Excludes. (@pako69)
* 🌱**Page Optimize** Lazy load iframe class excludes. (@vnnloser)
* 🌱**Page Optimize** Lazy load exclude URIs. (@wordpress_fan1 @aminaz)
* 🌱**GUI** New Dashboard and new menus.
* 🌱**Image Optimize** Supported GIF WebP optimization. (@Lucas Rolff)
* 🌱**Image Optimize** New workflow for image optimization (Gather first, request second).
* 🌱**Image Optimize** The return of Rescan.
* 🌱**CLI** Get single option cmd.
* 🌱**CLI** QUIC.cloud cmd supported.
* 🌱**CLI** CLI can send report now.
* 🌱**Health** Page speed and page score now are in dashboard.
* 🌱**Conf** Supported consts overwritten of `LITESPEED_CONF__` for all settings. (@menathor)
* 🌱**REST** New REST TTL setting.  (@thekendog)
* 🌱**CDN** New setting `HTML Attribute To Replace`. CDN can now support any HTML attribute to be replaced. (@danushkaj91)
* 🌱**Debug** Debug URI includes/excludes setings.
* 🌱**Crawler** 🐞 Support for multiple domains in custom sitemap. (@alchem)
* 🌱**Crawler** New Crawler dashboard. New sitemap w/ crawler status. New blacklist w/ reason.
* 🌱**Media** LQIP minimum dimensions setting. (@Lukasz Szmigiel)
* **Crawler** Able to add single rows to blacklist.
* **Crawler** Crawler data now saved into database instead of creating new files.
* **Crawler** Larger timeout to avoid wrongly added to blacklist.
* **Crawler** Manually changed the priority of mobile and WebP. (@rafaucau)
* **Browser** Larger Browser Cache TTL for Google Page Score improvement. (@max2348)
* **Task** Task refactored. Disabled cron will not show in cron list anymore.
* **Task** Speed up task load speed.
* **ESI** Added Bloom nonce to ESI for Elegant Themes.
* **Cloud** Able to redetect cloud nodes now.
* **Img_optm** Fixed stale data in redirected links.
* **Lazyload** CSS class `litespeed_lazyloaded` is now appended to HTML body after lazyload is finished. (@Adam Wilson)
* **Cache** Default drop qs values. (@gijo Varghese)
* **LQIP** Show all LQIP images in Media column.
* **CDN** Can now support custom REST API prefix other than wp-json. (#174 @therealgilles)
* **IAPI** Used REST for notify/destroy/check_img; Removed callback passive/aggreesive IAPI func
* **CSSJS** Saved all static files to litespeed folder; Uninstallation will remove static cache folder too; Reduced .htaccess rules by serving CSS/JS directly.
* **Object** Fixed override different ports issue. (@timofeycom #ISSUE178)
* **Conf** DB Tables will now only create when activating/upgrading/changing settings.
* **DB** Simplified table operation funcs.
* **CSSJS** Bypassed CSS/JS generation to return 404 if file is empty (@grubyy)
* **CSSJS** Inline JS defer will not conflict with JS inline optm anymore.
* **CDN** settings will not be overwritten by primary settings in network anymore. (@rudi Khoury)
* **OPcache** Purged all opcache when updating cache file. (@closte #170)
* **CLI** CLI cmd renamed.
* **CLI** Well-formated table to show all options.
* **Purge** Only purge related posts that have a status of "published" to avoid unnecessary "draft" purges. (@Jakub Knytl)
* **GUI** Removed basic/adv mode for settings. Moved non-cache settings to its own menu.
* **Htaccess** Protected .htaccess.bk file. Only kept one backup. (@teflonmann)
* **Crawler** Crawler cookie now support `_null` as empty value.
* **Crawler** Avoid crawler PHP fatal error on Windows OS. (@technisolutions)
* **Admin** Simplified admin setting logic.
* **Conf** Multi values settings now uniformed to multi lines for easier setting.
* **Conf** New preset default data file `data/consts.default.ini`.
* **Conf** Config setting renamed and uniformed.
* **Conf** Dropped `Conf::option()`. Used `Conf::val()` instead.
* **Conf** Improved conf initialization and upgrade convertion workflow.
* **Core** Code base refactored. New namespace LiteSpeed.
* **API** New API: iframe lazyload exclude filter.
* **GUI** human readable seconds. (@MarkCanada)
* **API** API refactored. * NOTE: All 3rd party plugins that are using previous APIs, especially `LiteSpeed_Cache_API`, need to be adjusted to the latest one. Same for ESI blocks.* ESI shortcode doesn't change.
* **API** New hook `litespeed_update_confs` to settings update.
* **API** New Hooks `litespeed_frontend_shortcut` and `litespeed_backend_shortcut` for dropdown menu. (@callaloo)
* **API** Removed `litespeed_option_*` hooks. Use `litespeed_force_option` hook insteadly
* **API** Renamed `litespeed_force_option` to `litespeed_conf_force`.
* **API** Removed function `litespeed_purge_single_post`.
* **REST** New rest API to fetch public IP.
* **GUI** Hiding Cloudflare/Object Cache/Cloud API key credentials. (@menathor)
* **GUI** Renamed all backend link tag from lscache to litespeed.
* **GUI** fixed duplicated form tag.
* **GUI** Fix cron doc link. (@arnab Mohapatra)
* **GUI** Frontend adminbar menu added `Purge All` actions. (@Monarobase)
* **GUI** Localized vue.js to avoid CloudFlare cookie. (@politicske)
* **GUI** Always show optm column in Media Library for future single row optm operation. (@mikeyhash)
* **GUI** Displayed TTL range below the corresponding setting.
* **GUI** GUI refactored.
* **Debug** Report can now append notes.
* **3rd** Default added parallax-image to webp replacement for BB.
* **3rd** User Switching plugin compatibility. (@robert Staddon)
* **3rd** Beaver Builder plugin compatibility with v3.0.
* **3rd** Avada plugin compatibility w/ BBPress. (@pimg)
* **3rd** WooCommerce PayPal Checkout Gateway compatibility. (#960642 @Glen Cabusas)
* **Network** Fixed potential timeout issue when containing a large volume of sites. (@alican532)
* **Debug** `Disable All Features` now will see the warning banner if ON.
* **Debug** Dropped `log filters` section.
* **Debug** Debug and Tools sections combined into new `Toolbox` section.
* 🐞**Crawler** Multi sites will now use separate sitemap even when `Use Primary Site` is ON. (@mrhuynhanh)
* 🐞**Img_optm** Fixed large volume image table storage issue. (#328956)
* 🐞 **Cloud** Cloud callback hash validation fixed OC conflict. (@pbpiotr)
* 🎊 Any user that had the contribution to our WP community or changelog (even just bug report/feedback/suggestion) can apply for extra credits in QUIC.cloud.

= 2.9.9.2 - Nov 24 2019 =
* 🌱**GUI** New settings to limit News Feed to plugin page only.

= 2.9.9.1 - Nov 18 2019 =
* 🌱**Env** Environment Report can now append a passwordless link for support access without wp-admin password.
* **Admin** The latest v3.0 beta test link may now be shown on the admin page when it's available.
* **3rd** Compatibility with [DoLogin Security](https://wordpress.org/plugins/dologin/).
* 🐞**ESI** Fixed a failure issue with Vary Group save. (@rafasshop)
* 🐞**3rd** In browsers where WebP is not supported, Divi image picker will no longer serve WebP. (@Austin Tinius)

= 2.9.9 - Oct 28 2019 =
* <strong>Core</strong>: Preload all classes to avoid getting error for upcoming v3.0 upgrade.
* <strong>Object</strong>: Improved compatibility with upcoming v3.0 release.
* <strong>ESI</strong>: Unlocked ESI for OLS in case OLS is using QUIC.cloud CDN which supports ESI.
* <strong>3rd</strong>: Elementor Edit button will now show when ESI enabled. (#PR149 #335322 @maxgorky)
* 🐞<strong>Media</strong>: Fixed missing Media optimization column when Admin role is excluded from optimization in settings. (@mikeyhash @pako69 @dgilfillan)

= 2.9.8.7 - Oct 11 2019 =
* <strong>3rd</strong>: Enhanced WP stateless compatibility. (#PR143)
* <strong>3rd</strong>: Fixed a PHP warning caused by previous PR for AMP. (#PR176)

= 2.9.8.6 - Sep 24 2019 =
* <strong>3rd</strong>: Bypassed page optimizations for AMP. (#359748 #PR169)
* <strong>GUI</strong>: Firefox compatibility with radio button state when reloading pages. (#288940 #PR162)
* <strong>GUI</strong>: Updated Slack invitation link. (#PR173)

= 2.9.8.5 - Aug 21 2019 =
* <strong>CCSS</strong>: Removed potential PHP notice when getting post_type. (@amcgiffert)
* <strong>CDN</strong>: Bypassed CDN replacement on admin page when adding media to page/post. (@martin_bailey)
* 🐞<strong>Media</strong>: Fixed inability to update or destroy postmeta data for child images. (#167713)

= 2.9.8.4 - Jul 25 2019 =
* <strong>Object</strong>: Increased compatibility with phpredis 5.0.
* <strong>Object</strong>: Appended `wc_session_id` to default Do Not Cache Groups setting to avoid issue where WooCommerce cart items were missing when Object Cache is used. NOTE: Existing users must add `wc_session_id` manually! (#895333)
* <strong>CSS</strong>: Added null onload handler for CSS async loading. (@joejordanbrown)
* 🕷️: Increased crawler timeout to avoid wrongly adding a URL to the blacklist.
* <strong>3rd</strong>: WooCommerce Advanced Bulk Edit can now purge cache automatically.

= 2.9.8.3 - Jul 9 2019 =
* <strong>CSS</strong>: Enhanced the CSS Minify compatibility for CSS with missing closing bracket syntax errors. (@fa508210020)
* 🕷️: Crawler now supports both cookie and no-cookie cases. (@tabare)
* <strong>CCSS</strong>: Enhanced compatibility with requested pages where meta info size exceeds 8k. (@Joe B)
* <strong>CCSS</strong>: No longer processing "font" or "import" directives as they are not considered critical. (@Ankit @Joe B)
* <strong>IAPI</strong>: Removed IPv6 from all servers to avoid invalid firewall whitelist.

= 2.9.8.2 - Jun 17 2019 =
* 🔥🐞 <strong>3rd</strong>: Fixed PHP 5.3 compatibility issue with Facetwp.

= 2.9.8.1 - Jun 17 2019 =
* <strong>3rd</strong>: Set ESI template hook priority to highest number to prevent ESI conflict with Enfold theme. (#289354)
* <strong>3rd</strong>: Improved Facetwp reset button compatibility with ESI. (@emilyel)
* <strong>3rd</strong>: Enabled user role change to fix duplicate login issue for plugins that use alternative login processes. (#114165 #717223 @sergiom87)
* <strong>GUI</strong>: Wrapped static text with translate function. (@halilemreozen)

= 2.9.8 - May 22 2019 =
* <strong>Core</strong>: Refactored loading priority so user related functions & optimization features are set after user initialization. (#717223 #114165 #413338)
* <strong>Media</strong>: Improved backup file calculation query to prevent out-of-memory issue.
* <strong>Conf</strong>: Feed cache now defaults to ON.
* <strong>API</strong>: Fully remote attachment compatibility API of image optimization now supported.
* 🕷️: Bypassed vary change for crawler; crawler can now simulate default vary cookie.
* <strong>ESI</strong>: Refactored ESI widget. Removed `widget_load_get_options()` function.
* <strong>ESI</strong>: Changed the input name of widget fields in form.
* <strong>3rd</strong>: Elementor can now save ESI widget settings in frontend builder.
* <strong>3rd</strong>: WP-Stateless compatibility.
* <strong>IAPI</strong>: Image optimization can now successfully finish the destroy process with large volume images with automatic continual mode.
* 🐞<strong>CDN</strong>: Fixed issue with Load JQuery Remotely setting where WP 5.2.1 provided an unexpected jQuery version.
* 🐞<strong>3rd</strong>: Login process now gets the correct role; fixed double login issue.

= 2.9.7.2 - May 2 2019 =
* <strong>Conf</strong>: Enhanced compatibility when an option is not properly initialized.
* <strong>Conf</strong>: Prevent non-array instance in widget from causing 500 error. (#210407)
* <strong>CCSS</strong>: Increase CCSS generation timeout to 60s.
* <strong>Media</strong>: Renamed lazyload CSS class to avoid conflicts with other plugins. (@DynamoProd)
* <strong>JS</strong>: Improved W3 validator. (@istanbulantik)
* <strong>QUIC</strong>: Synced cache tag prefix for static files cache.
* <strong>ESI</strong>: Restored query strings to ESI admin bar for accurate rendering. (#977284)
* <strong>ESI</strong>: Tweaked ESI init priority to honor LITESPEED_DISABLE_ALL const. ESI will now init after plugin loaded.
* 🐞<strong>ESI</strong>: No longer initialize ESI if ESI option is OFF.
* <strong>API</strong>: New "Disable All" API function.
* <strong>API</strong>: New "Force public cache" API function.
* 🐞<strong>Vary</strong>: Fixed an issue with saving vary groups.
* 🐞<strong>IAPI</strong>: Fixed an issue where image md5 validation failed due to whitespace in the image path.
* 🐞<strong>3rd</strong>: Bypass all optimization/ESI/Cache features when entering Divi Theme Builder frontend editor.
* 🐞<strong>3rd</strong>: Fixed an issue where DIVI admin bar exit button didn't work when ESI was ON.

= 2.9.7.1 - Apr 9 2019 =
* <strong>Purge</script>: Purge All no longer includes Purge CCSS/Placeholder.
* <strong>3rd</strong>: Divi Theme Builder no longer experiences nonce expiration issues in the contact form widget. (#475461)

= 2.9.7 - Apr 1 2019 =
* 🌱🌱🌱 QUIC.cloud CDN feature. Now Apache/Nginx can use LiteSpeed cache freely.

= 2.9.6 - Mar 27 2019 =
* 🌱<strong>IAPI</strong>: Appended XMP to `Preserve EXIF data` setting. WebP will now honor this setting. (#902219)
* <strong>Object</script>: Fixed SASL connection with LSMCD.
* <strong>ESI</strong>: Converted ESI URI parameters to JSON; Added ESI validation.
* <strong>Import</strong>: Import/Export will now use JSON format. <strong>Please re-export any backed up settings. Previous backup format is no longer recognized.</strong>
* <strong>Media</strong>: WebP replacement will honor `Role Excludes` setting now. (@mfazio26)
* <strong>Data</strong>: Forbid direct visit to const.default.ini.
* <strong>Utility</strong>: Can handle WHM passed in `LITESPEED_ERR` constant now.
* <strong>IAPI</strong>: Communicate via JSON encoding.
* <strong>IAPI</strong>: IAPI v2.9.6.

= 2.9.5 - Mar 14 2019 =
* 🌱 Auto convert default WordPress nonce to ESI to avoid expiration.
* 🌱 <strong>API</strong>: Ability to easily convert custom nonce to ESI by registering `LiteSpeed_Cache_API::nonce_action`.
* <strong>OPTM</strong>: Tweaked redundant attr `data-no-optimize` in func `_analyse_links` to `data-ignore-optimize` to offer the API to bypass optimization but still move src to top of source code.
* <strong>API</strong>: Renamed default nonce ESI ID from `lscwp_nonce_esi` to `nonce`.
* <strong>API</strong>: Added WebP generation & validation hook API. (@alim #wp-stateless)
* <strong>API</strong>: Added hook to bypass vary commenter check. (#wpdiscuz)
* <strong>Doc</strong>: Clarified Cache Mobile description. (@JohnnyNguyen)
* <strong>Doc</strong>: Replaced incorrect link in description. (@JohnnyNguyen)
* <strong>3rd</strong>: Improved wpDiscuz compatibility.
* 🐞<strong>3rd</strong>: Fixed Divi Theme Builder comment compatibility on non-builder pages. (#410919)
* <strong>3rd</strong>: Added YITH ESI adjustment.

= 2.9.4.1 - Feb 28 2019 =
* 🔥🐞<strong>Tag</strong>: Fixed issue where unnecessary warning potentially displayed after upgrade process when object cache is enabled.

= 2.9.4 - Feb 27 2019 =
* 🐞<strong>REST</strong>: New REST class with better WP5 Gutenberg and internal REST call support when ESI is embedded.
* <strong>ESI</strong>: ESI block ID is now in plain text in ESI URL parameters.
* 🐞<strong>ESI</strong>: Fixed a redundant ESI 301 redirect when comma is in ESI URL.
* <strong>ESI</strong>: REST call can now parse shortcodes in ESI.
* <strong>API</strong>: Changed ESI `parse_esi_param()` function to private and `load_esi_block` function to non-static.
* <strong>API</strong>: Added `litespeed_is_json` hook for buffer JSON conversion.
* <strong>GUI</strong>: Prepended plugin name to new version notification banner.
* <strong>3rd</strong>: WPML multi domains can now be handled in optimization without CDN tricks.

= 2.9.3 - Feb 20 2019 =
* <strong>ESI</strong>: ESI shortcodes can now be saved in Gutenberg editor.
* <strong>ESI</strong>: ESI now honors the parent page JSON data type to avoid breaking REST calls (LSWS 5.3.6+).
* <strong>ESI</strong>: Added is_json parameter support for admin_bar.
* <strong>ESI</strong>: Simplified comment form code.
* <strong>3rd</strong>: Better page builder plugin compatibility within AJAX calls.
* <strong>3rd</strong>: Compatibility with FacetWP (LSWS 5.3.6+).
* <strong>3rd</strong>: Compatibility with Beaver Builder.
* <strong>Debug</strong>: Added ESI buffer content to log.
* <strong>Tag</strong>: Only append blog ID to cache tags when site is part of a network.
* <strong>IAPI</strong>: Optimized database query for pulling images.
* <strong>GUI</strong>: Added more plugin version checking for better feature compatibility.
* <strong>GUI</strong>: Ability to bypass non-critical banners with the file .litespeed_no_banner.
* <strong>Media</strong>: Background image WebP replacement now supports quotes around src.

= 2.9.2 - Feb 5 2019 =
* <strong>API</strong>: Add a hook `litespeed_esi_shortcode-*` for ESI shortcodes.
* <strong>3rd</strong>: WooCommerce can purge products now when variation stock is changed.
* 🐞🕷️: Forced HTTP1.1 for crawler due to a CURL HTTP2 bug.

= 2.9.1 - Jan 25 2019 =
* <strong>Compatibility</strong>: Fixed fatal error for PHP 5.3.
* <strong>Compatibility</strong>: Fixed PHP warning in htmlspecialchars when building URLs. (@souljahn2)
* <strong>Media</strong>: Excluded invalid image src from lazyload. (@andrew55)
* <strong>Optm</strong>: Improved URL compatibility when detecting closest cloud server.
* <strong>ESI</strong>: Supported JSON format comment format in ESI with `is_json` parameter.
* <strong>API</strong>: Added filters to CCSS/CSS/JS content. (@lhoucine)
* <strong>3rd</strong>: Improved comment compatibility with Elegant Divi Builder.
* <strong>IAPI</strong>: New Europe Image Optimization server (EU5). <strong>Please whitelist the new [IAPI IP List](https://wp.api.litespeedtech.com/ips).</strong>
* <strong>GUI</strong>: No longer show banners when `Disable All` in `Debug` is ON. (@rabbitwordpress)
* <strong>GUI</strong>: Fixed button style for RTL languages.
* <strong>GUI</strong>: Removed unnecessary translation in report.
* <strong>GUI</strong>: Updated readme wiki links.
* <strong>GUI</strong>: Fixed pie styles in image optimization page.

= 2.9 - Dec 31 2018 =
* 🌱<strong>Media</strong>: Lazy Load Image Classname Excludes. (@thinkmedia)
* 🌱: New EU/AS cloud servers for faster image optimization handling.
* 🌱: New EU/AS cloud servers for faster CCSS generation.
* 🌱: New EU/AS cloud servers for faster responsive placeholder generation.
* 🌱<strong>Conf</strong>: Ability to set single options via link.
* 🌱<strong>Cache</strong>: Ability to add custom TTLs to Force Cache URIs.
* <strong>Purge</strong>: Added post type to Purge tags.
* <strong>Purge</strong>: Redefined CCSS page types.
* <strong>Core</strong>: Using Exception for .htaccess R/W.
* <strong>IAPI</strong>: <strong>New cloud servers added. Please whitelist the new [IAPI IP List](https://wp.api.litespeedtech.com/ips).</strong>
* <strong>Optm</strong>: Trim BOM when detecting if the page is HTML.
* <strong>GUI</strong>: Added PageSpeed Score comparison into promotion banner.
* <strong>GUI</strong>: Refactored promotion banner logic.
* <strong>GUI</strong>: Removed page optimized comment when ESI Silence is requested.
* <strong>GUI</strong>: WHM transient changed to option instead of transient when storing.
* <strong>GUI</strong>: Appending more descriptions to CDN filetype setting.
* <strong>IAPI</strong>: Removed duplicate messages.
* <strong>IAPI</strong>: Removed taken_failed/client_pull(duplicated) status.
* <strong>Debug</strong>: Environment report no longer generates hash for validation.
* <strong>3rd</strong>: Non-cacheable pages no longer punch ESI holes for Divi compatibility.
* 🐞<strong>Network</strong>: Added slashes for mobile rules when activating plugin.
* 🐞<strong>CCSS</strong>: Eliminated a PHP notice when appending CCSS.

= 2.8.1 - Dec 5 2018 =
* 🐞🕷️: Fixed an activation warning related to cookie crawler. (@kacper3355 @rastel72)
* 🐞<strong>Media</strong>: Replace safely by checking if pulled images is empty or not first. (@Monarobase)
* <strong>3rd</strong>: Shortcode ESI compatibility with Elementor.

= 2.8 - Nov 30 2018 =
* 🌱: ESI shortcodes.
* 🌱: Mobile crawler.
* 🌱: Cookie crawler.
* <strong>API</strong>: Can now add `_litespeed_rm_qs=0` to bypass Remove Query Strings.
* <strong>Optm</strong>: Removed error log when minify JS failed.
* 🐞<strong>Core</strong>: Fixed a bug that caused network activation PHP warning.
* <strong>Media</strong>: Removed canvas checking for WebP to support TOR. (@odeskumair)
* <strong>Media</strong>: Eliminated potential image placeholder PHP warning.
* <strong>3rd</strong>: Bypassed Google recaptcha from Remove Query Strings for better compatibility.
* <strong>IAPI</strong>: Showed destroy timeout details.
* <strong>Debug</strong>: Moved Google Fonts log to advanced level.
* <strong>GUI</strong>: Replaced all Learn More links for functions.
* <strong>GUI</strong>: Cosmetic updates including Emoji.
* 🕷️: Removed duplicated data in sitemap and blacklist.

= 2.7.3 - Nov 26 2018 =
* <strong>Optm</strong>: Improved page render speed with Web Font Loader JS library for Load Google Fonts Asynchronously.
* <strong>Optm</strong>: Directly used JS library files in plugin folder instead of short links `/min/`.
* <strong>Optm</strong>: Handled exceptions in JS optimization when meeting badly formatted JS.
* <strong>3rd</strong>: Added Adobe Lightroom support for NextGen Gallery.
* <strong>3rd</strong>: Improved Postman app support for POST JSON requests.
* <strong>IAPI</strong>: <strong>US3 server IP changed to 68.183.60.185</strong>.

= 2.7.2 - Nov 19 2018 =
* 🌱: Auto Upgrade feature.
* <strong>CDN</strong>: Bypass CDN for cron to avoid WP jQuery deregister warning.

= 2.7.1 - Nov 15 2018 =
* 🌱<strong>CLI</strong>: Ability to set CDN mapping by `set_option litespeed-cache-cdn_mapping[url][0] https://url`.
* 🌱<strong>CDN</strong>: Ability to customize default CDN mapping data in default.ini.
* 🌱<strong>API</strong>: Default.ini now supports both text-area items and on/off options.
* <strong>Vary</strong>: Refactored Vary and related API.
* <strong>Vary</strong>: New hook to manipulate vary cookies value.
* <strong>Core</strong>: Activation now can generate Object Cache file.
* <strong>Core</strong>: Unified Object Cache/rewrite rules generation process across activation/import/reset/CLI.
* <strong>Core</strong>: Always hook activation to make activation available through the front end.
* 🐞<strong>IAPI</strong>: Fixed a bug where environment report gave incorrect image optimization data.
* 🐞<strong>OLS</strong>: Fixed a bug where login cookie kept showing a warning on OpenLiteSpeed.
* 🐞<strong>Core</strong>: Fixed a bug where Import/Activation/CLI was missing CDN mapping settings.
* <strong>API</strong>: <strong>Filters `litespeed_cache_media_lazy_img_excludes/litespeed_optm_js_defer_exc` passed-in parameter is changed from string to array.</strong>

= 2.7 - Nov 2 2018 =
* 🌱: Separate Purge log for better debugging.
* <strong>3rd</strong>: Now fully compatible with WPML.
* <strong>IAPI</strong>: Sped up Image Optimization workflow.
* <strong>GUI</strong>: Current IP now shows in Debug settings.
* <strong>GUI</strong>: Space separated placeholder queue list for better look.
* <strong>IAPI</strong>: <strong>EU3 server IP changed to 165.227.131.98</strong>.

= 2.6.4.1 - Oct 25 2018 =
* 🔥🐞<strong>Media</strong>: Fixed a bug where the wrong table was used in the Image Optimization process.
* <strong>IAPI</strong>: IAPI v2.6.4.1.

= 2.6.4 - Oct 24 2018 =
* 🌱: Ability to create custom default config options per hosting company.
* 🌱: Ability to generate mobile Critical CSS.
* 🐞<strong>Media</strong>: Fixed a bug where Network sites could incorrectly override optimized images.
* 🐞<strong>CDN</strong>: Fixed a bug where image URLs containing backslashes were matched.
* <strong>Cache</strong>: Added default Mobile UA config setting.
* <strong>GUI</strong>: Fixed unknown shortcut characters for non-English languages Setting tabs.

= 2.6.3 - Oct 18 2018 =
* 🌱: Ability to Reset All Options.
* 🌱<strong>CLI</strong>: Added new `lscache-admin reset_options` command.
* <strong>GUI</strong>: Added shortcuts for more of the Settings tabs.
* <strong>Media</strong>: Updated Lazy Load JS library to the most recent version.
* There is no longer any need to explicitly Save Settings upon Import.
* Remove Query String now will remove *all* query strings in JS/CSS static files.
* <strong>IAPI</strong>: Added summary info to debug log.

= 2.6.2 - Oct 11 2018 =
* <strong>Setting</strong>: Automatically correct invalid numeric values in configuration settings upon submit.
* 🐞<strong>Media</strong>: Fixed the issue where iframe lazy load was broken by latest Chrome release. (@ofmarconi)
* 🐞: Fixed an issue with Multisite where subsites failed to purge when only primary site has WooCommerce . (@kierancalv)

= 2.6.1 - Oct 4 2018 =
* 🌱: Ability to generate separate Critical CSS Cache for Post Types & URIs.
* <strong>API</strong>: Filter `litespeed_frontend_htaccess` for frontend htaccess path.
* <strong>Media</strong>: Removed responsive placeholder generation history to save space.

= 2.6.0.1 - Sep 24 2018 =
* 🔥🐞: Fixed an issue in responsive placeholder generation where redundant history data was being saved and using a lot of space.

= 2.6 - Sep 22 2018 =
* <strong>Vary</strong>: Moved `litespeed_cache_api_vary` hook outside of OLS condition for .htaccess generation.
* <strong>CDN</strong>: Trim spaces in original URL of CDN setting.
* <strong>API</strong>: New filter `litespeed_option_` to change all options dynamically.
* <strong>API</strong>: New `LiteSpeed_Cache_API::force_option()` to change all options dynamically.
* <strong>API</strong>: New `LiteSpeed_Cache_API::vary()` to set default vary directly for easier compaitiblity with WPML WooCommerce Multilingual.
* <strong>API</strong>: New `LiteSpeed_Cache_API::nonce()` to safely and easily allow caching of wp-nonce.
* <strong>API</strong>: New `LiteSpeed_Cache_API::hook_vary_add()` to add new vary.
* <strong>Optm</strong>: Changed HTML/JS/CSS optimization options assignment position from constructor to `finalize()`.
* <strong>Doc</strong>: Added nonce to FAQ and mentioned nonce in 3rd Party Compatibility section.
* <strong>GUI</strong>: Moved inline minify to under html minify due to the dependency.
* <strong>3rd</strong>: Cached Aelia CurrencySwitcher by default.
* 🐞: Fixed issue where enabling remote JQuery caused missing jquery-migrate library error.

= 2.5.1 - Sep 11 2018 =
* 🌱 Responsive placeholder. (@szmigieldesign)
* Changed CSS::ccss_realpath function scope to private.
* 🐞 Detected JS filetype before optimizing to avoid PHP source conflict. (@closte #50)

= 2.5 - Sep 6 2018 =
* [IMPROVEMENT] <strong>CLI</strong> can now execute Remove Original Image Backups. (@Shon)
* [UPDATE] Fixed issue where WP-PostViews documentation contained extra slashes. (#545638)
* [UPDATE] Check LITESPEED_SERVER_TYPE for more accurate LSCache Disabled messaging.
* [IAPI] Fixed a bug where optimize/fetch error notification was not being received. (@LucasRolff)

= 2.4.4 - Aug 31 2018 =
* [NEW] <strong>CLI</strong> can now support image optimization. (@Shon)
* [IMPROVEMENT] <strong>GUI</strong> Cron/CLI will not create admin message anymore.
* [UPDATE] <strong>Media</strong> Fixed a PHP notice that appeared when pulling optimized images.
* [UPDATE] Fixed a PHP notice when detecting origin of ajax call. (@iosoft)
* [DEBUG] Debug log can now log referer URL.
* [DEBUG] Changes to options will now be logged.

= 2.4.3 - Aug 27 2018 =
* [NEW] <strong>Media</strong> Ability to inline image lazyload JS library. (@Music47ell)
* [IMPROVEMENT] <strong>Media</strong> Deleting images will now clear related optimization file & info too.
* [IMPROVEMENT] <strong>Media</strong> Non-image postfix data will now be bypassed before sending image optimization request.
* [BUGFIX] <strong>CDN</strong> CDN URL will no longer be replaced during admin ajax call. (@pankaj)
* [BUGFIX] <strong>CLI</strong> WPCLI can now save options without incorrectly clearing textarea items. (@Shon)
* [GUI] Moved Settings above Manage on the main menu.

= 2.4.2 - Aug 21 2018 =
* [IMPROVEMENT] <strong>Media</strong> Sped up Image Optimization process by replacing IAPI server pull communication.
* [IMPROVEMENT] <strong>Media</strong> Ability to delete optimized WebP/original image by item in Media Library. (@redgoodapple)
* [IMPROVEMENT] <strong>CSS Optimize</strong> Generate new optimized CSS name based on purge timestamp. Allows CSS cache to be cleared for visitors. (@bradbrownmagic)
* [IMPROVEMENT] <strong>API</strong> added litespeed_img_optm_options_per_image. (@gintsg)
* [UPDATE] Stopped showing "No Image Found" message when all images have finished optimization. (@knutsp)
* [UPDATE] Improved a PHP warning when saving settings. (@sergialarconrecio)
* [UPDATE] Changed backend adminbar icon default behavior from Purge All to Purge LSCache.
* [UPDATE] Clearing CCSS cache will clear unfinished queue too.
* [UPDATE] Added "$" exact match when adding URL by frontend adminbar dropdown menu, to avoid affecting any sub-URLs.
* [UPDATE] Fixed IAPI error message showing array bug. (@thiomas)
* [UPDATE] Debug Disable All will do a Purge All.
* [UPDATE] <strong>Critical CSS server IP changed to 142.93.3.57</strong>.
* [GUI] Showed plugin update link for IAPI version message.
* [GUI] Bypassed null IAPI response message.
* [GUI] Grouped related settings with indent.
* [IAPI] Added 503 handler for IAPI response.
* [IAPI] IAPI v2.4.2.
* [IAPI] <strong>Center Server IP Changed from 34.198.229.186 to 142.93.112.87</strong>.

= 2.4.1 - Jul 19 2018 =
* [NEW FEATURE] <strong>Media</strong> Auto Level Up. Auto refill credit.
* [NEW FEATURE] <strong>Media</strong> Auto delete original backups after pulled. (@borisov87 @JMCA2)
* [NEW FEATURE] <strong>Media</strong> Auto request image optimization. (@ericsondr)
* [IMPROVEMENT] <strong>Media</strong> Fetch 404 error will notify client as other errors.
* [IMPROVEMENT] <strong>Media</strong> Support WebP for PageSpeed Insights. (@LucasRolff)
* [BUGFIX] <strong>CLI</strong> Fixed the issue where CLI import/export caused certain textarea settings to be lost. (#767519)
* [BUGFIX] <strong>CSS Optimize</strong> Fixed the issue that duplicated optimized CSS and caused rapid expansion of CSS cache folder.
* [GUI] <strong>Media</strong> Refactored operation workflow and interface.
* [UPDATE] <strong>Media</strong> Set timeout seconds to avoid pulling timeout. (@Jose)
* [UPDATE] <strong>CDN</strong>Fixed the notice when no path is in URL. (@sabitkamera)
* [UPDATE] <strong>Media</strong> Auto correct credits when pulling.
* [UPDATE] <strong>GUI</strong> Removed redundant double quote in gui.cls. (@DaveyJake)
* [IAPI] IAPI v2.4.1.
* [IAPI] Allow new error status notification and success message from IAPI.

= 2.4 - Jul 2 2018 =
* [NEW FEATURE] <strong>Media</strong> Added lossless optimization.
* [NEW FEATURE] <strong>Media</strong> Added Request Orignal Images ON/OFF.
* [NEW FEATURE] <strong>Media</strong> Added Request WebP ON/OFF. (@JMCA2)
* [IMPROVEMENT] <strong>Media</strong> Improved optimization tools to archive maximum compression and score.
* [IMPROVEMENT] <strong>Media</strong> Improved speed of image pull.
* [IMPROVEMENT] <strong>Media</strong> Automatically recover credit after pulled.
* [REFACTOR] <strong>Config</strong> Separated configure const class.
* [BUGFIX] <strong>Report</strong> Report can be sent successfully with emoji now. (@music47ell)
* [IAPI] New Europe Image Optimization server (EU3/EU4).
* [IAPI] New America Image Optimization server (US3/US4/US5/US6).
* [IAPI] New Asian Image Optimization server (AS3).
* [IAPI] Refactored optimization process.
* [IAPI] Increased credit limit.
* [IAPI] Removed request interval limit.
* [IAPI] IAPI v2.4.
* <strong>We strongly recommended that you re-optimize your image library to get a better compression result</strong>.

= 2.3.1 - Jun 18 2018 =
* [IMPROVEMENT] New setting to disable Generate Critical CSS. (@cybmeta)
* [IMPROVEMENT] Added filter to can_cdn/can_optm check. (@Jacob)
* [UPDATE] *Critical CSS* Added 404 css. Limit cron interval.
* [UPDATE] AJAX will not bypass CDN anymore by default. (@Jacob)
* [GUI] Show Disable All Features warning if it is on in Debug tab.

= 2.3 - Jun 13 2018 =
* [NEW FEATURE] Automatically generate critical CSS. (@joeee @ivan_ivanov @3dseo)
* [BUGFIX] "Mark this page as..." from dropdown menu will not reset settings anymore. (@cbratschi)

= 2.2.7 - Jun 4 2018 =
* [IMPROVEMENT] Improved redirection for manual image pull to avoid too many redirections warning.
* [IAPI] Increased credit limit.
* [BUGFIX] Fixed 503 error when enabling log filters in Debug tab. (#525206)
* [UPDATE] Improve compatibility when using sitemap url on servers with allow_url_open off.
* [UPDATE] Removed Crawler HTTP2 option due to causing no-cache blacklist issue for certain environments.
* [UPDATE] Privacy policy can be now translated. (@Josemi)
* [UPDATE] IAPI Increased default img request max to 3000.

= 2.2.6 - May 24 2018 =
* [NEW FEATURE] Original image backups can be removed now. (@borisov87 @JMCA2)
* [BUGFIX] Role Excludes in Tuning tab can save now. (@pako69)
* [UPDATE] Added privacy policy support.

= 2.2.5 - May 14 2018 =
* [IAPI] <strong>Image Optimization</strong> New Asian Image Optimization server (AS2).
* [INTEGRATION] Removed wpForo 3rd party file. (@massimod)

= 2.2.4 - May 7 2018 =
* [IMPROVEMENT] Improved compatibility with themes using the same js_min library. (#129093 @Darren)
* [BUGFIX] Fixed a bug when checking image path for dynamic files. (@miladk)
* [INTEGRATION] Compatibility with Universal Star Rating. (@miladk)

= 2.2.3 - Apr 27 2018 =
* [NEW FEATURE] WebP For Extra srcset setting in Media tab. (@vengen)
* [REFACTOR] Removed redundant LS consts.
* [REFACTOR] Refactored adv_cache generation flow.
* [BUGFIX] Fixed issue where inline JS minify exception caused a blank page. (@oomskaap @kenb1978)
* [UPDATE] Changed HTTP/2 Crawl default value to OFF.
* [UPDATE] Added img.data-src to default WebP replacement value for WooCommerce WebP support.
* [UPDATE] Detached crawler from LSCache LITESPEED_ON status.
* [API] Improved ESI API to honor the cache control in ESI wrapper.
* [API] Added LITESPEED_PURGE_SILENT const to bypass the notification when purging
* [INTEGRATION] Fixed issue with nonce expiration when using ESI API. (#923505 @Dan)
* [INTEGRATION] Improved compatibility with Ninja Forms by bypassing non-javascript JS from inline JS minify.
* [INTEGRATION] Added a hook for plugins that change the CSS/JS path e.g. Hide My WordPress.

= 2.2.2 - Apr 16 2018 =
* [NEW FEATURE] WebP Attribute To Replace setting in Media tab. (@vengen)
* [IMPROVEMENT] Generate adv_cache file automatically when it is lost.
* [IMPROVEMENT] Improved compatibility with ajax login. (@veganostomy)
* [UPDATE] Added object cache lib check in case user downgrades LSCWP to non-object-cache versions.
* [UPDATE] Avoided infinite loop when users enter invalid hook values in Purge All Hooks settings.
* [UPDATE] Updated log format in media&cdn class.
* [UPDATE] Added more items to Report.

= 2.2.1 - Apr 10 2018 =
* [NEW FEATURE] Included Directories setting in CDN tab. (@Dave)
* [NEW FEATURE] Purge All Hooks setting in Advanced tab.
* [UPDATE] Added background-image WebP replacement support. (@vengen)
* [UPDATE] Show recommended values for textarea items in settings.
* [UPDATE] Moved CSS/JS optimizer log to Advanced level.
* [INTEGRATION] Added WebP support for Avada Fusion Sliders. (@vengen)

= 2.2.0.2 - Apr 3 2018 =
* [HOTFIX] <strong>Object Cache</strong> Fixed the PHP warning caused by previous improvement to Object Cache.

= 2.2.0.1 - Apr 3 2018 =
* [HOTFIX] Object parameter will no longer cause warnings to be logged for Purge and Cache classes. (@kelltech @khrifat)
* [UPDATE] Removed duplicated del_file func from Object Cache class.
* [BUGFIX] `CLI` no longer shows 400 error upon successful result.

= 2.2 - Apr 2 2018 =
* [NEW FEATURE] <strong>Debug</strong> Disable All Features setting in Debug tab. (@monarobase)
* [NEW FEATURE] <strong>Cache</strong> Force Cacheable URIs setting in Excludes tab.
* [NEW FEATURE] <strong>Purge</strong> Purge all LSCache and other caches in one link.
* [REFACTOR] <strong>Purge</strong> Refactored Purge class.
* [BUGFIX] Query strings in DoNotCacheURI setting now works.
* [BUGFIX] <strong>Cache</strong> Mobile cache compatibility with WebP vary. (@Shivam #987121)
* [UPDATE] <strong>Purge</strong> Moved purge_all to Purge class from core class.
* [API] Set cacheable/Set force cacheable. (@Jacob)

= 2.1.2 - Mar 28 2018 =
* [NEW FEATURE] <strong>Image Optimization</strong> Clean Up Unfinished Data feature.
* [IAPI] IAPI v2.1.2.
* [IMPROVEMENT] <strong>CSS/JS Minify</strong> Reduced loading time significantly by improving CSS/JS minify loading process. (@kokers)
* [IMPROVEMENT] <strong>CSS/JS Minify</strong> Cache empty JS Minify content. (@kokers)
* [IMPROVEMENT] <strong>Cache</strong> Cache 301 redirect when scheme/host are same.
* [BUGFIX] <strong>Media</strong> Lazy load now can support WebP. (@relle)
* [UPDATE] <strong>CSS/JS Optimize</strong> Serve static files for CSS async & lazy load JS library.
* [UPDATE] <strong>Report</strong> Appended Basic/Advanced View setting to Report.
* [UPDATE] <strong>CSS/JS Minify</strong> Removed zero-width space from CSS/JS content.
* [GUI] Added Purge CSS/JS Cache link in Admin.

= 2.1.1.1 - Mar 21 2018 =
* [BUGFIX] Fixed issue where activation failed to add rules to .htaccess.
* [BUGFIX] Fixed issue where 304 header was blank on feed page refresh.

= 2.1.1 - Mar 20 2018 =
* [NEW FEATURE] <strong>Browser Cache</strong> Unlocked for non-LiteSpeed users.
* [IMPROVEMENT] <strong>Image Optimization</strong> Fixed issue where images with bad postmeta value continued to show in not-yet-requested queue.

= 2.1 - Mar 15 2018 =
* [NEW FEATURE] <strong>Image Optimization</strong> Unlocked for non-LiteSpeed users.
* [NEW FEATURE] <strong>Object Cache</strong> Unlocked for non-LiteSpeed users.
* [NEW FEATURE] <strong>Crawler</strong> Unlocked for non-LiteSpeed users.
* [NEW FEATURE] <strong>Database Cleaner and Optimizer</strong> Unlocked for non-LiteSpeed users.
* [NEW FEATURE] <strong>Lazy Load Images</strong> Unlocked for non-LiteSpeed users.
* [NEW FEATURE] <strong>CSS/JS/HTML Minify/Combine Optimize</strong> Unlocked for non-LiteSpeed users.
* [IAPI] IAPI v2.0.
* [IAPI] Increased max rows prefetch when client has additional credit.
* [IMPROVEMENT] <strong>CDN</strong> Multiple domains may now be used.
* [IMPROVEMENT] <strong>Report</strong> Added WP environment constants for better debugging.
* [REFACTOR] Separated Cloudflare CDN class.
* [BUGFIX] <strong>Image Optimization</strong> Fixed issue where certain MySQL version failed to create img_optm table. (@philippwidmer)
* [BUGFIX] <strong>Image Optimization</strong> Fixed issue where callback validation failed when pulling and sending request simultaneously.
* [GUI] Added Slack community banner.
* [INTEGRATION] CDN compatibility with WPML multiple domains. (@egemensarica)

= 2.0 - Mar 7 2018 =
* [NEW FEATURE] <strong>Image Optimization</strong> Added level up guidance.
* [REFACTOR] <strong>Image Optimization</strong> Refactored Image Optimization class.
* [IAPI] <strong>Image Optimization</strong> New European Image Optimization server (EU2).
* [IMPROVEMENT] <strong>Image Optimization</strong> Manual pull action continues pulling until complete.
* [IMPROVEMENT] <strong>CDN</strong> Multiple CDNs can now be randomized for a single resource.
* [IMPROVEMENT] <strong>Image Optimization</strong> Improved compatibility of long src images.
* [IMPROVEMENT] <strong>Image Optimization</strong> Reduced runtime load.
* [IMPROVEMENT] <strong>Image Optimization</strong> Avoid potential loss/reset of notified images status when pulling.
* [IMPROVEMENT] <strong>Image Optimization</strong> Avoid duplicated optimization for multiple records in Media that have the same image source.
* [IMPROVEMENT] <strong>Image Optimization</strong> Fixed issue where phantom images continued to show in not-yet-requested queue.
* [BUGFIX] <strong>Core</strong> Improved compatibility when upgrading outside of WP Admin. (@jikatal @TylorB)
* [BUGFIX] <strong>Crawler</strong> Improved HTTP/2 compatibility to avoid erroneous blacklisting.
* [BUGFIX] <strong>Crawler</strong> Changing Delay setting will use server variable for min value validation if set.
* [UPDATE] <strong>Crawler</strong> Added HTTP/2 protocol switch in the Crawler settings.
* [UPDATE] Removed unnecessary translation strings.
* [GUI] Display translated role group name string instead of English values. (@Richard Hordern)
* [GUI] Added Join LiteSpeed Slack link.
* [GUI] <strong>Import / Export</strong> Cosmetic changes to Import Settings file field.
* [INTEGRATION] Improved compatibility with WPML Media for Image Optimization. (@szmigieldesign)

= 1.9.1.1 - February 20 2018 =
* [Hotfix] Removed empty crawler when no role simulation is set.

= 1.9.1 - February 20 2018 =
* [NEW FEATURE] Role Simulation crawler.
* [NEW FEATURE] WebP multiple crawler.
* [NEW FEATURE] HTTP/2 support for crawler.
* [BUGFIX] Fixed a js bug with the auto complete mobile user agents field when cache mobile is turned on.
* [BUGFIX] Fixed a constant undefined warning after activation.
* [GUI] Sitemap generation settings are no longer hidden when using a custom sitemap.

= 1.9 - February 12 2018 =
* [NEW FEATURE] Inline CSS/JS Minify.
* [IMPROVEMENT] Removed Composer vendor to thin the plugin folder.
* [UPDATE] Tweaked H2 to H1 in Admin headings for accessibility. (@steverep)
* [GUI] Added Mobile User Agents to basic view.
* [GUI] Moved Object Cache & Browser Cache from Cache tab to Advanced tab.
* [GUI] Moved LSCache Purge All from Adminbar to dropdown menu.

= 1.8.3 - February 2 2018 =
* [NEW FEATURE] Crawler server variable limitation support.
* [IMPROVEMENT] Added Store Transients option to fix transients missing issue when Cache Wp-Admin setting is OFF.
* [IMPROVEMENT] Tweaked ARIA support. (@steverep)
* [IMPROVEMENT] Used strpos instead of strncmp for performance. (@Zach E)
* [BUGFIX] Transient cache can now be removed when the Cache Wp-Admin setting is ON in Object Cache.
* [BUGFIX] Network sites can now save Advanced settings.
* [BUGFIX] Media list now shows in network sites.
* [BUGFIX] Show Crawler Status button is working again.
* [UPDATE] Fixed a couple of potential PHP notices in the Network cache tab and when no vary group is set.
* [GUI] Added Learn More link to all setting pages.

= 1.8.2 - January 29 2018 =
* [NEW FEATURE] Instant Click in the Advanced tab.
* [NEW FEATURE] Import/Export settings.
* [NEW FEATURE] Opcode Cache support.
* [NEW FEATURE] Basic/Advanced setting view.
* [IMPROVEMENT] Added ARIA support in widget settings.
* [BUGFIX] Multiple WordPress instances with same Object Cache address will no longer see shared data.
* [BUGFIX] WebP Replacement may now be set at the Network level.
* [BUGFIX] Object Cache file can now be removed at the Network level uninstall.

= 1.8.1 - January 22 2018 =
* [NEW FEATURE] Object Cache now supports Redis.
* [IMPROVEMENT] Memcached Object Cache now supports authorization.
* [IMPROVEMENT] A 500 error will no longer be encountered when turning on Object Cache without the proper PHP extension installed.
* [BUGFIX] Object Cache settings can now be saved at the Network level.
* [BUGFIX] Mu-plugin now supports Network setting.
* [BUGFIX] Fixed admin bar showing inaccurate Edit Page link.
* [UPDATE] Removed warning information when no Memcached server is available.

= 1.8 - January 17 2018 =
* [NEW FEATURE] Object Cache.
* [REFACTOR] Refactored Log class.
* [REFACTOR] Refactored LSCWP basic const initialization.
* [BUGFIX] Fixed Cloudflare domain search breaking when saving more than 50 domains under a single account.
* [UPDATE] Log filter settings are now their own item in the wp-option table.

= 1.7.2 - January 5 2018 =
* [NEW FEATURE] Cloudflare API support.
* [IMPROVEMENT] IAPI key can now be reset to avoid issues when domain is changed.
* [BUGFIX] Fixed JS optimizer breaking certain plugins JS.
* [UPDATE] Added cdn settings to environment report.
* [GUI] Added more shortcuts to backend adminbar.
* [INTEGRATION] WooCommerce visitors are now served from public cache when cart is empty.

= 1.7.1.1 - December 29 2017 =
* [BUGFIX] Fixed an extra trailing underscore issue when saving multiple lines with DNS Prefetch.
* [UPDATE] Cleaned up unused dependency vendor files.

= 1.7.1 - December 28 2017 =
* [NEW FEATURE] Added DNS Prefetch setting on the Optimize page.
* [NEW FEATURE] Added Combined File Max Size setting on the Tuning page.
* [IMPROVEMENT] Improved JS/CSS minify to achieve higher page scores.
* [IMPROVEMENT] Optimized JS/CSS files will not be served from private cache for OLS or with ESI off.
* [UPDATE] Fixed a potential warning for new installations on the Settings page.
* [UPDATE] Fixed an issue with guest users occasionally receiving PHP warnings.
* [BUGFIX] Fixed a bug with the Improve HTTPS Compatibility setting failing to save.
* Thanks to all of our users for your encouragement and support! Happy New Year!
* PS: Lookout 2018, we're back!

= 1.7 - December 22 2017 =
* [NEW FEATURE] Drop Query Strings setting in the Cache tab.
* [NEW FEATURE] Multiple CDN Mapping in the CDN tab.
* [IMPROVEMENT] Improve HTTP/HTTPS Compatibility setting in the Advanced tab.
* [IMPROVEMENT] Keep JS/CSS original position in HTML when excluded in setting.
* [IAPI] Reset client level credit after Image Optimization data is destroyed.
* [REFACTOR] Refactored build_input/textarea functions in admin_display class.
* [REFACTOR] Refactored CDN class.
* [GUI] Added a notice to Image Optimization and Crawler to warn when cache is disabled.
* [GUI] Improved image optimization indicator styles in Media Library List.

= 1.6.7 - December 15 2017 =
* [IAPI] Added ability to scan for new image thumbnail sizes and auto-resend image optimization requests.
* [IAPI] Added ability to destroy all optimization data.
* [IAPI] Updated IAPI to v1.6.7.
* [INTEGRATION] Fixed certain 3rd party plugins calling REST without user nonce causing logged in users to be served as guest.

= 1.6.6.1 - December 8 2017 =
* [IAPI] Limit first-time submission to one image group for test-run purposes.
* [BUGFIX] Fixed vary group generation issue associated with custom user role plugins.
* [BUGFIX] Fixed WooCommerce issue where logged-in users were erroneously purged when ESI is off.
* [BUGFIX] Fixed WooCommerce cache miss issue when ESI is off.

= 1.6.6 - December 6 2017 =
* [NEW FEATURE] Preserve EXIF in Media setting.
* [NEW FEATURE] Clear log button in Debug Log Viewer.
* [IAPI] Fixed notified images resetting to previous status when pulling.
* [IAPI] Fixed HTTPS compatibility for image optimization initialization.
* [IAPI] An error message is now displayed when image optimization request submission is bypassed due to a lack of credit.
* [IAPI] IAPI v1.6.6.
* [IMPROVEMENT] Support JS data-no-optimize attribute to bypass optimization.
* [GUI] Added image group wiki link.
* [INTEGRATION] Improved compatibility with Login With Ajax.
* [INTEGRATION] Added function_exists check for WooCommerce to avoid 500 errors.

= 1.6.5.1 - December 1 2017 =
* [HOTFIX] Fixed warning message on Edit .htaccess page.

= 1.6.5 - November 30 2017 =
* [IAPI] Manually pull image optimization action button.
* [IAPI] Automatic credit system for image optimization to bypass unfinished image optimization error.
* [IAPI] Notify failed images from LiteSpeed's Image Server.
* [IAPI] Reset/Clear failed images feature.
* [IAPI] Redesigned report page.
* [REFACTOR] Moved pull_img logic from admin_api to media.
* [BUGFIX] Fixed a compatibility issue for clients who have allow_url_open setting off.
* [BUGFIX] Fixed logged in users sometimes being served from guest cache.
* [UPDATE] Environment report is no longer saved to a file.
* [UPDATE] Removed crawler reset notification.
* [GUI] Added more details on image optimization.
* [GUI] Removed info page from admin menu.
* [GUI] Moved environment report from network level to single site level.
* [GUI] Crawler time added in a user friendly format.
* [INTEGRATION] Improved compatibility with FacetWP json call.

= 1.6.4 - November 22 2017 =
* [NEW FEATURE] Send env reports privately with a new built-in report number referral system.
* [IAPI] Increased request timeout to fix a cUrl 28 timeout issue.
* [BUGFIX] Fixed a TTL max value validation bug.
* [INTEGRATION] Improved Contact Form 7 REST call compatibility for logged in users.
* Thanks for all your ratings. That encouraged us to be more diligent. Happy Thanksgiving.

= 1.6.3 - November 17 2017 =
* [NEW FEATURE] Only async Google Fonts setting.
* [NEW FEATURE] Only create WebP images when optimizing setting.
* [NEW FEATURE] Batch switch images to original/optimized versions in Image Optimization.
* [NEW FEATURE] Browser Cache TTL setting.
* [NEW FEATURE] Cache WooCommerce Cart setting.
* [IMPROVEMENT] Moved optimized JS/CSS snippet in header html to after meta charset.
* [IMPROVEMENT] Added a constant for better JS/CSS optimization compatibility for different dir WordPress installation.
* [IAPI] Take over failed callback check instead of bypassing it.
* [IAPI] Image optimization requests are now limited to 500 images per request.
* [BUGFIX] Fixed a parsing failure bug not using attributes in html elements with dash.
* [BUGFIX] Fixed a bug causing non-script code to move to the top of a page when not using combination.
* [UPDATE] Added detailed logs for external link detection.
* [UPDATE] Added new lines in footer comment to avoid Firefox crash when enabled HTML minify.
* [API] `Purge private` / `Purge private all` / `Add private tag` functions.
* [GUI] Redesigned image optimization operation links in Media Lib list.
* [GUI] Tweaked wp-admin form save button position.
* [GUI] Added "learn more" link for image optimization.

= 1.6.2.1 - November 6 2017 =
* [INTEGRATION] Improved compatibility with old WooCommerce versions to avoid unknown 500 errors.
* [BUGFIX] Fixed WebP images sometimes being used in non-supported browsers.
* [BUGFIX] Kept query strings for HTTP/2 push to avoid re-fetching pushed sources.
* [BUGFIX] Excluded JS/CSS from HTTP/2 push when using CDN.
* [GUI] Fixed a typo in Media list.
* [GUI] Made more image optimization strings translatable.
* [GUI] Updated Tuning description to include API documentation.

= 1.6.2 - November 3 2017 =
* [NEW FEATURE] Do Not Cache Roles.
* [NEW FEATURE] Use WebP Images for supported browsers.
* [NEW FEATURE] Disable Optimization Poll ON/OFF Switch in Media tab.
* [NEW FEATURE] Revert image optimization per image in Media list.
* [NEW FEATURE] Disable/Enable image WebP per image in Media list.
* [IAPI] Limit optimized images fetching cron to a single process.
* [IAPI] Updated IAPI to v1.6.2.
* [IAPI] Fixed repeating image request issue by adding a failure status to local images.
* [REFACTOR] Refactored login vary logic.

= 1.6.1 - October 29 2017 =
* [IAPI] Updated LiteSpeed Image Optimization Server API to v1.6.1.

= 1.6 - October 27 2017 =
* [NEW FEATURE] Image Optimization.
* [NEW FEATURE] Role Excludes for Optimization.
* [NEW FEATURE] Combined CSS/JS Priority.
* [IMPROVEMENT] Bypass CDN for login/register page.
* [UPDATE] Expanded ExpiresByType rules to include new font types. ( Thanks to JMCA2 )
* [UPDATE] Removed duplicated type param in admin action link.
* [BUGFIX] Fixed CDN wrongly replacing img base64 and "fake" src in JS.
* [BUGFIX] Fixed image lazy load replacing base64 src.
* [BUGFIX] Fixed a typo in Optimize class exception.
* [GUI] New Tuning tab in admin settings panel.
* [REFACTOR] Simplified router by reducing actions and adding types.
* [REFACTOR] Renamed `run()` to `finalize()` in buffer process.

= 1.5 - October 17 2017 =
* [NEW FEATURE] Exclude JQuery (to fix inline JS error when using JS Combine).
* [NEW FEATURE] Load JQuery Remotely.
* [NEW FEATURE] JS Deferred Excludes.
* [NEW FEATURE] Lazy Load Images Excludes.
* [NEW FEATURE] Lazy Load Image Placeholder.
* [IMPROVEMENT] Improved Lazy Load size attribute for w3c validator.
* [UPDATE] Added basic caching info and LSCWP version to HTML comment.
* [UPDATE] Added debug log to HTML detection.
* [BUGFIX] Fixed potential font CORS issue when using CDN.
* [GUI] Added API docs to setting description.
* [REFACTOR] Relocated all classes under includes with backwards compatibility.
* [REFACTOR] Relocated admin templates.

= 1.4 - October 11 2017 =
* [NEW FEATURE] Lazy load images/iframes.
* [NEW FEATURE] Clean CSS/JS optimizer data functionality in DB Optimizer panel.
* [NEW FEATURE] Exclude certain URIs from optimizer.
* [IMPROVEMENT] Improved optimizer HTML check compatibility to avoid conflicts with ESI functions.
* [IMPROVEMENT] Added support for using ^ when matching the start of a path in matching settings.
* [IMPROVEMENT] Added wildcard support in CDN original URL.
* [IMPROVEMENT] Moved optimizer table initialization to admin setting panel with failure warning.
* [UPDATE] Added a one-time welcome banner.
* [UPDATE] Partly relocated class: 'api'.
* [API] Added API wrapper for removing wrapped HTML output.
* [INTEGRATION] Fixed WooCommerce conflict with optimizer.
* [INTEGRATION] Private cache support for WooCommerce v3.2.0+.
* [GUI] Added No Optimization menu to frontend.

= 1.3.1.1 - October 6 2017 =
* [BUGFIX] Improved optimizer table creating process in certain database charset to avoid css/js minify/combination failure.

= 1.3.1 - October 5 2017 =
* [NEW FEATURE] Remove WP Emoji Option.
* [IMPROVEMENT] Separated optimizer data from wp_options to improve compatibility with backup plugins.
* [IMPROVEMENT] Enhanced crawler cron hook to prevent de-scheduling in some cases.
* [IMPROVEMENT] Enhanced Remove Query Strings to also remove Emoji query strings.
* [IMPROVEMENT] Enhanced HTML detection when extra spaces are present at the beginning.
* [UPDATE] Added private cache support for OLS.
* [BUGFIX] Self-redirects are no longer cached.
* [BUGFIX] Fixed css async lib warning when loading in HTTP/2 push.

= 1.3 - October 1 2017 =
* [NEW FEATURE] Added Browser Cache support.
* [NEW FEATURE] Added Remove Query Strings support.
* [NEW FEATURE] Added Remove Google Fonts support.
* [NEW FEATURE] Added Load CSS Asynchronously support.
* [NEW FEATURE] Added Load JS Deferred support.
* [NEW FEATURE] Added Critical CSS Rules support.
* [NEW FEATURE] Added Private Cached URIs support.
* [NEW FEATURE] Added Do Not Cache Query Strings support.
* [NEW FEATURE] Added frontend adminbar shortcuts ( Purge this page/Do Not Cache/Private cache ).
* [IMPROVEMENT] Do Not Cache URIs now supports full URLs.
* [IMPROVEMENT] Improved performance of Do Not Cache settings.
* [IMPROVEMENT] Encrypted vary cookie.
* [IMPROVEMENT] Enhanced HTML optimizer.
* [IMPROVEMENT] Limited combined file size to avoid heavy memory usage.
* [IMPROVEMENT] CDN supports custom upload folder for media files.
* [API] Added purge single post API.
* [API] Added version compare API.
* [API] Enhanced ESI API for third party plugins.
* [INTEGRATION] Compatibility with NextGEN Gallery v2.2.14.
* [INTEGRATION] Compatibility with Caldera Forms v1.5.6.2+.
* [BUGFIX] Fixed CDN&Minify compatibility with css url links.
* [BUGFIX] Fixed .htaccess being regenerated despite there being no changes.
* [BUGFIX] Fixed CDN path bug for subfolder WP instance.
* [BUGFIX] Fixed crawler path bug for subfolder WP instance with different site url and home url.
* [BUGFIX] Fixed a potential Optimizer generating redundant duplicated JS in HTML bug.
* [GUI] Added a more easily accessed submit button in admin settings.
* [GUI] Admin settings page cosmetic changes.
* [GUI] Reorganized GUI css/img folder structure.
* [REFACTOR] Refactored configuration init.
* [REFACTOR] Refactored admin setting save.
* [REFACTOR] Refactored .htaccess operator and rewrite rule generation.

= 1.2.3.1 - September 20 2017 =
* [UPDATE] Improved PHP5.3 compatibility.

= 1.2.3 - September 20 2017 =
* [NEW FEATURE] Added CDN support.
* [IMPROVEMENT] Improved compatibility when upgrading by fixing a possible fatal error.
* [IMPROVEMENT] Added support for custom wp-content paths.
* [BUGFIX] Fixed non-primary network blogs not being able to minify.
* [BUGFIX] Fixed HTML Minify preventing Facebook from being able to parse og tags.
* [BUGFIX] Preview page is no longer cacheable.
* [BUGFIX] Corrected log and crawler timezone to match set WP timezone.
* [GUI] Revamp of plugin GUI.

= 1.2.2 - September 15 2017 =
* [NEW FEATURE] Added CSS/JS minification.
* [NEW FEATURE] Added CSS/JS combining.
* [NEW FEATURE] Added CSS/JS HTTP/2 server push.
* [NEW FEATURE] Added HTML minification.
* [NEW FEATURE] Added CSS/JS cache purge button in management.
* [UPDATE] Improved debug log formatting.
* [UPDATE] Fixed some description typos.

= 1.2.1 - September 7 2017 =
* [NEW FEATURE] Added Database Optimizer.
* [NEW FEATURE] Added Tab switch shortcut.
* [IMPROVEMENT] Added cache disabled check for management pages.
* [IMPROVEMENT] Renamed .htaccess backup for security.
* [BUGFIX] Fixed woocommerce default ESI setting bug.
* [REFACTOR] Show ESI page for OLS with notice.
* [REFACTOR] Management Purge GUI updated.

= 1.2.0.1 - September 1 2017 =
* [BUGFIX] Fixed a naming bug for network constant ON2.

= 1.2.0 - September 1 2017 =
* [NEW FEATURE] Added ESI support.
* [NEW FEATURE] Added a private cache TTL setting.
* [NEW FEATURE] Debug level can now be set to either 'Basic' or 'Advanced'.
* [REFACTOR] Renamed const 'NOTSET' to 'ON2' in class config.

= 1.1.6 - August 23 2017 =
* [NEW FEATURE] Added option to privately cache logged-in users.
* [NEW FEATURE] Added option to privately cache commenters.
* [NEW FEATURE] Added option to cache requests made through WordPress REST API.
* [BUGFIX] Fixed network 3rd-party full-page cache detection bug.
* [GUI] New Cache and Purge menus in Settings.

= 1.1.5.1 - August 16 2017 =
* [IMPROVEMENT] Improved compatibility of frontend&backend .htaccess path detection when site url is different than installation path.
* [UPDATE] Removed unused format string from header tags.
* [BUGFIX] 'showheader' Admin Query String now works.
* [REFACTOR] Cache tags will no longer output if not needed.

= 1.1.5 - August 10 2017 =
* [NEW FEATURE] Scheduled Purge URLs feature.
* [NEW FEATURE] Added buffer callback to improve compatibility with some plugins that force buffer cleaning.
* [NEW FEATURE] Hide purge_all admin bar quick link if cache is disabled.
* [NEW FEATURE] Required htaccess rules are now displayed when .htaccess is not writable.
* [NEW FEATURE] Debug log features: filter log support; heartbeat control; log file size limit; log viewer.
* [IMPROVEMENT] Separate crawler access log.
* [IMPROVEMENT] Lazy PURGE requests made after output are now queued and working.
* [IMPROVEMENT] Improved readme.txt with keywords relating to our compatible plugins list.
* [UPDATE] 'ExpiresDefault' conflict msg is now closeable and only appears in the .htaccess edit screen.
* [UPDATE] Improved debug log formatting.
* [INTEGRATION] Compatibility with MainWP plugin.
* [BUGFIX] Fixed Woocommerce order not purging product stock quantity.
* [BUGFIX] Fixed Woocommerce scheduled sale price not updating issue.
* [REFACTOR] Combined cache_enable functions into a single function.

= 1.1.4 - August 1 2017 =
* [IMPROVEMENT] Unexpected rewrite rules will now show an error message.
* [IMPROVEMENT] Added Cache Tag Prefix setting info in the Env Report and Info page.
* [IMPROVEMENT] LSCWP setting link is now displayed in the plugin list.
* [IMPROVEMENT] Improved performance when setting cache control.
* [UPDATE] Added backward compatibility for v1.1.2.2 API calls. (used by 3rd-party plugins)
* [BUGFIX] Fixed WPCLI purge tag/category never succeeding.

= 1.1.3 - July 31 2017 =
* [NEW FEATURE] New LiteSpeed_Cache_API class and documentation for 3rd party integration.
* [NEW FEATURE] New API function litespeed_purge_single_post($post_id).
* [NEW FEATURE] PHP CLI support for crawler.
* [IMPROVEMENT] Set 'no cache' for same location 301 redirects.
* [IMPROVEMENT] Improved LiteSpeed footer comment compatibility.
* [UPDATE] Removed 'cache tag prefix' setting.
* [BUGFIX] Fixed a bug involving CLI purge all.
* [BUGFIX] Crawler now honors X-LiteSpeed-Cache-Control for the 'no-cache' header.
* [BUGFIX] Cache/rewrite rules are now cleared when the plugin is uninstalled.
* [BUGFIX] Prevent incorrect removal of the advanced-cache.php on deactivation if it was added by another plugin.
* [BUGFIX] Fixed subfolder WP installations being unable to Purge By URL using a full URL path.
* [REFACTOR] Reorganized existing code for an upcoming ESI release.

= 1.1.2.2 - July 13 2017 =
* [BUGFIX] Fixed blank page in Hebrew language post editor by removing unused font-awesome and jquery-ui css libraries.

= 1.1.2.1 - July 5 2017 =
* [UPDATE] Improved compatibility with WooCommerce v3.1.0.

= 1.1.2 - June 20 2017 =
* [BUGFIX] Fixed missing form close tag.
* [UPDATE] Added a wiki link for enabling the crawler.
* [UPDATE] Improved Site IP description.
* [UPDATE] Added an introduction to the crawler on the Information page.
* [REFACTOR] Added more detailed error messages for Site IP and Custom Sitemap settings.

= 1.1.1.1 - June 15 2017 =
* [BUGFIX] Hotfix for insufficient validation of site IP value in crawler settings.

= 1.1.1 - June 15 2017 =
* [NEW] As of LiteSpeed Web Server v.5.1.16, the crawler can now be enabled/disabled at the server level.
* [NEW] Added the ability to provide a custom sitemap for crawling.
* [NEW] Added ability to use site IP address directly in crawler settings.
* [NEW] Crawler performance improved with the use of new custom user agent 'lsrunner'.
* [NEW] "Purge By URLs" now supports full URL paths.
* [NEW] Added thirdparty WP-PostRatings compatibility.
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
