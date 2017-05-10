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
	private $sitemap_file;
	private $meta_file;
	private $site_url;

	/**
	 * Initialize crawler, assign sitemap path
	 *
	 * @since    1.1.0
	 */
	protected function __construct()
	{
		$sitemap_path = LSWCP_DIR . 'var';
		if ( is_multisite() )
		{
			$blog_id = get_current_blog_id();
			$this->sitemap_file = $sitemap_path . '/crawlermap-' . $blog_id . '.data';
			$this->site_url = get_site_url($blog_id);
		}
		else{
			$this->sitemap_file = $sitemap_path . '/crawlermap.data';
			$this->site_url = get_option('siteurl');
		}
		$this->meta_file = $this->site_url . '.meta';
	}

	/**
	 * generate sitemap
	 * 
	 * @return [type] [description]
	 */
	public function generate_sitemap()
	{
		$urls = LiteSpeed_Cache_Crawler_Sitemap::get_instance()->generate_data();
		$meta = array(
			'list_size'	=> count($urls),
			'file_time'	=> time(),
			'last_pos'=> 0,
			'start_time'=> 0,
		);

		$ret = Litespeed_File::save(
			$this->sitemap_file,
			implode("\n", $urls),
			true
		);
		if ( $ret !== true )
		{
			LiteSpeed_Cache_Admin_Display::add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_RED,
				$ret
			);
		}
		else
		{
			$msg = sprintf(
				__('File Successfully created here %s', 'litespeed-cache'),
				$this->sitemap_file
			);
			LiteSpeed_Cache_Admin_Display::add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, $msg
			);
		}
	}

	/**
	 * Get sitemap file info
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public function info()
	{
		if ( ! file_exists($path) )
		{
			return false;
		}

		$info = array(
			'file_time'	=> filemtime($path),
			'list_size'	=> '',
		);

		return $info;
	}

	/**
	 * Crawling start
	 *
	 * @since    1.1.0
	 * @access   public
	 */
	public function crawl_data(){

		$options = $this->config->get_options();

		$crawler = LiteSpeed_Crawler_Crawler::get_instance();

		$sitemap = LiteSpeed_Cache_Crawler_Sitemap::get_instance();

		$metadata = LiteSpeed_Crawler_Metadata::get_instance();

		$crwlconfig = LiteSpeed_Crawler_Config::get_instance();

		$id = LiteSpeed_Cache_Config::CRWL_USLEEP;
		$sleeptime = $options[$id];

		$crwlconfig->set_sleep_milliseconds($sleeptime);

		$id = LiteSpeed_Cache_Config::CRWL_THREAD;
		$threads = $options[$id];

		$crwlconfig->set_num_threads($threads);

		$id = LiteSpeed_Cache_Config::CRWL_RUN_INTERVAL;
		$run_interval = $options[$id];

		$crwlconfig->set_run_seconds($run_interval);

		if ( is_multisite() ) {
			$blog_id = get_current_blog_id();
			$baseurl = get_site_url($blog_id);//todo: check if site_url() return same
		}
		else{
			$baseurl = site_url();
		}
		
		$metadata->set_base_url($baseurl);

		$mtdt = LiteSpeed_Cache_Config::CRWL_TRANSIENT;

		$transient = false;
		$tdata = $options[$mtdt];
		
		if ( $tdata !== ''){ 
			$metadata->setMetaCache($tdata);
			$transient = true;     
		}

		$crawler->run($crwlconfig, $sitemap);
		$data = $metadata->meta_data($transient);
		echo '<li>baseurl:' .$data['baseurl']. ' user-agent:'.$data['user_agent'].'  size:'.$data['size'].'  laststarttime:'.$data['laststarttime'].' lastendtime:'.$data['lastendtime'].'  totaltime:'.$data['totaltime'].'</li>';

		if(sizeof($data['skipped_urls']) > 0 ){
			$skipped_urls = '';
			$blk = LiteSpeed_Cache_Config::CRWL_BLACKLIST;
			$surls = array();
			$skipped = array();
			$site_url_len = strlen($baseurl);

			if($options[$blk] !== ''){
				$surls = explode(', ', $options[$blk]);
			}
			
			foreach($data['skipped_urls'] as $urls)
			{
				$url = $baseurl . $urls['url'];
				if(!in_array($url, $surls))
				{
					$surls[] = $url;
					$reason = 'no-cache';
					$skipped_urls = implode(', ', $surls);
				}
				else
				{
					$surls = array_map('trim', $surls);
					$skipped_urls = implode(', ', $surls);
				}
			}

			foreach ($surls as $surl)
			{
				$skipped['skipped_urls'][] = array('url'=> substr($surl, $site_url_len) ,'reason' => 'no-cache');
			}

			echo '<li>Skipped URLs: ' . $skipped_urls . '</li>';
			$this->update_blacklisted_urls($skipped_urls, $data);
			$sitemap->generate_data($skipped);
		}

		echo __('<li>Page will automaticallly reload in 3 sec(s)</li>', 'litespeed-cache');
	}

	function update_blacklisted_urls($skipped_urls, $data){
		$blk = LiteSpeed_Cache_Config::CRWL_BLACKLIST;

		$mtdt = LiteSpeed_Cache_Config::CRWL_TRANSIENT;

		$data = serialize($data);

		echo '<form method="post" action="options.php" id="blacklist_urls">';
		settings_fields(LiteSpeed_Cache_Config::OPTION_NAME);
		echo '<input type="hidden" id="'.$blk.'" name="litespeed-cache-conf['.$blk.']" value="'.$skipped_urls.'"/>';
		echo '<textarea name="litespeed-cache-conf[crawler_metadata]" type="text" id="crawler_metadata" rows="5" cols="80" style="display:none;">'.
		$data.'</textarea>';
		echo '<input type="hidden" name="blacklist_urls_hidden" value="blacklist_urls_hidden"/>';
		echo '<input type="hidden" name="crawl_page_url" id="crawl_page_url" value="/wp-admin/admin.php?page=lscache-crawler"/>';
		echo '</form>';
	}
}