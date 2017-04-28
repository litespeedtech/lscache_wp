<?php

class LiteSpeed_Crawler_Metadata
{
	/**
	 * @var int The current position in the list.
	 */
	private $current_position = 0;
	/**
	 * @var int The total number of urls to visit.
	 */
	private $list_size = 0;
	/**
	 * @var int The timestamp of the last time that a crawl was initiated.
	 */
	private $last_start_time = 0;
	/**
	 * @var int The timestamp of the last time that a crawl was completed.
	 */
	private $last_finished_time = 0;
	/**
	 * @var int The timestamp of the last time this current run finished.
	 */
	private $last_current_run_time = 0;
	/**
	 * @var int The ID of the sitemap used for this metadata.
	 */
	private $sitemap_id = 0;
	/**
	 * @var string The base URL to visit.
	 */
	private $base_url = '';
	/**
	 * @var string The hash of the sitemap. This hash should be used to verify
	 * that the sitemap is still relevant. If it does not match, the crawl
	 * should be restarted.
	 */
	private $sitemap_hash = '';
	/**
	 * @var int The current total queries completed. This may be larger than
	 * the $list_size if there are varies tested.
	 */
	private $current_total = 0;
	/**
	 * @var string The user agent string to use. This can be set to runner or
	 * worker to determine crawl mode.
	 */
	private $user_agent = '';
	/**
	 * @var string A message to display on the admin screen.
	 */
	private $tmp_msg = '';
	/**
	 * @var to store meta cache fields
	 */
	private $meta_cache = array();
	/**
	 * @var for LiteSpeed_Crawler_Metadata instance
	 */
	private static $instance;
	/**
	 * @var string to store skipped urls
	 */
	private $skipped_urls = '';


	private function __construct()
	{
	}

	public static function get_instance()
	{
		if (!isset(self::$instance)) {
			self::$instance = new LiteSpeed_Crawler_Metadata();
		}

		return self::$instance;
	}

	public function set_current_position($position)
	{
		$this->meta_cache['current_position'] = $position;
	}

	public function get_current_position()
	{
		return $this->meta_cache['current_position'];  
	}

	public function set_list_size($size)
	{
		$this->meta_cache['list_size'] = $size;
	}

	public function get_list_size()
	{
		return $this->meta_cache['list_size']; 
	}

	public function set_last_start_time($time)
	{
		$this->meta_cache['last_start_time'] = $time;
	}

	public function get_last_start_time()
	{
		return $this->meta_cache['last_start_time'];
	}

	public function set_last_finished_time($time)
	{
		$this->meta_cache['last_finished_time'] = $time;
	}

	public function get_last_finished_time()
	{ 
		return $this->meta_cache['last_finished_time']; 
	}

	public function set_last_current_run_time($time)
	{
		$this->meta_cache['last_current_run_time'] = $time;
	}

	public function get_last_current_run_time()
	{
		return $this->meta_cache['last_current_run_time']; 
	}

	public function set_sitemap_id($id)
	{
		$this->meta_cache['sitemap_id'] = $id;
	}

	public function get_sitemap_id()
	{
		return $this->meta_cache['sitemap_id']; 
	}

	public function set_base_url($url)
	{
		$this->meta_cache['base_url'] = $url;
	}

	public function get_base_url()
	{
		return $this->meta_cache['base_url']; 
	}

	public function set_sitemap_hash($hash)
	{
		$this->meta_cache['sitemap_hash'] = $hash;
	}

	public function get_sitemap_hash()
	{
		return $this->meta_cache['sitemap_hash'];

	}

	public function set_current_total($count)
	{
		$this->meta_cache['current_total'] = $count;
	}

	public function get_current_total()
	{
		return $this->meta_cache['current_total'];
	}

	public function set_user_agent($agent)
	{
		$this->meta_cache['user_agent']= $agent;
	}

	public function get_user_agent()
	{
		return $this->meta_cache['user_agent'];
	}

	public function set_tmp_msg($msg)
	{
		$this->meta_cache['tmp_msg'] = $msg;
	}

	public function get_tmp_msg()
	{
		return $this->meta_cache['tmp_msg'];
	}

	public function setMetaCache($cache)
	{
		$this->meta_cache = unserialize($cache);
	}

	public function loadMetaCache()
	{
		return serialize($this->meta_cache);
	}

	public function set_skipped_urls($urls, $reason = NULL)
	{
		$this->meta_cache['skipped_urls'][] = array('url'=>$urls,'reason' => $reason);
	}

	public function get_skipped_urls()
	{
		return $this->meta_cache['skipped_urls'];
	}

	public function duration($start, $stop)
	{
		return ($stop - $start);
	}

	public function meta_data($transient = false)
	{
		if(isset($transient) && $transient == true)
		{
			$meta = array();
			$meta = $this->loadMetaCache();
			$meta = unserialize($meta);

			$data = array(
				'baseurl' => $meta['baseurl'],
				'user_agent' => $this->get_user_agent(),
				'size' => $meta['list_size'],
				'laststarttime' => $meta['last_start_time'],
				'lastendtime' => $meta['last_finished_time'],
				'totaltime' => $this->duration( $meta['last_start_time'], $meta['last_finished_time']),
				'curpos' => $meta['current_position'],
				'skipped_urls' => $meta['skipped_urls'],    
			);
		}
		else
		{

			$data = array(
				'baseurl' => $this->get_base_url(),
				'user_agent' => $this->get_user_agent(),
				'size' => $this->get_list_size(),
				'laststarttime' => $this->get_last_start_time(),
				'lastendtime' => $this->get_last_finished_time(),
				'totaltime' => $this->duration($this->get_last_start_time(), $this->get_last_finished_time()),
				'curpos' => $this->get_current_position(),
				'skipped_urls' => $this->get_skipped_urls(),    
			);
		}
		
		return $data;
	}
}