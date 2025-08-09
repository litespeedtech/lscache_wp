<?php
/**
 * LiteSpeed Cache ESI Settings
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;
?>

<h3 class="litespeed-title-short">
	<?php echo esc_html__( 'ESI Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cache/#esi-tab' ); ?>
</h3>

<div class="litespeed-description">
	<p><?php echo esc_html__( 'With ESI (Edge Side Includes), pages may be served from cache for logged-in users.', 'litespeed-cache' ); ?></p>
	<p><?php echo esc_html__( 'ESI allows you to designate parts of your dynamic page as separate fragments that are then assembled together to make the whole page. In other words, ESI lets you “punch holes” in a page, and then fill those holes with content that may be cached privately, cached publicly with its own TTL, or not cached at all.', 'litespeed-cache' ); ?>
		<?php Doc::learn_more( 'https://blog.litespeedtech.com/2017/08/30/wpw-private-cache-vs-public-cache/', esc_html__( 'WpW: Private Cache vs. Public Cache', 'litespeed-cache' ) ); ?>
	</p>
	<p>
		💡:
		<?php echo esc_html__( 'You can turn shortcodes into ESI blocks.', 'litespeed-cache' ); ?>
		<?php
		printf(
			esc_html__( 'Replace %1$s with %2$s.', 'litespeed-cache' ),
			'<code>[shortcodeA att1="val1" att2="val2"]</code>',
			'<code>[esi shortcodeA att1="val1" att2="val2"]</code>'
		);
		?>
		<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/admin/#turning-wordpress-shortcodes-into-esi-blocks' ); ?>
	</p>
	<p>
		<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/api/#generate-esi-block-url', esc_html__( 'ESI sample for developers', 'litespeed-cache' ) ); ?>
	</p>
</div>

<div class="litespeed-relative">

<?php if ( ! LSWCP_ESI_SUPPORT && ! $this->conf( Base::O_CDN_QUIC ) ) : ?>
	<div class="litespeed-callout-danger">
		<h4><?php echo esc_html__( 'WARNING', 'litespeed-cache' ); ?></h4>
		<h4><?php echo esc_html__( 'These options are only available with LiteSpeed Enterprise Web Server or QUIC.cloud CDN.', 'litespeed-cache' ); ?></h4>
	</div>
<?php endif; ?>

<table class="wp-list-table striped litespeed-table"><tbody>
	<tr>
		<th>
			<?php $option_id = Base::O_ESI; ?>
			<?php $this->title( $option_id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $option_id ); ?>
			<div class="litespeed-desc">
				<?php echo esc_html__( 'Turn ON to cache public pages for logged in users, and serve the Admin Bar and Comment Form via ESI blocks. These two blocks will be uncached unless enabled below.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $option_id = Base::O_ESI_CACHE_ADMBAR; ?>
			<?php $this->title( $option_id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $option_id ); ?>
			<div class="litespeed-desc">
				<?php echo esc_html__( 'Cache the built-in Admin Bar ESI block.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $option_id = Base::O_ESI_CACHE_COMMFORM; ?>
			<?php $this->title( $option_id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $option_id ); ?>
			<div class="litespeed-desc">
				<?php echo esc_html__( 'Cache the built-in Comment Form ESI block.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $option_id = Base::O_ESI_NONCE; ?>
			<?php $this->title( $option_id ); ?>
		</th>
		<td>
			<div>
				<?php $this->build_textarea( $option_id ); ?>
			</div>
			<p class="litespeed-desc">
				<?php echo esc_html__( 'The list will be merged with the predefined nonces in your local data file.', 'litespeed-cache' ); ?>
				<?php echo esc_html__( 'The latest data file is', 'litespeed-cache' ); ?>: <a href="https://github.com/litespeedtech/lscache_wp/blob/master/data/esi.nonces.txt" target="_blank">https://github.com/litespeedtech/lscache_wp/blob/master/data/esi.nonces.txt</a>
				<br><span class="litespeed-success">
					<?php echo esc_html__( 'API', 'litespeed-cache' ); ?>:
					<?php printf( esc_html__( 'Filter %s is supported.', 'litespeed-cache' ), '<code>litespeed_esi_nonces</code>' ); ?>
				</span>
			</p>
			<div class="litespeed-desc">
				<?php echo esc_html__( 'The above nonces will be converted to ESI automatically.', 'litespeed-cache' ); ?>
				<?php Doc::one_per_line(); ?>
				<br><?php echo esc_html__( 'An optional second parameter may be used to specify cache control. Use a space to separate', 'litespeed-cache' ); ?>: <code>my_nonce_action private</code>
			</div>
			<div class="litespeed-desc">
				<?php printf( esc_html__( 'Wildcard %1$s supported (match zero or more characters). For example, to match %2$s and %3$s, use %4$s.', 'litespeed-cache' ), '<code>*</code>', '<code>nonce_formid_1</code>', '<code>nonce_formid_3</code>', '<code>nonce_formid_*</code>' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $option_id = Base::O_CACHE_VARY_GROUP; ?>
			<?php $this->title( $option_id ); ?>
		</th>
		<td>
			<table class="litespeed-vary-table wp-list-table striped litespeed-table form-table"><tbody>
			<?php foreach ( $roles as $curr_role => $curr_title ) : ?>
				<tr>
					<td class="litespeed-vary-title"><?php echo esc_html( $curr_title ); ?></td>
					<td class="litespeed-vary-val">
					<?php
						$this->build_input(
							$option_id . '[' . $curr_role . ']',
							'litespeed-input-short',
							$this->cls( 'Vary' )->in_vary_group( $curr_role )
						);
					?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody></table>
			<div class="litespeed-desc">
				<?php echo esc_html__( 'If your site contains public content that certain user roles can see but other roles cannot, you can specify a Vary Group for those user roles. For example, specifying an administrator vary group allows there to be a separate publicly-cached page tailored to administrators (with “edit” links, etc), while all other user roles see the default public page.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

</tbody></table>

</div>
