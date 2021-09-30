<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

// CSS::cls()->test_url( '' );
// exit;

$css_summary = CSS::get_summary();
$closest_server = Cloud::get_summary( 'server.' . Cloud::SVC_CCSS );

$ccss_queue = $this->load_queue( 'ccss' );
$ucss_queue = $this->load_queue( 'ucss' );
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
		<th class="litespeed-padding-left">
			<?php $id = Base::O_OPTM_UCSS; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php if ( ! $this->conf( Base::O_API_KEY ) ) : ?>
				<div class="litespeed-callout notice notice-error inline">
					<h4><?php echo __( 'WARNING', 'litespeed-cache' ) ; ?></h4>
					<?php echo Error::msg( 'lack_of_api_key' ); ?>
				</div>
				<?php endif; ?>

				<?php echo __( 'Use QUIC.cloud online service to generate unique CSS.', 'litespeed-cache' ); ?>
				<?php echo __( 'This will drop the unused CSS on each page from the combined file.', 'litespeed-cache' ); ?>
				<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#generate-ucss' ); ?><br />
				<?php echo __( 'Automatic generation of unique CSS is in the background via a cron-based queue.', 'litespeed-cache' ); ?>

				<?php if ( $this->conf( Base::O_OPTM_UCSS ) && ! $this->conf( Base::O_OPTM_CSS_COMB ) ) : ?>
				<br /><font class="litespeed-warning">
					<?php echo sprintf( __( 'This option is bypassed because %1$s option is %2$s.', 'litespeed-cache' ), '<code>' . Lang::title( Base::O_OPTM_CSS_COMB ) . '</code>', '<code>' . __( 'OFF', 'litespeed-cache' ) . '</code>' ); ?>
				</font>
				<?php endif; ?>

			</div>

			<div class="litespeed-desc litespeed-left20">
				<?php if ( $css_summary ) : ?>
					<?php if ( ! empty( $css_summary[ 'last_request_ucss' ] ) ) : ?>
						<p>
							<?php echo __( 'Last generated', 'litespeed-cache' ) . ': <code>' . Utility::readable_time( $css_summary[ 'last_request_ucss' ] ) . '</code>'; ?>
						</p>
						<p>
							<?php echo __( 'Last requested cost', 'litespeed-cache' ) . ': <code>' . $css_summary[ 'last_spent_ucss' ] . 's</code>'; ?>
						</p>
					<?php endif; ?>
				<?php endif; ?>

				<?php if ( $closest_server ) : ?>
					<a href="<?php echo Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_REDETECT_CLOUD, false, null, array( 'svc' => Cloud::SVC_CCSS ) ); ?>" data-balloon-pos="up" data-balloon-break aria-label='<?php echo sprintf( __( 'Current closest Cloud server is %s.&#10; Click to redetect.', 'litespeed-cache' ), $closest_server ); ?>' data-litespeed-cfm="<?php echo __( 'Are you sure you want to redetect the closest cloud server for this service?', 'litespeed-cache' ) ; ?>"><i class='litespeed-quic-icon'></i></a>
				<?php endif; ?>

				<?php if ( ! empty( $ucss_queue ) ) : ?>
					<div class="litespeed-callout notice notice-warning inline">
						<h4>
							<?php echo sprintf( __( 'URL list in %s queue waiting for cron', 'litespeed-cache' ), 'UCSS' ); ?> ( <?php echo count( $ucss_queue ); ?> )
							<a href="<?php echo Utility::build_url( Router::ACTION_CSS, CSS::TYPE_CLEAR_Q_UCSS ); ?>" class="button litespeed-btn-warning litespeed-right">Clear</a>
						</h4>
						<p>
						<?php $i=0; foreach ( $ucss_queue as $k => $v ) : ?>
							<?php if ( $i++ > 20 ) : ?>
								<?php echo '...'; ?>
								<?php break; ?>
							<?php endif; ?>
							<?php if ( ! is_array( $v ) ) continue; ?>
							<?php if ( ! empty( $v[ '_status' ] ) ) : ?><span class="litespeed-success"><?php endif; ?>
							<?php echo $v[ 'url' ]; ?>
							<?php if ( ! empty( $v[ '_status' ] ) ) : ?></span><?php endif; ?>
							<?php if ( $pos = strpos( $k, ' ' ) ) echo ' (' . __( 'Vary Group', 'litespeed-cache' ) . ':' . substr( $k, 0, $pos ) . ')'; ?>
							<?php if ( $v[ 'is_mobile' ] ) echo ' <span data-balloon-pos="up" aria-label="mobile">📱</span>'; ?>
							<?php if ( ! empty( $v[ 'is_webp' ] ) ) echo ' WebP'; ?>
							<br />
						<?php endforeach; ?>
						</p>
					</div>
					<a href="<?php echo Utility::build_url( Router::ACTION_CSS, CSS::TYPE_GEN_UCSS ); ?>" class="button litespeed-btn-success">
						<?php echo sprintf( __( 'Run %s Queue Manually', 'litespeed-cache' ), 'UCSS' ); ?>
					</a>
				<?php endif; ?>
			</div>

		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left">
			<?php $id = Base::O_OPTM_UCSS_INLINE; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Inline UCSS to reduce the extra CSS file loading. This option will not be automatically turned on for %1$s pages. To use it on %1$s pages, please set it to ON.', 'litespeed-cache' ), '<code>' . Lang::title( Base::O_GUEST ) . '</code>' ); ?>
				<br /><font class="litespeed-info">
					<?php echo sprintf( __( 'This option will automatically bypass %s option.', 'litespeed-cache' ), '<code>' . Lang::title( Base::O_OPTM_CSS_ASYNC ) . '</code>' ); ?>
				</font>
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

	<tr>
		<th>
			<?php $id = Base::O_OPTM_CSS_ASYNC; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php if ( ! $this->conf( Base::O_API_KEY ) ) : ?>
				<div class="litespeed-callout notice notice-error inline">
					<h4><?php echo __( 'WARNING', 'litespeed-cache' ) ; ?></h4>
					<?php echo Error::msg( 'lack_of_api_key' ); ?>
				</div>
				<?php endif; ?>
				<?php echo __( 'Optimize CSS delivery.', 'litespeed-cache' ); ?>
				<?php echo __( 'This can improve your speed score in services like Pingdom, GTmetrix and PageSpeed.', 'litespeed-cache' ); ?><br />
				<?php echo __( 'Use QUIC.cloud online service to generate critical CSS and load remaining CSS asynchronously.', 'litespeed-cache' ); ?>
				<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/pageopt/#load-css-asynchronously' ); ?><br />
				<?php echo __( 'Automatic generation of critical CSS is in the background via a cron-based queue.', 'litespeed-cache' ); ?><br />
				<?php echo sprintf( __( 'When this option is turned %s, it will also load Google Fonts asynchronously.', 'litespeed-cache' ), '<code>' . __( 'ON', 'litespeed-cache' ) . '</code>' ); ?>
				<br /><font class="litespeed-success">
					<?php echo __( 'API', 'litespeed-cache' ); ?>:
					<?php echo sprintf( __( 'Elements with attribute %s in HTML code will be excluded.', 'litespeed-cache' ), '<code>data-no-async="1"</code>' ); ?>
				</font>

				<?php if ( $this->conf( Base::O_OPTM_CSS_ASYNC ) && $this->conf( Base::O_OPTM_CSS_COMB ) && $this->conf( Base::O_OPTM_UCSS ) && $this->conf( Base::O_OPTM_UCSS_INLINE ) ) : ?>
				<br /><font class="litespeed-warning">
					<?php echo sprintf( __( 'This option is bypassed due to %s option.', 'litespeed-cache' ), '<code>' . Lang::title( Base::O_OPTM_UCSS_INLINE ) . '</code>' ); ?>
				</font>
				<?php endif; ?>

			</div>

			<div class="litespeed-desc litespeed-left20">
				<?php if ( $css_summary ) : ?>
					<?php if ( ! empty( $css_summary[ 'last_request_ccss' ] ) ) : ?>
						<p>
							<?php echo __( 'Last generated', 'litespeed-cache' ) . ': <code>' . Utility::readable_time( $css_summary[ 'last_request_ccss' ] ) . '</code>'; ?>
						</p>
						<p>
							<?php echo __( 'Last requested cost', 'litespeed-cache' ) . ': <code>' . $css_summary[ 'last_spent_ccss' ] . 's</code>'; ?>
						</p>
					<?php endif; ?>
				<?php endif; ?>

				<?php if ( $closest_server ) : ?>
					<a href="<?php echo Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_REDETECT_CLOUD, false, null, array( 'svc' => Cloud::SVC_CCSS ) ); ?>" data-balloon-pos="up" data-balloon-break aria-label='<?php echo sprintf( __( 'Current closest Cloud server is %s.&#10; Click to redetect.', 'litespeed-cache' ), $closest_server ); ?>' data-litespeed-cfm="<?php echo __( 'Are you sure you want to redetect the closest cloud server for this service?', 'litespeed-cache' ) ; ?>"><i class='litespeed-quic-icon'></i></a>
				<?php endif; ?>

				<?php if ( ! empty( $ccss_queue ) ) : ?>
					<div class="litespeed-callout notice notice-warning inline">
						<h4>
							<?php echo sprintf( __( 'URL list in %s queue waiting for cron', 'litespeed-cache' ), 'CCSS' ); ?> ( <?php echo count( $ccss_queue ); ?> )
							<a href="<?php echo Utility::build_url( Router::ACTION_CSS, CSS::TYPE_CLEAR_Q_CCSS ); ?>" class="button litespeed-btn-warning litespeed-right">Clear</a>
						</h4>
						<p>
						<?php $i=0; foreach ( $ccss_queue as $k => $v ) : ?>
							<?php if ( $i++ > 20 ) : ?>
								<?php echo '...'; ?>
								<?php break; ?>
							<?php endif; ?>
							<?php if ( ! is_array( $v ) ) continue; ?>
							<?php if ( ! empty( $v[ '_status' ] ) ) : ?><span class="litespeed-success"><?php endif; ?>
							<?php echo $v[ 'url' ]; ?>
							<?php if ( ! empty( $v[ '_status' ] ) ) : ?></span><?php endif; ?>
							<?php if ( $pos = strpos( $k, ' ' ) ) echo ' (' . __( 'Vary Group', 'litespeed-cache' ) . ':' . substr( $k, 0, $pos ) . ')'; ?>
							<?php if ( $v[ 'is_mobile' ] ) echo ' <span data-balloon-pos="up" aria-label="mobile">📱</span>'; ?>
							<?php if ( ! empty( $v[ 'is_webp' ] ) ) echo ' WebP'; ?>
							<br />
						<?php endforeach; ?>
						</p>
					</div>
					<a href="<?php echo Utility::build_url( Router::ACTION_CSS, CSS::TYPE_GEN_CCSS ); ?>" class="button litespeed-btn-success">
						<?php echo sprintf( __( 'Run %s Queue Manually', 'litespeed-cache' ), 'CCSS' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left">
			<?php $id = Base::O_OPTM_CCSS_PER_URL; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Disable this option to generate CCSS per Post Type instead of per page. This can save significant CCSS quota, however it may result in incorrect CSS styling if your site uses a page builder.', 'litespeed-cache' ); ?>
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
			<?php $this->build_switch( $id, array( __( 'Default', 'litespeed-cache' ), 'Swap' ) ); ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Set this to append %1$s to all %2$s rules before caching CSS to specify how fonts should be displayed while being downloaded.', 'litespeed-cache' ), '<code>font-display</code>', '<code>@font-face</code>' ); ?>
				<br /><?php echo sprintf( __( '%s is recommended.', 'litespeed-cache' ), '<code>' . __( 'Swap', 'litespeed-cache' ) . '</code>' ); ?>
			</div>
		</td>
	</tr>

</tbody></table>
