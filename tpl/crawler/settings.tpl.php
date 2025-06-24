<?php
/**
 * LiteSpeed Cache Crawler General Settings
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$this->form_action();
?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'Crawler General Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/crawler/#general-settings-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table">
	<tbody>
		<tr>
			<th>
				<?php $option_id = Base::O_CRAWLER; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'This will enable crawler cron.', 'litespeed-cache' ); ?>
					<br><?php Doc::notice_htaccess(); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_CRAWLER_CRAWL_INTERVAL; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_input( $option_id ); ?> <?php esc_html_e( 'seconds', 'litespeed-cache' ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Specify how long in seconds before the crawler should initiate crawling the entire sitemap again.', 'litespeed-cache' ); ?>
					<?php $this->recommended( $option_id ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_CRAWLER_SITEMAP; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_textarea( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'The crawler will use your XML sitemap or sitemap index. Enter the full URL to your sitemap here.', 'litespeed-cache' ); ?>
					<?php Doc::one_per_line(); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_CRAWLER_LOAD_LIMIT; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_input( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'The maximum average server load allowed while crawling. The number of crawler threads in use will be actively reduced until average server load falls under this limit. If this cannot be achieved with a single thread, the current crawler run will be terminated.', 'litespeed-cache' ); ?>
					<?php if ( ! empty( $_SERVER[ Base::ENV_CRAWLER_LOAD_LIMIT_ENFORCE ] ) ) : ?>
						<span class="litespeed-warning">
							<?php esc_html_e( 'NOTE', 'litespeed-cache' ); ?>:
							<?php
							printf(
								esc_html__( 'Server enforced value: %s', 'litespeed-cache' ),
								'<code>' . esc_html( sanitize_text_field( wp_unslash( $_SERVER[ Base::ENV_CRAWLER_LOAD_LIMIT_ENFORCE ] ) ) ) . '</code>'
							);
							?>
						</span>
					<?php elseif ( ! empty( $_SERVER[ Base::ENV_CRAWLER_LOAD_LIMIT ] ) ) : ?>
						<span class="litespeed-warning">
							<?php esc_html_e( 'NOTE', 'litespeed-cache' ); ?>:
							<?php
							printf(
								esc_html__( 'Server allowed max value: %s', 'litespeed-cache' ),
								'<code>' . esc_html( sanitize_text_field( wp_unslash( $_SERVER[ Base::ENV_CRAWLER_LOAD_LIMIT ] ) ) ) . '</code>'
							);
							?>
						</span>
					<?php endif; ?>
					<br>
					<?php $this->_api_env_var( Base::ENV_CRAWLER_LOAD_LIMIT, Base::ENV_CRAWLER_LOAD_LIMIT_ENFORCE ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_CRAWLER_ROLES; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_textarea( $option_id, 20 ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'To crawl the site as a logged-in user, enter the user ids to be simulated.', 'litespeed-cache' ); ?>
					<?php Doc::one_per_line(); ?>
					<?php if ( empty( $this->conf( Base::O_SERVER_IP ) ) ) : ?>
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
					<?php if ( empty( $this->conf( Base::O_ESI ) ) ) : ?>
						<div class="litespeed-danger litespeed-text-bold">
							🚨 <?php esc_html_e( 'NOTICE', 'litespeed-cache' ); ?>:
							<?php
							printf(
								esc_html__( 'You must set %1$s to %2$s before using this feature.', 'litespeed-cache' ),
								esc_html( Lang::title( Base::O_ESI ) ),
								esc_html__( 'ON', 'litespeed-cache' )
							);
							?>
							<?php
							Doc::learn_more(
								esc_url( admin_url( 'admin.php?page=litespeed-cache#esi' ) ),
								esc_html__( 'Click here to set.', 'litespeed-cache' ),
								true,
								false,
								true
							);
							?>
						</div>
					<?php endif; ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_CRAWLER_COOKIES; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->enroll( $option_id . '[name][]' ); ?>
				<?php $this->enroll( $option_id . '[vals][]' ); ?>
				<div id="litespeed_crawler_simulation_div"></div>
				<script type="text/babel">
					ReactDOM.render(
						<CrawlerSimulate list={ <?php echo wp_json_encode( $this->conf( $option_id ) ); ?> } />,
						document.getElementById( 'litespeed_crawler_simulation_div' )
					);
				</script>
				<div class="litespeed-desc">
					<?php esc_html_e( 'To crawl for a particular cookie, enter the cookie name, and the values you wish to crawl for. Values should be one per line. There will be one crawler created per cookie value, per simulated role.', 'litespeed-cache' ); ?>
					<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/crawler/#cookie-simulation' ); ?>
					<p>
						<?php
						printf(
							esc_html__( 'Use %1$s in %2$s to indicate this cookie has not been set.', 'litespeed-cache' ),
							'<code>_null</code>',
							esc_html__( 'Cookie Values', 'litespeed-cache' )
						);
						?>
					</p>
				</div>
			</td>
		</tr>
	</tbody>
</table>

<?php $this->form_end(); ?>