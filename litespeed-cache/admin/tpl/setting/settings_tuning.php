<?php
if ( ! defined( 'WPINC' ) ) die ;

?>

<h3 class="litespeed-title-short">
	<?php echo __('Tuning Settings', 'litespeed-cache'); ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:tuning', false, 'litespeed-learn-more' ) ; ?>
</h3>
<table><tbody>

	<tr>
		<th><?php echo __( 'Combined CSS Priority', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_CSS_COMBINED_PRIORITY ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Load combined CSS files before other CSS files.', 'litespeed-cache' ) ; ?>
				<?php echo sprintf( __( 'Set to %s by default.', 'litespeed-cache' ), __( 'OFF', 'litespeed-cache' ) ) ; ?>
				<br /><font class="litespeed-warning">
					⚠️
					<?php echo sprintf( __( 'Only set to %s when changing the order of combined and uncombined CSS is needed.', 'litespeed-cache'), __( 'ON', 'litespeed-cache' ) ) ; ?>
				</font>
				<br /><font class="litespeed-success">
					<?php echo __('API', 'litespeed-cache'); ?>:
					<?php echo sprintf( __( 'Elements with attribute %s in html code will be excluded from moving to top.', 'litespeed-cache' ), '<code>data-optimized="0"</code>' ) ; ?>
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
					<?php echo __('API', 'litespeed-cache'); ?>:
					<?php echo sprintf( __( 'Filter %s is supported.', 'litespeed-cache' ), '<code>litespeed_cache_optimize_css_excludes</code>' ) ; ?>
					<?php echo sprintf( __( 'Elements with attribute %s in html code will be excluded.', 'litespeed-cache' ), '<code>data-no-optimize="1"</code>' ) ; ?>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Combined JS Priority', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_JS_COMBINED_PRIORITY ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Load combined JS files before other JS files.', 'litespeed-cache' ) ; ?>
				<?php echo sprintf( __( 'Set to %s by default.', 'litespeed-cache' ), __( 'OFF', 'litespeed-cache' ) ) ; ?>
				<br /><font class="litespeed-warning">
					⚠️
					<?php echo sprintf( __( 'Only set to %s when changing the order of combined and uncombined JS is needed.', 'litespeed-cache'), __( 'ON', 'litespeed-cache' ) ) ; ?>
				</font>
				<br /><font class="litespeed-success">
					<?php echo __('API', 'litespeed-cache'); ?>:
					<?php echo sprintf( __( 'Elements with attribute %s in html code will be excluded from moving to top/bottom.', 'litespeed-cache' ), '<code>data-optimized="0"</code>' ) ; ?>
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
					<?php echo __('API', 'litespeed-cache'); ?>:
					<?php echo sprintf( __( 'Filter %s is supported.', 'litespeed-cache' ), '<code>litespeed_cache_optimize_js_excludes</code>' ) ; ?>
					<?php echo sprintf( __( 'Elements with attribute %s in html code will be excluded.', 'litespeed-cache' ), '<code>data-no-optimize="1"</code>' ) ; ?>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Max Combined File Size', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::OPID_OPTM_MAX_SIZE ; ?>
			<?php $this->build_input( $id, 'litespeed-input-short' ) ; ?> <?php echo __( 'MB', 'litespeed-cache' ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Specify the maximum size in megabytes for combined files.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'Larger numbers will generate fewer files, which is better for achieving higher page scores, but can cause heavy memory usage.', 'litespeed-cache' ) ; ?>
				<?php $this->recommended( $id ) ; ?>
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
				<br /><font class="litespeed-warning">
					⚠️
					<?php echo __( 'Google reCAPTCHA will be bypassed automatically.', 'litespeed-cache' ) ; ?>
				</font>
				<br /><font class="litespeed-success">
					<?php echo __('API', 'litespeed-cache'); ?>:
					<?php echo sprintf( __( 'Append query string %s to the resources to bypass this action.', 'litespeed-cache' ), '<code>&_litespeed_rm_qs=0</code>' ) ; ?>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Load Google Fonts Asynchronously', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_OPTM_GGFONTS_ASYNC ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Use Web Font Loader library to load Google Fonts asynchronously while leave other CSS intact.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This will also add a preconnect to Google for faster Google Fonts downloading.', 'litespeed-cache' ) ; ?>
				<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:tuning:google-fonts' ) ; ?>
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
					<?php echo __('API', 'litespeed-cache'); ?>:
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
			<?php $this->build_textarea2( LiteSpeed_Cache_Config::ITEM_OPTM_EXCLUDES ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Prevent any optimization of listed pages.', 'litespeed-cache' ) ; ?>
				<?php $this->_uri_usage_example() ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Role Excludes', 'litespeed-cache'); ?></th>
		<td>
			<?php foreach ( $roles as $role => $title ): ?>
				<?php $this->build_checkbox( LiteSpeed_Cache_Config::EXCLUDE_OPTIMIZATION_ROLES . "][", $title, $this->config->in_exclude_optimization_roles( $role ), $role ) ; ?>
			<?php endforeach; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Selected roles will be excluded from all optimizations.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>


</tbody></table>