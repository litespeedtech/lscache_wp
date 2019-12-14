<?php
/**
 * The crawler class
 *
 * @since      	1.1.0
 */
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

class Crawler extends Base
{
	const TYPE_REFRESH_MAP = 'refresh_map';

	protected static $_instance;

	private $_sitemeta = 'meta.data';

	private $_options;
	protected $_summary;

	/**
	 * Initialize crawler, assign sitemap path
	 *
	 * @since    1.1.0
	 * @access protected
	 */
	protected function __construct()
	{
		if ( is_multisite() ) {
			$this->_sitemeta = 'meta' . get_current_blog_id() . '.data';
		}

		$this->_options = Conf::get_instance()->get_options();

		$this->_summary = self::get_summary();

		Log::debug( '[Crawler] Init' );
	}

	/**
	 * Return crawler meta file
	 *
	 * @since    1.1.0
	 * @access public
	 */
	public function json_path()
	{
		if ( ! file_exists( LITESPEED_STATIC_DIR . '/crawler/' . $this->_sitemeta ) ) {
			return false;
		}

		return LITESPEED_STATIC_URL . '/crawler/' . $this->_sitemeta;
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
		$crawler = new Crawler_Engine($this->_sitemap_file);
		$ret = $crawler->reset_pos();
		$log = 'Crawler: Reset pos. ';
		if ( $ret !== true ) {
			$log .= "Error: $ret";
			$msg = sprintf(__('Failed to send position reset notification: %s', 'litespeed-cache'), $ret);
			Admin_Display::add_notice(Admin_Display::NOTICE_RED, $msg);
		}
		else {
			$msg = __('Position reset notification sent successfully', 'litespeed-cache');
			// Admin_Display::add_notice(Admin_Display::NOTICE_GREEN, $msg);
		}
		Log::debug($log);
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
		if ( ! Router::can_crawl() ) {
			Log::debug('Crawler: ......crawler is NOT allowed by the server admin......');
			return false;
		}
		if ( $force ) {
			Log::debug('Crawler: ......crawler manually ran......');
		}
		return self::get_instance()->_crawl_data($force);
	}

	/**
	 * Receive meta info from crawler
	 *
	 * @since    1.9.1
	 * @access   public
	 */
	public function read_meta()
	{
		$crawler = new Crawler_Engine( $this->_sitemap_file );
		return $crawler->read_meta();
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
		Log::debug('Crawler: ......crawler started......');
		// for the first time running
		if ( ! file_exists($this->_sitemap_file) ) {
			$ret = $this->_generate_sitemap();
			if ( $ret !== true ) {
				Log::debug('Crawler: ' . $ret);
				return $this->output($ret);
			}
		}

		$crawler = new Crawler_Engine($this->_sitemap_file);
		// if finished last time, regenerate sitemap
		if ( $last_fnished_at = $crawler->get_done_status() ) {
			// check whole crawling interval
			if ( ! $force && time() - $last_fnished_at < $this->_options[Base::O_CRAWLER_CRAWL_INTERVAL] ) {
				Log::debug('Crawler: Cron abort: cache warmed already.');
				// if not reach whole crawling interval, exit
				return;
			}
			Log::debug( 'Crawler: TouchedEnd. regenerate sitemap....' );
			$this->_generate_sitemap();
		}
		$crawler->set_base_url( home_url() );
		$crawler->set_run_duration($this->_options[Base::O_CRAWLER_RUN_DURATION]);

		/**
		 * Limit delay to use server setting
		 * @since 1.8.3
		 */
		$usleep = $this->_options[ Base::O_CRAWLER_USLEEP ];
		if ( ! empty( $_SERVER[ Base::ENV_CRAWLER_USLEEP ] ) && $_SERVER[ Base::ENV_CRAWLER_USLEEP ] > $usleep ) {
			$usleep = $_SERVER[ Base::ENV_CRAWLER_USLEEP ];
		}
		$crawler->set_run_delay( $usleep );
		$crawler->set_threads_limit( $this->_options[ Base::O_CRAWLER_THREADS ] );
		/**
		 * Set timeout to avoid incorrect blacklist addition #900171
		 * @since  3.0
		 */
		$crawler->set_timeout( $this->_options[ Base::O_CRAWLER_TIMEOUT ] );

		$server_load_limit = $this->_options[ Base::O_CRAWLER_LOAD_LIMIT ];
		if ( ! empty( $_SERVER[ Base::ENV_CRAWLER_LOAD_LIMIT_ENFORCE ] ) ) {
			$server_load_limit = $_SERVER[ Base::ENV_CRAWLER_LOAD_LIMIT_ENFORCE ];
		}
		elseif ( ! empty( $_SERVER[ Base::ENV_CRAWLER_LOAD_LIMIT ] ) && $_SERVER[ Base::ENV_CRAWLER_LOAD_LIMIT ] < $server_load_limit ) {
			$server_load_limit = $_SERVER[ Base::ENV_CRAWLER_LOAD_LIMIT ];
		}
		$crawler->set_load_limit( $server_load_limit );
		if ( $this->_options[Base::O_SERVER_IP] ) {
			$crawler->set_domain_ip($this->_options[Base::O_SERVER_IP]);
		}

		// Get current crawler
		$meta = $crawler->read_meta();
		$curr_crawler_pos = $meta[ 'curr_crawler' ];

		// Generate all crawlers
		$crawlers = $this->list_crawlers();

		// In case crawlers are all done but not reload, reload it
		if ( empty( $crawlers[ $curr_crawler_pos ] ) ) {
			$curr_crawler_pos = 0;
		}
		$current_crawler = $crawlers[ $curr_crawler_pos ];

		$cookies = array();
		/**
		 * Set role simulation
		 * @since 1.9.1
		 */
		if ( ! empty( $current_crawler[ 'uid' ] ) ) {
			// Get role simulation vary name
			$vary_inst = Vary::get_instance();
			$vary_name = $vary_inst->get_vary_name();
			$vary_val = $vary_inst->finalize_default_vary( $current_crawler[ 'uid' ] );
			$cookies[ $vary_name ] = $vary_val;
			$cookies[ 'litespeed_role' ] = $current_crawler[ 'uid' ];
		}

		/**
		 * Check cookie crawler
		 * @since  2.8
		 */
		foreach ( $current_crawler as $k => $v ) {
			if ( strpos( $k, 'cookie:') !== 0 ) {
				continue;
			}

			$cookies[ substr( $k, 7 ) ] = $v;
		}

		if ( $cookies ) {
			$crawler->set_cookies( $cookies );
		}

		/**
		 * Set WebP simulation
		 * @since  1.9.1
		 */
		if ( ! empty( $current_crawler[ 'webp' ] ) ) {
			$crawler->set_headers( array( 'Accept: image/webp,*/*' ) );
		}

		/**
		 * Set mobile crawler
		 * @since  2.8
		 */
		if ( ! empty( $current_crawler[ 'mobile' ] ) ) {
			$crawler->set_ua( 'Mobile' );
		}

		$ret = $crawler->engine_start();

		// merge blacklist
		if ( $ret['blacklist'] ) {
			$this->append_blacklist($ret['blacklist']);
		}

		if ( ! empty($ret['crawled']) ) {
			defined( 'LSCWP_LOG' ) && Log::debug( 'Crawler: Last crawled ' . $ret[ 'crawled' ] . ' item(s)' );
		}

		// return error
		if ( $ret['error'] !== false ) {
			Log::debug('Crawler: ' . $ret['error']);
			return $this->output($ret['error']);
		}
		else {
			$msg = 'Crawler #' . ( $curr_crawler_pos + 1 ) . ' reached end of sitemap file.';
			$msg_t = sprintf( __( 'Crawler %s reached end of sitemap file.', 'litespeed-cache' ), '#' . ( $curr_crawler_pos + 1 ) ) ;
			Log::debug('Crawler: ' . $msg);
			return $this->output($msg_t);
		}
	}

	private function append_blacklist()
	{

	}

	/**
	 * List all crawlers
	 *
	 * @since    1.9.1
	 * @access   public
	 */
	public function list_crawlers( $count_only = false )
	{
		/**
		 * Data structure:
		 * 	[
		 * 		tagA => [
		 * 			valueA => titleA,
		 * 			valueB => titleB
		 * 			...
		 * 		],
		 * 		...
		 * 	]
		 */
		$crawler_factors = array();

		// Add default Guest crawler
		$crawler_factors[ 'uid' ] = array( 0 => __( 'Guest', 'litespeed-cache' ) );

		// WebP on/off
		if ( Media::webp_enabled() ) {
			$crawler_factors[ 'webp' ] = array( 0 => '', 1 => 'WebP' );
		}

		// Mobile crawler
		if ( $this->_options[ Base::O_CACHE_MOBILE ] ) {
			$crawler_factors[ 'mobile' ] = array( 0 => '', 1 => '<font title="Mobile">📱</font>' );
		}

		// Get roles set
		// List all roles
		foreach ( $this->_options[ Base::O_CRAWLER_ROLES ] as $v ) {
			$role_title = '';
			$udata = get_userdata( $v );
			if ( isset( $udata->roles ) && is_array( $udata->roles ) ) {
				$tmp = array_values( $udata->roles );
				$role_title = array_shift( $tmp );
			}
			if ( ! $role_title ) {
				continue;
			}

			$crawler_factors[ 'uid' ][ $v ] = ucfirst( $role_title );
		}

		// Cookie crawler
		foreach ( $this->_options[ Base::O_CRAWLER_COOKIES ] as $v ) {
			if ( empty( $v[ 'name' ] ) ) {
				continue;
			}

			$this_cookie_key = 'cookie:' . $v[ 'name' ];

			$crawler_factors[ $this_cookie_key ] = array();

			foreach ( $v[ 'vals' ] as $v2 ) {
				$crawler_factors[ $this_cookie_key ][ $v2 ] = '<font title="Cookie">🍪</font>' . $v[ 'name' ] . '=' . $v2;
			}
		}

		// Crossing generate the crawler list
		$crawler_list = $this->_recursive_build_crawler( $crawler_factors );

		if ( $count_only ) {
			return count( $crawler_list );
		}

		return $crawler_list;
	}


	/**
	 * Build a crawler list recursively
	 *
	 * @since 2.8
	 * @access private
	 */
	private function _recursive_build_crawler( $crawler_factors, $group = array(), $i = 0 )
	{
		$current_factor = array_keys( $crawler_factors );
		$current_factor = $current_factor[ $i ];

		$if_touch_end = $i + 1 >= count( $crawler_factors );

		$final_list = array();

		foreach ( $crawler_factors[ $current_factor ] as $k => $v ) {

			// Don't alter $group bcos of loop usage
			$item = $group;
			$item[ 'title' ] = ! empty( $group[ 'title' ] ) ? $group[ 'title' ] : '';
			if ( $v ) {
				if ( $item[ 'title' ] ) {
					$item[ 'title' ] .= ' - ';
				}
				$item[ 'title' ] .= $v;
			}
			$item[ $current_factor ] = $k;

			if ( $if_touch_end ) {
				$final_list[] = $item;
			}
			else {
				// Inception: next layer
				$final_list = array_merge( $final_list, $this->_recursive_build_crawler( $crawler_factors, $item, $i + 1 ) );
			}

		}

		return $final_list;
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
			echo $msg;
			// exit();
		}
		else {
			echo "<script>alert('" . htmlspecialchars($msg) . "');</script>";
			// exit;
		}
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function handler()
	{
		$instance = self::get_instance();

		$type = Router::verify_type();

		switch ( $type ) {
			case self::TYPE_REFRESH_MAP :
				Crawler_Map::get_instance()->gen();
				break;

			default:
				break;
		}

		Admin::redirect();
	}

}
