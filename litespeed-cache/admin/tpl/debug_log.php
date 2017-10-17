<?php
if ( ! defined( 'WPINC' ) ) die ;


?>

<div class="wrap">
	<h2>
		<?php echo __( 'LiteSpeed Cache Debug Log Viewer', 'litespeed-cache' ) ; ?>
		<span class="litespeed-desc">
			v<?php echo LiteSpeed_Cache::PLUGIN_VERSION ; ?>
		</span>
	</h2>
</div>
<div class="wrap">
	<?php

		$file = LSWCP_CONTENT_DIR . '/debug.log' ;
		$lines = Litespeed_File::count_lines( $file ) ;
		$start = $lines > 1000 ? $lines - 1000 : 0 ;
		$logs = Litespeed_File::read( $file, $start ) ;
		$logs = implode( "\n", $logs ) ;

		echo nl2br( htmlspecialchars( $logs ) ) ;

	?>
</div>
