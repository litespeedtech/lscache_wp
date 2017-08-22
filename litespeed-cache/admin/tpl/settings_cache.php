<?php
if ( ! defined( 'WPINC' ) ) die ;
?>
<h3 class="litespeed-title"><?php echo __( 'Cache Control Settings', 'litespeed-cache' ) ; ?></h3>

<table class="form-table"><tbody>
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
			require LSWCP_DIR . 'admin/tpl/settings_inc.cache_favicon.php' ;
			require LSWCP_DIR . 'admin/tpl/settings_inc.cache_resources.php' ;
			require LSWCP_DIR . 'admin/tpl/settings_inc.cache_mobile.php' ;
		endif ;
	?>

</tbody></table>

