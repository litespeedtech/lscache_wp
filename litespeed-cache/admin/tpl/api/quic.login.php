<h1 class="litespeed-title"><?php echo __('Quic Cloud User Panel', 'litespeed-cache') ; ?></h1>

<form method="post" action="admin.php" id="litespeed_form_quic" class="litespeed-relative">
	<input type="hidden" name="<?php echo LiteSpeed_Cache::ACTION_KEY ; ?>" value="<?php echo LiteSpeed_Cache::ACTION_CDN_QUIC ; ?>" />
	<input type="hidden" name="step" value="2" />
	<?php wp_nonce_field( LiteSpeed_Cache::ACTION_CDN_QUIC, LiteSpeed_Cache::NONCE_NAME ) ; ?>

	<div class="litespeed-row">
		<h4><?php echo __( 'Email', 'litespeed-cache' ) ; ?>:</h4>
		<?php echo $data[ 'email' ] ; ?>
	</div>

	<div class="litespeed-row">
		<h4><?php echo __( 'Password', 'litespeed-cache' ) ; ?>:</h4>
		<input type="password" name="pswd" class="litespeed-regular-text" required />
	</div>

	<input type="submit" class="button litespeed-btn-primary" value="<?php echo __( 'Login', 'litespeed-cache' ) ; ?>" />

</form>
