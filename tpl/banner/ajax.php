<?php
/**
 * Health Check Script
 *
 * Triggers a health check request for speed when the document is loaded.
 *
 * @package LiteSpeed
 * @since 1.0.0
 * @deprecated 3.3 Will only show banner after user manually checked score
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$url = Utility::build_url( Router::ACTION_HEALTH, Health::TYPE_SPEED, true, null, array(), true );
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
	jQuery(document).ready( function() {
			jQuery.get( '<?php echo $url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>' ) ;
		} ) ;
});
</script>