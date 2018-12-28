<?php
if ( ! defined( 'WPINC' ) ) die ;

$url = LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_UTIL, LiteSpeed_Cache_Utility::TYPE_SCORE_CHK, true ) ;
$url = htmlspecialchars_decode( $url ) ;
?>
<script type='text/javascript'>
	(function ($) {
		jQuery(document).ready(function () {
			$.get( '<?php echo $url ?>' ) ;
		} ) ;
	} )( jQuery ) ;
</script>