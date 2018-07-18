<?php
if ( ! defined( 'WPINC' ) ) {
	die ;
}

?>

<h3 class="litespeed-title">
	<?php echo __( 'Image Information', 'litespeed-cache' ) ; ?>
</h3>

<div class="litespeed-flex-container">
	<div class="litespeed-icon-vertical-middle">
		<?php echo LiteSpeed_Cache_GUI::pie( $finished_percentage, 100, true ) ; ?>
	</div>
	<div>
		<p>
			<?php echo __( 'Images total', 'litespeed-cache') ; ?>:

			<code><?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'total_img' ] ) ; ?></code>

			<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:image-optimization:image-groups" target="_blank" class="litespeed-desc litespeed-left20" title="<?php echo __( 'What is a group?', 'litespeed-cache') ; ?>">?</a>
		</p>
		<p>
			<?php if ( ! empty( $img_count[ 'total_not_requested' ] ) ) : ?>
				<?php echo __('Images not yet requested', 'litespeed-cache') ; ?>:
				<code><?php echo LiteSpeed_Cache_Admin_Display::print_plural( $img_count[ 'total_not_requested' ] ) ; ?></code>
			<?php else : ?>
				<font class="litespeed-congratulate"><?php echo __('Congratulations, all done!', 'litespeed-cache') ; ?></font>
			<?php endif ; ?>
		</p>
	</div>
</div>