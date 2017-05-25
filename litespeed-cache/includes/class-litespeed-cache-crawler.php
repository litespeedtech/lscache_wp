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
class LiteSpeed_Cache_Crawler
{
	private static $_instance;
	private $_sitemap_file ;
	private $_site_url ;

	/**
	 * Initialize crawler, assign sitemap path
	 *
	 * @since    1.1.0
	 */
	private function __construct()
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

		if ( LiteSpeed_Cache_Log::get_enabled() ) {
			LiteSpeed_Cache_Log::push('Crawler log: Initialized') ;
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
	 * Proceed crawling
	 *
	 * @param bool $force If ignore whole crawling interval
	 */
	public static function crawl_data($force = false)
	{
		if ( $force ) {
			if ( LiteSpeed_Cache_Log::get_enabled() ) {
				LiteSpeed_Cache_Log::push('Crawler log: ......crawler manually ran......') ;
			}
		}
		return self::get_instance()->_crawl_data($force) ;
	}

	/**
	 * Crawling start
	 *
	 * @since    1.1.0
	 * @param bool $force If ignore whole crawling interval
	 * @access   protected
	 */
	protected function _crawl_data($force)
	{
		if ( LiteSpeed_Cache_Log::get_enabled() ) {
			LiteSpeed_Cache_Log::push('Crawler log: ......crawler started......') ;
		}
		// for the first time running
		if ( ! file_exists($this->_sitemap_file) ) {
			$ret = $this->_generate_sitemap() ;
			if ( $ret !== true ) {
				if ( LiteSpeed_Cache_Log::get_enabled() ) {
					LiteSpeed_Cache_Log::push('Crawler log: ' . $ret) ;
				}
				return $this->output($ret) ;
			}
		}

		$options = LiteSpeed_Cache_Config::get_instance()->get_options() ;

		$crawler = new Litespeed_Crawler($this->_sitemap_file) ;
		// if finished last time, regenerate sitemap
		if ( $last_fnished_at = $crawler->get_done_status() ) {
			// check whole crawling interval
			if ( ! $force && time() - $last_fnished_at < $options[LiteSpeed_Cache_Config::CRWL_CRAWL_INTERVAL] ) {
				$ret = 'Crawler log: less than whole crawling interval';
				if ( LiteSpeed_Cache_Log::get_enabled() ) {
					LiteSpeed_Cache_Log::push($ret) ;
				}
				// if not reach whole crawling interval, exit
				return $this->output($ret) ;
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

		if ( ! empty($ret['crawled']) && LiteSpeed_Cache_Log::get_enabled() ) {
			LiteSpeed_Cache_Log::push('Crawler log: Last crawled ' . $ret['crawled'] . ' item(s)') ;
		}

		// return error
		if ( $ret['error'] !== false ) {
			if ( LiteSpeed_Cache_Log::get_enabled() ) {
				LiteSpeed_Cache_Log::push('Crawler log: ' . $ret['error']) ;
			}
			return $this->output($ret['error']) ;
		}
		else {
			$msg = 'Crawler log: End of sitemap file' ;
			if ( LiteSpeed_Cache_Log::get_enabled() ) {
				LiteSpeed_Cache_Log::push($msg) ;
			}

			return $this->output($msg) ;
		}
	}

	/**
	 * Output info and exit
	 * 
	 * @param  string $error Error info
	 */
	protected function output($msg)
	{
		if ( defined('DOING_CRON') ) {
			echo $msg ;
			// exit();
		}
		else {
			echo "<script>alert('" . htmlspecialchars($msg) . "');</script>" ;
			// exit;
		}
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.1.0
	 * @access public
	 * @return Current class instance.
	 */
	public static function get_instance()
	{
		$cls = get_called_class();
		if (!isset(self::$_instance)) {
			self::$_instance = new $cls();
		}

		return self::$_instance;
	}
}
