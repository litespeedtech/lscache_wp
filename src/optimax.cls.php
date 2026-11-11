<?php
/**
 * The Optimax class for full page optimization.
 *
 * Sends entire page (HTML/JS/CSS/Images) to cloud for optimization.
 *
 * @since   8.0
 * @package LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Optimax - Full Page Optimization class.
 *
 * @since 8.0
 */
class Optimax extends Cloud_Queue_Svc {

	const LOG_TAG = '🚀';

	/** WP-Cron hook of the pull: one single event, at the service's try_later deadline. */
	const PULL_HOOK = 'litespeed_task_optimax_pull';

	const TYPE_REBUILD  = 'rebuild';
	const TYPE_RUN_CRON = 'run_cron';

	/** Failed sends after which a kept item is no longer retried automatically. */
	const MAX_TRIES = 5;

	/** Img_Optm `_summary` key counting the images OptimaX builds saved; Destroy clears it, Soft Reset keeps it. */
	const SUMMARY_OX_IMGS = 'ox_imgs';

	/** Settings OptimaX turns on: Cache, Browser Cache (bundle/fonts lifetime), Add Missing Sizes. Mobile Cache stays the owner's choice. */
	const REQUIRED = [ self::O_CACHE, self::O_CACHE_BROWSER, self::O_MEDIA_ADD_MISSING_SIZES ];

	/**
	 * Whether this run may only collect finished builds: sent as `pull_only`, so the service never starts one.
	 *
	 * @var bool
	 */
	private $_pull_only = false;

	/**
	 * Whether this run stored a build (_save_result()), for the push that follows a pull.
	 *
	 * @var bool
	 */
	private $_stored = false;

	/**
	 * The page whose other device this push run may still send (_send_more_after()), or null.
	 *
	 * @var string|null
	 */
	private $_pair_tag = null;

	/**
	 * The custom vary cookies Vary last finalized (`litespeed_vary_curr_cookies`).
	 *
	 * @var array
	 */
	private static $_vary_cookies = [];

	/**
	 * Outcome and figures of this request's page-change check, for serve() and the debug log.
	 *
	 * @var array
	 */
	private $_sync_stats = [];

	/**
	 * This request's local stylesheets as a sync may show them (Optimax_Sync::sheet_changes(), set by _same_sheets()).
	 *
	 * @var array
	 */
	private $_sheets = [];

	/**
	 * Init.
	 *
	 * @since 8.0
	 */
	public function __construct() {
		$this->_summary = self::get_summary();
	}

	/**
	 * Hook OptimaX into the rest of the plugin, from Core::init(). Static callbacks: the instance is only built when needed.
	 *
	 * @since 8.0
	 * @return void
	 */
	public static function hooks() {
		add_filter( 'litespeed_purgeable_folder', [ __CLASS__, 'purgeable_folder' ], 10, 2 );
		add_action( 'litespeed_frontend_shortcut', [ __CLASS__, 'purge_menu' ] );
		add_action( 'litespeed_backend_shortcut', [ __CLASS__, 'purge_menu' ] );
		add_action( 'update_option_' . Conf::name( self::O_IMG_OPTM_WEBP ), [ __CLASS__, 'nextgen_changed' ], 10, 2 );
		add_filter( 'litespeed_vary_curr_cookies', [ __CLASS__, 'vary_cookies' ], PHP_INT_MAX );

		// A qcbot request is served the build unchecked, so it must not enter the public cache; decided before the cache headers are sent.
		if ( Conf::cls()->conf( self::O_OPTIMAX ) && ! ( defined( 'LITESPEED_DISABLE_ALL' ) && LITESPEED_DISABLE_ALL ) ) {
			add_action( 'litespeed_control_finalize', [ static::cls(), 'qcbot_nocache' ] );
		}
	}

	/**
	 * `litespeed_purgeable_folder`: OptimaX saves the UCSS, CCSS and VPI of the pages it builds, so their purges are hidden while it is on.
	 *
	 * @since 8.0
	 * @param bool   $has  Whether the folder exists.
	 * @param string $type Cache folder.
	 * @return bool
	 */
	public static function purgeable_folder( $has, $type ) {
		return in_array( $type, [ 'ucss', 'ccss', 'vpi' ], true ) && Conf::cls()->conf( self::O_OPTIMAX ) ? false : $has;
	}

	/**
	 * Admin bar "Purge All - OptimaX", frontend and backend, while there are builds.
	 *
	 * @since 8.0
	 * @return void
	 */
	public static function purge_menu() {
		global $wp_admin_bar;

		if ( GUI::cls()->has_cache_folder( 'optimax' ) ) {
			$wp_admin_bar->add_menu(
				[
					'parent' => 'litespeed-menu',
					'id'     => 'litespeed-purge-optimax',
					'title'  => __( 'Purge All', 'litespeed-cache' ) . ' - OptimaX',
					'href'   => Utility::build_url( Router::ACTION_PURGE, Purge::TYPE_PURGE_ALL_OPTIMAX, false, doing_action( 'litespeed_frontend_shortcut' ) ? '_ori' : null ),
					'meta'   => [ 'tabindex' => '0' ],
				]
			);
		}
	}

	/**
	 * Next-Gen Image Format saved: OptimaX builds and their CSS link the old format's images.
	 *
	 * @since 8.0
	 * @param mixed $old Old value.
	 * @param mixed $val New value.
	 * @return void
	 */
	public static function nextgen_changed( $old, $val ) {
		if ( (int) $old !== (int) $val ) {
			Optimax_Pages::cls()->all_designs_changed( 'next-gen image format changed' );
		}
	}

	/**
	 * `litespeed_vary_curr_cookies`, last: keep this request's custom vary cookies for _page_version().
	 *
	 * @since 8.0
	 * @param array $cookies Vary cookies.
	 * @return array Unchanged.
	 */
	public static function vary_cookies( $cookies ) {
		self::$_vary_cookies = (array) $cookies;
		return $cookies;
	}

	/**
	 * Add the pull cron while a build is outstanding (Task::init()).
	 *
	 * @since 8.0
	 * @param array $trigger Task trigger: `name`, `hook`.
	 * @return void
	 */
	public static function hook_pull( $trigger ) {
		// On outstanding work, not on the cron switch; its own single event at the try_later deadline, not the general interval.
		if ( Conf::cls()->conf( self::O_OPTIMAX ) && self::need_pull() ) {
			self::_schedule_pull();
			add_action( $trigger['name'], $trigger['hook'] );
		}
	}

	/**
	 * The note under a setting OptimaX turns on (Admin_Display::_check_overwritten()).
	 *
	 * @since 8.0
	 * @return void
	 */
	public static function forced_note() {
		/* translators: 1: ON, 2: feature name */
		echo '<font class="litespeed-warning">' . wp_kses_post( '⚠️ ' . sprintf( __( 'This setting is %1$s because %2$s needs it!', 'litespeed-cache' ), '<code>' . esc_html__( 'ON', 'litespeed-cache' ) . '</code>', esc_html( Lang::title( self::O_OPTIMAX ) ) ) ) . '</font>';
	}

	/**
	 * Print a queue row's vary group: ` (Vary Group: 📱 <next-gen>)`, nothing when both are empty.
	 *
	 * @since 8.0
	 * @param bool   $has_mobile Mobile row.
	 * @param string $next_gen   Next-gen format HTML, or '' for none.
	 * @return void
	 */
	public static function vary_group( $has_mobile, $next_gen ) {
		if ( $has_mobile || '' !== $next_gen ) {
			echo ' (' . esc_html__( 'Vary Group', 'litespeed-cache' ) . ':'
				. ( $has_mobile ? ' <span data-balloon-pos="up" aria-label="mobile">📱</span>' : '' )
				. ( '' !== $next_gen ? ' ' . wp_kses_post( $next_gen ) : '' )
				. ')';
		}
	}

	/**
	 * Whether a build is outstanding: the service answered try_later (the deadline is stamped) and the queue has rows.
	 *
	 * @since 8.0
	 * @return bool
	 */
	public static function need_pull() {
		$ox = static::cls();
		// The summary check first: it is in memory, the queue is a file.
		return ! empty( self::get_summary( $ox->_next_run_after_key() ) ) && $ox->load_queue( 'optimax' );
	}

	/**
	 * Cron entry point for collecting finished builds; registered on outstanding work (need_pull()), whatever the cron switch.
	 *
	 * @since 8.0
	 * @return void
	 */
	public static function cron_pull() {
		$ox = static::cls();
		if ( ! $ox->conf( self::O_OPTIMAX ) ) {
			return;
		}

		self::debug( 'OX CRON PULL started' );

		// On the singleton cron() reuses, so _build_payload() sees it; later sends in this request are not pulls.
		$ox->_pull_only = true;
		static::cron();
		$ox->_pull_only = false;

		// The run may have set a new deadline: collect again at it.
		self::need_pull() && self::_schedule_pull();
	}

	/**
	 * Schedule the pull as a single WP-Cron event at the service's try_later deadline; only called while a build is outstanding (need_pull()).
	 *
	 * @since 8.0
	 * @return void
	 */
	private static function _schedule_pull() {
		// Deadline passed or unknown: pull in 5 s.
		$due    = (int) self::get_summary( static::cls()->_next_run_after_key() );
		$run_at = $due > time() ? $due : time() + 5;

		// A single event that fires no later than the deadline (give or take 5 s) stays; a recurring one left by an older version is replaced.
		$event = wp_get_scheduled_event( self::PULL_HOOK );
		if ( $event && empty( $event->schedule ) && $event->timestamp <= $run_at + 5 ) {
			return;
		}
		if ( $event ) {
			wp_clear_scheduled_hook( self::PULL_HOOK );
		}

		wp_schedule_single_event( $run_at, self::PULL_HOOK );
		self::debug( 'OX CRON PULL scheduled at ' . gmdate( 'Y-m-d H:i:s', $run_at ) );
	}

	/**
	 * Cron entry point for submitting queued URLs: one page per tick (both devices); the try_later deadline re-arms it.
	 *
	 * @since 8.0
	 * @return void
	 */
	public static function cron_push() {
		// The trigger is keyed on the cron switch alone: with OptimaX off, a leftover queue would buy builds serve() never serves.
		if ( static::cls()->conf( self::O_OPTIMAX ) ) {
			self::debug( 'OX CRON PUSH started' );
			static::cron();
		}
	}

	/**
	 * Admin action handler: rebuild the pages whose design changed, clear the queue, run it, run its cron.
	 *
	 * @since 8.0
	 * @return string|void Notice to show.
	 */
	public function handler() {
		$type  = Router::verify_type();
		$pages = $this->cls( 'Optimax_Pages' );
		// Back to the bare page: the default redirect keeps every query arg.
		$back = admin_url( 'admin.php?page=litespeed-optimax' );

		switch ( $type ) {
			case self::TYPE_REBUILD:
				$n = $pages->rebuild_all();
				/* translators: %d: number of page versions queued */
				Admin_Display::success( sprintf( _n( '%d page version queued for a new OptimaX build.', '%d page versions queued for a new OptimaX build.', $n, 'litespeed-cache' ), $n ) );
				break;

			case self::TYPE_RUN_CRON:
				// The "Run Cron now" button: push, then pull, as WP-Cron would. A task whose feature is off has no callback.
				foreach ( [ 'litespeed_task_optimax', self::PULL_HOOK ] as $hook ) {
					self::debug( 'Run cron now [name] ' . $hook );
					do_action( $hook );
				}
				return __( 'Cron tasks ran.', 'litespeed-cache' );

			case self::TYPE_CLEAR_Q:
				$kept = $pages->clear_waiting();
				if ( $kept ) {
					/* translators: %d: number of pages QUIC.cloud is still optimizing */
					Admin_Display::success( sprintf( _n( 'OptimaX queue cleared. %d page QUIC.cloud is already optimizing was kept.', 'OptimaX queue cleared. %d pages QUIC.cloud is already optimizing were kept.', $kept, 'litespeed-cache' ), $kept ) );
				} else {
					Admin_Display::success( __( 'OptimaX queue cleared.', 'litespeed-cache' ) );
				}
				break;

			case self::TYPE_GEN:
				// One page per click (both devices), behind the cron's own throttles: a manual run never loads QUIC.cloud more than the cron.
				$wait = (int) self::get_summary( $this->_next_run_after_key() ) - time();
				if ( $wait > 0 ) {
					/* translators: %s: time until the queue continues */
					Admin_Display::note( sprintf( __( 'QUIC.cloud is still optimizing a page. The OptimaX queue continues in %s.', 'litespeed-cache' ), Utility::readable_time( $wait, 0, true ) ) );
				} else {
					static::cron();
				}
				break;

			default:
				parent::handler();
				return;
		}

		Admin::redirect( $back );
	}

	/**
	 * Turn on the settings OptimaX needs while it is on, through their `litespeed_conf_load_option_*` filters: the stored values stay the owner's.
	 * Only turns a setting on: one already on keeps its value.
	 *
	 * @since 8.0
	 * @param Conf $conf Settings, loaded.
	 * @return void
	 */
	public static function force_required( $conf ) {
		foreach ( self::REQUIRED as $id ) {
			add_filter( 'litespeed_conf_load_option_' . $id, function ( $setting ) use ( $conf ) {
				return $setting || ! $conf->conf( self::O_OPTIMAX ) ? $setting : true;
			} );
		}
	}

	/**
	 * The same settings turned on in a raw options array, for code that reads the stored array instead of conf() (`.htaccess` rules).
	 *
	 * @since 8.0
	 * @param array $cfg Options, as stored.
	 * @return array
	 */
	public static function with_required( $cfg ) {
		if ( ! empty( $cfg[ self::O_OPTIMAX ] ) ) {
			foreach ( self::REQUIRED as $id ) {
				if ( empty( $cfg[ $id ] ) ) {
					$cfg[ $id ] = true;
				}
			}
		}
		return $cfg;
	}

	/**
	 * Whether OptimaX is what turns this setting on: OptimaX is on, the setting off.
	 *
	 * @since 8.0
	 * @param string $id Option id.
	 * @return bool
	 */
	public static function forces( $id ) {
		$conf = static::cls();
		return in_array( $id, self::REQUIRED, true ) && $conf->conf( self::O_OPTIMAX ) && ! $conf->conf( $id, true );
	}

	/**
	 * Svc id slug — drives queue type, Cloud::SVC_OPTIMAX, and summary key prefix.
	 *
	 * @return string
	 */
	protected function _svc_id() {
		return 'optimax';
	}

	/**
	 * Response field carrying the optimization payload (nested object).
	 *
	 * @return string
	 */
	protected function _data_key() {
		return 'data_optimax';
	}

	/**
	 * Optimax processes whole pages — needs a longer PHP execution window.
	 *
	 * @return int
	 */
	protected function _php_time_limit() {
		return 1200;
	}

	/**
	 * A page that failed stays queued: marked failed, moved to the end and sent again after RETRY_LATER_TTL (_skip_item()), up to MAX_TRIES.
	 *
	 * @since 8.0
	 * @param string $queue_k Queue key.
	 * @return void
	 */
	protected function _on_failed( $queue_k ) {
		$tries = 0;
		$this->_change_queue( function ( &$queue ) use ( $queue_k, &$tries ) {
			if ( ! isset( $queue[ $queue_k ] ) ) {
				return false;
			}
			$tries = (int) ( $queue[ $queue_k ]['_tries'] ?? 0 ) + 1;
			$v     = array_merge( $queue[ $queue_k ], [ '_status' => 'failed', '_err_at' => time(), '_tries' => $tries ] );
			unset( $queue[ $queue_k ] );
			$queue[ $queue_k ] = $v;
		} );
		if ( $tries ) {
			self::debug( 'Failed ' . $tries . '/' . self::MAX_TRIES . ', kept at the end of the queue [k] ' . $queue_k );
		}
	}

	/**
	 * Success with no status: nothing pending at QUIC.cloud, so the page stays queued for the next push.
	 *
	 * @since 8.0
	 * @param string $queue_k Queue key.
	 * @return bool
	 */
	protected function _no_status( $queue_k ) {
		self::debug( 'Nothing pending for [k] ' . $queue_k . ' — keeping it for the next push' );
		// Nothing in flight: clear the stale deadline so the push cron resends it.
		if ( ! $this->_waited ) {
			unset( $this->_summary[ $this->_next_run_after_key() ] );
			self::save_summary();
			self::debug( 'Cleared ' . $this->_svc_id() . ' try_later deadline; queued item will be resubmitted' );
		}
		// Back to waiting; a pull goes on to the other rows being built.
		$this->_change_queue( function ( &$queue ) use ( $queue_k ) {
			if ( ! isset( $queue[ $queue_k ]['_status'] ) ) {
				return false;
			}
			unset( $queue[ $queue_k ]['_status'] );
		} );
		return true;
	}

	/**
	 * Change the queue under a lock: both devices of a page are often queued by two requests at the same moment, and one lost its row.
	 *
	 * @since 8.0
	 * @param callable $change Gets the queue by reference; returning false leaves the file as it is.
	 * @return void
	 */
	protected function _change_queue( $change ) {
		$this->_queue = $this->update_queue( $this->_svc_id(), $change );
	}

	/**
	 * Read, change and write a queue under one flock, written atomically; unlocked if flock fails.
	 *
	 * @since 8.0
	 * @param string   $type   Queue type.
	 * @param callable $change Gets the current queue by reference; returning false leaves the file as it is.
	 * @return array The queue as saved: unchanged when the change returned false or did not save.
	 */
	public function update_queue( $type, $change ) {
		$static_path = LITESPEED_STATIC_DIR . $this->_build_filepath_prefix( $type ) . '.litespeed_conf.dat';
		wp_mkdir_p( dirname( $static_path ) );

		$lock = @fopen( $static_path . '.lock', 'c' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( $lock && ! flock( $lock, LOCK_EX ) ) {
			fclose( $lock ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			$lock = false;
		}
		if ( ! $lock ) {
			self::debug( 'Queue lock unavailable, changing it unlocked [type] ' . $type );
		}

		try {
			$queue   = $this->load_queue( $type );
			$changed = $queue;
			if ( false !== $change( $changed ) ) {
				if ( File::save_atomic( $static_path, \json_encode( $changed ) ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
					$queue = $changed;
				} else {
					self::debug( '❌ Queue not saved [type] ' . $type );
				}
			}
		} finally {
			if ( $lock ) {
				flock( $lock, LOCK_UN );
				fclose( $lock ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			}
		}

		return $queue;
	}

	/**
	 * Cron loop, plus one push after a pull that delivered a build: the next queued page would otherwise wait for the next push tick.
	 *
	 * @since 8.0
	 * @param bool $keep_going Process the whole queue when true.
	 * @return void
	 */
	protected function _cron_handler( $keep_going ) {
		$this->_stored   = false;
		$this->_pair_tag = null;
		parent::_cron_handler( $keep_going );

		// Only with the auto request cron on: the pull runs regardless of it, and must not start builds the owner did not ask for.
		if ( $this->_pull_only && $this->_stored && $this->conf( self::O_OPTIMAX_CRON ) ) {
			self::debug( 'OX CRON PUSH after a delivered pull' );
			$this->_pull_only = false;
			parent::_cron_handler( false );
		}
		$this->_pair_tag = null;
	}

	/**
	 * Which rows a run passes over.
	 *
	 * @since 8.0
	 * @param array $v Queue item.
	 * @return bool
	 */
	protected function _skip_item( $v ) {
		// A failed row waits RETRY_LATER_TTL before it is sent again, and stops at MAX_TRIES.
		if ( ( ! empty( $v['_tries'] ) && (int) $v['_tries'] >= self::MAX_TRIES ) || ( ! empty( $v['_err_at'] ) && time() - (int) $v['_err_at'] < self::RETRY_LATER_TTL ) ) {
			return true;
		}
		// A pull asks only about the rows QUIC.cloud is building: one never sent gets "nothing pending" and ends the run.
		if ( $this->_pull_only ) {
			return Optimax_Pages::is_waiting( $v );
		}
		// A push that just had a page accepted sends only that page's other device, if it is still waiting (_send_more_after()).
		return null !== $this->_pair_tag && ( $v['url_tag'] !== $this->_pair_tag || ! Optimax_Pages::is_waiting( $v ) );
	}

	/**
	 * Whether the run goes on after QUIC.cloud asked to wait: a pull to the other rows being built, a push once, to the page's other device.
	 *
	 * @since 8.0
	 * @param array  $v      The queue item.
	 * @param string $status 'queued' when QUIC.cloud just accepted it, '' when it was already building.
	 * @return bool
	 */
	protected function _send_more_after( $v, $status ) {
		if ( ! $this->_pull_only && 'queued' === $status && null === $this->_pair_tag ) {
			$this->_pair_tag = $v['url_tag'];
			return true;
		}
		return $this->_pull_only;
	}

	/**
	 * A pull goes on to the next building row after storing one, behind the in-flight request guard; a push stays at one.
	 *
	 * @since 8.0
	 * @return bool
	 */
	protected function _more_after_stored() {
		return $this->_pull_only;
	}

	/**
	 * Legacy summary key for the try_later deadline; kept across upgrades.
	 *
	 * @return string
	 */
	protected function _next_run_after_key() {
		return 'ox_next_run_after';
	}

	/**
	 * Reject malformed legacy queue rows before dispatch.
	 *
	 * @since 7.9.1
	 *
	 * @param string $queue_k Queue key.
	 * @param array  $v       Queue item.
	 * @return bool
	 */
	protected function _valid_queue_item( $queue_k, $v ) {
		foreach ( [ 'url', 'url_tag', 'vary' ] as $key ) {
			if ( ! is_array( $v ) || ! isset( $v[ $key ] ) || ! is_string( $v[ $key ] ) ) {
				return false;
			}
		}

		// A file (script, stylesheet, image, feed…) is never a page to optimize, however it got queued.
		if ( self::is_file_url( $v['url'] ) || self::is_file_url( $v['url_tag'] ) ) {
			return self::_bail( 'Not sent: file URL [url] ' . $v['url'] );
		}

		// Last: a version queued under the other Next-Gen Image Format setting would be kept under a vary nobody is served now.
		return '' !== $queue_k && '' !== $v['url'] && '' !== $v['url_tag'] &&
			( empty( $v['is_nextgen'] ) || in_array( $v['is_nextgen'], [ 'webp', 'avif' ], true ) ) &&
			( Optimax_Pages::is_served_version( $v['vary'], ! empty( $v['is_nextgen'] ) ) || self::_bail( 'Not sent: queued under another Next-Gen Image Format setting [k] ' . $queue_k ) );
	}

	/**
	 * Whether a URL names a file rather than a page.
	 *
	 * @since 8.0
	 * @param string $url URL or path.
	 * @return bool
	 */
	public static function is_file_url( $url ) {
		// Without the trailing slash Utility::request_url() adds under pretty permalinks: it would hide the extension.
		$ext = strtolower( pathinfo( untrailingslashit( (string) wp_parse_url( (string) $url, PHP_URL_PATH ) ), PATHINFO_EXTENSION ) );

		// Slugs have no dot: a page URL only has an extension from the permalink structure (`/%postname%.html`) or `index.php`.
		return '' !== $ext && ! in_array( $ext, [ 'php', strtolower( pathinfo( untrailingslashit( (string) get_option( 'permalink_structure' ) ), PATHINFO_EXTENSION ) ) ], true );
	}

	/**
	 * Build the request body for Cloud::post.
	 *
	 * @param string $queue_k Queue key.
	 * @param array  $v       Queue item.
	 * @return array
	 */
	protected function _build_payload( $queue_k, $v ) {
		// Debug only, for the delivery timing line: `_sent_at` is the first push, `_polls` the requests so far (`_tries` counts failures).
		if ( defined( 'LSCWP_LOG' ) ) {
			$this->_change_queue( function ( &$queue ) use ( $queue_k ) {
				if ( ! isset( $queue[ $queue_k ] ) ) {
					return false;
				}
				if ( empty( $queue[ $queue_k ]['_sent_at'] ) && ! $this->_pull_only ) {
					$queue[ $queue_k ]['_sent_at'] = time();
				}
				$queue[ $queue_k ]['_polls'] = (int) ( $queue[ $queue_k ]['_polls'] ?? 0 ) + 1;
			} );
		}

		$data = [
			'url'        => $v['url'],
			'queue_k'    => $queue_k,
			'is_mobile'  => ! empty( $v['is_mobile'] ) ? 1 : 0,
			// The current setting's format: '' (Next-Gen off) tells the service to do no image work.
			'is_nextgen' => $this->cls( 'Media' )->webp_support( true ),
			'optm_ori'   => $this->conf( self::O_IMG_OPTM_ORI ) ? 1 : 0,
		];

		// The optimized image sizes, so the service can give an `<img>` a srcset of files WordPress already generated.
		if ( '' !== $data['is_nextgen'] && $this->_img_sizes() ) {
			$data['img_sizes'] = $this->_img_sizes();
		}

		// A collection run must not create work: the service answers with a cached result or try_later.
		if ( $this->_pull_only ) {
			return $data + [ 'pull_only' => 1 ];
		}

		// The page's UCSS, CCSS and VPI, when its design is the one they were made for: QUIC.cloud reuses them.
		// Each is sent only when it exists: a missing one is generated as usual.
		if ( ! empty( $v['reuse'] ) ) {
			$kept = [];
			foreach ( [ 'ucss', 'ccss' ] as $type ) {
				$filename = $this->cls( 'Data' )->load_url_file( $v['url_tag'], $v['vary'], $type );
				$css      = $filename ? trim( (string) File::read( LITESPEED_STATIC_DIR . $this->_build_filepath_prefix( $type ) . $filename . '.css' ) ) : '';
				// Not a file holding only a generation error comment.
				if ( '' !== $css && ! ( '/*' === substr( $css, 0, 2 ) && '*/' === substr( $css, -2 ) ) ) {
					$kept[ $type ] = $css;
				}
			}

			$post_id = Optimax_Pages::post_id( $v['url'] );
			$vpi     = $post_id ? Utility::sanitize_lines( get_post_meta( $post_id, ! empty( $v['is_mobile'] ) ? VPI::POST_META_MOBILE : VPI::POST_META, true ), 'basename' ) : [];
			// Not the placeholders a VPI result can hold: `data:` URIs and its "empty result".
			$vpi = preg_grep( '/^(?:data:|(?:empty result)?\z)/', $vpi, PREG_GREP_INVERT );
			if ( $vpi ) {
				$kept['vpi'] = array_values( $vpi );
			}

			self::debug( 'Reusing kept ' . ( $kept ? implode( '/', array_keys( $kept ) ) : 'nothing' ) . ' [url] ' . $v['url_tag'] );
			$data = array_merge( $data, $kept );
		}

		// A change PHP could not patch: the render the build matches, the render that showed the change and the build's HTML.
		if ( ! empty( $v['sync'] ) ) {
			$sync = $this->cls( 'Optimax_Pages' )->load_sync( $v['url_tag'], $v['vary'] );
			if ( $sync ) {
				$data['sync'] = $sync;
			}
			self::debug( ( $sync ? 'Sync requested' : 'Sync snapshot missing, a build is requested instead' ) . ' [url] ' . $v['url_tag'] );
		}

		return $data;
	}

	/**
	 * Fan out the nested optimization payload to four save targets.
	 *
	 * @param array  $ox      data_optimax payload.
	 * @param string $queue_k Queue key.
	 * @param array  $v       Queue item.
	 * @return bool False when nothing was stored (abort), true otherwise.
	 */
	protected function _save_result( $ox, $queue_k, $v ) {
		if ( ! is_array( $ox ) || empty( $ox['html'] ) || ! is_string( $ox['html'] ) ) {
			self::debug( '❌ No HTML in data_optimax.' );
			return false;
		}
		if ( isset( $ox['imgs'] ) && ! is_array( $ox['imgs'] ) ) {
			return false;
		}
		foreach ( [ 'ucss', 'ccss' ] as $field ) {
			if ( isset( $ox[ $field ] ) && ! is_string( $ox[ $field ] ) ) {
				return false;
			}
		}

		$is_mobile  = ! empty( $v['is_mobile'] );
		$is_nextgen = $this->cls( 'Media' )->webp_support( true );

		// No next-gen format, so no image work was asked for; a service that predates that links `.webp` files this version cannot serve.
		if ( '' === $is_nextgen ) {
			unset( $ox['imgs'] );
			foreach ( [ 'html', 'ucss', 'ccss' ] as $field ) {
				if ( ! empty( $ox[ $field ] ) ) {
					$ox[ $field ] = preg_replace( '~' . Optimax_Sync::NEXTGEN_SUFFIX . '(?![A-Za-z0-9])~i', '.$1', (string) $ox[ $field ] );
				}
			}
		}

		// The build time the service reports, under its own key: _send_req() overwrites the shared last_spent with the local round-trip.
		if ( ! empty( $ox['took_ms'] ) ) {
			$this->_summary['last_took_ms_optimax'] = (int) $ox['took_ms'];
			self::debug( 'took_ms ' . (int) $ox['took_ms'] . ' [k] ' . $queue_k );
		}

		$pages  = $this->cls( 'Optimax_Pages' );
		$assets = [ 'js' => '', 'css' => '', 'imgs' => [] ];
		// A synced result is the version's own build with the change carried in: it takes over that build's manifest, so clean_expired() keeps its files.
		if ( ! empty( $ox['synced'] ) ) {
			$source = $pages->sync_source( $v['url_tag'], $v['vary'] );
			$assets = $source ? $pages->load_assets( $source ) : null;
			if ( ! $assets ) {
				return self::_bail( '❌ Synced result without the build it came from; result not stored [k] ' . $queue_k );
			}
			self::debug( 'Synced result keeps the assets of [build] ' . $source );
		}
		// The level QUIC.cloud's verification served the page at (`full`, `no-js-delay`, …), for the debug log only.
		if ( isset( $ox['level'] ) && is_string( $ox['level'] ) && preg_match( '/^[a-z][a-z-]{0,31}\z/', $ox['level'] ) ) {
			self::debug( 'level ' . $ox['level'] . ' [k] ' . $queue_k );
		}
		// The JS bundle, then an external used-CSS file: saved here and the HTML repointed; a failed pull stores nothing.
		foreach ( [ 'js' => 'JS bundle', 'css' => 'Used CSS' ] as $ext => $label ) {
			if ( empty( $ox[ $ext . '_url' ] ) ) {
				continue;
			}
			$con = $this->_fetch_con( $ox[ $ext . '_url' ], $ox[ $ext . '_sha256' ] ?? '' );
			// An empty body would share md5('') with every page: fail.
			if ( '' === trim( (string) $con ) ) {
				self::debug( '❌ Failed to fetch ' . $ext . '_url [k] ' . $queue_k );
				return self::_bail( '❌ ' . $label . ' not saved; result not stored [k] ' . $queue_k );
			}

			$name        = $this->_build_filepath_prefix( 'optimax' ) . md5( $con ) . '.' . $ext;
			$static_file = LITESPEED_STATIC_DIR . $name;
			$ok          = File::save( $static_file, $con, true );
			// Never repoint the HTML at a file that was not written.
			if ( false === $ok || ! file_exists( $static_file ) ) {
				self::debug( '❌ Failed to save ' . $ext . ' [file] ' . $static_file . ' [err] ' . wp_json_encode( $ok ) );
				return self::_bail( '❌ ' . $label . ' not saved; result not stored [k] ' . $queue_k );
			}
			self::debug( 'Saved ' . $ext . ': ' . $static_file );

			$ox['html']     = str_replace( $ox[ $ext . '_url' ], LITESPEED_STATIC_URL . $name, $ox['html'] );
			$assets[ $ext ] = basename( $name );
		}

		// Images before the HTML; an unsaved one is linked back to its original.
		$imgs = ! empty( $ox['imgs'] ) ? $this->_save_imgs( $ox['imgs'] ) : [ 'saved' => $assets['imgs'], 'restore' => [], 'count' => 0 ];
		if ( false === $imgs ) {
			return false;
		}
		$assets['imgs'] = $imgs['saved'];
		$restored       = 0;
		foreach ( $imgs['restore'] as $src => $formats ) {
			// The page links one format, the one this build was requested in.
			if ( ! in_array( $is_nextgen, $formats, true ) ) {
				continue;
			}
			// Undo the service's ".<format>" suffix by file name; matching a same-named file elsewhere is the safe error.
			$path = (string) wp_parse_url( (string) $src, PHP_URL_PATH );
			$name = false === strrpos( $path, '/' ) ? $path : substr( $path, strrpos( $path, '/' ) + 1 );
			// The reference may be encoded even when the service reported it decoded.
			$forms = '' === $name ? [] : array_unique( [ $name, rawurldecode( $name ), rawurlencode( rawurldecode( $name ) ) ] );
			foreach ( [ 'html', 'ucss', 'ccss' ] as $field ) {
				foreach ( empty( $ox[ $field ] ) ? [] : $forms as $form ) {
					$ox[ $field ] = str_replace( $form . '.' . $is_nextgen, $form, $ox[ $field ] );
				}
			}
			++$restored;
		}
		if ( $restored ) {
			self::debug( 'Linked ' . $restored . ' unsaved image(s) back to their originals [k] ' . $queue_k );
		}

		foreach ( [ 'ucss', 'ccss' ] as $type ) {
			if ( ! empty( $ox[ $type ] ) && ! $this->_save_css_con( $type, $ox[ $type ], $v['url_tag'], $v['vary'], $queue_k, $is_mobile, $is_nextgen ) ) {
				return false;
			}
		}

		if ( ! $this->_save_con( $ox['html'], $assets, $queue_k, $is_mobile, $is_nextgen, $v, ! empty( $ox['synced'] ) ) ) {
			return false;
		}

		// The build is ready: count its images in Image Optimization's summary, where Destroy clears them and Soft Reset leaves them.
		if ( $imgs['count'] ) {
			Img_Optm::save_summary( [ self::SUMMARY_OX_IMGS => (int) Img_Optm::get_summary( self::SUMMARY_OX_IMGS ) + $imgs['count'] ], true );
		}
		// The viewport images go where VPI keeps them: the page's post meta. Only a post or page has one; any other page is skipped.
		if ( isset( $ox['vpi'] ) ) {
			$post_id = is_array( $ox['vpi'] ) && array_filter( $ox['vpi'], 'is_string' ) === $ox['vpi'] ? Optimax_Pages::post_id( $v['url'] ) : null;
			if ( null === $post_id ) {
				self::debug( '❌ Malformed vpi in data_optimax, not saved' );
			} elseif ( ! $post_id ) {
				self::debug( 'No post for the page, vpi not saved [url] ' . $v['url'] );
			} else {
				$name = $is_mobile ? VPI::POST_META_MOBILE : VPI::POST_META;
				$this->cls( 'Metabox' )->save( $post_id, $name, array_map( 'urldecode', $ox['vpi'] ) );
				self::debug( 'Saved vpi [post_id] ' . $post_id . ' [meta] ' . $name );
			}
		}

		$this->_stored = true;
		// Debug only: where the time between a page change and its build went (the queue, the service, or waiting for the pull).
		if ( defined( 'LSCWP_LOG' ) ) {
			$row    = $this->load_queue( 'optimax' )[ $queue_k ] ?? [];
			$now    = time();
			$queued = (int) ( $row['queued_at'] ?? 0 );
			$sent   = (int) ( $row['_sent_at'] ?? 0 );
			$polls  = (int) ( $row['_polls'] ?? 0 );
			self::debug(
				'delivery timing: [request] ' . substr( hash( 'sha256', (string) $queue_k ), 0, 12 )
				. ' queued->sent=' . ( $queued && $sent ? ( $sent - $queued ) . 's' : '?' )
				. ' try_later=' . ( $polls ? max( 0, $polls - 1 ) : '?' )
				. ' sent->stored=' . ( $sent ? ( $now - $sent ) . 's' : '?' )
				. ' queued->stored=' . ( $queued ? ( $now - $queued ) . 's' : '?' )
			);
		}

		$pages->end_check( $v['url_tag'], $v['vary'], empty( $ox['synced'] ) ? 'rebuilt' : 'synced_qc' );
		$pages->drop_sync( $v['url_tag'], $v['vary'] );
		$url_id = $pages->url_id( $v['url_tag'] );
		if ( $url_id ) {
			$pages->clean_expired( $url_id );
		}

		return true;
	}

	/**
	 * Cache tag for every stored page an OptimaX build can replace: by url_tag alone, so no sibling vary is left stale.
	 *
	 * @since 8.0
	 * @param string $url_tag Page identity from get_url_tag().
	 * @return string
	 */
	public static function page_tag( $url_tag ) {
		return 'OPTIMAX.' . md5( $url_tag );
	}

	/**
	 * A file, not a page: the browser's Sec-Fetch-Dest says so (not "empty": prefetchers send it), or the URL has a file extension.
	 *
	 * @since 8.0
	 * @return bool
	 */
	private static function _is_static_file_request() {
		$dest = isset( $_SERVER['HTTP_SEC_FETCH_DEST'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_SEC_FETCH_DEST'] ) ) ) : '';

		// The raw path, not Utility::request_url(): its trailing slash under pretty permalinks hides the extension.
		return ! in_array( $dest, [ '', 'document', 'iframe', 'frame', 'empty' ], true ) || self::is_file_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	/**
	 * Generate URL tag for Optimax.
	 *
	 * @since 8.0
	 *
	 * @param string $request_url Current request URL.
	 * @return string The URL tag.
	 */
	public static function get_url_tag( $request_url ) {
		if ( apply_filters( 'litespeed_optimax_per_pagetype', false ) ) {
			return Utility::page_type();
		}

		return $request_url;
	}

	/**
	 * Whether this request has a live build, decided at init for Guest Mode; serve() still decides at render time.
	 *
	 * @since 8.0
	 * @return bool
	 */
	public function has_build() {
		if ( ! $this->conf( self::O_OPTIMAX ) || Router::is_logged_in() || self::_is_static_file_request() ) {
			return false;
		}

		// The page URL as Utility::request_url() will give it once WordPress has parsed the request: at init `$wp->request` is still empty.
		$path = (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( '' === $path ) {
			return false;
		}
		// Relative to the home path (a subfolder site: `/one/`).
		$home_path = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		if ( '' !== $home_path && '/' !== $home_path && 0 === strpos( $path, $home_path ) ) {
			$path = substr( $path, strlen( $home_path ) );
		}

		// Pretty permalinks only: _page_version() refuses plain ones.
		$version  = $this->_page_version( trailingslashit( home_url( ltrim( $path, '/' ) ) ), false );
		$filename = $version ? $this->cls( 'Data' )->load_url_file( $version[0], $version[1], 'optimax' ) : '';
		return $filename && file_exists( LITESPEED_STATIC_DIR . $this->_build_filepath_prefix( 'optimax' ) . $filename . '.html' ) && $this->_assets_intact( $filename );
	}

	/**
	 * The version a page request is served or queued as, after the checks has_build() and serve() share.
	 *
	 * @since 8.0
	 * @param string $request_url Request URL.
	 * @param bool   $log         Log why OptimaX does not run (serve() only: has_build() runs on every request).
	 * @return array|false `[ url_tag, vary ]`, or false when OptimaX does not run on this request.
	 */
	private function _page_version( $request_url, $log = true ) {
		// Plain permalinks put the query in the page URL: each `?x=N` would be a new OptimaX page.
		if ( ! get_option( 'permalink_structure' ) ) {
			return self::_bail( 'OX bypassed: no pretty permalinks', $log );
		}

		// The request URL leaves the query out: only args LSCache drops too (Drop Query String) keep it the same page.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $_GET && Optimax_Pages::has_other_query( array_keys( $_GET ), (array) $this->conf( self::O_CACHE_DROP_QS ) ) ) {
			return self::_bail( 'OX bypassed: query string', $log );
		}

		$excludes = apply_filters( 'litespeed_optimax_uri_exc', $this->conf( self::O_OPTIMAX_EXC ) );
		$hit      = isset( $_SERVER['REQUEST_URI'] ) ? REST::str_hit_uri( wp_unslash( $_SERVER['REQUEST_URI'] ), $excludes ) : false; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( $hit ) {
			return self::_bail( 'OX bypassed: hit OptimaX URI Excludes: ' . $hit, $log );
		}

		// One spelling per page: a visitor may spell its escapes another way.
		$url_tag = self::get_url_tag( Optimax_Pages::canonical_url( $request_url ) );
		// Guest Mode plays no part in a page's versions: a guest and a visitor share one build.
		self::$_vary_cookies = [];
		$vary                = $this->cls( 'Vary' )->finalize_full_varies( true );

		// A version per visitor-chosen value (currency, language) would never be reused: such a visitor gets the normal page.
		$has_value = false;
		foreach ( array_filter( self::$_vary_cookies ) as $name ) {
			// As Vary reads them: an empty or "0" cookie is no value.
			$has_value = $has_value || ! empty( $_COOKIE[ $name ] );
		}
		if ( $has_value && ! apply_filters( 'litespeed_optimax_queue_vary_cookies', false ) ) {
			return self::_bail( 'OX bypassed: custom vary cookie value', $log );
		}

		if ( ! Optimax_Pages::is_served_version( $vary ) ) {
			return self::_bail( 'OX bypassed: version not served under the current Next-Gen setting', $log );
		}

		return [ $url_tag, $vary ];
	}

	/**
	 * `litespeed_control_finalize`: keep a qcbot response for a page with a build out of the public cache.
	 * serve() gives qcbot the build without the change check: a spoofed qcbot UA could otherwise pin an old build for every visitor.
	 *
	 * @since 8.0
	 * @return void
	 */
	public function qcbot_nocache() {
		if ( self::_is_qcbot() && $this->has_build() ) {
			Control::set_nocache( 'OX qcbot' );
		}
	}

	/**
	 * Serve optimized page from cache if available.
	 *
	 * Called during buffer finalization as the first priority check.
	 * If ox HTML is found, returns it to skip all other optimization hooks.
	 *
	 * @since 8.0
	 *
	 * @param string $buffer The page WordPress rendered for this request.
	 * @return string|false The optimized HTML content, or false if not available.
	 */
	public function serve( $buffer = '' ) {
		// Only a full HTML document (Core::send_headers_force() ran check_is_html()): never REST/AJAX JSON, feeds or ESI fragments.
		if ( ! defined( 'LITESPEED_IS_HTML' ) ) {
			return self::_bail( 'serve() bypassed: not an HTML document' );
		}
		// A missing file answered with WordPress's 404 page: never queued under the file's URL.
		if ( self::_is_static_file_request() ) {
			return self::_bail( 'serve() bypassed: static file URL' );
		}
		// Not cacheable: the stored HTML would outlive the request it was personalized for.
		if ( ! Control::is_cacheable() ) {
			return self::_bail( 'serve() bypassed: not cacheable' );
		}
		// Logged-out pages only: a cached logged-in view would be built once per user vary, for nobody else.
		if ( Router::is_logged_in() ) {
			return self::_bail( 'serve() bypassed: logged in' );
		}
		// Every missing URL renders the same 404 template: one build, kept and requested under one URL, serves them all, so a new 404 URL never sends a request.
		// A fixed URL that 404s, not a visitor's: Rebuild now requests a page by its kept URL, and a visitor's missing URL may become a real page later.
		$request_url = is_404() ? home_url( '/optimax-404/' ) : Utility::request_url();
		$version     = $this->_page_version( $request_url );
		if ( ! $version ) {
			return false;
		}
		list( $url_tag, $vary ) = $version;

		$pages           = $this->cls( 'Optimax_Pages' );
		$filepath_prefix = $this->_build_filepath_prefix( 'optimax' );
		$filename        = $this->cls( 'Data' )->load_url_file( $url_tag, $vary, 'optimax' );

		// Tag every render a build can replace by page, not Tag::get_uri_tag() (its format differs per request).
		Tag::add( self::page_tag( $url_tag ) );

		if ( $filename && $this->_assets_intact( $filename ) ) {
			$static_file = LITESPEED_STATIC_DIR . $filepath_prefix . $filename . '.html';

			if ( file_exists( $static_file ) ) {
				$html = File::read( $static_file );
				if ( $html ) {
					// The 404 build is served as is: each missing URL's render differs a little (its own URL in a search form or a message), and comparing them would patch the shared build back and forth or send a sync for nothing.
					if ( ! is_404() ) {
						$html = $this->_timed_sync( $html, $buffer, $url_tag, $vary );
					}
					if ( null === $html ) {
						// The page changed in this render; the next one confirms it or not.
						Core::comment( 'OX: page has changed, checking the differences' );
						return false;
					}
					if ( false === $html ) {
						// A confirmed design change: the old design's kept UCSS/CCSS no longer fit.
						$design = 'design-to-qc' === $this->_sync_stats['outcome'];
						if ( $design ) {
							$pages->clear_kept( $url_tag );
						}
						// QUIC.cloud carries the change into the build, or builds this version when it cannot; visitors get the page without OptimaX meanwhile.
						if ( $this->_queue_sync( $request_url, $url_tag, $vary, $buffer, $filename ) ) {
							Core::comment( 'OX sync queued: page changed' );
							return false;
						}
						// Sync refused: rebuild.
						$pages->note_check( $url_tag, $vary, 'rebuild' );
						$pages->expire( $pages->url_id( $url_tag ) );
						if ( ! $design ) {
							$pages->clear_kept( $url_tag );
						}
						// Its other versions are still cached with their OptimaX HTML; drop them too.
						Purge::add( self::page_tag( $url_tag ) );
						$this->_queue_version( $request_url, $url_tag, $vary, false, $buffer, 'design', $pages->check_what( $url_tag, $vary ) );
						Core::comment( 'OX rebuild queued: page changed' );
						return false;
					}
					// A build older than the Rebuild Interval, counted from its first serve (patches keep it), is rebuilt while it keeps being served.
					// Not on a qcbot render: it is never kept as a baseline (qcbot_nocache() keeps the response out of the public cache).
					$ttl = (int) $this->conf( self::O_OPTIMAX_TTL );
					$fp  = $ttl > 0 && ! self::_is_qcbot() ? $pages->load_fingerprint( $url_tag, $vary ) : null;
					if ( $fp && ! empty( $fp['built'] ) && time() - (int) $fp['built'] >= $ttl && $this->_queue_version( $request_url, $url_tag, $vary, true, $buffer, 'ttl' ) ) {
						self::debug( 'Rebuild queued: build older than ' . $ttl . 's' );
					}
					self::debug( 'serve() hit: ' . $filepath_prefix . $filename . '.html' );
					Core::comment( 'OX served ✅' );
					return $html;
				}
				self::debug( 'serve() empty file: ' . $static_file );
			} else {
				self::debug( 'serve() file missing: ' . $static_file );
			}
		}

		// No build in use (never built, or its design changed): queue one; a version whose sync snapshot is still kept, as that sync.
		$sync = '' !== $pages->sync_source( $url_tag, $vary );
		$why  = $sync ? 'sync' : $pages->unbuilt_cause( $url_tag, $vary );
		if ( $this->_queue_version( $request_url, $url_tag, $vary, ! $sync && $this->_same_design( $buffer, $url_tag, $vary ), $buffer, $why, $pages->check_what( $url_tag, $vary ) ) ) {
			Core::comment( 'OX in queue' );
		}

		return false;
	}

	/**
	 * Queue a sync from the version's snapshot; the version stops serving its build meanwhile.
	 *
	 * @since 8.0
	 * @param string $request_url Request URL.
	 * @param string $url_tag     Page identity.
	 * @param string $vary        Raw vary.
	 * @param string $buffer      This render, which showed the change.
	 * @param string $filename    The build serving this version.
	 * @return bool Whether a sync replaces the rebuild; false leaves the caller to rebuild.
	 */
	private function _queue_sync( $request_url, $url_tag, $vary, $buffer, $filename ) {
		$pages = $this->cls( 'Optimax_Pages' );
		$fp    = $pages->load_fingerprint( $url_tag, $vary );
		if ( ! $fp || '' === $fp['base'] ) {
			return false;
		}
		// QUIC.cloud reads each stylesheet as its file is now: a sheet changed under the same URL shows it no change, and it would keep the old used CSS.
		if ( $this->_sheets['changed'] ) {
			return self::_bail( 'sync: a stylesheet changed under the same URL, rebuilding instead: ' . implode( ', ', array_slice( $this->_sheets['changed'], 0, 3 ) ) );
		}
		// The build links its own render's stylesheet and image URLs: a `?ver=` bump over an unchanged file and a proven next-gen copy are undone, or QUIC.cloud refuses the sync.
		$base = Optimax_Pages::unpack_base( $fp['base'] );
		$now  = Optimax_Sync::align_images( $base, Optimax_Sync::align_stylesheets( $base, $buffer, $this->_sheets['allow'] ) );
		if ( ! $pages->save_sync( $url_tag, $vary, $fp['base'], $now, $filename, $this->_sheets['same'] ) ) {
			return self::_bail( '❌ Sync snapshot not saved, rebuilding instead [url] ' . $url_tag );
		}

		if ( $this->_queue_version( $request_url, $url_tag, $vary, false, '', 'sync', $pages->check_what( $url_tag, $vary ) ) ) {
			self::debug( 'Sync queued [url] ' . $url_tag );
		}
		$pages->note_check( $url_tag, $vary, 'sync' );
		$pages->expire_version( $url_tag, $vary );

		return true;
	}

	/**
	 * Send kept UCSS/CCSS/VPI only when a fingerprint proves the design unchanged; a different design clears them.
	 *
	 * @since 8.0
	 * @param string $buffer  This render.
	 * @param string $url_tag Page identity.
	 * @param string $vary    Raw vary.
	 * @return bool
	 */
	private function _same_design( $buffer, $url_tag, $vary ) {
		if ( isset( $this->load_queue( 'optimax' )[ Optimax_Pages::queue_key( $url_tag, $vary ) ] ) ) {
			return false;
		}

		$pages = $this->cls( 'Optimax_Pages' );
		$old   = $pages->load_fingerprint( $url_tag, $vary );
		if ( ! $old ) {
			return self::_bail( 'Kept UCSS/CCSS/VPI not sent: no fingerprint to compare with' );
		}
		$now = '' !== (string) $buffer ? Optimax_Sync::fingerprint( $buffer ) : null;
		if ( ! $now ) {
			return false;
		}
		if ( $old['design'] !== $now['design'] ) {
			// Changed while the page had no build: no build to expire, but they no longer fit.
			$pages->clear_kept( $url_tag );
			$pages->drop_fingerprint( $url_tag, $vary );
			return false;
		}

		self::debug( 'Kept UCSS/CCSS/VPI reused: same design' );
		return true;
	}

	/**
	 * Queue this request's version of a page for a build; a version already queued stays as it is (QUIC.cloud may be building it).
	 *
	 * @since 8.0
	 * @param string $request_url Request URL.
	 * @param string $url_tag     Page identity.
	 * @param string $vary        Raw vary.
	 * @param bool   $reuse       Send the page's UCSS, CCSS and VPI with the request: its design has not changed since they were made.
	 * @param string $buffer      This request's render, kept as the render the build is compared with (_save_con()); empty when there is none.
	 * @param string $why         Why it is queued, an Optimax_Pages::CAUSES value kept with its build; `sync` asks QUIC.cloud to carry a page change into the build.
	 * @param string $why_detail  The change behind it, when there is one.
	 * @return bool Whether the version is queued.
	 */
	private function _queue_version( $request_url, $url_tag, $vary, $reuse, $buffer, $why, $why_detail = '' ) {
		// A cron request is not a page view: never queue its URL.
		if ( wp_doing_cron() ) {
			return self::_bail( 'Queue bypassed: doing cron' );
		}

		// The LiteSpeed crawler warms the cache: it would put the whole site in the queue at once.
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( false !== strpos( $ua, Crawler::FAST_USER_AGENT ) || false !== strpos( $ua, Crawler::USER_AGENT ) ) {
			return self::_bail( 'Not queued: crawler request' );
		}

		$queue_k = Optimax_Pages::queue_key( $url_tag, $vary );
		$item    = [
			'url'        => apply_filters( 'litespeed_optimax_url', $request_url ),
			'is_mobile'  => $this->_separate_mobile(),
			// The setting's format, not the browser's: '' tells the service the site has no next-gen format on, so it does no image work.
			'is_nextgen' => $this->cls( 'Media' )->webp_support( true ),
			'vary'       => $vary,
			'url_tag'    => $url_tag,
			'reuse'      => $reuse ? 1 : 0,
			'sync'       => 'sync' === $why ? 1 : 0,
			'queued_at'  => time(),
			'why'        => $why,
			'why_detail' => substr( (string) $why_detail, 0, 200 ),
		];
		$added   = false;
		$this->_change_queue( function ( &$queue ) use ( $queue_k, $item, $request_url, &$added ) {
			// _enqueue() works on $this->_queue.
			$this->_queue = $queue;
			if ( ! isset( $queue[ $queue_k ] ) ) {
				if ( ! $this->_enqueue( $queue_k, $item, $request_url ) ) {
					return false;
				}
			} elseif ( $item['sync'] && Optimax_Pages::is_waiting( $queue[ $queue_k ] ) ) {
				// Still waiting: a sync (never with reuse) replaces its request.
				foreach ( [ 'reuse', 'sync', 'why', 'why_detail' ] as $key ) {
					$this->_queue[ $queue_k ][ $key ] = $item[ $key ];
				}
			}
			// The other device's version too.
			$this->cls( 'Optimax_Pages' )->add_versions( $this->_queue, $item, $this->_max_queue_size() );
			$queue = $this->_queue;
			$added = true;
		} );
		if ( ! $added ) {
			return false;
		}
		self::debug( 'Added Optimax queue item [request] ' . substr( hash( 'sha256', $queue_k ), 0, 12 ) );
		if ( '' !== (string) $buffer ) {
			$this->cls( 'Optimax_Pages' )->save_queued_render( $url_tag, $vary, $buffer );
		}

		return true;
	}

	/**
	 * The queue size limit, for code queueing versions outside this class.
	 *
	 * @since 8.0
	 * @return int
	 */
	public function max_queue_size() {
		return $this->_max_queue_size();
	}

	/**
	 * Whether this request is QUIC.cloud's own fetcher (`qcbot/1.0`).
	 *
	 * @since 8.0
	 * @return bool
	 */
	private static function _is_qcbot() {
		return isset( $_SERVER['HTTP_USER_AGENT'] ) && false !== stripos( (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ), 'qcbot' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	/**
	 * _sync(), timed: one debug line with its time, memory and outcome.
	 *
	 * @since 8.0
	 * @param string $html    Stored OptimaX HTML.
	 * @param string $buffer  This render.
	 * @param string $url_tag Page identity.
	 * @param string $vary    Raw vary.
	 * @return string|false|null As _sync().
	 */
	private function _timed_sync( $html, $buffer, $url_tag, $vary ) {
		// QUIC.cloud's own fetcher renders the page in its own way: it must neither record, compare nor confirm a change.
		$qcbot             = self::_is_qcbot();
		$this->_sync_stats = [ 'outcome' => $qcbot ? 'qcbot' : 'same', 'ms' => [] ];
		$mem               = memory_get_usage();
		$t                 = microtime( true );
		$out               = $qcbot ? $html : $this->_sync( $html, $buffer, $url_tag, $vary );

		if ( defined( 'LSCWP_LOG' ) ) {
			$x    = $this->_sync_stats;
			$line = 'sync timing: ' . $x['outcome'] . ' total=' . self::_ms( $t ) . 'ms';
			foreach ( $x['ms'] as $k => $ms ) {
				$line .= ' ' . $k . '=' . $ms . 'ms';
			}
			$line .= ' mem+=' . round( ( memory_get_usage() - $mem ) / 1024 ) . 'KB'
				. ' peak=' . round( memory_get_peak_usage() / 1048576, 1 ) . 'MB'
				. ' render=' . round( strlen( (string) $buffer ) / 1024 ) . 'KB'
				. ' build=' . round( strlen( $html ) / 1024 ) . 'KB'
				. ( isset( $x['items'] ) ? ' items=' . $x['items'] : '' )
				. ( ! empty( $x['why'] ) ? ' why=' . substr( $x['why'], 0, 160 ) : '' );
			self::debug( $line );
		}

		return $out;
	}

	/**
	 * Milliseconds since a microtime( true ) start.
	 *
	 * @since 8.0
	 * @param float $t Start.
	 * @return float
	 */
	private static function _ms( $t ) {
		return round( ( microtime( true ) - $t ) * 1000, 1 );
	}

	/**
	 * Bring a build in step with this render of its page, compared with the fingerprint of the render the build matches.
	 * A design change, or a content change PHP cannot patch, goes to QUIC.cloud once a second render confirms it (_confirm()).
	 *
	 * @since 8.0
	 * @param string $html    Stored OptimaX HTML.
	 * @param string $buffer  This render.
	 * @param string $url_tag Page identity.
	 * @param string $vary    Raw vary.
	 * @return string|false|null The HTML to serve; false when a change is confirmed; null while a first sighting waits for the next render.
	 */
	private function _sync( $html, $buffer, $url_tag, $vary ) {
		$t   = microtime( true );
		$now = '' !== (string) $buffer ? Optimax_Sync::fingerprint( $buffer ) : null;

		$this->_sync_stats['ms']['fp'] = self::_ms( $t );
		if ( ! $now ) {
			$this->_sync_stats['outcome'] = 'unreadable';
			self::debug( 'sync skipped: render not readable' );
			return $html;
		}
		$this->_sync_stats['items'] = count( $now['items'] );

		$t     = microtime( true );
		$pages = $this->cls( 'Optimax_Pages' );
		$old   = $pages->load_fingerprint( $url_tag, $vary );

		$this->_sync_stats['ms']['load'] = self::_ms( $t );
		// No fingerprint yet (the build's first serve): this render becomes it.
		if ( ! $old ) {
			$this->_sync_stats['outcome'] = 'recorded';
			$now['base']                  = Optimax_Pages::pack_base( $buffer );
			$pages->save_fingerprint( $url_tag, $vary, $now, 0 );
			// This render is no build's: stylesheet copies left from another baseline would not be its CSS.
			$pages->drop_sheet_copies( $url_tag, $vary );
			// A check left open against a fingerprint of an older format has nothing to confirm against now.
			$pages->end_check( $url_tag, $vary );
			self::debug( 'sync: fingerprint recorded' );
			return $html;
		}

		$now = $this->_same_sheets( $html, $buffer, $old, $now, $url_tag, $vary );
		if ( $old['design'] !== $now['design'] ) {
			$where = ( count( $old['lines'] ) - count( $now['lines'] ) ) . ' line(s) removed at the end';
			foreach ( $now['lines'] as $i => $hash ) {
				if ( ! isset( $old['lines'][ $i ] ) || $old['lines'][ $i ] !== $hash ) {
					$where = 'at line ' . $i . ': ' . substr( $now['skel'][ $i ], 0, 300 );
					break;
				}
			}
			self::debug( 'sync: design changed ' . $where );
			if ( defined( 'LSCWP_LOG' ) ) {
				foreach ( Optimax_Sync::changed_sheets( $old['lines'], $now ) as $note ) {
					self::debug( 'sync: stylesheet changed ' . $note . ' [built] ' . (int) $old['built'] );
				}
			}
			$this->_sync_stats['outcome'] = 'design-to-qc';
			return $this->_confirm( $old, $now['design'], $url_tag, $vary, 'design: ' . $where );
		}

		$t       = microtime( true );
		$changed = Optimax_Sync::changed_item( $old['items'], $now['items'] );

		$this->_sync_stats['ms']['compare'] = self::_ms( $t );
		// Only per-render values differed (an image's id): the build as is.
		if ( null === $changed ) {
			// Back to what the build matches: a change seen before was a one-off. Cleared only here, so a content change the next render repeats can still be confirmed.
			if ( ! empty( $old['pending'] ) ) {
				$old['pending'] = '';
				$pages->save_fingerprint( $url_tag, $vary, $old, (int) $old['patches'], (int) $old['built'] );
				$pages->end_check( $url_tag, $vary );
				self::debug( 'sync: the changed render was a one-off; the build stays' );
			}
			return $html;
		}

		self::debug( 'sync: content changed: ' . $changed );
		$patched = $this->_patch( $html, $buffer, $old, $now, $url_tag, $vary, $changed );
		if ( false !== $patched ) {
			return $patched;
		}
		$this->_sync_stats['outcome'] = 'content-to-qc';
		return $this->_confirm( $old, 'content:' . Optimax_Sync::content_sig( $now['items'] ), $url_tag, $vary, 'content: ' . $changed );
	}

	/**
	 * This render's fingerprint with each builder stylesheet whose change styles nothing on the page read as the copy the build was cut from.
	 * Also notes the local stylesheets changed under the same URL: QUIC.cloud cannot see such a change, so _queue_sync() leaves it to a rebuild.
	 *
	 * @since 8.0
	 * @param string $html    Stored OptimaX HTML.
	 * @param string $buffer  This render.
	 * @param array  $old     Stored fingerprint.
	 * @param array  $now     This render's fingerprint.
	 * @param string $url_tag Page identity.
	 * @param string $vary    Raw vary.
	 * @return array $now, its unchanged sheets read as their copies.
	 */
	private function _same_sheets( $html, $buffer, $old, $now, $url_tag, $vary ) {
		$same = [];
		// Needs the build's render (`base`); a fingerprint without one compares no rules.
		if ( $old['design'] !== $now['design'] && '' !== $old['base'] ) {
			$t     = microtime( true );
			$pages = $this->cls( 'Optimax_Pages' );
			$build = md5( $html );
			$kept  = $pages->load_sheet_copies( $url_tag, $vary, $build );
			if ( $kept['md5'] ) {
				$known = $kept['known'];
				$notes = defined( 'LSCWP_LOG' ) ? [] : null;
				$same  = Optimax_Sync::same_sheets(
					$now,
					$kept['md5'],
					function ( $path ) use ( $kept ) {
						return Optimax_Pages::unpack_sheet( $kept['packed'][ $path ] );
					},
					function () use ( $html, $buffer, $old ) {
						return Optimax_Sync::rule_context( Optimax_Pages::unpack_base( $old['base'] ), $buffer, $html );
					},
					$known,
					$notes
				);
				if ( $known !== $kept['known'] ) {
					$pages->save_sheet_verdicts( $url_tag, $vary, $build, $known );
				}
				foreach ( (array) $notes as $note ) {
					self::debug( 'sync: builder stylesheet rules ' . $note );
				}
			}
			$this->_sync_stats['ms']['sheets'] = self::_ms( $t );
		}

		$now           = Optimax_Sync::with_sheets( $now, $same );
		$this->_sheets = Optimax_Sync::sheet_changes( $old['lines'], $now, $same ) + [ 'same' => $same ];
		return $now;
	}

	/**
	 * Write a content-only change into the stored build: the patched HTML becomes the version's build, this render its fingerprint.
	 *
	 * @since 8.0
	 * @param string $html    Stored OptimaX HTML.
	 * @param string $buffer  This render.
	 * @param array  $old     Stored fingerprint.
	 * @param array  $now     This render's fingerprint.
	 * @param string $url_tag Page identity.
	 * @param string $vary    Raw vary.
	 * @param string $changed The first changed item, for the check list.
	 * @return string|false The HTML to serve, or false when the change cannot be placed: it goes the QUIC.cloud way.
	 */
	private function _patch( $html, $buffer, $old, $now, $url_tag, $vary, $changed ) {
		$t       = microtime( true );
		$patched = Optimax_Sync::patch( $html, $old['items'], $now['items'], \Closure::fromCallable( [ $this, '_nextgen_img' ] ) );

		$this->_sync_stats['ms']['patch'] = self::_ms( $t );
		if ( false === $patched ) {
			$this->_sync_stats['why'] = Optimax_Sync::$why;
			return self::_bail( 'sync: a content change could not be placed in the OptimaX HTML: ' . Optimax_Sync::$why );
		}
		// Only per-render values differed: nothing to store.
		if ( $patched === $html ) {
			$this->_sync_stats['outcome'] = 'patch-noop';
			return $html;
		}

		// Stored with the assets of the build it was patched from. No purge: this request serves it, and is cached with it.
		$t     = microtime( true );
		$pages = $this->cls( 'Optimax_Pages' );
		$md5   = md5( $patched );
		$file  = LITESPEED_STATIC_DIR . $this->_build_filepath_prefix( 'optimax' ) . $md5 . '.html';
		if ( ! File::save_atomic( $file, $patched ) ) {
			return self::_bail( '❌ Failed to save patched build [file] ' . $file );
		}
		$assets = $pages->load_assets( (string) $this->cls( 'Data' )->load_url_file( $url_tag, $vary, 'optimax' ) );
		if ( ! $assets || ! $pages->save_assets( $md5, array_merge( $assets, [ 'action' => 'synced_plugin', 'cause' => 'patch', 'detail' => 'content: ' . $changed ] ) ) ) {
			return self::_bail( '❌ No asset manifest for the patched build [file] ' . $file );
		}
		$groups = Optimax_Pages::version_groups( $vary );
		$this->cls( 'Data' )->save_url( $url_tag, $vary, 'optimax', $md5, dirname( $file ), $groups['mobile'], $groups['nextgen'] );

		$patches     = (int) $old['patches'] + 1;
		$now['base'] = Optimax_Pages::pack_base( $buffer );
		$pages->save_fingerprint( $url_tag, $vary, $now, $patches, empty( $old['built'] ) ? 0 : (int) $old['built'] );
		$pages->end_check( $url_tag, $vary, 'synced_plugin' );

		$this->_sync_stats['ms']['save'] = self::_ms( $t );
		$this->_sync_stats['outcome']    = 'patched';
		self::debug( 'sync: content updated in PHP [patches] ' . $patches );

		return $patched;
	}

	/**
	 * Whether a page change is confirmed: the render before this one saw it too.
	 * Some plugins render the page differently once, right after a save (Elementor's element cache, Neve's font list), then go back.
	 *
	 * @since 8.0
	 * @param array  $old     Stored fingerprint.
	 * @param string $sig     The change seen: the new design, or an unplaceable content change in it.
	 * @param string $url_tag Page identity.
	 * @param string $vary    Raw vary.
	 * @param string $what    The change, for the OptimaX page's list of checks.
	 * @return false|null False when confirmed (the page needs a refresh), null while waiting.
	 */
	private function _confirm( $old, $sig, $url_tag, $vary, $what ) {
		if ( isset( $old['pending'] ) && $old['pending'] === $sig ) {
			self::debug( 'sync: change confirmed by a second render' );
			return false;
		}

		// A first sighting is remembered and served without OptimaX, cached for a minute only; a render matching the build again clears it (_sync()).
		$old['pending'] = $sig;
		$pages          = $this->cls( 'Optimax_Pages' );
		$pages->save_fingerprint( $url_tag, $vary, $old, (int) $old['patches'], (int) $old['built'] );
		$pages->note_check( $url_tag, $vary, 'confirming', $what );
		Control::set_custom_ttl( 60, 'OptimaX: confirm a page change' );
		// The cache-control header usually went out from the footer hook already; send it again with the short TTL.
		if ( defined( 'LITESPEED_DID_send_headers' ) && ! headers_sent() ) {
			header( $this->cls( 'Control' )->output() );
		}
		self::debug( 'sync: waiting for a second render to confirm the change' );

		return null;
	}

	/**
	 * An `<img>` of this render as a patched build links it: next-gen where the file exists.
	 *
	 * @since 8.0
	 * @param string $tag `<img>` tag.
	 * @return string
	 */
	private function _nextgen_img( $tag ) {
		$media = $this->cls( 'Media' );
		$attrs = Optimax_Sync::attrs( $tag );

		foreach ( Optimax_Sync::IMG_URL_ATTRS as $name ) {
			if ( ! empty( $attrs[ $name ] ) && 0 !== strpos( $attrs[ $name ], 'data:' ) ) {
				$new = Optimax_Sync::set_attr( $tag, $name, $media->webp_url( $attrs[ $name ] ) );
				$tag = false === $new ? $tag : $new;
			}
		}
		foreach ( [ 'srcset', 'data-srcset' ] as $name ) {
			if ( empty( $attrs[ $name ] ) ) {
				continue;
			}
			$srcs = [];
			foreach ( explode( ',', $attrs[ $name ] ) as $src ) {
				$parts  = preg_split( '~\s+~', trim( $src ), 2 );
				$srcs[] = $media->webp_url( $parts[0] ) . ( isset( $parts[1] ) ? ' ' . $parts[1] : '' );
			}
			$new = Optimax_Sync::set_attr( $tag, $name, implode( ', ', $srcs ) );
			$tag = false === $new ? $tag : $new;
		}

		return $tag;
	}

	/**
	 * Every file the build's manifest lists still exists; a missing one costs a rebuild instead of a broken page.
	 *
	 * @since 8.0
	 * @param string $filename Build HTML filename, without extension.
	 * @return bool
	 */
	private function _assets_intact( $filename ) {
		$assets = $this->cls( 'Optimax_Pages' )->load_assets( $filename );
		if ( ! $assets ) {
			return self::_bail( 'serve() bypassed: no asset manifest for ' . $filename );
		}

		$dir = LITESPEED_STATIC_DIR . $this->_build_filepath_prefix( 'optimax' );
		foreach ( [ $assets['js'], $assets['css'] ] as $file ) {
			if ( $file && ! file_exists( $dir . $file ) ) {
				return self::_bail( 'serve() bypassed: missing ' . $dir . $file );
			}
		}

		// The images this build saved beside their originals; those the site had already optimized are only linked, not listed.
		foreach ( $assets['imgs'] as $img ) {
			if ( ! file_exists( $img ) ) {
				return self::_bail( 'serve() bypassed: image missing ' . $img );
			}
		}

		return true;
	}

	/**
	 * Download and save optimized images locally.
	 *
	 * Each image entry has src and any requested ori/webp/avif artifact.
	 * Optimized images are saved beside their WordPress image targets.
	 *
	 * @since 8.0
	 *
	 * @param array $imgs Array of image optimization data.
	 * @return array|false `saved` next-gen paths, `restore` [ src => formats without a file ], `count` images saved; false when a fetch or digest check failed.
	 */
	private function _save_imgs( $imgs ) {
		$hooks        = [
			'ori'  => 'litespeed_img_pull_ori',
			'webp' => 'litespeed_img_pull_webp',
			'avif' => 'litespeed_img_pull_avif',
		];
		$types        = [ 'webp', 'avif' ];
		$optm_ori     = (bool) $this->conf( self::O_IMG_OPTM_ORI );
		$preserve_ori = $optm_ori && ! $this->conf( self::O_IMG_OPTM_RM_BKUP );
		if ( $optm_ori ) {
			$types[] = 'ori';
		}

		$out = [ 'saved' => [], 'restore' => [], 'count' => 0 ];
		foreach ( $imgs as $img ) {
			if ( ! is_array( $img ) ) {
				return false;
			}
			$src = ! empty( $img['src'] ) && is_string( $img['src'] ) && 2048 >= strlen( $img['src'] ) ? $img['src'] : '';

			$artifacts = [];
			foreach ( $types as $type ) {
				$url_key    = $type . '_url';
				$digest_key = $type . '_sha256';
				if ( empty( $img[ $url_key ] ) ) {
					continue;
				}
				$url = Img::normalize_cloud_url( $img[ $url_key ] );
				if (
					! $url || empty( $img[ $digest_key ] ) || ! is_string( $img[ $digest_key ] ) || ! preg_match( '/^[a-f0-9]{64}$/iD', $img[ $digest_key ] )
				) {
					return false;
				}
				$artifacts[ $type ] = [
					'url'    => $url,
					'digest' => strtolower( $img[ $digest_key ] ),
				];
			}

			// An entry skipped for want of an artifact URL or a WordPress target has its links put back on the original.
			if ( empty( $artifacts ) ) {
				if ( $src ) {
					self::debug( 'Skip Optimax image entry without an artifact URL; linked back to the original.' );
					$out['restore'][ $src ] = [ 'webp', 'avif' ];
				}
				continue;
			}
			if ( ! $src ) {
				self::debug( 'Skip Optimax image entry without a usable local source.' );
				continue;
			}
			$local = $this->_image_target( $img );
			if ( ! $local ) {
				self::debug( 'Skip Optimax image entry without a WordPress image target; linked back to the original.' );
				$out['restore'][ $src ] = [ 'webp', 'avif' ];
				continue;
			}
			list( $local_path, $local_root, $row ) = $local;

			$published = [];
			foreach ( $artifacts as $type => $artifact ) {
				$target = 'ori' === $type ? $local_path : $local_path . '.' . $type;
				$res    = Img::save( $artifact['url'], $target, $artifact['digest'], 'sha256', $type, $local_root, 'ori' === $type && $preserve_ori );
				if ( is_wp_error( $res ) ) {
					// Log the URL without its query string: it may carry a token.
					$parts  = wp_parse_url( $artifact['url'] );
					$label  = $parts['host'] . ( ! empty( $parts['path'] ) ? $parts['path'] : '/' );
					$detail = $res->get_error_data();
					self::debug( '❌ Failed to save img [url] ' . $label . ' [error] ' . $res->get_error_code() . ( '' !== (string) $detail ? ':' . $detail : '' ) );
					return false;
				}
				$published[ $type ] = $target;
			}
			++$out['count'];
			$out['saved'] = array_merge( $out['saved'], array_values( array_diff_key( $published, [ 'ori' => 1 ] ) ) );
			// A format the service sent no URL for has no file beside the original.
			$unsaved = array_values( array_diff( [ 'webp', 'avif' ], array_keys( $published ) ) );
			if ( $unsaved ) {
				$out['restore'][ $src ] = $unsaved;
			}
			if ( $row ) {
				foreach ( $hooks as $type => $hook ) {
					if ( isset( $published[ $type ] ) ) {
						do_action( $hook, $row, $published[ $type ] );
					}
				}
			}
		}

		return $out;
	}

	/**
	 * The optimized image sizes for the payload; the crop flag decides the sub-size file name WordPress wrote. Short keys: the list goes with every request.
	 *
	 * @since 8.0
	 * @return array List of `[ 'n' => name, 'w' => width, 'h' => height, 'c' => crop ]`.
	 */
	private function _img_sizes() {
		static $sizes = null;
		if ( null !== $sizes ) {
			return $sizes;
		}

		// Image Optimization's skip list, through the same filter Img_Optm reads it with.
		$skipped = apply_filters( 'litespeed_imgoptm_sizes_skipped', $this->conf( self::O_IMG_OPTM_SIZES_SKIPPED ) );
		$skipped = is_array( $skipped ) ? $skipped : [];

		$sizes = [];
		foreach ( $this->cls( 'Media' )->get_image_sizes() as $name => $size ) {
			$width  = (int) ( $size['width'] ?? 0 );
			$height = (int) ( $size['height'] ?? 0 );
			// No box: no file (and hidden from the settings by Utility::prepare_image_sizes_array()).
			if ( in_array( $name, $skipped, true ) || ( ! $width && ! $height ) ) {
				continue;
			}

			// Crop as WordPress keeps it: false, true or an anchor pair (a JSON list: a sparse array would encode as an object); any other array reads as true.
			$crop    = $size['crop'] ?? false;
			$anchor  = is_array( $crop ) ? array_values( $crop ) : [];
			$sizes[] = [
				'n' => (string) $name,
				'w' => $width,
				'h' => $height,
				'c' => 2 === count( $anchor ) && is_string( $anchor[0] ) && is_string( $anchor[1] ) ? $anchor : ! empty( $crop ),
			];
		}

		self::debug( 'img_sizes ' . count( $sizes ) . ' optimized size(s), ' . count( $skipped ) . ' skipped' );

		return $sizes;
	}

	/**
	 * Fetch the body of a JS or CSS sidecar the OptimaX service links, held to the rules the images are:
	 * an https QUIC.cloud URL (Img::normalize_cloud_url), no redirects, the same size bound, and the SHA-256 the service sent for it.
	 *
	 * @since 8.0
	 * @param string $url    Remote URL.
	 * @param mixed  $sha256 Hex SHA-256 of the body, or '' when the service sent none.
	 * @return string|false Body on success, false when refused or not verified.
	 */
	private function _fetch_con( $url, $sha256 ) {
		$url = Img::normalize_cloud_url( $url );
		if ( ! $url ) {
			return self::_bail( '❌ Refused sidecar URL: not an https QUIC.cloud artifact' );
		}
		// Logged without its query string: it may carry a token.
		$parts = wp_parse_url( $url );
		$label = $parts['host'] . ( ! empty( $parts['path'] ) ? $parts['path'] : '/' );

		$response = wp_safe_remote_get( $url, [ 'timeout' => 60, 'redirection' => 0, 'limit_response_size' => File::REMOTE_MAX_BYTES + 1 ] );
		if ( is_wp_error( $response ) ) {
			return self::_bail( 'Failed to fetch ' . $label . ': ' . $response->get_error_message() );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		if ( 200 !== $code || ! $body || File::REMOTE_MAX_BYTES < strlen( $body ) ) {
			return self::_bail( 'Unusable response [code] ' . $code . ' [bytes] ' . strlen( (string) $body ) . ' [url] ' . $label );
		}

		// The JS bundle runs on every OptimaX page: refuse it, and the used CSS, without a digest (Workers older than OX-qc_v4 send none).
		if ( '' === $sha256 ) {
			return self::_bail( '❌ Sidecar has no digest; refused [url] ' . $label );
		}
		if ( ! is_string( $sha256 ) || ! preg_match( '/^[a-f0-9]{64}$/iD', $sha256 ) || ! hash_equals( strtolower( $sha256 ), hash( 'sha256', $body ) ) ) {
			return self::_bail( '❌ Sidecar digest mismatch [url] ' . $label );
		}

		return $body;
	}

	/**
	 * Resolve an image to a local target and optional attachment hook context.
	 *
	 * @param array $img Cloud image entry.
	 * @return array|false `[ path, bound root, hook row ]`, or false.
	 */
	private function _image_target( $img ) {
		// `src` comes relative to the site root; both resolvers below need an absolute URL (a path finds no attachment, and cron may lack DOCUMENT_ROOT).
		if ( '/' === substr( $img['src'], 0, 1 ) && '//' !== substr( $img['src'], 0, 2 ) ) {
			$img['src'] = home_url( $img['src'] );
		}

		$post_id = attachment_url_to_postid( $img['src'] );
		if ( 0 < $post_id ) {
			$uploads  = wp_upload_dir();
			$base     = ! empty( $uploads['basedir'] ) ? trailingslashit( wp_normalize_path( $uploads['basedir'] ) ) : '';
			$attached = get_attached_file( $post_id, true );
			$attached = is_string( $attached ) ? wp_normalize_path( $attached ) : '';
			$url_path = wp_parse_url( $img['src'], PHP_URL_PATH );
			$filename = is_string( $url_path ) ? rawurldecode( basename( $url_path ) ) : '';
			if ( $base && 0 === strpos( $attached, $base ) && $filename ) {
				$dir   = dirname( substr( $attached, strlen( $base ) ) );
				$short = ( '.' === $dir ? '' : trailingslashit( $dir ) ) . $filename;
				$local = Img::normalize_cloud_path( $short ) === $short ? Img::local_file( apply_filters( 'litespeed_realpath', $base . $short ) ) : false;
				if ( $local && ( file_exists( $local[0] ) || $this->cls( 'Media' )->info( $short, $post_id ) ) ) {
					return [ $local[0], $local[1], (object) [ 'post_id' => $post_id, 'src' => $short ] ];
				}
			}
		}

		$local = Utility::is_internal_file( $img['src'] );
		$local = $local && ! empty( $local[0] ) ? Img::local_file( $local[0] ) : false;
		// Only into this site's uploads: never another subsite's files or a theme's.
		$uploads = wp_upload_dir();
		$root    = ! empty( $uploads['basedir'] ) ? File::bind_root( $uploads['basedir'] ) : false;
		return $local && $root && $root[0] === $local[1][0] ? [ $local[0], $local[1], false ] : false;
	}

	/**
	 * Save optimized HTML content.
	 *
	 * @param string $content    The optimized content.
	 * @param array  $assets     Files the HTML depends on, for its manifest (Optimax_Pages::save_assets()).
	 * @param string $queue_k    The queue key.
	 * @param bool   $is_mobile  Whether is mobile.
	 * @param string $is_nextgen Next-gen image format ('webp', 'avif', or '').
	 * @param array  $v          Queue item.
	 * @param bool   $synced     Whether the result is a sync of the version's build, not a build.
	 * @return bool
	 */
	private function _save_con( $content, $assets, $queue_k, $is_mobile, $is_nextgen, $v, $synced ) {
		$content = apply_filters( 'litespeed_optimax', $content, $queue_k );
		if ( ! is_string( $content ) ) {
			return false;
		}
		$content = File::remove_zero_space( $content );

		// Write to file
		$filecon_md5 = md5( $content );

		$filepath_prefix = $this->_build_filepath_prefix( 'optimax' );
		$static_file     = LITESPEED_STATIC_DIR . $filepath_prefix . $filecon_md5 . '.html';

		if ( ! File::save_atomic( $static_file, $content ) ) {
			return false;
		}

		// How and why it was made, for the list of optimized pages. A sync QUIC.cloud could not make comes back as a full build: rebuilt.
		$why              = isset( $v['why'] ) && in_array( $v['why'], Optimax_Pages::CAUSES, true ) ? $v['why'] : 'visit';
		$assets['detail'] = (string) ( $v['why_detail'] ?? '' );
		$assets['action'] = $synced ? 'synced_qc' : ( in_array( $why, [ 'sync', 'design' ], true ) ? 'rebuilt' : 'generated' );
		$assets['cause']  = $synced ? 'sync' : ( 'sync' === $why ? 'sync_failed' : $why );
		// Before the build goes live: _assets_intact() refuses a build without a manifest.
		if ( ! $this->cls( 'Optimax_Pages' )->save_assets( $filecon_md5, $assets ) ) {
			return false;
		}

		$url_tag = $v['url_tag'];
		$vary    = $v['vary'];
		self::debug2( "Save URL to file [file] $static_file [vary] $vary" );

		$data = $this->cls( 'Data' );
		$data->save_url( $url_tag, $vary, 'optimax', $filecon_md5, dirname( $static_file ), $is_mobile, $is_nextgen );
		if ( $filecon_md5 !== $data->load_url_file( $url_tag, $vary, 'optimax' ) ) {
			return false;
		}

		// Record the render the new build is compared with, so a page change made while it was being built is still seen.
		// QUIC.cloud's own read of the page is not comparable with a visitor's render: the one that showed a synced change is used, else the one the build was requested with.
		$pages                    = $this->cls( 'Optimax_Pages' );
		list( $kept, $from_sync ) = $pages->kept_render( $url_tag, $vary );
		// The fingerprint taken when the render was made: one taken now would read today's stylesheet files, and miss a CSS change made during the build.
		$fp = $kept['fp'];
		if ( ! $fp ) {
			// Without either render, the build's first serve records its own.
			$pages->drop_fingerprint( $url_tag, $vary );
		} else {
			// A synced result keeps the build's stylesheet copies, so the sheets whose change styled nothing read as those copies, as after a patch.
			if ( $synced && ! empty( $kept['same'] ) && isset( $fp['skel'] ) ) {
				$fp = Optimax_Sync::with_sheets( $fp, $kept['same'] );
			}
			$fp['base'] = Optimax_Pages::pack_base( $kept['render'] );
			$pages->save_fingerprint( $url_tag, $vary, $fp, 0 );
			// The builder stylesheets copied with that render become the build's copies when a real build is stored.
			if ( ! $synced ) {
				$pages->save_sheet_copies( $url_tag, $vary, $kept['css'] );
			}
			self::debug( 'sync: fingerprint recorded from the render ' . ( $from_sync ? 'that showed the synced change' : 'the build was requested with' ) );
		}
		Purge::add( self::page_tag( $url_tag ), true );
		// Evict a copy cached before the OptimaX tag existed.
		$this->cls( 'Purge' )->purge_url( $v['url'], true, true );
		return true;
	}

	/**
	 * Log why a request or result is refused, and return false.
	 *
	 * @since 8.0
	 * @param string $why Debug log line.
	 * @param bool   $log Whether to log it.
	 * @return false
	 */
	private static function _bail( $why, $log = true ) {
		$log && self::debug( $why );
		return false;
	}
}
