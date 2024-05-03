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
	const TYPE_START = 'start';
	const TYPE_RECALCULATE = 'recalculate';

	const DB_PREFIX = 'litespeed-resize';
	const DB_DATA = 'data';
	
	const BK_ADD = '_res_bk';

	const RES_DONE = 'done';
	const RES_BK = 'has_bk';
	const RES_ORIGINAL_SIZE = 'orig_size';
	const RES_NEW_SIZE = 'new_size';

	const S_CURRENT_POST = 'current_post';
	const S_CURRENT = 'current';
	const S_TOTAL = 'total';

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
		if (empty($this->_summary[self::S_CURRENT_POST])) {
			$this->_summary[self::S_CURRENT_POST] = 0;
		}
		if (empty($this->_summary[self::S_CURRENT])) {
			$this->_summary[self::S_CURRENT] = 0;
		}
		if (empty($this->_summary[self::S_TOTAL])) {
			$this->_summary[self::S_TOTAL] = 0;
		}

		// add_filter( 'big_image_size_threshold', array( $this, 'set_big_image_size_threshold'), 10, 1 );
		// Hooks
	    $this->add_upload_hooks();
	}

	public function update_summary_data($go_to_next = true){
		global $wpdb;
		$meta_name_data = $this->get_meta_name();

		// Next image will become Current image.
		$select_is_attachment = $wpdb->prepare(
			'SELECT ID FROM %i WHERE post_type = %s', 
			$wpdb->posts,
			'attachment'
		);
		$select_with_meta_resize = $wpdb->prepare(
			'SELECT b.%i FROM %i AS b WHERE b.%i = %s AND b.%i = a.%i',
			'meta_id',
			$wpdb->postmeta,
			'meta_key',
			$meta_name_data,
			'post_id',
			'post_id',
		);
		$sql = "SELECT a.%i
			FROM %i AS a
			WHERE
				a.%i IN (" . $select_is_attachment . ") AND
				NOT EXISTS (" . $select_with_meta_resize . ")
			ORDER BY a.%i ASC
			LIMIT 1";
		$prepare_sql = $wpdb->prepare(
			$sql,
			'post_id',
			$wpdb->postmeta,
			'post_id',
			'post_id'
		);
		// var_dump('--<br />', $prepare_sql);
		$current_image = $wpdb->get_var( $prepare_sql );
		if($current_image){
			$this->_summary[self::S_CURRENT_POST] = $current_image;
		}

		// Get totals
		$sql = "SELECT COUNT(%i) FROM %i WHERE %i = %s AND %i LIKE %s";
		$prepare_sql = $wpdb->prepare(
			$sql,
			'ID',
			$wpdb->posts,
			'post_type',
			'attachment',
			'post_mime_type',
			'%image/%'
		);
		// var_dump('--<br />', $prepare_sql);
		$total_posts = $wpdb->get_var( $prepare_sql );
		if($total_posts){
			$this->_summary[self::S_TOTAL] = $total_posts;
		}

		// Get current
		$sql = "SELECT COUNT(a.%i) FROM %i AS a WHERE %i = %s";
		$prepare_sql = $wpdb->prepare(
			$sql,
			'post_id',
			$wpdb->postmeta,
			'meta_key',
			$meta_name_data
		);
		// var_dump('--<br />', $prepare_sql);
		$current_posts = $wpdb->get_var( $prepare_sql );
		if($current_posts){
			$this->_summary[self::S_CURRENT] = $current_posts;
		}

		// var_dump('--<br />', $this->_summary);
		// die();
		self::save_summary();
	}

	public function get_meta_name(){
		return self::DB_PREFIX . '-' . self::DB_DATA;
	}

	public function optimize_next($update_summary_first = false){
		if($this->conf( self::O_IMG_OPTM_RESIZE )){
			// Do summary before resize. Eg: for first run.
			$update_summary_first && $this->update_summary_data();
			$summary = $this->get_summary();

			if($summary['current'] <= $summary['total']){
				$params = $this->prepare_parameters_from_id($summary[self::S_CURRENT_POST]);
				$result = $this->resize_image($params);

				if($result){
					$this->generate_attachment_metadata(array(), $summary[self::S_CURRENT_POST]);
				
					$msg = __('Done resizing current image.', 'litespeed-cache');
					Admin_Display::success($msg);
				}
				else{
					add_post_meta(
						$summary[self::S_CURRENT_POST], 
						$this->get_meta_name(),
						array(
							self::RES_NEW_SIZE => 0,
							self::RES_ORIGINAL_SIZE => 0,
							self::RES_BK   => 0,
						)
					);
					$msg = sprintf(
						__('Cannot resize image #%s. Skipping this image.', 'litespeed-cache'),
						$summary[self::S_CURRENT_POST]
					);
					Admin_Display::error($msg);
				}
				$this->update_summary_data();
			}
		}
		else{
			$msg = __('Image resize is turned off.', 'litespeed-cache');
			Admin_Display::error($msg);
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
			add_filter( 'wp_handle_upload', array( $this, 'wp_resize_image' ));
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
	 * WP Resize functionality.
	 *
	 * @param array $params File with path. Expected keys: type(mime type format), url, file(path to file)
	 * @return void
	 */
	public function wp_resize_image($params){
		$this->resize_image($params);

		return $params;
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
			return false;
		}

		try{
			if( ! $params['file'] ){
				throw( new \Exception( 'No image sent to resize.' ) );
			}
			$path_info = pathinfo($params['file']);
			$meta_add = array(
				self::RES_DONE => 0,
			);
			
			// Get resize size.
			$resize_size = $this->get_formatted_resize_size();

			// Image editor for current image.
			$editor = wp_get_image_editor( $params['file'] );
			if ( is_wp_error( $editor ) ) {
				throw( new \Exception( 'Editor cannot be created. ' . $editor->get_error_message() ) );
			}
			$current_sizes = $editor->get_size();

			// Do resize if needed.
			if($current_sizes['width'] > $resize_size[0] || $current_sizes['width'] > $resize_size[1]){
				$backup_path = $this->get_backup_path($params['file'], $path_info);

				// If need a backup.
				$meta_add[ self::RES_BK ] = 1;
				$make_backup = apply_filters('litespeed_img_resize_original_backup', true);
				if($make_backup){ // Possible values: true - make backup ; false - do not make backup
					// Check if backup was done.
					if ( ! is_file($backup_path) ){
						if( ! copy( $params['file'], $backup_path ) ) {
							self::debug('[Image Resize] Cannot make backup to file: ' . $params['file'] );
							$meta_add[ self::RES_BK ] = 0;
						}
					}
					else {
						self::debug('[Image Resize] Backup exists for file: ' . $params['file'] );
					}
				}

				// Add crop image data.
				$resize_crop = apply_filters('litespeed_img_resize_crop', false); // Possible values: see https://developer.wordpress.org/reference/classes/wp_image_editor/resize/

				// Prepare what to do.
				$editor->resize( $resize_size[0], $resize_size[1], $resize_crop );
				$editor->set_quality( 100 );

				// Save resized image.
				$saved = $editor->save( $params['file'] );
				if ( is_wp_error( $saved ) ) {
					throw( new \Exception( 'Error resizing: ' . $saved->get_error_message() ) );
				}

				// Done.
				self::debug('[Image Resize] Done: ' . $params['url'] );
				$meta_add[self::RES_ORIGINAL_SIZE] = filesize( $backup_path );
				$meta_add[self::RES_NEW_SIZE] = filesize( $params['file'] );
				$meta_add[self::RES_DONE] = 1;
			}
			else{
				$meta_add[self::RES_DONE] = 1;
			}

			// Save status of image upload. Temp to send data for meta.
			file::save( $path_info['dirname'] . '/' . $path_info['filename'] . '.lsc', json_encode($meta_add) );
			return true;
		}
		catch(\Exception $e){
			self::debug('[Image Resize] Cannot change file: ' . $params['url'] . ': ' . $e );
			return false;
		}
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
		$meta_name_data = $this->get_meta_name();

		// Create metas if do not exist.
		if( ! isset( $post_meta_attachment[$meta_name_data] ) ){
			$path_info = pathinfo($params['file']);
			$lsc_file  = $path_info['dirname'] . '/' . $path_info['filename'] . '.lsc';

			if( is_file( $lsc_file ) ){
				$data = file::read($lsc_file);
				$data = json_decode($data, true);
				
				// Add data meta.
				add_post_meta($id, $meta_name_data, array(
					self::RES_NEW_SIZE => $data[self::RES_NEW_SIZE],
					self::RES_ORIGINAL_SIZE => $data[self::RES_ORIGINAL_SIZE],
					self::RES_BK   => $data[self::RES_BK],
				));

				// Delete file.
				unlink( $lsc_file );
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

		return $path_info['filename'] . self::BK_ADD . '.' . $path_info['extension'];
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

	private function recalculate_summary( $id ){
		$this->update_summary_data(false);
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
			case self::TYPE_START:
				$this->optimize_next(true);
				break;
			case self::TYPE_RECALCULATE:
				$this->recalculate_summary(true);
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
