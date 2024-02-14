<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;
?>

    <h4 for="litespeed_metabox">VPI</h4>
    <p>
      <label for="vpi_desktop">Desktop Viewport Images</label>
      <?php if ( isset( $post_meta['desktop_timestamp'] ) ) : ?>
        <label id="vpi_desktop_timestamp" style="color:orange;font-size:8px;">Timestamp <?php echo date( 'm/d/Y', $post_meta['desktop_timestamp'] ); ?></label>
      <?php endif; ?>
      <div id="vpi_desktop_edit_box">
        <textarea id="vpi_desktop" name="vpi_desktop" rows="3" cols="30"><?php echo $desktop_organized_post_meta; ?></textarea>
        <p style="color:red;text-align:center;font-size:10px;"><?php echo $desktop_warning_msg; ?></p>
      </div>
    </p>
    <p>
      <label for="vpi_mobile">Mobile Viewport Images</label>
      <?php if ( isset( $post_meta['mobile_timestamp'] ) ) : ?>
        <label id="vpi_mobile_timestamp" style="color:orange;font-size:8px;">Timestamp <?php echo date( 'm/d/Y', $post_meta['mobile_timestamp'] ); ?></label>
      <?php endif; ?>
      <div id="vpi_mobile_edit_box">
        <textarea id="vpi_mobile" name="vpi_mobile" rows="3" cols="30"><?php echo $mobile_organized_post_meta; ?></textarea>
        <p style="color:red;text-align:center;font-size:10px;"><?php echo $mobile_warning_msg; ?></p>
      </div>
    </p>