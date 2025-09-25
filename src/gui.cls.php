<?php
/**
 * The frontend GUI class.
 *
 * Provides front-end and admin-bar UI helpers for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.3
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * GUI helpers for LiteSpeed Cache.
 */
class GUI extends Base {
	const LOG_TAG = '[GUI]';

	/**
	 * Counter for temporary HTML wrappers.
	 *
	 * @var int Counter for temporary HTML wrappers to remove from the buffer.
	 */
	private static $_clean_counter = 0;

	/**
	 * Promo display flag.
	 *
	 * @var bool Internal flag used by promo templates to decide whether to display.
	 */
	private $_promo_true = false;

	/**
	 * Promo list configuration.
	 *
	 * Format: [ file_tag => [ days, litespeed_only ], ... ]
	 *
	 * @var array<string, array{0:int,1:bool}>
	 */
	private $_promo_list = [
		'new_version' => [ 7, false ],
		'score'       => [ 14, false ],
		// 'slack'      => [ 3, false ],
	];

	/** Path to guest JavaScript file. */
	const LIB_GUEST_JS = 'assets/js/guest.min.js';

	/** Path to guest document.referrer JavaScript file. */
	const LIB_GUEST_DOCREF_JS = 'assets/js/guest.docref.min.js';

	/** Path to guest vary endpoint. */
	const PHP_GUEST = 'guest.vary.php';

	/** Dismiss type: WHM. */
	const TYPE_DISMISS_WHM = 'whm';

	/** Dismiss type: ExpiresDefault. */
	const TYPE_DISMISS_EXPIRESDEFAULT = 'ExpiresDefault';

	/** Dismiss type: Promo. */
	const TYPE_DISMISS_PROMO = 'promo';

	/** Dismiss type: PIN. */
	const TYPE_DISMISS_PIN = 'pin';

	/** WHM message option name. */
	const WHM_MSG = 'lscwp_whm_install';

	/** WHM message option value. */
	const WHM_MSG_VAL = 'whm_install';

	/**
	 * Summary options cache.
	 *
	 * @var array<string,mixed> Summary/options cache.
	 */
	protected $_summary;

	/**
	 * Instance.
	 *
	 * @since 1.3
	 */
	public function __construct() {
		$this->_summary = self::get_summary();
	}

	/**
	 * Frontend init.
	 *
	 * @since 3.0
	 */
	public function init() {
		self::debug2( 'init' );

		if ( is_admin_bar_showing() && current_user_can( 'manage_options' ) ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'frontend_enqueue_style' ] );
			add_action( 'admin_bar_menu', [ $this, 'frontend_shortcut' ], 95 );
		}

		/**
		 * Turn on instant click.
		 *
		 * @since 1.8.2
		 */
		if ( $this->conf( self::O_UTIL_INSTANT_CLICK ) ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'frontend_enqueue_style_public' ] );
		}

		// NOTE: this needs to be before optimizer to avoid wrapper being removed.
		add_filter( 'litespeed_buffer_finalize', [ $this, 'finalize' ], 8 );
	}

	/**
	 * Print a loading message when redirecting CCSS/UCSS page to avoid blank page confusion.
	 *
	 * @param int    $counter Files left in queue.
	 * @param string $type    Queue type label.
	 * @return void
	 */
	public static function print_loading( $counter, $type ) {
		echo '<div style="font-size:25px;text-align:center;padding-top:150px;width:100%;position:absolute;">';
		echo "<img width='35' src='" . esc_url( LSWCP_PLUGIN_URL . 'assets/img/Litespeed.icon.svg' ) . "' alt='' />   ";
		printf(
			/* translators: 1: number, 2: text */
			esc_html__( '%1$s %2$s files left in queue', 'litespeed-cache' ),
			esc_html( number_format_i18n( $counter ) ),
			esc_html( $type )
		);
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=litespeed-page_optm' ) ) . '">' . esc_html__( 'Cancel', 'litespeed-cache' ) . '</a></p>';
		echo '</div>';
	}

	/**
	 * Display the tab list.
	 *
	 * @since 7.3
	 *
	 * @param array<string,string> $tabs Key => Label pairs.
	 * @return void
	 */
	public static function display_tab_list( $tabs ) {
		$i = 1;
		foreach ( $tabs as $k => $val ) {
			$accesskey = $i <= 9 ? $i : '';
			printf(
				'<a class="litespeed-tab nav-tab" href="#%1$s" data-litespeed-tab="%1$s" litespeed-accesskey="%2$s">%3$s</a>',
				esc_attr( $k ),
				esc_attr( $accesskey ),
				esc_html( $val )
			);
			++$i;
		}
	}

	/**
	 * Render a pie chart SVG string.
	 *
	 * @since 1.6.6
	 *
	 * @param int         $percent             Percentage 0-100.
	 * @param int         $width               Width/height in pixels.
	 * @param bool        $finished_tick       Show a tick when 100%.
	 * @param bool        $without_percentage  Hide the % label.
	 * @param string|bool $append_cls          Extra CSS class.
	 * @return string SVG markup.
	 */
	public static function pie( $percent, $width = 50, $finished_tick = false, $without_percentage = false, $append_cls = false ) {
		$label      = $without_percentage ? $percent : ( $percent . '%' );
		$percentage = '<text x="50%" y="50%">' . esc_html( $label ) . '</text>';

		if ( 100 === $percent && $finished_tick ) {
			$percentage = '<text x="50%" y="50%" class="litespeed-pie-done">✓</text>';
		}

		$svg = sprintf(
			"<svg class='litespeed-pie %1\$s' viewbox='0 0 33.83098862 33.83098862' width='%2\$d' height='%2\$d' xmlns='http://www.w3.org/2000/svg'>
				<circle class='litespeed-pie_bg' cx='16.91549431' cy='16.91549431' r='15.91549431' />
				<circle class='litespeed-pie_circle' cx='16.91549431' cy='16.91549431' r='15.91549431' stroke-dasharray='%3\$d,100' />
				<g class='litespeed-pie_info'>%4\$s</g>
			</svg>",
			esc_attr( $append_cls ),
			$width,
			$percent,
			$percentage
		);

		return $svg;
	}

	/**
	 * Allowed SVG tags/attributes for kses.
	 *
	 * @since 7.3
	 *
	 * @return array<string,array<string,bool>> Allowed tags/attributes.
	 */
	public static function allowed_svg_tags() {
		return [
			'svg'    => [
				'width'   => true,
				'height'  => true,
				'viewbox' => true, // Note: SVG standard uses 'viewBox', but wp_kses normalizes to lowercase.
				'xmlns'   => true,
				'class'   => true,
				'id'      => true,
			],
			'circle' => [
				'cx'               => true,
				'cy'               => true,
				'r'                => true,
				'fill'             => true,
				'stroke'           => true,
				'class'            => true,
				'stroke-width'     => true,
				'stroke-dasharray' => true,
			],
			'path'   => [
				'd'      => true,
				'fill'   => true,
				'stroke' => true,
			],
			'text'   => [
				'x'            => true,
				'y'            => true,
				'dx'           => true,
				'dy'           => true,
				'font-size'    => true,
				'font-family'  => true,
				'font-weight'  => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'text-anchor'  => true,
				'class'        => true,
				'id'           => true,
			],
			'g'      => [
				'transform'    => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'class'        => true,
				'id'           => true,
			],
			'button' => [
				'type'               => true,
				'data-balloon-break' => true,
				'data-balloon-pos'   => true,
				'aria-label'         => true,
				'class'              => true,
			],
		];
	}

	/**
	 * Display a tiny pie with a tooltip.
	 *
	 * @since 3.0
	 *
	 * @param int         $percent    Percentage 0-100.
	 * @param int         $width      Width/height in pixels.
	 * @param string      $tooltip    Tooltip text.
	 * @param string      $tooltip_pos Tooltip position (e.g., 'up').
	 * @param string|bool $append_cls Extra CSS class.
	 * @return string HTML/SVG.
	 */
	public static function pie_tiny( $percent, $width = 50, $tooltip = '', $tooltip_pos = 'up', $append_cls = false ) {
		// formula C = 2πR.
		$dasharray = 2 * 3.1416 * 9 * ( $percent / 100 );

		return sprintf(
			"
		<button type='button' data-balloon-break data-balloon-pos='%1\$s' aria-label='%2\$s' class='litespeed-btn-pie'>
		<svg class='litespeed-pie litespeed-pie-tiny %3\$s' viewbox='0 0 30 30' width='%4\$d' height='%4\$d' xmlns='http://www.w3.org/2000/svg'>
			<circle class='litespeed-pie_bg' cx='15' cy='15' r='9' />
			<circle class='litespeed-pie_circle' cx='15' cy='15' r='9' stroke-dasharray='%5\$s,100' />
			<g class='litespeed-pie_info'><text x='50%%' y='50%%'>i</text></g>
		</svg>
		</button>
		",
			esc_attr( $tooltip_pos ),
			esc_attr( $tooltip ),
			esc_attr( $append_cls ),
			$width,
			esc_attr( $dasharray )
		);
	}

	/**
	 * Get CSS class name for PageSpeed score.
	 *
	 * Scale:
	 *  90-100 (fast)
	 *  50-89 (average)
	 *  0-49 (slow)
	 *
	 * @since 2.9
	 * @access public
	 *
	 * @param int $score Score 0-100.
	 * @return string Class name: success|warning|danger.
	 */
	public function get_cls_of_pagescore( $score ) {
		if ( $score >= 90 ) {
			return 'success';
		}

		if ( $score >= 50 ) {
			return 'warning';
		}

		return 'danger';
	}

	/**
	 * Handle dismiss actions for banners and notices.
	 *
	 * @since 1.0
	 * @access public
	 * @return void
	 */
	public static function dismiss() {
		$_instance = self::cls();

		switch ( Router::verify_type() ) {
			case self::TYPE_DISMISS_WHM:
            self::dismiss_whm();
				break;

			case self::TYPE_DISMISS_EXPIRESDEFAULT:
            self::update_option( Admin_Display::DB_DISMISS_MSG, Admin_Display::RULECONFLICT_DISMISSED );
				break;

			case self::TYPE_DISMISS_PIN:
            Admin_Display::dismiss_pin();
				break;

			case self::TYPE_DISMISS_PROMO:
            if ( empty( $_GET['promo_tag'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					break;
				}

            $promo_tag = sanitize_key( wp_unslash( $_GET['promo_tag'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ( empty( $_instance->_promo_list[ $promo_tag ] ) ) {
					break;
				}

            defined( 'LSCWP_LOG' ) && self::debug( 'Dismiss promo ' . $promo_tag );

            // Forever dismiss.
            if ( ! empty( $_GET['done'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$_instance->_summary[ $promo_tag ] = 'done';
				} elseif ( ! empty( $_GET['later'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                // Delay the banner to half year later.
                $_instance->_summary[ $promo_tag ] = time() + ( 86400 * 180 );
				} else {
                // Update welcome banner to 30 days after.
                $_instance->_summary[ $promo_tag ] = time() + ( 86400 * 30 );
				}

            self::save_summary();

				break;

			default:
				break;
		}

		if ( Router::is_ajax() ) {
			// All dismiss actions are considered as ajax call, so just exit.
			exit( wp_json_encode( [ 'success' => 1 ] ) );
		}

		// Plain click link, redirect to referral url.
		Admin::redirect();
	}

	/**
	 * Check if has rule conflict notice.
	 *
	 * @since 1.1.5
	 * @access public
	 *
	 * @return bool True if message should be shown.
	 */
	public static function has_msg_ruleconflict() {
		$db_dismiss_msg = self::get_option( Admin_Display::DB_DISMISS_MSG );
		if ( ! $db_dismiss_msg ) {
			self::update_option( Admin_Display::DB_DISMISS_MSG, -1 );
		}
		return Admin_Display::RULECONFLICT_ON === $db_dismiss_msg;
	}

	/**
	 * Check if has WHM notice.
	 *
	 * @since 1.1.1
	 * @access public
	 *
	 * @return bool True if message should be shown.
	 */
	public static function has_whm_msg() {
		$val = self::get_option( self::WHM_MSG );
		if ( ! $val ) {
			self::dismiss_whm();
			return false;
		}
		return self::WHM_MSG_VAL === $val;
	}

	/**
	 * Delete WHM message tag.
	 *
	 * @since 1.1.1
	 * @access public
	 * @return void
	 */
	public static function dismiss_whm() {
		self::update_option( self::WHM_MSG, -1 );
	}

	/**
	 * Whether current request is a LiteSpeed admin page.
	 *
	 * @since 2.9
	 *
	 * @return bool True if LiteSpeed page.
	 */
	private function _is_litespeed_page() {
		if (
			! empty( $_GET['page'] ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			in_array(
				(string) $_GET['page'], // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				[
					'litespeed-settings',
					'litespeed-dash',
					Admin::PAGE_EDIT_HTACCESS,
					'litespeed-optimization',
					'litespeed-crawler',
					'litespeed-import',
					'litespeed-report',
				],
				true
			)
		) {
			return true;
		}

		return false;
	}

	/**
	 * Display promo banner (or check-only mode to know which promo would display).
	 *
	 * @since 2.1
	 * @access public
	 *
	 * @param bool $check_only If true, only return the promo tag that would be shown.
	 * @return false|string False if none, or the promo tag string.
	 */
	public function show_promo( $check_only = false ) {
		$is_litespeed_page = $this->_is_litespeed_page();

		// Bypass showing info banner if disabled all in debug.
		if ( defined( 'LITESPEED_DISABLE_ALL' ) && LITESPEED_DISABLE_ALL ) {
			return false;
		}

		if ( file_exists( ABSPATH . '.litespeed_no_banner' ) ) {
			defined( 'LSCWP_LOG' ) && self::debug( 'Bypass banners due to silence file' );
			return false;
		}

		foreach ( $this->_promo_list as $promo_tag => $v ) {
			list( $delay_days, $litespeed_page_only ) = $v;

			if ( $litespeed_page_only && ! $is_litespeed_page ) {
				continue;
			}

			// First time check.
			if ( empty( $this->_summary[ $promo_tag ] ) ) {
				$this->_summary[ $promo_tag ] = time() + 86400 * $delay_days;
				self::save_summary();
				continue;
			}

			$promo_timestamp = $this->_summary[ $promo_tag ];

			// Was ticked as done.
			if ( 'done' === $promo_timestamp ) {
				continue;
			}

			// Not reach the dateline yet.
			if ( time() < $promo_timestamp ) {
				continue;
			}

			// Try to load, if can pass, will set $this->_promo_true = true.
			$this->_promo_true = false;
			include LSCWP_DIR . "tpl/banner/$promo_tag.php";

			// If not defined, means it didn't pass the display workflow in tpl.
			if ( ! $this->_promo_true ) {
				continue;
			}

			if ( $check_only ) {
				return $promo_tag;
			}

			defined( 'LSCWP_LOG' ) && self::debug( 'Show promo ' . $promo_tag );

			// Only contain one.
			break;
		}

		return false;
	}

	/**
	 * Load frontend public script.
	 *
	 * @since 1.8.2
	 * @access public
	 * @return void
	 */
	public function frontend_enqueue_style_public() {
		wp_enqueue_script( Core::PLUGIN_NAME, LSWCP_PLUGIN_URL . 'assets/js/instant_click.min.js', [], Core::VER, true );
	}

	/**
	 * Load frontend stylesheet.
	 *
	 * @since 1.3
	 * @access public
	 * @return void
	 */
	public function frontend_enqueue_style() {
		wp_enqueue_style( Core::PLUGIN_NAME, LSWCP_PLUGIN_URL . 'assets/css/litespeed.css', [], Core::VER, 'all' );
	}

	/**
	 * Load frontend menu shortcut items in the admin bar.
	 *
	 * @since  1.3
	 * @since  7.6 Add VPI clear.
	 * @access public
	 * @return void
	 */
	public function frontend_shortcut() {
		global $wp_admin_bar;

		$wp_admin_bar->add_menu(
			[
				'id'    => 'litespeed-menu',
				'title' => '<span class="ab-icon"></span>',
				'href'  => get_admin_url( null, 'admin.php?page=litespeed' ),
				'meta'  => [
					'tabindex' => 0,
					'class'    => 'litespeed-top-toolbar',
				],
			]
		);

		$wp_admin_bar->add_menu(
			[
				'parent' => 'litespeed-menu',
				'id'     => 'litespeed-purge-single',
				'title'  => esc_html__( 'Purge this page', 'litespeed-cache' ) . ' - LSCache',
				'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_FRONT, false, true ),
				'meta'   => [ 'tabindex' => '0' ],
			]
		);

		if ( $this->has_cache_folder( 'ucss' ) ) {
			$possible_url_tag = UCSS::get_url_tag();
			$append_arr       = [];
			if ( $possible_url_tag ) {
				$append_arr['url_tag'] = $possible_url_tag;
			}

			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-menu',
					'id'     => 'litespeed-purge-single-ucss',
					'title'  => esc_html__( 'Purge this page', 'litespeed-cache' ) . ' - UCSS',
					'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_UCSS, false, true, $append_arr ),
					'meta'   => [ 'tabindex' => '0' ],
				]
			);
		}

		$wp_admin_bar->add_menu(
			[
				'parent' => 'litespeed-menu',
				'id'     => 'litespeed-single-action',
				'title'  => esc_html__( 'Mark this page as ', 'litespeed-cache' ),
				'meta'   => [ 'tabindex' => '0' ],
			]
		);

		$current_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		if ( $current_uri ) {
			$append_arr = [
				Conf::TYPE_SET . '[' . self::O_CACHE_FORCE_URI . '][]' => $current_uri . '$',
				'redirect'                                           => $current_uri,
			];
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-single-action',
					'id'     => 'litespeed-single-forced_cache',
					'title'  => esc_html__( 'Forced cacheable', 'litespeed-cache' ),
					'href'   => Utility::build_url( Router::ACTION_CONF, Conf::TYPE_SET, false, true, $append_arr ),
				]
			);

			$append_arr = [
				Conf::TYPE_SET . '[' . self::O_CACHE_EXC . '][]' => $current_uri . '$',
				'redirect'                                      => $current_uri,
			];
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-single-action',
					'id'     => 'litespeed-single-noncache',
					'title'  => esc_html__( 'Non cacheable', 'litespeed-cache' ),
					'href'   => Utility::build_url( Router::ACTION_CONF, Conf::TYPE_SET, false, true, $append_arr ),
				]
			);

			$append_arr = [
				Conf::TYPE_SET . '[' . self::O_CACHE_PRIV_URI . '][]' => $current_uri . '$',
				'redirect'                                           => $current_uri,
			];
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-single-action',
					'id'     => 'litespeed-single-private',
					'title'  => esc_html__( 'Private cache', 'litespeed-cache' ),
					'href'   => Utility::build_url( Router::ACTION_CONF, Conf::TYPE_SET, false, true, $append_arr ),
				]
			);

			$append_arr = [
				Conf::TYPE_SET . '[' . self::O_OPTM_EXC . '][]' => $current_uri . '$',
				'redirect'                                      => $current_uri,
			];
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-single-action',
					'id'     => 'litespeed-single-nonoptimize',
					'title'  => esc_html__( 'No optimization', 'litespeed-cache' ),
					'href'   => Utility::build_url( Router::ACTION_CONF, Conf::TYPE_SET, false, true, $append_arr ),
				]
			);
		}

		$wp_admin_bar->add_menu(
			[
				'parent' => 'litespeed-single-action',
				'id'     => 'litespeed-single-more',
				'title'  => esc_html__( 'More settings', 'litespeed-cache' ),
				'href'   => get_admin_url( null, 'admin.php?page=litespeed-cache' ),
			]
		);

		$wp_admin_bar->add_menu(
			[
				'parent' => 'litespeed-menu',
				'id'     => 'litespeed-purge-all',
				'title'  => esc_html__( 'Purge All', 'litespeed-cache' ),
				'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL, false, '_ori' ),
				'meta'   => [ 'tabindex' => '0' ],
			]
		);

		$wp_admin_bar->add_menu(
			[
				'parent' => 'litespeed-menu',
				'id'     => 'litespeed-purge-all-lscache',
				'title'  => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'LSCache', 'litespeed-cache' ),
				'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_LSCACHE, false, '_ori' ),
				'meta'   => [ 'tabindex' => '0' ],
			]
		);

		$wp_admin_bar->add_menu(
			[
				'parent' => 'litespeed-menu',
				'id'     => 'litespeed-purge-cssjs',
				'title'  => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'CSS/JS Cache', 'litespeed-cache' ),
				'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_CSSJS, false, '_ori' ),
				'meta'   => [ 'tabindex' => '0' ],
			]
		);

		if ( $this->conf( self::O_CDN_CLOUDFLARE ) ) {
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-menu',
					'id'     => 'litespeed-purge-cloudflare',
					'title'  => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'Cloudflare', 'litespeed-cache' ),
					'href'   => Utility::build_url( Router::ACTION_CDN_CLOUDFLARE, CDN\Cloudflare::TYPE_PURGE_ALL ),
					'meta'   => [ 'tabindex' => '0' ],
				]
			);
		}

		if ( defined( 'LSCWP_OBJECT_CACHE' ) ) {
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-menu',
					'id'     => 'litespeed-purge-object',
					'title'  => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'Object Cache', 'litespeed-cache' ),
					'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_OBJECT, false, '_ori' ),
					'meta'   => [ 'tabindex' => '0' ],
				]
			);
		}

		if ( Router::opcache_enabled() ) {
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-menu',
					'id'     => 'litespeed-purge-opcache',
					'title'  => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'Opcode Cache', 'litespeed-cache' ),
					'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_OPCACHE, false, '_ori' ),
					'meta'   => [ 'tabindex' => '0' ],
				]
			);
		}

		if ( $this->has_cache_folder( 'ccss' ) ) {
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-menu',
					'id'     => 'litespeed-purge-ccss',
					'title'  => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - CCSS',
					'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_CCSS, false, '_ori' ),
					'meta'   => [ 'tabindex' => '0' ],
				]
			);
		}

		if ( $this->has_cache_folder( 'ucss' ) ) {
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-menu',
					'id'     => 'litespeed-purge-ucss',
					'title'  => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - UCSS',
					'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_UCSS, false, '_ori' ),
				]
			);
		}

		if ( $this->has_cache_folder( 'localres' ) ) {
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-menu',
					'id'     => 'litespeed-purge-localres',
					'title'  => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'Localized Resources', 'litespeed-cache' ),
					'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_LOCALRES, false, '_ori' ),
					'meta'   => [ 'tabindex' => '0' ],
				]
			);
		}

		if ( $this->has_cache_folder( 'lqip' ) ) {
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-menu',
					'id'     => 'litespeed-purge-placeholder',
					'title'  => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'LQIP Cache', 'litespeed-cache' ),
					'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_LQIP, false, '_ori' ),
					'meta'   => [ 'tabindex' => '0' ],
				]
			);
		}
    
		if ( $this->has_cache_folder( 'vpi' ) ) {
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-menu',
					'id'     => 'litespeed-purge-vpi',
					'title'  => __( 'Purge All', 'litespeed-cache' ) . ' - VPI',
					'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_VPI, false, '_ori' ),
					'meta'   => [ 'tabindex' => '0' ],
				]
			);
		}

		if ( $this->has_cache_folder( 'avatar' ) ) {
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-menu',
					'id'     => 'litespeed-purge-avatar',
					'title'  => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'Gravatar Cache', 'litespeed-cache' ),
					'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_AVATAR, false, '_ori' ),
					'meta'   => [ 'tabindex' => '0' ],
				]
			);
		}

		do_action( 'litespeed_frontend_shortcut' );
	}

	/**
	 * Hooked to wp_before_admin_bar_render.
	 * Adds links to the admin bar so users can quickly manage/purge.
	 *
	 * @since 1.7.2 Moved from admin_display.cls to gui.cls; Renamed from `add_quick_purge` to `backend_shortcut`.
	 * @access public
	 * @global \WP_Admin_Bar $wp_admin_bar
	 * @return void
	 */
	public function backend_shortcut() {
		global $wp_admin_bar;

		if ( defined( 'LITESPEED_DISABLE_ALL' ) && LITESPEED_DISABLE_ALL ) {
			$wp_admin_bar->add_menu(
				[
					'id'    => 'litespeed-menu',
					'title' => '<span class="ab-icon icon_disabled" title="LiteSpeed Cache"></span>',
					'href'  => 'admin.php?page=litespeed-toolbox#settings-debug',
					'meta'  => [
						'tabindex' => 0,
						'class'    => 'litespeed-top-toolbar',
					],
				]
			);
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-menu',
					'id'     => 'litespeed-enable_all',
					'title'  => esc_html__( 'Enable All Features', 'litespeed-cache' ),
					'href'   => 'admin.php?page=litespeed-toolbox#settings-debug',
					'meta'   => [ 'tabindex' => '0' ],
				]
			);
			return;
		}

		$wp_admin_bar->add_menu(
			[
				'id'    => 'litespeed-menu',
				'title' => '<span class="ab-icon" title="' . esc_attr__( 'LiteSpeed Cache Purge All', 'litespeed-cache' ) . ' - ' . esc_attr__( 'LSCache', 'litespeed-cache' ) . '"></span>',
				'href'  => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_LSCACHE ),
				'meta'  => [
					'tabindex' => 0,
					'class'    => 'litespeed-top-toolbar',
				],
			]
		);

		$wp_admin_bar->add_menu(
			[
				'parent' => 'litespeed-menu',
				'id'     => 'litespeed-bar-manage',
				'title'  => esc_html__( 'Manage', 'litespeed-cache' ),
				'href'   => 'admin.php?page=litespeed',
				'meta'   => [ 'tabindex' => '0' ],
			]
		);

		$wp_admin_bar->add_menu(
			[
				'parent' => 'litespeed-menu',
				'id'     => 'litespeed-bar-setting',
				'title'  => esc_html__( 'Settings', 'litespeed-cache' ),
				'href'   => 'admin.php?page=litespeed-cache',
				'meta'   => [ 'tabindex' => '0' ],
			]
		);

		if ( ! is_network_admin() ) {
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-menu',
					'id'     => 'litespeed-bar-imgoptm',
					'title'  => esc_html__( 'Image Optimization', 'litespeed-cache' ),
					'href'   => 'admin.php?page=litespeed-img_optm',
					'meta'   => [ 'tabindex' => '0' ],
				]
			);
		}

		$wp_admin_bar->add_menu(
			[
				'parent' => 'litespeed-menu',
				'id'     => 'litespeed-purge-all',
				'title'  => esc_html__( 'Purge All', 'litespeed-cache' ),
				'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL ),
				'meta'   => [ 'tabindex' => '0' ],
			]
		);

		$wp_admin_bar->add_menu(
			[
				'parent' => 'litespeed-menu',
				'id'     => 'litespeed-purge-all-lscache',
				'title'  => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'LSCache', 'litespeed-cache' ),
				'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_LSCACHE ),
				'meta'   => [ 'tabindex' => '0' ],
			]
		);

		$wp_admin_bar->add_menu(
			[
				'parent' => 'litespeed-menu',
				'id'     => 'litespeed-purge-cssjs',
				'title'  => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'CSS/JS Cache', 'litespeed-cache' ),
				'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_CSSJS ),
				'meta'   => [ 'tabindex' => '0' ],
			]
		);

		if ( $this->conf( self::O_CDN_CLOUDFLARE ) ) {
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-menu',
					'id'     => 'litespeed-purge-cloudflare',
					'title'  => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'Cloudflare', 'litespeed-cache' ),
					'href'   => Utility::build_url( Router::ACTION_CDN_CLOUDFLARE, CDN\Cloudflare::TYPE_PURGE_ALL ),
					'meta'   => [ 'tabindex' => '0' ],
				]
			);
		}

		if ( defined( 'LSCWP_OBJECT_CACHE' ) ) {
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-menu',
					'id'     => 'litespeed-purge-object',
					'title'  => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'Object Cache', 'litespeed-cache' ),
					'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_OBJECT ),
					'meta'   => [ 'tabindex' => '0' ],
				]
			);
		}

		if ( Router::opcache_enabled() ) {
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-menu',
					'id'     => 'litespeed-purge-opcache',
					'title'  => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'Opcode Cache', 'litespeed-cache' ),
					'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_OPCACHE ),
					'meta'   => [ 'tabindex' => '0' ],
				]
			);
		}

		if ( $this->has_cache_folder( 'ccss' ) ) {
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-menu',
					'id'     => 'litespeed-purge-ccss',
					'title'  => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - CCSS',
					'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_CCSS ),
					'meta'   => [ 'tabindex' => '0' ],
				]
			);
		}

		if ( $this->has_cache_folder( 'ucss' ) ) {
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-menu',
					'id'     => 'litespeed-purge-ucss',
					'title'  => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - UCSS',
					'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_UCSS ),
				]
			);
		}

		if ( $this->has_cache_folder( 'localres' ) ) {
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-menu',
					'id'     => 'litespeed-purge-localres',
					'title'  => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'Localized Resources', 'litespeed-cache' ),
					'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_LOCALRES ),
					'meta'   => [ 'tabindex' => '0' ],
				]
			);
		}

		if ( $this->has_cache_folder( 'lqip' ) ) {
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-menu',
					'id'     => 'litespeed-purge-placeholder',
					'title'  => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'LQIP Cache', 'litespeed-cache' ),
					'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_LQIP ),
					'meta'   => [ 'tabindex' => '0' ],
				]
			);
		}
    
    	if ( $this->has_cache_folder( 'vpi' ) ) {
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-menu',
					'id'     => 'litespeed-purge-vpi',
					'title'  => __( 'Purge All', 'litespeed-cache' ) . ' - VPI',
					'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_VPI ),
					'meta'   => [ 'tabindex' => '0' ],
				]
			);
		}

		if ( $this->has_cache_folder( 'avatar' ) ) {
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-menu',
					'id'     => 'litespeed-purge-avatar',
					'title'  => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'Gravatar Cache', 'litespeed-cache' ),
					'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_AVATAR ),
					'meta'   => [ 'tabindex' => '0' ],
				]
			);
		}

		do_action( 'litespeed_backend_shortcut' );
	}

	/**
	 * Clear unfinished data link/button.
	 *
	 * @since 2.4.2
	 * @access public
	 *
	 * @param int $unfinished_num Number of unfinished images.
	 * @return string HTML for action button.
	 */
	public static function img_optm_clean_up( $unfinished_num ) {
		return sprintf(
			'<a href="%1$s" class="button litespeed-btn-warning" data-balloon-pos="up" aria-label="%2$s"><span class="dashicons dashicons-editor-removeformatting"></span>&nbsp;%3$s</a>',
			esc_url( Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_CLEAN ) ),
			esc_attr__( 'Remove all previous unfinished image optimization requests.', 'litespeed-cache' ),
			esc_html__( 'Clean Up Unfinished Data', 'litespeed-cache' ) . ( $unfinished_num ? ': ' . Admin_Display::print_plural( $unfinished_num, 'image' ) : '' )
		);
	}

	/**
	 * Generate install link.
	 *
	 * @since 2.4.2
	 * @access public
	 *
	 * @param string $title Plugin title.
	 * @param string $name  Slug.
	 * @param string $v     Version (unused, kept for BC).
	 * @return string HTML link.
	 */
	public static function plugin_install_link( $title, $name, $v ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $name ), 'install-plugin_' . $name );

		$action = sprintf(
			'<a href="%1$s" class="install-now" data-slug="%2$s" data-name="%3$s" aria-label="%4$s">%5$s</a>',
			esc_url( $url ),
			esc_attr( $name ),
			esc_attr( $title ),
			esc_attr( sprintf( __( 'Install %s', 'litespeed-cache' ), $title ) ),
			esc_html__( 'Install Now', 'litespeed-cache' )
		);

		return $action;
	}

	/**
	 * Generate upgrade link.
	 *
	 * @since 2.4.2
	 * @access public
	 *
	 * @param string $title Plugin title.
	 * @param string $name  Slug.
	 * @param string $v     Version string.
	 * @return string HTML message with links.
	 */
	public static function plugin_upgrade_link( $title, $name, $v ) {
		$details_url = self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $name . '&section=changelog&TB_iframe=true&width=600&height=800' );
		$file        = $name . '/' . $name . '.php';

		$msg = sprintf(
			/* translators: 1: details URL, 2: class/aria, 3: version, 4: update URL, 5: class/aria */
			__('<a href="%1$s" %2$s>View version %3$s details</a> or <a href="%4$s" %5$s target="_blank">update now</a>.', 'litespeed-cache'),
			esc_url( $details_url ),
			sprintf(
				'class="thickbox open-plugin-details-modal" aria-label="%s"',
				esc_attr(
					sprintf(
						/* translators: 1: plugin title, 2: version */
						__( 'View %1$s version %2$s details', 'litespeed-cache' ),
						$title,
						$v
					)
				)
			),
			esc_html( $v ),
			esc_url( wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $file, 'upgrade-plugin_' . $file ) ),
			sprintf(
				'class="update-link" aria-label="%s"',
				esc_attr(
					sprintf(
						/* translators: %s: plugin title */
						__( 'Update %s now', 'litespeed-cache' ),
						$title
					)
				)
			)
		);

		return $msg;
	}

	/**
	 * Finalize buffer by GUI class.
	 *
	 * @since 1.6
	 * @access public
	 *
	 * @param string $buffer HTML buffer.
	 * @return string Filtered buffer.
	 */
	public function finalize( $buffer ) {
		$buffer = $this->_clean_wrapper( $buffer );

		// Maybe restore doc.ref.
		if ( $this->conf( Base::O_GUEST ) && false !== strpos( $buffer, '<head>' ) && defined( 'LITESPEED_IS_HTML' ) ) {
			$buffer = $this->_enqueue_guest_docref_js( $buffer );
		}

		if ( defined( 'LITESPEED_GUEST' ) && LITESPEED_GUEST && false !== strpos( $buffer, '</body>' ) && defined( 'LITESPEED_IS_HTML' ) ) {
			$buffer = $this->_enqueue_guest_js( $buffer );
		}

		return $buffer;
	}

	/**
	 * Append guest restore doc.ref JS for organic traffic count.
	 *
	 * @since 4.4.6
	 *
	 * @param string $buffer HTML buffer.
	 * @return string Buffer with inline script injected.
	 */
	private function _enqueue_guest_docref_js( $buffer ) {
		$js_con = File::read( LSCWP_DIR . self::LIB_GUEST_DOCREF_JS );
		$buffer = preg_replace( '/<head>/', '<head><script data-no-optimize="1">' . $js_con . '</script>', $buffer, 1 );
		return $buffer;
	}

	/**
	 * Append guest JS to update vary.
	 *
	 * @since 4.0
	 *
	 * @param string $buffer HTML buffer.
	 * @return string Buffer with inline script injected.
	 */
	private function _enqueue_guest_js( $buffer ) {
		$js_con = File::read( LSCWP_DIR . self::LIB_GUEST_JS );
		// Build path for guest endpoint using wp_parse_url for compatibility.
		$guest_update_path = wp_parse_url( LSWCP_PLUGIN_URL . self::PHP_GUEST, PHP_URL_PATH );
		$js_con            = str_replace( 'litespeed_url', esc_url( $guest_update_path ), $js_con );
		$buffer            = preg_replace( '/<\/body>/', '<script data-no-optimize="1">' . $js_con . '</script></body>', $buffer, 1 );
		return $buffer;
	}

	/**
	 * Clean wrapper from buffer.
	 *
	 * @since 1.4
	 * @since 1.6 Converted to private with adding prefix _.
	 * @access private
	 *
	 * @param string $buffer HTML buffer.
	 * @return string Cleaned buffer.
	 */
	private function _clean_wrapper( $buffer ) {
		if ( self::$_clean_counter < 1 ) {
			self::debug2( 'bypassed by no counter' );
			return $buffer;
		}

		self::debug2( 'start cleaning counter ' . self::$_clean_counter );

		for ( $i = 1; $i <= self::$_clean_counter; $i++ ) {
			// If miss beginning.
			$start = strpos( $buffer, self::clean_wrapper_begin( $i ) );
			if ( false === $start ) {
				$buffer = str_replace( self::clean_wrapper_end( $i ), '', $buffer );
				self::debug2( "lost beginning wrapper $i" );
				continue;
			}

			// If miss end.
			$end_wrapper = self::clean_wrapper_end( $i );
			$end         = strpos( $buffer, $end_wrapper );
			if ( false === $end ) {
				$buffer = str_replace( self::clean_wrapper_begin( $i ), '', $buffer );
				self::debug2( "lost ending wrapper $i" );
				continue;
			}

			// Now replace wrapped content.
			$buffer = substr_replace( $buffer, '', $start, $end - $start + strlen( $end_wrapper ) );
			self::debug2( "cleaned wrapper $i" );
		}

		return $buffer;
	}

	/**
	 * Display a to-be-removed HTML wrapper (begin tag).
	 *
	 * @since 1.4
	 * @access public
	 *
	 * @param int|false $counter Optional explicit wrapper id; auto-increment if false.
	 * @return string Wrapper begin HTML comment.
	 */
	public static function clean_wrapper_begin( $counter = false ) {
		if ( false === $counter ) {
			++self::$_clean_counter;
			$counter = self::$_clean_counter;
			self::debug( 'clean wrapper ' . $counter . ' begin' );
		}
		return '<!-- LiteSpeed To Be Removed begin ' . $counter . ' -->';
	}

	/**
	 * Display a to-be-removed HTML wrapper (end tag).
	 *
	 * @since 1.4
	 * @access public
	 *
	 * @param int|false $counter Optional explicit wrapper id; use latest if false.
	 * @return string Wrapper end HTML comment.
	 */
	public static function clean_wrapper_end( $counter = false ) {
		if ( false === $counter ) {
			$counter = self::$_clean_counter;
			self::debug( 'clean wrapper ' . $counter . ' end' );
		}
		return '<!-- LiteSpeed To Be Removed end ' . $counter . ' -->';
	}
}
