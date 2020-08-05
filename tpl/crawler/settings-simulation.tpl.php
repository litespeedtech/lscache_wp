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
			<div id="cookie_crawler">
				<div class="litespeed-block" v-for="( item, key ) in items">
					<div class='litespeed-col-auto'>
						<label class="litespeed-form-label"><?php echo __( 'Cookie Name', 'litespeed-cache' ); ?></label>
						<input type="text" v-model="item.name" name="<?php echo $id; ?>[name][]" class="regular-text">
					</div>
					<div class='litespeed-col-auto'>
						<label class="litespeed-form-label"><?php echo __( 'Cookie Values', 'litespeed-cache' ); ?></label>

						<textarea v-model="item.vals" rows="5" cols="40" name="<?php echo $id; ?>[vals][]" placeholder="<?php Doc::one_per_line(); ?>"></textarea>
					</div>
					<div class='litespeed-col-auto'>
						<button type="button" class="button button-link litespeed-collection-button litespeed-danger" data-action="remove" @click="$delete( items, key )">
							<span class="dashicons dashicons-dismiss"></span>
							<span class="screen-reader-text"><?php echo __( 'Remove cookie simulation', 'litespeed-cache' ) ; ?></span>
						</button>
					</div>
				</div>

				<p>
					<button type="button" @click='add_row' class="button button-link litespeed-form-action litespeed-link-with-icon" data-action="add">
						<span class="dashicons dashicons-plus-alt"></span><?php echo __( 'Add new cookie to simulate', 'litespeed-cache' ) ;?>
					</button>
				</p>

			</div>

			<script>
				var cookie_crawler = new Vue( {
					el: '#cookie_crawler',
					data: {
						counter: 0,
						items : [
							<?php
								// Build the cookie crawler Vue data
								/**
								 * Data Src Structure:
								 * @since  3.0
								 * 		crawler-cookie[ 0 ][ name ] = 'xxx'
								 * 	 	crawler-cookie[ 0 ][ vals ] = 'xxx'
								 *
								 * @deprecated 3.0 [ nameA => vals, nameB => vals ]
								 */
								$list = array();
								foreach ( Conf::val( $id ) as $v ) {
									if ( empty( $v[ 'name' ] ) ) {
										continue;
									}

									$list[] = "{ name: '$v[name]', vals: `" . implode( "\n", $v[ 'vals' ] ) . "` }";// $v contains line break
								}
								echo implode( ',', $list );
							?>
						]
					},
					methods: {
						add_row() {
							this.items.push( {
								id: ++ this.counter
							} );
						}
					}
				} );
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
