<?php

namespace LiteSpeed;

defined( 'WPINC' ) || exit;
?>

<h3 class="litespeed-title-short">
	<?php echo __( 'HTML Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#html-settings-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table">
	<tbody>
		<tr>
			<th>
				<?php $id = Base::O_OPTM_HTML_MIN; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $id ); ?>
				<div class="litespeed-desc">
					<?php echo __( 'Minify HTML content.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_OPTM_DNS_PREFETCH; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_textarea( $id ); ?>
				<div class="litespeed-desc">
					<?php echo __( 'Prefetching DNS can reduce latency for visitors.', 'litespeed-cache' ); ?>
					<?php echo __( 'For example', 'litespeed-cache' ); ?>: <code>//www.example.com</code>
					<?php Doc::one_per_line(); ?>
					<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#dns-prefetch' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_OPTM_DNS_PREFETCH_CTRL; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $id ); ?>
				<div class="litespeed-desc">
					<?php echo __( 'Automatically enable DNS prefetching for all URLs in the document, including images, CSS, JavaScript, and so forth.', 'litespeed-cache' ); ?>
					<?php echo __( 'This can improve the page loading speed.', 'litespeed-cache' ); ?>
					<?php Doc::learn_more( 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-DNS-Prefetch-Control' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_OPTM_DNS_PRECONNECT; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_textarea( $id ); ?>
				<div class="litespeed-desc">
					<?php echo __( 'Preconnecting speeds up future loads from a given origin.', 'litespeed-cache' ); ?>
					<?php echo __( 'For example', 'litespeed-cache' ); ?>: <code>https://example.com</code>
					<?php Doc::one_per_line(); ?>
					<?php Doc::learn_more( 'https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/rel/preconnect' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_OPTM_HTML_LAZY; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_textarea( $id ); ?>
				<div class="litespeed-desc">
					<?php echo __( 'Delay rendering off-screen HTML elements by its selector.', 'litespeed-cache' ); ?>
					<?php Doc::one_per_line(); ?>
					<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#html-lazyload-selectors' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_OPTM_HTML_SKIP_COMMENTS; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_textarea( $id ); ?>
				<div class="litespeed-desc">
					<?php echo __( 'When minifying HTML do not discard comments that match a specified pattern.', 'litespeed-cache' ); ?>
					<br />
					<?php printf( __( 'If comment to be kept is like: %1$s write: %2$s', 'litespeed-cache' ), '<code>&lt;!-- A comment that needs to be here --&gt;</code>', '<code>A comment that needs to be here</code>' ); ?>
					<br />
					<?php Doc::one_per_line(); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_OPTM_QS_RM; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $id ); ?>
				<div class="litespeed-desc">
					<?php echo __( 'Remove query strings from internal static resources.', 'litespeed-cache' ); ?>
					<br />
					<font class="litespeed-warning">
						⚠️
						<?php echo __( 'Google reCAPTCHA will be bypassed automatically.', 'litespeed-cache' ); ?>
					</font>
					<br />
					<font class="litespeed-success">
						<?php echo __( 'API', 'litespeed-cache' ); ?>:
						<?php printf( __( 'Append query string %s to the resources to bypass this action.', 'litespeed-cache' ), '<code>&_litespeed_rm_qs=0</code>' ); ?>
					</font>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_OPTM_GGFONTS_ASYNC; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $id ); ?>
				<div class="litespeed-desc">
					<?php echo __( 'Use Web Font Loader library to load Google Fonts asynchronously while leaving other CSS intact.', 'litespeed-cache' ); ?>
					<?php echo __( 'This will also add a preconnect to Google Fonts to establish a connection earlier.', 'litespeed-cache' ); ?>
					<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#load-google-fonts-asynchronously' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_OPTM_GGFONTS_RM; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $id ); ?>
				<div class="litespeed-desc">
					<?php echo __( 'Prevent Google Fonts from loading on all pages.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_OPTM_EMOJI_RM; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $id ); ?>
				<div class="litespeed-desc">
					<?php echo __( 'Stop loading WordPress.org emoji. Browser default emoji will be displayed instead.', 'litespeed-cache' ); ?>
					<?php echo __( 'This can improve your speed score in services like Pingdom, GTmetrix and PageSpeed.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $id = Base::O_OPTM_NOSCRIPT_RM; ?>
				<?php $this->title( $id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $id ); ?>
				<div class="litespeed-desc">
					<?php printf( __( 'This option will remove all %s tags from HTML.', 'litespeed-cache' ), '<code>&lt;noscript&gt;</code>' ); ?>
					<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#remove-noscript-tags' ); ?>
				</div>
			</td>
		</tr>

	</tbody>
</table>