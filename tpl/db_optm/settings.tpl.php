<?php
/**
 * LiteSpeed Cache Database Optimization Settings
 *
 * Manages settings for database optimization in LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$this->form_action();
?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'DB Optimization Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/database/#db-optimization-settings-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>
	<tr>
		<th>
			<?php $option_id = Base::O_DB_OPTM_REVISIONS_MAX; ?>
			<?php $this->title( $option_id ); ?>
		</th>
		<td>
			<?php $this->build_input( $option_id, 'litespeed-input-short' ); ?>
			<div class="litespeed-desc">
				<?php esc_html_e( 'Specify the number of most recent revisions to keep when cleaning revisions.', 'litespeed-cache' ); ?>
				<?php $this->_validate_ttl( $option_id, 1, 100, true ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $option_id = Base::O_DB_OPTM_REVISIONS_AGE; ?>
			<?php $this->title( $option_id ); ?>
		</th>
		<td>
			<?php $this->build_input( $option_id, 'litespeed-input-short' ); ?> <?php esc_html_e( 'Day(s)', 'litespeed-cache' ); ?>
			<div class="litespeed-desc">
				<?php esc_html_e( 'Revisions newer than this many days will be kept when cleaning revisions.', 'litespeed-cache' ); ?>
				<?php $this->_validate_ttl( $option_id, 1, 600, true ); ?>
			</div>
		</td>
	</tr>

</tbody></table>

<?php
$this->form_end();
?>