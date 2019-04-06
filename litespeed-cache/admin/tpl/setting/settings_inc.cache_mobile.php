<?php
if (!defined('WPINC')) die;

?>

	<!-- build_setting_mobile_view start -->
	<tr>
		<th><?php echo __('Cache Mobile', 'litespeed-cache'); ?></th>
		<td>
			<?php
				$this->build_switch(LiteSpeed_Cache_Config::O_CACHE_MOBILE);
			?>
			<div class="litespeed-desc">
				<?php echo __('Cache mobile views separately.', 'litespeed-cache'); ?>
				<?php echo __('Only enable for AMP or mobile-specific design/content, not for responsive sites.', 'litespeed-cache'); ?>
				<br /><font class="litespeed-warning">
					⚠️
					<?php echo __( 'This setting will edit the .htaccess file.', 'litespeed-cache' ) ; ?>
				</font>
			</div>
		</td>
	</tr>

	<tr>
		<th class="litespeed-padding-left"><?php echo __('List of Mobile User Agents', 'litespeed-cache'); ?></th>
		<td>
			<?php
				$id = LiteSpeed_Cache_Config::O_CACHE_MOBILE_RULES ;

				// if set, use value as input value
				if ( $_options[ LiteSpeed_Cache_Config::O_CACHE_MOBILE ] ) {

					// if enabled, check the setting in file
					if ( defined( 'LITESPEED_ON' ) ) {

						$mobile_agents = LiteSpeed_Cache_Admin_Rules::get_instance()->get_rewrite_rule_mobile_agents() ;
						if ( $mobile_agents !== $_options[ $id ] ) {
							echo '<div class="litespeed-callout-danger">'
									. __( 'Htaccess did not match configuration option.', 'litespeed-cache' )
									. ' ' . __( 'Please re-enter the mobile view setting.', 'litespeed-cache' )
									. ' ' . sprintf( __( 'List in WordPress database is: %s', 'litespeed-cache' ), '<b>' . $_options[ $id ] . '</b>' )
								. '</div>' ;
						}
					}
				}

				$this->build_textarea( $id ) ;
				$this->recommended( $id, true ) ;
			?>

			<div class="litespeed-desc">
				<i><?php echo __('One per line.', 'litespeed-cache'); ?></i>
				<font class="litespeed-warning">
					⚠️
					<?php echo sprintf( __( 'If %1$s is %2$s, then %3$s must be populated!', 'litespeed-cache' ), '<code>' . __('Cache Mobile', 'litespeed-cache') . '</code>', '<code>' . __('ON', 'litespeed-cache') . '</code>', '<code>' . __('List of Mobile User Agents', 'litespeed-cache') . '</code>' ) ; ?>
				</font>
			</div>
		</td>
	</tr>
	<!-- build_setting_mobile_view end -->