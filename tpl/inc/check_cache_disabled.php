<?php
namespace LiteSpeed ;
defined( 'WPINC' ) || exit ;

$reasons = array() ;

if ( ! defined( 'LITESPEED_ALLOWED' ) ) {
	if ( defined( 'LITESPEED_SERVER_TYPE' ) && LITESPEED_SERVER_TYPE == 'NONE' ) {
		$reasons[] = array(
			'title' => __( 'To use the caching functions you must have a LiteSpeed web server or be using QUIC.cloud CDN.', 'litespeed-cache' ),
			'link'	=> 'https://docs.litespeedtech.com/lscache/lscwp/faq/#why-do-the-cache-features-require-a-litespeed-server',
		) ;
	} else {
		$reasons[] = array(
			'title' => __( 'Please enable the LSCache Module at the server level, or ask your hosting provider.', 'litespeed-cache' ),
			'link'	=> 'https://docs.litespeedtech.com/lscache/lscwp/overview/#prerequisites',
		) ;
	}
}
elseif ( ! defined( 'LITESPEED_ON' ) ) {
	$reasons[] = array(
		'title' => __( 'Please enable LiteSpeed Cache in the plugin settings.', 'litespeed-cache' ) ,
		'link'	=> 'https://docs.litespeedtech.com/lscache/lscwp/cache/#enable-cache',
	) ;
}

if ( $reasons ) :
?>
	<div class="litespeed-callout notice notice-error inline">

		<h4><?php echo __( 'WARNING', 'litespeed-cache' ) ; ?></h4>

		<p>
			<?php echo __( 'LSCache caching functions on this page are currently unavailable!', 'litespeed-cache' ) ; ?>
		</p>

		<ul class="litespeed-list">
		<?php foreach ( $reasons as $v ) : ?>
			<li>
				<?php echo esc_html( $v[ 'title' ] ); ?>

				<a href="<?php echo $v[ 'link' ] ; ?>" target="_blank" class="litespeed-learn-more"><?php echo __( 'Learn More', 'litespeed-cache' ) ; ?></a>
			</li>
		<?php endforeach ; ?>
		</ul>

	</div>
<?php endif ;
