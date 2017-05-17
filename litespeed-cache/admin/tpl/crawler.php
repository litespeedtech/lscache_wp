<?php
if (!defined('WPINC')) die ;

$_options = LiteSpeed_Cache_Config::get_instance()->get_options() ;

$sitemap_time = LiteSpeed_Cache_Crawler::get_instance()->sitemap_time() ;
?>

<div class="wrap">
	<h2><?php echo __('LiteSpeed Cache Crawler', 'litespeed-cache') ; ?></h2>
</div>
<div class="wrap">
	<div class="litespeed-cache-welcome-panel">
		<h3 class="litespeed-title"><?php echo __('Crawler File', 'litespeed-cache') ; ?></h3>
		<a href="<?php echo LiteSpeed_Cache_Admin_Display::build_url(LiteSpeed_Cache::ACTION_CRAWLER_GENERATE_FILE) ; ?>" class="litespeed-btn litespeed-btn-success">
			<?php echo __('Generate Crawler File', 'litespeed-cache') ; ?>
		</a>

		<?php
			if ( $sitemap_time ) {
				echo sprintf(__('Generated at %s', 'litespeed-cache'), $sitemap_time) ;
			}
		 ?>
		<div class="litespeed-desc">
			<?php echo __('This will create a Crawler file in plugin folder', 'litespeed-cache') ; ?>
		</div>

<?php
	$seconds = $_options[LiteSpeed_Cache_Config::CRWL_CRON_INTERVAL] ;
	if($seconds > 0):
		$hours = floor($seconds / 3600) ;
		$act = LiteSpeed_Cache_Config::CRWL_CRON_ACTIVE ;
		$active = $_options[$act] ;
		$triggerLink = false ;
		$triggerLink = admin_url( 'admin-ajax.php?action=crawl_data&' . LiteSpeed_Cache::ACTION_KEY . '=' . LiteSpeed_Cache::ACTION_DO_CRAWL ) ;
		if ( $active > 0 ) {
			$active = 0 ;
			$active_text = __('Deactivate','litespeed-cache') ;
		}
		else {
			$active = 1 ;
			$active_text = __('Activate','litespeed-cache') ;
		}
		?>
		<h3 class="litespeed-title"><?php echo __('Crawler Cron', 'litespeed-cache') ; ?></h3>
		<table class="widefat striped">
			<thead><tr>
				<th scope="col"><?php echo __('Cron Name', 'litespeed-cache') ; ?></th>
				<th scope="col"><?php echo __('Recurrence', 'litespeed-cache') ; ?></th>
				<th scope="col"><?php echo __('Actions', 'litespeed-cache') ; ?></th>
			</tr></thead>
			<tbody>
				<tr>
					<td><?php echo __('LiteSpeed Cache Crawler', 'litespeed-cache') ; ?></td>
					<td>
						<?php echo sprintf(__('%d hour(s)', 'litespeed-cache'), $hours) ; ?>
					</td>
					<td><?php
						echo $active_text ;
						echo " <a href='$triggerLink' target='litespeedHiddenIframe' class='litespeed-btn litespeed-btn-success litespeed-btn-xs'>" . __('reset position', 'litespeed-cache') . "</a>" ;
						echo " <a href='$triggerLink' target='litespeedHiddenIframe' class='litespeed-btn litespeed-btn-success litespeed-btn-xs'>" . __('manually start', 'litespeed-cache') . "</a>" ;
					?></td>
				</tr>
			</tbody>
		</table>
		<div class="litespeed-desc">
			<?php echo __('Recurrence is calculated when you set Cron interval in seconds','litespeed-cache') ; ?>
		</div>
<?php endif ; ?>


		<h3 class="litespeed-title"><?php echo __('Show Crawler Status', 'litespeed-cache') ; ?></h3>

		<?php
			$ajaxUrl = LiteSpeed_Cache_Crawler::get_instance()->get_crawler_json_path() ;
			if ( $ajaxUrl ):
		?>

		<input type="button" id="litespeed-crawl-url-btn" value="<?php echo __('Show crawler status', 'litespeed-cache') ; ?>" class="litespeed-btn litespeed-btn-success" data-url="<?php echo $ajaxUrl ; ?>" />

		<div class="litespeed-shell litespeed-hide">
			<div class="litespeed-shell-header-bar"></div>
			<div class="litespeed-shell-header">
				<div class="litespeed-shell-header-bg"></div>
				<div class="litespeed-shell-header-num"></div>
				<div class="litespeed-shell-header-icon-container">
					<img id="litespeed-shell-icon" src="<?php echo plugins_url('img/Litespeed.icon.svg', dirname(__FILE__)); ?>" />
				</div>
			</div>
			<ul class="litespeed-shell-body">
				<li>Start watching...</li>
				<li id="litespeed-loading-dot"></li>
			</ul>
		</div>

		<?php else: ?>
		<p>
			<?php echo __('No crawler meta file generated yet', 'litespeed-cache') ; ?>
		</p>
		<?php endif ; ?>
	</div>
</div>

<iframe name="litespeedHiddenIframe" src="" width="0" height="0" frameborder="0"></iframe>
