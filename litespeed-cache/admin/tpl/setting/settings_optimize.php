<?php
if ( ! defined( 'WPINC' ) ) die ;

?>

<h3 class="litespeed-title-short">
	<?php echo __('Optimization Settings', 'litespeed-cache'); ?>
	<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:optimize" target="_blank" class="litespeed-learn-more"><?php echo __('Learn More', 'litespeed-cache') ; ?></a>
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
		<th><?php echo __( 'Inline CSS Minify', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_CSS_INLINE_MINIFY ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Minify inline CSS code.', 'litespeed-cache' ) ; ?>
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
		<th><?php echo __( 'Inline JS Minify', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_JS_INLINE_MINIFY ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Minify inline JS code.', 'litespeed-cache' ) ; ?>
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
		<th><?php echo __( 'Load CSS Asynchronously', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_OPTM_CSS_ASYNC ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Optimize CSS delivery. This will load Google Fonts asynchronously too.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This can improve your speed score in services like Pingdom, GTmetrix and PageSpeed.', 'litespeed-cache' ) ; ?>
				<br /><font class="litespeed-success">
					<?php echo __('API', 'litespeed-cache'); ?>:
					<?php echo sprintf( __( 'Elements with attribute %s in html code will be excluded.', 'litespeed-cache' ), '<code>data-no-async="1"</code>' ) ; ?>
				</font>
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
					<?php echo __('NOTE', 'litespeed-cache'); ?>:
					<?php echo sprintf( __( 'If there is any JS error related to %1$s when enabled %2$s, please try this option.', 'litespeed-cache' ), 'jQuery', __( 'JS Combine', 'litespeed-cache' ) ) ; ?>
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
				<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:optimize#dns_prefetch" target="_blank"><?php echo __( 'Learn More', 'litespeed-cache' ) ; ?></a>
				<?php echo __( 'One per line.', 'litespeed-cache' ) ; ?>
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