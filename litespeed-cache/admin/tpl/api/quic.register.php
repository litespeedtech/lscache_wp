<h2 class="litespeed-title"><?php echo __('Quic Cloud User Panel', 'litespeed-cache') ; ?></h2>

<form method="post" action="admin.php" id="litespeed_form_quic" class="litespeed-relative">
	<input type="hidden" name="<?php echo LiteSpeed_Cache::ACTION_KEY ; ?>" value="<?php echo LiteSpeed_Cache::ACTION_CDN_QUIC ; ?>" />
	<input type="hidden" name="step" value="2" />
	<?php wp_nonce_field( LiteSpeed_Cache::ACTION_CDN_QUIC, LiteSpeed_Cache::NONCE_NAME ) ; ?>

	<h3>Email: <?php echo $_email ; ?></h3>

	Password: <input type="password" name="pswd" class="litespeed-regular-text" required /><br />

	<input type="checkbox" class="form-check-input" id="exampleCheck1" required />
	<label class="form-check-label" for="exampleCheck1">I agree to <a href="https://quic.cloud/agreement" target="_blank">QuicCloud's terms and conditions</a></label><br />

	<button type="submit"><?php echo __( 'Register', 'litespeed-cache' ) ; ?></button>

</form>
