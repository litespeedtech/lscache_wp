<?php
/**
 * LiteSpeed Cache Import/Export Settings
 *
 * Renders the import/export settings interface for LiteSpeed Cache, allowing users to export, import, or reset plugin settings.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$summary = Import::get_summary();
?>

<h3 class="litespeed-title">
	<?php esc_html_e( 'Export Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/toolbox/#importexport-tab' ); ?>
</h3>

<div>
	<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_IMPORT, Import::TYPE_EXPORT ) ); ?>" class="button button-primary">
		<?php esc_html_e( 'Export', 'litespeed-cache' ); ?>
	</a>
</div>

<?php if ( ! empty( $summary['export_file'] ) ) : ?>
	<div class="litespeed-desc">
		<?php esc_html_e( 'Last exported', 'litespeed-cache' ); ?>: <code><?php echo esc_html( $summary['export_file'] ); ?></code> <?php echo esc_html( Utility::readable_time( $summary['export_time'] ) ); ?>
	</div>
<?php endif; ?>

<div class="litespeed-desc">
	<?php esc_html_e( 'This will export all current LiteSpeed Cache settings and save them as a file.', 'litespeed-cache' ); ?>
</div>

<h3 class="litespeed-title">
	<?php esc_html_e( 'Import Settings', 'litespeed-cache' ); ?>
</h3>

<?php $this->form_action( Router::ACTION_IMPORT, Import::TYPE_IMPORT, true ); ?>
	<div class="litespeed-div">
		<input type="file" name="ls_file" class="litespeed-input" />
	</div>
	<div class="litespeed-div">
		<?php submit_button( esc_html__( 'Import', 'litespeed-cache' ), 'button button-primary', 'litespeed-submit' ); ?>
	</div>
</form>

<?php if ( ! empty( $summary['import_file'] ) ) : ?>
	<div class="litespeed-desc">
		<?php esc_html_e( 'Last imported', 'litespeed-cache' ); ?>: <code><?php echo esc_html( $summary['import_file'] ); ?></code> <?php echo esc_html( Utility::readable_time( $summary['import_time'] ) ); ?>
	</div>
<?php endif; ?>

<div class="litespeed-desc">
	<?php esc_html_e( 'This will import settings from a file and override all current LiteSpeed Cache settings.', 'litespeed-cache' ); ?>
</div>

<h3 class="litespeed-title">
	<?php esc_html_e( 'Reset All Settings', 'litespeed-cache' ); ?>
</h3>

<div>
	<p class="litespeed-danger">🚨 <?php esc_html_e( 'This will reset all settings to default settings.', 'litespeed-cache' ); ?></p>
</div>
<div>
	<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_IMPORT, Import::TYPE_RESET ) ); ?>" data-litespeed-cfm="<?php echo esc_attr( __( 'Are you sure you want to reset all settings back to the default settings?', 'litespeed-cache' ) ); ?>" class="button litespeed-btn-danger-bg">
		<?php esc_html_e( 'Reset Settings', 'litespeed-cache' ); ?>
	</a>
</div>