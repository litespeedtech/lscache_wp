<?php
/**
 * LiteSpeed Cache Tuning Settings
 *
 * Manages tuning settings for LiteSpeed Cache, including Guest Mode configurations.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$this->form_action();
?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'Tuning Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/general/#tuning-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>
	<tr>
		<th>
			<?php $option_id = Base::O_GUEST_UAS; ?>
			<?php $this->title( $option_id ); ?>
		</th>
		<td>
			<div class="litespeed-textarea-recommended">
				<div>
					<?php $this->build_textarea( $option_id, 30 ); ?>
				</div>
				<div>
					<?php $this->recommended( $option_id ); ?>
				</div>
			</div>

			<div class="litespeed-desc">
				<?php esc_html_e( 'Listed User Agents will be considered as Guest Mode visitors.', 'litespeed-cache' ); ?>
				<?php Doc::one_per_line(); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $option_id = Base::O_GUEST_IPS; ?>
			<?php $this->title( $option_id ); ?>
		</th>
		<td>
			<div class="litespeed-textarea-recommended">
				<div>
					<?php $this->build_textarea( $option_id, 50 ); ?>
				</div>
				<div>
					<?php $this->recommended( $option_id ); ?>
				</div>
			</div>

			<div class="litespeed-desc">
				<?php esc_html_e( 'Listed IPs will be considered as Guest Mode visitors.', 'litespeed-cache' ); ?>
				<?php Doc::one_per_line(); ?>
			</div>
		</td>
	</tr>
</tbody></table>

<?php $this->form_end(); ?>