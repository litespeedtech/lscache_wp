<?php
if ( ! defined( 'WPINC' ) ) die ;

$reasons = array() ;

if ( ! defined( 'LITESPEED_ALLOWED' ) ) {
	if ( defined( 'LITESPEED_SERVER_TYPE' ) && LITESPEED_SERVER_TYPE == 'NONE') {
		$reasons[] = array(
			'title' => __( 'To use the caching functions you must have a LiteSpeed web server or be using QUIC.cloud CDN.', 'litespeed-cache' ),
			'link'	=> 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp#requirements',
		) ;
	} else {
		$reasons[] = array(
			'title' => __( 'Please enable the LSCache Module at the server level, or ask your hosting provider.', 'litespeed-cache' ),
			'link'	=> 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:installation',
		) ;
	}
}

if ( ! defined( 'LITESPEED_ON_IN_SETTING' ) ) {
	$reasons[] = array(
		'title' => __( 'Please enable LiteSpeed Cache in the plugin settings.', 'litespeed-cache' ) ,
		'link'	=> 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:general#enable_litespeed_cache',
	) ;
}

if ( ! $reasons && ! defined( 'LITESPEED_ON' ) ) {
	$reasons[] = array(
		'title' => __( 'LiteSpeed Cache is disabled.', 'litespeed-cache' ) ,
		'link'	=> 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:troubleshooting:lscache-is-disabled',
	) ;
}

if ( $reasons ) :
?>
	<div class="litespeed-callout-danger">

		<h4><?php echo __( 'WARNING', 'litespeed-cache' ) ; ?></h4>

		<p>
			<?php echo __( 'Caching functions on this page are currently unavailable!', 'litespeed-cache' ) ; ?>
		</p>

		<ul class="litespeed-list">
		<?php foreach ( $reasons as $v ) : ?>
			<li>
				<?php echo $v[ 'title' ] ; ?>

				<a href="<?php echo $v[ 'link' ] ; ?>" target="_blank" class="litespeed-learn-more"><?php echo __( 'Learn More', 'litespeed-cache' ) ; ?></a>
			</li>
		<?php endforeach ; ?>
		</ul>

	</div>
<?php endif ;
