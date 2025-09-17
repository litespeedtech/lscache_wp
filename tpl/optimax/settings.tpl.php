<?php
/**
 * LiteSpeed Cache OptimaX Settings
 *
 * Manages OptimaX settings for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 8.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$this->form_action();
?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'OptimaX Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/imageopt/#image-optimization-settings-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table">
	<tbody>

		<tr>
			<th>
				<?php $option_id = Base::O_OPTIMAX; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Turn on OptimaX. This will automatically request your pages OptimaX result via cron job.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

	</tbody>
</table>

<?php
$this->form_end();
