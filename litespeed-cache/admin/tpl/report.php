<?php
if (!defined('WPINC')) die;

$_report = LiteSpeed_Cache_Admin_Report::get_instance() ;
$report = $_report->generate_environment_report();

$env_ref = $_report->get_env_ref() ;

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

$install_link = LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_ACTIVATION, LiteSpeed_Cache_Activation::TYPE_INSTALL_3RD, false, null, array( 'plugin' => 'dologin' ) );
?>

<div class="wrap">
	<h1 class="litespeed-h1">
		<?php echo __('LiteSpeed Cache Report', 'litespeed-cache'); ?>
	</h1>
	<span class="litespeed-desc">
		v<?php echo LiteSpeed_Cache::PLUGIN_VERSION; ?>
	</span>
	<hr class="wp-header-end">
</div>

<div class="litespeed-wrap">
	<div class="litespeed-body">
		<?php if ( ! $has_pswdless_plugin ) : ?>
		<div class="litespeed-callout-danger">
			<h4><?php echo __('NOTICE:', 'litespeed-cache'); ?></h4>
			<?php echo sprintf( __('To generate a passwordless link for LiteSpeed Support Team access, you must install %s.', 'litespeed-cache'), '<a href="https://wordpress.org/plugins/dologin/" target="_blank">DoLogin Security</a>' ); ?>
			<a href="<?php echo $install_link; ?>" class="litespeed-btn-success"><?php echo __('Automatically Install', 'litespeed-cache'); ?></a>
			<a href="plugin-install.php?s=dologin+security&tab=search&type=term" target="_blank"><?php echo __('Manually Install', 'litespeed-cache'); ?></a>
		</div>
		<?php endif; ?>

		<h3 class="litespeed-title"><?php echo __('LiteSpeed Report Number', 'litespeed-cache') ; ?></h3>

		<p><?php echo __('Report number', 'litespeed-cache') ; ?>: <b><?php echo $env_ref[ 'num' ] ; ?></b></p>
		<p><?php echo __('Report date', 'litespeed-cache') ; ?>: <b><?php echo $env_ref[ 'dateline' ] ; ?></b></p>

		<?php include_once LSCWP_DIR . "admin/tpl/inc/api_key.php" ; ?>

		<h3 class="litespeed-title"><?php echo __('Report Summary', 'litespeed-cache') ; ?></h3>
		<div class="litespeed-desc">
			<?php echo __('The environment report contains detailed information about the WordPress configuration.', 'litespeed-cache'); ?>
			<br />
			<?php echo __('If you run into any issues, please refer to the report number in your support message.', 'litespeed-cache'); ?>
		</div>

		<form action="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_REPORT, LiteSpeed_Cache_Admin_Report::TYPE_SEND_REPORT ) ; ?>" method="post">

			<textarea id="litespeed-report" rows="20" cols="100" readonly><?php echo $report; ?></textarea>

			<p>
				Link: <input type="text" class="litespeed-regular-text" id="litespeed-report-link" name="link" value="<?php echo $link; ?>" />
				<?php if ( $has_pswdless_plugin ) : ?>
					<a href="<?php echo admin_url( 'admin.php?page=lscache-report&dologin_gen_link=1' ); ?>"><?php echo __( 'Generate Passwordless Link for Current User', 'litespeed-cache' ) ; ?></a>
				<?php else: ?>
					<a href="<?php echo $install_link; ?>" class="litespeed-btn-success"><?php echo __( 'Install DoLogin Security to Generate Passwordless Link', 'litespeed-cache' ) ; ?></a>
				<?php endif; ?>
			</p>
			<p>
				<?php if ( $link ) : ?>
					<strong><?php echo __('Please do NOT share the above passwordless link with anyone.', 'litespeed-cache'); ?></strong>
					<strong><?php echo sprintf( __('Generated links may be managed under <a %s>Settings</a>.', 'litespeed-cache'), 'href="' . menu_page_url( 'dologin', 0 ) . '"' ); ?></strong>
				<?php endif; ?>
			</p>

			<p class="litespeed-desc"><?php echo __( 'To grant wp-admin access to the LiteSpeed Support Team, please generate a passwordless link for the current logged-in user to be sent with the report.', 'litespeed-cache' ) ; ?></p>

			<button class="litespeed-btn-warning" type="submit">
				<?php echo __( 'Send To LiteSpeed', 'litespeed-cache' ) ; ?>
			</button>
			<span class="litespeed-desc">
				<?php echo __( 'Send this report to LiteSpeed. Refer to this report number when posting in the WordPress support forum.', 'litespeed-cache' ) ; ?>
			</span>
		</form>

	</div>
</div>

