<?php

/**
 * The crawler class
 *
 *
 * @since      	1.1.0
 * @since  		1.5 Moved into /inc
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Crawler
{
	private static $_instance;
	private $_sitemap_file ;
	private $_blacklist_file ;
	private $_home_url ;
	const CRWL_BLACKLIST = 'crawler_blacklist' ;

	/**
	 * Initialize crawler, assign sitemap path
	 *
	 * @since    1.1.0
	 * @access private
	 */
	private function __construct()
	{
		$sitemapPath = LSCWP_DIR . 'var' ;
		if ( is_multisite() ) {
			$blogID = get_current_blog_id() ;
			$this->_sitemap_file = $sitemapPath . '/crawlermap-' . $blogID . '.data' ;
			$this->_home_url = get_home_url( $blogID ) ;
		}
		else{
			$this->_sitemap_file = $sitemapPath . '/crawlermap.data' ;
			$this->_home_url = get_home_url() ;
		}
		$this->_blacklist_file = $this->_sitemap_file . '.blacklist' ;

		LiteSpeed_Cache_Log::debug('Crawler: Initialized') ;
	}

	/**
	 * Return crawler meta file
	 *
	 * @since    1.1.0
	 * @access public
	 * @return string Json data file path
	 */
	public function get_crawler_json_path()
	{
		if ( ! file_exists($this->_sitemap_file . '.meta') ) {
			return false ;
		}
		$metaUrl = implode('/', array_slice(explode('/', $this->_sitemap_file . '.meta'), -5)) ;
		return $this->_home_url . '/' . $metaUrl ;
	}

	/**
	 * Return blacklist content
	 *
	 * @since    1.1.0
	 * @access public
	 * @return string
	 */
	public function get_blacklist()
	{
		return Litespeed_File::read($this->_blacklist_file) ;
	}

	/**
	 * Return blacklist count
	 *
	 * @since    1.1.0
	 * @access public
	 * @return string
	 */
	public function count_blacklist()
	{
		return Litespeed_File::count_lines($this->_blacklist_file) ;
	}

	/**
	 * Save blacklist to file
	 *
	 * @since    1.1.0
	 * @access public
	 * @return bool If saved successfully
	 */
	public function save_blacklist()
	{
		if ( ! isset( $_POST[ self::CRWL_BLACKLIST ] ) ) {
			$msg = __( 'Can not find any form data for blacklist', 'litespeed-cache' ) ;
			LiteSpeed_Cache_Admin_Display::add_notice( LiteSpeed_Cache_Admin_Display::NOTICE_RED, $msg ) ;
			return false ;
		}
		$content = $_POST[ self::CRWL_BLACKLIST ] ;
		$content = array_map( 'trim', explode( "\n", $content ) ) ;// remove space
		$content = implode( "\n", array_filter( $content ) ) ;

		// save blacklist file
		$ret = Litespeed_File::save( $this->_blacklist_file, $content, true, false, false ) ;
		if ( $ret !== true ) {
			LiteSpeed_Cache_Admin_Display::add_notice( LiteSpeed_Cache_Admin_Display::NOTICE_RED, $ret ) ;
		}
		else {
			$msg = sprintf(
				__( 'File saved successfully: %s', 'litespeed-cache' ),
				$this->_blacklist_file
			) ;
			LiteSpeed_Cache_Admin_Display::add_notice( LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, $msg ) ;
		}

		return true ;
	}

	/**
	 * Append urls to current list
	 *
	 * @since    1.1.0
	 * @access public
	 * @param  array $list The url list needs to be appended
	 */
	public function append_blacklist( $list )
	{
		defined( 'LSCWP_LOG' ) && LiteSpeed_Cache_Log::debug( 'Crawler: append blacklist ' . count( $list ) ) ;

		$ori_list = Litespeed_File::read( $this->_blacklist_file ) ;
		$ori_list = explode( "\n", $ori_list ) ;
		$ori_list = array_merge( $ori_list, $list ) ;
		$ori_list = array_map( 'trim', $ori_list ) ;
		$ori_list = array_filter( $ori_list ) ;
		$content = implode( "\n", $ori_list ) ;

		// save blacklist
		$ret = Litespeed_File::save( $this->_blacklist_file, $content, true, false, false ) ;
		if ( $ret !== true ) {
			LiteSpeed_Cache_Log::debug( 'Crawler: append blacklist failed: ' . $ret ) ;
			return false ;
		}

		return true ;
	}

	/**
	 * Generate sitemap
	 *
	 * @since    1.1.0
	 * @access public
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
	 * Parse custom sitemap and return urls
	 *
	 * @since    1.1.1
	 * @access public
	 * @param  string  $sitemap       The url set map address
	 * @param  boolean $return_detail If return url list
	 * @return bollean|array          Url list or if is a sitemap
	 */
	public function parse_custom_sitemap($sitemap, $return_detail = true)
	{
		if ( ! file_get_contents($sitemap) ) {
			return LiteSpeed_Cache_Admin_Error::E_SETTING_CUSTOM_SITEMAP_READ ;
		}
		$xml_object = simplexml_load_file($sitemap) ;
		if ( ! $xml_object ) {
			return LiteSpeed_Cache_Admin_Error::E_SETTING_CUSTOM_SITEMAP_PARSE ;
		}
		if ( ! $return_detail ) {
			return true ;
		}
		// start parsing
		$_urls = array() ;

		$xml_array = (array)$xml_object ;
		if ( !empty($xml_array['sitemap']) ) {// parse sitemap set
			if ( is_object($xml_array['sitemap']) ) {
				$xml_array['sitemap'] = (array)$xml_array['sitemap'] ;
			}
			if ( !empty($xml_array['sitemap']['loc']) ) {// is single sitemap
				$urls = $this->parse_custom_sitemap($xml_array['sitemap']['loc']) ;
				if ( is_array($urls) && !empty($urls) ) {
					$_urls = array_merge($_urls, $urls) ;
				}
			}
			else {
				// parse multiple sitemaps
				foreach ($xml_array['sitemap'] as $val) {
					$val = (array)$val ;
					if ( !empty($val['loc']) ) {
						$urls = $this->parse_custom_sitemap($val['loc']) ;// recursive parse sitemap
						if ( is_array($urls) && !empty($urls) ) {
							$_urls = array_merge($_urls, $urls) ;
						}
					}
				}
			}
		}
		elseif ( !empty($xml_array['url']) ) {// parse url set
			if ( is_object($xml_array['url']) ) {
				$xml_array['url'] = (array)$xml_array['url'] ;
			}
			// if only 1 element
			if ( !empty($xml_array['url']['loc']) ) {
				$_urls[] = $xml_array['url']['loc'] ;
			}
			else {
				foreach ($xml_array['url'] as $val) {
					$val = (array)$val ;
					if ( !empty($val['loc']) ) {
						$_urls[] = $val['loc'] ;
					}
				}
			}
		}

		return $_urls ;
	}

	/**
	 * Generate the sitemap
	 *
	 * @since    1.1.0
	 * @access protected
	 * @return string|true
	 */
	protected function _generate_sitemap()
	{
		// use custom sitemap
		if ( $sitemap = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::CRWL_CUSTOM_SITEMAP ) ) {
			$sitemap_urls = $this->parse_custom_sitemap( $sitemap ) ;
			$urls = array() ;
			$offset = strlen( $this->_home_url ) ;
			if ( is_array( $sitemap_urls ) && ! empty( $sitemap_urls ) ) {
				foreach ( $sitemap_urls as $val ) {
					if ( stripos( $val, $this->_home_url ) === 0 ) {
						$urls[] = substr( $val, $offset ) ;
					}
				}
			}
		}
		else {
			$urls = LiteSpeed_Cache_Crawler_Sitemap::get_instance()->generate_data() ;
		}

		// filter urls
		$blacklist = Litespeed_File::read( $this->_blacklist_file ) ;
		$blacklist = explode( "\n", $blacklist ) ;
		$urls = array_diff( $urls, $blacklist ) ;
		LiteSpeed_Cache_Log::debug( 'Crawler: Generate sitemap' ) ;

		$ret = Litespeed_File::save( $this->_sitemap_file, implode( "\n", $urls ), true, false, false ) ;

		clearstatcache() ;

		// refresh list size in meta
		$crawler = new Litespeed_Crawler( $this->_sitemap_file ) ;
		$crawler->refresh_list_size() ;

		return $ret ;
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

		$filetime = date('m/d/Y H:i:s', filemtime($this->_sitemap_file) + LITESPEED_TIME_OFFSET ) ;

		return $filetime ;
	}

	/**
	 * Create reset pos file
	 *
	 * @since    1.1.0
	 * @access public
	 * @return mixed True or error message
	 */
	public function reset_pos()
	{
		$crawler = new Litespeed_Crawler($this->_sitemap_file) ;
		$ret = $crawler->reset_pos() ;
		$log = 'Crawler: Reset pos. ' ;
		if ( $ret !== true ) {
			$log .= "Error: $ret" ;
			$msg = sprintf(__('Failed to send position reset notification: %s', 'litespeed-cache'), $ret) ;
			LiteSpeed_Cache_Admin_Display::add_notice(LiteSpeed_Cache_Admin_Display::NOTICE_RED, $msg) ;
		}
		else {
			$msg = __('Position reset notification sent successfully', 'litespeed-cache') ;
			// LiteSpeed_Cache_Admin_Display::add_notice(LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, $msg) ;
		}
		LiteSpeed_Cache_Log::debug($log) ;
	}

	/**
	 * Proceed crawling
	 *
	 * @since    1.1.0
	 * @access public
	 * @param bool $force If ignore whole crawling interval
	 */
	public static function crawl_data($force = false)
	{
		if ( ! LiteSpeed_Cache_Router::can_crawl() ) {
			LiteSpeed_Cache_Log::debug('Crawler: ......crawler is NOT allowed by the server admin......') ;
			return false;
		}
		if ( $force ) {
			LiteSpeed_Cache_Log::debug('Crawler: ......crawler manually ran......') ;
		}
		return self::get_instance()->_crawl_data($force) ;
	}

	/**
	 * Receive meta info from crawler
	 *
	 * @since    1.9.1
	 * @access   public
	 */
	public function read_meta()
	{
		$crawler = new Litespeed_Crawler( $this->_sitemap_file ) ;
		return $crawler->read_meta() ;
	}

	/**
	 * Crawling start
	 *
	 * @since    1.1.0
	 * @access   protected
	 * @param bool $force If ignore whole crawling interval
	 */
	protected function _crawl_data($force)
	{
		LiteSpeed_Cache_Log::debug('Crawler: ......crawler started......') ;
		// for the first time running
		if ( ! file_exists($this->_sitemap_file) ) {
			$ret = $this->_generate_sitemap() ;
			if ( $ret !== true ) {
				LiteSpeed_Cache_Log::debug('Crawler: ' . $ret) ;
				return $this->output($ret) ;
			}
		}

		$options = LiteSpeed_Cache_Config::get_instance()->get_options() ;

		$crawler = new Litespeed_Crawler($this->_sitemap_file) ;
		// if finished last time, regenerate sitemap
		if ( $last_fnished_at = $crawler->get_done_status() ) {
			// check whole crawling interval
			if ( ! $force && time() - $last_fnished_at < $options[LiteSpeed_Cache_Config::CRWL_CRAWL_INTERVAL] ) {
				LiteSpeed_Cache_Log::debug('Crawler: Cron abort: cache warmed already.') ;
				// if not reach whole crawling interval, exit
				return;
			}
			LiteSpeed_Cache_Log::debug( 'Crawler: TouchedEnd. regenerate sitemap....' ) ;
			$this->_generate_sitemap() ;
		}
		$crawler->set_base_url($this->_home_url) ;
		$crawler->set_run_duration($options[LiteSpeed_Cache_Config::CRWL_RUN_DURATION]) ;

		$crawler->set_http2( $options[ LiteSpeed_Cache_Config::CRWL_HTTP2 ] ) ;

		/**
		 * Limit delay to use server setting
		 * @since 1.8.3
		 */
		$usleep = $options[ LiteSpeed_Cache_Config::CRWL_USLEEP ] ;
		if ( ! empty( $_SERVER[ LiteSpeed_Cache_Config::ENV_CRAWLER_USLEEP ] ) && $_SERVER[ LiteSpeed_Cache_Config::ENV_CRAWLER_USLEEP ] > $usleep ) {
			$usleep = $_SERVER[ LiteSpeed_Cache_Config::ENV_CRAWLER_USLEEP ] ;
		}
		$crawler->set_run_delay( $usleep ) ;
		$crawler->set_threads_limit( $options[ LiteSpeed_Cache_Config::CRWL_THREADS ] ) ;

		$server_load_limit = $options[ LiteSpeed_Cache_Config::CRWL_LOAD_LIMIT ] ;
		if ( ! empty( $_SERVER[ LiteSpeed_Cache_Config::ENV_CRAWLER_LOAD_LIMIT_ENFORCE ] ) ) {
			$server_load_limit = $_SERVER[ LiteSpeed_Cache_Config::ENV_CRAWLER_LOAD_LIMIT_ENFORCE ] ;
		}
		elseif ( ! empty( $_SERVER[ LiteSpeed_Cache_Config::ENV_CRAWLER_LOAD_LIMIT ] ) && $_SERVER[ LiteSpeed_Cache_Config::ENV_CRAWLER_LOAD_LIMIT ] < $server_load_limit ) {
			$server_load_limit = $_SERVER[ LiteSpeed_Cache_Config::ENV_CRAWLER_LOAD_LIMIT ] ;
		}
		$crawler->set_load_limit( $server_load_limit ) ;
		if ( $options[LiteSpeed_Cache_Config::CRWL_DOMAIN_IP] ) {
			$crawler->set_domain_ip($options[LiteSpeed_Cache_Config::CRWL_DOMAIN_IP]) ;
		}

		// Get current crawler
		$meta = $crawler->read_meta() ;
		$curr_crawler_pos = $meta[ 'curr_crawler' ] ;

		// Generate all crawlers
		$crawlers = $this->list_crawlers() ;

		// In case crawlers are all done but not reload, reload it
		if ( empty( $crawlers[ $curr_crawler_pos ] ) ) {
			$curr_crawler_pos = 0 ;
		}
		$current_crawler = $crawlers[ $curr_crawler_pos ] ;
		/**
		 * Set role simulation
		 * @since 1.9.1
		 */
		if ( $current_crawler[ 'uid' ] ) {
			// Get role simulation vary name
			$vary_inst = LiteSpeed_Cache_Vary::get_instance() ;
			$vary_name = $vary_inst->get_vary_name() ;
			$vary_val = $vary_inst->finalize_default_vary( $current_crawler[ 'uid' ] ) ;
			$cookies = array(
				$vary_name => $vary_val,
				'litespeed_role' => $current_crawler[ 'uid' ],
			) ;

			$crawler->set_cookies( $cookies ) ;
		}
		/**
		 * Set WebP simulation
		 * @since  1.9.1
		 */
		if ( $current_crawler[ 'webp' ] ) {
			$crawler->set_headers( array( 'Accept: image/webp,*/*' ) ) ;
		}

		$ret = $crawler->engine_start() ;

		// merge blacklist
		if ( $ret['blacklist'] ) {
			$this->append_blacklist($ret['blacklist']) ;
		}

		if ( ! empty($ret['crawled']) ) {
			defined( 'LSCWP_LOG' ) && LiteSpeed_Cache_Log::debug( 'Crawler: Last crawled ' . $ret[ 'crawled' ] . ' item(s)' ) ;
		}

		// return error
		if ( $ret['error'] !== false ) {
			LiteSpeed_Cache_Log::debug('Crawler: ' . $ret['error']) ;
			return $this->output($ret['error']) ;
		}
		else {
			$msg = 'Crawler #' . ( $curr_crawler_pos + 1 ) . ' reached end of sitemap file.' ;
			$msg_t = sprintf( __( 'Crawler %s reached end of sitemap file.', 'litespeed-cache' ), '#' . ( $curr_crawler_pos + 1 ) )  ;
			LiteSpeed_Cache_Log::debug('Crawler: ' . $msg) ;
			return $this->output($msg_t) ;
		}
	}

	/**
	 * List all crawlers
	 *
	 * @since    1.9.1
	 * @access   public
	 */
	public function list_crawlers( $count_only = false )
	{
		// Get roles set
		$roles = get_option( LiteSpeed_Cache_Config::ITEM_CRWL_AS_UIDS ) ;
		$roles = $roles ? explode( "\n", $roles ) : array() ;

		// WebP on/off
		$webp = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_MEDIA_IMG_WEBP ) ;

		if ( $count_only ) {
			$count = count( $roles ) + 1 ;
			if ( $webp ) {
				$count *= 2 ;
			}
			return $count ;
		}

		$crawler_list = array(
			array( 'uid' => 0, 'role_title' => __( 'Guest', 'litespeed-cache' ), 'webp' => 0 ),
		) ;

		if ( $webp ) {
			$crawler_list[] = array( 'uid' => 0, 'role_title' => __( 'Guest', 'litespeed-cache' ), 'webp' => 1 ) ;
		}

		// List all roles
		foreach ( $roles as $v ) {
			$role_title = '' ;
			$udata = get_userdata( $v ) ;
			if ( isset( $udata->roles ) && is_array( $udata->roles ) ) {
				$tmp = array_values( $udata->roles ) ;
				$role_title = array_shift( $tmp ) ;
			}
			if ( ! $role_title ) {
				continue ;
			}
			$crawler_list[] = array( 'uid' => $v, 'role_title' => $role_title, 'webp' => 0 ) ;

			if ( $webp ) {
				$crawler_list[] = array( 'uid' => $v, 'role_title' => $role_title, 'webp' => 1 ) ;
			}
		}

		return $crawler_list ;

	}

	/**
	 * Output info and exit
	 *
	 * @since    1.1.0
	 * @access protected
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
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}
}
