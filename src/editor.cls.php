<?php
/**
 * The Editor class
 *
 * @since
 * @package     LiteSpeed
 * @subpackage  LiteSpeed/inc
 * @author      LiteSpeed Technologies <info@litespeedtech.com>
 */
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

class Editor extends Base {

  /**
  * Add a post meta box into the sidebar of post editor.
  *
  * @since    4.3
  */
  function add_meta_boxes() {
    Debug2::debug( '[Editor] add_meta_boxes ');
    add_meta_box(
      'litespeed_metabox',                      // Unique ID
      'Litespeed Cache',                        // Title
      array( $this, 'meta_box_html' ),          // Callback function
      null,                                     // Admin page (or post type)
      'side',                                   // Context
      'high'                                    // Priority
    );
  }

  /**
  * Display the post meta box.
  *
  * @since    4.3
  */
  function meta_box_html( $post ) {
    if ( false ) {
      $post_meta = get_post_meta( $post->ID, 'litespeed_metabox_vpi', true );
      $desktop_organized_post_meta = '';
      $mobile_organized_post_meta = '';
      $desktop_warning_msg = 'Desktop VPI is empty';
      $mobile_warning_msg = 'Mobile VPI is empty';
      if ( isset( $post_meta['desktop'] ) && is_array( $post_meta['desktop'] ) ) {
        $desktop_organized_post_meta = esc_attr( implode("\n", $post_meta['desktop'] ) );
        $desktop_warning_msg = '';
      }
      if ( isset( $post_meta['mobile'] ) && is_array( $post_meta['mobile'] ) ) {
        $mobile_organized_post_meta = esc_attr( implode("\n", $post_meta['mobile'] ) );
        $mobile_warning_msg = '';
      }
      wp_nonce_field( basename( __FILE__ ), 'litespeed_metabox_nonce' );

      require_once LSCWP_DIR . 'tpl/editor/sidebar-metabox.tpl.php';
    }
  }

  /**
  * Save the meta box’s metadata.
  *
  * @since    4.3
  */
  function save_meta( $post_id, $post ) {
    /* Verify the nonce before proceeding. */
    if ( !isset( $_POST['litespeed_metabox_nonce'] ) || !wp_verify_nonce( $_POST['litespeed_metabox_nonce'], basename( __FILE__ ) ) )
      return $post_id;

    /* Get the post type object. */
    $post_type = get_post_type_object( $post->post_type );

    /* Check if the current user has permission to edit the post. */
    if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
      return $post_id;

    if ( false ) {
      Debug2::debug( '[Editor] save_meta' );

      /* Get the meta key. */
      $meta_key = 'litespeed_metabox_vpi';

      /* Get the meta value of the custom field key. */
      $meta_value = get_post_meta( $post_id, $meta_key, true );

      /* Get the posted data and sanitize it for use as an HTML class. */
      $new_desktop_meta_value = isset( $_POST['vpi_desktop'] ) ? $_POST['vpi_desktop'] : '';
      $new_mobile_meta_value = isset( $_POST['vpi_mobile'] ) ? $_POST['vpi_mobile'] : '';

      // if there is no VPI record and the user has not given any thing and updated,
      //meta_value is '', and both new_desktop_meta_value and new_desktop_meta_value is ''
      if ( ( $meta_value == $new_desktop_meta_value ) && ( $meta_value == $new_desktop_meta_value ) ) {
        // don't bother
        Debug2::debug( '[Editor] no row added and changed for vpi' );
        return;
      }

      /* Clean up the meta data from posted by the user, check if empty, if so then make it into an empty array. */
      /* Desktop */
      $new_desktop_meta_value = trim( $new_desktop_meta_value, ' ' );
      $new_desktop_meta_value = ( $new_desktop_meta_value == '' ) ? '' : explode( "\n", $new_desktop_meta_value );
      $new_desktop_meta_value = array_map( 'trim', $new_desktop_meta_value);
      /* Mobile */
      $new_mobile_meta_value = trim( $new_mobile_meta_value, ' ' );
      $new_mobile_meta_value = ( $new_mobile_meta_value == '' ) ? '' : explode( "\n", $new_mobile_meta_value );
      $new_mobile_meta_value = array_map( 'trim', $new_mobile_meta_value);

      if ( $meta_value == '' ) {
        $meta_value = array();
      }

      /* If the new meta value does not match the old value, update it. */
      if ( ( $new_desktop_meta_value != $meta_value['desktop']  ) || ( $new_mobile_meta_value != $meta_value['mobile'] ) ) {
        $new_meta_value = $meta_value;
        if ( $new_desktop_meta_value != $meta_value['desktop'] ) {
          $new_meta_value['desktop'] = $new_desktop_meta_value;
          Debug2::debug( '[Editor] changing desktop vpi' );
          $new_meta_value['user_in_control_desktop'] = 'true';
          $new_meta_value['desktop_timestamp'] = time();
        }
        if ( $new_mobile_meta_value != $meta_value['mobile'] ) {
          $new_meta_value['mobile'] = $new_mobile_meta_value;
          Debug2::debug( '[Editor] changing mobile vpi' );
          $new_meta_value['user_in_control_mobile'] = 'true';
          $new_meta_value['mobile_timestamp'] = time();
        }
        update_post_meta( $post_id, $meta_key, $new_meta_value );
        // if new meta is diff from old, then save
        //   check if
      }
    }
  }

  /**
  * Custom post class function, gather the classes
  *
  * @since    4.3
  */
  function post_class( $classes ) {
    /* Get the current post ID. */
    $post_id = get_the_ID();

    /* If we have a post ID, proceed. */
    if ( !empty( $post_id ) ) {

    /* Get the custom post class. */
    $post_class = get_post_meta( $post_id, 'litespeed_metabox_vpi', true );

    /* If a post class was input, sanitize it and add it to the post class array. */
    if ( !empty( $post_class ) )
      $classes[] = sanitize_html_class( $post_class );
    }

    return $classes;
  }
}