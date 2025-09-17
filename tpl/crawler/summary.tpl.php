<?php
/**
 * LiteSpeed Cache Crawler Summary
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$__crawler    = Crawler::cls();
$crawler_list = $__crawler->list_crawlers();
$summary      = Crawler::get_summary();

if ( $summary['curr_crawler'] >= count( $crawler_list ) ) {
	$summary['curr_crawler'] = 0;
}

$is_running = time() - $summary['is_running'] <= 900;

$disabled     = Router::can_crawl() ? '' : 'disabled';
$disabled_tip = '';
if ( ! $this->conf( Base::O_CRAWLER_SITEMAP ) ) {
	$disabled     = 'disabled';
	$disabled_tip = '<span class="litespeed-callout notice notice-error inline litespeed-left20">' . sprintf(
		esc_html__( 'You need to set the %s in Settings first before using the crawler', 'litespeed-cache' ),
		'<code>' . esc_html( Lang::title( Base::O_CRAWLER_SITEMAP ) ) . '</code>'
	) . '</span>';
}

$crawler_run_interval = defined( 'LITESPEED_CRAWLER_RUN_INTERVAL' ) ? LITESPEED_CRAWLER_RUN_INTERVAL : 600;
if ( $crawler_run_interval > 0 ) :
	$recurrence = '';
	$hours      = (int) floor( $crawler_run_interval / 3600 );
	if ( $hours ) {
		$recurrence .= sprintf(
			$hours > 1 ? esc_html__( '%d hours', 'litespeed-cache' ) : esc_html__( '%d hour', 'litespeed-cache' ),
			$hours
		);
	}
	$minutes = (int) floor( ( $crawler_run_interval % 3600 ) / 60 );
	if ( $minutes ) {
		$recurrence .= ' ';
		$recurrence .= sprintf(
			$minutes > 1 ? esc_html__( '%d minutes', 'litespeed-cache' ) : esc_html__( '%d minute', 'litespeed-cache' ),
			$minutes
		);
	}
?>

	<h3 class="litespeed-title litespeed-relative">
		<?php esc_html_e( 'Crawler Cron', 'litespeed-cache' ); ?>
		<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/crawler/' ); ?>
	</h3>

	<?php if ( ! Router::can_crawl() ) : ?>
		<div class="litespeed-callout notice notice-error inline">
			<h4><?php esc_html_e( 'WARNING', 'litespeed-cache' ); ?></h4>
			<p><?php esc_html_e( 'The crawler feature is not enabled on the LiteSpeed server. Please consult your server admin or hosting provider.', 'litespeed-cache' ); ?></p>
			<p>
				<?php
				printf(
					/* translators: %s: Link tags */
					esc_html__( 'See %sIntroduction for Enabling the Crawler%s for detailed information.', 'litespeed-cache' ),
					'<a href="https://docs.litespeedtech.com/lscache/lscwp/admin/#enabling-and-limiting-the-crawler" target="_blank" rel="noopener">',
					'</a>'
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( $summary['this_full_beginning_time'] ) : ?>
		<p>
			<b><?php esc_html_e( 'Current sitemap crawl started at', 'litespeed-cache' ); ?>:</b>
			<?php echo esc_html( Utility::readable_time( $summary['this_full_beginning_time'] ) ); ?>
		</p>
		<?php if ( ! $is_running ) : ?>
			<p>
				<b><?php esc_html_e( 'The next complete sitemap crawl will start at', 'litespeed-cache' ); ?>:</b>
				<?php echo esc_html( gmdate( 'm/d/Y H:i:s', $summary['this_full_beginning_time'] + LITESPEED_TIME_OFFSET + (int) $summary['last_full_time_cost'] + $this->conf( Base::O_CRAWLER_CRAWL_INTERVAL ) ) ); ?>
			</p>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( $summary['last_full_time_cost'] ) : ?>
		<p>
			<b><?php esc_html_e( 'Last complete run time for all crawlers', 'litespeed-cache' ); ?>:</b>
			<?php printf( esc_html__( '%d seconds', 'litespeed-cache' ), (int) $summary['last_full_time_cost'] ); ?>
		</p>
	<?php endif; ?>

	<?php if ( $summary['last_crawler_total_cost'] ) : ?>
		<p>
			<b><?php esc_html_e( 'Run time for previous crawler', 'litespeed-cache' ); ?>:</b>
			<?php printf( esc_html__( '%d seconds', 'litespeed-cache' ), (int) $summary['last_crawler_total_cost'] ); ?>
		</p>
	<?php endif; ?>

	<?php if ( $summary['curr_crawler_beginning_time'] ) : ?>
		<p>
			<b><?php esc_html_e( 'Current crawler started at', 'litespeed-cache' ); ?>:</b>
			<?php echo esc_html( Utility::readable_time( $summary['curr_crawler_beginning_time'] ) ); ?>
		</p>
	<?php endif; ?>

	<p>
		<b><?php esc_html_e( 'Current server load', 'litespeed-cache' ); ?>:</b>
		<?php echo esc_html( $__crawler->get_server_load() ); ?>
	</p>

	<?php if ( $summary['last_start_time'] ) : ?>
		<p class="litespeed-desc">
			<b><?php esc_html_e( 'Last interval', 'litespeed-cache' ); ?>:</b>
			<?php echo esc_html( Utility::readable_time( $summary['last_start_time'] ) ); ?>
		</p>
	<?php endif; ?>

	<?php if ( $summary['end_reason'] ) : ?>
		<p class="litespeed-desc">
			<b><?php esc_html_e( 'Ended reason', 'litespeed-cache' ); ?>:</b>
			<?php echo esc_html( $summary['end_reason'] ); ?>
		</p>
	<?php endif; ?>

	<?php if ( $summary['last_crawled'] ) : ?>
		<p class="litespeed-desc">
			<b><?php esc_html_e( 'Last crawled', 'litespeed-cache' ); ?>:</b>
			<?php
			printf(
				esc_html__( '%d item(s)', 'litespeed-cache' ),
				esc_html( $summary['last_crawled'] )
			);
			?>
		</p>
	<?php endif; ?>

	<p>
		<a href="<?php echo esc_url( Utility::build_url( Router::ACTION_CRAWLER, Crawler::TYPE_RESET ) ); ?>" class="button litespeed-btn-warning"><?php esc_html_e( 'Reset position', 'litespeed-cache' ); ?></a>
		<a href="<?php echo Router::can_crawl() ? esc_url( Utility::build_url( Router::ACTION_CRAWLER, Crawler::TYPE_START ) ) : 'javascript:;'; ?>" id="litespeed_manual_trigger" class="button litespeed-btn-success" litespeed-accesskey="R" <?php echo wp_kses_post( $disabled ); ?>><?php esc_html_e( 'Manually run', 'litespeed-cache' ); ?></a>
		<?php echo wp_kses_post( $disabled_tip ); ?>
	</p>

	<div class="litespeed-table-responsive">
		<table class="wp-list-table widefat striped" data-crawler-list>
			<thead>
				<tr>
					<th scope="col">#</th>
					<th scope="col"><?php esc_html_e( 'Cron Name', 'litespeed-cache' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Run Frequency', 'litespeed-cache' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Status', 'litespeed-cache' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Activate', 'litespeed-cache' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Running', 'litespeed-cache' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $crawler_list as $i => $v ) :
					$hit          = ! empty( $summary['crawler_stats'][ $i ][ Crawler::STATUS_HIT ] ) ? (int) $summary['crawler_stats'][ $i ][ Crawler::STATUS_HIT ] : 0;
					$miss         = ! empty( $summary['crawler_stats'][ $i ][ Crawler::STATUS_MISS ] ) ? (int) $summary['crawler_stats'][ $i ][ Crawler::STATUS_MISS ] : 0;
					$blacklisted  = ! empty( $summary['crawler_stats'][ $i ][ Crawler::STATUS_BLACKLIST ] ) ? (int) $summary['crawler_stats'][ $i ][ Crawler::STATUS_BLACKLIST ] : 0;
					$blacklisted += ! empty( $summary['crawler_stats'][ $i ][ Crawler::STATUS_NOCACHE ] ) ? (int) $summary['crawler_stats'][ $i ][ Crawler::STATUS_NOCACHE ] : 0;
					$waiting      = isset( $summary['crawler_stats'][ $i ][ Crawler::STATUS_WAIT ] )
						? (int) $summary['crawler_stats'][ $i ][ Crawler::STATUS_WAIT ]
						: (int) ( $summary['list_size'] - $hit - $miss - $blacklisted );
				?>
					<tr>
						<td>
							<?php
							echo esc_html( $i + 1 );
							if ( $i === $summary['curr_crawler'] ) {
								echo '<img class="litespeed-crawler-curr" src="' . esc_url( LSWCP_PLUGIN_URL . 'assets/img/Litespeed.icon.svg' ) . '" alt="Current Crawler">';
							}
							?>
						</td>
						<td><?php echo wp_kses_post( $v['title'] ); ?></td>
						<td><?php echo esc_html( $recurrence ); ?></td>
						<td>
							<?php
							printf(
								'<i class="litespeed-badge litespeed-bg-default" data-balloon-pos="up" aria-label="%s">%s</i> ',
								esc_attr__( 'Waiting', 'litespeed-cache' ),
								esc_html( $waiting > 0 ? $waiting : '-' )
							);
							printf(
								'<i class="litespeed-badge litespeed-bg-success" data-balloon-pos="up" aria-label="%s">%s</i> ',
								esc_attr__( 'Hit', 'litespeed-cache' ),
								esc_html( $hit > 0 ? $hit : '-' )
							);
							printf(
								'<i class="litespeed-badge litespeed-bg-primary" data-balloon-pos="up" aria-label="%s">%s</i> ',
								esc_attr__( 'Miss', 'litespeed-cache' ),
								esc_html( $miss > 0 ? $miss : '-' )
							);
							printf(
								'<i class="litespeed-badge litespeed-bg-danger" data-balloon-pos="up" aria-label="%s">%s</i> ',
								esc_attr__( 'Blocklisted', 'litespeed-cache' ),
								esc_html( $blacklisted > 0 ? $blacklisted : '-' )
							);
							?>
						</td>
						<td>
							<?php $this->build_toggle( 'litespeed-crawler-' . $i, $__crawler->is_active( $i ) ); ?>
							<?php if ( ! empty( $v['uid'] ) && empty( $this->conf( Base::O_SERVER_IP ) ) ) : ?>
								<div class="litespeed-danger litespeed-text-bold">
									🚨 <?php esc_html_e( 'NOTICE', 'litespeed-cache' ); ?>:
									<?php
									printf(
										esc_html__( 'You must set %s before using this feature.', 'litespeed-cache' ),
										esc_html( Lang::title( Base::O_SERVER_IP ) )
									);
									?>
									<?php
									Doc::learn_more(
										esc_url( admin_url( 'admin.php?page=litespeed-general#settings' ) ),
										esc_html__( 'Click here to set.', 'litespeed-cache' ),
										true,
										false,
										true
									);
									?>
								</div>
							<?php endif; ?>
						</td>
						<td>
							<?php
							if ( $i === $summary['curr_crawler'] ) {
								echo esc_html__( 'Position: ', 'litespeed-cache' ) . esc_html( $summary['last_pos'] + 1 );
								if ( $is_running ) {
									echo ' <span class="litespeed-label-success">' . esc_html__( 'running', 'litespeed-cache' ) . '</span>';
								}
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<p>
		<i class="litespeed-badge litespeed-bg-default"></i> = <?php esc_html_e( 'Waiting to be Crawled', 'litespeed-cache' ); ?><br>
		<i class="litespeed-badge litespeed-bg-success"></i> = <?php esc_html_e( 'Already Cached', 'litespeed-cache' ); ?><br>
		<i class="litespeed-badge litespeed-bg-primary"></i> = <?php esc_html_e( 'Successfully Crawled', 'litespeed-cache' ); ?><br>
		<i class="litespeed-badge litespeed-bg-danger"></i> = <?php esc_html_e( 'Blocklisted', 'litespeed-cache' ); ?><br>
	</p>

	<div class="litespeed-desc">
		<div><?php esc_html_e( 'Run frequency is set by the Interval Between Runs setting.', 'litespeed-cache' ); ?></div>
		<div>
			<?php
			esc_html_e( 'Crawlers cannot run concurrently. If both the cron and a manual run start at similar times, the first to be started will take precedence.', 'litespeed-cache' );
			?>
		</div>
		<div>
			<?php
			printf(
				/* translators: %s: Link tags */
				esc_html__( 'Please see %sHooking WP-Cron Into the System Task Scheduler%s to learn how to create the system cron task.', 'litespeed-cache' ),
				'<a href="https://developer.wordpress.org/plugins/cron/hooking-wp-cron-into-the-system-task-scheduler/" target="_blank" rel="noopener">',
				'</a>'
			);
			?>
		</div>
	</div>
<?php
endif;
?>

<h3 class="litespeed-title"><?php esc_html_e( 'Watch Crawler Status', 'litespeed-cache' ); ?></h3>

<?php
$ajax_url = $__crawler->json_path();
if ( $ajax_url ) :
?>
	<input type="button" id="litespeed-crawl-url-btn" value="<?php esc_attr_e( 'Show crawler status', 'litespeed-cache' ); ?>" class="button button-secondary" data-url="<?php echo esc_url( $ajax_url ); ?>" />
	<div class="litespeed-shell litespeed-hide">
		<div class="litespeed-shell-header-bar"></div>
		<div class="litespeed-shell-header">
			<div class="litespeed-shell-header-bg"></div>
			<div class="litespeed-shell-header-icon-container">
				<img id="litespeed-shell-icon" src="<?php echo esc_url( LSWCP_PLUGIN_URL . 'assets/img/Litespeed.icon.svg' ); ?>" alt="LiteSpeed Icon" />
			</div>
		</div>
		<ul class="litespeed-shell-body">
			<li><?php esc_html_e( 'Start watching...', 'litespeed-cache' ); ?></li>
			<li id="litespeed-loading-dot"></li>
		</ul>
	</div>
<?php else : ?>
	<p><?php esc_html_e( 'No crawler meta file generated yet', 'litespeed-cache' ); ?></p>
<?php endif; ?>

<script>
var _litespeed_meta;
var _litespeed_shell_interval = 3; // seconds
var _litespeed_shell_interval_range = [3, 60];
var _litespeed_shell_handle;
var _litespeed_shell_display_handle;
var _litespeed_crawler_url;
var _litespeed_dots;


(function ($) {
	'use strict';
	jQuery(document).ready(function () {
		$('#litespeed-crawl-url-btn').on('click', function () {
			if (!$(this).data('url')) {
				return false;
			}
			$('.litespeed-shell').removeClass('litespeed-hide');
			_litespeed_dots = window.setInterval(_litespeed_loading_dots, 300);
			_litespeed_crawler_url = $(this).data('url');
			litespeed_fetch_meta();
			$(this).hide();
		});

		$('#litespeed_manual_trigger').on('click', function (event) {
			$('#litespeed-loading-dot').before('<li>Manually Started</li>');
			_litespeed_shell_interval = _litespeed_shell_interval_range[0];
			litespeed_fetch_meta();
		});

		/**
		 * Freeze or melt a specific crawler
		 * @since  4.3
		 */
		if ($('[data-crawler-list] [data-litespeed_toggle_id]').length > 0) {
			$('[data-crawler-list] [data-litespeed_toggle_id]').on('click', function (e) {
				var crawler_id = $(this).attr('data-litespeed_toggle_id');
				var crawler_id = Number(crawler_id.split('-').pop());
				var that = this;
				$.ajax({
					url: '<?php echo function_exists('get_rest_url') ? get_rest_url(null, 'litespeed/v1/toggle_crawler_state') : '/'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>',
					dataType: 'json',
					method: 'POST',
					cache: false,
					data: { crawler_id: crawler_id },
					beforeSend: function (xhr) {
						xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_js( wp_create_nonce('wp_rest') ); ?>');
					},
					success: function (data) {
						$(that)
							.toggleClass('litespeed-toggle-btn-default litespeed-toggleoff', data == 0)
							.toggleClass('litespeed-toggle-btn-primary', data == 1);
						console.log('litespeed-crawler-ajax: change Activate option');
					},
					error: function (xhr, error) {
						console.log(xhr);
						console.log(error);
						console.log('litespeed-crawler-ajax: option failed to save due to some error');
					},
				});
			});
		}

	});
})(jQuery);


function litespeed_fetch_meta() {
	window.clearTimeout(_litespeed_shell_handle);
	jQuery('#litespeed-loading-dot').text('');
	jQuery.ajaxSetup({ cache: false });
	jQuery.getJSON(_litespeed_crawler_url, function (meta) {
		litespeed_pulse();
		var changed = false;
		if (meta && 'list_size' in meta) {
			new_meta =
				meta.list_size + ' ' + meta.file_time + ' ' + meta.curr_crawler + ' ' + meta.last_pos + ' ' + meta.last_count + ' ' + meta.last_start_time + ' ' + meta.is_running;
			if (new_meta != _litespeed_meta) {
				_litespeed_meta = new_meta;
				changed = true;
				string = _litespeed_build_meta(meta);
				jQuery('#litespeed-loading-dot').before(string);
				// remove first log elements
				log_length = jQuery('.litespeed-shell-body li').length;
				if (log_length > 50) {
					jQuery('.litespeed-shell-body li:lt(' + (log_length - 50) + ')').remove();
				}
				// scroll to end
				jQuery('.litespeed-shell-body')
					.stop()
					.animate(
						{
							scrollTop: jQuery('.litespeed-shell-body')[0].scrollHeight,
						},
						800,
					);
			}

			// dynamic adjust the interval length
			_litespeed_adjust_interval(changed);
		}
		// display interval counting
		litespeed_display_interval_reset();
		_litespeed_shell_handle = window.setTimeout(_litespeed_dynamic_timeout, _litespeed_shell_interval * 1000);
	});
}

function _litespeed_loading_dots() {
	jQuery('#litespeed-loading-dot').append('.');
}

/**
 * Dynamic adjust interval
 */
function _litespeed_adjust_interval(changed) {
	if (changed) {
		_litespeed_shell_interval -= Math.ceil(_litespeed_shell_interval / 2);
	} else {
		_litespeed_shell_interval++;
	}

	if (_litespeed_shell_interval < _litespeed_shell_interval_range[0]) {
		_litespeed_shell_interval = _litespeed_shell_interval_range[0];
	}
	if (_litespeed_shell_interval > _litespeed_shell_interval_range[1]) {
		_litespeed_shell_interval = _litespeed_shell_interval_range[1];
	}
}

function _litespeed_build_meta(meta) {
	var string =
		'<li>' +
		litespeed_date(meta.last_update_time) +
		'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Size: ' +
		meta.list_size +
		'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Crawler: #' +
		(meta.curr_crawler * 1 + 1) +
		'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Position: ' +
		(meta.last_pos * 1 + 1) +
		'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Threads: ' +
		meta.last_count +
		'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Status: ';
	if (meta.is_running) {
		string += 'crawling, ' + meta.last_status;
	} else {
		string += meta.end_reason ? meta.end_reason : '-';
	}
	string += '</li>';
	return string;
}

function _litespeed_dynamic_timeout() {
	litespeed_fetch_meta();
}

function litespeed_display_interval_reset() {
	window.clearInterval(_litespeed_shell_display_handle);
	jQuery('.litespeed-shell-header-bar').data('num', _litespeed_shell_interval);
	_litespeed_shell_display_handle = window.setInterval(_litespeed_display_interval, 1000);

	jQuery('.litespeed-shell-header-bar')
		.stop()
		.animate({ width: '100%' }, 500, function () {
			jQuery('.litespeed-shell-header-bar').css('width', '0%');
		});
}

function _litespeed_display_interval() {
	var num = jQuery('.litespeed-shell-header-bar').data('num');
	jQuery('.litespeed-shell-header-bar')
		.stop()
		.animate({ width: litespeed_get_percent(num, _litespeed_shell_interval) + '%' }, 1000);
	if (num > 0) num--;
	if (num < 0) num = 0;
	jQuery('.litespeed-shell-header-bar').data('num', num);
}

function litespeed_get_percent(num1, num2) {
	num1 = num1 * 1;
	num2 = num2 * 1;
	num = (num2 - num1) / num2;
	return num * 100;
}

function litespeed_date(timestamp) {
	var a = new Date(timestamp * 1000);
	var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
	var year = a.getFullYear();
	var month = months[a.getMonth()];
	var date = litespeed_add_zero(a.getDate());
	var hour = litespeed_add_zero(a.getHours());
	var min = litespeed_add_zero(a.getMinutes());
	var sec = litespeed_add_zero(a.getSeconds());
	var time = date + ' ' + month + ' ' + year + ' ' + hour + ':' + min + ':' + sec;
	return time;
}

function litespeed_add_zero(i) {
	if (i < 10) {
		i = '0' + i;
	}
	return i;
}

function litespeed_pulse() {
	jQuery('#litespeed-shell-icon').animate(
		{
			width: 27,
			height: 34,
			opacity: 1,
		},
		700,
		function () {
			jQuery('#litespeed-shell-icon').animate(
				{
					width: 23,
					height: 29,
					opacity: 0.5,
				},
				700,
			);
		},
	);
}

</script>