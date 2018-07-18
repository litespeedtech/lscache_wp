<?php
if ( ! defined( 'WPINC' ) ) {
	die ;
}

if ( empty( $optm_summary[ 'level' ] ) ) {
	return ;
}
?>

<p class="litespeed-success">
	<?php echo __( 'Level', 'litespeed-cache' ) ; ?>: <font class="litespeed-success" style="font-size: 50px;"><?php echo $optm_summary[ 'level' ] ; ?></font>

	<span class="litespeed-left20"></span>

	<?php echo __( 'Credit', 'litespeed-cache' ) ; ?>: <font class="litespeed-success" style="font-size: 30px;"><?php echo $optm_summary[ 'credit' ] ; ?></font>
	<span class="litespeed-desc"><?php echo __( 'Credit recovers with each successful pull.', 'litespeed-cache' ) ; ?></span>
</p>

<?php
if ( $optm_summary[ 'level' ] >= 5 || empty( $optm_summary[ '_level_data' ] ) ) {
	return ;
}

$next_level = $optm_summary[ 'level' ] + 1 ;
$next_level_data = $optm_summary[ '_level_data' ][ $next_level ] ;

$_progress = floor( $optm_summary[ 'credit_recovered' ] * 100 / $next_level_data[ 0 ] ) ;
?>

<div class="litespeed-progress">
	<div class="litespeed-progress-bar litespeed-progress-bar-yellow" role="progressbar" style="width: <?php echo $_progress ; ?>%" aria-valuenow="<?php echo $_progress ; ?>" aria-valuemin="0" aria-valuemax="100"></div>
</div>

<p class="litespeed-text-right litespeed-silence">
	<?php echo __( 'Next Level', 'litespeed-cache' ) ; ?>: <?php echo $next_level ; ?>

	<span class="litespeed-left20"></span>

	<?php echo __( 'Next Level Credit', 'litespeed-cache' ) ; ?>: <?php echo $next_level_data[ 1 ] ; ?>
</p>
