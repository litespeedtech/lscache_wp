<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;
?>

	<!-- build_setting_cache_resources -->
	<tr>
		<th>
			<?php $id = Base::O_CACHE_RES; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Some themes and plugins add resources via a PHP request.', 'litespeed-cache' ); ?>
				<?php echo __( 'Caching these pages may improve server performance by avoiding unnecessary PHP calls.', 'litespeed-cache' ); ?>
				<br /><?php Doc::notice_htaccess(); ?>
			</div>
		</td>
	</tr>
