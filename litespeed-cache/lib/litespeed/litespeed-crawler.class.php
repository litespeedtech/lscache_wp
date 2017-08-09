<?php
/**
* LiteSpeed Crawler Class
*
* @since 1.1.0
*/
require_once LSWCP_DIR . 'lib/litespeed-php-compatibility.func.php' ;

class Litespeed_Crawler
{
	private $_baseUrl ;
	private $_sitemap_file ;
	private $_meta_file ;
	private $_run_delay = 500 ;//microseconds
	private $_run_duration = 200 ;//seconds
	private $_threads_limit = 3 ;
	private $_load_limit = 1 ;
	private $_domain_ip = '' ;

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
		if ( $this->read_meta() === true && $this->_meta['done'] === 'touchedEnd' ) {
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
		if ( $this->read_meta() === true ) {
			$this->_meta['list_size'] = Litespeed_File::count_lines($this->_sitemap_file) ;
			$this->save_meta() ;
		}
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
		$ret = $this->read_meta() ;
		if ( $ret !== true || ! $this->_meta ) {
			return $this->_return($ret) ;
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
					elseif ( stripos($rets[$i], "X-Litespeed-Cache-Control: no-cache") !== false ) {
						$this->_blacklist[] = $url ;
					}
					elseif ( stripos($rets[$i], "HTTP/1.1 200 OK") === false && stripos($rets[$i], "HTTP/1.1 201 Created") === false ){
						$this->_blacklist[] = $url ;
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
		if ( $this->_meta['last_pos'] == 0 ) {
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
		if ( $end_reason === true ) {
			$end_reason = __('Reached end of sitemap file. Crawling completed.', 'litespeed-cache') ;
			$this->_meta['last_pos'] = 0 ;// reset last position
			$this->_meta['done'] = 'touchedEnd' ;// log done status
			$this->_meta['last_full_time_cost'] = time() - $this->_meta['this_full_beginning_time'] ;
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
	 * @param    string $ua as user-agent
	 * @return   options array
	 */
	private function _get_curl_options($ua = '')
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
			CURLOPT_TIMEOUT => 10,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_NOBODY => false,
			CURL_HTTP_VERSION_1_1 => 1,
			CURLOPT_HTTPHEADER => array(),
		) ;
		$options[CURLOPT_HTTPHEADER][] = "Cache-Control: max-age=0" ;

		if ( ! $ua ) {
			$ua = self::FAST_USER_AGENT ;
		}
		$options[CURLOPT_USERAGENT] = $ua ;

		if ( $this->_domain_ip && $this->_baseUrl ) {
			$parsed_url = parse_url($this->_baseUrl) ;

			if ( !empty($parsed_url['host']) ) {
				// assign domain for curl
				$options[CURLOPT_HTTPHEADER][] = "Host: " . $parsed_url['host'] ;
				// replace domain with direct ip
				$parsed_url['host'] = $this->_domain_ip ;
				$this->_baseUrl = http_build_url($parsed_url) ;
			}
		}

		// if is walker
		// $options[CURLOPT_FRESH_CONNECT] = true ;

		if ( !empty($referer) ) {
			$options[CURLOPT_REFERER] = $referer ;
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
		if ( $meta === false ) {
			return sprintf(__('Cannot read meta file: %s', 'litespeed-cache'), $this->_meta_file) ;
		}

		if ( $meta && $meta = json_decode($meta, true) ) {
			// check if sitemap changed since last time
			if ( ! isset($meta['file_time']) || $meta['file_time'] < filemtime($this->_sitemap_file) ) {
				$meta['file_time'] = filemtime($this->_sitemap_file) ;
				$meta['last_pos'] = 0 ;
			}
		}
		else {
			// initialize meta
			$meta = array(
				'list_size'			=> Litespeed_File::count_lines($this->_sitemap_file),
				'last_update_time'	=> 0,
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
			) ;
		}

		$this->_meta = $meta ;

		return true ;
	}

}
