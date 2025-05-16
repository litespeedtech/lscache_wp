<?php

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$content = null;
try {
	$content = Htaccess::cls()->htaccess_read();
} catch ( \Exception $e ) {
	echo '<div class="notice notice-error is-dismissible"><p>' . $e->getMessage() . '</p></div>';
}

$htaccess_path = Htaccess::get_frontend_htaccess();

// Check if there is `ExpiresDefault` in .htaccess
if ( defined( 'LITESPEED_ON' ) ) {
	if ( $content && stripos( $content, "\nExpiresDefault" ) !== false ) {
		$is_dismissed = GUI::get_option( self::DB_DISMISS_MSG );
		if ( $is_dismissed !== self::RULECONFLICT_DISMISSED ) {
			// Need to add a notice for browser cache compatibility
			if ( $is_dismissed !== self::RULECONFLICT_ON ) {
				GUI::update_option( self::DB_DISMISS_MSG, self::RULECONFLICT_ON );
			}
			require_once LSCWP_DIR . 'tpl/inc/show_rule_conflict.php';
		}
	}
	// don't dismiss the msg automatically
	// elseif ( $is_dismissed === Cache_Admin_Display::RULECONFLICT_ON ) {
	// update_option( self::DISMISS_MSG, Cache_Admin_Display::RULECONFLICT_DISMISSED );
	// }
}


?>

<h3 class="litespeed-title">
	<?php echo __( 'LiteSpeed Cache View .htaccess', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/toolbox/#view-htaccess-tab' ); ?>
</h3>

<h3 class="litespeed-title-short">
	<?php echo __( '.htaccess Path', 'litespeed-cache' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table">
	<tbody>
		<tr>
			<th>
				<?php echo __( 'Frontend .htaccess Path', 'litespeed-cache' ); ?>
			</th>
			<td>
				<code><?php echo $htaccess_path; ?></code>
				<div class="litespeed-desc">
					<?php echo __( 'Default path is', 'litespeed-cache' ); ?>: <code><?php echo Htaccess::get_frontend_htaccess( true ); ?></code>
					<br />
					<font class="litespeed-success">
						<?php echo __( 'API', 'litespeed-cache' ); ?>:
						<?php printf( __( 'PHP Constant %s is supported.', 'litespeed-cache' ), '<code>LITESPEED_CFG_HTACCESS</code>' ); ?>
						<?php printf( __( 'You can use this code %1$s in %2$s to specify the htaccess file path.', 'litespeed-cache' ), '<code>defined("LITESPEED_CFG_HTACCESS") || define("LITESPEED_CFG_HTACCESS", "your path on server");</code>', '<code>wp-config.php</code>' ); ?>
					</font>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php echo __( 'Backend .htaccess Path', 'litespeed-cache' ); ?>
			</th>
			<td>
				<?php echo Htaccess::get_backend_htaccess(); ?>
				<div class="litespeed-desc">
					<?php echo __( 'Default path is', 'litespeed-cache' ); ?>: <code><?php echo Htaccess::get_backend_htaccess( true ); ?></code>
					<br />
					<font class="litespeed-success">
						<?php echo __( 'API', 'litespeed-cache' ); ?>:
						<?php printf( __( 'PHP Constant %s is supported.', 'litespeed-cache' ), '<code>LITESPEED_CFG_HTACCESS_BACKEND</code>' ); ?>
						<?php printf( __( 'You can use this code %1$s in %2$s to specify the htaccess file path.', 'litespeed-cache' ), '<code>defined("LITESPEED_CFG_HTACCESS_BACKEND") || define("LITESPEED_CFG_HTACCESS_BACKEND", "your path on server");</code>', '<code>wp-config.php</code>' ); ?>
					</font>
				</div>
			</td>
		</tr>
	</tbody>
</table>

<?php if ( $content !== null ) : ?>

	<h3 class="litespeed-title"><?php printf( __( 'Current %s Contents', 'litespeed-cache' ), '.htaccess' ); ?></h3>

	<h4><?php echo $htaccess_path; ?></h4>

	<textarea readonly wrap="off" rows="50" class="large-text"><?php echo esc_textarea( $content ); ?></textarea>

<?php endif; ?>