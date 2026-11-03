<?php
/**
 * LiteSpeed Cache Guest Mode Setting
 *
 * Manages the Guest Mode setting for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$guest_update_url = wp_parse_url( LSWCP_PLUGIN_URL . GUI::PHP_GUEST, PHP_URL_PATH );

?>
	<tr>
		<th>
			<?php $option_id = Base::O_GUEST; ?>
			<?php $this->title( $option_id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $option_id ); ?>
			<div class="litespeed-desc">
				<?php esc_html_e( "Guest Mode provides an always cacheable landing page for an automated guest's first time visit, and then attempts to update cache varies via AJAX.", 'litespeed-cache' ); ?>
				<?php esc_html_e( 'This option can help to correct the cache vary for certain advanced mobile or tablet visitors.', 'litespeed-cache' ); ?>
				<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/general/#guest-mode' ); ?>
				<br /><?php Doc::notice_htaccess(); ?>
				<br /><?php Doc::crawler_affected(); ?>
			</div>
			<?php if ( $this->conf( $option_id ) ) : ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Guest Mode testing result', 'litespeed-cache' ); ?>:
					<font id='litespeed_gm_status'><?php esc_html_e( 'Testing', 'litespeed-cache' ); ?>...</font>
				</div>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Guest Mode IP/UA sync status', 'litespeed-cache' ); ?>:
					<font id='litespeed_gm_sync_status'><?php esc_html_e( 'Syncing', 'litespeed-cache' ); ?>...</font>
				</div>
				<script>
					(function ($) {
						jQuery(document).ready(function () {
							$.post( <?php echo wp_json_encode( $guest_update_url, Str::JSON_HTML_FLAGS ); ?>, function(data){
								if ( data === '[]' || data === '{"reload":"yes"}' ) {
									$('#litespeed_gm_status').html('<font class="litespeed-success"><?php echo esc_js( __( 'Guest Mode passed testing.', 'litespeed-cache' ) ); ?></font>');
								}
								else {
									$('#litespeed_gm_status').html('<font class="litespeed-danger"><?php echo esc_js( __( 'Guest Mode failed to test.', 'litespeed-cache' ) ); ?></font>');
								}
							}).fail( function(){
								$('#litespeed_gm_status').html('<font class="litespeed-danger"><?php echo esc_js( __( 'Guest Mode failed to test.', 'litespeed-cache' ) ); ?></font>');
							});

							// Sync Guest Mode IP/UA lists
							$.ajax({
								url: <?php echo wp_json_encode( rest_url( 'litespeed/v1/guest/sync' ), Str::JSON_HTML_FLAGS ); ?>,
								dataType: 'json',
								beforeSend: function(xhr) {
									xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>');
								},
								success: function(data) {
									if (data && data.success) {
										$('#litespeed_gm_sync_status').html('<font class="litespeed-success"><?php echo esc_js( __( 'Synced successfully.', 'litespeed-cache' ) ); ?></font>');
									} else {
										$('#litespeed_gm_sync_status').html('<font class="litespeed-warning"><?php echo esc_js( __( 'Sync failed.', 'litespeed-cache' ) ); ?></font>');
									}
								},
								error: function() {
									$('#litespeed_gm_sync_status').html('<font class="litespeed-warning"><?php echo esc_js( __( 'Sync failed.', 'litespeed-cache' ) ); ?></font>');
								}
							});
						});
					})(jQuery);
				</script>
			<?php endif; ?>
		</td>
	</tr>
