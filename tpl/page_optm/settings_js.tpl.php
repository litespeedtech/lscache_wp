<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;
?>

<h3 class="litespeed-title-short">
	<?php echo __( 'JS Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#js-settings-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_JS_MIN; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Minify JS files and inline JS codes.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_JS_COMB; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Combine all local JS files into a single file.', 'litespeed-cache' ); ?>
				<a href="https://docs.litespeedtech.com/lscache/lscwp/ts-optimize/" target="_blank"><?php echo __( 'How to Fix Problems Caused by CSS/JS Optimization.', 'litespeed-cache' ); ?></a>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_JS_COMB_EXT_INL; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Include external JS and inline JS in combined file when %1$s is also enabled. This option helps maintain the priorities of JS execution, which should minimize potential errors caused by JS Combine.', 'litespeed-cache' ), '<code>' . Lang::title( Base::O_OPTM_JS_COMB ) . '</code>' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_JS_HTTP2; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Pre-send internal JS files to the browser before they are requested. (Requires the HTTP/2 protocol)', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_JS_DEFER; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Doing so can help reduce resource contention and improve performance.', 'litespeed-cache' ); ?>
				<?php echo __( 'This can improve your speed score in services like Pingdom, GTmetrix and PageSpeed.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_JS_INLINE_DEFER; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id, array( __( 'Default', 'litespeed-cache' ), __( 'After DOM Ready', 'litespeed-cache' ), __( 'Deferred', 'litespeed-cache' ) ) ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Loading inline JS after DOM is fully loaded can increase JS compatibility and reduce JS error when other JS optimization features are enabled.', 'litespeed-cache' ); ?>
				<br /><?php echo sprintf( __( '%s is recommended although would cause the most issues for scripts that are placed inline to avoid being deferred.', 'litespeed-cache' ), '<code>' . __( 'Deferred', 'litespeed-cache' ) . '</code>' ); ?>
			</div>
		</td>
	</tr>

</tbody></table>
