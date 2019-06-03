<?php
if ( ! defined( 'WPINC' ) ) die ;

$url = LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_UTIL, LiteSpeed_Cache_Utility::TYPE_SCORE_CHK, true ) ;
$url = htmlspecialchars_decode( $url ) ;
?>
<script>
	document.addEventListener( 'DOMContentLoaded', function( event ) {
		jQuery(document).ready( function() {
			jQuery.get( '<?php echo $url ?>' ) ;
		} ) ;
	} ) ;
</script>