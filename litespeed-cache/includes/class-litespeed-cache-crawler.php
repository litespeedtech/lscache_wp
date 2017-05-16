<?php

/**
 * The crawler class
 *
 *
 * @since      1.1.0
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/includes
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Crawler extends LiteSpeed
{
	private $_sitemapFile ;
	private $_siteUrl ;

	/**
	 * Initialize crawler, assign sitemap path
	 *
	 * @since    1.1.0
	 */
	protected function __construct()
	{
		$sitemapPath = LSWCP_DIR . 'var' ;
		if ( is_multisite() ) {
			$blogID = get_current_blog_id() ;
			$this->_sitemapFile = $sitemapPath . '/crawlermap-' . $blogID . '.data' ;
			$this->_siteUrl = get_site_url($blogID) ;
		}
		else{
			$this->_sitemapFile = $sitemapPath . '/crawlermap.data' ;
			$this->_siteUrl = get_option('siteurl') ;
		}
	}

	/**
	 * Return crawler meta file
	 * 
	 * @return string Json data file path
	 */
	public function getCrawlerJsonPath()
	{
		if ( ! file_exists($this->_sitemapFile . '.meta') ) {
			return false ;
		}
		$litespeedPluginPath = implode('/', array_slice(explode('/', LSWCP_DIR), -4)) ;LiteSpeed_Cache_Log::push($litespeedPluginPath);
		return $this->_siteUrl . '' ;
	}

	/**
	 * Generate sitemap
	 * 
	 */
	public function generateSitemap()
	{
		$ret = $this->_generateSitemap() ;
		if ( $ret !== true ) {
			LiteSpeed_Cache_Admin_Display::add_notice(LiteSpeed_Cache_Admin_Display::NOTICE_RED, $ret) ;
		}
		else {
			$msg = sprintf(
				__('File created successfully: %s', 'litespeed-cache'),
				$this->_sitemapFile
			) ;
			LiteSpeed_Cache_Admin_Display::add_notice(LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, $msg) ;
		}
	}

	/**
	 * Generate the sitemap
	 * 
	 * @return string|true 
	 */
	protected function _generateSitemap()
	{
		$urls = LiteSpeed_Cache_Crawler_Sitemap::get_instance()->generateData() ;

		// filter urls
		$id = LiteSpeed_Cache_Config::CRWL_BLACKLIST ;
		$blacklist = LiteSpeed_Cache::config($id) ;
		$blacklist = explode("\n", $blacklist) ;
		$urls = array_diff($urls, $blacklist) ;
		if ( LiteSpeed_Cache_Log::get_enabled() ) {
			LiteSpeed_Cache_Log::push('Crawler log: Generate sitemap') ;
		}

		return Litespeed_File::save($this->_sitemapFile, implode("\n", $urls), true) ;
	}

	/**
	 * Get sitemap file info
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public function sitemapTime()
	{
		if ( ! file_exists($this->_sitemapFile) ) {
			return false ;
		}

		$filetime = date('m/d/Y H:i:s', filemtime($this->_sitemapFile)) ;

		return $filetime ;
	}

	/**
	 * Crawling start
	 *
	 * @since    1.1.0
	 * @access   public
	 */
	public function crawlData()
	{
		// for the first time running
		if ( ! file_exists($this->_sitemapFile) ) {
			$ret = $this->_generateSitemap() ;
			if ( $ret !== true ) {
				$this->terminateWithError($ret) ;
			}
		}

		$options = LiteSpeed_Cache_Config::get_instance()->get_options() ;

		$crawler = new Litespeed_Crawler($this->_sitemapFile) ;
		$crawler->setBaseUrl($this->_siteUrl) ;
		$crawler->setRunDuration($options[LiteSpeed_Cache_Config::CRWL_RUN_DURATION]) ;
		$crawler->setRunDelay($options[LiteSpeed_Cache_Config::CRWL_USLEEP]) ;
		$crawler->setThreadsLimit($options[LiteSpeed_Cache_Config::CRWL_THREADS]) ;
		$crawler->setLoadLimit($options[LiteSpeed_Cache_Config::CRWL_LOAD_LIMIT]) ;
		$ret = $crawler->engineStart() ;

		// merge blacklist
		if ( $ret['blacklist'] ) {
			LiteSpeed_Cache_Config::get_instance()->appendBlacklist($ret['blacklist']) ;
		}

		// return error
		if ( $ret['error'] !== false ) {
			if ( LiteSpeed_Cache_Log::get_enabled() ) {
				LiteSpeed_Cache_Log::push('Crawler log: ' . $ret['error']) ;
			}
			$this->terminateWithError($ret['error']) ;
		}
		else {
			if ( LiteSpeed_Cache_Log::get_enabled() ) {
				LiteSpeed_Cache_Log::push('Crawler log: End of sitemap file') ;
			}
			// regenerate the map and exit
			$this->_generateSitemap() ;
			wp_die() ;
		}
	}

	/**
	 * Exit with AJAX error
	 * 
	 * @param  string $error Error info
	 */
	public function terminateWithError($error)
	{
		// return ajax error
		echo json_encode(array(
			'error'	=> $error,
		)) ;
		wp_die() ;
	}

}