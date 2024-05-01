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

	const TYPE_NEXT = 'next';

	const DB_NEED_PULL = 'need_pull';

	private $bk_add = '_res_bk';
	private $meta_bk = 'lsc_resize_bk';
	private $meta_done = 'lsc_resize';
	private $mime_images = array( 'image/jpeg', 'image/png', 'image/gif' );
	
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
		if (empty($this->_summary['current_post_id'])) {
			$this->_summary['current_post_id'] = 0;
		}
		if (empty($this->_summary['current'])) {
			$this->_summary['current'] = 0;
		}
		if (empty($this->_summary['total'])) {
			$this->_summary['total'] = 0;
		}

		// Hooks
	    $this->add_upload_hooks();
	}

	public function update_summary_data(){
		
		self::save_summary();
	}

	public function optimize_next(){
		$summary = $this->get_summary();

		if($summary['current'] <= $summary['total']){
			$params = $this->prepare_parameters_from_id($summary['current_post_id']);
			$this->resize_image($params);

			$this->update_summary_data();
		}
	}

	/**
	 * Add upload file hooks.
	 *
	 * @return void
	 */
	public function add_upload_hooks(){
		// Wordpress default upload filter.
		add_filter('wp_handle_upload', array($this, 'resize_image'));
		add_filter('wp_generate_attachment_metadata', array($this, 'generate_attachment_metadata'), 10, 2);

		// Some plugins will need custom upload adjustment
	}

	/**
	 * Resize functionality.
	 *
	 * @param array $params File with path. Expected keys: type(mime type format), url, file(path to file)
	 * @return void
	 */
	public function resize_image($params){
		// Return if the file is not an image.
		if ( ! in_array( $params['type'], $this->mime_images, true ) ) {
			return $params;
		}

		try{
			if(!$params['file']){
				throw( new \Exception( 'No image sent to resize.' ) );
			}

			// If need a backup.
			if(apply_filters('litespeed_img_resize_original_backup', true)){ // Possible values: true - make backup ; false - do not make backup
				$path_info = pathinfo($params['file']);
				$to = str_replace(
					$path_info['basename'],
					$this->get_backup_name($params['file'], $path_info),
					$params['file']
				);
				
				// Check if backup was done.
				if (!is_file($to)){
					if(!copy($params['file'], $to)) {
						self::debug('[Image Resize] Cannot make backup to file: ' . $params['file'] );
					}
				}
				else {
					self::debug('[Image Resize] Backup exists for file: ' . $params['file'] );
				}
			}

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
	 * Generate custom metas for image.
	 *
	 * @param  mixed $meta
	 * @param  mixed $id
	 * @return void
	 */
	public function generate_attachment_metadata($meta, $id = null){
		$params = $this->prepare_parameters_from_id($id);

		if(
			$params['file'] && 
			in_array( $params['type'], $this->mime_images, true )
		){
			$path_info = pathinfo($params['file']);
			$backup_name = $this->get_backup_name($params['file'], $path_info);
			$backup_path = $path_info['dirname'] . '/' . $backup_name;

			if(is_file($backup_path)){
				$meta[$this->meta_bk] = true;
			}

			if(filesize($params['file']) > filesize($backup_path)){
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
	private function get_backup_name($file_path, $path_info = null){
		// If null sent, get pathinfo from file path.
		!$path_info && $path_info = pathinfo($file_path);

		return $path_info['filename'] . $this->bk_add . '.' . $path_info['extension'];
	}
	
	/**
	 * Prepare parameters from attachment id.
	 *
	 * @param  string|int $id Attachment id.
	 * @return string
	 */
	private function prepare_parameters_from_id( $id ){
		$params = array(
			'file' => null,
			'url' => null,
			'type' => null
		);

		$file_path = wp_get_original_image_path( $id );
		if( $file_path ){
			$url = wp_get_attachment_image_url($id, 'full');
			$type = wp_get_image_mime( $file_path );

			$params['file'] = $file_path;
			$params['url']  = $url ? $url : null;
			$params['type'] = $type ? $type : null;
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
			case self::TYPE_NEXT:
				$this->optimize_next();
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
