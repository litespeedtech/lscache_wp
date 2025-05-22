<?php
/**
 * LiteSpeed Cache Network Cache Settings
 *
 * Displays the network cache control settings section for LiteSpeed Cache.
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
			<th><?php esc_html_e( 'Network Enable Cache', 'litespeed-cache' ); ?></th>
			<td>
				<?php $this->build_switch( Base::O_CACHE ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Enabling LiteSpeed Cache for WordPress here enables the cache for the network.', 'litespeed-cache' ); ?><br />
					<?php esc_html_e( 'It is STRONGLY recommended that the compatibility with other plugins on a single/few sites is tested first.', 'litespeed-cache' ); ?><br />
					<?php esc_html_e( 'This is to ensure compatibility prior to enabling the cache for all sites.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<?php
		require LSCWP_DIR . 'tpl/cache/settings_inc.cache_mobile.tpl.php';
		require LSCWP_DIR . 'tpl/cache/settings_inc.cache_dropquery.tpl.php';
		?>
	</tbody>
</table>