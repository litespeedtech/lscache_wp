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

defined('WPINC') || exit();

class Img_Resize extends Base
{
	const LOG_TAG = '📐';

	const TYPE_NEXT = 'next';
	const TYPE_START = 'start';
	const TYPE_RECALCULATE = 'recalculate';
	const TYPE_DELETE_BK = 'delete_bk';

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

	// TODO: Under image lib, could have two buttons: 1. resize. 2. switch backup/resized imag 3. details about resize
	
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

		// Hooks
	    $this->add_upload_hooks();
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

			// TODO: on delete image, delete extra: file
			
			// Wordpress default upload filter.
			add_filter( 'wp_handle_upload', array( $this, 'wp_resize_image' ));
			// TODO: find better filter?!
			add_filter( 'wp_generate_attachment_metadata', array( $this, 'generate_attachment_metadata' ), 10, 2);
	
			// Some plugins will need custom upload adjustment
		}
	}

	/**
	 * Delete resize data.
	 *
	 * @access public
	 */
	public function reset_row($post_id)
	{
		global $wpdb;

		if (!$post_id) {
			return;
		}

		// self::debug('_reset_row [pid] ' . $post_id);

		// # TODO: Load image sub files
		// $img_q = "SELECT b.post_id, b.meta_value
		// 	FROM `$wpdb->postmeta` b
		// 	WHERE b.post_id =%d  AND b.meta_key = '_wp_attachment_metadata'";
		// $q = $wpdb->prepare($img_q, array($post_id));
		// $v = $wpdb->get_row($q);

		// $meta_value = $this->_parse_wp_meta_value($v);
		// if ($meta_value) {
		// 	$this->tmp_pid = $v->post_id;
		// 	$this->tmp_path = pathinfo($meta_value['file'], PATHINFO_DIRNAME) . '/';
		// 	$this->_destroy_optm_file($meta_value, true);
		// 	if (!empty($meta_value['sizes'])) {
		// 		array_map(array($this, '_destroy_optm_file'), $meta_value['sizes']);
		// 	}
		// }

		// delete_post_meta($post_id, self::DB_SIZE);
		// delete_post_meta($post_id, self::DB_SET);

		// $msg = __('Reset the optimized data successfully.', 'litespeed-cache');
		// Admin_Display::succeed($msg);
	}
	
	/**
	 * Update summary with necessary data.
	 *
	 * @param  bool $go_to_next
	 * @return void
	 */
	public function update_summary_data($go_to_next = true){
		global $wpdb;
		$meta_name_data = $this->get_meta_name();
		$select_is_attachment = $wpdb->prepare(
			'SELECT ID FROM %i WHERE post_type = %s AND %i LIKE %s', 
			array(
				$wpdb->posts,
				'attachment',
				'post_mime_type',
				'%image/%'
			)
		);

		if($go_to_next){
			// Next image will become Current image.
			$select_with_meta_resize = $wpdb->prepare(
				'SELECT b.%i FROM %i AS b WHERE b.%i = %s AND b.%i = a.%i',
				array(
					'meta_id',
					$wpdb->postmeta,
					'meta_key',
					$meta_name_data,
					'post_id',
					'post_id',
				)
			);
			$sql = "SELECT a.%i FROM %i AS a WHERE a.%i IN (" . $select_is_attachment . ") AND NOT EXISTS (" . $select_with_meta_resize . ") ORDER BY a.%i ASC LIMIT 1";
			$prepare_sql = $wpdb->prepare(
				$sql,
				array(
					'post_id',
					$wpdb->postmeta,
					'post_id',
					'post_id'
				)
			);
			$current_image = $wpdb->get_var( $prepare_sql );
			if($current_image){
				$this->_summary[self::S_CURRENT_POST] = $current_image;
			}
		}

		// Get totals
		$prepare_sql = $select_is_attachment;
		$total_posts = $wpdb->get_var( $prepare_sql );
		if($total_posts){
			$this->_summary[self::S_TOTAL] = $total_posts;
		}

		// Get current
		$sql = "SELECT COUNT(a.%i) FROM %i AS a WHERE %i = %s";
		$prepare_sql = $wpdb->prepare(
			$sql,
			array(
				'post_id',
				$wpdb->postmeta,
				'meta_key',
				$meta_name_data
			)
		);
		$current_posts = $wpdb->get_var( $prepare_sql );
		if($current_posts){
			$this->_summary[self::S_CURRENT] = $current_posts;
		}

		self::save_summary();
	}
	
	/**
	 * Get meta name.
	 *
	 * @return string
	 */
	public function get_meta_name(){
		return self::DB_PREFIX . '-' . self::DB_DATA;
	}
	
	/**
	 * Resize image from button
	 *
	 * @param  mixed $update_summary_first
	 * @return void
	 */
	public function resize_next($update_summary_first = false){
		if($this->conf( self::O_IMG_OPTM_RESIZE )){
			// Do summary before resize + go to next. Eg: for first run.
			$update_summary_first && $this->update_summary_data();
			// Get summary.
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
	 * Set big image size threshold in WP > 5.3.0
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
	 * Resize style. Possible values: 0 - keep width ; 1 - keep height.
	 *
	 * @return void
	 */
	public function resize_style(){
		return apply_filters('litespeed_img_resize_style', 1);
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
			if(
				$current_sizes['width'] > $resize_size[0] ||
				$current_sizes['height'] > $resize_size[1]
			){
				$backup_path = $this->get_backup_path( $params['file'], $path_info);

				// If need a backup.
				$upload_dir = wp_upload_dir();
				$meta_add[ self::RES_BK ] = str_replace($upload_dir['basedir'], '', $backup_path);
				if( apply_filters( 'litespeed_img_resize_original_backup', !$this->conf( self::O_IMG_OPTM_STOP_BK ) ) ){ // Possible values: true - make backup ; false - do not make backup
					// Check if backup was done.
					if ( ! is_file($backup_path) ){
						// Cannot make backup.
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
	
	/**
	 * Ensure the resize from settings is formatted correctly and return it.
	 *
	 * @return array
	 */
	public function get_formatted_resize_size(){
		// Get sizes.
		$resize_size = $this->conf( self::O_IMG_OPTM_RESIZE_SIZE );

		$resize = explode( 'x', $resize_size );
		// Ensure correct size format. Null is used in resize function to get auto value for the missing size.
		if( strstr( $resize_size, 'x' ) === false ){
			$resize_style = $this->resize_style();

			// If resize to keep width.
			if($resize_style === 0) $resize = [ $resize_size, null ];
			// If resize to keep height.
			if($resize_style === 1) $resize = [ null, $resize_size ];
		}

		return $resize;
	}
		
	/**
	 * Generate custom metas for image.
	 *
	 * @param  mixed $meta
	 * @param  mixed $id
	 * @return void
	 */
	public function generate_attachment_metadata($meta, $id = null){
		// Get file info from post id.
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
	 * Generate post metas from parameters.
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
					self::RES_NEW_SIZE => $data[self::RES_NEW_SIZE] ? $data[self::RES_NEW_SIZE] : 0,
					self::RES_ORIGINAL_SIZE => $data[self::RES_ORIGINAL_SIZE] ? $data[self::RES_ORIGINAL_SIZE] : 0,
					self::RES_BK   => $data[self::RES_BK],
				));

				// Delete file.
				unlink( $lsc_file );
			}
		}
	}
	
	/**
	 * Get image backup name from image path.
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
	 * Get image backup path from image path.
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
	 * Prepare parameters for resize from attachment id.
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
				$url = wp_get_attachment_image_url( $id, 'full' );
				$type = wp_get_image_mime( $file_path );

				$params['file'] = $file_path;
				$params['url']  = $url ? $url : null;
				$params['type'] = $type ? $type : null;
			}
		}

		return $params;
	}
	
	/**
	 * Recalculate summary.
	 *
	 * @return void
	 */
	private function recalculate_summary( $go_to_next ){
		$this->update_summary_data( $go_to_next );
	}
	
	/**
	 * Delete backups.
	 *
	 * @return void
	 */
	private function delete_backups(){
		global $wpdb;
		$meta_name_data = $this->get_meta_name();
		$errors = 0;
		$dones = 0;
		
		$select_with_meta_resize = $wpdb->prepare(
			'SELECT %i, %i, %i FROM %i WHERE %i = %s',
			array(
				'meta_id',
				'post_id',
				'meta_value',
				$wpdb->postmeta,
				'meta_key',
				$meta_name_data
			)
		);
		$attachments = $wpdb->get_results($select_with_meta_resize);

		if( $attachments && count( $attachments ) > 0 ){
			try{
				$upload_dir = wp_upload_dir();
				foreach( $attachments as $attachment ){
					if( $attachment->meta_value ){
						$meta = unserialize( $attachment->meta_value );
						if($meta[self::RES_BK]){
							$path = $upload_dir['basedir'] . $meta[self::RES_BK];
							if( !unlink($path) ){
								$errors++;
								self::debug( '[Image Resize] Cannot delete backup image: ' . $path );
							}

							$meta[self::RES_BK] = 0;
							update_post_meta( $attachment->post_id, $meta_name_data, $meta );
							$dones++;
						}
					}
				}

				if($dones > 0){
					$msg = sprintf(
						__('Backup images(%s) have been deleted.', 'litespeed-cache'),
						$dones
					);
					if($errors){
						$msg .= ' ' . sprintf(
							__('There were some errors(%s). You can check debug log to see the error.', 'litespeed-cache'),
							$errors
						);
						Admin_Display::error($msg);
					}
					else{
						Admin_Display::success($msg);
					}
				}
				else{
					$msg = __('No backup images found.', 'litespeed-cache');
					Admin_Display::info($msg);
				}
			}
			catch( \Exception $e ){
				self::debug( '[Image Resize] Cannot delete all backup images: ' . $e );
				
				$msg = __('Error deleting backups.', 'litespeed-cache');
				Admin_Display::error($msg);
			}
		}
		else{
			$msg = __('No backups found.', 'litespeed-cache');
			Admin_Display::info($msg);
		}
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
				$this->resize_next();
				break;
			case self::TYPE_START:
				$this->resize_next(true);
				break;
			case self::TYPE_RECALCULATE:
				$this->recalculate_summary(false);
				break;
			case self::TYPE_DELETE_BK:
				$this->delete_backups();
				break;
			default:
				break;
		}

		Admin::redirect();
	}
}
