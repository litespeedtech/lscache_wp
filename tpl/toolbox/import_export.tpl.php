<?php
namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$summary = Import::get_summary();
?>

<h3 class="litespeed-title">
	<?php echo __( 'Export Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/toolbox/#importexport-tab' ); ?>
</h3>

<div><a href="<?php echo Utility::build_url( Router::ACTION_IMPORT, Import::TYPE_EXPORT ); ?>" class="button button-primary">
	<?php echo __( 'Export', 'litespeed-cache' ); ?>
</a></div>

<?php if ( ! empty( $summary['export_file'] ) ) : ?>
<div class="litespeed-desc">
	<?php echo __( 'Last exported', 'litespeed-cache' ); ?>: <code><?php echo $summary['export_file']; ?></code> <?php echo Utility::readable_time( $summary['export_time'] ); ?>
</div>
<?php endif; ?>

<div class="litespeed-desc">
	<?php echo __( 'This will export all current LiteSpeed Cache settings and save them as a file.', 'litespeed-cache' ); ?>
</div>

<h3 class="litespeed-title"><?php echo __( 'Import Settings', 'litespeed-cache' ); ?></h3>

<?php $this->form_action( Router::ACTION_IMPORT, Import::TYPE_IMPORT, true ); ?>

	<div class="litespeed-div">
		<input type="file" name="ls_file" class="litespeed-input" />
	</div>
	<div class="litespeed-div">
		<?php submit_button( __( 'Import', 'litespeed-cache' ), 'button button-primary', 'litespeed-submit' ); ?>
	</div>
</form>

<?php if ( ! empty( $summary['import_file'] ) ) : ?>
<div class="litespeed-desc">
	<?php echo __( 'Last imported', 'litespeed-cache' ); ?>: <code><?php echo $summary['import_file']; ?></code> <?php echo Utility::readable_time( $summary['import_time'] ); ?>
</div>
<?php endif; ?>

<div class="litespeed-desc">
	<?php echo __( 'This will import settings from a file and override all current LiteSpeed Cache settings.', 'litespeed-cache' ); ?>
</div>

<h3 class="litespeed-title"><?php echo __( 'Reset All Settings', 'litespeed-cache' ); ?></h3>
<div><p>🚨 <?php echo __( 'This will reset all settings to default settings.', 'litespeed-cache' ); ?></p>
</div>
<div><a href="<?php echo Utility::build_url( Router::ACTION_IMPORT, Import::TYPE_RESET ); ?>" data-litespeed-cfm="<?php echo __( 'Are you sure you want to reset all settings back to the default settings?', 'litespeed-cache' ); ?>" class="button litespeed-btn-danger-bg">
	<?php echo __( 'Reset Settings', 'litespeed-cache' ); ?>
</a></div>



