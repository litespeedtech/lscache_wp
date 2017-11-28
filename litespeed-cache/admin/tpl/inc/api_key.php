<?php
if (!defined('WPINC')) die;

$api_key = get_option( LiteSpeed_Cache_Admin_API::DB_API_KEY ) ;

?>


		<?php if ( ! $api_key ) : ?>
			<p class="litespeed-desc">
				<?php echo __( 'This will also generate an API key from LiteSpeed\'s Server.', 'litespeed-cache' ) ; ?>
			</p>
		<?php endif ; ?>

