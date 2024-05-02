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

	private $db_prefix = 'litespeed-resize';
	private $db_status = 'status';
	private $db_data = 'data';
	
	private $res_need = 'need';
	private $res_done = 'done';
	private $res_bk = 'has_bk';
	private $res_orig_size = 'orig_size';
	private $res_new_size = 'new_size';
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

		// add_filter( 'big_image_size_threshold', array( $this, 'set_big_image_size_threshold'), 10, 1 );
		// Hooks
	    $this->add_upload_hooks();
	}

	public function update_summary_data(){
		$sql = 'aaa';
		var_dump($sql);
		die();
		//self::save_summary();
	}

	public function optimize_next(){
		$this->update_summary_data();
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
		// If image resize optimization is ON, do resize.
		if($this->conf( self::O_IMG_OPTM_RESIZE )){
			if ( version_compare( get_bloginfo( 'version' ), '5.3.0', '>=' ) ) {
				add_filter( 'big_image_size_threshold', array( $this, 'set_big_image_size_threshold'), 10, 1 );
			}

			// TODO: on delete image, delete extra.
			
			// Wordpress default upload filter.
			add_filter( 'wp_handle_upload', array( $this, 'resize_image' ));
			// TODO: find better filter?!
			add_filter( 'wp_generate_attachment_metadata', array( $this, 'generate_attachment_metadata' ), 10, 2);
	
			// Some plugins will need custom upload adjustment
		}
	}
	
	/**
	 * Set big image size threshold
	 *
	 * @param  mixed $size Current size.
	 * @return int
	 */
	public function set_big_image_size_threshold( $size ){
		$resize_size = $this->get_formatted_resize_size();

		return max(
			(int) $resize_size[0],
			(int) $resize_size[1],
			(int) $size
		) + 1;
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
			if( ! $params['file'] ){
				throw( new \Exception( 'No image sent to resize.' ) );
			}
			$path_info = pathinfo($params['file']);
			$meta_add = array(
				$this->res_done => 1,
				$this->res_need => 1,
			);
			
			// Get sizes.
			$resize_size = $this->get_formatted_resize_size();

			// Image editor.
			$editor = wp_get_image_editor( $params['file'] );
			if ( is_wp_error( $editor ) ) {
				throw( new \Exception( 'Editor cannot be created. ' . $editor->get_error_message() ) );
			}
			$current_sizes = $editor->get_size();

			// Test if resize is needed.
			if($current_sizes['width'] > $resize_size[0] || $current_sizes['width'] > $resize_size[1]){
				$backup_path = $this->get_backup_path($params['file'], $path_info);

				// If need a backup.
				$meta_add[ $this->res_bk ] = 1;
				if(apply_filters('litespeed_img_resize_original_backup', true)){ // Possible values: true - make backup ; false - do not make backup
					// Check if backup was done.
					if ( ! is_file($backup_path) ){
						if( ! copy( $params['file'], $backup_path ) ) {
							self::debug('[Image Resize] Cannot make backup to file: ' . $params['file'] );
							$meta_add[ $this->res_bk ] = 0;
						}
					}
					else {
						self::debug('[Image Resize] Backup exists for file: ' . $params['file'] );
					}
				}

				// Resize image.
				$resize_crop = apply_filters('litespeed_img_resize_crop', false); // Possible values: see https://developer.wordpress.org/reference/classes/wp_image_editor/resize/

				// Prepare what to do.
				$editor->resize( $resize_size[0], $resize_size[1], $resize_crop );
				$editor->set_quality( 100 );

				// Save new file.
				$saved = $editor->save( $params['file'] );
				if ( is_wp_error( $saved ) ) {
					throw( new \Exception( 'Error resizing. ' . $saved->get_error_message() ) );
				}

				// Done.
				self::debug('[Image Resize] Done: ' . $params['url'] );
				$meta_add[$this->res_orig_size] = filesize( $backup_path );
				$meta_add[$this->res_new_size] = filesize( $params['file'] );
			}
			else{
				$meta_add[$this->res_done] = 0;
				$meta_add[$this->res_need] = 0;
			}

			// Save status of image upload. Temp to send data for meta.
			file::save( $path_info['dirname'] . '/' . $path_info['filename'] . '.lsc', json_encode($meta_add) );
		}
		catch(\Exception $e){
			self::debug('[Image Resize] Cannot change file: ' . $params['url'] . ': ' . $e );
		}

		return $params;
	}

	public function get_formatted_resize_size(){
		// Get sizes.
		$resize_size = $this->conf( self::O_IMG_OPTM_RESIZE_SIZE );

		// Ensure correct size format.
		if( strstr( 'x', $resize_size ) === false ){
			$resize_style = apply_filters('litespeed_img_resize_style', 0); // Possible values: 0 - keep width ; 1 - keep height.

			// If resize to keep width.
			if($resize_style === 0) $resize_size .= 'xnull';
			// If resize to keep height.
			else if($resize_style === 1) $resize_size = 'nullx' . $resize_size;
		}
		$resize_size = explode( 'x', $resize_size );

		// Ensure there is null NOT 'null'. Needed for resize function.
		$resize_size[0] = $resize_size[0] === 'null' ? null : $resize_size[0];
		$resize_size[1] = $resize_size[1] === 'null' ? null : $resize_size[1];

		return $resize_size;
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

		try{
			if(
				$params['file'] && 
				in_array( $params['type'], $this->mime_images, true )
			){
				$this->generate_post_meta($params, $id);
			}
		}
		catch(\Exception $e){
			return $meta;
		}

		return $meta;
	}
	
	/**
	 * Generate post metas.
	 *
	 * @param  array $params Image data.
	 * @param  mixed $id Post id.
	 * @return void
	 */
	public function generate_post_meta($params, $id){
		$post_meta_attachment = get_post_meta($id);
		$meta_name_status = $this->db_prefix.'-'.$this->db_status;

		// Create if meta do not exist.
		if( !$post_meta_attachment[$meta_name_status] ){
			$path_info = pathinfo($params['file']);
			$lsc_file = $path_info['dirname'] . '/' . $path_info['filename'] . '3.lsc';

			if(is_file($lsc_file)){
				$data = file::read($lsc_file);
				$data = json_decode($data, true);
				
				add_post_meta($id, $this->db_prefix.'-'.$this->db_status, array(
					$this->res_done => $data[$this->res_done],
					$this->res_need => $data[$this->res_need],
					$this->res_bk   => $data[$this->res_bk],
				));
				add_post_meta($id, $this->db_prefix.'-'.$this->db_data, array(
					$this->res_new_size => $data[$this->res_new_size],
					$this->res_orig_size => $data[$this->res_orig_size],
				));
			}
			else{
				add_post_meta($id, $this->db_prefix.'-'.$this->db_status, array(
					$this->res_done => true,
					$this->res_need => false,
				));
			}
		}
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
	 * Get image backup path.
	 *
	 * @param  string $file_path File with path.
	 * @param  array $path_info Path info of file.
	 * @return string
	 */
	private function get_backup_path($file_path, $path_info = null){
		// If null sent, get pathinfo from file path.
		!$path_info && $path_info = pathinfo($file_path);

		$backup_name = $this->get_backup_name( $file_path, $path_info );
		
		return $path_info['dirname'] . '/' . $backup_name;
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
		$metas = wp_get_attachment_metadata( $id );

		if(isset($metas['file'])){
			$upload_dir = wp_upload_dir();
			$file_path = $upload_dir['basedir'] . '/' . $metas['file'];

			if( $file_path ){
				$url = wp_get_attachment_image_url( $id, 'full' ); // TODO: WP 4.6
				$type = wp_get_image_mime( $file_path ); // TODO: WP 4.7.1

				$params['file'] = $file_path;
				$params['url']  = $url ? $url : null;
				$params['type'] = $type ? $type : null;
			}
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
