<?php
namespace LiteSpeed\Thirdparty;

defined( 'WPINC' ) || exit;

use \LiteSpeed\API;
use \LiteSpeed\Doc;
use \LiteSpeed\Admin_Display;
?>

<div data-litespeed-layout='woocommerce'>

<h3 class="litespeed-title-short">
	<?php echo __( 'WooCommerce Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cache/#woocommerce-tab' ); ?>
</h3>

<div class="litespeed-callout notice notice-warning inline">
	<h4><?php echo __( 'NOTICE:', 'litespeed-cache' ); ?></h4>
	<p><?php echo __( 'After verifying that the cache works in general, please test the cart.', 'litespeed-cache' ); ?></p>
	<p><?php echo sprintf( __( 'To test the cart, visit the <a %s>FAQ</a>.', 'litespeed-cache' ), 'href="https://docs.litespeedtech.com/lscache/lscwp/installation/#testing" target="_blank"' ); ?></p>
</div>

<table class="wp-list-table striped litespeed-table"><tbody>
	<tr>
		<th>
			<?php $id = self::O_UPDATE_INTERVAL; ?>
			<?php echo __( 'Product Update Interval', 'litespeed-cache' ); ?>
		</th>
		<td>
			<?php
			$options = array(
				self::O_PQS_CS	=> __( 'Purge product on changes to the quantity or stock status.', 'litespeed-cache' ) . ' ' . __( 'Purge categories only when stock status changes.', 'litespeed-cache' ),
				self::O_PS_CS	=> __( 'Purge product and categories only when the stock status changes.', 'litespeed-cache' ),
				self::O_PS_CN	=> __( 'Purge product only when the stock status changes.', 'litespeed-cache' ) . ' ' . __( 'Do not purge categories on changes to the quantity or stock status.', 'litespeed-cache' ),
				self::O_PQS_CQS	=> __( 'Always purge both product and categories on changes to the quantity or stock status.', 'litespeed-cache' ),
			);
			$conf = (int) apply_filters( 'litespeed_conf', $id );
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
			<?php $id = self::O_SHOP_FRONT_TTL; ?>
			<?php echo __( 'Use Front Page TTL for the Shop Page', 'litespeed-cache' ); ?>
		</th>
		<td>
			<?php do_action( 'litespeed_build_switch', $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Checking this option will force the shop page to use the front page TTL setting.', 'litespeed-cache' ); ?>
				<?php echo sprintf( __( 'For example, if the homepage for the site is located at %1$s, the shop page may be located at %2$s.', 'litespeed-cache' ), 'https://www.EXAMPLE.com', 'https://www.EXAMPLE.com/shop' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = self::O_WOO_CACHE_CART; ?>
			<?php echo __( 'Privately Cache Cart', 'litespeed-cache' ); ?>
		</th>
		<td>
			<?php do_action( 'litespeed_build_switch', $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Privately cache cart when not empty.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

</tbody></table>

</div>