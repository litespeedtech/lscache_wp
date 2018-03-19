<?php
if ( ! defined( 'WPINC' ) ) die ;

?>

<h3 class="litespeed-title-short">
	<?php echo __('ESI Settings', 'litespeed-cache'); ?>
	<a href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:esi" target="_blank" class="litespeed-learn-more"><?php echo __('Learn More', 'litespeed-cache') ; ?></a>
</h3>

<?php $this->cache_disabled_warning() ; ?>

<div class="litespeed-desc">
	<p><?php echo __('With ESI (Edge Side Includes), pages may be served from cache for logged-in users.', 'litespeed-cache'); ?></p>
	<p><?php echo __('ESI allows you to designate parts of your dynamic page as separate fragments that are then assembled together to make the whole page. In other words, ESI lets you “punch holes” in a page, and then fill those holes with content that may be cached privately, cached publicly with its own TTL, or not cached at all.', 'litespeed-cache'); ?>
		<?php echo sprintf(
		__( 'Learn more about public cache vs. private cache <a %s>on our blog</a>.', 'litespeed-cache' ),
		'href="https://blog.litespeedtech.com/2017/08/30/wpw-private-cache-vs-public-cache/" target="_blank"'
		) ; ?>
	</p>
	<p><?php echo sprintf(
		__( '<a %s>ESI sample for developers</a>.', 'litespeed-cache' ),
		'href="https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:esi_sample" target="_blank"'
		) ; ?></p>
</div>

<div class="litespeed-relative">

<?php if ( ! LSWCP_ESI_SUPPORT ) : ?>
	<div class="litespeed-ent-notice">
		<div class="litespeed-ent-notice-desc"><?php echo __('Available in LiteSpeed Enterprise version', 'litespeed-cache'); ?></div>
	</div>
<?php endif; ?>

<table><tbody>
	<tr>
		<th><?php echo __('Enable ESI', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::OPID_ESI_ENABLE); ?>
			<div class="litespeed-desc">
				<?php echo __('Enable caches public pages for logged in users and serves the Admin Bar and Comment Form via ESI blocks. These two blocks will be uncached unless enabled below.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Cache Admin Bar', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::OPID_ESI_CACHE_ADMBAR); ?>
			<div class="litespeed-desc">
				<?php echo __('Cache the build-in Admin Bar ESI block.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Cache Comment Form', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_switch(LiteSpeed_Cache_Config::OPID_ESI_CACHE_COMMFORM); ?>
			<div class="litespeed-desc">
				<?php echo __('Cache the build-in Comment Form ESI block.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Vary Group', 'litespeed-cache'); ?></th>
		<td>
			<table class="litespeed-vary-table"><tbody>
			<?php foreach ( $roles as $role => $title ): ?>
				<tr>
					<td class='litespeed-vary-title'><?php echo $title ; ?></td>
					<td class='litespeed-vary-val'>
						<input type="text" class="litespeed-input-short"
							name="<?php echo LiteSpeed_Cache_Config::VARY_GROUP ; ?>[<?php echo $role ; ?>]"
							value="<?php echo $this->config->in_vary_group( $role ) ; ?>" />
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody></table>
			<div class="litespeed-desc">
				<?php echo __( 'If your site contains public content that certain user roles can see but other roles cannot, you can specify a Vary Group for those user roles. For example, specifying an administrator vary group allows there to be a separate publicly-cached page tailored to administrators (with “edit” links, etc), while all other user roles see the default public page.', 'litespeed-cache' ) ; ?>
			</div>
		</td>
	</tr>




</tbody></table>

</div>