<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$summary = Profiles::get_summary();

function apply_url( $profile ) {
	echo Utility::build_url(
		Router::ACTION_PROFILES,
		Profiles::TYPE_APPLY,
		false,
		null,
		array( 'profile' => $profile )
	);
}

function apply_label() {
	esc_html_e( 'Apply Profile', 'litespeed-cache' );
}
?>

<h3 class="litespeed-title">
	<?php esc_html_e( 'LiteSpeed Cache Profiles', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/general/#profiles-tab' ); ?>
</h3>

<div><a href="<?php apply_url( 'essentials' ); ?>" class="button button-primary">
	<?php apply_label(); ?>
</a></div>

<h3 class="litespeed-title">
	<?php esc_html_e( 'History', 'litespeed-cache' ); ?>
</h3>

<?php if ( ! empty( $summary['profile'] ) ) : ?>
<div class="litespeed-desc">
	<?php
	printf(
		esc_html__( 'Last applied the %1$s profile %2$s', 'litespeed-cache' ),
		'<strong>' . ucwords( $summary['profile'] ) . '</strong>',
		Utility::readable_time( $summary['profile_time'] )
	);
	?>
</div>
<?php endif; ?>

<div><a
		href="<?php echo Utility::build_url( Router::ACTION_PROFILES, Profiles::TYPE_REVERT ); ?>"
		class="button button-primary"
	>
	<?php esc_html_e( 'Revert', 'litespeed-cache' ); ?>
</a></div>



<!--
<div class="litespeed-desc">
	<?php echo __( 'This will export all current LiteSpeed Cache settings and save them as a file.', 'litespeed-cache' ); ?>
</div>

<h3 class="litespeed-title"><?php echo __('Import Settings', 'litespeed-cache'); ?></h3>

<?php $this->form_action( Router::ACTION_IMPORT, Import::TYPE_IMPORT, true ); ?>

	<div class="litespeed-div">
		<input type="file" name="ls_file" class="litespeed-input" />
	</div>
	<div class="litespeed-div">
		<?php submit_button(__('Import', 'litespeed-cache'), 'button button-primary', 'litespeed-submit'); ?>
	</div>
</form>

<?php if ( ! empty( $summary[ 'import_file' ] ) ) : ?>
<div class="litespeed-desc">
	<?php echo __( 'Last imported', 'litespeed-cache' ); ?>: <code><?php echo $summary[ 'import_file' ]; ?></code> <?php echo Utility::readable_time( $summary[ 'import_time' ]); ?>
</div>
<?php endif; ?>

<div class="litespeed-desc">
	<?php echo __( 'This will import settings from a file and override all current LiteSpeed Cache settings.', 'litespeed-cache' ); ?>
</div>

<h3 class="litespeed-title"><?php echo __('Reset All Settings', 'litespeed-cache'); ?></h3>
<div><p>🚨 <?php echo __( 'This will reset all settings to default settings.', 'litespeed-cache' ); ?></p>
</div>
<div><a href="<?php echo Utility::build_url( Router::ACTION_IMPORT, Import::TYPE_RESET ); ?>" data-litespeed-cfm="<?php echo __( 'Are you sure you want to reset all settings back to the default settings?', 'litespeed-cache' ); ?>" class="button litespeed-btn-danger-bg">
	<?php echo __( 'Reset Settings', 'litespeed-cache' ); ?>
</a></div>

-->
