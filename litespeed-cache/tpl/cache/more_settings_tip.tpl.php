<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

global $pagenow;
if ( $pagenow != 'options-general.php' ) {
	return;
}
?>
	<div class="litespeed-callout notice notice-success inline">

		<h4><?php echo __( 'NOTE', 'litespeed-cache' ); ?></h4>

		<p>
			<?php echo sprintf( __( 'More settings available under %s menu', 'litespeed-cache' ), '<code>' . __( 'LiteSpeed Cache', 'litespeed-cache' ) . '</code>' ); ?>
		</p>

	</div>
