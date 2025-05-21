<?php

/**
 * Auto registration for LiteSpeed classes
 *
 * @since       1.1.0
 */
defined('WPINC') || exit();

// Force define for object cache usage before plugin init
!defined('LSCWP_DIR') && define('LSCWP_DIR', __DIR__ . '/'); // Full absolute path '/var/www/html/***/wp-content/plugins/litespeed-cache/' or MU

// Load all classes instead of autoload for direct conf update purpose when upgrade to new version.
// NOTE: These files need to load exactly in order
$litespeed_php_files = array(
	// core file priority
	'src/root.cls.php',
	'src/base.cls.php',

	// main src files
	'src/activation.cls.php',
	'src/admin-display.cls.php',
	'src/admin-settings.cls.php',
	'src/admin.cls.php',
	'src/api.cls.php',
	'src/avatar.cls.php',
	'src/cdn.cls.php',
	'src/cloud.cls.php',
	'src/conf.cls.php',
	'src/control.cls.php',
	'src/core.cls.php',
	'src/crawler-map.cls.php',
	'src/crawler.cls.php',
	'src/css.cls.php',
	'src/data.cls.php',
	'src/db-optm.cls.php',
	'src/debug2.cls.php',
	'src/doc.cls.php',
	'src/error.cls.php',
	'src/esi.cls.php',
	'src/file.cls.php',
	'src/gui.cls.php',
	'src/health.cls.php',
	'src/htaccess.cls.php',
	'src/img-optm.cls.php',
	'src/import.cls.php',
	'src/import.preset.cls.php',
	'src/lang.cls.php',
	'src/localization.cls.php',
	'src/media.cls.php',
	'src/metabox.cls.php',
	'src/object-cache.cls.php',
	'src/optimize.cls.php',
	'src/optimizer.cls.php',
	'src/placeholder.cls.php',
	'src/purge.cls.php',
	'src/report.cls.php',
	'src/rest.cls.php',
	'src/router.cls.php',
	'src/str.cls.php',
	'src/tag.cls.php',
	'src/task.cls.php',
	'src/tool.cls.php',
	'src/ucss.cls.php',
	'src/utility.cls.php',
	'src/vary.cls.php',
	'src/vpi.cls.php',

	// Extra CDN cls files
	'src/cdn/cloudflare.cls.php',
	'src/cdn/quic.cls.php',

	// CLI classes
	'cli/crawler.cls.php',
	'cli/debug.cls.php',
	'cli/image.cls.php',
	'cli/online.cls.php',
	'cli/option.cls.php',
	'cli/presets.cls.php',
	'cli/purge.cls.php',

	// 3rd party libraries
	'lib/css_js_min/pathconverter/converter.cls.php',
	'lib/css_js_min/minify/exception.cls.php',
	'lib/css_js_min/minify/minify.cls.php',
	'lib/css_js_min/minify/css.cls.php',
	'lib/css_js_min/minify/js.cls.php',
	'lib/urirewriter.cls.php',
	'lib/guest.cls.php',
	'lib/html-min.cls.php',
	// 'lib/object-cache.php',
	// 'lib/php-compatibility.func.php',

	// upgrade purpose delay loaded funcs
	// 'src/data.upgrade.func.php',
);
foreach ($litespeed_php_files as $class) {
	$file = LSCWP_DIR . $class;
	require_once $file;
}

if (!function_exists('litespeed_autoload')) {
	function litespeed_autoload( $cls ) {
		if (strpos($cls, '.') !== false) {
			return;
		}

		if (strpos($cls, 'LiteSpeed') !== 0) {
			return;
		}

		$file = explode('\\', $cls);
		array_shift($file);
		$file = implode('/', $file);
		$file = str_replace('_', '-', strtolower($file));

		// if (strpos($file, 'lib/') === 0 || strpos($file, 'cli/') === 0 || strpos($file, 'thirdparty/') === 0) {
		// $file = LSCWP_DIR . $file . '.cls.php';
		// } else {
		// $file = LSCWP_DIR . 'src/' . $file . '.cls.php';
		// }

		if (strpos($file, 'thirdparty/') !== 0) {
			return;
		}

		$file = LSCWP_DIR . $file . '.cls.php';

		if (file_exists($file)) {
			require_once $file;
		}
	}
}

spl_autoload_register('litespeed_autoload');
