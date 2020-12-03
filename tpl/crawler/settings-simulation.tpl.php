<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$this->form_action();
?>

<h3 class="litespeed-title-short">
	<?php echo __('Crawler Simulation Settings', 'litespeed-cache'); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/crawler/#simulation-settings-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>
	<tr>
		<th>
			<?php $id = Base::O_CRAWLER_ROLES; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->build_textarea( $id, 20 ); ?>

			<div class="litespeed-desc">
				<?php echo __('To crawl the site as a logged-in user, enter the user ids to be simulated.', 'litespeed-cache'); ?>
				<?php Doc::one_per_line(); ?>
			</div>

		</td>
	</tr>

	<tr>
		<th>
			<?php $id = Base::O_CRAWLER_COOKIES; ?>
			<?php $this->title( $id ); ?>
		</th>
		<td>
			<?php $this->enroll( $id . '[name][]' ); ?>
			<?php $this->enroll( $id . '[vals][]' ); ?>

			<div id="litespeed_crawler_simulation_div"></div>

			<script type="text/babel">
				ReactDOM.render(
					<CrawlerSimulate list={ <?php echo json_encode( Conf::val( $id ) ); ?> } />,
					document.getElementById( 'litespeed_crawler_simulation_div' )
				);
			</script>

			<div class="litespeed-desc">
				<?php echo __('To crawl for a particular cookie, enter the cookie name, and the values you wish to crawl for. Values should be one per line, and can include a blank line. There will be one crawler created per cookie value, per simulated role.', 'litespeed-cache'); ?>
				<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/crawler/#cookie-simulation' ); ?>
				<p><?php echo sprintf( __( 'Use %1$s in %2$s to indicate this cookie has not been set.', 'litespeed-cache' ), '<code>_null</code>', __( 'Cookie Values', 'litespeed-cache' ) ); ?></p>
			</div>

		</td>
	</tr>

</tbody></table>

<?php
$this->form_end();
