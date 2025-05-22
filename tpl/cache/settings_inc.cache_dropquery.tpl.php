<?php
/**
 * LiteSpeed Cache Drop Query Strings Setting
 *
 * Displays the drop query strings setting for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;
?>

<tr>
	<th scope="row">
		<?php $option_id = Base::O_CACHE_DROP_QS; ?>
		<?php $this->title( $option_id ); ?>
	</th>
	<td>
		<?php $this->build_textarea( $option_id, 40 ); ?>
		<div class="litespeed-desc">
			<?php
			printf(
				/* translators: %s: LiteSpeed Web Server version */
				esc_html__( 'Ignore certain query strings when caching. (LSWS %s required)', 'litespeed-cache' ),
				'v5.2.3+'
			);
			?>
			<?php
			printf(
				/* translators: %1$s: Example query string, %2$s: Example wildcard */
				esc_html__( 'For example, to drop parameters beginning with %1$s, %2$s can be used here.', 'litespeed-cache' ),
				'<code>utm</code>',
				'<code>utm*</code>'
			);
			?>
			<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cache/#drop-query-string' ); ?>
			<br />
			<?php Doc::one_per_line(); ?>
			<br />
			<?php Doc::notice_htaccess(); ?>
		</div>
	</td>
</tr>