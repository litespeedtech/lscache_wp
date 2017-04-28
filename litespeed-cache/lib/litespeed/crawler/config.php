<?php

class LiteSpeed_Crawler_Config
{
	/**
	 * @var int How long to run this iteration for in seconds.
	 */
	private $run_seconds = 300;
	/**
	 * @var int How long to wait between each request in milliseconds.
	 */
	private $sleep_milliseconds = 250;
	/**
	 * @var int The maximum load to allow during a run.
	 */
	private $max_load = 1;
	/**
	 * @var int The number of threads to try to run.
	 */
	private $num_threads = 1;

	private static $instance;
	
	private function __construct()
	{
	}

	/**
	* Get the LiteSpeed_Crawler_Crawler object.
	*
	* @access   public
	*/
	public static function get_instance()
	{
		if (!isset(self::$instance)) 
		{
			self::$instance = new LiteSpeed_Crawler_Config();
		}
		return self::$instance;
	}

	public function set_run_seconds($run_seconds)
	{
		$this->run_seconds = $run_seconds;
	}

	public function get_run_seconds()
	{
		return $this->run_seconds;
	}

	public function set_sleep_milliseconds($sleep_milliseconds)
	{
		$this->sleep_milliseconds = $sleep_milliseconds;
	}

	public function get_sleep_milliseconds()
	{
		return $this->sleep_milliseconds;
	}

	public function set_max_load($max_load)
	{
		$this->max_load = $max_load;
	}

	public function get_max_load()
	{
		return $this->max_load;
	}

	public function set_num_threads($num_threads)
	{
		$this->num_threads = $num_threads;
	}

	public function get_num_threads()
	{
		return $this->num_threads;
	}
}
