<h2 class="litespeed-title"><?php echo __('Quic Cloud User Panel', 'litespeed-cache') ; ?></h2>

<form method="post" action="admin.php" id="litespeed_form_quic" class="litespeed-relative">
	<input type="hidden" name="<?php echo LiteSpeed_Cache::ACTION_KEY ; ?>" value="<?php echo LiteSpeed_Cache::ACTION_CDN_QUIC ; ?>" />
	<input type="hidden" name="step" value="2" />
	<?php wp_nonce_field( LiteSpeed_Cache::ACTION_CDN_QUIC, LiteSpeed_Cache::NONCE_NAME ) ; ?>

	<h3>Email: <?php echo $_email ; ?></h3>

	Password: <input type="password" name="pswd" class="litespeed-regular-text" required />

	<button type="submit"><?php echo __( 'Login', 'litespeed-cache' ) ; ?></button>

</form>
