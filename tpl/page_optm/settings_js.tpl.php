<?php
/**
 * LiteSpeed Cache JS Settings
 *
 * Renders the JS optimization settings interface for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;
?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'JS Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#js-settings-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table">
	<tbody>

		<tr>
			<th>
				<?php $option_id = Base::O_OPTM_JS_MIN; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<?php Doc::maybe_on_by_gm( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Minify JS files and inline JS codes.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_OPTM_JS_COMB; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<?php Doc::maybe_on_by_gm( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Combine all local JS files into a single file.', 'litespeed-cache' ); ?>
					<a href="https://docs.litespeedtech.com/lscache/lscwp/ts-optimize/" target="_blank"><?php esc_html_e( 'How to Fix Problems Caused by CSS/JS Optimization.', 'litespeed-cache' ); ?></a>
					<br />
					<font class="litespeed-danger">
						🚨 <?php esc_html_e( 'This option may result in a JS error or layout issue on frontend pages with certain themes/plugins.', 'litespeed-cache' ); ?>
						<?php esc_html_e( 'JS error can be found from the developer console of browser by right clicking and choosing Inspect.', 'litespeed-cache' ); ?>
					</font>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_OPTM_JS_COMB_EXT_INL; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php printf( esc_html__( 'Include external JS and inline JS in combined file when %1$s is also enabled. This option helps maintain the priorities of JS execution, which should minimize potential errors caused by JS Combine.', 'litespeed-cache' ), '<code>' . esc_html( Lang::title( Base::O_OPTM_JS_COMB ) ) . '</code>' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_OPTM_JS_DEFER; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id, array( esc_html__( 'OFF', 'litespeed-cache' ), esc_html__( 'Deferred', 'litespeed-cache' ), esc_html__( 'Delayed', 'litespeed-cache' ) ) ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Deferring until page is parsed or delaying till interaction can help reduce resource contention and improve performance causing a lower FID (Core Web Vitals metric).', 'litespeed-cache' ); ?>
					<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#load-js-deferred' ); ?><br />
					<?php esc_html_e( 'This can improve your speed score in services like Pingdom, GTmetrix and PageSpeed.', 'litespeed-cache' ); ?>
					<?php Doc::learn_more( 'https://web.dev/fid/#what-is-fid' ); ?>
					<br />
					<font class="litespeed-danger">
						🚨 <?php esc_html_e( 'This option may result in a JS error or layout issue on frontend pages with certain themes/plugins.', 'litespeed-cache' ); ?>
					</font>
				</div>
			</td>
		</tr>

	</tbody>
</table>