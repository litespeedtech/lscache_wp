<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

?>

	<!-- build_setting_mobile_view start -->
	<tr>
		<th>
			<?php $id = Base::O_CACHE_MOBILE; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php
				$this->build_switch( $id );
			?>
			<div class="litespeed-desc">
				<?php echo __( 'Cache mobile views separately.', 'litespeed-cache' ); ?>
				<?php echo __( 'Only enable for AMP or mobile-specific design/content, not for responsive sites.', 'litespeed-cache' ); ?>
				<br /><?php Doc::notice_htaccess(); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left">
			<?php $id = Base::O_CACHE_MOBILE_RULES; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
		<?php
			// if set, use value as input value
			if ( Conf::val( Base::O_CACHE_MOBILE ) ) {

				// if enabled, check the setting in file
				if ( defined( 'LITESPEED_ON' ) ) {

					try {
						$mobile_agents = Htaccess::get_instance()->current_mobile_agents();
						if ( $mobile_agents !== Utility::arr2regex( Conf::val( $id ), true ) ) {
							echo '<div class="litespeed-callout notice notice-error inline"><p>'
									. __( 'Htaccess did not match configuration option.', 'litespeed-cache' )
									. ' ' . sprintf( __( 'Htaccess rule is: %s', 'litespeed-cache' ), '<code>' . $mobile_agents . '</code>' )
								. '</p></div>';
						}
					} catch( \Exception $e ) {
						echo '<div class="litespeed-callout notice notice-error inline"><p>' . $e->getMessage() . '</p></div>';
					}

				}
			} ?>

			<div class="litespeed-textarea-recommended">
				<div>
					<?php $this->build_textarea( $id, 40 ); ?>
				</div>
				<div>
					<?php $this->recommended( $id ); ?>
				</div>
			</div>

			<div class="litespeed-desc">
				<?php Doc::one_per_line(); ?>

				<?php $this->_validate_syntax( $id ); ?>

				<?php if ( Conf::val( Base::O_CACHE_MOBILE ) && ! Conf::val( $id ) ) : ?>
				<font class="litespeed-warning">
					❌
					<?php echo sprintf( __( 'If %1$s is %2$s, then %3$s must be populated!', 'litespeed-cache' ), '<code>' . __('Cache Mobile', 'litespeed-cache') . '</code>', '<code>' . __('ON', 'litespeed-cache') . '</code>', '<code>' . __('List of Mobile User Agents', 'litespeed-cache') . '</code>' ); ?>
				</font>
				<?php endif; ?>
			</div>
		</td>
	</tr>
	<!-- build_setting_mobile_view end -->