<?php
/**
 * The cron task class.
 *
 * @since   1.1.3
 * @package LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Schedules and runs LiteSpeed Cache background tasks.
 */
class Task extends Root {

	/**
	 * Tag for debug logs.
	 *
	 * @var string
	 */
	const LOG_TAG = '⏰';

	/**
	 * Map of option id => cron hook registration.
	 *
	 * @var array<string,array{name:string,hook:callable|string}>
	 */
	private static $_triggers = [
		Base::O_IMG_OPTM_CRON => [
			'name' => 'litespeed_task_imgoptm_pull',
			'hook' => 'LiteSpeed\Img_Optm::start_async_cron',
		], // always fetch immediately
		Base::O_OPTM_CSS_ASYNC => [
			'name' => 'litespeed_task_ccss',
			'hook' => 'LiteSpeed\CSS::cron_ccss',
		],
		Base::O_OPTM_UCSS => [
			'name' => 'litespeed_task_ucss',
			'hook' => 'LiteSpeed\UCSS::cron',
		],
		Base::O_MEDIA_VPI_CRON => [
			'name' => 'litespeed_task_vpi',
			'hook' => 'LiteSpeed\VPI::cron',
		],
		Base::O_MEDIA_PLACEHOLDER_RESP_ASYNC => [
			'name' => 'litespeed_task_lqip',
			'hook' => 'LiteSpeed\Placeholder::cron',
		],
		Base::O_DISCUSS_AVATAR_CRON => [
			'name' => 'litespeed_task_avatar',
			'hook' => 'LiteSpeed\Avatar::cron',
		],
		Base::O_IMG_OPTM_AUTO => [
			'name' => 'litespeed_task_imgoptm_req',
			'hook' => 'LiteSpeed\Img_Optm::cron_auto_request',
		],
		Base::O_GUEST => [
			'name' => 'litespeed_task_guest_sync',
			'hook' => 'LiteSpeed\Guest::cron',
		], // Daily sync Guest Mode IP/UA lists
		Base::O_CRAWLER => [
			'name' => 'litespeed_task_crawler',
			'hook' => 'LiteSpeed\Crawler::start_async_cron',
		], // Set crawler to last one to use above results
	];

	/**
	 * Options allowed to run for guest optimization.
	 *
	 * @var array<int,string>
	 */
	private static $_guest_options = [ Base::O_OPTM_CSS_ASYNC, Base::O_OPTM_UCSS, Base::O_MEDIA_VPI ];

	/**
	 * Schedule id for crawler.
	 *
	 * @var string
	 */
	const FILTER_CRAWLER = 'litespeed_crawl_filter';

	/**
	 * Schedule id for general tasks.
	 *
	 * @var string
	 */
	const FILTER = 'litespeed_filter';

	/**
	 * Keep all tasks in cron.
	 *
	 * @since 3.0
	 * @access public
	 * @return void
	 */
	public function init() {
		self::debug2( 'Init' );
		add_filter( 'cron_schedules', [ $this, 'lscache_cron_filter' ] );

		$guest_optm = $this->conf( Base::O_GUEST ) && $this->conf( Base::O_GUEST_OPTM );

		foreach ( self::$_triggers as $id => $trigger ) {
			if ( Base::O_IMG_OPTM_CRON === $id ) {
				if ( ! Img_Optm::need_pull() ) {
					continue;
				}
			} elseif ( ! $this->conf( $id ) ) {
				if ( ! $guest_optm || ! in_array( $id, self::$_guest_options, true ) ) {
					continue;
				}
			}

			// Special check for crawler.
			if ( Base::O_CRAWLER === $id ) {
				if ( ! Router::can_crawl() ) {
					continue;
				}

				add_filter( 'cron_schedules', [ $this, 'lscache_cron_filter_crawler' ] ); // phpcs:ignore WordPress.WP.CronInterval.ChangeDetected
			}

			if ( ! wp_next_scheduled( $trigger['name'] ) ) {
				self::debug( 'Cron hook register [name] ' . $trigger['name'] );

				// Determine schedule: crawler uses its own, guest uses daily, others use 15min
				if ( Base::O_CRAWLER === $id ) {
					$schedule = self::FILTER_CRAWLER;
				} elseif ( Base::O_GUEST === $id ) {
					$schedule = 'daily';
				} else {
					$schedule = self::FILTER;
				}

				wp_schedule_event( time(), $schedule, $trigger['name'] );
			}

			add_action( $trigger['name'], $trigger['hook'] );
		}
	}

	/**
	 * Handle all async noabort requests.
	 *
	 * @since 5.5
	 * @return void
	 */
	public static function async_litespeed_handler() {
		$hash_data = self::get_option( 'async_call-hash', [] );
		if ( ! $hash_data || ! is_array( $hash_data ) || empty( $hash_data['hash'] ) || empty( $hash_data['ts'] ) ) {
			self::debug( 'async_litespeed_handler no hash data', $hash_data );
			return;
		}

		$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 120 < time() - (int) $hash_data['ts'] || '' === $nonce || $nonce !== $hash_data['hash'] ) {
			self::debug( 'async_litespeed_handler nonce mismatch' );
			return;
		}
		self::delete_option( 'async_call-hash' );

		$type = Router::verify_type();
		self::debug( 'type=' . $type );

		// Don't lock up other requests while processing.
		session_write_close();

		switch ( $type ) {
			case 'crawler':
				Crawler::async_handler();
				break;
			case 'crawler_force':
				Crawler::async_handler( true );
				break;
			case 'imgoptm':
				Img_Optm::async_handler();
				break;
			case 'imgoptm_force':
				Img_Optm::async_handler( true );
				break;
			default:
				break;
		}
	}

	/**
	 * Async caller wrapper func.
	 *
	 * @since 5.5
	 *
	 * @param string $type Async operation type.
	 * @return void
	 */
	public static function async_call( $type ) {
		$hash = Str::rrand( 32 );
		self::update_option(
			'async_call-hash',
			[
				'hash' => $hash,
				'ts'   => time(),
			]
		);

		$args = [
			'timeout'   => 0.01,
			'blocking'  => false,
			'sslverify' => false,
			// 'cookies' => $_COOKIE,
		];

		$qs = [
			'action'      => 'async_litespeed',
			'nonce'       => $hash,
			Router::TYPE  => $type,
		];

		$url = add_query_arg( $qs, admin_url( 'admin-ajax.php' ) );
		self::debug( 'async call to ' . $url );
		wp_safe_remote_post( esc_url_raw( $url ), $args );
	}

	/**
	 * Clean all potential existing crons.
	 *
	 * @since 3.0
	 * @access public
	 * @return void
	 */
	public static function destroy() {
		Utility::compatibility();
		array_map( 'wp_clear_scheduled_hook', array_column( self::$_triggers, 'name' ) );
	}

	/**
	 * Try to clean the crons if disabled.
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $id Option id of cron trigger.
	 * @return void
	 */
	public function try_clean( $id ) {
		if ( $id && ! empty( self::$_triggers[ $id ] ) ) {
			if ( ! $this->conf( $id ) || ( Base::O_CRAWLER === $id && ! Router::can_crawl() ) ) {
				self::debug( 'Cron clear [id] ' . $id . ' [hook] ' . self::$_triggers[ $id ]['name'] );
				wp_clear_scheduled_hook( self::$_triggers[ $id ]['name'] );
			}
			return;
		}

		self::debug( '❌ Unknown cron [id] ' . $id );
	}

	/**
	 * Register cron interval for general tasks.
	 *
	 * @since 1.6.1
	 * @access public
	 *
	 * @param array $schedules Existing schedules.
	 * @return array
	 */
	public function lscache_cron_filter( $schedules ) {
		if ( ! array_key_exists( self::FILTER, $schedules ) ) {
			$schedules[ self::FILTER ] = [
				'interval' => 900,
				'display'  => __( 'Every 15 Minutes', 'litespeed-cache' ),
			];
		}
		return $schedules;
	}

	/**
	 * Register cron interval for crawler.
	 *
	 * @since 1.1.0
	 * @access public
	 *
	 * @param array $schedules Existing schedules.
	 * @return array
	 */
	public function lscache_cron_filter_crawler( $schedules ) {
		$crawler_run_interval = defined( 'LITESPEED_CRAWLER_RUN_INTERVAL' ) ? (int) constant( 'LITESPEED_CRAWLER_RUN_INTERVAL' ) : 600;

		if ( ! array_key_exists( self::FILTER_CRAWLER, $schedules ) ) {
			$schedules[ self::FILTER_CRAWLER ] = [
				'interval' => $crawler_run_interval,
				'display'  => __( 'LiteSpeed Crawler Cron', 'litespeed-cache' ),
			];
		}
		return $schedules;
	}
}
