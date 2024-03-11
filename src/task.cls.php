<?php

/**
 * The cron task class.
 *
 * @since      	1.1.3
 * @since  		1.5 Moved into /inc
 */

namespace LiteSpeed;

defined('WPINC') || exit();

class Task extends Root
{
	const LOG_TAG = '⏰';
	private static $_triggers = array(
		Base::O_IMG_OPTM_CRON => array('name' => 'litespeed_task_imgoptm_pull', 'hook' => 'LiteSpeed\Img_Optm::start_async_cron'), // always fetch immediately
		Base::O_OPTM_CSS_ASYNC => array('name' => 'litespeed_task_ccss', 'hook' => 'LiteSpeed\CSS::cron_ccss'),
		Base::O_OPTM_UCSS => array('name' => 'litespeed_task_ucss', 'hook' => 'LiteSpeed\UCSS::cron'),
		Base::O_MEDIA_VPI_CRON => array('name' => 'litespeed_task_vpi', 'hook' => 'LiteSpeed\VPI::cron'),
		Base::O_MEDIA_PLACEHOLDER_RESP_ASYNC => array('name' => 'litespeed_task_lqip', 'hook' => 'LiteSpeed\Placeholder::cron'),
		Base::O_DISCUSS_AVATAR_CRON => array('name' => 'litespeed_task_avatar', 'hook' => 'LiteSpeed\Avatar::cron'),
		Base::O_IMG_OPTM_AUTO => array('name' => 'litespeed_task_imgoptm_req', 'hook' => 'LiteSpeed\Img_Optm::cron_auto_request'),
		Base::O_CRAWLER => array('name' => 'litespeed_task_crawler', 'hook' => 'LiteSpeed\Crawler::start_async_cron'), // Set crawler to last one to use above results
	);

	private static $_guest_options = array(Base::O_OPTM_CSS_ASYNC, Base::O_OPTM_UCSS, Base::O_MEDIA_VPI);

	const FITLER_CRAWLER = 'litespeed_crawl_filter';
	const FITLER = 'litespeed_filter';

	/**
	 * Keep all tasks in cron
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init()
	{
		self::debug2('Init');
		add_filter('cron_schedules', array($this, 'lscache_cron_filter'));

		$guest_optm = $this->conf(Base::O_GUEST) && $this->conf(Base::O_GUEST_OPTM);

		foreach (self::$_triggers as $id => $trigger) {
			if (!$this->conf($id)) {
				if (!$guest_optm || !in_array($id, self::$_guest_options)) {
					continue;
				}
			}

			// Special check for crawler
			if ($id == Base::O_CRAWLER) {
				if (!Router::can_crawl()) {
					continue;
				}

				add_filter('cron_schedules', array($this, 'lscache_cron_filter_crawler'));
			}

			if (!wp_next_scheduled($trigger['name'])) {
				self::debug('Cron hook register [name] ' . $trigger['name']);

				wp_schedule_event(time(), $id == Base::O_CRAWLER ? self::FITLER_CRAWLER : self::FITLER, $trigger['name']);
			}

			add_action($trigger['name'], $trigger['hook']);
		}
	}

	/**
	 * Handle all async noabort requests
	 *
	 * @since 5.5
	 */
	public static function async_litespeed_handler()
	{
		$type = Router::verify_type();

		self::debug('type=' . $type);

		// Don't lock up other requests while processing
		session_write_close();
		switch ($type) {
			case 'crawler':
				Crawler::async_handler();
				break;
			case 'crawler_force':
				Crawler::async_handler(true);
				break;
			case 'imgoptm':
				Img_Optm::async_handler();
				break;
			case 'imgoptm_force':
				Img_Optm::async_handler(true);
				break;
			default:
		}
	}

	/**
	 * Async caller wrapper func
	 *
	 * @since 5.5
	 */
	public static function async_call($type)
	{
		$args = array(
			'timeout' => 0.01,
			'blocking' => false,
			'sslverify' => false,
			// 'cookies'   => $_COOKIE,
		);
		$qs = array(
			'action' => 'async_litespeed',
			'nonce' => wp_create_nonce('async_litespeed'),
			Router::TYPE => $type,
		);
		$url = add_query_arg($qs, admin_url('admin-ajax.php'));
		self::debug('async call to ' . $url);
		wp_remote_post(esc_url_raw($url), $args);
	}

	/**
	 * Clean all potential existing crons
	 *
	 * @since 3.0
	 * @access public
	 */
	public static function destroy()
	{
		Utility::compatibility();
		array_map('wp_clear_scheduled_hook', array_column(self::$_triggers, 'name'));
	}

	/**
	 * Try to clean the crons if disabled
	 *
	 * @since 3.0
	 * @access public
	 */
	public function try_clean($id)
	{
		// Clean v2's leftover cron ( will remove in v3.1 )
		// foreach ( wp_get_ready_cron_jobs() as $hooks ) {
		// 	foreach ( $hooks as $hook => $v ) {
		// 		if ( strpos( $hook, 'litespeed_' ) === 0 && ( substr( $hook, -8 ) === '_trigger' || strpos( $hook, 'litespeed_task_' ) !== 0 ) ) {
		// 			self::debug( 'Cron clear legacy [hook] ' . $hook );
		// 			wp_clear_scheduled_hook( $hook );
		// 		}
		// 	}
		// }

		if ($id && !empty(self::$_triggers[$id])) {
			if (!$this->conf($id) || ($id == Base::O_CRAWLER && !Router::can_crawl())) {
				self::debug('Cron clear [id] ' . $id . ' [hook] ' . self::$_triggers[$id]['name']);
				wp_clear_scheduled_hook(self::$_triggers[$id]['name']);
			}
			return;
		}

		self::debug('❌ Unknown cron [id] ' . $id);
	}

	/**
	 * Register cron interval imgoptm
	 *
	 * @since 1.6.1
	 * @access public
	 */
	public function lscache_cron_filter($schedules)
	{
		if (!array_key_exists(self::FITLER, $schedules)) {
			$schedules[self::FITLER] = array(
				'interval' => 60,
				'display' => __('Every Minute', 'litespeed-cache'),
			);
		}
		return $schedules;
	}

	/**
	 * Register cron interval
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public function lscache_cron_filter_crawler($schedules)
	{
		$interval = $this->conf(Base::O_CRAWLER_RUN_INTERVAL);
		// $wp_schedules = wp_get_schedules();
		if (!array_key_exists(self::FITLER_CRAWLER, $schedules)) {
			// 	self::debug('Crawler cron log: cron filter '.$interval.' added');
			$schedules[self::FITLER_CRAWLER] = array(
				'interval' => $interval,
				'display' => __('LiteSpeed Crawler Cron', 'litespeed-cache'),
			);
		}
		return $schedules;
	}
}
