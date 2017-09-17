<?php
if ( ! defined( 'WPINC' ) ) die ;


?>

<h3 class="litespeed-title"><?php echo __( 'Optimization Settings', 'litespeed-cache' ) ; ?></h3>

<div class="litespeed-callout-warning">
	<h4><?php echo __( 'NOTICE:', 'litespeed-cache' ) ; ?></h4>
	<?php echo __( 'Please test thoroughly when enabling any option in this list. After changing Minify/Combine settings, please do a Purge All action.', 'litespeed-cache' ) ; ?>
</div>


<table class="litespeed-form-table"><tbody>
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
		<th><?php echo __( 'CSS Excludes', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_textarea(LiteSpeed_Cache_Config::OPID_CSS_EXCLUDES); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Listed CSS files will not be minified/combined. The full URL or a partial string can be used.', 'litespeed-cache' ) ; ?>
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
		<th><?php echo __( 'JS Excludes', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_textarea(LiteSpeed_Cache_Config::OPID_JS_EXCLUDES); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Listed JS files will not be minified/combined. The full URL or a partial string can be used.', 'litespeed-cache' ) ; ?>
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

</tbody></table>