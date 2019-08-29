<?php
namespace LiteSpeed ;
defined( 'WPINC' ) || exit ;
?>

<link rel="stylesheet" href="<?php echo LSWCP_PLUGIN_URL ; ?>assets/css/litespeed.css" />

<div class="litespeed litespeed-modal">

	<?php if ( $_progress ) : ?>
	<div class="litespeed-progress">
		<div class="litespeed-progress-bar" role="progressbar" style="width: <?php echo $_progress ; ?>%" aria-valuenow="<?php echo $_progress ; ?>" aria-valuemin="0" aria-valuemax="100"></div>
	</div>
	<?php endif ; ?>

	<div class="litespeed-wrap">
