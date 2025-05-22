<?php
/**
 * LiteSpeed Cache Exclude Cookies Setting
 *
 * Displays the exclude cookies setting for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;
?>

<tr>
	<th scope="row">
		<?php $option_id = Base::O_CACHE_EXC_COOKIES; ?>
		<?php $this->title( $option_id ); ?>
	</th>
	<td>
		<?php $this->build_textarea( $option_id ); ?>
		<div class="litespeed-desc">
			<?php
			printf(
				/* translators: %s: "cookies" */
				esc_html__( 'To prevent %s from being cached, enter them here.', 'litespeed-cache' ),
				esc_html__( 'cookies', 'litespeed-cache' )
			);
			?>
			<?php Doc::one_per_line(); ?>
			<?php $this->_validate_syntax( $option_id ); ?>
			<br /><?php Doc::notice_htaccess(); ?>
		</div>
	</td>
</tr>