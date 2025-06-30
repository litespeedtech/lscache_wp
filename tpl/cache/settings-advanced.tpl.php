<?php
/**
 * Advanced Settings Template
 *
 * @package     LiteSpeed
 * @since       1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'Advanced Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( esc_url( 'https://docs.litespeedtech.com/lscache/lscwp/cache/#advanced-tab' ) ); ?>
</h3>

<div class="litespeed-callout notice notice-warning inline">
	<h4><?php esc_html_e( 'NOTICE:', 'litespeed-cache' ); ?></h4>
	<p><?php esc_html_e( 'These settings are meant for ADVANCED USERS ONLY.', 'litespeed-cache' ); ?></p>
</div>

<table class="wp-list-table striped litespeed-table">
	<tbody>
		<tr>
			<th scope="row">
				<?php $option_id = Base::O_CACHE_AJAX_TTL; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<div class="litespeed-textarea-recommended">
					<div>
						<?php $this->build_textarea( $option_id, 60 ); ?>
					</div>
				</div>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Specify an AJAX action in POST/GET and the number of seconds to cache that request, separated by a space.', 'litespeed-cache' ); ?>
					<?php Doc::one_per_line(); ?>
				</div>
			</td>
		</tr>

		<?php if ( ! $this->_is_multisite ) : ?>
			<?php require LSCWP_DIR . 'tpl/cache/settings_inc.login_cookie.tpl.php'; ?>
		<?php endif; ?>

		<tr>
			<th scope="row">
				<?php $option_id = Base::O_UTIL_NO_HTTPS_VARY; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Enable this option if you are using both HTTP and HTTPS in the same domain and are noticing cache irregularities.', 'litespeed-cache' ); ?>
					<?php Doc::learn_more( esc_url( 'https://docs.litespeedtech.com/lscache/lscwp/cache/#improve-httphttps-compatibility' ) ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php $option_id = Base::O_UTIL_INSTANT_CLICK; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'When a visitor hovers over a page link, preload that page. This will speed up the visit to that link.', 'litespeed-cache' ); ?>
					<?php Doc::learn_more( esc_url( 'https://docs.litespeedtech.com/lscache/lscwp/cache/#instant-click' ) ); ?>
					<br />
					<span class="litespeed-danger">
					⚠️
						<?php esc_html_e( 'This will generate extra requests to the server, which will increase server load.', 'litespeed-cache' ); ?>
					</span>
				</div>
			</td>
		</tr>
	</tbody>
</table>