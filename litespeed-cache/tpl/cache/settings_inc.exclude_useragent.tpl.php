<?php
namespace LiteSpeed ;
defined( 'WPINC' ) || exit ;
?>

	<tr>
		<th>
			<?php $id = Base::O_CACHE_EXC_USERAGENTS ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
		<?php $this->build_textarea( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'To prevent %s from being cached, enter them here.', 'litespeed-cache' ), __( 'user agents', 'litespeed-cache') ) ; ?>
				<?php Doc::one_per_line() ; ?>
				<?php $this->_validate_syntax( $id ) ; ?>
				<br /><?php Doc::notice_htaccess() ; ?>
			</div>
		</td>
	</tr>
