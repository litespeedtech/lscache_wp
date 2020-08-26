<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

global $wp_roles;
if ( !isset( $wp_roles ) ) {
	$wp_roles = new \WP_Roles();
}

$roles = array();
foreach ( $wp_roles->roles as $k => $v ) {
	$roles[ $k ] = $v[ 'name' ];
}
ksort( $roles );

?>
<h3 class="litespeed-title-short">
	<?php echo __( 'Tuning Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#tuning-settings-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_CSS_COMB_PRIO; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Load combined CSS files before other CSS files.', 'litespeed-cache' ); ?>
				<?php echo sprintf( __( 'Set to %s by default.', 'litespeed-cache' ), __( 'OFF', 'litespeed-cache' ) ); ?>
				<br /><font class="litespeed-warning">
					⚠️
					<?php echo sprintf( __( 'Only set to %s when changing the order of combined and uncombined CSS is needed.', 'litespeed-cache'), __( 'ON', 'litespeed-cache' ) ); ?>
				</font>
				<br /><font class="litespeed-success">
					<?php echo __('API', 'litespeed-cache'); ?>:
					<?php echo sprintf( __( 'Elements with attribute %s in html code will be excluded from moving to top.', 'litespeed-cache' ), '<code>data-optimized="0"</code>' ); ?>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_CSS_EXC; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Listed CSS files will not be minified/combined.', 'litespeed-cache' ); ?>
				<?php Doc::full_or_partial_url(); ?>
				<?php Doc::one_per_line(); ?>
				<br /><font class="litespeed-success">
					<?php echo __( 'API', 'litespeed-cache' ); ?>:
					<?php echo sprintf( __( 'Filter %s is supported.', 'litespeed-cache' ), '<code>litespeed_optimize_css_excludes</code>' ); ?>
					<?php echo sprintf( __( 'Elements with attribute %s in html code will be excluded.', 'litespeed-cache' ), '<code>data-no-optimize="1"</code>' ); ?>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_JS_COMB_PRIO; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Load combined JS files before other JS files.', 'litespeed-cache' ); ?>
				<?php echo sprintf( __( 'Set to %s by default.', 'litespeed-cache' ), __( 'OFF', 'litespeed-cache' ) ); ?>
				<br /><font class="litespeed-warning">
					⚠️
					<?php echo sprintf( __( 'Only set to %s when changing the order of combined and uncombined JS is needed.', 'litespeed-cache'), __( 'ON', 'litespeed-cache' ) ); ?>
				</font>
				<br /><font class="litespeed-success">
					<?php echo __( 'API', 'litespeed-cache' ); ?>:
					<?php echo sprintf( __( 'Elements with attribute %s in html code will be excluded from moving to top/bottom.', 'litespeed-cache' ), '<code>data-optimized="0"</code>' ); ?>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_JS_EXC; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Listed JS files will not be minified/combined.', 'litespeed-cache' ); ?>
				<?php Doc::full_or_partial_url(); ?>
				<?php Doc::one_per_line(); ?>
				<br /><font class="litespeed-success">
					<?php echo __( 'API', 'litespeed-cache' ); ?>:
					<?php echo sprintf( __( 'Filter %s is supported.', 'litespeed-cache' ), '<code>litespeed_optimize_js_excludes</code>' ); ?>
					<?php echo sprintf( __( 'Elements with attribute %s in html code will be excluded.', 'litespeed-cache' ), '<code>data-no-optimize="1"</code>' ); ?>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_MAX_SIZE; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input( $id, 'litespeed-input-short' ); ?> <?php echo __( 'MB', 'litespeed-cache' ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Specify the maximum size in megabytes for combined files.', 'litespeed-cache' ); ?>
				<?php echo __( 'Larger numbers will generate fewer files, which is better for achieving higher page scores, but can cause heavy memory usage.', 'litespeed-cache' ); ?>
				<?php $this->recommended( $id ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_CCSS_CON; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ); ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Specify critical CSS rules for above-the-fold content when enabling %s.', 'litespeed-cache' ), __( 'Load CSS Asynchronously', 'litespeed-cache' ) ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_JS_DEFER_EXC; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Listed JS files will not be deferred.', 'litespeed-cache' ); ?>
				<?php Doc::full_or_partial_url(); ?>
				<?php Doc::one_per_line(); ?>
				<br /><span class="litespeed-success">
					<?php echo __( 'API', 'litespeed-cache' ); ?>:
					<?php echo sprintf( __( 'Filter %s is supported.', 'litespeed-cache' ), '<code>litespeed_optm_js_defer_exc</code>' ); ?>
					<?php echo sprintf( __( 'Elements with attribute %s in html code will be excluded.', 'litespeed-cache' ), '<code>data-no-defer="1"</code>' ); ?>
				</span>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_JS_INLINE_DEFER_EXC; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Inline JS codes including the above fragments will not be deferred.', 'litespeed-cache' ); ?>
				<?php Doc::one_per_line(); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_EXC; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Prevent any optimization of listed pages.', 'litespeed-cache' ); ?>
				<?php $this->_uri_usage_example(); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_EXC_ROLES; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<div class="litespeed-desc">
				<?php echo __( 'Selected roles will be excluded from all optimizations.', 'litespeed-cache' ); ?>
			</div>
			<div class="litespeed-tick-list">
				<?php foreach ( $roles as $role => $title ): ?>
					<?php $this->build_checkbox( $id . '[]', $title, $this->__cfg->in_optm_exc_roles( $role ), $role ); ?>
				<?php endforeach; ?>
			</div>

		</td>
	</tr>

</tbody></table>
