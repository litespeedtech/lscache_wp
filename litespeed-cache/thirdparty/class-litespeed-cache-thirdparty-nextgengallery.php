<?php

/**
 * The Third Party integration with the NextGen Gallery plugin.
 *
 * @since		1.0.5
 * @package		LiteSpeed_Cache
 * @subpackage	LiteSpeed_Cache/thirdparty
 * @author		LiteSpeed Technologies <info@litespeedtech.com>
 */
class LiteSpeed_Cache_ThirdParty_NextGenGallery
{

	const CACHETAG_ALBUMS = 'NGG_A.';
	const CACHETAG_GALLERIES = 'NGG_G.';
	const CACHETAG_TAGS = 'NGG_T.';

	/**
	 * Detect is triggered at the load_nextgen_gallery_modules action.
	 *
	 * If this action is triggered, assume NextGen Gallery is used.
	 *
	 * @since	1.0.5
	 * @access	public
	 */
	public static function detect()
	{
		add_action('ngg_added_new_image', 'LiteSpeed_Cache_ThirdParty_NextGenGallery::add_image');
		add_action('ngg_ajax_image_save', 'LiteSpeed_Cache_ThirdParty_NextGenGallery::update_image');
		add_action('ngg_delete_picture', 'LiteSpeed_Cache_ThirdParty_NextGenGallery::delete_image');

		add_action('ngg_gallery_sort', 'LiteSpeed_Cache_ThirdParty_NextGenGallery::update_gallery');
		add_action('ngg_delete_gallery', 'LiteSpeed_Cache_ThirdParty_NextGenGallery::update_gallery');

		add_action('ngg_update_album', 'LiteSpeed_Cache_ThirdParty_NextGenGallery::update_album');
		add_action('ngg_delete_album', 'LiteSpeed_Cache_ThirdParty_NextGenGallery::update_album');

		add_filter('ngg_displayed_gallery_cache_params', 'LiteSpeed_Cache_ThirdParty_NextGenGallery::add_container');
	}

	/**
	 * When an image is added, need to purge all pages that displays its gallery.
	 *
	 * @since	1.0.5
	 * @access	public
	 * @param	string	$image	The image object added.
	 */
	public static function add_image($image)
	{
		if (!$image) {
			return;
		}
		$gallery = $image->get_gallery();
		if (($gallery) && ($gallery->pageid)) {
			LiteSpeed_Cache_Tags::add_purge_tag(self::CACHETAG_GALLERIES . $gallery->pageid);
		}
	}

	/**
	 * When an image is updated, need to purge all pages that displays its gallery.
	 *
	 * @since 1.0.5
	 * @access	public
	 */
	public static function update_image()
	{
		if ($_POST['id']) {
			$id = $_POST['id'];
		}
		else if ($_POST['image']) {
			$id = $_POST['image'];
		}
		else {
			error_log('LiteSpeed_Cache hit ngg_ajax_image_save with no post image id.');
			return;
		}
		$image = C_Image_Mapper::get_instance()->find($id);
		if ($image) {
			LiteSpeed_Cache_Tags::add_purge_tag(self::CACHETAG_GALLERIES . $image->galleryid);
		}
	}

	/**
	 * When an image is deleted, need to purge all pages that displays its gallery.
	 *
	 * @since 1.0.5
	 * @access	public
	 */
	public static function delete_image()
	{
		if ($_GET['gid']) {
			LiteSpeed_Cache_Tags::add_purge_tag(self::CACHETAG_GALLERIES . $_GET['gid']);
		}
	}

	/**
	 * When a gallery is updated, need to purge all pages that display the gallery.
	 *
	 * @since 1.0.5
	 * @access  public
	 * @param	integer		$gid	The gallery id of the gallery updated.
	 */
	public static function update_gallery($gid)
	{
		LiteSpeed_Cache_Tags::add_purge_tag(self::CACHETAG_GALLERIES . $gid);
	}

	/**
	 * When an album is updated, need to purge all pages that display the album.
	 *
	 * @since 1.0.5
	 * @access public
	 * @param	integer		$aid	The album id of the album updated.
	 */
	public static function update_album($aid)
	{
		LiteSpeed_Cache_Tags::add_purge_tag(self::CACHETAG_ALBUMS . $aid);
	}

	/**
	 * When rendering a page, if the page has a gallery, album or tag cloud,
	 * it needs to be tagged appropriately.
	 *
	 * @since 1.0.5
	 * @access public
	 * @param object $render_parms Parameters used to render the associated part of the page.
	 */
	public static function add_container($render_parms)
	{
		// Check if null. If it is null, can't continue.
		if (is_null($render_parms)) {
			return null;
		}
		$src = $render_parms[0]->source;
		$container_ids = $render_parms[0]->container_ids;
		// Can switch on first char if we end up with more sources.
		switch ($src) {
			case 'albums':
				$tag = self::CACHETAG_ALBUMS;
				break;
			case 'galleries':
				$tag = self::CACHETAG_GALLERIES;
				break;
			case 'tags':
				$tag = self::CACHETAG_TAGS;
				break;
			default:
				return $render_parms;
		}

		foreach ($container_ids as $id) {
			LiteSpeed_Cache_Tags::add_cache_tag($tag . $id);
		}

		return $render_parms;
	}
}

add_action('load_nextgen_gallery_modules', 'LiteSpeed_Cache_ThirdParty_NextGenGallery::detect');

