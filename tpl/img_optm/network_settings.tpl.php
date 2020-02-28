<?php
namespace LiteSpeed ;
defined( 'WPINC' ) || exit ;

$this->form_action( Router::ACTION_SAVE_SETTINGS_NETWORK ) ;
?>

<h3 class="litespeed-title-short">
	<?php echo __( 'Image Optimization Settings', 'litespeed-cache' ) ; ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:media', false, 'litespeed-learn-more' ) ; ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>
	<?php require LSCWP_DIR . 'tpl/img_optm/settings.media_webp.tpl.php'; ?>

</tbody></table>

<?php
$this->form_end( true ) ;

