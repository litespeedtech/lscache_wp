<?php
if ( ! defined( 'WPINC' ) ) die ;
?>

	<tr>
		<th><?php echo __( 'Do Not Cache Cookies', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php
				$id = LiteSpeed_Cache_Config::O_CACHE_EXC_COOKIES ;

				$this->build_textarea( $id ) ;
			?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'To prevent %s from being cached, enter it here.', 'litespeed-cache' ), __( 'cookies', 'litespeed-cache') ) ; ?>
				<i><?php echo __('One per line.', 'litespeed-cache'); ?></i>
				<br /><font class="litespeed-warning">
					⚠️
					<?php echo __('This setting will edit the .htaccess file.', 'litespeed-cache'); ?>
				</font>
			</div>
		</td>
	</tr>
