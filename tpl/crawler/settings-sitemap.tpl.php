<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$this->form_action();
?>

<h3 class="litespeed-title-short">
	<?php echo __('Crawler Sitemap Settings', 'litespeed-cache'); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/crawler/#sitemap-settings-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>
	<tr>
		<th>
			<?php $id = Base::O_CRAWLER_SITEMAP; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input( $id, 'litespeed-input-long' ); ?>
			<div class="litespeed-desc">
				<?php echo __('The crawler can use your Google XML Sitemap instead of its own. Enter the full URL to your sitemap here.', 'litespeed-cache'); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_CRAWLER_DROP_DOMAIN; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_switch( $id ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'The crawler will parse the sitemap and save into the database before crawling. When parsing the sitemap, dropping the domain can save DB storage.', 'litespeed-cache' ); ?>
				<?php echo __( 'If you are using multiple domains for one site, and have multiple domains in the sitemap, please keep this option OFF so the crawler knows to crawl every domain.', 'litespeed-cache' ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_CRAWLER_MAP_TIMEOUT; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_input( $id, 'litespeed-input-short' ); ?>
			<div class="litespeed-desc">
				<?php echo __( 'Specify the timeout while parsing the sitemap.', 'litespeed-cache' ); ?>
				<?php $this->recommended( $id ); ?>
				<?php $this->_validate_ttl( $id, 15, 1800 ); ?>
			</div>
		</td>
	</tr>

	<tr>
		<th><?php echo __( 'Sitemap Generation', 'litespeed-cache' ); ?></th>
		<td>
			<div class="litespeed-block">
				<div class='litespeed-col-auto litespeed-toggle-stack'>
					<div class="litespeed-row litespeed-toggle-wrapper">
						<div class="litespeed-form-label litespeed-form-label--toggle"><?php echo __( 'Include Posts', 'litespeed-cache' ); ?></div>
					<?php
						$this->build_toggle( Base::O_CRAWLER_POSTS );
					?>
					</div>

					<div class="litespeed-row litespeed-toggle-wrapper">
						<div class="litespeed-form-label litespeed-form-label--toggle"><?php echo __( 'Include Pages', 'litespeed-cache' ); ?></div>
					<?php
						$this->build_toggle( Base::O_CRAWLER_PAGES );
					?>
					</div>

					<div class="litespeed-row litespeed-toggle-wrapper">
						<div class="litespeed-form-label litespeed-form-label--toggle"><?php echo __( 'Include Categories', 'litespeed-cache' ); ?></div>
					<?php
						$this->build_toggle( Base::O_CRAWLER_CATS );
					?>
					</div>

					<div class="litespeed-row litespeed-toggle-wrapper">
						<div class="litespeed-form-label litespeed-form-label--toggle"><?php echo __( 'Include Tags', 'litespeed-cache' ); ?></div>
					<?php
						$this->build_toggle( Base::O_CRAWLER_TAGS );
					?>
					</div>

				</div>

				<div class='litespeed-col-auto'>
					<h4><?php echo __('Exclude Custom Post Types', 'litespeed-cache'); ?></h4>

					<?php $this->build_textarea( Base::O_CRAWLER_EXC_CPT, 40 ); ?>

					<div class="litespeed-desc">
						<?php echo __('Exclude certain Custom Post Types in sitemap.', 'litespeed-cache'); ?>
					</div>
				</div>

				<div class='litespeed-col-auto'>
					<div class="litespeed-callout notice notice-warning inline">
						<h4><?php echo __('Available Custom Post Type','litespeed-cache'); ?></h4>
						<p>
							<?php echo implode('<br />', array_diff(get_post_types(), array('post', 'page'))); ?>
						</p>
					</div>
				</div>

				<div class='litespeed-col-auto'>
					<h4><?php echo __('Order links by', 'litespeed-cache'); ?></h4>

					<?php $this->build_switch( Base::O_CRAWLER_ORDER_LINKS, array( __('Date, descending (Default)', 'litespeed-cache'), __('Date, ascending', 'litespeed-cache'), __('Alphabetical, descending', 'litespeed-cache'), __('Alphabetical, ascending', 'litespeed-cache') ) ); ?>
					<div class="litespeed-desc">
						<?php echo sprintf( __( 'These options will be invalid when using %s.', 'litespeed-cache' ), '<code>' . __( 'Custom Sitemap', 'litespeed-cache' ) . '</code>' ); ?>
					</div>
				</div>
			</div>

		</td>
	</tr>

</tbody></table>

<?php
$this->form_end();
