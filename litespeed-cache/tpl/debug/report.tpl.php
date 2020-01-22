<?php
namespace LiteSpeed ;
defined( 'WPINC' ) || exit ;

$_report = Report::get_instance() ;
$report = $_report->generate_environment_report();

$env_ref = Report::get_summary() ;

// Detect password less plugin
$link = '';
$has_pswdless_plugin = false;
if ( function_exists( 'dologin_gen_link' ) ) {
	$has_pswdless_plugin = true;
	if ( ! empty( $_GET[ 'dologin_gen_link' ] ) ) {
		unset( $_GET[ 'dologin_gen_link' ] );
		$link = dologin_gen_link( 'Litespeed Report' );
		?>
		<script>window.history.pushState( 'remove_gen_link', document.title, window.location.href.replace( '&dologin_gen_link=1', '' ) );</script>
		<?php
	}
}

$install_link = Utility::build_url( Router::ACTION_ACTIVATION, Activation::TYPE_INSTALL_3RD, false, null, array( 'plugin' => 'dologin' ) );
?>


<h3 class="litespeed-title"><?php echo __('LiteSpeed Report Number', 'litespeed-cache') ; ?></h3>

<p><?php echo __('Report number', 'litespeed-cache') ; ?>: <b><?php echo ! empty( $env_ref[ 'num' ] ) ? $env_ref[ 'num' ] : '-' ; ?></b></p>
<p><?php echo __('Report date', 'litespeed-cache') ; ?>: <b><?php echo ! empty( $env_ref[ 'dateline' ] ) ? date( 'm/d/Y H:i:s', $env_ref[ 'dateline' ] ) : '-' ; ?></b></p>

<p class="litespeed-desc">
	<?php echo __( 'The environment report contains detailed information about the WordPress configuration.', 'litespeed-cache' ); ?>
	<br />
	<?php echo __('If you run into any issues, please refer to the report number in your support message.', 'litespeed-cache'); ?>
</p>

<form action="<?php echo Utility::build_url( Router::ACTION_REPORT, Report::TYPE_SEND_REPORT ); ?>" method="post">

	<textarea id="litespeed-report" rows="20" cols="100" readonly><?php echo $report; ?></textarea>


	<?php if ( ! $has_pswdless_plugin ) : ?>
		<div class="litespeed-callout notice notice-warning inline">
			<h4><?php echo __( 'NOTICE:', 'litespeed-cache' ); ?></h4>
			<p>
				<?php echo sprintf( __( 'To generate a passwordless link for LiteSpeed Support Team access, you must install %s.', 'litespeed-cache' ), '<a href="https://wordpress.org/plugins/dologin/" target="_blank">DoLogin Security</a>' ); ?>
			</p>
			<p>
				<a href="<?php echo $install_link; ?>" class="button litespeed-btn litespeed-right20"><?php echo __( 'Install DoLogin Security', 'litespeed-cache' ); ?></a> 
				<a href="plugin-install.php?s=dologin+security&tab=search&type=term" target="_blank"><?php echo __( 'Go to plugins list', 'litespeed-cache' ); ?></a>
			</p>
		</div>
	<?php endif; ?>
	<p>
		<label for="litespeed-report-link" class="litespeed-right10">Passwordless link</label>
		<input type="text" class="litespeed-regular-text" id="litespeed-report-link" name="link" value="<?php echo $link; ?>" style="width:500px;" />
		<?php if ( $has_pswdless_plugin ) : ?>
			<a href="<?php echo admin_url( 'admin.php?page=litespeed-debug&dologin_gen_link=1' ); ?>" class="button button-secondary"><?php echo __( 'Generate Link for Current User', 'litespeed-cache' ) ; ?></a>
		<?php else: ?>
			<button type="button" class="button button-secondary" disabled><?php echo __( 'Generate Link for Current User', 'litespeed-cache' ) ; ?></button>
		<?php endif; ?>
	</p>
	<p>
		<?php if ( $link ) : ?>
			<strong>🚨 <?php echo __('Please do NOT share the above passwordless link with anyone.', 'litespeed-cache'); ?></strong>
			<strong><?php echo sprintf( __('Generated links may be managed under <a %s>Settings</a>.', 'litespeed-cache'), 'href="' . menu_page_url( 'dologin', 0 ) . '"' ); ?></strong>
		<?php endif; ?>
	</p>

	<p class="litespeed-desc"><?php echo __( 'To grant wp-admin access to the LiteSpeed Support Team, please generate a passwordless link for the current logged-in user to be sent with the report.', 'litespeed-cache' ) ; ?></p>

	<p class="litespeed-top30">
			<?php echo __( 'Send this report to LiteSpeed. Refer to this report number when posting in the WordPress support forum.', 'litespeed-cache' ) ; ?>
		</p>
	<p>
		<button class="button button-primary" type="submit">
			<?php echo __( 'Send to LiteSpeed', 'litespeed-cache' ) ; ?>
		</button>
		
	</p>
</form>

<?php include_once LSCWP_DIR . "tpl/inc/api_key.php" ; ?>

