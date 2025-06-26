<?php
/**
 * LiteSpeed Cache Network Exclude Settings
 *
 * Displays the network exclude settings section for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;
?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'Exclude Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cache/#excludes-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table">
	<tbody>
		<?php
		// Cookie
		require LSCWP_DIR . 'tpl/cache/settings_inc.exclude_cookies.tpl.php';

		// User Agent
		require LSCWP_DIR . 'tpl/cache/settings_inc.exclude_useragent.tpl.php';
		?>
	</tbody>
</table>