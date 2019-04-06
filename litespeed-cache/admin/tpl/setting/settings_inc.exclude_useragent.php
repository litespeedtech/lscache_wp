<?php
if (!defined('WPINC')) die;

?>

	<tr>
		<th><?php echo __( 'Do Not Cache User Agents', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php
				$file_writable = LiteSpeed_Cache_Admin_Rules::writable();

				$this->build_textarea( LiteSpeed_Cache_Config::O_CACHE_EXC_USERAGENTS ) ;
			?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'To prevent %s from being cached, enter it here.', 'litespeed-cache' ), __( 'user agents', 'litespeed-cache') ) ; ?>
				<i><?php echo __('One per line.', 'litespeed-cache'); ?></i>
				<br /><font class="litespeed-warning">
					⚠️
					<?php echo __('This setting will edit the .htaccess file.', 'litespeed-cache'); ?>
				</font>
			</div>
		</td>
	</tr>
