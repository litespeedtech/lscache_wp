<?php
if (!defined('WPINC')) die;

$readonly = LiteSpeed_Cache_Admin_Rules::writable() ? '' : 'readonly';
$content = LiteSpeed_Cache_Admin_Rules::get_instance()->htaccess_read();

// Check if there is `ExpiresDefault` in .htaccess
if ( LiteSpeed_Cache::config( LiteSpeed_Cache_Config::OPID_ENABLED ) ) {
	$htaccess_con = Litespeed_File::read( LiteSpeed_Cache_Admin_Rules::get_frontend_htaccess() ) ;
	if ( $content && stripos( $content, "\nExpiresDefault" ) !== false ) {
		$is_dismissed = get_option( self::DISMISS_MSG ) ;
		if ( $is_dismissed !== self::RULECONFLICT_DISMISSED ) {
			// Need to add a notice for browser cache compatibility
			if ( $is_dismissed !== self::RULECONFLICT_ON ) {
				update_option( self::DISMISS_MSG, self::RULECONFLICT_ON ) ;
			}
			$this->show_rule_conflict() ;
		}
	}
	// don't dismiss the msg automatically
	// elseif ( $is_dismissed === LiteSpeed_Cache_Admin_Display::RULECONFLICT_ON ) {
	// 	update_option( self::DISMISS_MSG, LiteSpeed_Cache_Admin_Display::RULECONFLICT_DISMISSED ) ;
	// }
}


?>

<div class="wrap">
	<h2>
		<?php echo __('LiteSpeed Cache Edit .htaccess', 'litespeed-cache'); ?>
		<span class="litespeed-desc">
			v<?php echo LiteSpeed_Cache::PLUGIN_VERSION; ?>
		</span>
	</h2>
</div>
<div class="wrap">
	<div class="litespeed-cache-welcome-panel">
		<div class="litespeed-callout litespeed-callout-danger">
			<p><span class="attention"><?php echo __('WARNING: This page is meant for advanced users.', 'litespeed-cache'); ?></span></p>
			<?php echo __('Any changes made to the .htaccess file may break the site.', 'litespeed-cache'); ?>
			<?php echo __('Please consult the host/server admin before making any changes.', 'litespeed-cache'); ?>
		</div>

		<?php if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT): ?>
		<h3><?php echo __('File editing is disabled in configuration.', 'litespeed-cache'); ?></h3>

		<?php elseif($content === false): ?>
		<h3><?php $this->display_messages(); ?></h3>

		<?php else: ?>

		<form method="post" action="admin.php?page=<?php echo LiteSpeed_Cache::PAGE_EDIT_HTACCESS; ?>">
			<?php $this->form_action(LiteSpeed_Cache::ACTION_SAVE_HTACCESS); ?>

			<h3><?php echo sprintf(__('Current %s contents:', 'litespeed-cache'), '.htaccess'); ?></h3>

			<!--p><span class="attention"><?php echo sprintf(__('DO NOT EDIT ANYTHING WITHIN %s', 'litespeed-cache'), LiteSpeed_Cache_Admin_Rules::LS_MODULE_DONOTEDIT); ?></span></p-->

			<p><?php echo __('These are added by the LS Cache plugin and may cause problems if they are changed.', 'litespeed-cache'); ?></p>

			<textarea id="wpwrap" name="<?php echo LiteSpeed_Cache_Admin_Rules::EDITOR_TEXTAREA_NAME; ?>" wrap="off" rows="30" class="code"
				<?php echo $readonly; ?>
			><?php echo esc_textarea($content); ?></textarea>

			<button type="submit" class="litespeed-btn litespeed-btn-success"><?php echo __('Save', 'litespeed-cache'); ?></button>
		</form>

		<?php require LSWCP_DIR . 'admin/tpl/info_common_rewrite.php'; ?>

		<?php endif; ?>
	</div>
</div>
