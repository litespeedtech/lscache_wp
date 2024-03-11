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
			<?php $this->title($id); ?>
		</th>
		<td>
			<?php $this->build_textarea($id); ?>
			<div class="litespeed-desc">
				<?php echo __('The crawler will use your XML sitemap or sitemap index. Enter the full URL to your sitemap here.', 'litespeed-cache'); ?>
				<?php Doc::one_per_line(); ?>
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
</tbody></table>

<?php
$this->form_end();
