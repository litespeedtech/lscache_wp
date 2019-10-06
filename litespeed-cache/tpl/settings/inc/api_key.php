<?php
namespace LiteSpeed ;
defined( 'WPINC' ) || exit ;

$api_key = Conf::val( Base::O_API_KEY ) ;

?>

<?php if ( ! $api_key ) : ?>
	<p class="litespeed-desc">
		<?php echo __( 'This will also generate an API key from LiteSpeed\'s Server.', 'litespeed-cache' ) ; ?>
	</p>
<?php endif ; ?>

