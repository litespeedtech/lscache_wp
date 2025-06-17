<?php
/**
 * LiteSpeed Cache Browser Cache Settings
 *
 * Displays the browser cache settings section for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;
?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'Browser Cache Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cache/#browser-tab' ); ?>
</h3>

<?php if ( 'LITESPEED_SERVER_OLS' === LITESPEED_SERVER_TYPE ) : ?>
	<div class="litespeed-callout notice notice-warning inline">
		<h4><?php esc_html_e( 'NOTICE:', 'litespeed-cache' ); ?></h4>
		<p>
			<?php esc_html_e( 'OpenLiteSpeed users please check this', 'litespeed-cache' ); ?>:
			<?php Doc::learn_more( 'https://openlitespeed.org/kb/how-to-set-up-custom-headers/', esc_html__( 'Setting Up Custom Headers', 'litespeed-cache' ) ); ?>
		</p>
	</div>
<?php endif; ?>

<table class="wp-list-table striped litespeed-table">
	<tbody>
		<tr>
			<th scope="row">
				<?php $option_id = Base::O_CACHE_BROWSER; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( "Browser caching stores static files locally in the user's browser. Turn on this setting to reduce repeated requests for static files.", 'litespeed-cache' ); ?><br>
					<?php Doc::notice_htaccess(); ?><br>
					<?php
					printf(
						/* translators: %1$s: Opening link tag, %2$s: Closing link tag */
						esc_html__( 'You can turn on browser caching in server admin too. %1$sLearn more about LiteSpeed browser cache settings%2$s.', 'litespeed-cache' ),
						'<a href="https://docs.litespeedtech.com/lscache/lscwp/cache/#how-to-set-it-up" target="_blank" rel="noopener">',
						'</a>'
					);
					?>
				</div>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php $option_id = Base::O_CACHE_TTL_BROWSER; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_input( $option_id ); ?> <?php $this->readable_seconds(); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'The amount of time, in seconds, that files will be stored in browser cache before expiring.', 'litespeed-cache' ); ?>
					<?php $this->recommended( $option_id ); ?>
					<?php $this->_validate_ttl( $option_id, 30 ); ?>
				</div>
			</td>
		</tr>
	</tbody>
</table>
