<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

?>
<h3 class="litespeed-title-short">
	<?php echo __( 'Tuning Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/general/#tuning-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>
	<tr>
		<th>
			<?php $id = Base::O_GUEST_UAS; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id, 30 ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Listed User Agents will be considered as Guest Mode visitors.', 'litespeed-cache' ); ?>
				<?php Doc::one_per_line(); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_GUEST_IPS; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id, 50 ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Listed IPs will be considered as Guest Mode visitors.', 'litespeed-cache' ); ?>
				<?php Doc::one_per_line(); ?>
			</div>
		</td>
	</tr>
</tbody></table>
