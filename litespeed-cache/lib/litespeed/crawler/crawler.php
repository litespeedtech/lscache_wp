<?php
/**
 * The Crawler Class
 *
 *
 * @since      1.1.0
 * @package    LiteSpeed_Crawler_Crawler
 * @subpackage LiteSpeed_Cache/lib
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */

class LiteSpeed_Crawler_Crawler
{
	const WARMUP_META_CACHE_ID = 'lscache_warmup_meta';
	const DELTA_META_CACHE_ID  = 'lscache_delta_meta';
	const USER_AGENT_WALKER    = 'lscache_walker';     
	const USER_AGENT_RUNNER    = 'lscache_runner';
	const ENV_COOKIE_NAME      = 'lscache_vary';
	const BATCH_SIZE           = 10;

	private $sleepTime;
	private $start_time;
	private $stop_time;
	private $message;
	private static $instance;

	/**
	* Initialize Cralwer Class
	*
	* @access   private
	*/
	private function __construct()
	{
		$cur_dir = dirname(__FILE__) ;
		require_once $cur_dir .'/config.php';
		require_once $cur_dir .'/metadata.php';
	}

	/**
	* Get the LiteSpeed_Crawler_Crawler object.
	*
	* @access   public
	*/
	public static function get_instance()
	{
		if (!isset(self::$instance)) {
			self::$instance = new LiteSpeed_Crawler_Crawler();
		}
		return self::$instance;
	}

	/**
	* Function to run the Class entry point
	*
  	* @param   array $config 
	* @param   object $sitemap 
  	* @return  array of crawled links
	* @access  public
	*/
	public function run($config, $sitemap)
	{

		$sleep = $config->get_sleep_milliseconds();
		$threads = $config->get_num_threads();
		$run = $config->get_run_seconds();

		$this->sleepTime = $sleep == 0 ? 10000 : $sleep;

		$urls = $sitemap->loadData();

		if(sizeof($urls) > 0)
			self::crawl($urls, $this->sleepTime, $threads, $run);
		else
    		return array();
	}

	/**
	* Function to crawl links
	*
	* @param   array $urls 
	* @param   int $sleep 
	* @param   int $thread
	* @param   int $run
	* @return  array of crawled links
	* @access  private
	*/
	private function crawl($urls, $sleep = 0, $threads = 1, $run = 0)
	{

		if (empty($urls)) {
		  echo "\nNo more links are there to crawl\n";
		  exit();
		}

		$ua = '';

		$j = 0;

		// Disable time limit
		set_time_limit(0);

		$minstance = LiteSpeed_Crawler_Metadata::get_instance();
		$meta = array();

		$meta = $minstance->loadMetaCache();
		$meta = unserialize($meta);

		if(sizeof($meta) > 2)
		{
		  $last_end_time = $meta['lastendtime'];
		  $last_start_time = $meta['laststarttime'];
		  $baseurl = $meta['baseurl'];

		  $total_time = $minstance->duration($last_start_time, $last_end_time);
		  if($total_time < $run)
		  {
		    $minstance->set_user_agent($meta['user_agent']);
		    $minstance->set_last_start_time($meta['laststarttime']);
		    $minstance->set_last_finished_time($meta['laststarttime']);
		    $minstance->set_list_size($meta['size']);
		    $minstance->set_current_position($meta['curpos']);
		    return;
		  }
		  else
		  {
		    $ua = self::USER_AGENT_WALKER;
		  }
		}
		else
		{
		  $ua = self::USER_AGENT_RUNNER;
		  $baseurl = $minstance->get_base_url();
		}

		$minstance->set_user_agent($ua);

		$minstance->set_last_start_time(self::start());

		$chunks = array_chunk($urls, self::BATCH_SIZE);

		$options = self::get_curl_options($ua, $threads);

		foreach ($chunks as $chunk) 
		{
		  $curlArr = array();
		  $i = 0;
		  $master = curl_multi_init();

		  foreach ($chunk as $key) 
		  {     
		    $url = $baseurl.$key['url'];    
		    $nocache = $key['nocache'];
		    if($nocache == '')
		    {
		      $curlArr[$i] = curl_init();
		      curl_setopt($curlArr[$i], CURLOPT_URL, $url);
		      curl_setopt_array($curlArr[$i], $options);
		      curl_multi_add_handle($master, $curlArr[$i]);
		      $i++;
		    }
		  }

		  $running = null;
		  $i = 0;
		  $response_header = array();
		 
		  do //todo: try to replace `do`
		  {
		    curl_multi_exec($master, $running);   //fork uri in parallel
		    usleep($sleep);       //release virtual memory
		  } while ($running > 0);

		  foreach ($chunk as $c) 
		  { 
		    $value = $c['url'];
		    $nocache = $c['nocache'];

		    if($nocache == '')
		    {
		    $response_header  = curl_multi_getcontent($curlArr[$i]);
		    $cache            = strtolower(self::getHeaderValue($response_header, "x-litespeed-cache"));
		    $max_age          = strtolower(self::checkIfExists($response_header, "max-age=0"));
		    $status           = strtolower(self::checkIfExists($response_header, "HTTP/1.1 200 OK"));

		    if((isset($max_age)) && ($max_age == true)) 
		    {
		      if((isset($nocache)) && ($nocache !== ''))
		        $reason = $nocache;
		      else  
		        $reason = 'no-cache';
		        $minstance->set_skipped_urls($value, $reason);
		    }
		    else
		    {
		      if((isset($status)) && ($status == true))
		      {
		        $j++;
		      }
		      else
		      {
		        $reason = 'http-code-invalid';
		        $minstance->set_skipped_urls($value, $reason);
		      }
		    }
		    curl_multi_remove_handle($master, $curlArr[$i]);
		    curl_close($curlArr[$i]);
		    $i++;
		    $curpos = $j;
		    }
		  }
		  curl_multi_close($master);

		  //Stop timer
		  $minstance->set_last_finished_time(self::stop());

		}//foreach chunks

		$minstance->set_list_size($j);
		$minstance->set_current_position($curpos);
	}

	/**
	* Function to check if given value exists in responseHeader
	*
	* @param   string $header 
	* @param   string $search 
	* @return  bool
	* @access  private
	*/
	private static function checkIfExists($header='', $search='')
	{
		if (stripos(strtolower($header), $search) !== false){
			return true;
		}
		return false;
	}

	/**
	* Function to check if given value exists in responseHeader
	*
	* @param   string $header 
	* @param   string $directove 
	* @return  bull if value not found else return value
	* @access  private
	*/
	private static function getHeaderValue($header='', $directive='')
	{
		preg_match("#[\r\n]".$directive.":(.*)[\r\n\;]# Ui", $header, $match);

		if (isset($match[1]) && trim($match[1]) != "")
		{
		  return trim($match[1]);
		}
		else return null;
	}

	/**
	* Function to start the crawl time
	*
	* @access   private
	*/
	private function start()
	{
		return $this->start_time = microtime(true);
	}

	/**
	* Function to stop the crawl time
	*
	* @access   private
	*/
	private function stop()
	{
		return $this->stop_time = microtime(true);
	}

	/**
	* Function to get curl_options
	*
	* @param    string $ua as user-agent
	* @param    int $threads
	* @return   options array
	* @access   private
	*/
	private function get_curl_options($ua = '', $threads = 0)
	{
		$responseTimeOut = 10;
		$referer = (array_key_exists('HTTP_HOST', $_SERVER) && array_key_exists('REQUEST_URI', $_SERVER)) ? 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] : null;
		$headers = array();

		if($ua != '')
		{
		  $headers[] = 'User-Agent: '. $ua;
		}
		  
		$headers[] = "Cache-Control: max-age=0";
		  
		$options = array(
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_HEADER => true,
		  CURLOPT_CUSTOMREQUEST => 'GET',
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_CONNECTTIMEOUT => $responseTimeOut,
		  CURLOPT_TIMEOUT => $responseTimeOut,
		  CURLOPT_SSL_VERIFYHOST => 0,
		  CURLOPT_SSL_VERIFYPEER => false,
		  CURLOPT_NOBODY => true,
		  CURL_HTTP_VERSION_1_1 => 1,
		  CURLOPT_HTTPHEADER => $headers
		);

		if( $ua == self::USER_AGENT_WALKER )
		{
		  $options[CURLOPT_FRESH_CONNECT] = true;
		}

		if( $threads > 1 )
		{
		  $options[CURLOPT_MAXCONNECTS] = $threads;
		}

		if (!empty($referer)) 
		{
		  $options[CURLOPT_REFERER] = $referer;
		}

		return $options;
  	}
}
