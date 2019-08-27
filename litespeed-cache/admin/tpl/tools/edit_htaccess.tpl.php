<?php defined( 'WPINC' ) || exit ; ?>
<?php

$readonly = LiteSpeed_Htaccess::get_instance()->writable() ? '' : 'readonly';

$content = null ;
try {
	$content = LiteSpeed_Htaccess::get_instance()->htaccess_read();
} catch( \Exception $e ) {
	echo '<div class="notice notice-error is-dismissible"><p>'. $e->getMessage() . '</p></div>' ;
}


$htaccess_path = LiteSpeed_Htaccess::get_frontend_htaccess() ;

// Check if there is `ExpiresDefault` in .htaccess
if ( defined( 'LITESPEED_ON' ) ) {
	if ( $content && stripos( $content, "\nExpiresDefault" ) !== false ) {
		$is_dismissed = get_option( self::DISMISS_MSG ) ;
		if ( $is_dismissed !== self::RULECONFLICT_DISMISSED ) {
			// Need to add a notice for browser cache compatibility
			if ( $is_dismissed !== self::RULECONFLICT_ON ) {
				update_option( self::DISMISS_MSG, self::RULECONFLICT_ON ) ;
			}
			require_once LSCWP_DIR . 'admin/tpl/inc/show_rule_conflict.php' ;
		}
	}
	// don't dismiss the msg automatically
	// elseif ( $is_dismissed === LiteSpeed_Cache_Admin_Display::RULECONFLICT_ON ) {
	// 	update_option( self::DISMISS_MSG, LiteSpeed_Cache_Admin_Display::RULECONFLICT_DISMISSED ) ;
	// }
}


?>

<h3 class="litespeed-title">
	<?php echo __('LiteSpeed Cache Edit .htaccess', 'litespeed-cache'); ?>
</h3>

<div class="litespeed-callout notice notice-error inline">
	<h4>🚨 <?php echo __('This page is meant for advanced users.', 'litespeed-cache'); ?></h4>
	<p>
		<?php echo __('Any changes made to the .htaccess file may break the site.', 'litespeed-cache'); ?>
		<?php echo __('Please consult the host/server admin before making any changes.', 'litespeed-cache'); ?>
	</p>
</div>

<?php $this->form_action() ; ?>

<h3 class="litespeed-title-short">
	<?php echo __( '.htaccess Path Settings', 'litespeed-cache' ) ; ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:tool', false, 'litespeed-learn-more' ) ; ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>
	<tr>
		<th>
			<?php $id = LiteSpeed_Config::O_MISC_HTACCESS_FRONT ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_input( $id, 'litespeed-input-long' ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Specify the frontend .httaccess path.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'Leave empty to auto detect', 'litespeed-cache' ) ; ?>: <code><?php echo LiteSpeed_Htaccess::get_frontend_htaccess( true ) ; ?></code>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = LiteSpeed_Config::O_MISC_HTACCESS_BACK ; ?>
			<?php $this->title( $id ) ; ?>
		</th>
		<td>
			<?php $this->build_input( $id, 'litespeed-input-long' ) ; ?>
			<div class="litespeed-desc">
				<?php echo __( 'Specify the backend .httaccess path.', 'litespeed-cache' ) ; ?>
				<?php echo __( 'Leave empty to auto detect', 'litespeed-cache' ) ; ?>: <code><?php echo LiteSpeed_Htaccess::get_backend_htaccess( true ) ; ?></code>
			</div>
		</td>
	</tr>
</tbody></table>

<?php $this->form_end() ; ?>

<?php if ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ) : ?>
<div class="litespeed-h3"><?php echo __('File editing is disabled in configuration.', 'litespeed-cache'); ?></div>

<?php elseif( $content !== null ) : ?>

<?php $this->form_action( LiteSpeed_Cache::ACTION_SAVE_HTACCESS ) ; ?>

	<h3 class="litespeed-title"><?php echo sprintf(__('Current %s Contents', 'litespeed-cache'), '.htaccess'); ?></h3>

	<p><span class="attention"><?php echo sprintf(__('DO NOT EDIT ANYTHING WITHIN %s', 'litespeed-cache'), '<code>' . LiteSpeed_Htaccess::LS_MODULE_DONOTEDIT . '</code>' ); ?></span></p>

	<h4><?php echo $htaccess_path ; ?></h4>

	<textarea name="<?php echo LiteSpeed_Htaccess::EDITOR_TEXTAREA_NAME; ?>" wrap="off" rows="50" class="large-text"
			<?php echo $readonly; ?>
		><?php echo esc_textarea($content); ?></textarea>
	<button type="submit" class="button button-primary"><?php echo __('Save', 'litespeed-cache'); ?></button>
</form>

<?php endif; ?>
