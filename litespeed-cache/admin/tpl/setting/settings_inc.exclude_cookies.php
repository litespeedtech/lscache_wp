<?php
if ( ! defined( 'WPINC' ) ) die ;
?>

	<tr>
		<th><?php echo __( 'Do Not Cache Cookies', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php
				$id = LiteSpeed_Cache_Config::ID_NOCACHE_COOKIES;

				$file_writable = LiteSpeed_Cache_Admin_Rules::writable();

				$this->build_textarea($id, false, str_replace('|', "\n", $_options[$id]));//, !$file_writable
			?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'To prevent %s from being cached, enter it here.', 'litespeed-cache' ), __( 'cookies', 'litespeed-cache') ) ; ?>
				<i>
					<?php echo sprintf(__('Spaces should have a backslash in front of them, %s.', 'litespeed-cache'), '<code>\ </code>'); ?>
					<?php echo __('One per line.', 'litespeed-cache'); ?>
				</i>
				<br /><font class="litespeed-warning">
					⚠️
					<?php echo __('This setting will edit the .htaccess file.', 'litespeed-cache'); ?>
				</font>
			</div>
		</td>
	</tr>
