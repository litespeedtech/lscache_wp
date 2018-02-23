<?php
if (!defined('WPINC')) die;

?>

	<tr>
		<th><?php echo __( 'Do Not Cache User Agents', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php
				$file_writable = LiteSpeed_Cache_Admin_Rules::writable();

				$this->build_input(LiteSpeed_Cache_Config::ID_NOCACHE_USERAGENTS, 'litespeed-input-long');//, !$file_writable
			?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'To prevent %s from being cached, enter it here.', 'litespeed-cache' ), __( 'user agents', 'litespeed-cache') ) ; ?>
				<i>
					<?php echo sprintf( __( 'SYNTAX: Separate each user agent with a bar, %s.', 'litespeed-cache' ), '<code>|</code>' ) ; ?>
					<?php echo sprintf( __( 'Spaces should have a backslash in front of them, %s.', 'litespeed-cache' ), '<code>\</code>' ) ; ?>
				</i>
				<br /><font class="litespeed-warning">
					<?php echo __('NOTE', 'litespeed-cache'); ?>:
					<?php echo __('This setting will edit the .htaccess file.', 'litespeed-cache'); ?>
				</font>
			</div>
		</td>
	</tr>
