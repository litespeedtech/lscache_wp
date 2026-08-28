<?php
/**
 * The report class
 *
 * @since      1.1.0
 * @package    LiteSpeed
 */

namespace LiteSpeed;

defined('WPINC') || exit();

/**
 * Generates and sends the environment report to the LiteSpeed center server.
 */
class Report extends Base {

	const TYPE_SEND_REPORT = 'send_report';

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  1.6.5
	 * @access public
	 */
	public function handler() {
		// Defer until after `wp_loaded` so 3rd-party appended options (e.g. WooCommerce wc_update_interval / wc_cart_vary) are present in the regenerated report.
		if (!did_action('wp_loaded')) {
			add_action('wp_loaded', [ $this, 'handler' ], 5);
			return;
		}

		$type = Router::verify_type();

		switch ($type) {
			case self::TYPE_SEND_REPORT:
				$this->post_env();
				break;

			default:
				break;
		}

		Admin::redirect();
	}

	/**
	 * Post env report number to ls center server
	 *
	 * @since  1.6.5
	 * @access public
	 */
	public function post_env() {
		$report_con = $this->generate_environment_report();

		// Generate link.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in Router::verify_type().
		$link = !empty($_POST['link']) ? esc_url_raw(wp_unslash($_POST['link'])) : '';

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in Router::verify_type().
		$notes = !empty($_POST['notes']) ? sanitize_textarea_field(wp_unslash($_POST['notes'])) : '';

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in Router::verify_type().
		$php_info   = !empty($_POST['attach_php']) ? sanitize_text_field(wp_unslash($_POST['attach_php'])) : '';
		$report_php = '1' === $php_info ? $this->generate_php_report() : '';

		if ($report_php) {
			$report_con .= "\nPHPINFO\n" . $report_php;
		}

		$data = [
			'env' => $report_con,
			'link' => $link,
			'notes' => $notes,
		];

		$json = Cloud::post(Cloud::API_REPORT, $data);
		if (!is_array($json)) {
			return;
		}

		$num     = !empty($json['num']) ? $json['num'] : '--';
		$summary = [
			'num' => $num,
			'dateline' => time(),
		];

		self::save_summary($summary);

		return $num;
	}

	/**
	 * Gathers the PHP information.
	 *
	 * @since 7.0
	 * @access public
	 *
	 * @param int $flags Flags passed to phpinfo().
	 * @return string
	 */
	public function generate_php_report( $flags = INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES ) {
		// INFO_ENVIRONMENT
		$report = '';

		ob_start();
		phpinfo($flags); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_phpinfo
		$report = ob_get_contents();
		ob_end_clean();

		preg_match('%<style type="text/css">(.*?)</style>.*?<body>(.*?)</body>%s', $report, $report);

		return $report[2];
	}

	/**
	 * Gathers the environment details and creates the report.
	 * Will write to the environment report file.
	 *
	 * @since 1.0.12
	 * @access public
	 *
	 * @param array|null $options Plugin options to report, or null to load the current options.
	 * @return string
	 */
	public function generate_environment_report( $options = null ) {
		global $wp_version, $_SERVER;
		$frontend_htaccess = Htaccess::get_frontend_htaccess();
		$backend_htaccess  = Htaccess::get_backend_htaccess();
		$paths             = [ $frontend_htaccess ];
		if ($frontend_htaccess !== $backend_htaccess) {
			$paths[] = $backend_htaccess;
		}

		if (is_multisite()) {
			$active_plugins = get_site_option('active_sitewide_plugins');
			if (!empty($active_plugins)) {
				$active_plugins = array_keys($active_plugins);
			}
		} else {
			$active_plugins = get_option('active_plugins');
		}

		$theme_obj    = wp_get_theme();
		$active_theme = $theme_obj->get('Name');

		$extras = [
			'wordpress version' => $wp_version,
			'siteurl' => get_option('siteurl'),
			'home' => get_option('home'),
			'home_url' => home_url(),
			'locale' => get_locale(),
			'active theme' => $active_theme,
		];

		$extras['active plugins'] = $active_plugins;
		$extras['cloud']          = Cloud::get_summary();
		foreach ([ 'mini_html', 'pk_b64', 'sk_b64', 'cdn_dash', 'ips' ] as $v) {
			unset($extras['cloud'][$v]);
		}

		if (is_null($options)) {
			$options = $this->get_options(true);

			if (is_multisite()) {
				$options2 = $this->get_options();
				foreach ($options2 as $k => $v) {
					if (isset($options[$k]) && $options[$k] !== $v) {
						$options['[Overwritten] ' . $k] = $v;
					}
				}
			}
		}

		if (!is_null($options) && is_multisite()) {
			$blogs = Activation::get_network_ids();
			if (!empty($blogs)) {
				$i = 0;
				foreach ($blogs as $blog_id) {
					if (++$i > 3) {
						// Only log 3 subsites
						break;
					}
					$opts = $this->cls('Conf')->load_options($blog_id, true);
					if (isset($opts[self::O_CACHE])) {
						$options['blog ' . $blog_id . ' radio select'] = $opts[self::O_CACHE];
					}
				}
			}
		}

		// Security: Remove cf key in report
		$secure_fields = [ self::O_CDN_CLOUDFLARE_KEY, self::O_OBJECT_PSWD ];
		foreach ($secure_fields as $v) {
			if (!empty($options[$v])) {
				$options[$v] = str_repeat('*', strlen($options[$v]));
			}
		}

		$report = $this->build_environment_report($_SERVER, $options, $extras, $paths);
		return $report;
	}

	/**
	 * Builds the environment report buffer with the given parameters
	 *
	 * @access private
	 *
	 * @param array $server         Server variables to include.
	 * @param array $options        Plugin options to include.
	 * @param array $extras         Extra WordPress-specific values to include.
	 * @param array $htaccess_paths Htaccess file paths whose contents are appended.
	 * @return string
	 */
	private function build_environment_report( $server, $options, $extras = [], $htaccess_paths = [] ) {
		$server_keys   = [
			'DOCUMENT_ROOT' => '',
			'SERVER_SOFTWARE' => '',
			'X-LSCACHE' => '',
			'HTTP_X_LSCACHE' => '',
		];
		$server_vars   = array_intersect_key($server, $server_keys);
		$server_vars[] = 'LSWCP_TAG_PREFIX = ' . LSWCP_TAG_PREFIX;

		$server_vars = array_merge($server_vars, $this->cls('Base')->server_vars());

		$buf = $this->_format_report_section('Server Variables', $server_vars);

		$buf .= $this->_format_report_section('WordPress Specific Extras', $extras);

		$buf .= $this->_format_report_section('LSCache Plugin Options', $options);

		if (empty($htaccess_paths)) {
			return $buf;
		}

		foreach ($htaccess_paths as $path) {
			if (!file_exists($path) || !is_readable($path)) {
				$buf .= $path . " does not exist or is not readable.\n";
				continue;
			}

			$content = file_get_contents($path); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if (false === $content) {
				$buf .= $path . " returned false for file_get_contents.\n";
				continue;
			}
			$buf .= $path . " contents:\n" . $content . "\n\n";
		}
		return $buf;
	}

	/**
	 * Creates a part of the environment report based on a section header and an array for the section parameters.
	 *
	 * @since 1.0.12
	 * @access private
	 *
	 * @param string $section_header Section title.
	 * @param array  $section        Key/value pairs rendered under the section.
	 * @return string
	 */
	private function _format_report_section( $section_header, $section ) {
		$tab = '    '; // four spaces

		if (empty($section)) {
			return 'No matching ' . $section_header . "\n\n";
		}
		$buf = $section_header;

		foreach ($section as $k => $v) {
			$buf .= "\n" . $tab;

			if (!is_numeric($k)) {
				$buf .= $k . ' = ';
			}

			if (!is_string($v)) {
				$v = var_export($v, true); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
			} else {
				$v = esc_html($v);
			}

			$buf .= $v;
		}
		return $buf . "\n\n";
	}
}
