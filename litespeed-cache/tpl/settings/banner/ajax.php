<?php
defined( 'WPINC' ) || exit ;

$url = Utility::build_url( Core::ACTION_UTIL, Utility::TYPE_SCORE_CHK, true ) ;
$url = htmlspecialchars_decode( $url ) ;
?>
<script>
	document.addEventListener( 'DOMContentLoaded', function( event ) {
		jQuery(document).ready( function() {
			jQuery.get( '<?php echo $url ?>' ) ;
		} ) ;
	} ) ;
</script>