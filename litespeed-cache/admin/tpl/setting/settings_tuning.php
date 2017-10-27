<?php
if ( ! defined( 'WPINC' ) ) die ;

global $wp_roles ;
if ( !isset( $wp_roles ) ) {
	$wp_roles = new WP_Roles() ;
}
$roles = array_keys( $wp_roles->roles ) ;

sort( $roles ) ;

?>

<h3 class="litespeed-title"><?php echo __( 'Tuning Settings', 'litespeed-cache' ) ; ?></h3>

<table><tbody>

	<tr>
		<th><?php echo __( 'CSS Combined Priority', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_CSS_COMBINED_PRIORITY ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Load the combined CSS files first, before the other CSS files. By default the uncombined CSS will enqueue first.', 'litespeed-cache' ) ; ?>
				<br /><font class="litespeed-warning">
					<?php echo __('NOTE:', 'litespeed-cache'); ?>
					<?php echo __( 'Only turn this on when you need to change the order of combined and uncombined CSS.', 'litespeed-cache') ; ?>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'CSS Excludes', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_textarea(LiteSpeed_Cache_Config::OPID_CSS_EXCLUDES); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Listed CSS files will not be minified/combined.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'Both full URLs and partial strings can be used.', 'litespeed-cache' ) ; ?>
				<?php echo __('One per line.', 'litespeed-cache'); ?>
				<br /><font class="litespeed-success">
					<?php echo __('API:', 'litespeed-cache'); ?>
					<?php echo sprintf( __( 'Filter %s is supported.', 'litespeed-cache' ), '<code>litespeed_cache_optimize_css_excludes</code>' ) ; ?>
					<?php echo sprintf( __( 'Elements with attribute %s in html code will be excluded.', 'litespeed-cache' ), '<code>data-no-optimize="1"</code>' ) ; ?>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'JS Combined Priority', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_JS_COMBINED_PRIORITY ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Load the combined JS files first, before the other JS files. By default the uncombined JS will enqueue first.', 'litespeed-cache' ) ; ?>
				<br /><font class="litespeed-warning">
					<?php echo __('NOTE:', 'litespeed-cache'); ?>
					<?php echo __( 'Only turn this on when you need to change the order of combined and uncombined JS.', 'litespeed-cache') ; ?>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'JS Excludes', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_textarea(LiteSpeed_Cache_Config::OPID_JS_EXCLUDES); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Listed JS files will not be minified/combined.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'Both full URLs and partial strings can be used.', 'litespeed-cache' ) ; ?>
				<?php echo __('One per line.', 'litespeed-cache'); ?>
				<br /><font class="litespeed-success">
					<?php echo __('API:', 'litespeed-cache'); ?>
					<?php echo sprintf( __( 'Filter %s is supported.', 'litespeed-cache' ), '<code>litespeed_cache_optimize_js_excludes</code>' ) ; ?>
					<?php echo sprintf( __( 'Elements with attribute %s in html code will be excluded.', 'litespeed-cache' ), '<code>data-no-optimize="1"</code>' ) ; ?>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Remove Query Strings', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_OPTM_QS_RM ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Remove query strings from static resources.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This can improve your speed score in services like Pingdom, GTmetrix and PageSpeed.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Remove Google Fonts', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_OPTM_GGFONTS_RM ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Prevent google fonts from loading on all your pages.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Critical CSS Rules', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_textarea2( LiteSpeed_Cache_Config::ITEM_OPTM_CSS ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Specify critical CSS rules for above-the-fold content when enabling %s.', 'litespeed-cache' ), __( 'Load CSS Asynchronously', 'litespeed-cache' ) ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'JS Deferred Excludes', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_textarea2( LiteSpeed_Cache_Config::ITEM_OPTM_JS_DEFER_EXC ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Listed JS files will not be deferred.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'Both full URLs and partial strings can be used.', 'litespeed-cache' ) ; ?>
				<?php echo __('One per line.', 'litespeed-cache'); ?>
				<br /><font class="litespeed-success">
					<?php echo __('API:', 'litespeed-cache'); ?>
					<?php echo sprintf( __( 'Filter %s is supported.', 'litespeed-cache' ), '<code>litespeed_optm_js_defer_exc</code>' ) ; ?>
					<?php echo sprintf( __( 'Elements with attribute %s in html code will be excluded.', 'litespeed-cache' ), '<code>data-no-defer="1"</code>' ) ; ?>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Remove WordPress Emoji', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_OPTM_EMOJI_RM ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Stop loading wordpress.org emoji. Browser default emoji will be displayed instead.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This can improve your speed score in services like Pingdom, GTmetrix and PageSpeed.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'URI Excludes', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_textarea(LiteSpeed_Cache_Config::OPID_OPTM_EXCLUDES); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Prevent any optimization of listed pages.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'Both full URLs and partial strings can be used.', 'litespeed-cache' ) ; ?>
				<?php echo sprintf( __( 'For example, for %s, %s can be used here.', 'litespeed-cache' ), '<code>/mypath/mypage?aa=bb</code>', '<code>mypage?aa=</code>' ) ; ?>
				<?php echo __('One per line.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Role Excludes', 'litespeed-cache'); ?></th>
		<td>
			<?php foreach ( $roles as $role ): ?>
				<?php $this->build_checkbox( LiteSpeed_Cache_Config::EXCLUDE_OPTIMIZATION_ROLES . "][", $role, $this->config->in_exclude_optimization_roles( $role ), $role ) ; ?>
			<?php endforeach; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Prevent any optimization of list roles.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>


</tbody></table>