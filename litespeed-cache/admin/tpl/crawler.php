<?php
if (!defined('WPINC')) die ;

$_options = LiteSpeed_Cache_Config::get_instance()->get_options() ;

$_crawler_instance = LiteSpeed_Cache_Crawler::get_instance() ;

$sitemap_time = $_crawler_instance->sitemap_time() ;

$crawler_list = $_crawler_instance->list_crawlers() ;

$meta = $_crawler_instance->read_meta() ;
if ( $meta[ 'curr_crawler' ] >= count( $crawler_list ) ) {
	$meta[ 'curr_crawler' ] = 0 ;
}

$is_running = time() - $meta[ 'is_running' ] <= $_options[LiteSpeed_Cache_Config::CRWL_RUN_DURATION] ;

$disabled = LiteSpeed_Cache_Router::can_crawl() ? '' : 'disabled' ;

LiteSpeed_Cache_GUI::show_promo() ;
?>

<div class="wrap">
	<h1 class="litespeed-h1">
		<?php echo __('LiteSpeed Cache Crawler', 'litespeed-cache') ; ?>
	</h1>
	<span class="litespeed-desc">
		v<?php echo LiteSpeed_Cache::PLUGIN_VERSION; ?>
	</span>
	<hr class="wp-header-end">

</div>

<div class="litespeed-wrap">
	<div class="litespeed-body">
		<h3 class="litespeed-title"><?php echo __('Crawler File', 'litespeed-cache') ; ?></h3>
		<a href="<?php echo LiteSpeed_Cache_Utility::build_url(LiteSpeed_Cache::ACTION_CRAWLER_GENERATE_FILE) ; ?>" class="litespeed-btn-success">
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

		<h3 class="litespeed-title litespeed-relative">
			<?php echo __('Crawler Cron', 'litespeed-cache') ; ?>
			<span class="litespeed-switch-drag litespeed-cron-onoff-btn">
				<input type="checkbox" name="litespeed_crawler_cron_enable" id="litespeed_crawler_cron_enable" value="1"
					data-url="<?php echo LiteSpeed_Cache_Utility::build_url( LiteSpeed_Cache::ACTION_CRAWLER_CRON_ENABLE, false, true ) ; ?>"
					<?php if( $_options[LiteSpeed_Cache_Config::CRWL_CRON_ACTIVE] && LiteSpeed_Cache_Router::can_crawl() ) echo "checked"; ?>
					<?php echo $disabled ; ?>
				/>
				<label class="litespeed-switch-drag-label" for="litespeed_crawler_cron_enable">
					<span class="litespeed-switch-drag-inner" data-on="<?php echo __('Enable', 'litespeed-cache'); ?>" data-off="<?php echo __('Disable', 'litespeed-cache'); ?>"></span>
					<span class="litespeed-switch-drag-switch"></span>
				</label>
			</span>
		</h3>
		<?php if ( ! LiteSpeed_Cache_Router::can_crawl() ): ?>
			<div class="litespeed-callout-danger">
				<h4><?php echo __('WARNING', 'litespeed-cache'); ?></h4>
				<p><?php echo __('The crawler feature is not enabled on the LiteSpeed server. Please consult your server admin.', 'litespeed-cache'); ?></p>
				<p><?php echo sprintf(__('See <a %s>Introduction for Enabling the Crawler</a> for detailed information.', 'litespeed-cache'), 'href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:enabling_the_crawler" target="_blank"') ; ?></p>
			</div>
		<?php endif; ?>


		<?php if ( $meta[ 'this_full_beginning_time' ] ) : ?>
		<p>
			<b><?php echo __( 'Current sitemap crawl started at', 'litespeed-cache' ) ; ?>:</b>
			<?php echo LiteSpeed_Cache_Utility::readable_time( $meta[ 'this_full_beginning_time' ] ) ; ?>
		</p>

			<?php if ( ! $is_running ) : ?>
		<p>
			<b><?php echo __( 'The next complete sitemap crawl will start at', 'litespeed-cache' ) ; ?>:</b>
			<?php echo date('m/d/Y H:i:s',$meta[ 'this_full_beginning_time' ] + LITESPEED_TIME_OFFSET + $meta[ 'last_full_time_cost' ] + $_options[LiteSpeed_Cache_Config::CRWL_CRAWL_INTERVAL]) ; ?>
			<?php endif ; ?>
		</p>

		<?php endif ; ?>
		<?php if ( $meta[ 'last_full_time_cost' ] ) : ?>
		<p>
			<b><?php echo __( 'Last complete run time for all crawlers', 'litespeed-cache' ) ; ?>:</b>
			<?php echo sprintf( __( '%d seconds', 'litespeed-cache' ), $meta[ 'last_full_time_cost' ] ) ; ?>
		</p>
		<?php endif ; ?>

		<?php if ( $meta[ 'last_crawler_total_cost' ] ) : ?>
		<p>
			<b><?php echo __('Run time for previous crawler', 'litespeed-cache') ; ?>:</b>
			<?php echo sprintf( __( '%d seconds', 'litespeed-cache' ), $meta[ 'last_crawler_total_cost' ] ) ; ?>
		</p>
		<?php endif ; ?>

		<?php if ( $meta[ 'curr_crawler_beginning_time' ] ) : ?>
		<p>
			<b><?php echo __('Current crawler started at', 'litespeed-cache') ; ?>:</b>
			<?php echo LiteSpeed_Cache_Utility::readable_time( $meta[ 'curr_crawler_beginning_time' ] ) ; ?>
		</p>
		<?php endif ; ?>

		<?php if ( $meta[ 'last_start_time' ] ) : ?>
		<p class='litespeed-desc'>
			<b><?php echo __('Last interval', 'litespeed-cache') ; ?>:</b>
			<?php echo LiteSpeed_Cache_Utility::readable_time( $meta[ 'last_start_time' ] ) ; ?>
		</p>
		<?php endif ; ?>

		<?php if ( $meta[ 'end_reason' ] ) : ?>
		<p class='litespeed-desc'>
			<b><?php echo __( 'Ended reason', 'litespeed-cache' ) ; ?>:</b>
			<?php echo $meta[ 'end_reason' ] ; ?>
		</p>
		<?php endif ; ?>

		<?php if ( $meta[ 'last_crawled' ] ) : ?>
		<p class='litespeed-desc'>
			<?php echo sprintf(__('<b>Last crawled:</b> %s item(s)', 'litespeed-cache'), $meta[ 'last_crawled' ] ) ; ?>
		</p>
		<?php endif ; ?>

		<?php echo " <a href='" . LiteSpeed_Cache_Utility::build_url(LiteSpeed_Cache::ACTION_CRAWLER_RESET_POS) . "' class='litespeed-btn-warning litespeed-btn-xs'>" . __('Reset position', 'litespeed-cache') . "</a>" ;

		$href = LiteSpeed_Cache_Router::can_crawl() ? LiteSpeed_Cache_Utility::build_url(LiteSpeed_Cache::ACTION_DO_CRAWL) : 'javascript:;' ;
		echo " <a href='$href' id='litespeed_manual_trigger' target='litespeedHiddenIframe' class='litespeed-btn-success litespeed-btn-xs' $disabled>" . __('Manually run', 'litespeed-cache') . "</a>" ;
		?>


		<table class="litespeed-table">
			<thead><tr >
				<th scope="col">#</th>
				<th scope="col"><?php echo __('Cron Name', 'litespeed-cache') ; ?></th>
				<th scope="col"><?php echo __('Run Frequency', 'litespeed-cache') ; ?></th>
				<th scope="col"><?php echo __('Size', 'litespeed-cache') ; ?></th>
				<th scope="col"><?php echo __('Status', 'litespeed-cache') ; ?></th>
			</tr></thead>
			<tbody>
				<?php foreach ( $crawler_list as $i => $v ) : ?>
				<tr>
					<td>
					<?php
						echo $i + 1 ;
						if ( $i == $meta[ 'curr_crawler' ] ) {
							echo "<img class='litespeed-crawler-curr' src='" . LSWCP_PLUGIN_URL . "img/Litespeed.icon.svg' />" ;
						}
					?>
					</td>
					<td>
						<?php echo ucfirst( $v[ 'role_title' ] ) ; ?>
						<?php if ( $v[ 'webp' ] ) {
							echo ' - WebP' ;
						} ?>
					</td>
					<td><?php echo $recurrence ; ?></td>
					<td><?php echo "Size: $meta[list_size]" ; ?></td>
					<td>
					<?php
						if ( $i == $meta[ 'curr_crawler' ] ) {
							echo "Position: " . ( $meta[ 'last_pos' ] + 1 ) ;
							if ( $is_running ) {
								echo " <span class='litespeed-label-success'>" . __( 'running', 'litespeed-cache' ) . "</span>" ;
							}
						}
					?>
					</td>
				</tr>
				<?php endforeach ; ?>
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
			$ajaxUrl = $_crawler_instance->get_crawler_json_path() ;
			if ( $ajaxUrl ):
		?>

		<input type="button" id="litespeed-crawl-url-btn" value="<?php echo __('Show crawler status', 'litespeed-cache') ; ?>" class="litespeed-btn-primary" data-url="<?php echo $ajaxUrl ; ?>" />

		<div class="litespeed-shell litespeed-hide">
			<div class="litespeed-shell-header-bar"></div>
			<div class="litespeed-shell-header">
				<div class="litespeed-shell-header-bg"></div>
				<div class="litespeed-shell-header-icon-container">
					<img id="litespeed-shell-icon" src="<?php echo LSWCP_PLUGIN_URL . 'img/Litespeed.icon.svg' ; ?>" />
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


		<h3 class="litespeed-title"><?php echo __('Sitemap Generation Blacklist', 'litespeed-cache') ; ?></h3>

		<form method="post" action="admin.php?page=lscache-crawler">
			<?php $this->form_action(LiteSpeed_Cache::ACTION_BLACKLIST_SAVE); ?>
			<p>
				<textarea name="<?php echo LiteSpeed_Cache_Crawler::CRWL_BLACKLIST; ?>" rows="10" class="litespeed-textarea"><?php echo $_crawler_instance->get_blacklist(); ?></textarea>
			</p>

			<p>
				<button type="submit" class="litespeed-btn-success"><?php echo __('Save', 'litespeed-cache'); ?></button>
			</p>
		</form>
		<div class="litespeed-desc">
			<p><?php echo sprintf(__('Current blacklist has %s item(s).', 'litespeed-cache'), $_crawler_instance->count_blacklist()); ?></p>
			<p><?php echo __('All Urls which returned no-cache tags will be added here, after the initial crawling.', 'litespeed-cache'); ?></p>
		</div>

	</div>
</div>

<iframe name="litespeedHiddenIframe" src="" width="0" height="0" frameborder="0"></iframe>
