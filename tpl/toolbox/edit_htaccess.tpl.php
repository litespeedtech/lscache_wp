<?php
/**
 * LiteSpeed Cache View .htaccess
 *
 * Renders the .htaccess view interface for LiteSpeed Cache, displaying the contents and paths of frontend and backend .htaccess files.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$content = null;
try {
	$content = Htaccess::cls()->htaccess_read();
} catch ( \Exception $e ) {
	?>
	<div class="notice notice-error is-dismissible">
		<p><?php echo wp_kses_post( $e->getMessage() ); ?></p>
	</div>
	<?php
}

$htaccess_path = Htaccess::get_frontend_htaccess();

// Check for `ExpiresDefault` in .htaccess when LiteSpeed is enabled
if ( defined( 'LITESPEED_ON' ) && $content && stripos( $content, "\nExpiresDefault" ) !== false ) {
	$is_dismissed = GUI::get_option( self::DB_DISMISS_MSG );
	if ( self::RULECONFLICT_DISMISSED !== $is_dismissed ) {
		if ( self::RULECONFLICT_ON !== $is_dismissed ) {
			GUI::update_option( self::DB_DISMISS_MSG, self::RULECONFLICT_ON );
		}
		require_once LSCWP_DIR . 'tpl/inc/show_rule_conflict.php';
	}
}
?>

<h3 class="litespeed-title">
	<?php esc_html_e( 'LiteSpeed Cache View .htaccess', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/toolbox/#view-htaccess-tab' ); ?>
</h3>

<h3 class="litespeed-title-short">
	<?php esc_html_e( '.htaccess Path', 'litespeed-cache' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table">
	<tbody>
		<tr>
			<th>
				<?php esc_html_e( 'Frontend .htaccess Path', 'litespeed-cache' ); ?>
			</th>
			<td>
				<code><?php echo esc_html( $htaccess_path ); ?></code>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Default path is', 'litespeed-cache' ); ?>: <code><?php echo esc_html( Htaccess::get_frontend_htaccess( true ) ); ?></code>
					<br />
					<font class="litespeed-success">
						<?php esc_html_e( 'API', 'litespeed-cache' ); ?>:
						<?php printf( esc_html__( 'PHP Constant %s is supported.', 'litespeed-cache' ), '<code>LITESPEED_CFG_HTACCESS</code>' ); ?>
						<?php printf( esc_html__( 'You can use this code %1$s in %2$s to specify the htaccess file path.', 'litespeed-cache' ), '<code>defined("LITESPEED_CFG_HTACCESS") || define("LITESPEED_CFG_HTACCESS", "your path on server");</code>', '<code>wp-config.php</code>' ); ?>
					</font>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php esc_html_e( 'Backend .htaccess Path', 'litespeed-cache' ); ?>
			</th>
			<td>
				<code><?php echo esc_html( Htaccess::get_backend_htaccess() ); ?></code>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Default path is', 'litespeed-cache' ); ?>: <code><?php echo esc_html( Htaccess::get_backend_htaccess( true ) ); ?></code>
					<br />
					<font class="litespeed-success">
						<?php esc_html_e( 'API', 'litespeed-cache' ); ?>:
						<?php printf( esc_html__( 'PHP Constant %s is supported.', 'litespeed-cache' ), '<code>LITESPEED_CFG_HTACCESS_BACKEND</code>' ); ?>
						<?php printf( esc_html__( 'You can use this code %1$s in %2$s to specify the htaccess file path.', 'litespeed-cache' ), '<code>defined("LITESPEED_CFG_HTACCESS_BACKEND") || define("LITESPEED_CFG_HTACCESS_BACKEND", "your path on server");</code>', '<code>wp-config.php</code>' ); ?>
					</font>
				</div>
			</td>
		</tr>
	</tbody>
</table>

<?php if ( null !== $content ) : ?>
	<h3 class="litespeed-title">
		<?php printf( esc_html__( 'Current %s Contents', 'litespeed-cache' ), '.htaccess' ); ?>
	</h3>

	<h4><?php echo esc_html( $htaccess_path ); ?></h4>

	<textarea readonly wrap="off" rows="50" class="large-text"><?php echo esc_textarea( $content ); ?></textarea>
<?php endif; ?>