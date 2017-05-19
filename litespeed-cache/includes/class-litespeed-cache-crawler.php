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
	private $_sitemap_file ;
	private $_site_url ;

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
			$this->_sitemap_file = $sitemapPath . '/crawlermap-' . $blogID . '.data' ;
			$this->_site_url = get_site_url($blogID) ;
		}
		else{
			$this->_sitemap_file = $sitemapPath . '/crawlermap.data' ;
			$this->_site_url = get_option('siteurl') ;
		}
	}

	/**
	 * Return crawler meta file
	 * 
	 * @return string Json data file path
	 */
	public function get_crawler_json_path()
	{
		if ( ! file_exists($this->_sitemap_file . '.meta') ) {
			return false ;
		}
		$metaUrl = implode('/', array_slice(explode('/', $this->_sitemap_file . '.meta'), -5)) ;
		return $this->_site_url . '/' . $metaUrl ;
	}

	/**
	 * Return crawler meta info
	 * 
	 * @return array Meta array
	 */
	public function get_meta()
	{
		if ( ! file_exists($this->_sitemap_file . '.meta') || ! $meta = Litespeed_File::read($this->_sitemap_file . '.meta') ) {
			return false ;
		}
		return json_decode($meta) ;
	}

	/**
	 * Generate sitemap
	 * 
	 */
	public function generate_sitemap()
	{
		$ret = $this->_generate_sitemap() ;
		if ( $ret !== true ) {
			LiteSpeed_Cache_Admin_Display::add_notice(LiteSpeed_Cache_Admin_Display::NOTICE_RED, $ret) ;
		}
		else {
			$msg = sprintf(
				__('File created successfully: %s', 'litespeed-cache'),
				$this->_sitemap_file
			) ;
			LiteSpeed_Cache_Admin_Display::add_notice(LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, $msg) ;
		}
	}

	/**
	 * Generate the sitemap
	 * 
	 * @return string|true 
	 */
	protected function _generate_sitemap()
	{
		$urls = LiteSpeed_Cache_Crawler_Sitemap::get_instance()->generate_data() ;

		// filter urls
		$id = LiteSpeed_Cache_Config::CRWL_BLACKLIST ;
		$blacklist = LiteSpeed_Cache::config($id) ;
		$blacklist = explode("\n", $blacklist) ;
		$urls = array_diff($urls, $blacklist) ;
		if ( LiteSpeed_Cache_Log::get_enabled() ) {
			LiteSpeed_Cache_Log::push('Crawler log: Generate sitemap') ;
		}

		return Litespeed_File::save($this->_sitemap_file, implode("\n", $urls), true) ;
	}

	/**
	 * Get sitemap file info
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public function sitemap_time()
	{
		if ( ! file_exists($this->_sitemap_file) ) {
			return false ;
		}

		$filetime = date('m/d/Y H:i:s', filemtime($this->_sitemap_file)) ;

		return $filetime ;
	}

	/**
	 * Create reset pos file
	 * 
	 * @return mixed True or error message
	 */
	public function reset_pos()
	{
		$crawler = new Litespeed_Crawler($this->_sitemap_file) ;
		$ret = $crawler->reset_pos() ;
		$log = 'Crawler log: Reset pos. ' ;
		if ( $ret !== true ) {
			$log .= "Error: $ret" ;
			$msg = sprintf(__('Failed to send position reset notification: %s', 'litespeed-cache'), $ret) ;
			LiteSpeed_Cache_Admin_Display::add_notice(LiteSpeed_Cache_Admin_Display::NOTICE_RED, $msg) ;
		}
		else {
			$msg = __('Position reset notification sent successfully', 'litespeed-cache') ;
			LiteSpeed_Cache_Admin_Display::add_notice(LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, $msg) ;
		}
		if ( LiteSpeed_Cache_Log::get_enabled() ) {
			LiteSpeed_Cache_Log::push($log) ;
		}
	}

	/**
	 * Crawling start
	 *
	 * @since    1.1.0
	 * @access   public
	 */
	public function crawl_data()
	{
		// for the first time running
		if ( ! file_exists($this->_sitemap_file) ) {
			$ret = $this->_generate_sitemap() ;
			if ( $ret !== true ) {
				$this->terminate_with_error($ret) ;
			}
		}

		$options = LiteSpeed_Cache_Config::get_instance()->get_options() ;

		$crawler = new Litespeed_Crawler($this->_sitemap_file) ;
		// if finished last time, regenerate sitemap
		if ( $last_fnished_at = $crawler->get_done_status() ) {
			// check whole crawling interval
			if ( time() - $last_fnished_at < $options[LiteSpeed_Cache_Config::CRWL_WHOLE_INTERVAL] ) {
				$ret = 'Crawler log: less than whole crawling interval';
				if ( LiteSpeed_Cache_Log::get_enabled() ) {
					LiteSpeed_Cache_Log::push($ret) ;
				}
				// if not reach whole crawling interval, exit
				$this->terminate_with_error($ret) ;
			}
			$this->_generate_sitemap() ;
		}
		$crawler->set_base_url($this->_site_url) ;
		$crawler->set_run_duration($options[LiteSpeed_Cache_Config::CRWL_RUN_DURATION]) ;
		$crawler->set_run_delay($options[LiteSpeed_Cache_Config::CRWL_USLEEP]) ;
		$crawler->set_threads_limit($options[LiteSpeed_Cache_Config::CRWL_THREADS]) ;
		$crawler->set_load_limit($options[LiteSpeed_Cache_Config::CRWL_LOAD_LIMIT]) ;
		$ret = $crawler->engine_start() ;

		// merge blacklist
		if ( $ret['blacklist'] ) {
			LiteSpeed_Cache_Config::get_instance()->append_blacklist($ret['blacklist']) ;
		}

		// return error
		if ( $ret['error'] !== false ) {
			if ( LiteSpeed_Cache_Log::get_enabled() ) {
				LiteSpeed_Cache_Log::push('Crawler log: ' . $ret['error']) ;
			}
			$this->terminate_with_error($ret['error']) ;
		}
		else {
			$msg = 'Crawler log: End of sitemap file' ;
			if ( LiteSpeed_Cache_Log::get_enabled() ) {
				LiteSpeed_Cache_Log::push($msg) ;
			}

			wp_die($msg, '', array('response'=>200)) ;
		}
	}

	/**
	 * Exit with AJAX error
	 * 
	 * @param  string $error Error info
	 */
	public function terminate_with_error($error)
	{
		// return ajax error
		wp_die($error, '', array('response'=>200)) ;
	}

}