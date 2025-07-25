<?php
/**
 * LiteSpeed Cache Image Optimization Network Settings
 *
 * Manages network-wide image optimization settings for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$this->form_action( Router::ACTION_SAVE_SETTINGS_NETWORK );
?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'Image Optimization Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/imageopt/#image-optimization-settings-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>
	<?php require LSCWP_DIR . 'tpl/img_optm/settings.media_webp.tpl.php'; ?>

</tbody></table>

<?php
$this->form_end( true );