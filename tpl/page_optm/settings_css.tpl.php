<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

// CSS::get_instance()->test_url( '' );
// exit;

$css_summary = CSS::get_summary();
$closest_server = Cloud::get_summary( 'server.' . Cloud::SVC_CCSS );

?>

<h3 class="litespeed-title-short">
	<?php echo __( 'CSS Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_CSS_MIN; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Minify CSS files and inline CSS code.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_CSS_COMB; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Combine CSS files and inline CSS code.', 'litespeed-cache' ); ?>
				<a href="https://docs.litespeedtech.com/lscache/lscwp/ts-optimize/" target="_blank"><?php echo __( 'How to Fix Problems Caused by CSS/JS Optimization.', 'litespeed-cache' ); ?></a>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_CSS_COMB_EXT_INL; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Include external CSS and inline CSS in combined file when %1$s is also enabled. This option helps maintain the priorities of CSS, which should minimize potential errors caused by CSS Combine.', 'litespeed-cache' ), '<code>' . Lang::title( Base::O_OPTM_CSS_COMB ) . '</code>' ); ?>
			</div>
		</td>
	</tr>

	<tr class="litespeed-hide">
		<th class="litespeed-padding-left">
			<?php $id = Base::O_OPTM_UCSS; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Use QUIC.cloud online service to generate unique CSS.', 'litespeed-cache' ); ?>
				<?php echo __( 'This will drop the unused CSS on each page.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr class="litespeed-hide">
		<th class="litespeed-padding-left">
			<?php $id = Base::O_OPTM_UCSS_ASYNC; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Generate unique CSS in the background via a cron-based queue.', 'litespeed-cache' ); ?>
				<?php echo sprintf( __( 'If set to %s this is done in the foreground, which may slow down page load.', 'litespeed-cache' ), '<code>' . __('OFF', 'litespeed-cache') . '</code>' ); ?>
			</div>

			<?php if ( $css_summary ) : ?>
			<div class="litespeed-desc litespeed-left20">
				<?php if ( ! empty( $css_summary[ 'last_request_ucss' ] ) ) : ?>
					<p>
						<?php echo __( 'Last generated', 'litespeed-cache' ) . ': <code>' . Utility::readable_time( $css_summary[ 'last_request_ucss' ] ) . '</code>'; ?>
					</p>
					<p>
						<?php echo __( 'Last requested cost', 'litespeed-cache' ) . ': <code>' . $css_summary[ 'last_spent_ucss' ] . 's</code>'; ?>
					</p>
				<?php endif; ?>

				<?php if ( $closest_server ) : ?>
					<a href="<?php echo Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_REDETECT_CLOUD, false, null, array( 'svc' => Cloud::SVC_CCSS ) ); ?>" data-balloon-pos="up" data-balloon-break aria-label='<?php echo sprintf( __( 'Current closest Cloud server is %s.&#10; Click to redetect.', 'litespeed-cache' ), $closest_server ); ?>' data-litespeed-cfm="<?php echo __( 'Are you sure you want to redetect the closest cloud server for this service?', 'litespeed-cache' ) ; ?>"><i class='litespeed-quic-icon'></i></a>
				<?php endif; ?>

			</div>
			<?php endif; ?>

		</td>
	</tr>

	<tr class="litespeed-hide">
		<th class="litespeed-padding-left">
			<?php $id = Base::O_OPTM_UCSS_WHITELIST; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'List the CSS selector that its style should be always contained in UCSS.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_CSS_HTTP2; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Pre-send internal CSS files to the browser before they are requested. (Requires the HTTP/2 protocol)', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_CSS_ASYNC; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php if ( ! Conf::val( Base::O_API_KEY ) ) : ?>
				<div class="litespeed-callout notice notice-error inline">
					<h4><?php echo __( 'WARNING', 'litespeed-cache' ) ; ?></h4>
					<?php echo Error::msg( 'lack_of_api_key' ); ?>
				</div>
				<?php endif; ?>
				<?php echo __( 'Optimize CSS delivery.', 'litespeed-cache' ); ?>
				<?php echo __( 'This can improve your speed score in services like Pingdom, GTmetrix and PageSpeed.', 'litespeed-cache' ); ?><br />
				<?php echo sprintf( __( 'When this option is turned %s, it will also load Google Fonts asynchronously.', 'litespeed-cache' ), '<code>' . __( 'ON', 'litespeed-cache' ) . '</code>' ); ?>
				<br /><font class="litespeed-success">
					<?php echo __( 'API', 'litespeed-cache' ); ?>:
					<?php echo sprintf( __( 'Elements with attribute %s in html code will be excluded.', 'litespeed-cache' ), '<code>data-no-async="1"</code>' ); ?>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left">
			<?php $id = Base::O_OPTM_CCSS_GEN; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Leave this option %1$s to allow communication with the QUIC.cloud CCSS service. If set to %2$s, Critical CSS will not be generated.', 'litespeed-cache' ), '<code>' . __( 'ON', 'litespeed-cache' ) . '</code>', '<code>' . __( 'OFF', 'litespeed-cache' ) . '</code>' ); ?><br />
				<?php echo sprintf( __( 'This option only works if %1$s is %2$s.', 'litespeed-cache' ), '<code>' . __( 'Load CSS Asynchronously', 'litespeed-cache' ) . '</code>', '<code>' . __( 'ON', 'litespeed-cache' ) . '</code>' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left">
			<?php $id = Base::O_OPTM_CCSS_ASYNC; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Automatically generate critical CSS in the background via a cron-based queue.', 'litespeed-cache' ); ?>
				<?php echo sprintf( __( 'If set to %s this is done in the foreground, which may slow down page load.', 'litespeed-cache' ), '<code>' . __('OFF', 'litespeed-cache') . '</code>' ); ?>
				<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#generate-critical-css-in-the-background' ); ?>
			</div>

			<?php if ( $css_summary ) : ?>
			<div class="litespeed-desc litespeed-left20">
				<?php if ( ! empty( $css_summary[ 'last_request' ] ) ) : ?>
					<p>
						<?php echo __( 'Last generated', 'litespeed-cache' ) . ': <code>' . Utility::readable_time( $css_summary[ 'last_request' ] ) . '</code>'; ?>
					</p>
					<p>
						<?php echo __( 'Last requested cost', 'litespeed-cache' ) . ': <code>' . $css_summary[ 'last_spent' ] . 's</code>'; ?>
					</p>
				<?php endif; ?>

				<?php if ( $closest_server ) : ?>
					<a href="<?php echo Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_REDETECT_CLOUD, false, null, array( 'svc' => Cloud::SVC_CCSS ) ); ?>" data-balloon-pos="up" data-balloon-break aria-label='<?php echo sprintf( __( 'Current closest Cloud server is %s.&#10; Click to redetect.', 'litespeed-cache' ), $closest_server ); ?>' data-litespeed-cfm="<?php echo __( 'Are you sure you want to redetect the closest cloud server for this service?', 'litespeed-cache' ) ; ?>"><i class='litespeed-quic-icon'></i></a>
				<?php endif; ?>

				<?php if ( ! empty( $css_summary[ 'queue' ] ) ) : ?>
					<div class="litespeed-callout notice notice-warning inline">
						<h4>
							<?php echo __( 'URL list in queue waiting for cron','litespeed-cache' ); ?>
							<a href="<?php echo Utility::build_url( Router::ACTION_CSS, CSS::TYPE_CLEAR_Q ); ?>" class="button litespeed-btn-warning litespeed-right">Clear</a>
						</h4>
						<p>
						<?php foreach ( $css_summary[ 'queue' ] as $k => $v ) : ?>
							<?php if ( ! is_array( $v ) ) continue; ?>
							<?php echo $v[ 'url' ]; ?>
							<?php if ( $v[ 'is_mobile' ] ) echo ' <span data-balloon-pos="up" aria-label="mobile">📱</span>'; ?>
							<br />
						<?php endforeach; ?>
						</p>
					</div>
					<a href="<?php echo Utility::build_url( Router::ACTION_CSS, CSS::TYPE_GENERATE_CRITICAL ); ?>" class="button litespeed-btn-success">
						<?php echo __( 'Run Queue Manually', 'litespeed-cache' ); ?>
					</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left">
			<?php $id = Base::O_OPTM_CCSS_SEP_POSTTYPE; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __('List post types where each item of that type should have its own CCSS generated.', 'litespeed-cache'); ?>
				<?php echo sprintf( __( 'For example, if every Page on the site has different formatting, enter %s in the box. Separate critical CSS files will be stored for every Page on the site.', 'litespeed-cache' ), '<code>page</code>' ); ?>
				<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#separate-ccss-cache-post-types' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left">
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
		<th class="litespeed-padding-left">
			<?php $id = Base::O_OPTM_CSS_ASYNC_INLINE; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'This will inline the asynchronous CSS library to avoid render blocking.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_OPTM_CSS_FONT_DISPLAY; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id, array( __( 'Default', 'litespeed-cache' ), 'Block', 'Swap', 'Fallback', 'Optional' ) ); ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Set this to append %1$s to all %2$s rules before caching CSS to specify how fonts should be displayed while being downloaded.', 'litespeed-cache' ), '<code>font-display</code>', '<code>@font-face</code>' ); ?>
				<br /><?php echo sprintf( __( '%s is recommended.', 'litespeed-cache' ), '<code>' . __( 'Swap', 'litespeed-cache' ) . '</code>' ); ?>
			</div>
		</td>
	</tr>

</tbody></table>
