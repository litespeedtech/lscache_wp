<?php
// phpcs:ignoreFile
/**
 * The Third Party integration with the NextGen Gallery plugin.
 *
 * @since       1.0.5
 */
namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

// Try preload instead
// todo: need test
// add_action('load_nextgen_gallery_modules', 'NextGenGallery::detect') ;

class NextGenGallery {

	const CACHETAG_ALBUMS    = 'NGG_A.';
	const CACHETAG_GALLERIES = 'NGG_G.';
	const CACHETAG_TAGS      = 'NGG_T.';

	/**
	 * Detect is triggered at the load_nextgen_gallery_modules action.
	 *
	 * If this action is triggered, assume NextGen Gallery is used.
	 *
	 * @since   1.0.5
	 * @access  public
	 */
	public static function preload() {
		add_action('ngg_added_new_image', __CLASS__ . '::add_image');
		add_action('ngg_ajax_image_save', __CLASS__ . '::update_image');
		add_action('ngg_delete_picture', __CLASS__ . '::delete_image');
		add_action('ngg_moved_images', __CLASS__ . '::move_image', 10, 3);
		add_action('ngg_copied_images', __CLASS__ . '::copy_image', 10, 3);
		add_action('ngg_generated_image', __CLASS__ . '::gen_image');
		add_action('ngg_recovered_image', __CLASS__ . '::gen_image');

		add_action('ngg_gallery_sort', __CLASS__ . '::update_gallery');
		add_action('ngg_delete_gallery', __CLASS__ . '::update_gallery');

		add_action('ngg_update_album', __CLASS__ . '::update_album');
		add_action('ngg_delete_album', __CLASS__ . '::update_album');

		add_filter('ngg_displayed_gallery_cache_params', __CLASS__ . '::add_container');
	}

	/**
	 * When an image is added, need to purge all pages that displays its gallery.
	 *
	 * @since   1.0.5
	 * @access  public
	 * @param   string $image  The image object added.
	 */
	public static function add_image( $image ) {
		if (!$image || !method_exists($image, 'get_gallery')) {
			return;
		}
		$gallery = $image->get_gallery();
		if ($gallery && $gallery->pageid) {
			do_action('litespeed_purge', self::CACHETAG_GALLERIES . $gallery->pageid);
		}
	}

	/**
	 * When an image is updated, need to purge all pages that displays its gallery.
	 *
	 * @since 1.0.5
	 * @access  public
	 */
	public static function update_image() {
		if (isset($_REQUEST['gallery_id'])) {
			do_action('litespeed_purge', self::CACHETAG_GALLERIES . sanitize_key($_REQUEST['gallery_id']));
			return;
		}

		if (isset($_POST['task_list'])) {
			$task_list = str_replace('\\', '', $_POST['task_list']);
			$task_list = json_decode($task_list, true);

			if (!empty($task_list[0]['query']['id'])) {
				do_action('litespeed_purge', self::CACHETAG_GALLERIES . sanitize_key($task_list[0]['query']['id']));
				return;
			}
		}

		if (isset($_POST['id'])) {
			$id = (int) $_POST['id'];
		} elseif (isset($_POST['image'])) {
			$id = (int) $_POST['image'];
		} elseif (isset($_GET['pid'])) {
			$id = (int) $_GET['pid'];
		} else {
			error_log('LiteSpeed_Cache hit ngg_ajax_image_save with no post image id.');
			return;
		}
		$image = \C_Image_Mapper::get_instance()->find($id);
		if ($image) {
			do_action('litespeed_purge', self::CACHETAG_GALLERIES . $image->galleryid);
		}
	}

	/**
	 * When an image is deleted, need to purge all pages that displays its gallery.
	 *
	 * @since 1.0.5
	 * @access  public
	 */
	public static function delete_image() {
		if (isset($_GET['gid'])) {
			do_action('litespeed_purge', self::CACHETAG_GALLERIES . sanitize_key($_GET['gid']));
		}
	}

	/**
	 * When an image is moved, need to purge all old galleries and the new gallery.
	 *
	 * @since 1.0.8
	 * @access  public
	 * @param array   $images unused
	 * @param array   $old_gallery_ids Source gallery ids for the images.
	 * @param integer $new_gallery_id Destination gallery id.
	 */
	public static function move_image( $images, $old_gallery_ids, $new_gallery_id ) {
		foreach ($old_gallery_ids as $gid) {
			do_action('litespeed_purge', self::CACHETAG_GALLERIES . $gid);
		}
		do_action('litespeed_purge', self::CACHETAG_GALLERIES . $new_gallery_id);
	}

	/**
	 * When an image is copied, need to purge the destination gallery.
	 *
	 * @param array   $image_pid_map unused
	 * @param array   $old_gallery_ids unused
	 * @param integer $new_gallery_id Destination gallery id.
	 */
	public static function copy_image( $image_pid_map, $old_gallery_ids, $new_gallery_id ) {
		do_action('litespeed_purge', self::CACHETAG_GALLERIES . $new_gallery_id);
	}

	/**
	 * When an image is re-generated, need to purge the gallery it belongs to.
	 * Also applies to recovered images.
	 *
	 * @param Image $image The re-generated image.
	 */
	public static function gen_image( $image ) {
		do_action('litespeed_purge', self::CACHETAG_GALLERIES . $image->galleryid);
	}

	/**
	 * When a gallery is updated, need to purge all pages that display the gallery.
	 *
	 * @since 1.0.5
	 * @access  public
	 * @param   integer $gid    The gallery id of the gallery updated.
	 */
	public static function update_gallery( $gid ) {
		// New version input will be an object with gid value
		if (is_object($gid) && !empty($gid->gid)) {
			$gid = $gid->gid;
		}

		do_action('litespeed_purge', self::CACHETAG_GALLERIES . $gid);
	}

	/**
	 * When an album is updated, need to purge all pages that display the album.
	 *
	 * @since 1.0.5
	 * @access public
	 * @param   integer $aid    The album id of the album updated.
	 */
	public static function update_album( $aid ) {
		do_action('litespeed_purge', self::CACHETAG_ALBUMS . $aid);
	}

	/**
	 * When rendering a page, if the page has a gallery, album or tag cloud,
	 * it needs to be tagged appropriately.
	 *
	 * @since 1.0.5
	 * @access public
	 * @param object $render_parms Parameters used to render the associated part of the page.
	 * @return mixed Null if passed in null, $render_parms otherwise.
	 */
	public static function add_container( $render_parms ) {
		// Check if null. If it is null, can't continue.
		if (is_null($render_parms)) {
			return null;
		}
		$src           = $render_parms[0]->source;
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
			do_action('litespeed_tag_add', $tag . $id);
		}

		return $render_parms;
	}
}
