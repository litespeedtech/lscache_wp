<?php

/**
 * The crawler class
 *
 *
 * @since      1.1.0
 * @package    LiteSpeed_Cache
 * @subpackage LiteSpeed_Cache/admin
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_Admin_Crawler extends LiteSpeed{

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.1.0
	 */
	protected function __construct(){
	}



	/**
	 * Saves Crawler settings.
	 *
	 * @since 1.1.0
	 * @access private
	 * @param array $input The input options.
	 * @param array $options The current options.
	 * @param array $errors The errors list.
	 */
	private function save_crawler_settings(&$input, &$options, &$errors){
		$id = LiteSpeed_Cache_Config::CRWL_ORDER_LINKS;
		$options[$id] = $input[$id];

		$id = LiteSpeed_Cache_Config::CRWL_CPT;
		$options[$id] = $input[$id];

		$id = LiteSpeed_Cache_Config::CRWL_USLEEP;
		if ((!$this->validate_cron_settings($input, $id))) {
			$errors[] = __('Delay must be an integer between 30 and 2147483647', 'litespeed-cache');
		}
		else{
			$options[$id] = $input[$id];
		}

		$id = LiteSpeed_Cache_Config::CRWL_CRON_INTERVAL;
		if ((!$this->validate_cron_settings($input, $id))) {
			$errors[] = __('Cron Interval must be an integer between 30 and 2147483647', 'litespeed-cache');
		}
		else{
			$options[$id] = $input[$id];
		}

		$id = LiteSpeed_Cache_Config::CRWL_RUN_INTERVAL;
		if ((!$this->validate_cron_settings($input, $id))) {
			$errors[] = __('Run Duration must be an integer between 30 and 2147483647', 'litespeed-cache');
		}
		else{
			$options[$id] = $input[$id];
		}

		$id = LiteSpeed_Cache_Config::CRWL_THREAD;
		if ((!$this->validate_cron_settings($input, $id, true))) {
			$errors[] = __('Maximum number of threads should be les than 10', 'litespeed-cache');
		}
		else{
			$options[$id] = $input[$id];
		}

		$id = LiteSpeed_Cache_Config::CRWL_BLACKLIST;
		$options[$id] = $input[$id];

		self::parse_checkbox(LiteSpeed_Cache_Config::CRWL_PAGES,
			$input, $options);

		self::parse_checkbox(LiteSpeed_Cache_Config::CRWL_POSTS,
			$input, $options);

		self::parse_checkbox(LiteSpeed_Cache_Config::CRWL_CATS,
			$input, $options);

		self::parse_checkbox(LiteSpeed_Cache_Config::CRWL_TAGS,
			$input, $options);
		
	}

	/**
	 * Saves Cron settings.
	 *
	 * @since 1.1.0
	 * @access private
	 * @param array $input The input options.
	 * @param array $options The current options.
	 */
	private function save_cron_settings(&$input, &$options){
		$id = LiteSpeed_Cache_Config::CRWL_CRON_ACTIVE;
		$options[$id] = $input[$id];
	}

	/**
	 * Saves Cron settings.
	 *
	 * @since 1.1.0
	 * @access private
	 * @param array $input The input options.
	 * @param array $options The current options.
	 */
	private function save_black_list_urls_settings(&$input, &$options){
		$id = LiteSpeed_Cache_Config::CRWL_BLACKLIST;
		$options[$id] = $input[$id];

		$id = LiteSpeed_Cache_Config::CRWL_TRANSIENT;
		$options[$id] = $input[$id];
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
			$sitemap->generateData($options, $skipped);
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