<?php defined( 'WPINC' ) || exit ; ?>

	<tr>
		<th>
			<?php $id = LiteSpeed_Config::O_CACHE_EXC_USERAGENTS ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
		<?php $this->build_textarea( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'To prevent %s from being cached, enter it here.', 'litespeed-cache' ), __( 'user agents', 'litespeed-cache') ) ; ?>
				<?php LiteSpeed_Doc::one_per_line() ; ?>
				<?php $this->_validate_syntax( $id ) ; ?>
				<br /><?php LiteSpeed_Doc::notice_htaccess() ; ?>
			</div>
		</td>
	</tr>
