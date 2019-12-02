<?php
/**
* LiteSpeed Crawler Class
*
* @since 1.1.0
*/

class Litespeed_Crawler
{
	private $_baseUrl ;
	private $_sitemap_file ;
	private $_meta_file ;
	private $_http2 = true ;
	private $_run_delay = 500 ;//microseconds
	private $_run_duration = 200 ;//seconds
	private $_threads_limit = 3 ;
	private $_load_limit = 1 ;
	private $_domain_ip = '' ;
	private $_ua = '' ;

	private $_curl_headers = array() ;
	private $_cookies = array() ;

	protected $_blacklist ;
	protected $_meta ;
	protected $_max_run_time ;
	protected $_cur_thread_time ;
	protected $_cur_threads = -1 ;

	const CHUNKS = 10000 ;
	const USER_AGENT = 'lscache_walker' ;
	const FAST_USER_AGENT = 'lscache_runner' ;

	/**
	 * Set load limit
	 *
	 * @since  1.1.0
	 * @access public
	 * @param  string $sitemap_file Sitemap file location
	 */
	public function __construct($sitemap_file)
	{
		$this->_sitemap_file = $sitemap_file ;
		$this->_meta_file = $this->_sitemap_file . '.meta' ;
	}

	/**
	 * Set User Agent
	 *
	 * @since  2.8
	 * @access public
	 */
	public function set_ua( $ua )
	{
		$this->_ua = $ua ;
	}

	/**
	 * Set http/2 option for curl request
	 *
	 * @since  2.0
	 * @access public
	 */
	public function set_http2( $is_enabled )
	{
		$this->_http2 = $is_enabled ;
	}

	/**
	 * Set headers for curl request
	 *
	 * @since  1.9.1
	 * @access public
	 */
	public function set_headers( $headers )
	{
		$this->_curl_headers = $headers ;
	}

	/**
	 * Set cookies for curl request
	 *
	 * @since  1.9.1
	 * @access public
	 */
	public function set_cookies( $cookies )
	{
		$this->_cookies = $cookies ;
	}

	/**
	 * Set domain ip
	 *
	 * @since  1.1.1
	 * @access public
	 * @param  string $val The domain's direct ip
	 */
	public function set_domain_ip($val)
	{
		$this->_domain_ip = $val ;
	}

	/**
	 * Set domain url
	 *
	 * @since  1.1.0
	 * @access public
	 * @param  string $val The prefix url
	 */
	public function set_base_url($val)
	{
		$this->_baseUrl = $val ;
	}

	/**
	 * Set run delay
	 *
	 * @since  1.1.0
	 * @access public
	 * @param  int $val Delay microseconds
	 */
	public function set_run_delay($val)
	{
		$this->_run_delay = $val ;
	}

	/**
	 * Set load limit
	 *
	 * @since  1.1.0
	 * @access public
	 * @param  int $val Run duration in seconds
	 */
	public function set_run_duration($val)
	{
		$this->_run_duration = $val ;
	}

	/**
	 * Set load limit
	 *
	 * @since  1.1.0
	 * @access public
	 * @param  int $val Threads limit in a time
	 */
	public function set_threads_limit($val)
	{
		$this->_threads_limit = $val ;
	}

	/**
	 * Set load limit
	 *
	 * @since  1.1.0
	 * @access public
	 * @param  int $val Server load limit to be checked before crawling
	 */
	public function set_load_limit($val)
	{
		$this->_load_limit = $val ;
	}

	/**
	 * Get if last crawler touched end
	 *
	 * @since  1.1.0
	 * @access public
	 * @return bool|int		False or last ended time
	 */
	public function get_done_status()
	{
		$this->read_meta() ;
		if ( $this->_meta['done'] === 'touchedEnd' ) {
			return $this->_meta['last_full_time_cost'] + $this->_meta['this_full_beginning_time'] ;
		}
		return false ;
	}

	/**
	 * Refresh list_size in meta
	 *
	 * @since  1.1.0
	 * @access public
	 * @return boolean True if succeeded, false otherwise
	 */
	public function refresh_list_size()
	{
		$this->read_meta() ;

		$this->_meta['list_size'] = Litespeed_File::count_lines($this->_sitemap_file) ;
		$this->save_meta() ;

		return false ;
	}

	/**
	 * Create reset pos file
	 *
	 * @since  1.1.0
	 * @access public
	 * @return mixed True or error message
	 */
	public function reset_pos()
	{
		return Litespeed_File::save( $this->_meta_file . '.reset', time() , true, false, false ) ;
	}

	/**
	 * Start crawler
	 *
	 * @since  1.1.0
	 * @access public
	 * @return string|bool crawled result
	 */
	public function engine_start()
	{
		$this->read_meta() ;
		if ( ! isset( $this->_meta ) ) {
			return $this->_return( sprintf(__('Cannot read meta file: %s', 'litespeed-cache'), $this->_meta_file) ) ;// NOTE: deprecated due to default_meta usage
		}

		// check if is running
		if ( $this->_meta['is_running'] && time() - $this->_meta['is_running'] < $this->_run_duration ) {
			return $this->_return(__('Oh look, there is already another LiteSpeed crawler running!', 'litespeed-cache')) ;
		}

		// check current load
		$this->_adjust_current_threads() ;
		if ( $this->_cur_threads == 0 ) {
			return $this->_return(__('Stopped due to load hit the maximum.', 'litespeed-cache')) ;
		}

		// log started time
		$this->_meta['last_start_time'] = time() ;
		$ret = $this->save_meta() ;
		if ( $ret !== true ) {
			return $this->_return($ret) ;
		}
		// set time limit
		$maxTime = (int) ini_get('max_execution_time') ;
		if ( $maxTime == 0 ) {
			$maxTime = 300 ; // hardlimit
		}
		else {
			$maxTime -= 5 ;
		}
		if ( $maxTime >= $this->_run_duration ) {
			$maxTime = $this->_run_duration ;
		}
		elseif ( ini_set('max_execution_time', $this->_run_duration + 15 ) !== false ) {
			$maxTime = $this->_run_duration ;
		}
		$this->_max_run_time = $maxTime + time() ;

		// mark running
		$this->_prepare_running() ;
		$curlOptions = $this->_get_curl_options() ;
		// run cralwer
		$end_reason = $this->_do_running($curlOptions) ;
		$this->_terminate_running($end_reason) ;

		return $this->_return($end_reason) ;
	}

	/**
	 * Check returned curl header to find if the status is 200 ok or not
	 *
	 * @since  2.0
	 * @access private
	 */
	private function _status_ok_and_cached( $headers )
	{
		if ( stripos( $headers, 'X-Litespeed-Cache-Control: no-cache' ) !== false ) {
			return false ;
		}

		$_http_status_ok_list = array(
			'HTTP/1.1 200 OK',
			'HTTP/1.1 201 Created',
			'HTTP/2 200',
			'HTTP/2 201',
		) ;

		foreach ( $_http_status_ok_list as $http_status ) {
			if ( stripos( $headers, $http_status ) !== false ) {
				return true ;
			}
		}

		return false ;
	}

	/**
	 * Run crawler
	 *
	 * @since  1.1.0
	 * @access private
	 * @param  array $curlOptions Curl options
	 * @return array              array('error', 'blacklist')
	 */
	private function _do_running($curlOptions)
	{
		while ( $urlChunks = Litespeed_File::read($this->_sitemap_file, $this->_meta['last_pos'], self::CHUNKS) ) {// get url list
			// start crawling
			$urlChunks = array_chunk($urlChunks, $this->_cur_threads) ;
			foreach ( $urlChunks as $urls ) {
				$urls = array_map('trim', $urls) ;
				// multi curl
				try {
					$rets = $this->_multi_request($urls, $curlOptions) ;
				} catch ( Exception $e ) {
					return sprintf(__('Stopped due to error when crawling urls %1$s : %2$s', 'litespeed-cache'), implode(' ', $urls) , $e->getMessage()) ;
				}

				// check result headers
				foreach ( $urls as $i => $url ) {
					// check response
					if ( stripos($rets[$i], "HTTP/1.1 428 Precondition Required") !== false ) {
						return __('Stopped: crawler disabled by the server admin', 'litespeed-cache') ;
					}

					if ( ! $this->_status_ok_and_cached( $rets[ $i ] ) ) {
						// Only default visitor crawler needs to add blacklist
						if ( $this->_meta[ 'curr_crawler' ] == 0 ) {
							$this->_blacklist[] = $url ;
						}
					}
				}

				// update offset position
				$_time = time() ;
				$this->_meta['last_pos'] += $i + 1 ;
				$this->_meta['last_count'] = $i + 1 ;
				$this->_meta['last_crawled'] += $i + 1 ;
				$this->_meta['last_update_time'] = $_time ;
				$this->_meta['last_status'] = 'updated position' ;

				// check duration
				if ( $this->_meta['last_update_time'] > $this->_max_run_time ) {
					return __('Stopped due to exceeding defined Maximum Run Time', 'litespeed-cache') ;
				}

				// make sure at least each 10s save meta once
				if ( $_time - $this->_meta['meta_save_time'] > 10 ) {
					$this->save_meta() ;
				}

				// check if need to reset pos each 5s
				if ( $_time > $this->_meta['pos_reset_check'] ) {
					$this->_meta['pos_reset_check'] = $_time + 5 ;
					if ( file_exists($this->_meta_file . '.reset') && unlink($this->_meta_file . '.reset') ) {
						$this->_meta['last_pos'] = 0 ;
						$this->_meta['curr_crawler'] = 0 ;
						// reset done status
						$this->_meta['done'] = 0 ;
						$this->_meta['this_full_beginning_time'] = 0 ;
						return __('Stopped due to reset meta position', 'litespeed-cache') ;
					}
				}

				// check loads
				if ( $this->_meta['last_update_time'] - $this->_cur_thread_time > 60 ) {
					$this->_adjust_current_threads() ;
					if ( $this->_cur_threads == 0 ) {
						return __('Stopped due to load over limit', 'litespeed-cache') ;
					}
				}

				$this->_meta['last_status'] = 'sleeping ' . $this->_run_delay . 'ms' ;

				usleep($this->_run_delay) ;
			}
		}

		return true ;
	}

	/**
	 * Mark running status
	 *
	 * @since  1.1.0
	 * @access protected
	 */
	protected function _prepare_running()
	{
		$this->_meta['is_running'] = time() ;
		$this->_meta['done'] = 0 ;// reset done status
		$this->_meta['last_status'] = 'prepare running' ;
		$this->_meta['last_crawled'] = 0 ;

		// Current crawler starttime mark
		if ( $this->_meta['last_pos'] == 0 ) {
			$this->_meta[ 'curr_crawler_beginning_time' ] = time() ;
		}

		if ( $this->_meta['curr_crawler'] == 0 && $this->_meta['last_pos'] == 0 ) {
			$this->_meta['this_full_beginning_time'] = time() ;
			$this->_meta['list_size'] = Litespeed_File::count_lines($this->_sitemap_file) ;
		}
		$this->save_meta() ;
	}

	/**
	 * Terminate crawling
	 *
	 * @since  1.1.0
	 * @access protected
	 * @param  string $end_reason The reason to terminate
	 */
	protected function _terminate_running($end_reason)
	{
		if ( $end_reason === true ) { // Current crawler is fully done
			$end_reason = sprintf( __( 'Crawler %s reached end of sitemap file.', 'litespeed-cache' ), '#' . ( $this->_meta['curr_crawler'] + 1 ) ) ;
			$this->_meta[ 'curr_crawler' ]++ ; // Jump to next cralwer
			$this->_meta[ 'last_pos' ] = 0 ;// reset last position
			$this->_meta[ 'last_crawler_total_cost' ] = time() - $this->_meta[ 'curr_crawler_beginning_time' ] ;
			$count_crawlers = LiteSpeed_Cache_Crawler::get_instance()->list_crawlers( true ) ;
			if ( $this->_meta[ 'curr_crawler' ] >= $count_crawlers ) {
				defined( 'LSCWP_LOG' ) && LiteSpeed_Cache_Log::debug( 'Crawler Lib: _terminate_running Touched end, whole crawled. Reload crawler!' ) ;
				$this->_meta[ 'curr_crawler' ] = 0 ;
				$this->_meta['done'] = 'touchedEnd' ;// log done status
				$this->_meta['last_full_time_cost'] = time() - $this->_meta['this_full_beginning_time'] ;
			}
		}
		$this->_meta['last_status'] = 'stopped' ;
		$this->_meta['is_running'] = 0 ;
		$this->_meta['end_reason'] = $end_reason ;
		$this->save_meta() ;
	}

	/**
	 * Return crawler result
	 *
	 * @since  1.1.0
	 * @access protected
	 * @param  string $end_reason Reason to end
	 * @return array             The results of returning
	 */
	protected function _return($end_reason)
	{
		return array(
			'error'		=> $end_reason === true ? false : $end_reason,
			'blacklist'	=> $this->_blacklist,
			'crawled'	=> $this->_meta['last_crawled'],
		) ;

	}

	/**
	 * Adjust threads dynamically
	 *
	 * @since  1.1.0
	 * @access protected
	 */
	protected function _adjust_current_threads()
	{
		$load = sys_getloadavg() ;
		$curload = 1 ;

		if ( $this->_cur_threads == -1 ) {
			// init
			if ( $curload > $this->_load_limit ) {
				$curthreads = 0 ;
			}
			elseif ( $curload >= ($this->_load_limit - 1) ) {
				$curthreads = 1 ;
			}
			else {
				$curthreads = intval($this->_load_limit - $curload) ;
				if ( $curthreads > $this->_threads_limit ) {
					$curthreads = $this->_threads_limit ;
				}
			}
		}
		else {
			// adjust
			$curthreads = $this->_cur_threads ;
			if ( $curload >= $this->_load_limit + 1 ) {
				sleep(5) ;  // sleep 5 secs
				if ( $curthreads >= 1 ) {
					$curthreads -- ;
				}
			}
			elseif ( $curload >= $this->_load_limit ) {
				if ( $curthreads > 1 ) {// if already 1, keep
					$curthreads -- ;
				}
			}
			elseif ( ($curload + 1) < $this->_load_limit ) {
				if ( $curthreads < $this->_threads_limit ) {
					$curthreads ++ ;
				}
			}
		}

		// $log = 'set current threads = ' . $curthreads . ' previous=' . $this->_cur_threads
		// 	. ' max_allowed=' . $this->_threads_limit . ' load_limit=' . $this->_load_limit . ' current_load=' . $curload;

		$this->_cur_threads = $curthreads ;
		$this->_cur_thread_time = time() ;
	}

	/**
	 * Send multi curl requests
	 *
	 * @since  1.1.0
	 * @access protected
	 * @param  array $urls    The url lists to send to
	 * @param  array $options Curl options
	 * @return array          Curl results
	 */
	protected function _multi_request($urls, $options)
	{
		$mh = curl_multi_init() ;
		$curls = array() ;
		foreach ($urls as $i => $url) {
			$curls[$i] = curl_init() ;
			curl_setopt($curls[$i], CURLOPT_URL, $this->_baseUrl . $url) ;
			curl_setopt_array($curls[$i], $options) ;
			curl_multi_add_handle($mh, $curls[$i]) ;
		}

		// execute curl
		$last_start_time = null ;
		do {
			curl_multi_exec($mh, $last_start_time) ;
			if ( curl_multi_select($mh) == -1 ) {
				usleep(1) ;
			}
		} while ($last_start_time > 0) ;

		// curl done
		$ret = array() ;
		foreach ($urls as $i => $url) {
			$thisCurl = $curls[$i] ;
			$ret[] = curl_multi_getcontent($thisCurl) ;

			curl_multi_remove_handle($mh, $thisCurl) ;
			curl_close($thisCurl) ;
		}
		curl_multi_close($mh) ;

		return $ret ;
	}

	/**
	 * Get curl_options
	 *
	 * @since  1.1.0
	 * @access private
	 * @return   options array
	 */
	private function _get_curl_options()
	{
		$referer = null ;
		if ( isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI']) ) {
			$referer = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ;
		}

		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER => true,
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_ENCODING => 'gzip',
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT => 30, // Larger timeout to avoid incorrect blacklist addition #900171
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_NOBODY => false,
			CURLOPT_HTTPHEADER => $this->_curl_headers,
		) ;
		$options[CURLOPT_HTTPHEADER][] = "Cache-Control: max-age=0" ;

		/**
		 * Try to enable http2 connection (only available since PHP7+)
		 * @since  1.9.1
		 * @since  2.2.7 Commented due to cause no-cache issue
		 * @since  2.9.1+ Fixed wrongly usage of CURL_HTTP_VERSION_1_1 const
		 */
		$options[ CURLOPT_HTTP_VERSION ] = CURL_HTTP_VERSION_1_1 ;
		// if ( defined( 'CURL_HTTP_VERSION_2' ) && $this->_http2 ) {
		// 	defined( 'LSCWP_LOG' ) && LiteSpeed_Cache_Log::debug( 'Crawler Lib: Enabled HTTP2' ) ;
		// 	$options[ CURL_HTTP_VERSION_2 ] = 1 ;
		// }
		// else {
		// }

		if ( strpos( $this->_ua, self::FAST_USER_AGENT ) !== 0 ) {
			$this->_ua = self::FAST_USER_AGENT . ' ' . $this->_ua ;
		}
		$options[CURLOPT_USERAGENT] = $this->_ua ;

		if ( $this->_domain_ip && $this->_baseUrl ) {
			$parsed_url = parse_url($this->_baseUrl) ;

			if ( !empty($parsed_url['host']) ) {
				// assign domain for curl
				$options[CURLOPT_HTTPHEADER][] = "Host: " . $parsed_url['host'] ;
				// replace domain with direct ip
				$parsed_url['host'] = $this->_domain_ip ;
				LiteSpeed_Cache_Utility::compatibility() ;
				$this->_baseUrl = http_build_url($parsed_url) ;
			}
		}

		// if is walker
		// $options[CURLOPT_FRESH_CONNECT] = true ;

		if ( !empty($referer) ) {
			$options[CURLOPT_REFERER] = $referer ;
		}

		/**
		 * Append hash to cookie for validation
		 * @since  1.9.1
		 */
		$hash = Litespeed_String::rrand( 6 ) ;
		update_option( LiteSpeed_Cache_Config::ITEM_CRAWLER_HASH, $hash ) ;
		$this->_cookies[ 'litespeed_hash' ] = $hash ;

		$cookies = array() ;
		foreach ( $this->_cookies as $k => $v ) {
			if ( ! $v ) {
				continue ;
			}
			$cookies[] = "$k=" . urlencode( $v ) ;
		}
		if ( $cookies ) {
			$options[ CURLOPT_COOKIE ] = implode( '; ', $cookies ) ;
		}

		return $options ;
	}

	/**
	 * Save existing meta
	 *
	 * @since  1.1.0
	 * @access public
	 * @return mixed True or error message
	 */
	public function save_meta()
	{
		$this->_meta[ 'meta_save_time' ] = time() ;

		$ret = Litespeed_File::save( $this->_meta_file, json_encode( $this->_meta ), false, false, false ) ;
		return $ret ;
	}

	/**
	 * Read existing meta
	 *
	 * @since  1.1.0
	 * @access public
	 * @return mixed True or error message
	 */
	public function read_meta()
	{
		// get current meta info
		$meta = Litespeed_File::read($this->_meta_file) ;

		if ( $meta && $meta = json_decode( $meta, true ) ) {
			// check if sitemap changed since last time
			if ( ! isset($meta['file_time']) || $meta['file_time'] < filemtime($this->_sitemap_file) ) {
				defined( 'LSCWP_LOG' ) && LiteSpeed_Cache_Log::debug( 'Crawler Lib: Sitemap timestamp changed, reset crawler' ) ;
				$meta['file_time'] = filemtime($this->_sitemap_file) ;
				$meta['last_pos'] = 0 ;
				$meta['curr_crawler'] = 0 ;
			}
		}

		if ( ! $meta ) {
			$meta = array() ;
		}

		$this->_meta = array_merge( $this->_default_meta(), $meta ) ;

		return $this->_meta ;
	}

	/**
	 * Get defaut meta to avoid missing key warning
	 *
	 * @since  1.9.1
	 * @access private
	 */
	private function _default_meta()
	{
		return array(
			'list_size'			=> Litespeed_File::count_lines($this->_sitemap_file),
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
		) ;
	}

}
