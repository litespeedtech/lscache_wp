<?php
if ( ! defined( 'WPINC' ) ) die ;

$last_critical_css_generated = LiteSpeed_Cache_CSS::get_summary() ;

?>

<h3 class="litespeed-title-short">
	<?php echo __('Optimization Settings', 'litespeed-cache'); ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:optimize', false, 'litespeed-learn-more' ) ; ?>
</h3>

<?php if ( ! LiteSpeed_Cache_Data::optm_available() ) : ?>
<div class="litespeed-callout-danger">
	<h4><?php echo __( 'WARNING', 'litespeed-cache' ) ; ?></h4>
	<p><?php echo sprintf( __( 'Failed to create Optimizer table. Please follow <a %s>Table Creation guidance from LiteSpeed Wiki</a> to finish setup.', 'litespeed-cache' ), 'href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:installation" target="_blank"' ) ; ?></p>
</div>
<?php endif; ?>

<div class="litespeed-callout-warning">
	<h4><?php echo __( 'NOTICE', 'litespeed-cache' ) ; ?></h4>
	<p><?php echo __( 'Please test thoroughly when enabling any option in this list. After changing Minify/Combine settings, please do a Purge All action.', 'litespeed-cache' ) ; ?></p>
</div>


<table><tbody>
	<tr>
		<th><?php echo __( 'CSS Minify', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_CSS_MINIFY ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Minify CSS files.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'CSS Combine', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_CSS_COMBINE ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Combine CSS files.', 'litespeed-cache' ) ; ?>
				<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:optimize-issue" target="_blank"><?php echo __( 'How to Fix Problems Caused by CSS/JS Optimization.', 'litespeed-cache' ) ; ?></a>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'CSS HTTP/2 Push', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_CSS_HTTP2 ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Pre-send internal CSS files to the browser before they are requested. (Requires the HTTP/2 protocol)', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'JS Minify', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_JS_MINIFY ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Minify JS files.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'JS Combine', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_JS_COMBINE ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Combine JS files.', 'litespeed-cache' ) ; ?>
				<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:optimize-issue" target="_blank"><?php echo __( 'How to Fix Problems Caused by CSS/JS Optimization.', 'litespeed-cache' ) ; ?></a>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'JS HTTP/2 Push', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_JS_HTTP2 ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Pre-send internal JS files to the browser before they are requested. (Requires the HTTP/2 protocol)', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'CSS/JS Cache TTL', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $id = LiteSpeed_Cache_Config::OPID_OPTIMIZE_TTL ; ?>
			<?php $this->build_input( $id ) ; ?> <?php echo __( 'seconds', 'litespeed-cache' ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Specify how long, in seconds, CSS/JS files are cached. Minimum is %1$s seconds.', 'litespeed-cache' ), 3600 ) ; ?>
				<?php $this->recommended( $id ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'HTML Minify', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_HTML_MINIFY ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Minify HTML content.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left"><?php echo __( 'Inline CSS Minify', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_CSS_INLINE_MINIFY ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Minify inline CSS code.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left"><?php echo __( 'Inline JS Minify', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_JS_INLINE_MINIFY ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Minify inline JS code.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Load CSS Asynchronously', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_OPTM_CSS_ASYNC ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Optimize CSS delivery.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This can improve your speed score in services like Pingdom, GTmetrix and PageSpeed.', 'litespeed-cache' ) ; ?><br />
				<?php echo sprintf( __( 'When this option is turned %s, it will also load Google Fonts asynchronously.', 'litespeed-cache' ), '<code>' . __( 'ON', 'litespeed-cache' ) . '</code>' ) ; ?>
				<br /><font class="litespeed-success">
					<?php echo __('API', 'litespeed-cache'); ?>:
					<?php echo sprintf( __( 'Elements with attribute %s in html code will be excluded.', 'litespeed-cache' ), '<code>data-no-async="1"</code>' ) ; ?>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left"><?php echo __( 'Generate Critical CSS', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPT_OPTM_CCSS_GEN ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Leave this option %1$s to allow communication with LiteSpeed CCSS server. If set to %2$s, Critical CSS will not be generated.', 'litespeed-cache' ), '<code>' . __( 'ON', 'litespeed-cache' ) . '</code>', '<code>' . __( 'OFF', 'litespeed-cache' ) . '</code>' ) ; ?><br />
				<?php echo sprintf( __( 'This option only works if %1$s is %2$s.', 'litespeed-cache' ), '<code>' . __( 'Load CSS Asynchronously', 'litespeed-cache' ) . '</code>', '<code>' . __( 'ON', 'litespeed-cache' ) . '</code>' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left"><?php echo __( 'Generate Critical CSS In Background', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPT_OPTM_CCSS_ASYNC ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Automatically generate critical CSS in the background via a cron-based queue.', 'litespeed-cache' ) ; ?>
				<?php echo sprintf( __( 'If set to %s this is done in the foreground, which may slow down page load.', 'litespeed-cache' ), '<code>' . __('OFF', 'litespeed-cache') . '</code>' ) ; ?>
				<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:optimize#generate_critical_css' ) ; ?>
			</div>

			<?php if ( $last_critical_css_generated ) : ?>
			<div class="litespeed-desc litespeed-left20">
				<?php if ( ! empty( $last_critical_css_generated[ 'last_request' ] ) ) : ?>
					<p>
						<?php echo __( 'Last generated', 'litespeed-cache' ) . ': <code>' . LiteSpeed_Cache_Utility::readable_time( $last_critical_css_generated[ 'last_request' ] ) . '</code>' ; ?>
					</p>
					<p>
						<?php echo __( 'Last requested cost', 'litespeed-cache' ) . ': <code>' . $last_critical_css_generated[ 'last_spent' ] . 's</code>' ; ?>
					</p>
				<?php endif ; ?>
				<?php if ( ! empty( $last_critical_css_generated[ 'queue' ] ) ) : ?>
					<div class="litespeed-callout-warning">
						<h4><?php echo __( 'URL list in queue waiting for cron','litespeed-cache' ) ; ?></h4>
						<p>
						<?php foreach ( $last_critical_css_generated[ 'queue' ] as $k => $v ) : ?>
							<?php if ( ! is_array( $v ) ) continue ; ?>
							<?php echo $v[ 'url' ] ; ?>
							<?php if ( $v[ 'is_mobile' ] ) echo ' <span title="mobile">📱</span>' ; ?>
							<br />
						<?php endforeach ; ?>
						</p>
					</p>
					<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_CSS, LiteSpeed_Cache_CSS::TYPE_GENERATE_CRITICAL ) ; ?>" class="litespeed-btn-success">
						<?php echo __( 'Run Queue Manually', 'litespeed-cache' ) ; ?>
					</a>
				<?php endif ; ?>
			</div>
			<?php endif ; ?>
		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left"><?php echo __( 'Separate CCSS Cache Post Types', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_textarea2( LiteSpeed_Cache_Config::ITEM_OPTM_CCSS_SEPARATE_POSTTYPE ) ; ?>
			<div class="litespeed-desc">
				<?php echo __('List post types where each item of that type should have its own CCSS generated.', 'litespeed-cache'); ?>
				<?php echo sprintf( __( 'For example, if every Page on the site has different formatting, enter %s in the box. Separate critical CSS files will be stored for every Page on the site.', 'litespeed-cache' ), '<code>page</code>' ) ; ?>
				<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:optimize#separate_ccss_cache_post_types' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left"><?php echo __( 'Separate CCSS Cache URIs', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_textarea2( LiteSpeed_Cache_Config::ITEM_OPTM_CCSS_SEPARATE_URI ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Separate critical CSS files will be generated for paths containing these strings.', 'litespeed-cache' ) ; ?>
				<?php $this->_uri_usage_example() ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left"><?php echo __( 'Inline CSS Async Lib', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPT_OPTM_CSS_ASYNC_INLINE ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'This will inline the asynchronous CSS library to avoid render blocking.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Load JS Deferred', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_OPTM_JS_DEFER ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Doing so can help reduce resource contention and improve performance.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This can improve your speed score in services like Pingdom, GTmetrix and PageSpeed.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Exclude JQuery', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_OPTM_EXC_JQUERY ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Improve compatibility with inline JS by preventing jQuery optimization. (Recommended Setting: %s)', 'litespeed-cache' ), __( 'ON', 'litespeed-cache' ) ) ; ?>
				<br /><font class="litespeed-warning">
					⚠️
					<?php echo sprintf( __( 'If there is any JS error related to %1$s when enabled %2$s, please turn on this option.', 'litespeed-cache' ), 'jQuery', __( 'JS Combine', 'litespeed-cache' ) ) ; ?>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'DNS Prefetch', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_textarea2( LiteSpeed_Cache_Config::ITEM_DNS_PREFETCH ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Prefetching DNS can reduce latency for visiters.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'For example', 'litespeed-cache' ) ; ?>: <code>//www.example.com</code>
				<?php echo __( 'One per line.', 'litespeed-cache' ) ; ?>
				<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:optimize#dns_prefetch' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Remove Comments', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_OPTM_RM_COMMENT ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Remove the comments inside of JS/CSS files when minifying.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This can improve your speed score in services like Pingdom, GTmetrix and PageSpeed.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

</tbody></table>