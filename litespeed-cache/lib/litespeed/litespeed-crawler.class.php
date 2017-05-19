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
	private $_run_delay = 500 ;//microseconds
	private $_run_duration = 200 ;//seconds
	private $_threads_limit = 3 ;
	private $_load_limit = 1 ;

	protected $_blacklist ;
	protected $_meta ;
	protected $_max_run_time ;
	protected $_cur_thread_time ;
	protected $_cur_threads = -1 ;

	const CHUNKS = 10;//10000 ;

	public function __construct($sitemap_file)
	{
		$this->_sitemap_file = $sitemap_file ;
		$this->_meta_file = $this->_sitemap_file . '.meta' ;
	}

	public function set_base_url($val)
	{
		$this->_baseUrl = $val ;
	}

	public function set_run_delay($val)
	{
		$this->_run_delay = $val ;
	}

	public function set_run_duration($val)
	{
		$this->_run_duration = $val ;
	}

	public function set_threads_limit($val)
	{
		$this->_threads_limit = $val ;
	}

	public function set_load_limit($val)
	{
		$this->_load_limit = $val ;
	}

	/**
	 * Get if last crawler touched end
	 * 
	 * @return bool
	 */
	public function get_done_status()
	{
		if ( $this->read_meta() === true && $this->_meta['done'] === 'touchedEnd' ) {
			return true ;
		}
		return false ;
	}

	/**
	 * Create reset pos file
	 * 
	 * @return mixed True or error message
	 */
	public function reset_pos()
	{
		return Litespeed_File::save( $this->_meta_file . '.reset', time() , true) ;
	}

	/**
	 * Start crawler
	 * 
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
			return $this->_return(__('Oh look, there is already another LiteSpeed crawler here', 'litespeed-cache')) ;
		}

		// check current load
		$this->_adjust_current_threads() ;
		if ( $this->_cur_threads == 0 ) {
			return $this->_return(__('Load over limit', 'litespeed-cache')) ;
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
					return 'Error when crawling url ' . implode(' ', $urls) . ' : ' . $e->getMessage() ;
				}

				// check result headers
				foreach ( $urls as $i => $url ) {
					// check response
					if ( stripos(strtolower($rets[$i]), "max-age=0") !== false ) {//todo: check x-litespeed cache
						$this->_blacklist[] = $url ;
					}
					elseif ( stripos(strtolower($rets[$i]), "HTTP/1.1 200 OK") === false ){
						$this->_blacklist[] = $url ;
					}
				}

				// update offset position
				$this->_meta['last_pos'] += $i + 1 ;
				$this->_meta['last_count'] = $i + 1 ;
				$this->_meta['last_update'] = time() ;
				$this->_meta['last_status'] = 'updated position' ;

				// check duration
				if ( $this->_meta['last_update'] > $this->_max_run_time ) {
					return __('Stopped due to exceeding defined Maximum Run Time', 'litespeed-cache') ;
				}

				// make sure at least each 20s save meta once
				if ( time() - $this->_meta['meta_save_time'] > 10 ) {
					$this->save_meta() ;
				}

				// check loads
				if ( $this->_meta['last_update'] - $this->_cur_thread_time > 60 ) {
					$this->_adjust_current_threads() ;
					if ( $this->_cur_threads == 0 ) {
						return __('Load over limit', 'litespeed-cache') ;
					}
				}

				// check if need to reset pos
				if ( file_exists($this->_meta_file . '.reset') && unlink($this->_meta_file . '.reset') ) {
					$this->_meta['last_pos'] = 0 ;
					return __('Stopped due to reset meta position', 'litespeed-cache') ;
				}

				$this->_meta['last_status'] = 'sleeping ' . $this->_run_delay . 'ms' ;

				usleep($this->_run_delay) ;
			}
		}

		return true ;
	}

	/**
	 * Mark running status
	 */
	protected function _prepare_running()
	{
		$this->_meta['is_running'] = time() ;
		$this->_meta['done'] = 0 ;// reset done status
		$this->_meta['last_status'] = 'prepare running' ;
		if ( $this->_meta['last_pos'] == 0 ) {
			$this->_meta['this_full_beginning_time'] = time() ;
		}
		$this->save_meta() ;
	}

	/**
	 * Terminate crawling
	 * 
	 * @param  string $end_reason The reason to terminate
	 */
	protected function _terminate_running($end_reason)
	{
		if ( $end_reason === true ) {
			$end_reason = __('End of sitemap file', 'litespeed-cache') ;
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
	 * @param  string $end_reason Reason to end
	 * @return array             The results of returning
	 */
	protected function _return($end_reason)
	{
		return array(
			'error'		=> $end_reason === true ? false : $end_reason,
			'blacklist'	=> $this->_blacklist,
		) ;

	}

	/**
	 * Adjust threads dynamically
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
	* @param    string $ua as user-agent
	* @return   options array
	* @access   private
	*/
	private function _get_curl_options($ua = '')
	{
		$referer = null ;
		if ( isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI']) ) {
			$referer = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ;
		}

		$headers = array() ;

		if ( $ua != '' ) {
			$headers[] = 'User-Agent: '. $ua ;
		}

		$headers[] = "Cache-Control: max-age=0" ;

		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER => true,
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_ENCODING => 'gzip',
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_NOBODY => false,
			CURL_HTTP_VERSION_1_1 => 1,
			CURLOPT_HTTPHEADER => $headers
		) ;

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
	 * @return mixed True or error message
	 */
	public function save_meta()
	{
		$this->_meta['meta_save_time'] = time() ;

		$ret = Litespeed_File::save($this->_meta_file, json_encode($this->_meta)) ;
		return $ret ;
	}

	/**
	 * Read existing meta
	 * 
	 * @return mixed True or error message
	 */
	public function read_meta()
	{
		// get current meta info
		$meta = Litespeed_File::read($this->_meta_file) ;
		if ( $meta === false ) {
			return sprintf(__('Can not read meta file: %s', 'litespeed-cache'), $this->_meta_file) ;
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
				'last_update'		=> 0,
				'last_pos'			=> 0,
				'last_count'		=> 0,
				'last_start_time'	=> 0,
				'last_status'		=> '',
				'is_running'		=> 0,
				'end_reason'		=> '',
				'meta_save_time'	=> 0,
				'done'				=> 0,
				'this_full_beginning_time'	=> 0,
				'last_full_time_cost'		=> 0,
			) ;
		}

		$this->_meta = $meta ;

		return true ;
	}

}