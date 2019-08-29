<?php
/**
 * The Crawler Sitemap Class
 *
 *
 * @since      	1.1.0
 * @since  		1.5 Moved into /inc
 * @package    	LiteSpeed
 * @subpackage 	LiteSpeed/inc
 * @author     	LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed ;

defined( 'WPINC' ) || exit ;

class Crawler_Sitemap
{
	private static $_instance ;
	private $home_url ;// Used to simplify urls

	protected $_urls = array() ;

	/**
	 * Instantiate the class
	 *
	 * @since 1.1.0
	 * @access private
	 */
	private function __construct()
	{
		if ( is_multisite() ) {
			$this->home_url = get_home_url( get_current_blog_id() ) ;
		}
		else{
			$this->home_url = get_home_url() ;
		}
	}

	/**
	 * Generate all urls
	 *
	 * @since 1.1.0
	 * @access public
	 */
	public function generate_data($blacklist = array())
	{
		global $wpdb ;

		$optionOrderBy = Core::config( Conf::O_CRWL_ORDER_LINKS ) ;

		$show_pages = Core::config( Conf::O_CRWL_PAGES ) ;

		$show_posts = Core::config( Conf::O_CRWL_POSTS ) ;

		$show_cats = Core::config( Conf::O_CRWL_CATS ) ;

		$show_tags = Core::config( Conf::O_CRWL_TAGS ) ;

		switch ( $optionOrderBy ) {
			case 'date_asc':
				$orderBy = " ORDER BY post_date ASC" ;
				break ;

			case 'alpha_desc':
				$orderBy = " ORDER BY post_title DESC" ;
				break ;

			case 'alpha_asc':
				$orderBy = " ORDER BY post_title ASC" ;
				break ;

			case 'date_desc':
			default:
				$orderBy = " ORDER BY post_date DESC" ;
				break ;
		}

		$post_type_array = array() ;
		if ( isset($show_pages) && $show_pages == 1 ) {
			$post_type_array[] = 'page' ;
		}

		if ( isset($show_posts) && $show_posts == 1 ) {
			$post_type_array[] = 'post' ;
		}

		if ( $excludeCptArr = Core::config( Conf::O_CRWL_EXC_CPT ) ) {
			$cptArr = get_post_types() ;
			$cptArr = array_diff($cptArr, array('post', 'page')) ;
			$cptArr = array_diff($cptArr, $excludeCptArr) ;
			$post_type_array = array_merge($post_type_array, $cptArr) ;
		}

		if ( ! empty($post_type_array) ) {
			Log::debug( 'Crawler sitemap log: post_type is ' . implode( ',', $post_type_array ) ) ;

			$q = "SELECT ID, post_date FROM $wpdb->posts where post_type IN (" . implode( ',', array_fill( 0, count( $post_type_array ), '%s' ) ) . ") AND post_status='publish' $orderBy" ;
			$results = $wpdb->get_results( $wpdb->prepare( $q, $post_type_array ) ) ;

			foreach ( $results as $result ){
				$slug = str_replace($this->home_url, '', get_permalink($result->ID)) ;
				if ( ! in_array($slug, $blacklist) ) {
					$this->_urls[] = $slug ;
				}
			}
		}

		//Generate Categories Link if option checked
		if ( isset($show_cats) && $show_cats == 1 ) {
			$cats = get_terms("category", array("hide_empty"=>true, "hierarchical"=>false)) ;
			if ( $cats && is_array($cats) && count($cats) > 0 ) {
				foreach ( $cats as $cat ) {
					$slug = str_replace($this->home_url, '', get_category_link($cat->term_id)) ;
					if ( ! in_array($slug, $blacklist) ){
						$this->_urls[] = $slug ;//var_dump($slug);exit;//todo: check permalink
					}
				}
			}
		}

		//Generate tags Link if option checked
		if ( isset($show_tags) && $show_tags == 1 ) {
			$tags = get_terms("post_tag", array("hide_empty"=>true, "hierarchical"=>false)) ;
			if ( $tags && is_array($tags) && count($tags) > 0 ) {
				foreach ( $tags as $tag ) {
					$slug = str_replace($this->home_url, '', get_tag_link($tag->term_id)) ;
					if ( ! in_array($slug, $blacklist) ) {
						$this->_urls[] = $slug ;
					}
				}
			}
		}

		return apply_filters('litespeed_crawler_sitemap', $this->_urls) ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.1.0
	 * @access public
	 * @return Current class instance.
	 */
	public static function get_instance()
	{
		if ( ! isset(self::$_instance) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}
}
