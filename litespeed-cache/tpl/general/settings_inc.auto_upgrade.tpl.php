<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;
?>

	<!-- build_setting_auto_upgrade -->
	<tr>
		<th>
			<?php $id = Base::O_AUTO_UPGRADE; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Turn this option ON to have LiteSpeed Cache updated automatically, whenever a new version is released. If OFF, update manually as usual.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>
