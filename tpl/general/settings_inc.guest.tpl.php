<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;
?>
	<tr>
		<th>
			<?php $id = Base::O_GUEST; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Guest option will give an always cacheable landing page to the first time visit, then try to update the vary.', 'litespeed-cache' ); ?>
				<?php echo __( 'This option can help correcting certain advanced mobile/tablet users.', 'litespeed-cache' ); ?>
				<br /><?php Doc::notice_htaccess(); ?>
			</div>
		</td>
	</tr>

