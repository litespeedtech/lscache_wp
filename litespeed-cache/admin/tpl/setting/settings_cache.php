<?php
if ( ! defined( 'WPINC' ) ) die ;
?>
<h3 class="litespeed-title"><?php echo __( 'Cache Control Settings', 'litespeed-cache' ) ; ?></h3>

<table><tbody>
	<tr>
		<th><?php echo __( 'Cache Logged-in Users', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_CACHE_PRIV ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Privately cache frontend pages for logged-in users. (LSWS %s required)', 'litespeed-cache' ), 'v5.2.1+' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Cache Commenters', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_CACHE_COMMENTER ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Privately cache commenters that have pending comments. Disabling this option will serve non-cacheable pages to commenters. (LSWS %s required)', 'litespeed-cache' ), 'v5.2.1+' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Cache REST API', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_CACHE_REST ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Cache requests made by WordPress REST API calls.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Cache Login Page', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::OPID_CACHE_PAGE_LOGIN ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Disabling this option may negatively affect performance.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<?php
		if ( ! is_multisite() ) :
			require LSWCP_DIR . 'admin/tpl/setting/settings_inc.cache_favicon.php' ;
			require LSWCP_DIR . 'admin/tpl/setting/settings_inc.cache_resources.php' ;
			require LSWCP_DIR . 'admin/tpl/setting/settings_inc.cache_mobile.php' ;
		endif ;
	?>

	<tr>
		<th><?php echo __( 'Private Cached URIs', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_textarea(LiteSpeed_Cache_Config::OPID_CACHE_URI_PRIV); ?>
			<div class="litespeed-desc">
				<?php echo __('URI Paths containing these strings will NOT be cached as public.', 'litespeed-cache'); ?>
				<?php echo __('The URLs will be compared to the REQUEST_URI server variable.', 'litespeed-cache'); ?>
				<?php echo sprintf( __( 'For example, for %s, %s can be used here.', 'litespeed-cache' ), '<code>/mypath/mypage?aa=bb</code>', '<code>mypage?aa=</code>' ) ; ?>
				<br />
				<i>
					<?php echo sprintf( __( 'To match the beginning, add %s to the beginning of the item.', 'litespeed-cache' ), '<code>^</code>' ) ; ?>
					<?php echo sprintf( __( 'To do an exact match, add %s to the end of the URL.', 'litespeed-cache' ), '<code>$</code>' ) ; ?>
					<?php echo __('One per line.', 'litespeed-cache'); ?>
				</i>
			</div>
		</td>
	</tr>

	<?php
		if ( ! is_multisite() ) :
			require LSWCP_DIR . 'admin/tpl/setting/settings_inc.cache_browser.php' ;
		endif ;
	?>

</tbody></table>

