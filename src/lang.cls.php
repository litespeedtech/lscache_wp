<?php
/**
 * The language class.
 *
 * @since      	3.0
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed ;

defined( 'WPINC' ) || exit ;

class Lang extends Base {
	/**
	 * Get image status per status bit
	 *
	 * @since  3.0
	 */
	public static function img_status( $status = null )
	{
		$list = array(
			Img_Optm::STATUS_RAW		=> __( 'Images not requested', 'litespeed-cache' ),
			Img_Optm::STATUS_REQUESTED	=> __( 'Images requested', 'litespeed-cache' ),
			Img_Optm::STATUS_NOTIFIED	=> __( 'Images notified to pull', 'litespeed-cache' ),
			Img_Optm::STATUS_PULLED		=> __( 'Images optimized and pulled', 'litespeed-cache' ),
			Img_Optm::STATUS_FAILED		=> __( 'Images failed to pull', 'litespeed-cache' ),
			Img_Optm::STATUS_ERR_FETCH	=> __( 'Images failed to fetch', 'litespeed-cache' ),
			Img_Optm::STATUS_ERR_404	=> __( 'Images failed to fetch', 'litespeed-cache') . ' (404)',
			Img_Optm::STATUS_ERR_OPTM	=> __( 'Images previously optimized', 'litespeed-cache' ),
			Img_Optm::STATUS_ERR			=> __( 'Images failed with other errors', 'litespeed-cache' ),
			Img_Optm::STATUS_MISS		=> __( 'Image files missing', 'litespeed-cache' ),
			Img_Optm::STATUS_DUPLICATED	=> __( 'Duplicate image files ignored', 'litespeed-cache' ),
			Img_Optm::STATUS_XMETA		=> __( 'Images with wrong meta', 'litespeed-cache' ),
		);

		if ( $status !== null ) {
			return ! empty( $list[ $status ] ) ? $list[ $status ] : 'N/A';
		}

		return $list;
	}

	/**
	 * Try translating a string
	 *
	 * @since  4.7
	 */
	public static function maybe_translate( $raw_string ) {
		$map = array(
			'auto_alias_failed_cdn' => __( 'Unable to automatically add %1$s as a Domain Alias for main %2$s domain, due to potential CDN conflict.', 'litespeed-cache' ) . ' ' . Doc::learn_more( 'https://quic.cloud/docs/cdn/dns/how-to-setup-domain-alias/', false, false, false, true ),

			'auto_alias_failed_uid' =>
				__( 'Unable to automatically add %1$s as a Domain Alias for main %2$s domain.', 'litespeed-cache' ) .
				' ' . __( 'Alias is in use by another QUIC.cloud account.', 'litespeed-cache' ) .
				' ' . Doc::learn_more( 'https://quic.cloud/docs/cdn/dns/how-to-setup-domain-alias/', false, false, false, true ),
		);

		// Maybe has placeholder
		if ( strpos( $raw_string, '::' ) ) {
			$replacements = explode( '::', $raw_string );
			if ( empty( $map[ $replacements[0] ] ) ) {
				return $raw_string;
			}
			$tpl = $map[ $replacements[0] ];
			unset($replacements[0]);
			return vsprintf( $tpl, array_values( $replacements ) );
		}

		// Direct translation only
		if ( empty( $map[ $raw_string ] ) ) return $raw_string;

		return $map[ $raw_string ];
	}

	/**
	 * Get the title of id
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function title( $id )
	{
		$_lang_list = array(
			self::O_SERVER_IP					=> __( 'Server IP', 'litespeed-cache' ),
			self::O_API_KEY						=> __( 'Domain Key', 'litespeed-cache' ),
			self::O_GUEST_UAS					=> __( 'Guest Mode User Agents', 'litespeed-cache' ),
			self::O_GUEST_IPS					=> __( 'Guest Mode IPs', 'litespeed-cache' ),

			self::O_CACHE						=> __( 'Enable Cache', 'litespeed-cache' ),
			self::O_CACHE_BROWSER				=> __( 'Browser Cache', 'litespeed-cache' ),
			self::O_CACHE_TTL_PUB				=> __( 'Default Public Cache TTL', 'litespeed-cache' ),
			self::O_CACHE_TTL_PRIV				=> __( 'Default Private Cache TTL', 'litespeed-cache' ),
			self::O_CACHE_TTL_FRONTPAGE			=> __( 'Default Front Page TTL', 'litespeed-cache' ),
			self::O_CACHE_TTL_FEED				=> __( 'Default Feed TTL', 'litespeed-cache' ),
			self::O_CACHE_TTL_REST				=> __( 'Default REST TTL', 'litespeed-cache' ),
			self::O_CACHE_TTL_STATUS			=> __( 'Default HTTP Status Code Page TTL', 'litespeed-cache' ),
			self::O_CACHE_TTL_BROWSER			=> __( 'Browser Cache TTL', 'litespeed-cache' ),
			self::O_AUTO_UPGRADE				=> __( 'Automatically Upgrade', 'litespeed-cache' ),
			self::O_GUEST						=> __( 'Guest Mode', 'litespeed-cache' ),
			self::O_GUEST_OPTM					=> __( 'Guest Optimization', 'litespeed-cache' ),
			self::O_NEWS						=> __( 'Notifications', 'litespeed-cache' ),
			self::O_CACHE_PRIV					=> __( 'Cache Logged-in Users', 'litespeed-cache' ),
			self::O_CACHE_COMMENTER				=> __( 'Cache Commenters', 'litespeed-cache' ),
			self::O_CACHE_REST					=> __( 'Cache REST API', 'litespeed-cache' ),
			self::O_CACHE_PAGE_LOGIN			=> __( 'Cache Login Page', 'litespeed-cache' ),
			self::O_CACHE_FAVICON				=> __( 'Cache favicon.ico', 'litespeed-cache' ),
			self::O_CACHE_RES					=> __( 'Cache PHP Resources', 'litespeed-cache' ),
			self::O_CACHE_MOBILE				=> __( 'Cache Mobile', 'litespeed-cache' ),
			self::O_CACHE_MOBILE_RULES			=> __( 'List of Mobile User Agents', 'litespeed-cache' ),
			self::O_CACHE_PRIV_URI				=> __( 'Private Cached URIs', 'litespeed-cache' ),
			self::O_CACHE_DROP_QS				=> __( 'Drop Query String', 'litespeed-cache' ),

			self::O_OBJECT						=> __( 'Object Cache', 'litespeed-cache' ),
			self::O_OBJECT_KIND					=> __( 'Method', 'litespeed-cache' ),
			self::O_OBJECT_HOST					=> __( 'Host', 'litespeed-cache' ),
			self::O_OBJECT_PORT					=> __( 'Port', 'litespeed-cache' ),
			self::O_OBJECT_LIFE					=> __( 'Default Object Lifetime', 'litespeed-cache' ),
			self::O_OBJECT_USER					=> __( 'Username', 'litespeed-cache' ),
			self::O_OBJECT_PSWD					=> __( 'Password', 'litespeed-cache' ),
			self::O_OBJECT_DB_ID				=> __( 'Redis Database ID', 'litespeed-cache' ),
			self::O_OBJECT_GLOBAL_GROUPS		=> __( 'Global Groups', 'litespeed-cache' ),
			self::O_OBJECT_NON_PERSISTENT_GROUPS	=> __( 'Do Not Cache Groups', 'litespeed-cache' ),
			self::O_OBJECT_PERSISTENT			=> __( 'Persistent Connection', 'litespeed-cache' ),
			self::O_OBJECT_ADMIN				=> __( 'Cache WP-Admin', 'litespeed-cache' ),
			self::O_OBJECT_TRANSIENTS			=> __( 'Store Transients', 'litespeed-cache' ),

			self::O_PURGE_ON_UPGRADE			=> __( 'Purge All On Upgrade', 'litespeed-cache' ),
			self::O_PURGE_STALE					=> __( 'Serve Stale', 'litespeed-cache' ),
			self::O_PURGE_TIMED_URLS			=> __( 'Scheduled Purge URLs', 'litespeed-cache' ),
			self::O_PURGE_TIMED_URLS_TIME		=> __( 'Scheduled Purge Time', 'litespeed-cache' ),
			self::O_CACHE_FORCE_URI				=> __( 'Force Cache URIs', 'litespeed-cache' ),
			self::O_CACHE_FORCE_PUB_URI			=> __( 'Force Public Cache URIs', 'litespeed-cache' ),
			self::O_CACHE_EXC					=> __( 'Do Not Cache URIs', 'litespeed-cache' ),
			self::O_CACHE_EXC_QS				=> __( 'Do Not Cache Query Strings', 'litespeed-cache' ),
			self::O_CACHE_EXC_CAT				=> __( 'Do Not Cache Categories', 'litespeed-cache' ),
			self::O_CACHE_EXC_TAG				=> __( 'Do Not Cache Tags', 'litespeed-cache' ),
			self::O_CACHE_EXC_ROLES				=> __( 'Do Not Cache Roles', 'litespeed-cache' ),
			self::O_OPTM_CSS_MIN				=> __( 'CSS Minify', 'litespeed-cache' ),
			self::O_OPTM_CSS_COMB				=> __( 'CSS Combine', 'litespeed-cache' ),
			self::O_OPTM_CSS_COMB_EXT_INL		=> __( 'CSS Combine External and Inline', 'litespeed-cache' ),
			self::O_OPTM_UCSS					=> __( 'Generate UCSS', 'litespeed-cache' ),
			self::O_OPTM_UCSS_INLINE			=> __( 'UCSS Inline', 'litespeed-cache' ),
			self::O_OPTM_UCSS_SELECTOR_WHITELIST	=> __( 'UCSS Selector Allowlist', 'litespeed-cache' ),
			self::O_OPTM_UCSS_FILE_EXC_INLINE	=> __( 'UCSS File Excludes and Inline', 'litespeed-cache' ),
			self::O_OPTM_UCSS_EXC				=> __( 'UCSS URI Excludes', 'litespeed-cache' ),
			self::O_OPTM_JS_MIN					=> __( 'JS Minify', 'litespeed-cache' ),
			self::O_OPTM_JS_COMB				=> __( 'JS Combine', 'litespeed-cache' ),
			self::O_OPTM_JS_COMB_EXT_INL		=> __( 'JS Combine External and Inline', 'litespeed-cache' ),
			self::O_OPTM_HTML_MIN				=> __( 'HTML Minify', 'litespeed-cache' ),
			self::O_OPTM_HTML_LAZY				=> __( 'HTML Lazy Load Selectors', 'litespeed-cache' ),
			self::O_OPTM_CSS_ASYNC				=> __( 'Load CSS Asynchronously', 'litespeed-cache' ),
			self::O_OPTM_CCSS_PER_URL			=> __( 'CCSS Per URL', 'litespeed-cache' ),
			self::O_OPTM_CSS_ASYNC_INLINE		=> __( 'Inline CSS Async Lib', 'litespeed-cache' ),
			self::O_OPTM_CSS_FONT_DISPLAY		=> __( 'Font Display Optimization', 'litespeed-cache' ),
			self::O_OPTM_JS_DEFER				=> __( 'Load JS Deferred', 'litespeed-cache' ),
			self::O_OPTM_LOCALIZE				=> __( 'Localize Resources', 'litespeed-cache' ),
			self::O_OPTM_LOCALIZE_DOMAINS		=> __( 'Localization Files', 'litespeed-cache' ),
			self::O_OPTM_DNS_PREFETCH			=> __( 'DNS Prefetch', 'litespeed-cache' ),
			self::O_OPTM_DNS_PREFETCH_CTRL		=> __( 'DNS Prefetch Control', 'litespeed-cache' ),
			self::O_OPTM_CSS_EXC				=> __( 'CSS Excludes', 'litespeed-cache' ),
			self::O_OPTM_JS_EXC					=> __( 'JS Excludes', 'litespeed-cache' ),
			self::O_OPTM_QS_RM					=> __( 'Remove Query Strings', 'litespeed-cache' ),
			self::O_OPTM_GGFONTS_ASYNC			=> __( 'Load Google Fonts Asynchronously', 'litespeed-cache' ),
			self::O_OPTM_GGFONTS_RM				=> __( 'Remove Google Fonts', 'litespeed-cache' ),
			self::O_OPTM_CCSS_CON				=> __( 'Critical CSS Rules', 'litespeed-cache' ),
			self::O_OPTM_CCSS_SEP_POSTTYPE		=> __( 'Separate CCSS Cache Post Types', 'litespeed-cache' ),
			self::O_OPTM_CCSS_SEP_URI			=> __( 'Separate CCSS Cache URIs', 'litespeed-cache' ),
			self::O_OPTM_JS_DEFER_EXC			=> __( 'JS Deferred / Delayed Excludes', 'litespeed-cache' ),
			self::O_OPTM_GM_JS_EXC				=> __( 'Guest Mode JS Excludes', 'litespeed-cache' ),
			self::O_OPTM_EMOJI_RM				=> __( 'Remove WordPress Emoji', 'litespeed-cache' ),
			self::O_OPTM_NOSCRIPT_RM			=> __( 'Remove Noscript Tags', 'litespeed-cache' ),
			self::O_OPTM_EXC					=> __( 'URI Excludes', 'litespeed-cache' ),
			self::O_OPTM_GUEST_ONLY				=> __( 'Optimize for Guests Only', 'litespeed-cache' ),
			self::O_OPTM_EXC_ROLES				=> __( 'Role Excludes', 'litespeed-cache' ),

			self::O_DISCUSS_AVATAR_CACHE		=> __( 'Gravatar Cache', 'litespeed-cache' ),
			self::O_DISCUSS_AVATAR_CRON			=> __( 'Gravatar Cache Cron', 'litespeed-cache' ),
			self::O_DISCUSS_AVATAR_CACHE_TTL	=> __( 'Gravatar Cache TTL', 'litespeed-cache' ),

			self::O_MEDIA_LAZY					=> __( 'Lazy Load Images', 'litespeed-cache' ),
			self::O_MEDIA_LAZY_EXC				=> __( 'Lazy Load Image Excludes', 'litespeed-cache' ),
			self::O_MEDIA_LAZY_CLS_EXC			=> __( 'Lazy Load Image Class Name Excludes', 'litespeed-cache' ),
			self::O_MEDIA_LAZY_PARENT_CLS_EXC	=> __( 'Lazy Load Image Parent Class Name Excludes', 'litespeed-cache' ),
			self::O_MEDIA_IFRAME_LAZY_CLS_EXC	=> __( 'Lazy Load Iframe Class Name Excludes', 'litespeed-cache' ),
			self::O_MEDIA_IFRAME_LAZY_PARENT_CLS_EXC	=> __( 'Lazy Load Iframe Parent Class Name Excludes', 'litespeed-cache' ),
			self::O_MEDIA_LAZY_URI_EXC			=> __( 'Lazy Load URI Excludes', 'litespeed-cache' ),
			self::O_MEDIA_LQIP_EXC				=> __( 'LQIP Excludes', 'litespeed-cache' ),
			self::O_MEDIA_LAZY_PLACEHOLDER		=> __( 'Basic Image Placeholder', 'litespeed-cache' ),
			self::O_MEDIA_PLACEHOLDER_RESP		=> __( 'Responsive Placeholder', 'litespeed-cache' ),
			self::O_MEDIA_PLACEHOLDER_RESP_COLOR	=> __( 'Responsive Placeholder Color', 'litespeed-cache' ),
			self::O_MEDIA_PLACEHOLDER_RESP_SVG	=> __( 'Responsive Placeholder SVG', 'litespeed-cache' ),
			self::O_MEDIA_LQIP					=> __( 'LQIP Cloud Generator', 'litespeed-cache' ),
			self::O_MEDIA_LQIP_QUAL				=> __( 'LQIP Quality', 'litespeed-cache' ),
			self::O_MEDIA_LQIP_MIN_W			=> __( 'LQIP Minimum Dimensions', 'litespeed-cache' ),
			// self::O_MEDIA_LQIP_MIN_H			=> __( 'LQIP Minimum Height', 'litespeed-cache' ),
			self::O_MEDIA_PLACEHOLDER_RESP_ASYNC	=> __( 'Generate LQIP In Background', 'litespeed-cache' ),
			self::O_MEDIA_IFRAME_LAZY			=> __( 'Lazy Load Iframes', 'litespeed-cache' ),
			self::O_MEDIA_ADD_MISSING_SIZES		=> __( 'Add Missing Sizes', 'litespeed-cache' ),
			self::O_MEDIA_VPI					=> __( 'Viewport Images', 'litespeed-cache' ),
			self::O_MEDIA_VPI_CRON 				=> __( 'Viewport Images Cron', 'litespeed-cache' ),

			self::O_IMG_OPTM_AUTO				=> __( 'Auto Request Cron', 'litespeed-cache' ),
			self::O_IMG_OPTM_CRON				=> __( 'Auto Pull Cron', 'litespeed-cache' ),
			self::O_IMG_OPTM_ORI				=> __( 'Optimize Original Images', 'litespeed-cache' ),
			self::O_IMG_OPTM_RM_BKUP			=> __( 'Remove Original Backups', 'litespeed-cache' ),
			self::O_IMG_OPTM_WEBP				=> __( 'Image WebP Replacement', 'litespeed-cache' ),
			self::O_IMG_OPTM_LOSSLESS			=> __( 'Optimize Losslessly', 'litespeed-cache' ),
			self::O_IMG_OPTM_EXIF				=> __( 'Preserve EXIF/XMP data', 'litespeed-cache' ),
			self::O_IMG_OPTM_WEBP_ATTR			=> __( 'WebP Attribute To Replace', 'litespeed-cache' ),
			self::O_IMG_OPTM_WEBP_REPLACE_SRCSET	=> __( 'WebP For Extra srcset', 'litespeed-cache' ),
			self::O_IMG_OPTM_JPG_QUALITY		=> __( 'WordPress Image Quality Control', 'litespeed-cache' ),
			self::O_ESI							=> __( 'Enable ESI', 'litespeed-cache' ),
			self::O_ESI_CACHE_ADMBAR			=> __( 'Cache Admin Bar', 'litespeed-cache' ),
			self::O_ESI_CACHE_COMMFORM			=> __( 'Cache Comment Form', 'litespeed-cache' ),
			self::O_ESI_NONCE					=> __( 'ESI Nonces', 'litespeed-cache' ),
			self::O_CACHE_VARY_GROUP			=> __( 'Vary Group', 'litespeed-cache' ),
			self::O_PURGE_HOOK_ALL				=> __( 'Purge All Hooks', 'litespeed-cache' ),
			self::O_UTIL_NO_HTTPS_VARY			=> __( 'Improve HTTP/HTTPS Compatibility', 'litespeed-cache' ),
			self::O_UTIL_INSTANT_CLICK			=> __( 'Instant Click', 'litespeed-cache' ),
			self::O_CACHE_EXC_COOKIES			=> __( 'Do Not Cache Cookies', 'litespeed-cache' ),
			self::O_CACHE_EXC_USERAGENTS		=> __( 'Do Not Cache User Agents', 'litespeed-cache' ),
			self::O_CACHE_LOGIN_COOKIE			=> __( 'Login Cookie', 'litespeed-cache' ),

			self::O_MISC_HEARTBEAT_FRONT		=> __( 'Frontend Heartbeat Control', 'litespeed-cache' ),
			self::O_MISC_HEARTBEAT_FRONT_TTL	=> __( 'Frontend Heartbeat TTL', 'litespeed-cache' ),
			self::O_MISC_HEARTBEAT_BACK			=> __( 'Backend Heartbeat Control', 'litespeed-cache' ),
			self::O_MISC_HEARTBEAT_BACK_TTL		=> __( 'Backend Heartbeat TTL', 'litespeed-cache' ),
			self::O_MISC_HEARTBEAT_EDITOR		=> __( 'Editor Heartbeat', 'litespeed-cache' ),
			self::O_MISC_HEARTBEAT_EDITOR_TTL	=> __( 'Editor Heartbeat TTL', 'litespeed-cache' ),

			self::O_CDN_QUIC					=> __( 'QUIC.cloud CDN', 'litespeed-cache' ),
			self::O_CDN 						=> __( 'Use CDN Mapping', 'litespeed-cache' ),
			self::CDN_MAPPING_URL				=> __( 'CDN URL', 'litespeed-cache' ),
			self::CDN_MAPPING_INC_IMG			=> __( 'Include Images', 'litespeed-cache' ),
			self::CDN_MAPPING_INC_CSS			=> __( 'Include CSS', 'litespeed-cache' ),
			self::CDN_MAPPING_INC_JS			=> __( 'Include JS', 'litespeed-cache' ),
			self::CDN_MAPPING_FILETYPE			=> __( 'Include File Types', 'litespeed-cache' ),
			self::O_CDN_ATTR					=> __( 'HTML Attribute To Replace', 'litespeed-cache' ),
			self::O_CDN_ORI						=> __( 'Original URLs', 'litespeed-cache' ),
			self::O_CDN_ORI_DIR					=> __( 'Included Directories', 'litespeed-cache' ),
			self::O_CDN_EXC						=> __( 'Exclude Path', 'litespeed-cache' ),
			self::O_CDN_CLOUDFLARE				=> __( 'Cloudflare API', 'litespeed-cache' ),

			self::O_CRAWLER					=> __( 'Crawler', 'litespeed-cache' ),
			self::O_CRAWLER_USLEEP			=> __( 'Delay', 'litespeed-cache' ),
			self::O_CRAWLER_RUN_DURATION	=> __( 'Run Duration', 'litespeed-cache' ),
			self::O_CRAWLER_RUN_INTERVAL	=> __( 'Interval Between Runs', 'litespeed-cache' ),
			self::O_CRAWLER_CRAWL_INTERVAL	=> __( 'Crawl Interval', 'litespeed-cache' ),
			self::O_CRAWLER_THREADS			=> __( 'Threads', 'litespeed-cache' ),
			self::O_CRAWLER_TIMEOUT			=> __( 'Timeout', 'litespeed-cache' ),
			self::O_CRAWLER_LOAD_LIMIT		=> __( 'Server Load Limit', 'litespeed-cache' ),
			self::O_CRAWLER_ROLES			=> __( 'Role Simulation', 'litespeed-cache' ),
			self::O_CRAWLER_COOKIES			=> __( 'Cookie Simulation', 'litespeed-cache' ),
			self::O_CRAWLER_SITEMAP			=> __( 'Custom Sitemap', 'litespeed-cache' ),
			self::O_CRAWLER_DROP_DOMAIN		=> __( 'Drop Domain from Sitemap', 'litespeed-cache' ),
			self::O_CRAWLER_MAP_TIMEOUT		=> __( 'Sitemap Timeout', 'litespeed-cache' ),

			self::O_DEBUG_DISABLE_ALL			=> __( 'Disable All Features', 'litespeed-cache' ),
			self::O_DEBUG						=> __( 'Debug Log', 'litespeed-cache' ),
			self::O_DEBUG_IPS					=> __( 'Admin IPs', 'litespeed-cache' ),
			self::O_DEBUG_LEVEL					=> __( 'Debug Level', 'litespeed-cache' ),
			self::O_DEBUG_FILESIZE				=> __( 'Log File Size Limit', 'litespeed-cache' ),
			self::O_DEBUG_COOKIE				=> __( 'Log Cookies', 'litespeed-cache' ),
			self::O_DEBUG_COLLAPS_QS			=> __( 'Collapse Query Strings', 'litespeed-cache' ),
			self::O_DEBUG_INC					=> __( 'Debug URI Includes', 'litespeed-cache' ),
			self::O_DEBUG_EXC					=> __( 'Debug URI Excludes', 'litespeed-cache' ),
			self::O_DEBUG_EXC_STRINGS			=> __( 'Debug String Excludes', 'litespeed-cache' ),

			self::O_DB_OPTM_REVISIONS_MAX		=> __( 'Revisions Max Number', 'litespeed-cache' ),
			self::O_DB_OPTM_REVISIONS_AGE		=> __( 'Revisions Max Age', 'litespeed-cache' ),

		) ;

		if ( array_key_exists( $id, $_lang_list ) ) {
			return $_lang_list[ $id ] ;
		}

		return 'N/A' ;
	}

}
