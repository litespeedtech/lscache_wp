<?php
/**
 * The crawler class
 *
 * @since      	1.1.0
 */
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

class Crawler extends Root {
	const LOG_TAG = '🕸️';

	const TYPE_REFRESH_MAP = 'refresh_map';
	const TYPE_EMPTY = 'empty';
	const TYPE_BLACKLIST_EMPTY = 'blacklist_empty';
	const TYPE_BLACKLIST_DEL = 'blacklist_del';
	const TYPE_BLACKLIST_ADD = 'blacklist_add';
	const TYPE_START = 'start';
	const TYPE_RESET = 'reset';

	const USER_AGENT = 'lscache_walker';
	const FAST_USER_AGENT = 'lscache_runner';
	const CHUNKS = 10000;

	private $_sitemeta = 'meta.data';
	private $_resetfile;
	private $_end_reason;
	private $_ncpu = 1;

	private $_crawler_conf = array(
		'cookies' => array(),
		'headers' => array(),
		'ua'	=> '',
	);
	private $_crawlers = array();
	private $_cur_threads = -1;
	private $_max_run_time;
	private $_cur_thread_time;
	private $_map_status_list = array(
		'H'	=> array(),
		'M'	=> array(),
		'B'	=> array(),
		'N'	=> array(),
	);
	protected $_summary;

	/**
	 * Initialize crawler, assign sitemap path
	 *
	 * @since    1.1.0
	 */
	public function __construct() {
		if ( is_multisite() ) {
			$this->_sitemeta = 'meta' . get_current_blog_id() . '.data';
		}

		$this->_resetfile = LITESPEED_STATIC_DIR . '/crawler/' . $this->_sitemeta . '.reset';

		$this->_summary = self::get_summary();

		$this->_ncpu = $this->_get_server_cpu();

		self::debug( 'Init w/ CPU cores=' . $this->_ncpu );
	}

	/**
	 * Try get server CPUs
	 * @since 5.2
	 */
	private function _get_server_cpu(){
		$cpuinfo_file = '/proc/cpuinfo';
		$setting_open_dir = ini_get('open_basedir');
		if ( $setting_open_dir ) return 1; // Server has limit

		try {
			if ( ! @is_file( $cpuinfo_file ) ) return 1;
		}
		catch ( \Exception $e ) {
			return 1;
		}

		$cpuinfo = file_get_contents( $cpuinfo_file );
		preg_match_all('/^processor/m', $cpuinfo, $matches);
		return count($matches[0]) ?: 1;
	}

	/**
	 * Check whether the current crawler is active/runable/useable/enabled/want it to work or not
	 *
	 * @since  4.3
	 */
	public function is_active( $curr ){
		$bypass_list = self::get_option( 'bypass_list' , array() );
		return ! in_array( $curr, $bypass_list );
	}

	/**
	 * Toggle the current crawler's activeness state, i.e., runable/useable/enabled/want it to work or not, and return the updated state
	 *
	 * @since  4.3
	 */
	public function toggle_activeness( $curr ) { // param type: int
		$bypass_list = self::get_option( 'bypass_list' , array() );
		if ( in_array( $curr, $bypass_list ) ) { // when the ith opt was off / in the bypassed list, turn it on / remove it from the list
		    unset( $bypass_list[ array_search( $curr, $bypass_list ) ] );
			$bypass_list = array_values( $bypass_list );
			self::update_option( 'bypass_list' , $bypass_list );
			return true;
		} else {        	// when the ith opt was on / not in the bypassed list, turn it off / add it to the list
			$bypass_list[] = ( int ) $curr;
			self::update_option( 'bypass_list' , $bypass_list );
			return false;
		}
	}

	/**
	 * Clear bypassed list
	 *
	 * @since  4.3
	 * @access public
	 */
	public function clear_disabled_list() {
		self::update_option( 'bypass_list', array() );

		$msg = __( 'Crawler disabled list is cleared! All crawlers are set to active! ', 'litespeed-cache' );
		Admin_Display::note( $msg );

		self::debug( 'All crawlers are set to active...... ' );
	}

	/**
	 * Overwride get_summary to init elements
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function get_summary( $field = false ) {
		$_default = array(
			'list_size'			=> 0,
			'last_update_time'	=> 0,
			'curr_crawler'		=> 0,
			'curr_crawler_beginning_time'	=> 0,
			'last_pos'			=> 0,
			'last_count'		=> 0,
			'last_crawled'		=> 0,
			'last_start_time'	=> 0,
			'last_status'		=> '',
			'is_running'		=> 0,
			'end_reason'		=> '',
			'meta_save_time'	=> 0,
			'pos_reset_check'	=> 0,
			'done'				=> 0,
			'this_full_beginning_time'	=> 0,
			'last_full_time_cost'		=> 0,
			'last_crawler_total_cost'	=> 0,
			'crawler_stats'		=> array(), // this will store all crawlers hit/miss crawl status
		);

		$summary = parent::get_summary();
		$summary = array_merge( $_default, $summary );

		if ( ! $field ) {
			return $summary;
		}

		if ( array_key_exists( $field, $summary ) ) {
			return $summary[ $field ];
		}

		return null;
	}

	/**
	 * Overwride save_summary
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function save_summary( $data = false, $reload = false, $overwrite = false ) {
		$instance = self::cls();
		$instance->_summary[ 'meta_save_time' ] = time();

		if ( ! $data ) {
			$data = $instance->_summary;
		}

		parent::save_summary( $data, $reload, $overwrite );

		File::save( LITESPEED_STATIC_DIR . '/crawler/' . $instance->_sitemeta, json_encode( $data ), true );
	}

	/**
	 * Proceed crawling
	 *
	 * @since    1.1.0
	 * @access public
	 */
	public static function start( $force = false ) {
		if ( ! Router::can_crawl() ) {
			self::debug( '......crawler is NOT allowed by the server admin......' );
			return false;
		}

		if ( $force ) {
			self::debug( '......crawler manually ran......' );
		}

		self::cls()->_crawl_data( $force );
	}

	/**
	 * Crawling start
	 *
	 * @since    1.1.0
	 * @access   private
	 */
	private function _crawl_data( $force ) {
		self::debug( '......crawler started......' );
		// for the first time running
		if ( ! $this->_summary || ! Data::cls()->tb_exist( 'crawler' ) || ! Data::cls()->tb_exist( 'crawler_blacklist' ) ) {
			$this->cls( 'Crawler_Map' )->gen();
		}

		// if finished last time, regenerate sitemap
		if ( $this->_summary['done'] === 'touchedEnd' ) {
			// check whole crawling interval
			$last_fnished_at = $this->_summary[ 'last_full_time_cost' ] + $this->_summary[ 'this_full_beginning_time' ];
			if ( ! $force && time() - $last_fnished_at < $this->conf( Base::O_CRAWLER_CRAWL_INTERVAL ) ) {
				self::debug( 'Cron abort: cache warmed already.' );
				// if not reach whole crawling interval, exit
				return;
			}
			self::debug( 'TouchedEnd. regenerate sitemap....' );
			$this->cls( 'Crawler_Map' )->gen();
		}

		$this->list_crawlers();

		// Skip the crawlers that in bypassed list
		while ( ! $this->is_active( $this->_summary[ 'curr_crawler' ] ) && $this->_summary[ 'curr_crawler' ] < count( $this->_crawlers ) ) {
			self::debug( 'Skipped the Crawler #' . $this->_summary[ 'curr_crawler' ] . ' ......' );
			$this->_summary[ 'curr_crawler' ]++;
		}
		if ( $this->_summary[ 'curr_crawler' ] >= count( $this->_crawlers ) ) {
			$this->_end_reason = 'end';
			$this->_terminate_running();
			return;
		}

		// In case crawlers are all done but not reload, reload it
		if ( empty( $this->_summary[ 'curr_crawler' ] ) || empty( $this->_crawlers[ $this->_summary[ 'curr_crawler' ] ] ) ) {
			$this->_summary[ 'curr_crawler' ] = 0;
			$this->_summary[ 'crawler_stats' ][ $this->_summary[ 'curr_crawler' ] ] = array();
		}

		$this->load_conf();

		$this->_engine_start();
	}

	/**
	 * Load conf before running crawler
	 *
	 * @since  3.0
	 * @access private
	 */
	private function load_conf() {
		$this->_crawler_conf[ 'base' ] = home_url();

		$current_crawler = $this->_crawlers[ $this->_summary[ 'curr_crawler' ] ];

		/**
		 * Set role simulation
		 * @since 1.9.1
		 */
		if ( ! empty( $current_crawler[ 'uid' ] ) ) {
			// Get role simulation vary name
			$vary_name = $this->cls( 'Vary' )->get_vary_name();
			$vary_val = $this->cls( 'Vary' )->finalize_default_vary( $current_crawler[ 'uid' ] );
			$this->_crawler_conf[ 'cookies' ][ $vary_name ] = $vary_val;
			$this->_crawler_conf[ 'cookies' ][ 'litespeed_role' ] = $current_crawler[ 'uid' ];
		}

		/**
		 * Check cookie crawler
		 * @since  2.8
		 */
		foreach ( $current_crawler as $k => $v ) {
			if ( strpos( $k, 'cookie:') !== 0 ) {
				continue;
			}

			if ( $v == '_null' ) {
				continue;
			}

			$this->_crawler_conf[ 'cookies' ][ substr( $k, 7 ) ] = $v;
		}

		/**
		 * Set WebP simulation
		 * @since  1.9.1
		 */
		if ( ! empty( $current_crawler[ 'webp' ] ) ) {
			$this->_crawler_conf[ 'headers' ][] = 'Accept: image/webp,*/*';
		}

		/**
		 * Set mobile crawler
		 * @since  2.8
		 */
		if ( ! empty( $current_crawler[ 'mobile' ] ) ) {
			$this->_crawler_conf[ 'ua' ] = 'Mobile iPhone';
		}

		/**
		 * Limit delay to use server setting
		 * @since 1.8.3
		 */
		$this->_crawler_conf[ 'run_delay' ] = $this->conf( Base::O_CRAWLER_USLEEP ); // microseconds
		if ( ! empty( $_SERVER[ Base::ENV_CRAWLER_USLEEP ] ) && $_SERVER[ Base::ENV_CRAWLER_USLEEP ] > $this->_crawler_conf[ 'run_delay' ] ) {
			$this->_crawler_conf[ 'run_delay' ] = $_SERVER[ Base::ENV_CRAWLER_USLEEP ];
		}

		$this->_crawler_conf[ 'run_duration' ] = $this->conf( Base::O_CRAWLER_RUN_DURATION );

		$this->_crawler_conf[ 'load_limit' ] = $this->conf( Base::O_CRAWLER_LOAD_LIMIT );
		if ( ! empty( $_SERVER[ Base::ENV_CRAWLER_LOAD_LIMIT_ENFORCE ] ) ) {
			$this->_crawler_conf[ 'load_limit' ] = $_SERVER[ Base::ENV_CRAWLER_LOAD_LIMIT_ENFORCE ];
		}
		elseif ( ! empty( $_SERVER[ Base::ENV_CRAWLER_LOAD_LIMIT ] ) && $_SERVER[ Base::ENV_CRAWLER_LOAD_LIMIT ] < $this->_crawler_conf[ 'load_limit' ] ) {
			$this->_crawler_conf[ 'load_limit' ] = $_SERVER[ Base::ENV_CRAWLER_LOAD_LIMIT ];
		}

	}

	/**
	 * Start crawler
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function _engine_start() {
		// check if is running
		if ( $this->_summary['is_running'] && time() - $this->_summary['is_running'] < $this->_crawler_conf[ 'run_duration' ] ) {
			$this->_end_reason = 'stopped';
			self::debug( 'The crawler is running.' );
			return;
		}

		// check current load
		$this->_adjust_current_threads();
		if ( $this->_cur_threads == 0 ) {
			$this->_end_reason = 'stopped_highload';
			self::debug( 'Stopped due to heavy load.' );
			return;
		}

		// log started time
		self::save_summary( array( 'last_start_time' => time() ) );

		// set time limit
		$maxTime = (int) ini_get( 'max_execution_time' );
		self::debug( 'ini_get max_execution_time=' . $maxTime );
		if ( $maxTime == 0 ) {
			$maxTime = 300; // hardlimit
		}
		else {
			$maxTime -= 5;
		}
		if ( $maxTime >= $this->_crawler_conf[ 'run_duration' ] ) {
			$maxTime = $this->_crawler_conf[ 'run_duration' ];
			self::debug( 'Use run_duration setting as max_execution_time=' . $maxTime );
		}
		elseif ( ini_set( 'max_execution_time', $this->_crawler_conf[ 'run_duration' ] + 15 ) !== false ) {
			$maxTime = $this->_crawler_conf[ 'run_duration' ];
			self::debug( 'ini_set max_execution_time=' . $maxTime );
		}
		self::debug( 'final max_execution_time=' . $maxTime );
		$this->_max_run_time = $maxTime + time();

		// mark running
		$this->_prepare_running();
		// run cralwer
		$this->_do_running();
		$this->_terminate_running();
	}

	/**
	 * Adjust threads dynamically
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function _adjust_current_threads() {
		/**
		 * If server is windows, exit
		 * @see  https://wordpress.org/support/topic/crawler-keeps-causing-crashes/
		 */
		if ( ! function_exists( 'sys_getloadavg' ) ) {
			self::debug( 'set threads=0 due to func sys_getloadavg not exist!' );
			$this->_cur_threads = 0;
			return;
		}

		$curload = sys_getloadavg();
		$curload = $curload[0];
		self::debug( 'Server load: ' . $curload );
		$curload /= $this->_ncpu;

		// $curload = 1;

		if ( $this->_cur_threads == -1 ) {
			// init
			if ( $curload > $this->_crawler_conf[ 'load_limit' ] ) {
				$curthreads = 0;
			}
			elseif ( $curload >= ( $this->_crawler_conf[ 'load_limit' ] - 1 ) ) {
				$curthreads = 1;
			}
			else {
				$curthreads = intval( $this->_crawler_conf[ 'load_limit' ] - $curload );
				if ( $curthreads > $this->conf( Base::O_CRAWLER_THREADS ) ) {
					$curthreads = $this->conf( Base::O_CRAWLER_THREADS );
				}
			}
		}
		else {
			// adjust
			$curthreads = $this->_cur_threads;
			if ( $curload >= $this->_crawler_conf[ 'load_limit' ] + 1 ) {
				sleep( 5 );  // sleep 5 secs
				if ( $curthreads >= 1 ) {
					$curthreads --;
				}
			}
			elseif ( $curload >= $this->_crawler_conf[ 'load_limit' ] ) {
				// if ( $curthreads > 1 ) {// if already 1, keep
					$curthreads --;
				// }
			}
			elseif ( ($curload + 1) < $this->_crawler_conf[ 'load_limit' ] ) {
				if ( $curthreads < $this->conf( Base::O_CRAWLER_THREADS ) ) {
					$curthreads ++;
				}
			}
		}

		// $log = 'set current threads = ' . $curthreads . ' previous=' . $this->_cur_threads
		// 	. ' max_allowed=' . $this->conf( Base::O_CRAWLER_THREADS ) . ' load_limit=' . $this->_crawler_conf[ 'load_limit' ] . ' current_load=' . $curload;

		$this->_cur_threads = $curthreads;
		$this->_cur_thread_time = time();
	}

	/**
	 * Mark running status
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function _prepare_running() {
		$this->_summary[ 'is_running' ] = time();
		$this->_summary[ 'done' ] = 0;// reset done status
		$this->_summary[ 'last_status' ] = 'prepare running';
		$this->_summary[ 'last_crawled' ] = 0;

		// Current crawler starttime mark
		if ( $this->_summary[ 'last_pos' ] == 0 ) {
			$this->_summary[ 'curr_crawler_beginning_time' ] = time();
		}

		if ( $this->_summary[ 'curr_crawler' ] == 0 && $this->_summary[ 'last_pos' ] == 0 ) {
			$this->_summary[ 'this_full_beginning_time' ] = time();
			$this->_summary[ 'list_size' ] = $this->cls( 'Crawler_Map' )->count_map();
		}

		if ( $this->_summary[ 'end_reason' ] == 'end' && $this->_summary[ 'last_pos' ] == 0 ) {
			$this->_summary[ 'crawler_stats' ][ $this->_summary[ 'curr_crawler' ] ] = array();
		}

		self::save_summary();
	}

	/**
	 * Run crawler
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function _do_running() {
		$options = $this->_get_curl_options( true );

		while ( $urlChunks = $this->cls( 'Crawler_Map' )->list_map( self::CHUNKS, $this->_summary['last_pos'] ) ) {
			// start crawling
			$urlChunks = array_chunk( $urlChunks, $this->_cur_threads );
			foreach ( $urlChunks as $rows ) {
				// multi curl
				$rets = $this->_multi_request( $rows, $options );

				// check result headers
				foreach ( $rows as $row ) {
					if ( empty( $rets[ $row[ 'id' ] ] ) ) { // If already in blacklist, no curl happened, no corresponding record
						continue;
					}

					// check response
					if ( $rets[ $row[ 'id' ] ][ 'code' ] == 428 ) { // HTTP/1.1 428 Precondition Required (need to test)
						$this->_end_reason = 'crawler_disabled';
						self::debug( 'crawler_disabled' );
						return;
					}

					$status = $this->_status_parse( $rets[ $row[ 'id' ] ][ 'header' ], $rets[ $row[ 'id' ] ][ 'code' ], $row[ 'url' ] ); // B or H or M or N(nocache)
					$this->_map_status_list[ $status ][ $row[ 'id' ] ] = array(
						'url'	=> $row[ 'url' ],
						'code' 	=> $rets[ $row[ 'id' ] ][ 'code' ], // 201 or 200 or 404
					);
					if ( empty( $this->_summary[ 'crawler_stats' ][ $this->_summary[ 'curr_crawler' ] ][ $status ] ) ) {
						$this->_summary[ 'crawler_stats' ][ $this->_summary[ 'curr_crawler' ] ][ $status ] = 0;
					}
					$this->_summary[ 'crawler_stats' ][ $this->_summary[ 'curr_crawler' ] ][ $status ]++;
				}

				// update offset position
				$_time = time();
				$this->_summary[ 'last_pos' ] += $this->_cur_threads;
				$this->_summary[ 'last_count' ] = $this->_cur_threads;
				$this->_summary[ 'last_crawled' ] += $this->_cur_threads;
				$this->_summary[ 'last_update_time' ] = $_time;
				$this->_summary[ 'last_status' ] = 'updated position';

				// check duration
				if ( $this->_summary[ 'last_update_time' ] > $this->_max_run_time ) {
					$this->_end_reason = 'stopped_maxtime';
					self::debug( 'Terminated due to maxtime' );
					return;
					// return __('Stopped due to exceeding defined Maximum Run Time', 'litespeed-cache');
				}

				// make sure at least each 10s save meta & map status once
				if ( $_time - $this->_summary[ 'meta_save_time' ] > 10 ) {
					$this->_map_status_list = $this->cls( 'Crawler_Map' )->save_map_status( $this->_map_status_list, $this->_summary[ 'curr_crawler' ] );
					self::save_summary();
				}

				// check if need to reset pos each 5s
				if ( $_time > $this->_summary[ 'pos_reset_check' ] ) {
					$this->_summary[ 'pos_reset_check' ] = $_time + 5;
					if ( file_exists( $this->_resetfile ) && unlink( $this->_resetfile ) ) {
						self::debug( 'Terminated due to reset file' );

						$this->_summary[ 'last_pos' ] = 0;
						$this->_summary[ 'curr_crawler' ] = 0;
						$this->_summary[ 'crawler_stats' ][ $this->_summary[ 'curr_crawler' ] ] = array();
						// reset done status
						$this->_summary[ 'done' ] = 0;
						$this->_summary[ 'this_full_beginning_time' ] = 0;
						$this->_end_reason = 'stopped_reset';
						return;
						// return __('Stopped due to reset meta position', 'litespeed-cache');
					}
				}

				// check loads
				if ( $this->_summary[ 'last_update_time' ] - $this->_cur_thread_time > 60 ) {
					$this->_adjust_current_threads();
					if ( $this->_cur_threads == 0 ) {
						$this->_end_reason = 'stopped_highload';
						self::debug( 'Terminated due to highload' );
						return;
						// return __('Stopped due to load over limit', 'litespeed-cache');
					}
				}

				$this->_summary[ 'last_status' ] = 'sleeping ' . $this->_crawler_conf[ 'run_delay' ] . 'ms';

				usleep( $this->_crawler_conf[ 'run_delay' ] );
			}
		}

		// All URLs are done for current crawler
		$this->_end_reason = 'end';
		$this->_summary[ 'crawler_stats' ][ $this->_summary[ 'curr_crawler' ] ][ 'W' ] = 0;
		self::debug( 'Crawler #' . $this->_summary['curr_crawler'] . ' touched end' );
	}

	/**
	 * Send multi curl requests
	 * If res=B, bypass request and won't return
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function _multi_request( $rows, $options ) {
		$mh = curl_multi_init();
		$curls = array();
		foreach ( $rows as $row ) {
			if ( substr( $row[ 'res' ], $this->_summary[ 'curr_crawler' ], 1 ) == 'B' ) {
				continue;
			}
			if ( substr( $row[ 'res' ], $this->_summary[ 'curr_crawler' ], 1 ) == 'N' ) {
				continue;
			}
			$curls[ $row[ 'id' ] ] = curl_init();

			// Append URL
			$url = $row[ 'url' ];
			if ( $this->conf( Base::O_CRAWLER_DROP_DOMAIN ) ) {
				$url = $this->_crawler_conf[ 'base' ] . $row[ 'url' ];
			}
			curl_setopt( $curls[ $row[ 'id' ] ], CURLOPT_URL, $url );
			self::debug( 'Crawling [url] ' . $url . ( $url == $row[ 'url' ] ? '' : ' [ori] ' . $row[ 'url' ] ) );

			curl_setopt_array( $curls[ $row[ 'id' ] ], $options );

			curl_multi_add_handle( $mh, $curls[ $row[ 'id' ] ] );
		}

		// execute curl
		if ( $curls ) {
			$last_start_time = null;
			do {
				curl_multi_exec( $mh, $last_start_time );
				if ( curl_multi_select( $mh ) == -1 ) {
					usleep( 1 );
				}
			} while ( $last_start_time > 0 );
		}

		// curl done
		$ret = array();
		foreach ( $rows as $row ) {
			if ( substr( $row[ 'res' ], $this->_summary[ 'curr_crawler' ], 1 ) == 'B' ) {
				continue;
			}
			if ( substr( $row[ 'res' ], $this->_summary[ 'curr_crawler' ], 1 ) == 'N' ) {
				continue;
			}

			$ch = $curls[ $row[ 'id' ] ];

			// Parse header
			$header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
			$content = curl_multi_getcontent( $ch );
			$header = substr( $content, 0, $header_size );

			$ret[ $row[ 'id' ] ] = array(
				'header' => $header,
				'code'	=> curl_getinfo( $ch, CURLINFO_HTTP_CODE ),
			);

			curl_multi_remove_handle( $mh, $ch );
			curl_close( $ch );
		}
		curl_multi_close( $mh );

		return $ret;
	}

	/**
	 * Check returned curl header to find if cached or not
	 *
	 * @since  2.0
	 * @access private
	 */
	private function _status_parse( $header, $code, $url ) {
		if ( $code == 201 ) {
			return 'H';
		}

		if ( stripos( $header, 'X-Litespeed-Cache-Control: no-cache' ) !== false ) {
			// If is from DIVI, taken as miss
			if ( defined( 'LITESPEED_CRAWLER_IGNORE_NONCACHEABLE' ) && LITESPEED_CRAWLER_IGNORE_NONCACHEABLE ) {
				return 'M';
			}

			// If blacklist is disabled
			if ( ( defined( 'LITESPEED_CRAWLER_DISABLE_BLOCKLIST' ) && LITESPEED_CRAWLER_DISABLE_BLOCKLIST ) || apply_filters( 'litespeed_crawler_disable_blocklist', '__return_false', $url ) ) {
				return 'M';
			}

			return 'N'; // Blacklist
		}

		$_cache_headers = array(
			'x-litespeed-cache',
			'x-lsadc-cache',
			'x-qc-cache',
		);

		foreach ( $_cache_headers as $_header ) {
			if ( stripos( $header, $_header ) !== false ) {
				if ( stripos( $header, $_header . ': miss' ) !== false ) {
					return 'M'; // Miss
				}
				return 'H'; // Hit
			}
		}

		return 'B'; // Blacklist
	}

	/**
	 * Get curl_options
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function _get_curl_options( $crawler_only = false ) {
		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER => true,
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_ENCODING => 'gzip',
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT => $this->conf( Base::O_CRAWLER_TIMEOUT ), // Larger timeout to avoid incorrect blacklist addition #900171
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_NOBODY => false,
			CURLOPT_HTTPHEADER => $this->_crawler_conf[ 'headers' ],
		);
		$options[ CURLOPT_HTTPHEADER ][] = 'Cache-Control: max-age=0';

		/**
		 * Try to enable http2 connection (only available since PHP7+)
		 * @since  1.9.1
		 * @since  2.2.7 Commented due to cause no-cache issue
		 * @since  2.9.1+ Fixed wrongly usage of CURL_HTTP_VERSION_1_1 const
		 */
		$options[ CURLOPT_HTTP_VERSION ] = CURL_HTTP_VERSION_1_1;
		// 	$options[ CURL_HTTP_VERSION_2 ] = 1;

		// IP resolve
		if ( $this->conf( Base::O_SERVER_IP ) ) {
			Utility::compatibility();
			if ( ( $this->conf( Base::O_CRAWLER_DROP_DOMAIN ) || ! $crawler_only ) && $this->_crawler_conf[ 'base' ] ) {
				// Resolve URL to IP
				$parsed_url = parse_url( $this->_crawler_conf[ 'base' ] );

				if ( ! empty( $parsed_url[ 'host' ] ) ) {
					$dom = $parsed_url[ 'host' ];
					$port = $parsed_url[ 'scheme' ] == 'https' ? '443' : '80';
					$url = $dom . ':' . $port . ':' . $this->conf( Base::O_SERVER_IP );

					$options[ CURLOPT_RESOLVE ] = array( $url );
					$options[ CURLOPT_DNS_USE_GLOBAL_CACHE ] = false;
				}
			}
		}

		// if is walker
		// $options[ CURLOPT_FRESH_CONNECT ] = true;

		// Referer
		if ( isset( $_SERVER[ 'HTTP_HOST' ] ) && isset( $_SERVER[ 'REQUEST_URI' ] ) ) {
			$options[ CURLOPT_REFERER ] = 'http://' . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ];
		}

		// User Agent
		if ( $crawler_only ) {
			if ( strpos( $this->_crawler_conf[ 'ua' ], Crawler::FAST_USER_AGENT ) !== 0 ) {
				$this->_crawler_conf[ 'ua' ] = Crawler::FAST_USER_AGENT . ' ' . $this->_crawler_conf[ 'ua' ];
			}
		}
		$options[ CURLOPT_USERAGENT ] = $this->_crawler_conf[ 'ua' ];

		/**
		 * Append hash to cookie for validation
		 * @since  1.9.1
		 */
		if ( $crawler_only ) {
			$this->_crawler_conf[ 'cookies' ][ 'litespeed_hash' ] = Router::get_hash();
		}

		// Cookies
		$cookies = array();
		foreach ( $this->_crawler_conf[ 'cookies' ] as $k => $v ) {
			if ( ! $v ) {
				continue;
			}
			$cookies[] = $k . '=' . urlencode( $v );
		}
		if ( $cookies ) {
			$options[ CURLOPT_COOKIE ] = implode( '; ', $cookies );
		}

		return $options;
	}

	/**
	 * Self curl to get HTML content
	 *
	 * @since  3.3
	 */
	public function self_curl( $url, $ua, $uid = false, $accept = false ) { // $accept not in use yet
		$this->_crawler_conf[ 'base' ] = home_url();
		$this->_crawler_conf[ 'ua' ] = $ua;
		if ( $accept ) {
			$this->_crawler_conf[ 'headers' ] = array( 'Accept: ' . $accept );
		}
		if ( $uid ) {
			$this->_crawler_conf[ 'cookies' ][ 'litespeed_role' ] = $uid;
			$this->_crawler_conf[ 'cookies' ][ 'litespeed_hash' ] = Router::get_hash();
		}

		$options = $this->_get_curl_options();
		$options[ CURLOPT_HEADER ] = false;
		$options[ CURLOPT_FOLLOWLOCATION ] = true;

		$ch = curl_init();
		curl_setopt_array( $ch, $options );
		curl_setopt( $ch, CURLOPT_URL, $url );
		$result = curl_exec( $ch );
		$code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		if ( $code != 200 ) {
			self::debug('❌ Response code is not 200 in self_curl() [code] ' . var_export( $code, true ) );
			return false;
		}

		return $result;
	}

	/**
	 * Terminate crawling
	 *
	 * @since  1.1.0
	 * @access private
	 */
	private function _terminate_running() {
		$this->_map_status_list = $this->cls( 'Crawler_Map' )->save_map_status( $this->_map_status_list, $this->_summary[ 'curr_crawler' ] );

		if ( $this->_end_reason == 'end' ) { // Current crawler is fully done
			// $end_reason = sprintf( __( 'Crawler %s reached end of sitemap file.', 'litespeed-cache' ), '#' . ( $this->_summary['curr_crawler'] + 1 ) );
			$this->_summary[ 'curr_crawler' ]++; // Jump to next cralwer
			// $this->_summary[ 'crawler_stats' ][ $this->_summary[ 'curr_crawler' ] ] = array(); // reset this at next crawl time
			$this->_summary[ 'last_pos' ] = 0;// reset last position
			$this->_summary[ 'last_crawler_total_cost' ] = time() - $this->_summary[ 'curr_crawler_beginning_time' ];
			$count_crawlers = count( $this->list_crawlers() );
			if ( $this->_summary[ 'curr_crawler' ] >= $count_crawlers ) {
				self::debug( '_terminate_running Touched end, whole crawled. Reload crawler!' );
				$this->_summary[ 'curr_crawler' ] = 0;
				// $this->_summary[ 'crawler_stats' ][ $this->_summary[ 'curr_crawler' ] ] = array();
				$this->_summary[ 'done' ] = 'touchedEnd';// log done status
				$this->_summary[ 'last_full_time_cost' ] = time() - $this->_summary[ 'this_full_beginning_time' ];
			}
		}
		$this->_summary[ 'last_status' ] = 'stopped';
		$this->_summary[ 'is_running' ] = 0;
		$this->_summary[ 'end_reason' ] = $this->_end_reason;
		self::save_summary();
	}

	/**
	 * List all crawlers ( tagA => [ valueA => titleA, ... ] ...)
	 *
	 * @since    1.9.1
	 * @access   public
	 */
	public function list_crawlers() {
		if ( $this->_crawlers ) {
			return $this->_crawlers;
		}

		$crawler_factors = array();

		// Add default Guest crawler
		$crawler_factors[ 'uid' ] = array( 0 => __( 'Guest', 'litespeed-cache' ) );

		// WebP on/off
		if ( $this->conf( Base::O_IMG_OPTM_WEBP_REPLACE ) ) {
			$crawler_factors[ 'webp' ] = array( 1 => 'WebP', 0 => '' );
		}

		// Guest Mode on/off
		if ( $this->conf( Base::O_GUEST ) ) {
			$vary_name = $this->cls( 'Vary' )->get_vary_name();
			$vary_val = 'guest_mode:1';
			if ( ! defined( 'LSCWP_LOG' ) ) {
				$vary_val = md5( $this->conf( Base::HASH ) . $vary_val );
			}
			$crawler_factors[ 'cookie:' . $vary_name ] = array( $vary_val => '', '_null' => '<font data-balloon-pos="up" aria-label="Guest Mode">👒</font>' );
		}

		// Mobile crawler
		if ( $this->conf( Base::O_CACHE_MOBILE ) ) {
			$crawler_factors[ 'mobile' ] = array( 1 => '<font data-balloon-pos="up" aria-label="Mobile">📱</font>', 0 => '' );
		}

		// Get roles set
		// List all roles
		foreach ( $this->conf( Base::O_CRAWLER_ROLES ) as $v ) {
			$role_title = '';
			$udata = get_userdata( $v );
			if ( isset( $udata->roles ) && is_array( $udata->roles ) ) {
				$tmp = array_values( $udata->roles );
				$role_title = array_shift( $tmp );
			}
			if ( ! $role_title ) {
				continue;
			}

			$crawler_factors[ 'uid' ][ $v ] = ucfirst( $role_title );
		}

		// Cookie crawler
		foreach ( $this->conf( Base::O_CRAWLER_COOKIES ) as $v ) {
			if ( empty( $v[ 'name' ] ) ) {
				continue;
			}

			$this_cookie_key = 'cookie:' . $v[ 'name' ];

			$crawler_factors[ $this_cookie_key ] = array();

			foreach ( $v[ 'vals' ] as $v2 ) {
				$crawler_factors[ $this_cookie_key ][ $v2 ] = $v2 == '_null' ? '' : '<font data-balloon-pos="up" aria-label="Cookie">🍪</font>' . esc_html( $v[ 'name' ] ) . '=' . esc_html( $v2 );
			}
		}

		// Crossing generate the crawler list
		$this->_crawlers = $this->_recursive_build_crawler( $crawler_factors );

		return $this->_crawlers;
	}

	/**
	 * Build a crawler list recursively
	 *
	 * @since 2.8
	 * @access private
	 */
	private function _recursive_build_crawler( $crawler_factors, $group = array(), $i = 0 ) {
		$current_factor = array_keys( $crawler_factors );
		$current_factor = $current_factor[ $i ];

		$if_touch_end = $i + 1 >= count( $crawler_factors );

		$final_list = array();

		foreach ( $crawler_factors[ $current_factor ] as $k => $v ) {

			// Don't alter $group bcos of loop usage
			$item = $group;
			$item[ 'title' ] = ! empty( $group[ 'title' ] ) ? $group[ 'title' ] : '';
			if ( $v ) {
				if ( $item[ 'title' ] ) {
					$item[ 'title' ] .= ' - ';
				}
				$item[ 'title' ] .= $v;
			}
			$item[ $current_factor ] = $k;

			if ( $if_touch_end ) {
				$final_list[] = $item;
			}
			else {
				// Inception: next layer
				$final_list = array_merge( $final_list, $this->_recursive_build_crawler( $crawler_factors, $item, $i + 1 ) );
			}

		}

		return $final_list;
	}

	/**
	 * Return crawler meta file
	 *
	 * @since    1.1.0
	 * @access public
	 */
	public function json_path() {
		if ( ! file_exists( LITESPEED_STATIC_DIR . '/crawler/' . $this->_sitemeta ) ) {
			return false;
		}

		return LITESPEED_STATIC_URL . '/crawler/' . $this->_sitemeta;
	}


	/**
	 * Create reset pos file
	 *
	 * @since    1.1.0
	 * @access public
	 */
	public function reset_pos() {
		File::save( $this->_resetfile, time() , true );

		self::save_summary( array( 'is_running' => 0 ) );
	}

	/**
	 * Display status based by matching crawlers order
	 *
	 * @since  3.0
	 * @access public
	 */
	public function display_status( $status_row, $reason_set ) {
		if ( ! $status_row ) {
			return '';
		}

		$_status_list = array(
			'-' => 'default',
			'M' => 'primary',
			'H' => 'success',
			'B' => 'danger',
			'N' => 'warning',
		);

		$reason_set = explode( ',', $reason_set );

		$status = '';
		foreach ( str_split( $status_row ) as $k => $v ) {
			$reason = $reason_set[ $k ];
			if ( $reason == 'Man' ) {
				$reason = __( 'Manually added to blocklist', 'litespeed-cache' );
			}
			if ( $reason == 'Existed' ) {
				$reason = __( 'Previously existed in blocklist', 'litespeed-cache' );
			}
			if ( $reason ) {
				$reason = 'data-balloon-pos="up" aria-label="' . $reason . '"';
			}
			$status .= '<i class="litespeed-dot litespeed-bg-' . $_status_list[ $v ] . '" ' . $reason . '>' . ( $k + 1 ) . '</i>';
		}

		return $status;
	}

	/**
	 * Output info and exit
	 *
	 * @since    1.1.0
	 * @access protected
	 * @param  string $error Error info
	 */
	protected function output($msg) {
		if ( defined('DOING_CRON') ) {
			echo $msg;
			// exit();
		}
		else {
			echo "<script>alert('" . htmlspecialchars($msg) . "');</script>";
			// exit;
		}
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  3.0
	 * @access public
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ( $type ) {
			case self::TYPE_REFRESH_MAP:
				$this->cls( 'Crawler_Map' )->gen();
				break;

			case self::TYPE_EMPTY:
				$this->cls( 'Crawler_Map' )->empty_map();
				break;

			case self::TYPE_BLACKLIST_EMPTY:
				$this->cls( 'Crawler_Map' )->blacklist_empty();
				break;

			case self::TYPE_BLACKLIST_DEL:
				if ( ! empty( $_GET[ 'id' ] ) ) {
					$this->cls( 'Crawler_Map' )->blacklist_del( $_GET[ 'id' ] );
				}
				break;

			case self::TYPE_BLACKLIST_ADD:
				if ( ! empty( $_GET[ 'id' ] ) ) {
					$this->cls( 'Crawler_Map' )->blacklist_add( $_GET[ 'id' ] );
				}
				break;

			// Handle the ajax request to proceed crawler manually by admin
			case self::TYPE_START:
				self::start( true );
				break;

			case self::TYPE_RESET:
				$this->reset_pos();
				break;

			default:
				break;
		}

		Admin::redirect();
	}

}
