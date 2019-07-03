<?php defined( 'WPINC' ) || exit ; ?>
<?php

$log = LiteSpeed_Cache_Import::get_instance()->summary() ;
?>

<h3 class="litespeed-title"><?php echo __('Export Settings', 'litespeed-cache') ; ?></h3>

<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMPORT, LiteSpeed_Cache_Import::TYPE_EXPORT ) ; ?>" class="litespeed-btn-success">
	<?php echo __( 'Export', 'litespeed-cache' ) ; ?>
</a>

<?php if ( ! empty( $log[ 'export' ] ) ) : ?>
<div class="litespeed-desc">
	<?php echo __( 'Last exported', 'litespeed-cache' ) ; ?>: <code><?php echo $log[ 'export' ][ 'file' ] ; ?></code> <?php echo LiteSpeed_Cache_Utility::readable_time( $log[ 'export' ][ 'time' ]) ; ?>
</div>
<?php endif ; ?>

<div class="litespeed-desc">
	<?php echo __( 'This will export all current LiteSpeed Cache settings and save as a file.', 'litespeed-cache' ) ; ?>
</div>

<h3 class="litespeed-title"><?php echo __('Import Settings', 'litespeed-cache') ; ?></h3>

<?php $this->form_action( LiteSpeed_Cache::ACTION_IMPORT, LiteSpeed_Cache_Import::TYPE_IMPORT, true ) ; ?>

	<div class="litespeed-div litespeed-left20">
		<input type="file" name="ls_file" class="litespeed-input" />
	</div>
	<div class="litespeed-div">
		<?php submit_button(__('Import', 'litespeed-cache'), 'litespeed-btn-success', 'litespeed-submit') ; ?>
	</div>
</form>

<?php if ( ! empty( $log[ 'import' ] ) ) : ?>
<div class="litespeed-desc">
	<?php echo __( 'Last imported', 'litespeed-cache' ) ; ?>: <code><?php echo $log[ 'import' ][ 'file' ] ; ?></code> <?php echo LiteSpeed_Cache_Utility::readable_time( $log[ 'import' ][ 'time' ]) ; ?>
</div>
<?php endif ; ?>

<div class="litespeed-desc">
	<?php echo __( 'This will import settings from a file and override all current LiteSpeed Cache settings.', 'litespeed-cache' ) ; ?>
</div>

<h3 class="litespeed-title"><?php echo __('Reset All Settings', 'litespeed-cache') ; ?></h3>

<a href="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_IMPORT, LiteSpeed_Cache_Import::TYPE_RESET ) ; ?>" data-litespeed-cfm="<?php echo __( 'Are you sure to reset all settings to default settings?', 'litespeed-cache' ) ; ?>" class="litespeed-btn-danger">
	<?php echo __( 'Reset', 'litespeed-cache' ) ; ?>
</a>

<div class="litespeed-desc">
	🚨
	<?php echo __( 'This will reset all settings to default settings.', 'litespeed-cache' ) ; ?>
</div>

