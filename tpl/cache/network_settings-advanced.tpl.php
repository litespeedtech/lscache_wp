<?php
/**
 * LiteSpeed Cache Advanced Settings
 *
 * Displays the advanced settings section for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;
?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'Advanced Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cache/#advanced-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table">
	<tbody>
		<?php require LSCWP_DIR . 'tpl/cache/settings_inc.login_cookie.tpl.php'; ?>
	</tbody>
</table>