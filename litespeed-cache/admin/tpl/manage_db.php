<?php
if ( ! defined( 'WPINC' ) ) die ;


?>

<h3 class="litespeed-title"><?php echo __('Database Optimizer', 'litespeed-cache'); ?></h3>

<p>
	<?php echo __('Post Revisions', 'litespeed-cache'); ?>
	<?php
		$id = 'revision' ;
		echo LiteSpeed_Cache_Admin_Optimize::db_count( $id ) ;
		$url = LiteSpeed_Cache_Admin_Optimize::generate_url( $id ) ;
	?>
	<a href="<?php echo $url; ?>" class="litespeed-btn litespeed-btn-success">
		<?php echo __('Clean all post revisions', 'litespeed-cache'); ?>
	</a>
</p>




