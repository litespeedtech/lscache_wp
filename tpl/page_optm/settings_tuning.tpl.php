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
			<?php $id = Base::O_OPTM_CSS_EXC; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Listed CSS files or inline CSS code will not be minified/combined.', 'litespeed-cache' ); ?>
				<?php Doc::full_or_partial_url(); ?>
				<?php Doc::one_per_line(); ?>
				<br /><font class="litespeed-success">
					<?php echo __( 'API', 'litespeed-cache' ); ?>:
					<?php echo sprintf( __( 'Filter %s is supported.', 'litespeed-cache' ), '<code>litespeed_optimize_css_excludes</code>' ); ?>
					<?php echo sprintf( __( 'Elements with attribute %s in html code will be excluded.', 'litespeed-cache' ), '<code>data-no-optimize="1"</code>' ); ?>
					<br /><?php echo __( 'Predefined list will also be combined w/ the above settings', 'litespeed-cache' ); ?>: <a href="https://github.com/litespeedtech/lscache_wp/blob/dev/data/css_excludes.txt" target="_blank">https://github.com/litespeedtech/lscache_wp/blob/dev/data/css_excludes.txt</a>
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
				<?php echo __( 'Listed JS files or inline JS code will not be minified/combined.', 'litespeed-cache' ); ?>
				<?php Doc::full_or_partial_url(); ?>
				<?php Doc::one_per_line(); ?>
				<br /><font class="litespeed-success">
					<?php echo __( 'API', 'litespeed-cache' ); ?>:
					<?php echo sprintf( __( 'Filter %s is supported.', 'litespeed-cache' ), '<code>litespeed_optimize_js_excludes</code>' ); ?>
					<?php echo sprintf( __( 'Elements with attribute %s in html code will be excluded.', 'litespeed-cache' ), '<code>data-no-optimize="1"</code>' ); ?>
					<br /><?php echo __( 'Predefined list will also be combined w/ the above settings', 'litespeed-cache' ); ?>: <a href="https://github.com/litespeedtech/lscache_wp/blob/dev/data/js_excludes.txt" target="_blank">https://github.com/litespeedtech/lscache_wp/blob/dev/data/js_excludes.txt</a>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_UCSS_WHITELIST; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'List the CSS selector that its style should be always contained in UCSS.', 'litespeed-cache' ); ?>
				<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#ucss-whitelist', __( 'How to choose an UCSS whitelist selector?', 'litespeed-cache' ) ); ?>
				<br /><?php echo sprintf( __( 'Wildcard %s supported.', 'litespeed-cache' ), '<code>*</code>' ); ?>
				<div class="litespeed-callout notice notice-warning inline">
					<h4><?php echo __( 'Note', 'litespeed-cache' ); ?></h4>
					<p>
						<?php echo __( 'The selector must exist in the CSS. Parent classes in the HTML will not work.', 'litespeed-cache' ); ?>
					</p>
				</div>
				<font class="litespeed-success">
					<?php echo __( 'Predefined list will also be combined w/ the above settings', 'litespeed-cache' ); ?>: <a href="https://github.com/litespeedtech/lscache_wp/blob/dev/data/ucss_whitelist.txt" target="_blank">https://github.com/litespeedtech/lscache_wp/blob/dev/data/ucss_whitelist.txt</a>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_UCSS_EXC; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Listed URI will not generate UCSS.', 'litespeed-cache' ); ?>
				<?php Doc::full_or_partial_url(); ?>
				<?php Doc::one_per_line(); ?>
				<br /><span class="litespeed-success">
					<?php echo __( 'API', 'litespeed-cache' ); ?>:
					<?php echo sprintf( __( 'Filter %s is supported.', 'litespeed-cache' ), '<code>litespeed_ucss_exc</code>' ); ?>
				</span>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_CCSS_SEP_POSTTYPE; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __('List post types where each item of that type should have its own CCSS generated.', 'litespeed-cache'); ?>
				<?php echo sprintf( __( 'For example, if every Page on the site has different formatting, enter %s in the box. Separate critical CSS files will be stored for every Page on the site.', 'litespeed-cache' ), '<code>page</code>' ); ?>
				<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#separate-ccss-cache-post-types_1' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_CCSS_SEP_URI; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Separate critical CSS files will be generated for paths containing these strings.', 'litespeed-cache' ); ?>
				<?php $this->_uri_usage_example(); ?>
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
				<?php echo __( 'Listed JS files or inline JS code will not be deferred.', 'litespeed-cache' ); ?>
				<?php Doc::full_or_partial_url(); ?>
				<?php Doc::one_per_line(); ?>
				<br /><span class="litespeed-success">
					<?php echo __( 'API', 'litespeed-cache' ); ?>:
					<?php echo sprintf( __( 'Filter %s is supported.', 'litespeed-cache' ), '<code>litespeed_optm_js_defer_exc</code>' ); ?>
					<?php echo sprintf( __( 'Elements with attribute %s in html code will be excluded.', 'litespeed-cache' ), '<code>data-no-defer="1"</code>' ); ?>
					<br /><?php echo __( 'Predefined list will also be combined w/ the above settings', 'litespeed-cache' ); ?>: <a href="https://github.com/litespeedtech/lscache_wp/blob/dev/data/js_defer_excludes.txt" target="_blank">https://github.com/litespeedtech/lscache_wp/blob/dev/data/js_defer_excludes.txt</a>
				</span>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_GM_JS_EXC; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ); ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Listed JS files or inline JS code will not be optimized by %s.', 'litespeed-cache' ), '<code>' . Lang::title( Base::O_GUEST ) . '</code>' ); ?>
				<?php Doc::full_or_partial_url(); ?>
				<?php Doc::one_per_line(); ?>
				<br /><span class="litespeed-success">
					<?php echo __( 'API', 'litespeed-cache' ); ?>:
					<?php echo sprintf( __( 'Filter %s is supported.', 'litespeed-cache' ), '<code>litespeed_optm_gm_js_exc</code>' ); ?>
					<?php echo sprintf( __( 'Elements with attribute %s in html code will be excluded.', 'litespeed-cache' ), '<code>data-no-defer="1"</code>' ); ?>
				</span>
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
			<?php $id = Base::O_OPTM_GUEST_ONLY; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Only optimize pages for guest (not logged in) visitors. If turned this OFF, CSS/JS/CCSS files will be doubled by each user group.', 'litespeed-cache' ); ?>
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
					<?php $this->build_checkbox( $id . '[]', $title, $this->cls( 'Conf' )->in_optm_exc_roles( $role ), $role ); ?>
				<?php endforeach; ?>
			</div>

		</td>
	</tr>

</tbody></table>
