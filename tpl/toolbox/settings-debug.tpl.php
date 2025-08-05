<?php
/**
 * LiteSpeed Cache Debug Settings Interface
 *
 * Renders the debug settings interface for LiteSpeed Cache, allowing users to configure debugging options and view the site with specific settings bypassed.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$this->form_action( $this->_is_network_admin ? Router::ACTION_SAVE_SETTINGS_NETWORK : false );
?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'Debug Helpers', 'litespeed-cache' ); ?>
</h3>

<a href="<?php echo esc_url( home_url( '/' ) . '?' . Router::ACTION . '=before_optm' ); ?>" class="button button-success" target="_blank">
	<?php esc_html_e( 'View Site Before Optimization', 'litespeed-cache' ); ?>
</a>

<a href="<?php echo esc_url( home_url( '/' ) . '?' . Router::ACTION . '=' . Core::ACTION_QS_NOCACHE ); ?>" class="button button-success" target="_blank">
	<?php esc_html_e( 'View Site Before Cache', 'litespeed-cache' ); ?>
</a>


<?php
$temp_disabled_time = $this->conf( Base::DEBUG_TMP_DISABLE );
$temp_disabled      = Debug2::is_tmp_disable();
if ( !$temp_disabled ) {
?>
	<a href="<?php echo wp_kses_post( Utility::build_url(Router::ACTION_TMP_DISABLE, false, false, '_ori') ); ?>" class="button litespeed-btn-danger">
		<?php esc_html_e( 'Disable All Features for 24 Hours', 'litespeed-cache' ); ?>
	</a>
<?php
} else {
	$date = wp_date( get_option('date_format') . ' ' . get_option( 'time_format' ), $temp_disabled_time );
?>
	<a href="<?php echo wp_kses_post( Utility::build_url(Router::ACTION_TMP_DISABLE, false, false, '_ori') ); ?>" class="button litespeed-btn-warning">
		<?php esc_html_e( 'Remove `Disable All Feature` Flag Now', 'litespeed-cache' ); ?>
	</a>
	<div class="litespeed-callout notice notice-warning inline">
		<h4><?php esc_html_e( 'NOTICE', 'litespeed-cache' ); ?></h4>
		<p><?php echo wp_kses_post( sprintf ( __( 'LiteSpeed Cache is temporarily disabled until: %s.', 'litespeed-cache' ), '<strong>' . $date . '</strong>' ) ); ?></p>
	</div>
<?php
}
?>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'Debug Settings', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/toolbox/#debug-settings-tab' ); ?>
</h3>

<table class="wp-list-table striped litespeed-table">
	<tbody>
		<tr>
			<th>
				<?php $option_id = Base::O_DEBUG_DISABLE_ALL; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'This will disable LSCache and all optimization features for debug purpose.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_DEBUG; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id, array( esc_html__( 'OFF', 'litespeed-cache' ), esc_html__( 'ON', 'litespeed-cache' ), esc_html__( 'Admin IP Only', 'litespeed-cache' ) ) ); ?>
				<div class="litespeed-desc">
					<?php printf( esc_html__( 'Outputs to a series of files in the %s directory.', 'litespeed-cache' ), '<code>wp-content/litespeed/debug</code>' ); ?>
					<?php esc_html_e( 'To prevent filling up the disk, this setting should be OFF when everything is working.', 'litespeed-cache' ); ?>
					<?php esc_html_e( 'The Admin IP option will only output log messages on requests from admin IPs listed below.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_DEBUG_IPS; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_textarea( $option_id, 50 ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Allows listed IPs (one per line) to perform certain actions from their browsers.', 'litespeed-cache' ); ?>
					<?php esc_html_e( 'Your IP', 'litespeed-cache' ); ?>: <code><?php echo esc_html( Router::get_ip() ); ?></code>
					<?php $this->_validate_ip( $option_id ); ?>
					<br />
					<?php
					Doc::learn_more(
						'https://docs.litespeedtech.com/lscache/lscwp/admin/#admin-ip-commands',
						esc_html__( 'More information about the available commands can be found here.', 'litespeed-cache' )
					);
					?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_DEBUG_LEVEL; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id, array( esc_html__( 'Basic', 'litespeed-cache' ), esc_html__( 'Advanced', 'litespeed-cache' ) ) ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Advanced level will log more details.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_DEBUG_FILESIZE; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_input( $option_id, 'litespeed-input-short' ); ?> <?php esc_html_e( 'MB', 'litespeed-cache' ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Specify the maximum size of the log file.', 'litespeed-cache' ); ?>
					<?php $this->recommended( $option_id ); ?>
					<?php $this->_validate_ttl( $option_id, 3, 3000 ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_DEBUG_COLLAPSE_QS; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_switch( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Shorten query strings in the debug log to improve readability.', 'litespeed-cache' ); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_DEBUG_INC; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_textarea( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Only log listed pages.', 'litespeed-cache' ); ?>
					<?php $this->_uri_usage_example(); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_DEBUG_EXC; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_textarea( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Prevent any debug log of listed pages.', 'litespeed-cache' ); ?>
					<?php $this->_uri_usage_example(); ?>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php $option_id = Base::O_DEBUG_EXC_STRINGS; ?>
				<?php $this->title( $option_id ); ?>
			</th>
			<td>
				<?php $this->build_textarea( $option_id ); ?>
				<div class="litespeed-desc">
					<?php esc_html_e( 'Prevent writing log entries that include listed strings.', 'litespeed-cache' ); ?>
					<?php Doc::one_per_line(); ?>
				</div>
			</td>
		</tr>
	</tbody>
</table>

<?php $this->form_end(); ?>