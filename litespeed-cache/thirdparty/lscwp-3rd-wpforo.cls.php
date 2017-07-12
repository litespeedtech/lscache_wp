<?php

/**
 * The Third Party integration with the wpForo plugin.
 *
 * @since        1.0.15
 * @package       LiteSpeed_Cache
 * @subpackage    LiteSpeed_Cache/thirdparty
 * @author        LiteSpeed Technologies <info@litespeedtech.com>
 */
if ( ! defined('ABSPATH') ) {
	die() ;
}

LiteSpeed_Cache_API::register('LiteSpeed_Cache_ThirdParty_WpForo') ;
if ( defined('WPFORO_VERSION') ) {
	add_action('wpforo_actions', 'LiteSpeed_Cache_ThirdParty_WpForo::purge_tag') ;
}

class LiteSpeed_Cache_ThirdParty_WpForo
{
	const CACHETAG_COMMON = 'WF' ;
	const CACHETAG_FRONTPAGE = 'WF_F' ;
	const CACHETAG_FORUM = 'WF_F.' ;
	const CACHETAG_TOPIC = 'WF_T.' ;

	/**
	 * Detects if wpForo is installed.
	 *
	 * @since 1.0.15
	 * @access public
	 */
	public static function detect()
	{
		if ( defined('WPFORO_VERSION') ) {
			LiteSpeed_Cache_API::hook_tag('LiteSpeed_Cache_ThirdParty_WpForo::cache_tags') ;
		}
	}

	/**
	 * Purge tags based on hooks
	 */
	public static function purge_tag()
	{
		if( ! empty($_POST) ) {
			add_action('wpforo_after_add_topic', 'LiteSpeed_Cache_ThirdParty_WpForo::purge_tag_topic_add') ;
			add_action('wpforo_start_edit_topic', 'LiteSpeed_Cache_ThirdParty_WpForo::purge_tag_topic_update') ;
			add_action('wpforo_after_delete_topic', 'LiteSpeed_Cache_ThirdParty_WpForo::purge_tag_topic_update') ;

			add_action('wpforo_after_add_post', 'LiteSpeed_Cache_ThirdParty_WpForo::purge_tag_post_add', 10, 2) ;
			add_action('wpforo_after_edit_post', 'LiteSpeed_Cache_ThirdParty_WpForo::purge_tag_topic_update') ;
			add_action('wpforo_after_delete_post', 'LiteSpeed_Cache_ThirdParty_WpForo::purge_tag_topic_update') ;
		}
		// for those which doesn't have hooks
		add_action('shutdown', 'LiteSpeed_Cache_ThirdParty_WpForo::wpforo_hook_when_shutdown', -1) ;// use -1 to make evoked before send tag header
	}

	/**
	 * Shutdown purge
	 */
	public static function wpforo_hook_when_shutdown() {
		global $wpforo ;
		// admin forum actions
		if ( wpforo_is_admin() && isset($_GET['page']) && $_GET['page'] == 'wpforo-forums' ) {
			if( (isset($_POST['wpforo_submit']) && isset($_REQUEST['forum']) && isset($_GET['action']))
					|| (isset($_POST['wpforo_delete']) && $_GET['action'] == 'del')
					|| (isset($_POST['forums_hierarchy_submit'])) ) {
				LiteSpeed_Cache_API::purge(self::CACHETAG_COMMON) ;
			}
		}

		##Moderation
		if( wpforo_is_admin() && isset($_GET['page']) && $_GET['page'] == 'wpforo-moderations' ) {
			$pids = array() ;
			if( !empty($_GET['id']) && ($pid = wpforo_bigintval($_GET['id'])) ){
			    $pids = (array) $pid ;
			}
			elseif( !empty($_GET['ids']) && ($ids = trim($_GET['ids'])) ){
			    $ids = explode(',', urldecode($ids)) ;
			    $pids = array_map('wpforo_bigintval', array_filter($ids)) ;
			}
			$pids = array_diff($pids, (array) $wpforo->current_userid) ;
			if( ! empty($pids) ) {
				foreach ($pids as $pid){
					$post = $wpforo->post->get_post($pid) ;
					$topicid = $post['topicid'] ;
					self::purge_tag_topic($topicid) ;
				}
			}

		}
	}

	/**
	 * Purge current topic, and all forums/parent forums/forum homepage
	 */
	public static function purge_tag_topic($topicid)
	{
		LiteSpeed_Cache_API::purge(self::CACHETAG_TOPIC . $topicid) ;
		LiteSpeed_Cache_API::purge(self::CACHETAG_FORUM) ;
		LiteSpeed_Cache_API::purge(self::CACHETAG_FRONTPAGE) ;
	}

	/**
	 * Purge topic when a topic is added
	 */
	public static function purge_tag_topic_add($args)
	{
		// LiteSpeed_Cache_API::purge(self::CACHETAG_FORUM . $args['forumid']) ;
		LiteSpeed_Cache_API::purge(self::CACHETAG_FORUM) ;
		LiteSpeed_Cache_API::purge(self::CACHETAG_FRONTPAGE) ;
	}

	/**
	 * Purge topic when a topic is modified
	 */
	public static function purge_tag_topic_update($args)
	{
		if( ! empty($args['topicid']) ) {
			self::purge_tag_topic($args['topicid']) ;
		}
	}

	/**
	 * Purge topic when a post is added
	 */
	public static function purge_tag_post_add($post, $topic)
	{
		if( ! empty($topic['topicid']) ) {
			self::purge_tag_topic($topic['topicid']) ;
		}
	}

	/**
	 * Send cache tags for current page
	 */
	public static function cache_tags()
	{
		global $wpforo ;
		if( ! empty($wpforo->current_object['template']) ) {
			if( $wpforo->current_object['template'] == 'forum' ) {
				LiteSpeed_Cache_API::tag_add(self::CACHETAG_FRONTPAGE) ;
				LiteSpeed_Cache_API::tag_add(self::CACHETAG_COMMON) ;
			}
			if( $wpforo->current_object['template'] == 'topic' ) {
				LiteSpeed_Cache_API::tag_add(self::CACHETAG_FORUM . $wpforo->current_object['forum']['forumid']) ;
				LiteSpeed_Cache_API::tag_add(self::CACHETAG_FORUM) ;
				LiteSpeed_Cache_API::tag_add(self::CACHETAG_COMMON) ;
			}
			if( $wpforo->current_object['template'] == 'post' ) {
				LiteSpeed_Cache_API::tag_add(self::CACHETAG_TOPIC . $wpforo->current_object['topic']['topicid']) ;
				LiteSpeed_Cache_API::tag_add(self::CACHETAG_COMMON) ;
			}
		}
	}

}
