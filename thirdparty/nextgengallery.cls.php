<?php
/**
 * The Third Party integration with the NextGen Gallery plugin.
 *
 * @since 1.0.5
 * @package LiteSpeed
 * @subpackage LiteSpeed_Cache\Thirdparty
 */

namespace LiteSpeed\Thirdparty;

defined('WPINC') || exit();

/**
 * Provides LiteSpeed Cache compatibility for NextGen Gallery.
 */
class NextGenGallery {

	const CACHETAG_ALBUMS    = 'NGG_A.';
	const CACHETAG_GALLERIES = 'NGG_G.';
	const CACHETAG_TAGS      = 'NGG_T.';

	/**
	 * Hook NextGen Gallery events for purging cache.
	 *
	 * @since 1.0.5
	 * @return void
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
	 * Purge cache when an image is added.
	 *
	 * @since 1.0.5
	 * @param object $image The image object added.
	 * @return void
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
	 * Purge cache when an image is updated.
	 *
	 * @since 1.0.5
	 * @return void
	 */
	public static function update_image() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['gallery_id'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			do_action( 'litespeed_purge', self::CACHETAG_GALLERIES . sanitize_key( wp_unslash( $_REQUEST['gallery_id'] ) ) );
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['task_list'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing
			$task_list = str_replace( '\\', '', wp_unslash( $_POST['task_list'] ) );
			$task_list = json_decode( $task_list, true );

			if ( ! empty( $task_list[0]['query']['id'] ) ) {
				do_action( 'litespeed_purge', self::CACHETAG_GALLERIES . sanitize_key( $task_list[0]['query']['id'] ) );
				return;
			}
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['id'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$id = (int) $_POST['id'];
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		} elseif ( isset( $_POST['image'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$id = (int) $_POST['image'];
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( isset( $_GET['pid'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$id = (int) $_GET['pid'];
		} else {
			error_log( 'LiteSpeed_Cache hit ngg_ajax_image_save with no post image id.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}
		$image = \C_Image_Mapper::get_instance()->find($id);
		if ($image) {
			do_action('litespeed_purge', self::CACHETAG_GALLERIES . $image->galleryid);
		}
	}

	/**
	 * Purge cache when an image is deleted.
	 *
	 * @since 1.0.5
	 * @return void
	 */
	public static function delete_image() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['gid'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			do_action( 'litespeed_purge', self::CACHETAG_GALLERIES . sanitize_key( wp_unslash( $_GET['gid'] ) ) );
		}
	}

	/**
	 * Purge cache when an image is moved.
	 *
	 * @since 1.0.8
	 * @param array $images Unused.
	 * @param array $old_gallery_ids Source gallery IDs.
	 * @param int   $new_gallery_id Destination gallery ID.
	 * @return void
	 */
	public static function move_image( $images, $old_gallery_ids, $new_gallery_id ) {
		foreach ($old_gallery_ids as $gid) {
			do_action('litespeed_purge', self::CACHETAG_GALLERIES . $gid);
		}
		do_action('litespeed_purge', self::CACHETAG_GALLERIES . $new_gallery_id);
	}

	/**
	 * Purge cache when an image is copied.
	 *
	 * @since 1.0.8
	 * @param array $image_pid_map Unused.
	 * @param array $old_gallery_ids Unused.
	 * @param int   $new_gallery_id Destination gallery ID.
	 * @return void
	 */
	public static function copy_image( $image_pid_map, $old_gallery_ids, $new_gallery_id ) {
		do_action('litespeed_purge', self::CACHETAG_GALLERIES . $new_gallery_id);
	}

	/**
	 * Purge cache when an image is regenerated or recovered.
	 *
	 * @since 1.0.8
	 * @param object $image The regenerated image object.
	 * @return void
	 */
	public static function gen_image( $image ) {
		do_action('litespeed_purge', self::CACHETAG_GALLERIES . $image->galleryid);
	}

	/**
	 * Purge cache when a gallery is updated.
	 *
	 * @since 1.0.5
	 * @param int|object $gid Gallery ID or object with gid.
	 * @return void
	 */
	public static function update_gallery( $gid ) {
		if (is_object($gid) && !empty($gid->gid)) {
			$gid = $gid->gid;
		}
		do_action('litespeed_purge', self::CACHETAG_GALLERIES . $gid);
	}

	/**
	 * Purge cache when an album is updated.
	 *
	 * @since 1.0.5
	 * @param int $aid Album ID.
	 * @return void
	 */
	public static function update_album( $aid ) {
		do_action('litespeed_purge', self::CACHETAG_ALBUMS . $aid);
	}

	/**
	 * Tag gallery/album/tag content during rendering.
	 *
	 * @since 1.0.5
	 * @param object $render_parms Render parameters.
	 * @return mixed Null if $render_parms is null, otherwise same input.
	 */
	public static function add_container( $render_parms ) {
		if (is_null($render_parms)) {
			return null;
		}
		$src           = $render_parms[0]->source;
		$container_ids = $render_parms[0]->container_ids;

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
