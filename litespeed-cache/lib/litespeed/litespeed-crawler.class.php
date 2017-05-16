<?php
/**
* LiteSpeed Crawler Class
* 
* @since 1.1.0
*/
class Litespeed_Crawler
{
	private $_baseUrl ;
	private $_sitemapFile ;
	private $_metaFile ;
	private $_runDelay = 500 ;//microseconds
	private $_runDuration = 200 ;//seconds
	private $_threadsLimit = 3 ;
	private $_loadLimit = 1 ;

	protected $_blacklist ;
	protected $_meta ;
	protected $_maxRunTime ;
	protected $_curThreadTime ;
	protected $_curThreads = -1 ;

	const CHUNKS = 10;//10000 ;

	public function __construct($sitemapFile)
	{
		$this->_sitemapFile = $sitemapFile ;
		$this->_metaFile = $this->_sitemapFile . '.meta' ;
	}

	public function setBaseUrl($val)
	{
		$this->_baseUrl = $val ;
	}

	public function setRunDelay($val)
	{
		$this->_runDelay = $val ;
	}

	public function setRunDuration($val)
	{
		$this->_runDuration = $val ;
	}

	public function setThreadsLimit($val)
	{
		$this->_threadsLimit = $val ;
	}

	public function setLoadLimit($val)
	{
		$this->_loadLimit = $val ;
	}

	/**
	 * Start crawler
	 * 
	 * @return string|bool crawled result
	 */
	public function engineStart()
	{
		$ret = $this->readMeta() ;
		if ( $ret !== true || ! $this->_meta ) {
			return $this->_return($ret) ;
		}

		// check if is running
		if ( $this->_meta['isRunning'] && time() - $this->_meta['isRunning'] < $this->_runDuration ) {
			return $this->_return(__('Oh look, there is already another LiteSpeed crawler here', 'litespeed-cache')) ;
		}

		// check current load
		$this->_adjustCurThreads() ;
		if ( $this->_curThreads == 0 ) {
			return $this->_return(__('Load over limit', 'litespeed-cache')) ;
		}

		// log started time
		$this->_meta['lastStartTime'] = time() ;
		$ret = $this->saveMeta() ;
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
		if ( $maxTime >= $this->_runDuration ) {
			$maxTime = $this->_runDuration ;
		}
		elseif ( ini_set('max_execution_time', $this->_runDuration + 15 ) !== false ) {
			$maxTime = $this->_runDuration ;
		}
		$this->_maxRunTime = $maxTime + time() ;

		// mark running
		$this->_prepareRunning() ;
		$curlOptions = $this->_getCurlOptions() ;
		// run cralwer
		$endReason = $this->_doRunning($curlOptions) ;
		$this->_terminateRunning($endReason) ;

		return $this->_return($endReason) ;
	}

	/**
	 * Run crawler
	 * 
	 * @param  array $curlOptions Curl options
	 * @return array              array('error', 'blacklist')
	 */
	private function _doRunning($curlOptions)
	{
		while ( $urlChunks = Litespeed_File::read($this->_sitemapFile, $this->_meta['lastPos'], self::CHUNKS) ) {// get url list
			// start crawling
			$urlChunks = array_chunk($urlChunks, $this->_curThreads) ;
			foreach ( $urlChunks as $urls ) {
				$urls = array_map('trim', $urls) ;
				// multi curl
				try {
					$rets = $this->_multiRequest($urls, $curlOptions) ;
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
				$this->_savePosition(count($urls)) ;

				// check duration
				if ( $this->_meta['lastUpdate'] > $this->_maxRunTime ) {
					return __('Stopped due to exceeding defined Maximum Run Time', 'litespeed-cache') ;
				}

				// check loads
				if ( $this->_meta['lastUpdate'] - $this->_curThreadTime > 60 ) {
					$this->_adjustCurThreads() ;
					if ( $this->_curThreads == 0 ) {
						return __('Load over limit', 'litespeed-cache') ;
					}
				}

				usleep($this->_runDelay) ;
			}
		}

		return true ;
	}

	/**
	 * Mark running status
	 */
	protected function _prepareRunning()
	{
		$this->_meta['isRunning'] = time() ;
		$this->saveMeta() ;
	}

	/**
	 * Terminate crawling
	 * 
	 * @param  string $endReason The reason to terminate
	 */
	protected function _terminateRunning($endReason)
	{
		if ( $endReason === true ) {
			$endReason = __('End of sitemap file', 'litespeed-cache') ;
			$this->_meta['lastPos'] = 0 ;// reset last position
		}
		$this->_meta['isRunning'] = 0 ;
		$this->_meta['endReason'] = $endReason ;
		$this->saveMeta() ;
	}

	/**
	 * Return crawler result
	 * @param  string $endReason Reason to end
	 * @return array             The results of returning
	 */
	protected function _return($endReason)
	{
		return array(
			'error'		=> $endReason === true ? false : $endReason,
			'blacklist'	=> $this->_blacklist,
		) ;

	}

	/**
	 * Save current position
	 * 
	 * @param  int $count Offsets to save based on current pos
	 */
	protected function _savePosition($count)
	{
		$now = time() ;
		$this->_meta['lastUpdate'] = $now ;
		$this->_meta['lastPos'] += $count ;
		$this->_meta['lastCount'] = $count ;
		$this->saveMeta() ;
	}

	/**
	 * Adjust threads dynamically
	 */
	protected function _adjustCurThreads()
	{
		$load = sys_getloadavg() ;
		$curload = 1 ;

		if ( $this->_curThreads == -1 ) {
			// init
			if ( $curload > $this->_loadLimit ) {
				$curthreads = 0 ;
			}
			elseif ( $curload >= ($this->_loadLimit - 1) ) {
				$curthreads = 1 ;
			}
			else {
				$curthreads = intval($this->_loadLimit - $curload) ;
				if ( $curthreads > $this->_threadsLimit ) {
					$curthreads = $this->_threadsLimit ;
				}
			}
		}
		else {
			// adjust
			$curthreads = $this->_curThreads ;
			if ( $curload >= $this->_loadLimit + 1 ) {
				sleep(5) ;  // sleep 5 secs
				if ( $curthreads >= 1 ) {
					$curthreads -- ;
				}
			}
			elseif ( $curload >= $this->_loadLimit ) {
				if ( $curthreads > 1 ) {// if already 1, keep
					$curthreads -- ;
				}
			}
			elseif ( ($curload + 1) < $this->_loadLimit ) {
				if ( $curthreads < $this->_threadsLimit ) {
					$curthreads ++ ;
				}
			}
		}

		// $log = 'set current threads = ' . $curthreads . ' previous=' . $this->_curThreads
		// 	. ' max_allowed=' . $this->_threadsLimit . ' load_limit=' . $this->_loadLimit . ' current_load=' . $curload;

		$this->_curThreads = $curthreads ;
		$this->_curThreadTime = time() ;
	}

	/**
	 * Send multi curl requests
	 * 
	 * @param  array $urls    The url lists to send to
	 * @param  array $options Curl options
	 * @return array          Curl results
	 */
	protected function _multiRequest($urls, $options)
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
		$lastStartTime = null ;
		do {
			curl_multi_exec($mh, $lastStartTime) ;
			if ( curl_multi_select($mh) == -1 ) {
				usleep(1) ;
			}
		} while ($lastStartTime > 0) ;

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
	private function _getCurlOptions($ua = '')
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
			CURLOPT_ENCODING => "",
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_NOBODY => true,
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
	public function saveMeta()
	{
		return Litespeed_File::save($this->_metaFile, json_encode($this->_meta)) ;
	}

	/**
	 * Read existing meta
	 * 
	 * @return mixed True or error message
	 */
	public function readMeta()
	{
		// get current meta info
		$meta = Litespeed_File::read($this->_metaFile) ;
		if ( $meta === false ) {
			return sprintf(__('Can not read meta file: %s', 'litespeed-cache'), $this->_metaFile) ;
		}

		if ( $meta ) {
			$meta = json_decode($meta, true) ;
			// check if sitemap changed since last time
			if ( ! isset($meta['fileTime']) || $meta['fileTime'] < filemtime($this->_sitemapFile) ) {
				$meta = false ;
			}
		}

		// initialize meta
		if ( ! $meta ) {
			$meta = array(
				'listSize'	=> Litespeed_File::count_lines($this->_sitemapFile),
				'fileTime'	=> filemtime($this->_sitemapFile),
				'lastUpdate'=> 0,
				'lastPos'=> 0,
				'lastCount'=> 0,
				'lastStartTime'=> 0,
				'isRunning'=> 0,
				'endReason'=> '',
			) ;
		}

		$this->_meta = $meta ;

		return true ;
	}

}