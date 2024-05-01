<?php

/**
 * The class to processing image.
 *
 * @since 		6.3
 * @package    	LiteSpeed
 * @subpackage 	LiteSpeed/src
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */

namespace LiteSpeed;

use WpOrg\Requests\Autoload;
use WpOrg\Requests\Requests;

defined('WPINC') || exit();

class Img_Resize extends Base
{
	const LOG_TAG = '📐';

	const TYPE_START = 'start';
	const TYPE_PAUSE = 'pause';
	const TYPE_CONTINUE = 'continue';
	const TYPE_RESTART = 'restart';

	const DB_NEED_PULL = 'need_pull';

	private $bk_add = '_res_bk';
	private $meta_bk = 'lsc_resize_bk';
	private $meta_done = 'lsc_resize';
	
	protected $_summary;
	
	/**
	 * Init
	 *
	 * @return void
	 */
	public function init()
	{
		Debug2::debug2('[Img Processing] init');

		$this->_summary = self::get_summary();
		if (empty($this->_summary['next_post_id'])) {
			$this->_summary['next_post_id'] = 0;
		}
		if (empty($this->_summary['is_running'])) {
			$this->_summary['is_running'] = 0;
		}
		if (empty($this->_summary['ended'])) {
			$this->_summary['ended'] = 0;
		}

		// Hooks
	    $this->add_upload_hooks();
	}


	/**
	 * Cron start async req
	 */
	public static function start_async_cron()
	{
		Task::async_call('imgresize');
	}

	/**
	 * Manually start async req
	 */
	public static function start_async()
	{
		Task::async_call('imgresize_force');

		$msg = __('Started async image processing request', 'litespeed-cache');
		Admin_Display::success($msg);
	}

	/**
	 * Ajax req handler
	 */
	public static function async_handler()
	{
		self::start();
	}

	/**
	 * Set running to done
	 */
	private static function start(){

	}
	
	/**
	 * Break cron operation.
	 *
	 * @return void
	 */
	public function do_break(){
		$this->_summary['is_running'] = 0;
		$this->_summary['ended'] = 0;
		self::save_summary();
	}
	
	/**
	 * Continue cron operation.
	 *
	 * @return void
	 */
	public function do_continue(){
		$this->_summary['is_running'] = time();
		$this->_summary['ended'] = 0;
		self::save_summary();
	}
	
	/**
	 * Break cron operation.
	 *
	 * @return void
	 */
	public function do_restart(){
		$this->_summary['next_post_id'] = 0;
		$this->_summary['is_running'] = time();
		$this->_summary['ended'] = 0;
		self::save_summary();
	}

	/**
	 * Set running to done
	 */
	private function _finished_running()
	{
		$this->_summary['next_post_id'] = 0;
		$this->_summary['is_running'] = 0;
		$this->_summary['ended'] = 1;
		self::save_summary();
	}
	



	/**
	 * Add upload file hooks.
	 *
	 * @return void
	 */
	public function add_upload_hooks(){
		// Wordpress default upload filter.
		add_filter('wp_handle_upload', array($this, 'wp_upload_resize_image'));
		add_filter('wp_generate_attachment_metadata', array($this, 'generate_attachment_metadata'), 10, 2);

		// Some plugins will need custom upload adjustment
	}

		
	/**
	 * Generate metas for images.
	 *
	 * @param  mixed $meta
	 * @param  mixed $id
	 * @return void
	 */
	public function generate_attachment_metadata($meta, $id = null){
		$file_path = wp_get_original_image_path( $id );
		$mime = wp_get_image_mime( $file_path );

		if(
			$file_path && 
			in_array( $mime, array( 'image/jpeg', 'image/png', 'image/gif' ), true )
		){
			$path_info = pathinfo($file_path);
			$backup_name = $this->get_resize_bk_name($file_path);

			if(is_file($path_info['dirname'].'/'.$backup_name)){
				$meta[$this->meta_bk] = true;
			}

			if(filesize($file_path) > filesize($backup_name)){
				$meta[$this->meta_done] = true;
			}
		}

		return $meta;
	}
	
	/**
	 * Get image backup name.
	 *
	 * @param  string $file_path File with path.
	 * @param  array $path_info Path info of file.
	 * @return string
	 */
	private function get_resize_bk_name($file_path, $path_info = null){
		// If not sent, get new pathinfo.
		!$path_info && $path_info = pathinfo($file_path);

		return $path_info['filename'] . $this->bk_add . '.' . $path_info['extension'];
	}

	/**
	 * WP upload hooks - add resize functionality.
	 *
	 * @return void
	 */
	public function wp_upload_resize_image($params){
		// Return if the file is not an image.
		if ( ! in_array( $params['type'], array( 'image/jpeg', 'image/png', 'image/gif' ), true ) ) {
			return $params;
		}

		// If need a backup.
		if(apply_filters('litespeed_img_resize_original_backup', true)){ // Possible values: true - make backup ; false - do not make backup
			$path_info = pathinfo($params['file']);
			$to = str_replace(
				$path_info['basename'],
				$this->get_resize_bk_name($params['file'], $path_info),
				$params['file']
			);

			if (!copy($params['file'], $to)) {
				self::debug('Image Resize: cannot make backup to file: ' . $params['file'] );
			}
		}

		try{
			$resize_style = apply_filters('litespeed_img_resize_style', 0); // Possible values: 0 - keep width ; 1 - keep height.
			$resize_crop = apply_filters('litespeed_img_resize_crop', false); // Possible values: see https://developer.wordpress.org/reference/classes/wp_image_editor/resize/

			// Get sizes.
			$resize_size = $this->conf( self::O_IMG_OPTM_RESIZE_SIZE );
			// Ensure correct size format.
			if( strstr( 'x', $resize_size ) === false ){
				// If resize to keep width.
				if($resize_style === 0) $resize_size .= 'x0';
				// If resize to keep height.
				else if($resize_style === 1) $resize_size = '0x' . $resize_size;
			}
			$resize_size = explode( 'x', $resize_size );

			// Image editor.
			$editor = wp_get_image_editor( $params['file'] );
			if ( is_wp_error( $editor ) ) {
				throw( new \Exception( 'Editor cannot be created. ' . $editor->get_error_message() ) );
			}

			// Prepare what to do.
			$editor->resize( 
				$resize_style === 0 ? (int) $resize_size[0] : null, // resize by width
				$resize_style === 1 ? (int) $resize_size[1] : null,  // resize by height
				$resize_crop
			);
			$editor->set_quality( 100 );

			// Save new file.
			$saved = $editor->save($params['file']);
			if ( is_wp_error( $saved ) ) {
				throw( new \Exception( 'Error resizing. ' . $saved->get_error_message() ) );
			}

			// Done.
			self::debug('[Image Resize] Done: ' . $params['url'] );
		}
		catch(\Exception $e){
			self::debug('[Image Resize] Cannot change file: ' . $params['url'] . ': ' . $e );
		}

		return $params;
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @access public
	 */
	public function handler()
	{
		$type = Router::verify_type();

		switch ($type) {
			case self::TYPE_START:
				self::start_async();
				break;
			case self::TYPE_PAUSE:
				$this->do_break();
				break;
			case self::TYPE_CONTINUE:
				self::do_continue();
				break;
			case self::TYPE_RESTART:
				$this->do_restart();
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
