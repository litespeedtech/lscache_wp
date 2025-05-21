<?php
namespace LiteSpeed;

defined( 'WPINC' ) || exit;
?>

	<tr>
		<th>
			<?php $id = Base::O_CACHE_DROP_QS; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id, 40 ); ?>
			<div class="litespeed-desc">
				<?php printf( __( 'Ignore certain query strings when caching. (LSWS %s required)', 'litespeed-cache' ), 'v5.2.3+' ); ?>
				<?php printf( __( 'For example, to drop parameters beginning with %1$s, %2$s can be used here.', 'litespeed-cache' ), '<code>utm</code>', '<code>utm*</code>' ); ?>
				<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cache/#drop-query-string' ); ?>

				<br />
				<?php Doc::one_per_line(); ?>

				<br /><?php Doc::notice_htaccess(); ?>
			</div>
		</td>
	</tr>
