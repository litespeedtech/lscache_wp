<?php
if (!defined('WPINC')) die;

$readonly = LiteSpeed_Cache_Admin_Rules::writable() ? '' : 'readonly';
$content = LiteSpeed_Cache_Admin_Rules::get_instance()->htaccess_read();
$htaccess_path = LiteSpeed_Cache_Admin_Rules::get_frontend_htaccess() ;

// Check if there is `ExpiresDefault` in .htaccess
if ( defined( 'LITESPEED_ON' ) ) {
	$htaccess_con = Litespeed_File::read( LiteSpeed_Cache_Admin_Rules::get_frontend_htaccess() ) ;
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

<div class="wrap">
	<h1 class="litespeed-h1">
		<?php echo __('LiteSpeed Cache Edit .htaccess', 'litespeed-cache'); ?>
	</h1>
	<span class="litespeed-desc">
		v<?php echo LiteSpeed_Cache::PLUGIN_VERSION; ?>
	</span>
	<hr class="wp-header-end">
</div>
<div class="litespeed-wrap">
	<div class="litespeed-body">
		<div class="litespeed-callout-danger">
			<h4><?php echo __('WARNING: This page is meant for advanced users.', 'litespeed-cache'); ?></h4>
			<p>
				<?php echo __('Any changes made to the .htaccess file may break the site.', 'litespeed-cache'); ?>
				<?php echo __('Please consult the host/server admin before making any changes.', 'litespeed-cache'); ?>
			</p>
		</div>

		<?php if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT): ?>
		<div class="litespeed-h3"><?php echo __('File editing is disabled in configuration.', 'litespeed-cache'); ?></div>

		<?php elseif($content === false): ?>
		<div class="litespeed-h3"><?php $this->display_messages(); ?></div>

		<?php else: ?>

		<form method="post" action="admin.php?page=<?php echo LiteSpeed_Cache::PAGE_EDIT_HTACCESS; ?>">
			<?php $this->form_action(LiteSpeed_Cache::ACTION_SAVE_HTACCESS); ?>

			<div class="litespeed-title"><?php echo sprintf(__('Current %s Contents', 'litespeed-cache'), '.htaccess'); ?></div>

			<p><span class="attention"><?php echo sprintf(__('DO NOT EDIT ANYTHING WITHIN %s', 'litespeed-cache'), '<code>' . LiteSpeed_Cache_Admin_Rules::LS_MODULE_DONOTEDIT . '</code>' ); ?></span></p>

			<h4><?php echo $htaccess_path ; ?></h4>

			<textarea name="<?php echo LiteSpeed_Cache_Admin_Rules::EDITOR_TEXTAREA_NAME; ?>" wrap="off" rows="50" class="litespeed-input-long"
				<?php echo $readonly; ?>
			><?php echo esc_textarea($content); ?></textarea>

			<button type="submit" class="litespeed-btn-primary"><?php echo __('Save', 'litespeed-cache'); ?></button>
		</form>

		<?php endif; ?>
	</div>
</div>
