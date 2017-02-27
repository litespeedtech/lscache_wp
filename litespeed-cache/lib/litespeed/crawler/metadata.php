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

	public function set_current_position($position)
	{
		$this->current_position = $position;
	}

	public function get_current_position()
	{
		return $this->current_position;
	}

	public function set_list_size($size)
	{
		$this->list_size = $size;
	}

	public function get_list_size()
	{
		return $this->list_size;
	}

	public function set_last_start_time($time)
	{
		$this->last_start_time = $time;
	}

	public function get_last_start_time()
	{
		return $this->last_start_time;
	}

	public function set_last_finished_time($time)
	{
		$this->last_finished_time = $time;
	}

	public function get_last_finished_time()
	{
		return $this->last_finished_time;
	}

	public function set_last_current_run_time($time)
	{
		$this->last_current_run_time = $time;
	}

	public function get_last_current_run_time()
	{
		return $this->last_current_run_time;
	}

	public function set_sitemap_id($id)
	{
		$this->sitemap_id = $id;
	}

	public function get_sitemap_id()
	{
		return $this->sitemap_id;
	}

	public function set_base_url($url)
	{
		$this->base_url = $url;
	}

	public function get_base_url()
	{
		return $this->base_url;
	}

	public function set_sitemap_hash($hash)
	{
		$this->sitemap_hash = $hash;
	}

	public function get_sitemap_hash()
	{
		return $this->sitemap_hash;
	}

	public function set_current_total($count)
	{
		$this->current_total = $count;
	}

	public function get_current_total()
	{
		return $this->current_total;
	}

	public function set_user_agent($agent)
	{
		$this->user_agent = $agent;
	}

	public function get_user_agent()
	{
		return $this->user_agent;
	}

	public function set_tmp_msg($msg)
	{
		$this->tmp_msg = $msg;
	}

	public function get_tmp_msg()
	{
		return $this->tmp_msg;
	}

}
