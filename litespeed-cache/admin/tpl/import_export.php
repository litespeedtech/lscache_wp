<?php
if ( ! defined( 'WPINC' ) ) die ;

$log = get_option( LiteSpeed_Cache_Import::DB_IMPORT_LOG, array() ) ;
?>

<div class="wrap">
	<h1 class="litespeed-h1">
		<?php echo __('LiteSpeed Cache Import / Export', 'litespeed-cache') ; ?>
	</h1>
	<span class="litespeed-desc">
		v<?php echo LiteSpeed_Cache::PLUGIN_VERSION; ?>
	</span>
	<hr class="wp-header-end">
</div>

<div class="litespeed-wrap">
	<div class="litespeed-body">
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

		<form method="post" action="admin.php?page=lscache-import" id="litespeed_form_import" enctype="multipart/form-data" class="">
			<?php $this->form_action( LiteSpeed_Cache::ACTION_IMPORT, LiteSpeed_Cache_Import::TYPE_IMPORT ) ; ?>

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

	</div>
</div>
