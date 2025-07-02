<?php
/**
 * LiteSpeed Cache Control Settings
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;
?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'Cache Control Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cache/' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table">
	<tbody>
		<tr>
			<th>
				<?php $option_id = Base::O_CACHE; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php if ( $this->_is_multisite ) : ?>
					<?php $this->build_switch( $option_id, array( esc_html__( 'OFF', 'litespeed-cache' ), esc_html__( 'ON', 'litespeed-cache' ), esc_html__( 'Use Network Admin Setting', 'litespeed-cache' ) ) ); ?>
				<?php else : ?>
					<?php $this->build_switch( $option_id ); ?>
				<?php endif; ?>
				<div class="litespeed-desc">
					<?php
					printf(
						/* translators: %s: Link tags */
						esc_html__( 'Please visit the %sInformation%s page on how to test the cache.', 'litespeed-cache' ),
						'<a href="https://docs.litespeedtech.com/lscache/lscwp/installation/#testing" target="_blank" rel="noopener">',
						'</a>'
					);
					?>
					<br>
					<strong><?php esc_html_e( 'NOTICE', 'litespeed-cache' ); ?>: </strong><?php esc_html_e( 'When disabling the cache, all cached entries for this site will be purged.', 'litespeed-cache' ); ?>
					<br>
					<?php if ( $this->_is_multisite ) : ?>
						<?php esc_html_e( 'The network admin setting can be overridden here.', 'litespeed-cache' ); ?>
						<br>
					<?php endif; ?>
					<?php if ( ! $this->conf( Base::O_CACHE ) && $this->conf( Base::O_CDN_QUIC ) ) : ?>
						<span class="litespeed-success"><?php esc_html_e( 'With QUIC.cloud CDN enabled, you may still be seeing cache headers from your local server.', 'litespeed-cache' ); ?></span>
					<?php endif; ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_CACHE_PRIV; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php printf( esc_html__( 'Privately cache frontend pages for logged-in users. (LSWS %s required)', 'litespeed-cache' ), 'v5.2.1+' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_CACHE_COMMENTER; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php printf( esc_html__( 'Privately cache commenters that have pending comments. Disabling this option will serve non-cacheable pages to commenters. (LSWS %s required)', 'litespeed-cache' ), 'v5.2.1+' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_CACHE_REST; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Cache requests made by WordPress REST API calls.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_CACHE_PAGE_LOGIN; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Disabling this option may negatively affect performance.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<?php if ( ! $this->_is_multisite ) : ?>
			<?php require LSCWP_DIR . 'tpl/cache/settings_inc.cache_mobile.tpl.php'; ?>
		<?php endif; ?>

		<tr>
			<th>
				<?php $option_id = Base::O_CACHE_PRIV_URI; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_textarea( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'URI Paths containing these strings will NOT be cached as public.', 'litespeed-cache' ); ?>
					<?php $this->_uri_usage_example(); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_CACHE_FORCE_URI; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_textarea( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Paths containing these strings will be cached regardless of no-cacheable settings.', 'litespeed-cache' ); ?>
					<?php $this->_uri_usage_example(); ?>
					<br>
					<?php esc_html_e( 'To define a custom TTL for a URI, add a space followed by the TTL value to the end of the URI.', 'litespeed-cache' ); ?>
					<?php
					printf(
						esc_html__( 'For example, %1$s defines a TTL of %2$s seconds for %3$s.', 'litespeed-cache' ),
						'<code>/mypath/mypage 300</code>',
						300,
						'<code>/mypath/mypage</code>'
					);
					?>
					<?php Doc::one_per_line(); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_CACHE_FORCE_PUB_URI; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_textarea( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Paths containing these strings will be forced to public cached regardless of no-cacheable settings.', 'litespeed-cache' ); ?>
					<?php $this->_uri_usage_example(); ?>
					<br>
					<?php esc_html_e( 'To define a custom TTL for a URI, add a space followed by the TTL value to the end of the URI.', 'litespeed-cache' ); ?>
					<?php
					printf(
						esc_html__( 'For example, %1$s defines a TTL of %2$s seconds for %3$s.', 'litespeed-cache' ),
						'<code>/mypath/mypage 300</code>',
						300,
						'<code>/mypath/mypage</code>'
					);
					?>
					<?php Doc::one_per_line(); ?>
				</div>
			</td>
		</tr>

		<?php if ( ! $this->_is_multisite ) : ?>
			<?php require LSCWP_DIR . 'tpl/cache/settings_inc.cache_dropquery.tpl.php'; ?>
		<?php endif; ?>

	</tbody>
</table>
