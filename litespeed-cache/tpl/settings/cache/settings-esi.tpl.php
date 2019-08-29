<?php defined( 'WPINC' ) || exit ; ?>

<h3 class="litespeed-title-short">
	<?php echo __( 'ESI Settings', 'litespeed-cache' ) ; ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:esi', false, 'litespeed-learn-more' ) ; ?>
</h3>

<?php $this->cache_disabled_warning() ; ?>

<div class="litespeed-description">
	<p><?php echo __( 'With ESI (Edge Side Includes), pages may be served from cache for logged-in users.', 'litespeed-cache' ) ; ?></p>
	<p><?php echo __( 'ESI allows you to designate parts of your dynamic page as separate fragments that are then assembled together to make the whole page. In other words, ESI lets you “punch holes” in a page, and then fill those holes with content that may be cached privately, cached publicly with its own TTL, or not cached at all.', 'litespeed-cache' ) ; ?>
		<?php $this->learn_more( 'https://blog.litespeedtech.com/2017/08/30/wpw-private-cache-vs-public-cache/', __( 'WpW: Private Cache vs. Public Cache', 'litespeed-cache' ) ) ; ?>
	</p>
	<p>
		💡:
		<?php echo __( 'You can turn shortcodes into ESI blocks.', 'litespeed-cache' ) ; ?>
		<?php echo sprintf(
			__( 'Replace %1$s with %2$s.', 'litespeed-cache' ),
			'<code>[shortcodeA att1="val1" att2="val2"]</code>',
			'<code>[esi shortcodeA att1="val1" att2="val2"]</code>'
		) ; ?>
		<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:esi:shortcode' ) ; ?>
	</p>
	<p>
		<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:esi_sample', __( 'ESI sample for developers', 'litespeed-cache' ) ) ; ?>
	</p>
</div>

<div class="litespeed-relative">

<?php if ( ! LSWCP_ESI_SUPPORT ) : ?>
	<div class="litespeed-ent-notice">
		<div class="litespeed-ent-notice-desc"><?php echo __( 'Available in LiteSpeed Enterprise version', 'litespeed-cache' ) ; ?></div>
	</div>
<?php endif; ?>

<table class="wp-list-table striped litespeed-table"><tbody>
	<tr>
		<th>
			<?php $id = Const::O_ESI ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Enable caches public pages for logged in users and serves the Admin Bar and Comment Form via ESI blocks. These two blocks will be uncached unless enabled below.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Const::O_ESI_CACHE_ADMBAR ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __(' Cache the build-in Admin Bar ESI block.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Const::O_ESI_CACHE_COMMFORM ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_switch( $id ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Cache the build-in Comment Form ESI block.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Const::O_CACHE_VARY_GROUP ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<table class="litespeed-vary-table wp-list-table striped litespeed-table form-table"><tbody>
			<?php foreach ( $roles as $role => $title ): ?>
				<tr>
					<td class='litespeed-vary-title'><?php echo $title ; ?></td>
					<td class='litespeed-vary-val'>
					<?php
						$this->build_input(
							$id . '[' . $role . ']',
							'litespeed-input-short',
							Vary::get_instance()->in_vary_group( $role )
						) ;
					?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody></table>
			<div class="litespeed-desc">
				<?php echo __( 'If your site contains public content that certain user roles can see but other roles cannot, you can specify a Vary Group for those user roles. For example, specifying an administrator vary group allows there to be a separate publicly-cached page tailored to administrators (with “edit” links, etc), while all other user roles see the default public page.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>

</tbody></table>

</div>