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
		<a href="<?php echo $this->build_url(LiteSpeed_Cache::ACTION_CRAWLER_GENERATE_FILE) ; ?>" class="litespeed-btn litespeed-btn-success">
			<?php echo __('Generate Crawler File', 'litespeed-cache') ; ?>
		</a>

		<?php
			if ( $sitemap_time ) {
				echo sprintf(__('Generated at %s', 'litespeed-cache'), $sitemap_time) ;
			}
		 ?>
		<div class="litespeed-desc">
			<?php echo sprintf(__('On click, this will create a crawler sitemap file in plugin directory %s.', 'litespeed-cache'), '`./var`') ; ?>
		</div>

<?php
	$seconds = $_options[LiteSpeed_Cache_Config::CRWL_RUN_INTERVAL] ;
	if($seconds > 0):
		$recurrence = '' ;
		$hours = (int)floor($seconds / 3600) ;
		if ( $hours ) {
			if ( $hours > 1) {
				$recurrence .= sprintf(__('%d hours', 'litespeed-cache'), $hours);
			}
			else {
				$recurrence .= sprintf(__('%d hour', 'litespeed-cache'), $hours);
			}
		}
		$minutes = (int)floor( ($seconds % 3600 ) / 60 ) ;
		if ( $minutes ) {
			$recurrence .= ' ' ;
			if ( $minutes > 1) {
				$recurrence .= sprintf(__('%d minutes', 'litespeed-cache'), $minutes);
			}
			else {
				$recurrence .= sprintf(__('%d minute', 'litespeed-cache'), $minutes);
			}
		}
		?>
		<h3 class="litespeed-title"><?php echo __('Crawler Cron', 'litespeed-cache') ; ?></h3>
		<table class="widefat striped">
			<thead><tr >
				<th scope="col"><?php echo __('Cron Name', 'litespeed-cache') ; ?></th>
				<th scope="col"><?php echo __('Run Frequency', 'litespeed-cache') ; ?></th>
				<th scope="col"><?php echo __('Last Status', 'litespeed-cache') ; ?></th>
				<th scope="col"><?php echo __('Activation', 'litespeed-cache') ; ?></th>
				<th scope="col"><?php echo __('Actions', 'litespeed-cache') ; ?></th>
			</tr></thead>
			<tbody>
				<tr>
					<td>
						<?php
							echo __('LiteSpeed Cache Crawler', 'litespeed-cache');
						?>
						<div class='litespeed-desc'>
						<?php
							$meta = LiteSpeed_Cache_Crawler::get_instance()->get_meta();
							if ( $meta && $meta->this_full_beginning_time ) {
								if (is running) {
									echo sprintf(__('The current sitemap crawl began at %s', 'litespeed-cache'),
											date('m/d/Y H:i:s' ,$meta->this_full_beginning_time));

								}
								else {
									echo sprintf(__('The last sitemap crawl began at %s', 'litespeed-cache'),
											date('m/d/Y H:i:s' ,$meta->this_full_beginning_time));
									echo sprintf(__('The next sitemap crawl will start at %s', 'litespeed-cache'),
											date('m/d/Y H:i:s',$meta->this_full_beginning_time
													+ $meta->last_full_time_cost + $_options[LiteSpeed_Cache_Config::CRWL_WHOLE_INTERVAL]));
								}

							}
						?>
						</div>
					</td>
					<td>
						<?php echo $recurrence ; ?>
						<div class='litespeed-desc'>
						<?php
							if ( $meta && $meta->last_full_time_cost ) {
								echo sprintf(__('The last complete run cost %s seconds', 'litespeed-cache'), $meta->last_full_time_cost) ;
							}
						?>
						</div>
					</td>
					<td>
					<?php
						if ( $meta ) {
							echo "Size: {$meta->list_size}<br />Position: {$meta->last_pos}" ;
							if ( $meta->is_running && time() - $meta->is_running <= $_options[LiteSpeed_Cache_Config::CRWL_RUN_DURATION] ) {
								echo "<br /><div class='litespeed-label litespeed-label-success'>" . __('Is running', 'litespeed-cache') . "</div>" ;
							}
						}
						else {
							echo "-" ;
						}
					?>
					</td>
					<td>
						<label class="litespeed-switch-onoff">
							<input type="checkbox" name="litespeed_crawler_cron_enable" id="litespeed_crawler_cron_enable" value="1" data-url="<?php echo $this->build_url(LiteSpeed_Cache::ACTION_CRAWLER_CRON_ENABLE, 'cron_enable') ; ?>" <?php if($_options[LiteSpeed_Cache_Config::CRWL_CRON_ACTIVE]) echo "checked"; ?> />
							<span data-on="Enable" data-off="Disable"></span>
							<span></span>
						</label>
					</td>
					<td>
					<?php
						echo " <a href='" . $this->build_url(LiteSpeed_Cache::ACTION_CRAWLER_RESET_POS) . "' class='litespeed-btn litespeed-btn-warning litespeed-btn-xs'>" . __('Reset position', 'litespeed-cache') . "</a>" ;
						echo " <a href='" . $this->build_url(LiteSpeed_Cache::ACTION_DO_CRAWL) . "' id='litespeed_manual_trigger' target='litespeedHiddenIframe' class='litespeed-btn litespeed-btn-success litespeed-btn-xs'>" . __('Manually run', 'litespeed-cache') . "</a>" ;
					?>
						<?php if ( $meta && $meta->last_start_time ): ?>
						<div class='litespeed-desc'>
							<?php echo sprintf(__('Last interval: %s', 'litespeed-cache'), date('m/d/Y H:i:s' ,$meta->last_start_time)) ; ?>
						</div>
						<?php endif ; ?>

						<?php if ( $meta && $meta->end_reason ): ?>
						<div class='litespeed-desc'>
							<?php echo sprintf(__('Ended reason: %s', 'litespeed-cache'), $meta->end_reason) ; ?>
						</div>
						<?php endif ; ?>

						<?php if ( $meta && $meta->last_crawled ): ?>
						<div class='litespeed-desc'>
							<?php echo sprintf(__('Last crawled: %s item(s)', 'litespeed-cache'), $meta->last_crawled) ; ?>
						</div>
						<?php endif ; ?>
					</td>
				</tr>
			</tbody>
		</table>
		<div class="litespeed-desc">
			<div><?php echo __('Run frequency is set by the Interval Between Runs setting.','litespeed-cache') ; ?></div>
			<div><?php echo __('Only one crawler can run concurrently.', 'litespeed-cache')
					. __('If both the cron and manual run start at a similar time, the first one to start will run.','litespeed-cache') ; ?></div>
			<div><?php echo sprintf(__('Please follow <a %s>Hooking WP-Cron Into the System Task Scheduler</a> to create the system cron task.','litespeed-cache'), ' href="https://developer.wordpress.org/plugins/cron/hooking-into-the-system-task-scheduler/" target="_blank" ') ; ?></div>
		</div>
<?php endif ; ?>


		<h3 class="litespeed-title"><?php echo __('Watch Crawler Status', 'litespeed-cache') ; ?></h3>

		<?php
			$ajaxUrl = LiteSpeed_Cache_Crawler::get_instance()->get_crawler_json_path() ;
			if ( $ajaxUrl ):
		?>

		<input type="button" id="litespeed-crawl-url-btn" value="<?php echo __('Show crawler status', 'litespeed-cache') ; ?>" class="litespeed-btn litespeed-btn-primary" data-url="<?php echo $ajaxUrl ; ?>" />

		<div class="litespeed-shell litespeed-hide">
			<div class="litespeed-shell-header-bar"></div>
			<div class="litespeed-shell-header">
				<div class="litespeed-shell-header-bg"></div>
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
