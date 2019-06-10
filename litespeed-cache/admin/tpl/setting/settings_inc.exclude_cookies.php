<?php defined( 'WPINC' ) || exit ; ?>

	<tr>
		<th><?php echo __( 'Do Not Cache Cookies', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php
				$id = LiteSpeed_Cache_Config::O_CACHE_EXC_COOKIES ;
				$this->build_textarea( $id ) ;
			?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'To prevent %s from being cached, enter it here.', 'litespeed-cache' ), __( 'cookies', 'litespeed-cache') ) ; ?>
				<?php LiteSpeed_Cache_Doc::one_per_line() ; ?>
				<?php $this->_validate_syntax( $id ) ; ?>
				<br /><?php LiteSpeed_Cache_Doc::notice_htaccess() ; ?>
			</div>
		</td>
	</tr>
