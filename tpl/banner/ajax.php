<?php
/**
 * @deprecated 3.3 Will only show banner after user manually checked score
 */

namespace LiteSpeed ;
defined( 'WPINC' ) || exit ;

$url = Utility::build_url( Router::ACTION_HEALTH, Health::TYPE_SPEED, true ) ;
$url = htmlspecialchars_decode( $url ) ;
?>
<script>
	document.addEventListener( 'DOMContentLoaded', function( event ) {
		jQuery(document).ready( function() {
			jQuery.get( '<?php echo $url; ?>' ) ;
		} ) ;
	} ) ;
</script>