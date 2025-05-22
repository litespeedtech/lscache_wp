<?php
/**
 * LiteSpeed Cache Network Purge Settings
 *
 * Displays the network purge settings section for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;
?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'Purge Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cache/#purge-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table">
	<tbody>
		<?php require LSCWP_DIR . 'tpl/cache/settings_inc.purge_on_upgrade.tpl.php'; ?>
	</tbody>
</table>