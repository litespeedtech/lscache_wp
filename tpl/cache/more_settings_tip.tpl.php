<?php
/**
 * LiteSpeed Cache Setting Tip
 *
 * Displays a notice to inform users about additional LiteSpeed Cache settings.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

global $pagenow;
if ( 'options-general.php' !== $pagenow ) {
	return;
}
?>

<div class="litespeed-callout notice notice-success inline">
	<h4><?php esc_html_e( 'NOTE', 'litespeed-cache' ); ?></h4>
	<p>
		<?php
		printf(
			/* translators: %s: LiteSpeed Cache menu label */
			esc_html__( 'More settings available under %s menu', 'litespeed-cache' ),
			'<code>' . esc_html__( 'LiteSpeed Cache', 'litespeed-cache' ) . '</code>'
		);
		?>
	</p>
</div>