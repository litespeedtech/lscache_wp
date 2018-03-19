<?php
if ( ! defined( 'WPINC' ) ) die ;

$reasons = array() ;

if ( ! defined( 'LITESPEED_ALLOWED' ) ) {
	$reasons[] = array(
		'title' => __( 'LSCache Module is disabled.', 'litespeed-cache' ),
		'link'	=> 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:installation',
	) ;
}

if ( ! defined( 'LITESPEED_ON_IN_SETTING' ) ) {
	$reasons[] = array(
		'title' => __( 'LiteSpeed cache is disabled in setting.', 'litespeed-cache' ) ,
		'link'	=> 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:general#enable_litespeed_cache',
	) ;
}

if ( ! $reasons && ! defined( 'LITESPEED_ON' ) ) {
	$reasons[] = array(
		'title' => __( 'LiteSpeed cache is disabled.', 'litespeed-cache' ) ,
		'link'	=> 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:general#enable_litespeed_cache',
	) ;
}

if ( $reasons ) :
?>
	<div class="litespeed-callout-danger">

		<h4><?php echo __( 'WARNING', 'litespeed-cache' ) ; ?></h4>

		<p>
			<?php echo __( 'The functionalities here can not work due to:', 'litespeed-cache' ) ; ?>
		</p>

		<ul class="litespeed-list">
		<?php foreach ( $reasons as $v ) : ?>
			<li>
				<?php echo $v[ 'title' ] ; ?>

				<a href="<?php echo $v[ 'link' ] ; ?>" target="_blank" class="litespeed-learn-more"><?php echo __( 'How to fix it?', 'litespeed-cache' ) ; ?></a>
			</li>
		<?php endforeach ; ?>
		</ul>

	</div>
<?php endif ;
