<?php
/**
 * The frontend GUI class.
 *
 * @since      	1.3
 * @subpackage 	LiteSpeed/src
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

class GUI extends Base {
	private static $_clean_counter = 0;

	private $_promo_true;

	// [ file_tag => [ days, litespeed_only ], ... ]
	private $_promo_list = array(
		'new_version'	=> array( 7, false ),
		'score'			=> array( 14, false ),
		// 'slack'		=> array( 3, false ),
	);

	const LIB_GUEST_JS = 'assets/js/guest.min.js';
	const LIB_GUEST_DOCREF_JS = 'assets/js/guest.docref.min.js';
	const PHP_GUEST = 'guest.vary.php';

	const TYPE_DISMISS_WHM = 'whm';
	const TYPE_DISMISS_EXPIRESDEFAULT = 'ExpiresDefault';
	const TYPE_DISMISS_PROMO = 'promo';
	const TYPE_DISMISS_PIN = 'pin';

	const WHM_MSG = 'lscwp_whm_install';
	const WHM_MSG_VAL = 'whm_install';

	protected $_summary;

	/**
	 * Instance
	 *
	 * @since  1.3
	 */
	public function __construct() {
		$this->_summary = self::get_summary();

	}

	/**
	 * Frontend Init
	 *
	 * @since  3.0
	 */
	public function init() {
		Debug2::debug2( '[GUI] init' );
		if ( is_admin_bar_showing() && current_user_can( 'manage_options' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue_style' ) );
			add_action( 'admin_bar_menu', array( $this, 'frontend_shortcut' ), 95 );
		}

		/**
		 * Turn on instant click
		 * @since  1.8.2
		 */
		if ( $this->conf( self::O_UTIL_INSTANT_CLICK ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue_style_public' ) );
		}

		// NOTE: this needs to be before optimizer to avoid wrapper being removed
		add_filter( 'litespeed_buffer_finalize', array( $this, 'finalize' ), 8 );
	}

	/**
	 * Get the lscache stats
	 *
	 * @since  3.0
	 */
	public function lscache_stats() {
		return false;

		$stat_titles = array(
			'PUB_CREATES'		=> __( 'Public Caches', 'litespeed-cache' ),
			'PUB_HITS'			=> __( 'Public Cache Hits', 'litespeed-cache' ),
			'PVT_CREATES'		=> __( 'Private Caches', 'litespeed-cache' ),
			'PVT_HITS'			=> __( 'Private Cache Hits', 'litespeed-cache' ),
		);

		// Build the readable format
		$data = array();
		foreach ( $stat_titles as $k => $v ) {
			if ( array_key_exists( $k, $stats ) ) {
				$data[ $v ] = number_format( $stats[ $k ] );
			}
		}

		return $data;
	}

	/**
	* Print a loading message when redirecting CCSS/UCSS page to aviod whiteboard confusion
	*/
	public static function print_loading( $counter, $type ) {
		echo '<div style="font-size: 25px; text-align: center; padding-top: 150px; width: 100%; position: absolute;">';
		echo "<img width='35' src='" . LSWCP_PLUGIN_URL . "assets/img/Litespeed.icon.svg' />   ";
		echo sprintf( __( '%1$s %2$s files left in queue', 'litespeed-cache' ), $counter, $type );
		echo '<p><a href="' . admin_url( 'admin.php?page=litespeed-page_optm' ) . '">' . __( 'Cancel', 'litespeed-cache' ) . '</a></p>';
		echo '</div>';
	}

	/**
	 * Display a pie
	 *
	 * @since 1.6.6
	 */
	public static function pie( $percent, $width = 50, $finished_tick = false, $without_percentage = false, $append_cls = false ) {
		$percentage = '<text x="50%" y="50%">' . $percent . ( $without_percentage ? '' : '%' ) . '</text>';

		if ( $percent == 100 && $finished_tick ) {
			$percentage = '<text x="50%" y="50%" class="litespeed-pie-done">&#x2713</text>';
		}

		return "
		<svg class='litespeed-pie $append_cls' viewbox='0 0 33.83098862 33.83098862' width='$width' height='$width' xmlns='http://www.w3.org/2000/svg'>
			<circle class='litespeed-pie_bg' cx='16.91549431' cy='16.91549431' r='15.91549431' />
			<circle class='litespeed-pie_circle' cx='16.91549431' cy='16.91549431' r='15.91549431' stroke-dasharray='$percent,100' />
			<g class='litespeed-pie_info'>$percentage</g>
		</svg>
		";
	}

	/**
	 * Display a tiny pie with a tooltip
	 *
	 * @since 3.0
	 */
	public static function pie_tiny( $percent, $width = 50, $tooltip = '', $tooltip_pos = 'up', $append_cls = false ) {

		// formula C = 2πR
		$dasharray = 2 * 3.1416 * 9 * ( $percent / 100 );

		return "
		<button type='button' data-balloon-break data-balloon-pos='$tooltip_pos' aria-label='$tooltip' class='litespeed-btn-pie'>
		<svg class='litespeed-pie litespeed-pie-tiny $append_cls' viewbox='0 0 30 30' width='$width' height='$width' xmlns='http://www.w3.org/2000/svg'>
			<circle class='litespeed-pie_bg' cx='15' cy='15' r='9' />
			<circle class='litespeed-pie_circle' cx='15' cy='15' r='9' stroke-dasharray='$dasharray,100' />
			<g class='litespeed-pie_info'><text x='50%' y='50%'>i</text></g>
		</svg>
		</button>
		";
	}

	/**
	 * Get classname of PageSpeed Score
	 *
	 * Scale:
	 * 	90-100 (fast)
	 * 	50-89 (average)
	 * 	0-49 (slow)
	 *
	 * @since  2.9
	 * @access public
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
	 * Dismiss banner
	 *
	 * @since 1.0
	 * @access public
	 */
	public static function dismiss() {
		$_instance = self::cls();
		switch ( Router::verify_type() ) {
			case self::TYPE_DISMISS_WHM :
				self::dismiss_whm();
				break;

			case self::TYPE_DISMISS_EXPIRESDEFAULT :
				self::update_option( Admin_Display::DB_DISMISS_MSG, Admin_Display::RULECONFLICT_DISMISSED );
				break;

			case self::TYPE_DISMISS_PIN:
				admin_display::dismiss_pin();
				break;

			case self::TYPE_DISMISS_PROMO:
				if ( empty( $_GET[ 'promo_tag' ] ) ) {
					break;
				}

				$promo_tag = sanitize_key( $_GET[ 'promo_tag' ] );

				if ( empty( $_instance->_promo_list[ $promo_tag ] ) ) {
					break;
				}

				defined( 'LSCWP_LOG' ) && Debug2::debug( '[GUI] Dismiss promo ' . $promo_tag );

				// Forever dismiss
				if ( ! empty( $_GET[ 'done' ] ) ) {
					$_instance->_summary[ $promo_tag ] = 'done';
				}
				elseif ( ! empty( $_GET[ 'later' ] ) ) {
					// Delay the banner to half year later
					$_instance->_summary[ $promo_tag ] = time() + 86400 * 180;
				}
				else {
					// Update welcome banner to 30 days after
					$_instance->_summary[ $promo_tag ] = time() + 86400 * 30;
				}

				self::save_summary();

				break;

			default:
				break;
		}

		if ( Router::is_ajax() ) {
			// All dismiss actions are considered as ajax call, so just exit
			exit( json_encode( array( 'success' => 1 ) ) );
		}

		// Plain click link, redirect to referral url
		Admin::redirect();
	}

	/**
	 * Check if has rule conflict notice
	 *
	 * @since 1.1.5
	 * @access public
	 * @return boolean
	 */
	public static function has_msg_ruleconflict() {
		$db_dismiss_msg = self::get_option( Admin_Display::DB_DISMISS_MSG );
		if ( ! $db_dismiss_msg ) {
			self::update_option( Admin_Display::DB_DISMISS_MSG, -1 );
		}
		return $db_dismiss_msg == Admin_Display::RULECONFLICT_ON;
	}

	/**
	 * Check if has whm notice
	 *
	 * @since 1.1.1
	 * @access public
	 * @return boolean
	 */
	public static function has_whm_msg() {
		$val = self::get_option( self::WHM_MSG );
		if ( ! $val ) {
			self::dismiss_whm();
			return false;
		}
		return $val == self::WHM_MSG_VAL;
	}

	/**
	 * Delete whm msg tag
	 *
	 * @since 1.1.1
	 * @access public
	 */
	public static function dismiss_whm() {
		self::update_option( self::WHM_MSG, -1 );
	}

	/**
	 * Set current page a litespeed page
	 *
	 * @since  2.9
	 */
	private function _is_litespeed_page() {
		if ( ! empty( $_GET[ 'page' ] ) && in_array( $_GET[ 'page' ],
			array(
				'litespeed-settings',
				'litespeed-dash',
				Admin::PAGE_EDIT_HTACCESS,
				'litespeed-optimization',
				'litespeed-crawler',
				'litespeed-import',
				'litespeed-report',
			) )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Display promo banner
	 *
	 * @since 2.1
	 * @access public
	 */
	public function show_promo( $check_only = false ) {
		$is_litespeed_page = $this->_is_litespeed_page();

		// Bypass showing info banner if disabled all in debug
		if ( defined( 'LITESPEED_DISABLE_ALL' ) ) {
			if ( $is_litespeed_page && ! $check_only ) {
				include_once LSCWP_DIR . "tpl/inc/disabled_all.php";
			}

			return false;
		}

		if ( file_exists( ABSPATH . '.litespeed_no_banner' ) ) {
			defined( 'LSCWP_LOG' ) && Debug2::debug( '[GUI] Bypass banners due to silence file' );
			return false;
		}

		foreach ( $this->_promo_list as $promo_tag => $v ) {
			list( $delay_days, $litespeed_page_only ) = $v;

			if ( $litespeed_page_only && ! $is_litespeed_page ) {
				continue;
			}

			// first time check
			if ( empty( $this->_summary[ $promo_tag ] ) ) {
				$this->_summary[ $promo_tag ] = time() + 86400 * $delay_days;
				self::save_summary();

				continue;
			}

			$promo_timestamp = $this->_summary[ $promo_tag ];

			// was ticked as done
			if ( $promo_timestamp == 'done' ) {
				continue;
			}

			// Not reach the dateline yet
			if ( time() < $promo_timestamp ) {
				continue;
			}

			// try to load, if can pass, will set $this->_promo_true = true
			$this->_promo_true = false;
			include LSCWP_DIR . "tpl/banner/$promo_tag.php";

			// If not defined, means it didn't pass the display workflow in tpl.
			if ( ! $this->_promo_true ) {
				continue;
			}

			if ( $check_only ) {
				return $promo_tag;
			}

			defined( 'LSCWP_LOG' ) && Debug2::debug( '[GUI] Show promo ' . $promo_tag );

			// Only contain one
			break;

		}

		return false;
	}

	/**
	 * Load frontend public script
	 *
	 * @since  1.8.2
	 * @access public
	 */
	public function frontend_enqueue_style_public() {
		wp_enqueue_script( Core::PLUGIN_NAME, LSWCP_PLUGIN_URL . 'assets/js/instant_click.min.js', array(), Core::VER, true );
	}

	/**
	 * Load frontend menu shortcut
	 *
	 * @since  1.3
	 * @access public
	 */
	public function frontend_enqueue_style() {
		wp_enqueue_style( Core::PLUGIN_NAME, LSWCP_PLUGIN_URL . 'assets/css/litespeed.css', array(), Core::VER, 'all' );
	}

	/**
	 * Load frontend menu shortcut
	 *
	 * @since  1.3
	 * @access public
	 */
	public function frontend_shortcut() {
		global $wp_admin_bar;

		$wp_admin_bar->add_menu( array(
			'id'	=> 'litespeed-menu',
			'title'	=> '<span class="ab-icon"></span>',
			'href'	=> get_admin_url( null, 'admin.php?page=litespeed' ),
			'meta'	=> array( 'tabindex' => 0, 'class' => 'litespeed-top-toolbar' ),
		) );

		$wp_admin_bar->add_menu( array(
			'parent'	=> 'litespeed-menu',
			'id'		=> 'litespeed-purge-single',
			'title'		=> __( 'Purge this page', 'litespeed-cache' ) . ' - LSCache',
			'href'		=> Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_FRONT, false, true ),
			'meta'		=> array( 'tabindex' => '0' ),
		) );

		if ( $this->has_cache_folder( 'ucss' ) ) {
			$possible_url_tag = CSS::get_url_tag();
			$append_arr = array();
			if ( $possible_url_tag ) {
				$append_arr[ 'url_tag' ] = $possible_url_tag;
			}

			$wp_admin_bar->add_menu( array(
				'parent'	=> 'litespeed-menu',
				'id'		=> 'litespeed-purge-single-ucss',
				'title'		=> __( 'Purge this page', 'litespeed-cache' ) . ' - UCSS',
				'href'		=> Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_UCSS, false, true, $append_arr ),
				'meta'		=> array( 'tabindex' => '0' ),
			) );
		}

		$wp_admin_bar->add_menu( array(
			'parent'	=> 'litespeed-menu',
			'id'		=> 'litespeed-single-action',
			'title'		=> __( 'Mark this page as ', 'litespeed-cache' ),
			'meta'		=> array( 'tabindex' => '0' ),
		) );

		if ( ! empty( $_SERVER[ 'REQUEST_URI' ] ) ) {
			$append_arr = array(
				Conf::TYPE_SET . '[' . self::O_CACHE_FORCE_URI . '][]' => $_SERVER[ 'REQUEST_URI' ] . '$',
				'redirect'	=> $_SERVER[ 'REQUEST_URI' ],
			);
			$wp_admin_bar->add_menu( array(
				'parent'	=> 'litespeed-single-action',
				'id'		=> 'litespeed-single-forced_cache',
				'title'		=> __( 'Forced cacheable', 'litespeed-cache' ),
				'href'		=> Utility::build_url( Router::ACTION_CONF, Conf::TYPE_SET, false, true, $append_arr ),
			) );

			$append_arr = array(
				Conf::TYPE_SET . '[' . self::O_CACHE_EXC . '][]' => $_SERVER[ 'REQUEST_URI' ] . '$',
				'redirect'	=> $_SERVER[ 'REQUEST_URI' ],
			);
			$wp_admin_bar->add_menu( array(
				'parent'	=> 'litespeed-single-action',
				'id'		=> 'litespeed-single-noncache',
				'title'		=> __( 'Non cacheable', 'litespeed-cache' ),
				'href'		=> Utility::build_url( Router::ACTION_CONF, Conf::TYPE_SET, false, true, $append_arr ),
			) );

			$append_arr = array(
				Conf::TYPE_SET . '[' . self::O_CACHE_PRIV_URI . '][]' => $_SERVER[ 'REQUEST_URI' ] . '$',
				'redirect'	=> $_SERVER[ 'REQUEST_URI' ],
			);
			$wp_admin_bar->add_menu( array(
				'parent'	=> 'litespeed-single-action',
				'id'		=> 'litespeed-single-private',
				'title'		=> __( 'Private cache', 'litespeed-cache' ),
				'href'		=> Utility::build_url( Router::ACTION_CONF, Conf::TYPE_SET, false, true, $append_arr ),
			) );

			$append_arr = array(
				Conf::TYPE_SET . '[' . self::O_OPTM_EXC . '][]' => $_SERVER[ 'REQUEST_URI' ] . '$',
				'redirect'	=> $_SERVER[ 'REQUEST_URI' ],
			);
			$wp_admin_bar->add_menu( array(
				'parent'	=> 'litespeed-single-action',
				'id'		=> 'litespeed-single-nonoptimize',
				'title'		=> __( 'No optimization', 'litespeed-cache' ),
				'href'		=> Utility::build_url( Router::ACTION_CONF, Conf::TYPE_SET, false, true, $append_arr ),
			) );
		}

		$wp_admin_bar->add_menu( array(
			'parent'	=> 'litespeed-single-action',
			'id'		=> 'litespeed-single-more',
			'title'		=> __( 'More settings', 'litespeed-cache' ),
			'href'		=> get_admin_url( null, 'admin.php?page=litespeed-cache' ),
		) );

		$wp_admin_bar->add_menu( array(
			'parent'	=> 'litespeed-menu',
			'id'		=> 'litespeed-purge-all',
			'title'		=> __( 'Purge All', 'litespeed-cache' ),
			'href'		=> Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL, false, '_ori' ),
			'meta'		=> array( 'tabindex' => '0' ),
		) );

		$wp_admin_bar->add_menu( array(
			'parent'	=> 'litespeed-menu',
			'id'		=> 'litespeed-purge-all-lscache',
			'title'		=> __( 'Purge All', 'litespeed-cache' ) . ' - ' . __( 'LSCache', 'litespeed-cache' ),
			'href'		=> Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_LSCACHE, false, '_ori' ),
			'meta'		=> array( 'tabindex' => '0' ),
		) );

		$wp_admin_bar->add_menu( array(
			'parent'	=> 'litespeed-menu',
			'id'		=> 'litespeed-purge-cssjs',
			'title'		=> __( 'Purge All', 'litespeed-cache' ) . ' - ' . __( 'CSS/JS Cache', 'litespeed-cache' ),
			'href'		=> Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_CSSJS, false, '_ori' ),
			'meta'		=> array( 'tabindex' => '0' ),
		) );

		if ( defined( 'LSCWP_OBJECT_CACHE' ) ) {
			$wp_admin_bar->add_menu( array(
				'parent'	=> 'litespeed-menu',
				'id'		=> 'litespeed-purge-object',
				'title'		=> __( 'Purge All', 'litespeed-cache' ) . ' - ' . __( 'Object Cache', 'litespeed-cache' ),
				'href'		=> Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_OBJECT, false, '_ori' ),
				'meta'		=> array( 'tabindex' => '0' ),
			) );
		}

		if ( Router::opcache_enabled() ) {
			$wp_admin_bar->add_menu( array(
				'parent'	=> 'litespeed-menu',
				'id'		=> 'litespeed-purge-opcache',
				'title'		=> __( 'Purge All', 'litespeed-cache' ) . ' - ' . __( 'Opcode Cache', 'litespeed-cache' ),
				'href'		=> Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_OPCACHE, false, '_ori' ),
				'meta'		=> array( 'tabindex' => '0' ),
			) );
		}

		if ( $this->has_cache_folder( 'ccss' ) ) {
			$wp_admin_bar->add_menu( array(
				'parent'	=> 'litespeed-menu',
				'id'		=> 'litespeed-purge-ccss',
				'title'		=> __( 'Purge All', 'litespeed-cache' ) . ' - CCSS',
				'href'		=> Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_CCSS, false, '_ori' ),
				'meta'		=> array( 'tabindex' => '0' ),
			) );
		}

		if ( $this->has_cache_folder( 'ucss' ) ) {
			$wp_admin_bar->add_menu( array(
				'parent'	=> 'litespeed-menu',
				'id'		=> 'litespeed-purge-ucss',
				'title'		=> __( 'Purge All', 'litespeed-cache' ) . ' - UCSS',
				'href'		=> Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_UCSS, false, '_ori' ),
			) );
		}

		if ( $this->has_cache_folder( 'localres' ) ) {
			$wp_admin_bar->add_menu( array(
				'parent'	=> 'litespeed-menu',
				'id'		=> 'litespeed-purge-localres',
				'title'		=> __( 'Purge All', 'litespeed-cache' ) . ' - ' . __( 'Localized Resources', 'litespeed-cache' ),
				'href'		=> Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_LOCALRES, false, '_ori' ),
				'meta'		=> array( 'tabindex' => '0' ),
			) );
		}

		if ( $this->has_cache_folder( 'lqip' ) ) {
			$wp_admin_bar->add_menu( array(
				'parent'	=> 'litespeed-menu',
				'id'		=> 'litespeed-purge-placeholder',
				'title'		=> __( 'Purge All', 'litespeed-cache' ) . ' - ' . __( 'LQIP Cache', 'litespeed-cache' ),
				'href'		=> Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_LQIP, false, '_ori' ),
				'meta'		=> array( 'tabindex' => '0' ),
			) );
		}

		if ( $this->has_cache_folder( 'avatar' ) ) {
			$wp_admin_bar->add_menu( array(
				'parent'	=> 'litespeed-menu',
				'id'		=> 'litespeed-purge-avatar',
				'title'		=> __( 'Purge All', 'litespeed-cache' ) . ' - ' . __( 'Gravatar Cache', 'litespeed-cache' ),
				'href'		=> Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_AVATAR, false, '_ori' ),
				'meta'		=> array( 'tabindex' => '0' ),
			) );
		}

		do_action( 'litespeed_frontend_shortcut' );

	}

	/**
	 * Hooked to wp_before_admin_bar_render.
	 * Adds a link to the admin bar so users can quickly purge all.
	 *
	 * @access public
	 * @global WP_Admin_Bar $wp_admin_bar
	 * @since 1.7.2 Moved from admin_display.cls to gui.cls; Renamed from `add_quick_purge` to `backend_shortcut`
	 */
	public function backend_shortcut() {
		global $wp_admin_bar;

		// if ( defined( 'LITESPEED_ON' ) ) {
		$wp_admin_bar->add_menu( array(
			'id'    => 'litespeed-menu',
			'title' => '<span class="ab-icon" title="' . __( 'LiteSpeed Cache Purge All', 'litespeed-cache' ) . ' - ' . __( 'LSCache', 'litespeed-cache' ) . '"></span>',
			'href'		=> Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_LSCACHE ),
			'meta'  => array( 'tabindex' => 0, 'class' => 'litespeed-top-toolbar' ),
		) );
		// }
		// else {
		// 	$wp_admin_bar->add_menu( array(
		// 		'id'    => 'litespeed-menu',
		// 		'title' => '<span class="ab-icon" title="' . __( 'LiteSpeed Cache', 'litespeed-cache' ) . '"></span>',
		// 		'meta'  => array( 'tabindex' => 0, 'class' => 'litespeed-top-toolbar' ),
		// 	) );
		// }

		$wp_admin_bar->add_menu( array(
			'parent'	=> 'litespeed-menu',
			'id'		=> 'litespeed-bar-manage',
			'title'		=> __( 'Manage', 'litespeed-cache' ),
			'href'		=> 'admin.php?page=litespeed',
			'meta'		=> array( 'tabindex' => '0' ),
		) );

		$wp_admin_bar->add_menu( array(
			'parent'	=> 'litespeed-menu',
			'id'		=> 'litespeed-bar-setting',
			'title'		=> __( 'Settings', 'litespeed-cache' ),
			'href'		=> 'admin.php?page=litespeed-cache',
			'meta'		=> array( 'tabindex' => '0' ),
		) );

		if ( ! is_network_admin() ) {
			$wp_admin_bar->add_menu( array(
				'parent'	=> 'litespeed-menu',
				'id'		=> 'litespeed-bar-imgoptm',
				'title'		=> __( 'Image Optimization', 'litespeed-cache' ),
				'href'		=> 'admin.php?page=litespeed-img_optm',
				'meta'		=> array( 'tabindex' => '0' ),
			) );
		}

		$wp_admin_bar->add_menu( array(
			'parent'	=> 'litespeed-menu',
			'id'		=> 'litespeed-purge-all',
			'title'		=> __( 'Purge All', 'litespeed-cache' ),
			'href'		=> Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL ),
			'meta'		=> array( 'tabindex' => '0' ),
		) );

		$wp_admin_bar->add_menu( array(
			'parent'	=> 'litespeed-menu',
			'id'		=> 'litespeed-purge-all-lscache',
			'title'		=> __( 'Purge All', 'litespeed-cache' ) . ' - ' . __( 'LSCache', 'litespeed-cache' ),
			'href'		=> Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_LSCACHE ),
			'meta'		=> array( 'tabindex' => '0' ),
		) );

		$wp_admin_bar->add_menu( array(
			'parent'	=> 'litespeed-menu',
			'id'		=> 'litespeed-purge-cssjs',
			'title'		=> __( 'Purge All', 'litespeed-cache' ) . ' - ' . __( 'CSS/JS Cache', 'litespeed-cache' ),
			'href'		=> Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_CSSJS ),
			'meta'		=> array( 'tabindex' => '0' ),
		) );

		if ( $this->conf( self::O_CDN_CLOUDFLARE ) ) {
			$wp_admin_bar->add_menu( array(
				'parent'	=> 'litespeed-menu',
				'id'		=> 'litespeed-purge-cloudflare',
				'title'		=> __( 'Purge All', 'litespeed-cache' ) . ' - ' . __( 'Cloudflare', 'litespeed-cache' ),
				'href'		=> Utility::build_url( Router::ACTION_CDN_CLOUDFLARE, CDN\Cloudflare::TYPE_PURGE_ALL ),
				'meta'		=> array( 'tabindex' => '0' ),
			) );
		}

		if ( defined( 'LSCWP_OBJECT_CACHE' ) ) {
			$wp_admin_bar->add_menu( array(
				'parent'	=> 'litespeed-menu',
				'id'		=> 'litespeed-purge-object',
				'title'		=> __( 'Purge All', 'litespeed-cache' ) . ' - ' . __( 'Object Cache', 'litespeed-cache' ),
				'href'		=> Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_OBJECT ),
				'meta'		=> array( 'tabindex' => '0' ),
			) );
		}

		if ( Router::opcache_enabled() ) {
			$wp_admin_bar->add_menu( array(
				'parent'	=> 'litespeed-menu',
				'id'		=> 'litespeed-purge-opcache',
				'title'		=> __( 'Purge All', 'litespeed-cache' ) . ' - ' . __( 'Opcode Cache', 'litespeed-cache' ),
				'href'		=> Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_OPCACHE ),
				'meta'		=> array( 'tabindex' => '0' ),
			) );
		}

		if ( $this->has_cache_folder( 'ccss' ) ) {
			$wp_admin_bar->add_menu( array(
				'parent'	=> 'litespeed-menu',
				'id'		=> 'litespeed-purge-ccss',
				'title'		=> __( 'Purge All', 'litespeed-cache' ) . ' - CCSS',
				'href'		=> Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_CCSS ),
				'meta'		=> array( 'tabindex' => '0' ),
			) );
		}

		if ( $this->has_cache_folder( 'ucss' ) ) {
			$wp_admin_bar->add_menu( array(
				'parent'	=> 'litespeed-menu',
				'id'		=> 'litespeed-purge-ucss',
				'title'		=> __( 'Purge All', 'litespeed-cache' ) . ' - UCSS',
				'href'		=> Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_UCSS ),
			) );
		}

		if ( $this->has_cache_folder( 'localres' ) ) {
			$wp_admin_bar->add_menu( array(
				'parent'	=> 'litespeed-menu',
				'id'		=> 'litespeed-purge-localres',
				'title'		=> __( 'Purge All', 'litespeed-cache' ) . ' - ' . __( 'Localized Resources', 'litespeed-cache' ),
				'href'		=> Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_LOCALRES ),
				'meta'		=> array( 'tabindex' => '0' ),
			) );
		}

		if ( $this->has_cache_folder( 'lqip' ) ) {
			$wp_admin_bar->add_menu( array(
				'parent'	=> 'litespeed-menu',
				'id'		=> 'litespeed-purge-placeholder',
				'title'		=> __( 'Purge All', 'litespeed-cache' ) . ' - ' . __( 'LQIP Cache', 'litespeed-cache' ),
				'href'		=> Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_LQIP ),
				'meta'		=> array( 'tabindex' => '0' ),
			) );
		}

		if ( $this->has_cache_folder( 'avatar' ) ) {
			$wp_admin_bar->add_menu( array(
				'parent'	=> 'litespeed-menu',
				'id'		=> 'litespeed-purge-avatar',
				'title'		=> __( 'Purge All', 'litespeed-cache' ) . ' - ' . __( 'Gravatar Cache', 'litespeed-cache' ),
				'href'		=> Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_AVATAR ),
				'meta'		=> array( 'tabindex' => '0' ),
			) );
		}

		do_action( 'litespeed_backend_shortcut' );
	}

	/**
	 * Clear unfinished data
	 *
	 * @since  2.4.2
	 * @access public
	 */
	public static function img_optm_clean_up( $unfinished_num ) {
		return sprintf(
			'<a href="%1$s" class="button litespeed-btn-warning" data-balloon-pos="up" aria-label="%2$s"><span class="dashicons dashicons-editor-removeformatting"></span>&nbsp;%3$s</a>',
			Utility::build_url( Router::ACTION_IMG_OPTM, Img_Optm::TYPE_CLEAN ),
			__( 'Remove all previous unfinished image optimization requests.', 'litespeed-cache' ),
			__( 'Clean Up Unfinished Data', 'litespeed-cache' ) . ( $unfinished_num ? ': ' . Admin_Display::print_plural( $unfinished_num, 'image' ) : '')
		);
	}

	/**
	 * Generate install link
	 *
	 * @since  2.4.2
	 * @access public
	 */
	public static function plugin_install_link( $title, $name, $v ) {
		$url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=' . $name ), 'install-plugin_' . $name );

		$action = sprintf(
			'<a href="%1$s" class="install-now" data-slug="%2$s" data-name="%3$s" aria-label="%4$s">%5$s</a>',
			esc_url( $url ),
			esc_attr( $name ),
			esc_attr( $title ),
			esc_attr( sprintf( __( 'Install %s', 'litespeed-cache' ), $title ) ),
			__( 'Install Now', 'litespeed-cache' )
		);

		return $action;

		// $msg .= " <a href='$upgrade_link' class='litespeed-btn-success' target='_blank'>" . __( 'Click here to upgrade', 'litespeed-cache' ) . '</a>';

	}

	/**
	 * Generate upgrade link
	 *
	 * @since  2.4.2
	 * @access public
	 */
	public static function plugin_upgrade_link( $title, $name, $v ) {
		$details_url = self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $name . '&section=changelog&TB_iframe=true&width=600&height=800' );
		$file = $name . '/' . $name . '.php';

		$msg = sprintf( __( '<a href="%1$s" %2$s>View version %3$s details</a> or <a href="%4$s" %5$s target="_blank">update now</a>.', 'litespeed-cache' ),
			esc_url( $details_url ),
			sprintf( 'class="thickbox open-plugin-details-modal" aria-label="%s"',
				esc_attr( sprintf( __( 'View %1$s version %2$s details', 'litespeed-cache' ), $title, $v ) )
			),
			$v,
			wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $file, 'upgrade-plugin_' . $file ),
			sprintf( 'class="update-link" aria-label="%s"',
				esc_attr( sprintf( __( 'Update %s now', 'litespeed-cache' ), $title ) )
			)
		);

		return $msg;
	}

	/**
	 * Finalize buffer by GUI class
	 *
	 * @since  1.6
	 * @access public
	 */
	public function finalize( $buffer ) {
		$buffer = $this->_clean_wrapper( $buffer );

		// Maybe restore doc.ref
		if ( $this->conf( Base::O_GUEST ) && strpos( $buffer, '<head>' ) !== false && defined( 'LITESPEED_IS_HTML' ) ) {
			$buffer = $this->_enqueue_guest_docref_js( $buffer );
		}

		if ( defined( 'LITESPEED_GUEST' ) && LITESPEED_GUEST && strpos( $buffer, '</body>' ) !== false && defined( 'LITESPEED_IS_HTML' ) ) {
			$buffer = $this->_enqueue_guest_js( $buffer );
		}

		return $buffer;
	}

	/**
	 * Append guest restore doc.ref JS for organic traffic count
	 *
	 * @since  4.4.6
	 */
	private function _enqueue_guest_docref_js( $buffer ) {
		$js_con = File::read( LSCWP_DIR . self::LIB_GUEST_DOCREF_JS );
		$buffer = preg_replace( '/<head>/', '<head><script data-no-optimize="1">' . $js_con . '</script>', $buffer, 1 );
		return $buffer;
	}

	/**
	 * Append guest JS to update vary
	 *
	 * @since  4.0
	 */
	private function _enqueue_guest_js( $buffer ) {
		$js_con = File::read( LSCWP_DIR . self::LIB_GUEST_JS );
		// $guest_update_url = add_query_arg( 'litespeed_guest', 1, home_url( '/' ) );
		$guest_update_url = parse_url( LSWCP_PLUGIN_URL . self::PHP_GUEST, PHP_URL_PATH );
		$js_con = str_replace( 'litespeed_url', esc_url( $guest_update_url ), $js_con );
		$buffer = preg_replace( '/<\/body>/', '<script data-no-optimize="1">' . $js_con . '</script></body>', $buffer, 1 );
		return $buffer;
	}

	/**
	 * Clean wrapper from buffer
	 *
	 * @since  1.4
	 * @since  1.6 converted to private with adding prefix _
	 * @access private
	 */
	private function _clean_wrapper( $buffer ) {
		if ( self::$_clean_counter < 1 ) {
			Debug2::debug2( "GUI bypassed by no counter" );
			return $buffer;
		}

		Debug2::debug2( "GUI start cleaning counter " . self::$_clean_counter );

		for ( $i = 1; $i <= self::$_clean_counter; $i ++ ) {
			// If miss beginning
			$start = strpos( $buffer, self::clean_wrapper_begin( $i ) );
			if ( $start === false ) {
				$buffer = str_replace( self::clean_wrapper_end( $i ), '', $buffer );
				Debug2::debug2( "GUI lost beginning wrapper $i" );
				continue;
			}

			// If miss end
			$end_wrapper = self::clean_wrapper_end( $i );
			$end = strpos( $buffer, $end_wrapper );
			if ( $end === false ) {
				$buffer = str_replace( self::clean_wrapper_begin( $i ), '', $buffer );
				Debug2::debug2( "GUI lost ending wrapper $i" );
				continue;
			}

			// Now replace wrapped content
			$buffer = substr_replace( $buffer, '', $start, $end - $start + strlen( $end_wrapper ) );
			Debug2::debug2( "GUI cleaned wrapper $i" );
		}

		return $buffer;
	}

	/**
	 * Display a to-be-removed html wrapper
	 *
	 * @since  1.4
	 * @access public
	 */
	public static function clean_wrapper_begin( $counter = false ) {
		if ( $counter === false ) {
			self::$_clean_counter ++;
			$counter = self::$_clean_counter;
			Debug2::debug( "GUI clean wrapper $counter begin" );
		}
		return '<!-- LiteSpeed To Be Removed begin ' . $counter . ' -->';
	}

	/**
	 * Display a to-be-removed html wrapper
	 *
	 * @since  1.4
	 * @access public
	 */
	public static function clean_wrapper_end( $counter = false ) {
		if ( $counter === false ) {
			$counter = self::$_clean_counter;
			Debug2::debug( "GUI clean wrapper $counter end" );
		}
		return '<!-- LiteSpeed To Be Removed end ' . $counter . ' -->';
	}

}


