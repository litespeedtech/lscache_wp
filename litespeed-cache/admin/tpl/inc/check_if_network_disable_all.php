<?php
if ( ! defined( 'WPINC' ) ) die ;

if ( ! $this->get_disable_all() ) {
	return ;
}
?>
		<div class="litespeed-callout-danger">
			<h4><?php echo __( 'WARNING', 'litespeed-cache' ) ; ?></h4>
			<p>
				<?php echo __('The network admin selected use primary site configs for all subsites.', 'litespeed-cache') ; ?>
				<?php echo __('The following options are selected, but are not editable in this settings page.', 'litespeed-cache') ; ?>
			</p>
		</div>
