<?php
// phpcs:ignoreFile

namespace LiteSpeed\Thirdparty;

defined( 'WPINC' ) || exit;

use LiteSpeed\API;
use LiteSpeed\Doc;
use LiteSpeed\Admin_Display;
use LiteSpeed\Lang;
use LiteSpeed\Base;
?>

<div data-litespeed-layout='woocommerce'>

	<h3 class="litespeed-title-short">
		<?php echo __( 'WooCommerce Settings', 'litespeed-cache' ); ?>
		<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cache/#woocommerce-tab' ); ?>
	</h3>

	<div class="litespeed-callout notice notice-warning inline">
		<h4><?php echo __( 'NOTICE:', 'litespeed-cache' ); ?></h4>
		<p><?php echo __( 'After verifying that the cache works in general, please test the cart.', 'litespeed-cache' ); ?></p>
		<p><?php printf( __( 'To test the cart, visit the <a %s>FAQ</a>.', 'litespeed-cache' ), 'href="https://docs.litespeedtech.com/lscache/lscwp/installation/#non-cacheable-pages" target="_blank"' ); ?></p>
		<p><?php echo __( 'By default, the My Account, Checkout, and Cart pages are automatically excluded from caching. Misconfiguration of page associations in WooCommerce settings may cause some pages to be erroneously excluded.', 'litespeed-cache' ); ?></p>
	</div>

	<table class="wp-list-table striped litespeed-table">
		<tbody>
			<tr>
				<th>
					<?php $id = self::O_UPDATE_INTERVAL; ?>
					<?php echo __( 'Product Update Interval', 'litespeed-cache' ); ?>
				</th>
				<td>
					<?php
					$options = array(
						self::O_PQS_CS  => __( 'Purge product on changes to the quantity or stock status.', 'litespeed-cache' ) . ' ' . __( 'Purge categories only when stock status changes.', 'litespeed-cache' ),
						self::O_PS_CS   => __( 'Purge product and categories only when the stock status changes.', 'litespeed-cache' ),
						self::O_PS_CN   => __( 'Purge product only when the stock status changes.', 'litespeed-cache' ) . ' ' . __( 'Do not purge categories on changes to the quantity or stock status.', 'litespeed-cache' ),
						self::O_PQS_CQS => __( 'Always purge both product and categories on changes to the quantity or stock status.', 'litespeed-cache' ),
					);
					$conf    = (int) apply_filters( 'litespeed_conf', $id );
					foreach ( $options as $k => $v ) :
						$checked = (int) $k === $conf ? ' checked ' : '';
						?>
						<?php do_action( 'litespeed_setting_enroll', $id ); ?>
						<div class='litespeed-radio-row'>
							<input type='radio' autocomplete='off' name='<?php echo $id; ?>' id='conf_<?php echo $id; ?>_<?php echo $k; ?>' value='<?php echo $k; ?>' <?php echo $checked; ?> />
							<label for='conf_<?php echo $id; ?>_<?php echo $k; ?>'><?php echo $v; ?></label>
						</div>
					<?php endforeach; ?>
					<div class="litespeed-desc">
						<?php echo __( 'Determines how changes in product quantity and product stock status affect product pages and their associated category pages.', 'litespeed-cache' ); ?>
					</div>
				</td>
			</tr>

			<tr>
				<th>
					<?php $id = self::O_CART_VARY; ?>
					<?php echo __( 'Vary for Mini Cart', 'litespeed-cache' ); ?>
				</th>
				<td>
					<?php
					$conf = (int) apply_filters( 'litespeed_conf', $id );
					$this->cls( 'Admin_Display' )->build_switch( $id );
					?>
					<div class="litespeed-desc">
						<?php echo __( 'Generate a separate vary cache copy for the mini cart when the cart is not empty.', 'litespeed-cache' ); ?>
						<?php echo __( 'If your theme does not use JS to update the mini cart, you must enable this option to display the correct cart contents.', 'litespeed-cache' ); ?>
						<br /><?php Doc::notice_htaccess(); ?>
					</div>
				</td>
			</tr>

		</tbody>
	</table>
</div>