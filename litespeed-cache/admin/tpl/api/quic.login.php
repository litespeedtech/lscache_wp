<?php
if ( ! defined( 'WPINC' ) ) {
	die ;
}
?>

<h1 class="litespeed-title"><?php echo __('Quic Cloud User Panel', 'litespeed-cache') ; ?></h1>

<form method="post" action="admin.php" id="litespeed_form_quic" class="litespeed-relative">
	<input type="hidden" name="<?php echo LiteSpeed_Cache::ACTION_KEY ; ?>" value="<?php echo LiteSpeed_Cache::ACTION_CDN_QUIC ; ?>" />
	<input type="hidden" name="step" value="login" />
	<input type="hidden" name="email" value="<?php echo $data[ 'email' ] ; ?>" />
	<?php wp_nonce_field( LiteSpeed_Cache::ACTION_CDN_QUIC, LiteSpeed_Cache::NONCE_NAME ) ; ?>

	<?php if ( ! empty( $data[ '_err' ] ) ) : ?>
	<div class="litespeed-callout-danger">
		<h4><?php echo __( 'ERROR', 'litespeed-cache' ) ; ?>:</h4>
		<ol>
			<li><?php echo $data[ '_err' ] ; ?></li>
		</ol>
	</div>
	<?php endif ; ?>

	<div class="litespeed-row">
		<h4><?php echo __( 'Email', 'litespeed-cache' ) ; ?>:</h4>
		<?php echo $data[ 'email' ] ; ?>
	</div>

	<div class="litespeed-row">
		<h4><?php echo __( 'Password', 'litespeed-cache' ) ; ?> <?php echo __( 'Or', 'litespeed-cache' ) ; ?> <?php echo __( 'User API Key', 'litespeed-cache' ) ; ?> :</h4>
		<input type="password" name="pswd_or_key" class="litespeed-regular-text" value="<?php echo LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPT_CDN_QUIC_KEY ) ; ?>" />
	</div>

	<input type="submit" class="button litespeed-btn-success" value="<?php echo __( 'Login', 'litespeed-cache' ) ; ?>" />

	<a href="javascript:;" onclick="window.history.back();" class="button litespeed-btn-primary litespeed-right"><?php echo __( 'Back', 'litespeed-cache' ) ; ?></a>
</form>
