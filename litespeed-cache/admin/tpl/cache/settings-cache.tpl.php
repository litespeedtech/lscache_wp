<?php defined( 'WPINC' ) || exit ; ?>

<h3 class="litespeed-title-short">
	<?php echo __( 'Cache Control Settings', 'litespeed-cache' ) ; ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:cache', false, 'litespeed-learn-more' ) ; ?>
</h3>

<?php $this->cache_disabled_warning() ; ?>

<table><tbody>
	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_CACHE_PRIV ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Privately cache frontend pages for logged-in users. (LSWS %s required)', 'litespeed-cache' ), 'v5.2.1+' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_CACHE_COMMENTER ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Privately cache commenters that have pending comments. Disabling this option will serve non-cacheable pages to commenters. (LSWS %s required)', 'litespeed-cache' ), 'v5.2.1+' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_CACHE_REST ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Cache requests made by WordPress REST API calls.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_CACHE_PAGE_LOGIN ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Disabling this option may negatively affect performance.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<?php
		if ( ! is_multisite() ) :
			require LSCWP_DIR . 'admin/tpl/cache/settings_inc.cache_favicon.tpl.php' ;
			require LSCWP_DIR . 'admin/tpl/cache/settings_inc.cache_resources.tpl.php' ;
			require LSCWP_DIR . 'admin/tpl/cache/settings_inc.cache_mobile.tpl.php' ;
		endif ;
	?>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_CACHE_PRIV_URI ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'URI Paths containing these strings will NOT be cached as public.', 'litespeed-cache' ) ; ?>
				<?php $this->_uri_usage_example() ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_CACHE_FORCE_URI ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_textarea( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Paths containing these strings will be cached regardless of no-cacheable settings.', 'litespeed-cache' ) ; ?>
				<?php $this->_uri_usage_example() ; ?>
				<br /><?php echo __( 'To define a custom TTL for a URI, add a space followed by the TTL value to the end of the URI.', 'litespeed-cache' ) ; ?>
				<?php echo sprintf( __( 'For example, %1$s defines a TTL of %2$s seconds for %3$s.', 'litespeed-cache' ), '<code>/mypath/mypage 300</code>', 300, '<code>/mypath/mypage</code>' ) ; ?>
				<?php LiteSpeed_Cache_Doc::one_per_line() ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Cache_Config::O_CACHE_DROP_QS ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_textarea( $id, 40 ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Ignore certain query strings when caching.', 'litespeed-cache' ) ; ?>
				<?php echo sprintf( __( 'For example, to drop parameters beginning with %s, %s can be used here.', 'litespeed-cache' ), '<code>utm</code>', '<code>utm*</code>' ) ; ?>
				<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:drop_query_string' ) ; ?>

				<br />
				<?php LiteSpeed_Cache_Doc::one_per_line() ; ?>
			</div>
		</td>
	</tr>

</tbody></table>

