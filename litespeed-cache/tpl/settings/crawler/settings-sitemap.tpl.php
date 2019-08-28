<?php
if ( !defined('WPINC') ) die;

$this->form_action() ;
?>

<h3 class="litespeed-title-short">
	<?php echo __('Crawler Sitemap Settings', 'litespeed-cache'); ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:crawler', false, 'litespeed-learn-more' ) ; ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>
	<tr>
		<th><?php echo __('Custom Sitemap', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Config::O_CRWL_CUSTOM_SITEMAP ; ?>
			<?php $this->build_input( $id, 'litespeed-input-long' ) ; ?>
			<div class="litespeed-desc">
				<?php echo __('The crawler can use your Google XML Sitemap instead of its own. Enter the full URL to your sitemap here.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __('Sitemap Generation', 'litespeed-cache'); ?></th>
		<td>
			<div class="litespeed-block">
				<div class='litespeed-cdn-mapping-col2'>
					<div class="litespeed-row">
						<div class="litespeed-col-inc"><?php echo __( 'Include Posts', 'litespeed-cache' ) ; ?></div>
					<?php
						$this->build_toggle( LiteSpeed_Config::O_CRWL_POSTS ) ;
					?>
					</div>

					<div class="litespeed-row">
						<div class="litespeed-col-inc"><?php echo __( 'Include Pages', 'litespeed-cache' ) ; ?></div>
					<?php
						$this->build_toggle( LiteSpeed_Config::O_CRWL_PAGES ) ;
					?>
					</div>

					<div class="litespeed-row">
						<div class="litespeed-col-inc"><?php echo __( 'Include Categories', 'litespeed-cache' ) ; ?></div>
					<?php
						$this->build_toggle( LiteSpeed_Config::O_CRWL_CATS ) ;
					?>
					</div>

					<div class="litespeed-row">
						<div class="litespeed-col-inc"><?php echo __( 'Include Tags', 'litespeed-cache' ) ; ?></div>
					<?php
						$this->build_toggle( LiteSpeed_Config::O_CRWL_TAGS ) ;
					?>
					</div>

				</div>

				<div class='litespeed-col-auto'>
					<h4><?php echo __('Exclude Custom Post Types', 'litespeed-cache'); ?></h4>

					<?php $this->build_textarea( LiteSpeed_Config::O_CRWL_EXC_CPT, 40 ) ; ?>

					<div class="litespeed-desc">
						<?php echo __('Exclude certain Custom Post Types in sitemap.', 'litespeed-cache'); ?>
					</div>
				</div>

				<div class='litespeed-col-auto'>
					<div class="litespeed-callout notice notice-warning inline">
						<h4><?php echo __('Available Custom Post Type','litespeed-cache'); ?></h4>
						<p>
							<?php echo implode('<br />', array_diff(get_post_types( '', 'names' ), array('post', 'page'))); ?>
						</p>
					</div>
				</div>

				<div class='litespeed-col-auto'>
					<h4><?php echo __('Order links by', 'litespeed-cache'); ?></h4>

					<div class="litespeed-switch">
						<?php $this->build_radio(
							LiteSpeed_Config::O_CRWL_ORDER_LINKS,
							LiteSpeed_Config::CRWL_DATE_DESC,
							__('Date, descending (Default)', 'litespeed-cache')
						); ?>

						<?php $this->build_radio(
							LiteSpeed_Config::O_CRWL_ORDER_LINKS,
							LiteSpeed_Config::CRWL_DATE_ASC,
							__('Date, ascending', 'litespeed-cache')
						); ?>

						<?php $this->build_radio(
							LiteSpeed_Config::O_CRWL_ORDER_LINKS,
							LiteSpeed_Config::CRWL_ALPHA_DESC,
							__('Alphabetical, descending', 'litespeed-cache')
						); ?>

						<?php $this->build_radio(
							LiteSpeed_Config::O_CRWL_ORDER_LINKS,
							LiteSpeed_Config::CRWL_ALPHA_ASC,
							__('Alphabetical, ascending', 'litespeed-cache')
						); ?>
					</div>
					<div class="litespeed-desc">
						<?php echo sprintf( __( 'These options will be invalid when using %s.', 'litespeed-cache' ), '<code>' . __( 'Custom Sitemap', 'litespeed-cache' ) . '</code>' ) ; ?>
					</div>
				</div>
			</div>

		</td>
	</tr>

</tbody></table>

<?php
$this->form_end() ;
