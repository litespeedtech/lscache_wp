<?php
/**
 * LiteSpeed Cache Modal
 *
 * Renders the modal interface for LiteSpeed Cache, including a progress bar if applicable.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;
?>

<link rel="stylesheet" href="<?php echo esc_url( LSWCP_PLUGIN_URL ); ?>assets/css/litespeed.css" />

<div class="litespeed litespeed-modal<?php 
if(isset($_progress) && $_progress ) { 
    echo ' litespeed-progress';
} 
?>"<?php if($_id ) { echo ' id="' . $_id . '"'; 
}  ?>>

	<?php if ( $_progress ) : ?>
	<div class="litespeed-progress">
		<div class="litespeed-progress-bar" role="progressbar" style="width: <?php echo esc_attr( $_progress ); ?>%" aria-valuenow="<?php echo esc_attr( $_progress ); ?>" aria-valuemin="0" aria-valuemax="100"></div>
	</div>
	<?php endif; ?>
  
  <?php if ($_title ) : ?>
  <div class="iziModal-header-title">
      <h2><?php echo $_title; ?></h2>
  </div>
  <?php endif ; ?>

	<div class="litespeed-wrap">
