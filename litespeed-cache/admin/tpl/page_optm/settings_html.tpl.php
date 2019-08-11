<?php defined( 'WPINC' ) || exit ; ?>

<h3 class="litespeed-title-short">
	<?php echo __( 'Optimization Settings', 'litespeed-cache' ) ; ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:optimize', false, 'litespeed-learn-more' ) ; ?>
</h3>

<?php if ( LiteSpeed_Cache_Optimize::need_db() && ! LiteSpeed_Cache_Data::tb_cssjs_exist() ) : ?>
<div class="litespeed-callout notice notice-error inline">
	<h4><?php echo __( 'WARNING', 'litespeed-cache' ) ; ?></h4>
	<p><?php echo sprintf( __( 'Failed to create Optimizer table. Please follow <a %s>Table Creation guidance from LiteSpeed Wiki</a> to finish setup.', 'litespeed-cache' ), 'href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:installation" target="_blank"' ) ; ?></p>
</div>
<?php endif; ?>

<div class="litespeed-callout notice notice-warning inline">
	<h4><?php echo __( 'NOTICE', 'litespeed-cache' ) ; ?></h4>
	<p><?php echo __( 'Please test thoroughly when enabling any option in this list. After changing Minify/Combine settings, please do a Purge All action.', 'litespeed-cache' ) ; ?></p>
</div>

<table class="wp-list-table striped form-table"><tbody>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_OPTM_TTL ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_input( $id ) ; ?> <?php $this->readable_seconds() ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Specify how long, in seconds, CSS/JS files are cached.', 'litespeed-cache' ) ; ?>
				<?php $this->recommended( $id ) ; ?>
				<?php $this->_validate_ttl( $id, 3600 ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_OPTM_HTML_MIN ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Minify HTML content.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left">
			<?php $id = LiteSpeed_Cache_Config::O_OPTM_CSS_INLINE_MIN ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Minify inline CSS code.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left">
			<?php $id = LiteSpeed_Cache_Config::O_OPTM_JS_INLINE_MIN ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Minify inline JS code.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_OPTM_DNS_PREFETCH ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Prefetching DNS can reduce latency for visiters.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'For example', 'litespeed-cache' ) ; ?>: <code>//www.example.com</code>
				<?php LiteSpeed_Cache_Doc::one_per_line() ; ?>
				<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:optimize#dns_prefetch' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_OPTM_RM_COMMENT ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Remove the comments inside of JS/CSS files when minifying.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This can improve your speed score in services like Pingdom, GTmetrix and PageSpeed.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_OPTM_QS_RM ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Remove query strings from static resources.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This can improve your speed score in services like Pingdom, GTmetrix and PageSpeed.', 'litespeed-cache' ) ; ?>
				<br /><font class="litespeed-warning">
					⚠️
					<?php echo __( 'Google reCAPTCHA will be bypassed automatically.', 'litespeed-cache' ) ; ?>
				</font>
				<br /><font class="litespeed-success">
					<?php echo __( 'API', 'litespeed-cache' ) ; ?>:
					<?php echo sprintf( __( 'Append query string %s to the resources to bypass this action.', 'litespeed-cache' ), '<code>&_litespeed_rm_qs=0</code>' ) ; ?>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_OPTM_GGFONTS_ASYNC ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Use Web Font Loader library to load Google Fonts asynchronously while leave other CSS intact.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This will also add a preconnect to Google for faster Google Fonts downloading.', 'litespeed-cache' ) ; ?>
				<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:tuning:google-fonts' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_OPTM_GGFONTS_RM ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Prevent google fonts from loading on all your pages.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_OPTM_EMOJI_RM ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Stop loading wordpress.org emoji. Browser default emoji will be displayed instead.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'This can improve your speed score in services like Pingdom, GTmetrix and PageSpeed.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

</tbody></table>