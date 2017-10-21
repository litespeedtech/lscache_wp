<?php

/**
 * The class to operate media data.
 *
 * @since 		1.4
 * @since  		1.5 Moved into /inc
 * @package    	LiteSpeed_Cache
 * @subpackage 	LiteSpeed_Cache/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */

class LiteSpeed_Cache_Media
{
	private static $_instance ;

	const LAZY_LIB = '/min/lazyload.js' ;

	const TYPE_IMG_OPTIMIZE = 'img_optm' ;

	const DB_POSTMETA_OPTIMIZE_DATA = 'litespeed-optimize-data' ;
	const DB_POSTMETA_OPTIMIZE_STATUS = 'litespeed-optimize-status' ;
	const DB_POSTMETA_OPTIMIZE_STATUS_REQUESTED = 'requested' ;
	const DB_POSTMETA_OPTIMIZE_STATUS_SERVER_FINISHED = 'server_finished' ;
	const DB_POSTMETA_OPTIMIZE_STATUS_PULLED = 'pulled' ;

	private $content ;
	private $wp_upload_dir ;
	private $tmp_pid ;
	private $tmp_path ;
	private $_img_in_queue = array() ;
	private $_img_total = 0 ;

	/**
	 * Init
	 *
	 * @since  1.4
	 * @access private
	 */
	private function __construct()
	{
		LiteSpeed_Cache_Log::debug2( 'Media init' ) ;

		$this->_static_request_check() ;

		$this->wp_upload_dir = wp_upload_dir() ;
	}

	/**
	 * Check if the request is for static file
	 *
	 * @since  1.4
	 * @access private
	 * @return  string The static file content
	 */
	private function _static_request_check()
	{
		// This request is for js/css_async.js
		if ( strpos( $_SERVER[ 'REQUEST_URI' ], self::LAZY_LIB ) !== false ) {
			LiteSpeed_Cache_Log::debug( 'Media run lazyload lib' ) ;

			LiteSpeed_Cache_Control::set_cacheable() ;
			LiteSpeed_Cache_Control::set_no_vary() ;
			LiteSpeed_Cache_Control::set_custom_ttl( 8640000 ) ;
			LiteSpeed_Cache_Tag::add( LiteSpeed_Cache_Tag::TYPE_MIN . '_LAZY' ) ;

			$file = LSWCP_DIR . 'js/lazyload.min.js' ;

			header( 'Content-Length: ' . filesize( $file ) ) ;
			header( 'Content-Type: application/x-javascript; charset=utf-8' ) ;

			echo file_get_contents( $file ) ;
			exit ;
		}
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  1.6
	 * @access public
	 */
	public static function handler()
	{
		$instance = self::get_instance() ;

		switch ( LiteSpeed_Cache_Router::verify_type() ) {
			case self::TYPE_IMG_OPTIMIZE :
				$instance->_img_optimize() ;
				break ;

			default:
				break ;
		}

		LiteSpeed_Cache_Admin::redirect() ;
	}

	/**
	 * Push raw img to LiteSpeed server
	 *
	 * @since 1.6
	 * @access private
	 */
	private function _img_optimize()
	{

		LiteSpeed_Cache_Log::debug( 'Media preparing images to push' ) ;

		global $wpdb ;
		// Get images
		$q = "SELECT b.post_id, b.meta_value
			FROM $wpdb->posts a
			LEFT JOIN $wpdb->postmeta b ON b.post_id = a.ID
			LEFT JOIN $wpdb->postmeta c ON c.post_id = a.ID AND c.meta_key = %s
			WHERE a.post_type = 'attachment'
				AND a.post_status = 'inherit'
				AND a.post_mime_type IN ('image/jpeg', 'image/png')
				AND b.meta_key = '_wp_attachment_metadata'
				AND c.post_id IS NULL
			ORDER BY a.ID DESC
			LIMIT %d
			" ;
		$q = $wpdb->prepare( $q, array( self::DB_POSTMETA_OPTIMIZE_STATUS, apply_filters( 'litespeed_img_optimize_max_rows', 500 ) ) ) ;

		$img_set = array() ;
		$list = $wpdb->get_results( $q ) ;
		if ( ! $list ) {
			LiteSpeed_Cache_Log::debug( 'Media optimize bypass: no image found' ) ;
			return ;
		}

		LiteSpeed_Cache_Log::debug( 'Media found images: ' . count( $list ) ) ;

		foreach ( $list as $v ) {

			if ( ! $v->meta_value ) {
				LiteSpeed_Cache_Log::debug( 'Media bypass image due to no meta_value: pid ' . $v->post_id ) ;
				continue ;
			}

			try {
				$meta_value = unserialize( $v->meta_value ) ;
			}
			catch ( \Exception $e ) {
				LiteSpeed_Cache_Log::debug( 'Media bypass image due to meta_value not json: pid ' . $v->post_id ) ;
				continue ;
			}

			if ( empty( $meta_value[ 'file' ] ) ) {
				LiteSpeed_Cache_Log::debug( 'Media bypass image due to no ori file: pid ' . $v->post_id ) ;
				continue ;
			}

			// push orig image to queue
			$this->tmp_pid = $v->post_id ;
			$this->tmp_path = pathinfo( $meta_value[ 'file' ], PATHINFO_DIRNAME ) . '/' ;
			$this->_img_queue( $meta_value, true ) ;
			if ( ! empty( $meta_value[ 'sizes' ] ) ) {
				array_map( array( $this, '_img_queue' ), $meta_value[ 'sizes' ] ) ;
			}

		}

		// push to LiteSpeed server
		if ( ! empty( $this->_img_in_queue ) ) {
			$total_groups = count( $this->_img_in_queue ) ;
			LiteSpeed_Cache_Log::debug( 'Media prepared images to push: gruops ' . $total_groups . ' images ' . $this->_img_total ) ;

			// Push to LiteSpeed server
			$json = LiteSpeed_Cache_Admin_API::post( LiteSpeed_Cache_Admin_API::SAPI_ACTION_IMG_OPTIMIZE, $this->_img_in_queue ) ;

			if ( ! is_array( $json ) ) {
				LiteSpeed_Cache_Log::debug( 'Media: Failed to post to LiteSpeed server ', $json ) ;
				$msg = sprintf( __( 'Failed to push to LiteSpeed server: %s', 'litespeed-cache' ), $json ) ;
				LiteSpeed_Cache_Admin_Display::error( $msg ) ;
				return ;
			}

			// Mark them as requested
			$pids = $json[ 'pids' ] ;
			if ( empty( $pids ) || ! is_array( $pids ) ) {
				LiteSpeed_Cache_Log::debug( 'Media: Failed to parse data from LiteSpeed server ', $pids ) ;
				$msg = sprintf( __( 'Failed to parse data from LiteSpeed server: %s', 'litespeed-cache' ), $pids ) ;
				LiteSpeed_Cache_Admin_Display::error( $msg ) ;
				return ;
			}

			LiteSpeed_Cache_Log::debug( 'Media: posts data from LiteSpeed server count: ' . count( $pids ) ) ;

			// Exclude those who have meta already
			$q = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s and post_id in ( " . implode( ',', array_fill( 0, count( $pids ), '%s' ) ) . " )" ;
			$tmp = $wpdb->get_results( $wpdb->prepare( $q, array_merge( array( self::DB_POSTMETA_OPTIMIZE_STATUS ), $pids ) ) ) ;
			$exists_pids = array() ;
			foreach ( $tmp as $v ) {
				$exists_pids[] = $v->post_id ;
			}
			if ( $exists_pids ) {
				LiteSpeed_Cache_Log::debug( 'Media: existing posts data from LiteSpeed server count: ' . count( $exists_pids ) ) ;
			}
			$pids = array_diff( $pids, $exists_pids ) ;

			if ( ! $pids ) {
				LiteSpeed_Cache_Log::debug( 'Media: Failed to store data from LiteSpeed server with empty pids' ) ;
				LiteSpeed_Cache_Admin_Display::error( __( 'Post data is empty.', 'litespeed-cache' ) ) ;
				return ;
			}

			LiteSpeed_Cache_Log::debug( 'Media: diff posts data from LiteSpeed server count: ' . count( $pids ) ) ;

			$q = "INSERT INTO $wpdb->postmeta ( post_id, meta_key, meta_value ) VALUES " ;
			$data_to_add = array() ;
			foreach ( $pids as $v ) {
				$data_to_add[] = $v ;
				$data_to_add[] = self::DB_POSTMETA_OPTIMIZE_STATUS ;
				$data_to_add[] = self::DB_POSTMETA_OPTIMIZE_STATUS_REQUESTED ;
			}
			// Add placeholder
			$q .= implode( ',', array_map(
				function( $el ) { return '(' . implode( ',', $el ) . ')' ; },
				array_chunk( array_fill( 0, count( $data_to_add ), '%s' ), 3 )
			) ) ;
			// Store data
			$wpdb->query( $wpdb->prepare( $q, $data_to_add ) ) ;


			$accepted_groups = count( $pids ) ;
			$accepted_imgs = $json[ 'total' ] ;

			$msg = sprintf( __( 'Pushed %1$s groups with %2$s images to LiteSpeed optimization server, accepted %3$s groups with %4$s images.', 'litespeed-cache' ), $total_groups, $this->_img_total, $accepted_groups, $accepted_imgs ) ;
			LiteSpeed_Cache_Admin_Display::succeed( $msg ) ;
		}
	}

	/**
	 * Add a new img to queue which will be pushed to LiteSpeed
	 *
	 * @since 1.6
	 * @access private
	 */
	private function _img_queue( $meta_value, $ori_file = false )
	{
		if ( empty( $meta_value[ 'file' ] ) || empty( $meta_value[ 'width' ] ) || empty( $meta_value[ 'height' ] ) ) {
			LiteSpeed_Cache_Log::debug2( 'Media bypass image due to lack of file/w/h: pid ' . $this->tmp_pid, $meta_value ) ;
			return ;
		}

		if ( ! $ori_file ) {
			$meta_value[ 'file' ] = $this->tmp_path . $meta_value[ 'file' ] ;
		}

		// check file exists or not
		$real_file = $this->wp_upload_dir[ 'basedir' ] . '/' . $meta_value[ 'file' ] ;
		if ( ! file_exists( $real_file ) ) {
			LiteSpeed_Cache_Log::debug2( 'Media bypass image due to file not exist: pid ' . $this->tmp_pid . ' ' . $real_file ) ;
			return ;
		}

		LiteSpeed_Cache_Log::debug2( 'Media adding image: pid ' . $this->tmp_pid ) ;

		$img_info = array(
			'url'	=> $this->wp_upload_dir[ 'baseurl' ] . '/' . $meta_value[ 'file' ],
			'width'	=> $meta_value[ 'width' ],
			'height'	=> $meta_value[ 'height' ],
			'mime_type'	=> ! empty( $meta_value[ 'mime_type' ] ) ? $meta_value[ 'mime_type' ] : '' ,
		) ;
		$img_info[ 'md5' ] = md5_file( $real_file ) ;

		if ( empty( $this->_img_in_queue[ $this->tmp_pid ] ) ) {
			$this->_img_in_queue[ $this->tmp_pid ] = array() ;
		}
		$this->_img_in_queue[ $this->tmp_pid ][] = $img_info ;
		$this->_img_total ++ ;
	}

	/**
	 * Count images
	 *
	 * @since 1.6
	 * @access public
	 */
	public function img_count()
	{
		global $wpdb ;
		$q = "SELECT count(*)
			FROM $wpdb->posts a
			LEFT JOIN $wpdb->postmeta b ON b.post_id = a.ID
			WHERE a.post_type = 'attachment'
				AND a.post_status = 'inherit'
				AND a.post_mime_type IN ('image/jpeg', 'image/png')
				AND b.meta_key = '_wp_attachment_metadata'
			" ;
		// $q = "SELECT count(*) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status = 'inherit' AND post_mime_type IN ('image/jpeg', 'image/png') " ;
		$total_img = $wpdb->get_var( $q ) ;

		$q = "SELECT count(*)
			FROM $wpdb->posts a
			LEFT JOIN $wpdb->postmeta b ON b.post_id = a.ID
			LEFT JOIN $wpdb->postmeta c ON c.post_id = a.ID
			WHERE a.post_type = 'attachment'
				AND a.post_status = 'inherit'
				AND a.post_mime_type IN ('image/jpeg', 'image/png')
				AND b.meta_key = '_wp_attachment_metadata'
				AND c.meta_key = %s
				AND c.meta_value= %s
			" ;
		$total_requested = $wpdb->get_var( $wpdb->prepare( $q, array( self::DB_POSTMETA_OPTIMIZE_STATUS, self::DB_POSTMETA_OPTIMIZE_STATUS_REQUESTED ) ) ) ;
		$total_server_finished = $wpdb->get_var( $wpdb->prepare( $q, array( self::DB_POSTMETA_OPTIMIZE_STATUS, self::DB_POSTMETA_OPTIMIZE_STATUS_SERVER_FINISHED ) ) ) ;
		$total_pulled = $wpdb->get_var( $wpdb->prepare( $q, array( self::DB_POSTMETA_OPTIMIZE_STATUS, self::DB_POSTMETA_OPTIMIZE_STATUS_PULLED ) ) ) ;

		return array(
			'total_img'	=> $total_img,
			'total_requested'	=> $total_requested,
			'total_server_finished'	=> $total_server_finished,
			'total_pulled'	=> $total_pulled,
		) ;
	}

	/**
	 * Run lazy load process
	 * NOTE: As this is after cache finalized, can NOT set any cache control anymore
	 *
	 * Only do for main page. Do NOT do for esi or dynamic content.
	 *
	 * @since  1.4
	 * @access public
	 * @return  string The buffer
	 */
	public static function finalize( $content )
	{
		if ( defined( 'LITESPEED_NO_LAZY' ) ) {
			LiteSpeed_Cache_Log::debug2( 'Media bypass: NO_LAZY const' ) ;
			return $content ;
		}

		if ( ! defined( 'LITESPEED_IS_HTML' ) ) {
			LiteSpeed_Cache_Log::debug2( 'Media bypass: Not frontend HTML type' ) ;
			return $content ;
		}

		LiteSpeed_Cache_Log::debug( 'Media start' ) ;

		$instance = self::get_instance() ;
		$instance->content = $content ;

		$instance->_lazyload() ;
		return $instance->content ;
	}

	/**
	 * Run lazyload replacement for images in buffer
	 *
	 * @since  1.4
	 * @access private
	 */
	private function _lazyload()
	{
		$cfg_img_lazy = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_MEDIA_IMG_LAZY ) ;
		$cfg_iframe_lazy = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_MEDIA_IFRAME_LAZY ) ;

		// image lazy load
		if ( $cfg_img_lazy ) {
			$html_list = $this->_parse_img() ;
			$html_list_ori = $html_list ;

			$placeholder = LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_MEDIA_IMG_LAZY_PLACEHOLDER ) ?: LITESPEED_PLACEHOLDER ;

			foreach ( $html_list as $k => $v ) {
				$snippet = '<noscript>' . $v . '</noscript>' ;
				$v = str_replace( array( ' src=', ' srcset=', ' sizes=' ), array( ' data-src=', ' data-srcset=', ' data-sizes=' ), $v ) ;
				$v = str_replace( '<img ', '<img data-lazyloaded="1" src="' . $placeholder . '" ', $v ) ;
				$snippet = $v . $snippet ;

				$html_list[ $k ] = $snippet ;
			}

			$this->content = str_replace( $html_list_ori, $html_list, $this->content ) ;
		}

		// iframe lazy load
		if ( $cfg_iframe_lazy ) {
			$html_list = $this->_parse_iframe() ;
			$html_list_ori = $html_list ;

			foreach ( $html_list as $k => $v ) {
				$snippet = '<noscript>' . $v . '</noscript>' ;
				$v = str_replace( ' src=', ' data-src=', $v ) ;
				$v = str_replace( '<iframe ', '<iframe data-lazyloaded="1" src="about:blank" ', $v ) ;
				$snippet = $v . $snippet ;

				$html_list[ $k ] = $snippet ;
			}

			$this->content = str_replace( $html_list_ori, $html_list, $this->content ) ;
		}

		// Include lazyload lib js and init lazyload
		if ( $cfg_img_lazy || $cfg_iframe_lazy ) {
			$lazy_lib_url = LiteSpeed_Cache_Utility::get_permalink_url( self::LAZY_LIB ) ;
			$this->content = str_replace( '</body>', '<script src="' . $lazy_lib_url . '"></script></body>', $this->content ) ;
		}
	}

	/**
	 * Parse img src
	 *
	 * @since  1.4
	 * @access private
	 * @return array  All the src & related raw html list
	 */
	private function _parse_img()
	{
		/**
		 * Exclude list
		 * @since 1.5
		 */
		$excludes = apply_filters( 'litespeed_cache_media_lazy_img_excludes', get_option( LiteSpeed_Cache_Config::ITEM_MEDIA_LAZY_IMG_EXC ) ) ;
		if ( $excludes ) {
			$excludes = explode( "\n", $excludes ) ;
		}

		$html_list = array() ;

		$content = preg_replace( '#<!--.*-->#sU', '', $this->content ) ;
		preg_match_all( '#<img \s*([^>]+)/?>#isU', $content, $matches, PREG_SET_ORDER ) ;
		foreach ( $matches as $match ) {
			$attrs = LiteSpeed_Cache_Utility::parse_attr( $match[ 1 ] ) ;

			if ( empty( $attrs[ 'src' ] ) ) {
				continue ;
			}

			LiteSpeed_Cache_Log::debug2( 'Media: found: ' . $attrs[ 'src' ] ) ;

			if ( ! empty( $attrs[ 'data-no-lazy' ] ) || ! empty( $attrs[ 'data-lazyloaded' ] ) || ! empty( $attrs[ 'data-src' ] ) || ! empty( $attrs[ 'data-srcset' ] ) ) {
				LiteSpeed_Cache_Log::debug2( 'Media bypassed' ) ;
				continue ;
			}

			/**
			 * Exclude from lazyload by setting
			 * @since  1.5
			 */
			if ( $excludes && LiteSpeed_Cache_Utility::str_hit_array( $attrs[ 'src' ], $excludes ) ) {
				LiteSpeed_Cache_Log::debug2( 'Media: lazyload image exclude ' . $attrs[ 'src' ] ) ;
				continue ;
			}

			// to avoid multiple replacement
			if ( in_array( $match[ 0 ], $html_list ) ) {
				continue ;
			}

			$html_list[] = $match[ 0 ] ;
		}

		return $html_list ;
	}

	/**
	 * Parse iframe src
	 *
	 * @since  1.4
	 * @access private
	 * @return array  All the src & related raw html list
	 */
	private function _parse_iframe()
	{
		$html_list = array() ;

		$content = preg_replace( '#<!--.*-->#sU', '', $this->content ) ;
		preg_match_all( '#<iframe \s*([^>]+)></iframe>#isU', $content, $matches, PREG_SET_ORDER ) ;
		foreach ( $matches as $match ) {
			$attrs = LiteSpeed_Cache_Utility::parse_attr( $match[ 1 ] ) ;

			if ( empty( $attrs[ 'src' ] ) ) {
				continue ;
			}

			LiteSpeed_Cache_Log::debug2( 'Media found iframe: ' . $attrs[ 'src' ] ) ;

			if ( ! empty( $attrs[ 'data-no-lazy' ] ) || ! empty( $attrs[ 'data-lazyloaded' ] ) || ! empty( $attrs[ 'data-src' ] ) ) {
				LiteSpeed_Cache_Log::debug2( 'Media bypassed' ) ;
				continue ;
			}

			// to avoid multiple replacement
			if ( in_array( $match[ 0 ], $html_list ) ) {
				continue ;
			}

			$html_list[] = $match[ 0 ] ;
		}

		return $html_list ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.4
	 * @access public
	 * @return Current class instance.
	 */
	public static function get_instance()
	{
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}

}