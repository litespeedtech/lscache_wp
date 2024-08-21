<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$guest_update_url = parse_url( LSWCP_PLUGIN_URL . GUI::PHP_GUEST, PHP_URL_PATH );

?>
	<tr>
		<th>
			<?php $id = Base::O_GUEST; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Guest Mode provides an always cacheable landing page for an automated guest\'s first time visit, and then attempts to update cache varies via AJAX.', 'litespeed-cache' ); ?>
				<?php echo __( 'This option can help to correct the cache vary for certain advanced mobile or tablet visitors.', 'litespeed-cache' ); ?>
				<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/general/#guest-mode' ); ?>
				<br /><?php Doc::notice_htaccess(); ?>
				<br /><?php Doc::crawler_affected(); ?>
			</div>
			<?php if ( $this->conf( $id ) ) : ?>
				<div class="litespeed-desc">
					<?php echo __( 'Guest Mode testing result', 'litespeed-cache' ); ?>:
					<font id='litespeed_gm_status'><?php echo __( 'Testing', 'litespeed-cache' ); ?>...</font>
				</div>
				<script>
					(function ($) {
						jQuery(document).ready(function () {
							$.post( '<?php echo $guest_update_url; ?>', function(data){
								if ( data == '[]' || $data == '{"reload":"yes"}' ) {
									$('#litespeed_gm_status').html('<font class="litespeed-success"><?php echo __( 'Guest Mode passed testing.', 'litespeed-cache' ); ?></font>');
								}
								else {
									$('#litespeed_gm_status').html('<font class="litespeed-danger"><?php echo __( 'Guest Mode failed to test.', 'litespeed-cache' ); ?></font>');
								}
							}).fail( function(){
								$('#litespeed_gm_status').html('<font class="litespeed-danger"><?php echo __( 'Guest Mode failed to test.', 'litespeed-cache' ); ?></font>');
							})
						});
					})(jQuery);
				</script>
			<?php endif; ?>
		</td>
	</tr>

