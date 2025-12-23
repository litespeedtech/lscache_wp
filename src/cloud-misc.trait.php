<?php
/**
 * Cloud misc trait
 *
 * @package LiteSpeed
 * @since 7.6
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Trait Cloud_Misc
 *
 * Handles version check, news, usage, promo, and other misc features.
 */
trait Cloud_Misc {

	/**
	 * Load QC status for dash usage.
	 * Format to translate: `<a href="{#xxx#}" class="button button-primary">xxxx</a><a href="{#xxx#}">xxxx2</a>`
	 *
	 * @since 7.0
	 *
	 * @param string $type  Type.
	 * @param bool   $force Force refresh.
	 * @return string
	 */
	public function load_qc_status_for_dash( $type, $force = false ) {
		return Str::translate_qc_apis( $this->_load_qc_status_for_dash( $type, $force ) );
	}

	/**
	 * Internal: load QC status HTML for dash.
	 *
	 * @param string $type  Type.
	 * @param bool   $force Force refresh.
	 * @return string
	 */
	private function _load_qc_status_for_dash( $type, $force = false ) {
		if (
			! $force &&
			! empty( $this->_summary['mini_html'] ) &&
			isset( $this->_summary['mini_html'][ $type ] ) &&
			! empty( $this->_summary['mini_html'][ 'ttl.' . $type ] ) &&
			$this->_summary['mini_html'][ 'ttl.' . $type ] > time()
		) {
			return Str::safe_html( $this->_summary['mini_html'][ $type ] );
		}

		// Try to update dash content
		$data = self::post( self::SVC_D_DASH, [ 'action2' => ( 'cdn_dash_mini' === $type ? 'cdn_dash' : $type ) ] );
		if ( ! empty( $data['qc_activated'] ) ) {
			// Sync conf as changed
			if ( empty( $this->_summary['qc_activated'] ) || $this->_summary['qc_activated'] !== $data['qc_activated'] ) {
				$msg = sprintf( __( 'Congratulations, %s successfully set this domain up for the online services with CDN service.', 'litespeed-cache' ), 'QUIC.cloud' );
				Admin_Display::success( '🎊 ' . $msg );
				$this->_clear_reset_qc_reg_msg();
				// Turn on CDN option
				$this->cls( 'Conf' )->update_confs( [ self::O_CDN_QUIC => true ] );
				$this->cls( 'CDN\Quic' )->try_sync_conf( true );
			}

			$this->_summary['qc_activated'] = $data['qc_activated'];
			$this->save_summary();
		}

		// Show the info
		if ( isset( $this->_summary['mini_html'][ $type ] ) ) {
			return Str::safe_html( $this->_summary['mini_html'][ $type ] );
		}

		return '';
	}

	/**
	 * Show latest commit version always if is on dev
	 *
	 * @since 3.0
	 */
	public function check_dev_version() {
		if ( ! preg_match( '/[^\d\.]/', Core::VER ) ) {
			return;
		}

		$last_check = empty( $this->_summary[ 'last_request.' . self::API_VER ] ) ? 0 : $this->_summary[ 'last_request.' . self::API_VER ];

		if ( time() - $last_check > 86400 ) {
			$auto_v = self::version_check( 'dev' );
			if ( ! empty( $auto_v['dev'] ) ) {
				self::save_summary( [ 'version.dev' => $auto_v['dev'] ] );
			}
		}

		if ( empty( $this->_summary['version.dev'] ) ) {
			return;
		}

		self::debug( 'Latest dev version ' . $this->_summary['version.dev'] );

		if ( version_compare( $this->_summary['version.dev'], Core::VER, '<=' ) ) {
			return;
		}

		// Show the dev banner
		require_once LSCWP_DIR . 'tpl/banner/new_version_dev.tpl.php';
	}

	/**
	 * Check latest version
	 *
	 * @since 2.9
	 * @access public
	 *
	 * @param string|false $src Source.
	 * @return mixed
	 */
	public static function version_check( $src = false ) {
		$req_data = [
			'v'   => defined( 'LSCWP_CUR_V' ) ? LSCWP_CUR_V : '',
			'src' => $src,
			'php' => phpversion(),
		];
		// If code ver is smaller than db ver, bypass
		if ( ! empty( $req_data['v'] ) && version_compare( Core::VER, $req_data['v'], '<' ) ) {
			return;
		}
		if ( defined( 'LITESPEED_ERR' ) ) {
			$litespeed_err   = constant( 'LITESPEED_ERR' );
			$req_data['err'] = base64_encode( ! is_string( $litespeed_err ) ? wp_json_encode( $litespeed_err ) : $litespeed_err ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		}
		$data = self::post( self::API_VER, $req_data );

		return $data;
	}

	/**
	 * Show latest news
	 *
	 * @since 3.0
	 */
	public function news() {
		$this->_update_news();

		if ( empty( $this->_summary['news.new'] ) ) {
			return;
		}

		if ( ! empty( $this->_summary['news.plugin'] ) && Activation::cls()->dash_notifier_is_plugin_active( $this->_summary['news.plugin'] ) ) {
			return;
		}

		require_once LSCWP_DIR . 'tpl/banner/cloud_news.tpl.php';
	}

	/**
	 * Update latest news
	 *
	 * @since 2.9.9.1
	 */
	private function _update_news() {
		if ( ! empty( $this->_summary['news.utime'] ) && time() - (int) $this->_summary['news.utime'] < 86400 * 7 ) {
			return;
		}

		self::save_summary( [ 'news.utime' => time() ] );

		$data = self::get( self::API_NEWS );
		if ( empty( $data['id'] ) ) {
			return;
		}

		// Save news
		if ( ! empty( $this->_summary['news.id'] ) && (string) $this->_summary['news.id'] === (string) $data['id'] ) {
			return;
		}

		$this->_summary['news.id']      = $data['id'];
		$this->_summary['news.plugin']  = ! empty( $data['plugin'] ) ? $data['plugin'] : '';
		$this->_summary['news.title']   = ! empty( $data['title'] ) ? $data['title'] : '';
		$this->_summary['news.content'] = ! empty( $data['content'] ) ? $data['content'] : '';
		$this->_summary['news.zip']     = ! empty( $data['zip'] ) ? $data['zip'] : '';
		$this->_summary['news.new']     = 1;

		if ( $this->_summary['news.plugin'] ) {
			$plugin_info = Activation::cls()->dash_notifier_get_plugin_info( $this->_summary['news.plugin'] );
			if ( $plugin_info && ! empty( $plugin_info->name ) ) {
				$this->_summary['news.plugin_name'] = $plugin_info->name;
			}
		}

		self::save_summary();
	}

	/**
	 * Check if contains a package in a service or not
	 *
	 * @since 4.0
	 *
	 * @param string $service Service.
	 * @param int    $pkg     Package flag.
	 * @return bool
	 */
	public function has_pkg( $service, $pkg ) {
		if ( ! empty( $this->_summary[ 'usage.' . $service ]['pkgs'] ) && ( $this->_summary[ 'usage.' . $service ]['pkgs'] & $pkg ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get allowance of current service
	 *
	 * @since 3.0
	 * @access private
	 *
	 * @param string      $service Service.
	 * @param string|bool $err    Error code by ref.
	 * @return int
	 */
	public function allowance( $service, &$err = false ) {
		// Only auto sync usage at most one time per day
		if ( empty( $this->_summary[ 'last_request.' . self::SVC_D_USAGE ] ) || time() - (int) $this->_summary[ 'last_request.' . self::SVC_D_USAGE ] > 86400 ) {
			$this->sync_usage();
		}

		if ( in_array( $service, [ self::SVC_CCSS, self::SVC_UCSS, self::SVC_VPI ], true ) ) {
			// @since 4.2
			$service = self::SVC_PAGE_OPTM;
		}

		if ( empty( $this->_summary[ 'usage.' . $service ] ) ) {
			return 0;
		}
		$usage = $this->_summary[ 'usage.' . $service ];

		// Image optm is always free
		$allowance_max = 0;
		if ( self::SVC_IMG_OPTM === $service ) {
			$allowance_max = self::IMG_OPTM_DEFAULT_GROUP;
		}

		$allowance = (int) $usage['quota'] - (int) $usage['used'];

		$err = 'out_of_quota';

		if ( $allowance > 0 ) {
			if ( $allowance_max && $allowance_max < $allowance ) {
				$allowance = $allowance_max;
			}

			// Daily limit @since 4.2
			if ( isset( $usage['remaining_daily_quota'] ) && $usage['remaining_daily_quota'] >= 0 && $usage['remaining_daily_quota'] < $allowance ) {
				$allowance = $usage['remaining_daily_quota'];
				if ( ! $allowance ) {
					$err = 'out_of_daily_quota';
				}
			}

			return $allowance;
		}

		// Check Pay As You Go balance
		if ( empty( $usage['pag_bal'] ) ) {
			return $allowance_max;
		}

		if ( $allowance_max && $allowance_max < $usage['pag_bal'] ) {
			return $allowance_max;
		}

		return (int) $usage['pag_bal'];
	}

	/**
	 * Sync Cloud usage summary data
	 *
	 * @since 3.0
	 * @access public
	 */
	public function sync_usage() {
		$usage = $this->_post( self::SVC_D_USAGE );
		if ( ! $usage ) {
			return;
		}

		self::debug( 'sync_usage ' . wp_json_encode( $usage ) );

		foreach ( self::$services as $v ) {
			$this->_summary[ 'usage.' . $v ] = ! empty( $usage[ $v ] ) ? $usage[ $v ] : false;
		}

		self::save_summary();

		return $this->_summary;
	}

	/**
	 * REST call: check if the error domain is valid call for auto alias purpose
	 *
	 * @since 5.0
	 */
	public function rest_err_domains() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$alias = !empty( $_POST['alias'] ) ? sanitize_text_field( wp_unslash( $_POST['alias'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['main_domain'] ) || !$alias ) {
			return self::err( 'lack_of_param' );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->extract_msg( $_POST, 'Quic.cloud', false, true );

		if ( $this->_is_err_domain( $alias ) ) {
			if ( site_url() === $alias ) {
				$this->_remove_domain_from_err_list( $alias );
			}
			return self::ok();
		}

		return self::err( 'Not an alias req from here' );
	}

	/**
	 * Remove a domain from err domain
	 *
	 * @since 5.0
	 *
	 * @param string $url URL to remove.
	 */
	private function _remove_domain_from_err_list( $url ) {
		unset( $this->_summary['err_domains'][ $url ] );
		self::save_summary();
	}

	/**
	 * Check if is err domain
	 *
	 * @since 5.0
	 *
	 * @param string $site_url Site URL.
	 * @return bool
	 */
	private function _is_err_domain( $site_url ) {
		if ( empty( $this->_summary['err_domains'] ) ) {
			return false;
		}
		if ( ! array_key_exists( $site_url, $this->_summary['err_domains'] ) ) {
			return false;
		}
		// Auto delete if too long ago
		if ( time() - (int) $this->_summary['err_domains'][ $site_url ] > 86400 * 10 ) {
			$this->_remove_domain_from_err_list( $site_url );

			return false;
		}
		if ( time() - (int) $this->_summary['err_domains'][ $site_url ] > 86400 ) {
			return false;
		}
		return true;
	}

	/**
	 * Show promo from cloud
	 *
	 * @since  3.0
	 * @access public
	 */
	public function show_promo() {
		if ( empty( $this->_summary['promo'] ) ) {
			return;
		}

		require_once LSCWP_DIR . 'tpl/banner/cloud_promo.tpl.php';
	}

	/**
	 * Clear promo from cloud
	 *
	 * @since  3.0
	 * @access private
	 */
	private function _clear_promo() {
		if ( count( $this->_summary['promo'] ) > 1 ) {
			array_shift( $this->_summary['promo'] );
		} else {
			$this->_summary['promo'] = [];
		}
		self::save_summary();
	}

	/**
	 * Display a banner for dev env if using preview QC node.
	 *
	 * @since 7.0
	 */
	public function maybe_preview_banner() {
		if ( false !== strpos( $this->_cloud_server, 'preview.' ) ) {
			Admin_Display::note( __( 'Linked to QUIC.cloud preview environment, for testing purpose only.', 'litespeed-cache' ), true, true, 'litespeed-warning-bg' );
		}
	}
}
