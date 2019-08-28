<?php
if ( !defined('WPINC') ) die;

$this->form_action() ;
?>

<h3 class="litespeed-title-short">
	<?php echo __('Crawler Simulation Settings', 'litespeed-cache'); ?>
	<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:crawler', false, 'litespeed-learn-more' ) ; ?>
</h3>

<table class="wp-list-table striped litespeed-table"><tbody>
	<tr>
		<th><?php echo __('Role Simulation', 'litespeed-cache'); ?></th>
		<td>
			<?php $this->build_textarea( LiteSpeed_Config::O_CRWL_ROLES, 20 ) ; ?>

			<div class="litespeed-desc">
				<?php echo __('To crawl the site as a logged-in user, enter the user ids to be simulated.', 'litespeed-cache'); ?>
				<?php LiteSpeed_Doc::one_per_line() ; ?>
			</div>

		</td>
	</tr>

	<tr>
		<th><?php echo __('Cookie Simulation', 'litespeed-cache'); ?></th>
		<td>
			<?php $id = LiteSpeed_Config::O_CRWL_COOKIES ; ?>
			<?php $this->enroll( $id . '[name][]' ) ; ?>
			<?php $this->enroll( $id . '[vals][]' ) ; ?>
			<div id="cookie_crawler">
				<div class="litespeed-block" v-for="( item, key ) in items">
					<div class='litespeed-col-auto'>
						<h4><?php echo __( 'Cookie Name', 'litespeed-cache' ) ; ?></h4>
					</div>
					<div class='litespeed-col-auto'>
						<input type="text" v-model="item.name" name="<?php echo $id ; ?>[name][]" class="regular-text" style="margin-top:1.33em;" >
					</div>
					<div class='litespeed-col-auto'>
						<h4><?php echo __( 'Cookie Values', 'litespeed-cache' ) ; ?></h4>
					</div>
					<div class='litespeed-col-auto'>
						<textarea v-model="item.vals" rows="5" cols="40" class="litespeed-textarea-success" name="<?php echo $id ; ?>[vals][]" placeholder="<?php LiteSpeed_Doc::one_per_line() ; ?>"></textarea>
					</div>
					<div class='litespeed-col-auto'>
						<button type="button" class="button litespeed-btn-danger litespeed-btn-tiny" @click="$delete( items, key )">X</button>
					</div>
				</div>

				<button type="button" @click='add_row' class="button litespeed-btn-success litespeed-btn-tiny">+</button>
			</div>

			<script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.5.17/vue.min.js"></script>
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
								$list = array() ;
								foreach ( $this->__cfg->option( $id ) as $v ) {
									if ( empty( $v[ 'name' ] ) ) {
										continue ;
									}

									$list[] = "{ name: '$v[name]', vals: `" . implode( "\n", $v[ 'vals' ] ) . "` }" ;// $v contains line break
								}
								echo implode( ',', $list ) ;
							?>
						]
					},
					methods: {
						add_row() {
							this.items.push( {
								id: ++ this.counter
							} ) ;
						}
					}
				} ) ;
			</script>

			<div class="litespeed-desc">
				<?php echo __('To crawl for a particular cookie, enter the cookie name, and the values you wish to crawl for. Values should be one per line, and can include a blank line. There will be one crawler created per cookie value, per simulated role.', 'litespeed-cache'); ?>
				<?php $this->learn_more( 'https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:configuration:crawler#cookie_simulation' ) ; ?>
				<p><?php echo sprintf( __( 'Use %1$s in %2$s for the case that not set this cookie.', 'litespeed-cache' ), '<code>_null</code>', __( 'Cookie Values', 'litespeed-cache' ) ) ; ?></p>
			</div>

		</td>
	</tr>

</tbody></table>

<?php
$this->form_end() ;
