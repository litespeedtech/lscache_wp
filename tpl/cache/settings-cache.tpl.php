<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;
?>

<h3 class="litespeed-title-short">
	<?php echo __( 'Cache Control Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cache/' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>
	<tr>
		<th>
			<?php $id = Base::O_CACHE; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php if ( $this->_is_multisite ) : ?>
				<?php $this->build_switch( $id, array( __( 'OFF', 'litespeed-cache' ), __( 'ON', 'litespeed-cache' ), __( 'Use Network Admin Setting', 'litespeed-cache' ) ) ); ?>
			<?php else : ?>
				<?php $this->build_switch( $id ); ?>
			<?php endif; ?>
			<div class="litespeed-desc">
				<?php echo sprintf(__('Please visit the <a %s>Information</a> page on how to test the cache.', 'litespeed-cache'),
					'href="https://docs.litespeedtech.com/lscache/lscwp/installation/#testing" target="_blank"'); ?>

				<strong><?php echo __('NOTICE', 'litespeed-cache'); ?>: </strong><?php echo __('When disabling the cache, all cached entries for this site will be purged.', 'litespeed-cache'); ?>

				<?php if ( $this->_is_multisite ): ?>
				<br><?php echo __('The network admin setting can be overridden here.', 'litespeed-cache'); ?>
				<?php endif; ?>

				<?php if ( ! Conf::val( Base::O_CACHE ) && Conf::val( Base::O_CDN_QUIC ) ): ?>
				<br><font class="litespeed-success"><?php echo __( 'With QUIC.cloud CDN enabled, you may still be seeing cache headers from your local server.', 'litespeed-cache' ); ?></font>
				<?php endif; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_CACHE_PRIV; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Privately cache frontend pages for logged-in users. (LSWS %s required)', 'litespeed-cache' ), 'v5.2.1+' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_CACHE_COMMENTER; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Privately cache commenters that have pending comments. Disabling this option will serve non-cacheable pages to commenters. (LSWS %s required)', 'litespeed-cache' ), 'v5.2.1+' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_CACHE_REST; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Cache requests made by WordPress REST API calls.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_CACHE_PAGE_LOGIN; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Disabling this option may negatively affect performance.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<?php
		if ( ! $this->_is_multisite ) :
			require LSCWP_DIR . 'tpl/cache/settings_inc.cache_favicon.tpl.php';
			require LSCWP_DIR . 'tpl/cache/settings_inc.cache_resources.tpl.php';
			require LSCWP_DIR . 'tpl/cache/settings_inc.cache_mobile.tpl.php';
		endif;
	?>

	<tr>
		<th>
			<?php $id = Base::O_CACHE_PRIV_URI; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'URI Paths containing these strings will NOT be cached as public.', 'litespeed-cache' ); ?>
				<?php $this->_uri_usage_example(); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_CACHE_FORCE_URI; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Paths containing these strings will be cached regardless of no-cacheable settings.', 'litespeed-cache' ); ?>
				<?php $this->_uri_usage_example(); ?>
				<br /><?php echo __( 'To define a custom TTL for a URI, add a space followed by the TTL value to the end of the URI.', 'litespeed-cache' ); ?>
				<?php echo sprintf( __( 'For example, %1$s defines a TTL of %2$s seconds for %3$s.', 'litespeed-cache' ), '<code>/mypath/mypage 300</code>', 300, '<code>/mypath/mypage</code>' ); ?>
				<?php Doc::one_per_line(); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_CACHE_FORCE_PUB_URI; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Paths containing these strings will be forced to public cached regardless of no-cacheable settings.', 'litespeed-cache' ); ?>
				<?php $this->_uri_usage_example(); ?>
				<br /><?php echo __( 'To define a custom TTL for a URI, add a space followed by the TTL value to the end of the URI.', 'litespeed-cache' ); ?>
				<?php echo sprintf( __( 'For example, %1$s defines a TTL of %2$s seconds for %3$s.', 'litespeed-cache' ), '<code>/mypath/mypage 300</code>', 300, '<code>/mypath/mypage</code>' ); ?>
				<?php Doc::one_per_line(); ?>
			</div>
		</td>
	</tr>

	<?php
		if ( ! $this->_is_multisite ) :
			require LSCWP_DIR . 'tpl/cache/settings_inc.cache_dropquery.tpl.php';
		endif;
	?>

</tbody></table>

