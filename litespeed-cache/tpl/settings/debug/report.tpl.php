<?php
defined( 'WPINC' ) || exit ;

$_report = Report::get_instance() ;
$report = $_report->generate_environment_report();

$env_ref = $_report->get_env_ref() ;

?>

<h3 class="litespeed-title"><?php echo __('LiteSpeed Report Number', 'litespeed-cache') ; ?></h3>

<p><?php echo __('Report number', 'litespeed-cache') ; ?>: <b><?php echo $env_ref[ 'num' ] ; ?></b></p>
<p><?php echo __('Report date', 'litespeed-cache') ; ?>: <b><?php echo $env_ref[ 'dateline' ] ; ?></b></p>

<p><a href="<?php echo Utility::build_url( Core::ACTION_REPORT, Report::TYPE_SEND_REPORT ) ; ?>" class="button litespeed-btn-warning">
	<?php echo __( 'Send To LiteSpeed', 'litespeed-cache' ) ; ?>
</a></p>
<p class="litespeed-desc">
	<?php echo __( 'Send this report to LiteSpeed. Refer to this report number when posting in the WordPress support forum.', 'litespeed-cache' ) ; ?>
</p>

<?php include_once LSCWP_DIR . "tpl/settings/inc/api_key.php" ; ?>

<h3 class="litespeed-title"><?php echo __('Report Summary', 'litespeed-cache') ; ?></h3>
<div class="litespeed-description">
	<?php echo __('The environment report contains detailed information about the WordPress configuration.', 'litespeed-cache'); ?>
	<br />
	<?php echo __('If you run into any issues, please refer to the report number in your support message.', 'litespeed-cache'); ?>
</div>
<textarea id="litespeed-report" rows="20" cols="100" readonly><?php echo $report; ?></textarea>


