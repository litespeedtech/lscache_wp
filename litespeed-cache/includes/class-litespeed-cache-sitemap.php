<?php
/**
 * The Crawler Class
 *
 *
 * @since      1.0.15
 * @package    LiteSpeed_Crawler_Crawler
 * @subpackage LiteSpeed_Cache/lib
 * @author     LiteSpeed Technologies <info@litespeedtech.com>
 */

class LiteSpeed_Cache_Crawler_Sitemap extends LiteSpeed{
	protected static $_instance;

	private $folder;
	private $filename;
	private $site_url;// Used to simplify urls
	private $urls;

	/**
	 * Instantiate the class
	 */
	function __construct(){
        $this->folder = LSWCP_DIR . 'var/';
        $this->urls = array();
		if ( is_multisite() ) {
			$blog_id = get_current_blog_id();
			$this->filename = $this->folder . 'crawlmap-' . $blog_id . '.log';
			$this->site_url = get_site_url($blog_id);
		}
		else{
			$this->filename = $this->folder . 'crawlmap.log';
			$this->site_url = get_option('siteurl');
		}
	}

	/**
	 * Adds an item to sitemap
	 *
	 * @param string $loc URL of the page.
	 * @param bool $no_cache
	 */
	private function addItem($loc, $no_cache = NULL) {
		if($no_cache) $loc .= "\t$no_cache";
		$this->urls[] = $loc;
	}

	/**
	 * Save sitemap
	 *
	 * @since 1.0.15
	 */
	private function save(){
		if(!file_exists($this->folder)){
			if(!@mkdir($this->folder)){
				LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
					LiteSpeed_Cache_Admin_Display::NOTICE_RED,
					sprintf(__('File can not be written to %s', 'litespeed-cache'), $this->filename));
				return false;
			}
		}
		return @file_put_contents($this->filename, implode("\n", $this->urls));
	}

	/**
	 * Run script to generate Sitemap file in plugin folder/var
	 *
	 * @since 1.0.15
	 * @access public
	 */
	public function generateData($options, $blacklist_urls = array() ){	
		global $wpdb;

		$id = LiteSpeed_Cache_Config::CRWL_ORDER_LINKS;
		$optionOrderBy = $options[$id];

		$id = LiteSpeed_Cache_Config::CRWL_PAGES;
		$show_pages = $options[$id];

		$id = LiteSpeed_Cache_Config::CRWL_POSTS;
		$show_posts = $options[$id];

		$id = LiteSpeed_Cache_Config::CRWL_CATS;
		$show_cats = $options[$id];

		$id = LiteSpeed_Cache_Config::CRWL_TAGS;
		$show_tags = $options[$id];

		switch ($optionOrderBy) {
			case 'date_asc':
				$orderBy = " ORDER BY post_date ASC";
				break;
			case 'alpha_desc':
				$orderBy = " ORDER BY post_title DESC";
				break;
			case 'alpha_asc':
				$orderBy = " ORDER BY post_title ASC";
				break;
			case 'date_desc':
			default:
				$orderBy = " ORDER BY post_date DESC";
				break;
		}

		$post_type_array = array();
		if(isset($show_pages) && $show_pages == 1){
			$post_type_array[] = 'page';
		}
		if(isset($show_posts) && $show_posts == 1){
			$post_type_array[] = 'post';	
		}

		$id = LiteSpeed_Cache_Config::CRWL_CPT;
		if(isset($options[$id])){
			$excludeCptArr = explode(',', $options[$id]);
			$excludeCptArr = array_map('trim', $excludeCptArr);
			$cptArr = get_post_types();
			$cptArr = array_diff($cptArr, array('post', 'page'));
			$cptArr = array_diff($cptArr, $excludeCptArr);
			$post_type_array = array_merge($post_type_array, $cptArr);
		}

		if(!empty($post_type_array)){
			$post_type = implode("','", $post_type_array);

			$query = "SELECT ID, post_date FROM ".$wpdb->prefix."posts where post_type IN ('".$post_type."') AND post_status='publish' ".$orderBy;
			$results = $wpdb->get_results($query);

			foreach($results as $result){
				$slug = str_replace($this->site_url, '', get_permalink($result->ID));
				$reason = in_array($slug, $blacklist_urls) ? 'no-cache' : NULL;
				$this->addItem($slug, $reason);
			}
		}

		//Generate Categories Link if option checked
		if(isset($show_cats) && $show_cats == 1){
			$cats = get_terms("category", array("hide_empty"=>true, "hierarchical"=>false));
			if($cats && is_array($cats) && count($cats) > 0) {
				foreach($cats as $cat) {
					$slug = str_replace($this->site_url, '', get_category_link($cat->term_id));
					$reason = in_array($slug, $blacklist_urls) ? 'no-cache' : NULL;
					$this->addItem($slug, $reason);
				}
			}
		}

		//Generate tags Link if option checked
		if(isset($show_tags) && $show_tags == 1){
			$tags = get_terms("post_tag", array("hide_empty"=>true, "hierarchical"=>false));
			if($tags && is_array($tags) && count($tags) > 0) {
				foreach($tags as $tag) {
					$slug = str_replace($this->site_url, '', get_tag_link($tag->term_id));
					$reason = in_array($slug, $blacklist_urls) ? 'no-cache' : NULL;
					$this->addItem($slug, $reason);
				}
			}
		}

		if($this->save()){
			$msg = sprintf(__('File Successfully created here %s', 'litespeed-cache'), $this->filename);
			LiteSpeed_Cache_Admin_Display::get_instance()->add_notice(
				LiteSpeed_Cache_Admin_Display::NOTICE_GREEN, $msg);
		}

	}

	/**
	 * Load urls data from sitemap
	 *
	 * @since 1.0.15
	 * @access public
	 */
	public function loadData(){
        return @file($this->filename);
	}

}
