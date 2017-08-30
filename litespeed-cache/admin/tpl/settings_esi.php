<?php
if ( ! defined( 'WPINC' ) ) die ;

global $wp_roles ;
if ( !isset( $wp_roles ) ) {
	$wp_roles = new WP_Roles() ;
}
$roles = array_keys( $wp_roles->roles ) ;

sort( $roles ) ;

?>

<h3 class="litespeed-title"><?php echo __('ESI Settings', 'litespeed-cache'); ?></h3>

<p><?php echo __('ESI enables the capability to publicly cache pages for logged in users.', 'litespeed-cache'); ?></p>
<p><?php echo __('ESI functions by replacing the private information blocks with an ESI include.', 'litespeed-cache'); ?></p>
<p><?php echo __('When the server sees an ESI include, a sub request is created, containing the private information.', 'litespeed-cache'); ?></p>

<table class="form-table"><tbody>
	<tr>
		<th><?php echo __('Enable ESI', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::OPID_ESI_ENABLE); ?>
			<div class="litespeed-desc">
				<?php echo __('Enabling ESI will cache the public page for logged in users.', 'litespeed-cache'); ?>
				<?php echo __('The Admin Bar and comment form will be served via ESI blocks.', 'litespeed-cache'); ?>
				<?php echo __('The ESI blocks will not be cached until Cache ESI is checked.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Cache ESI', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::OPID_ESI_CACHE); ?>
			<div class="litespeed-desc">
				<?php echo __('Cache the ESI blocks.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Vary Group', 'litespeed-cache'); ?></th>
		<td>
			<table class="litespeed-vary-table"><tbody>
			<?php foreach ( $roles as $role ): ?>
				<tr>
					<td class='litespeed-vary-title'><?php echo $role ; ?></td>
					<td class='litespeed-vary-val'>
						<input type="text" class="regular-text small-text"
							name="<?php echo LiteSpeed_Cache_Config::VARY_GROUP ; ?>[<?php echo $role ; ?>]"
							value="<?php echo $this->config->in_vary_group( $role ) ; ?>" />
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody></table>
			<div class="litespeed-desc">
				<?php echo __( 'Specify separate groups for logged-in users with default vary in public cache. E.g. on frontend there are some contents that admin can see but the other roles can not see, then set role admin to a different group.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>




</tbody></table>