<?php
/**
 * LiteSpeed Cache Mobile View Settings
 *
 * Displays the mobile view cache settings for LiteSpeed Cache.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;
?>

<tr>
	<th scope="row">
		<?php $cid = Base::O_CACHE_MOBILE; ?>
		<?php $this->title( $cid ); ?>
	</th>
	<td>
		<?php $this->build_switch( $cid ); ?>
		<div class="litespeed-desc">
			<?php esc_html_e( 'Serve a separate cache copy for mobile visitors.', 'litespeed-cache' ); ?>
			<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/cache/#cache-mobile', esc_html__( 'Learn more about when this is needed', 'litespeed-cache' ) ); ?>
			<br /><?php Doc::notice_htaccess(); ?>
			<br /><?php Doc::crawler_affected(); ?>
		</div>
	</td>
</tr>

<tr>
	<th scope="row" class="litespeed-padding-left">
		<?php $cid = Base::O_CACHE_MOBILE_RULES; ?>
		<?php $this->title( $cid ); ?>
	</th>
	<td>
		<?php
		if ( $this->conf( Base::O_CACHE_MOBILE ) ) {
			if ( defined( 'LITESPEED_ON' ) ) {
				try {
					$mobile_agents = Htaccess::cls()->current_mobile_agents();
					if ( Utility::arr2regex( $this->conf( $cid ), true ) !== $mobile_agents ) {
						?>
						<div class="litespeed-callout notice notice-error inline">
							<p>
								<?php esc_html_e( 'Htaccess did not match configuration option.', 'litespeed-cache' ); ?>
								<?php
								printf(
									/* translators: %s: Current mobile agents in htaccess */
									esc_html__( 'Htaccess rule is: %s', 'litespeed-cache' ),
									'<code>' . esc_html( $mobile_agents ) . '</code>'
								);
								?>
							</p>
						</div>
						<?php
					}
				} catch ( \Exception $e ) {
					?>
					<div class="litespeed-callout notice notice-error inline">
						<p><?php echo wp_kses_post( $e->getMessage() ); ?></p>
					</div>
					<?php
				}
			}
		}
		?>

		<div class="litespeed-textarea-recommended">
			<div>
				<?php $this->build_textarea( $cid, 40 ); ?>
			</div>
			<div>
				<?php $this->recommended( $cid ); ?>
			</div>
		</div>

		<div class="litespeed-desc">
			<?php Doc::one_per_line(); ?>
			<?php $this->_validate_syntax( $cid ); ?>

			<?php if ( $this->conf( Base::O_CACHE_MOBILE ) && ! $this->conf( $cid ) ) : ?>
				<span class="litespeed-warning">
					❌
					<?php
					printf(
						/* translators: %1$s: Cache Mobile label, %2$s: ON status, %3$s: List of Mobile User Agents label */
						esc_html__( 'If %1$s is %2$s, then %3$s must be populated!', 'litespeed-cache' ),
						'<code>' . esc_html__( 'Cache Mobile', 'litespeed-cache' ) . '</code>',
						'<code>' . esc_html__( 'ON', 'litespeed-cache' ) . '</code>',
						'<code>' . esc_html__( 'List of Mobile User Agents', 'litespeed-cache' ) . '</code>'
					);
					?>
				</span>
			<?php endif; ?>
		</div>
	</td>
</tr>