<?php
if (!defined('WPINC')) die;

?>

	<!-- build_setting_mobile_view start -->
	<tr>
		<th><?php echo __('Cache Mobile', 'litespeed-cache'); ?></th>
		<td>
			<?php
				$file_writable = LiteSpeed_Cache_Admin_Rules::writable();
				$this->build_switch(LiteSpeed_Cache_Config::OPID_CACHE_MOBILE); //, !$file_writable
			?>
			<div class="litespeed-desc">
				<?php echo __('When enabled, mobile views will be cached separately.', 'litespeed-cache'); ?>
				<?php echo __('A site built with responsive design does not need to check this.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('List of Mobile User Agents', 'litespeed-cache'); ?></th>
		<td>
			<?php
				$id = LiteSpeed_Cache_Config::ID_MOBILEVIEW_LIST;
				$wp_default_mobile = 'Mobile|Android|Silk/|Kindle|BlackBerry|Opera\ Mini|Opera\ Mobi';
				$cache_enable_id = is_network_admin() ? LiteSpeed_Cache_Config::NETWORK_OPID_ENABLED : LiteSpeed_Cache_Config::OPID_ENABLED;
				$mobile_agents = '';
				$input_value = $wp_default_mobile;

				// if set, use value as input value
				if ( $_options[$id] ){

					$input_value = $_options[$id];

					// if enabled, check the setting in file
					if ( $_options[$cache_enable_id]){

						$mobile_agents = LiteSpeed_Cache_Admin_Rules::get_instance()->get_rewrite_rule_mobile_agents();
						$this->display_messages();
						if ( $mobile_agents ){
							$input_value = $mobile_agents;
						}

						if ( $mobile_agents !== $_options[$id] ){
							echo '<p class="attention">'
									. __('Htaccess did not match configuration option.', 'litespeed-cache')
									. ' ' . __('Please re-enter the mobile view setting.', 'litespeed-cache')
									. ' ' . sprintf(__('List in WordPress database is: %s', 'litespeed-cache'), '<b>' . $_options[$id] . '</b>')
								. '</p>';
						}
					}
				}

				$this->build_input($id, 'large-text widget ui-draggable-dragging code', false, !$_options[$id], 'litespeed-mobileview-rules', $input_value);
			?>

			<input type="hidden" name="<?php echo LiteSpeed_Cache_Config::OPTION_NAME; ?>[<?php echo $id; ?>__default]"
				id="litespeed-mobileview-rules-default"
				value="<?php echo esc_textarea($_options[$id] ?: $wp_default_mobile); ?>"
			/>

			<div class="litespeed-desc">
				<strong><?php echo __('NOTICE:', 'litespeed-cache'); ?></strong>
				<?php echo __('This setting will edit the .htaccess file.', 'litespeed-cache'); ?><br />

				<i>
				<?php
					echo sprintf(__('SYNTAX: Each entry should be separated with a bar, %s', 'litespeed-cache'), "'|'.")
						. ' ' . sprintf(__('Any spaces should be escaped with a backslash before the space, %s', 'litespeed-cache'), "'\\ '.")
						. '<br />'
						. sprintf(__('The default list WordPress uses is %s', 'litespeed-cache'), "<b>$wp_default_mobile</b>");
				?>
				</i>
			</div>
		</td>
	</tr>
	<!-- build_setting_mobile_view end -->