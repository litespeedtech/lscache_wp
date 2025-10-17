<?php
/**
 * LiteSpeed Cache Beta Test
 *
 * Renders the beta test interface for LiteSpeed Cache, allowing users to switch plugin versions or test GitHub commits.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

// List of available public versions
$v_list = array(
	'7.6.2',
	'7.6.1',
	'7.6',
	'7.5.0.1',
	'7.4',
	'7.3.0.1',
	'7.3',
	'7.2',
	'7.1',
	'7.0.1',
	'6.5.4',
	'5.7.0.1',
	'4.6',
	'3.6.4',
);
?>

<?php $this->form_action( Router::ACTION_DEBUG2, Debug2::TYPE_BETA_TEST ); ?>

	<h3 class="litespeed-title">
		<?php esc_html_e( 'Try GitHub Version', 'litespeed-cache' ); ?>
		<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/toolbox/#beta-test-tab' ); ?>
	</h3>

	<?php if ( defined( 'LITESPEED_DISABLE_ALL' ) && LITESPEED_DISABLE_ALL ) : ?>
		<div class="litespeed-callout notice notice-warning inline">
			<h4><?php esc_html_e( 'NOTICE:', 'litespeed-cache' ); ?></h4>
			<p><?php esc_html_e( 'LiteSpeed Cache is disabled. This functionality will not work.', 'litespeed-cache' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="litespeed-desc">
		<?php esc_html_e( 'Use this section to switch plugin versions. To beta test a GitHub commit, enter the commit URL in the field below.', 'litespeed-cache' ); ?>
	</div>
	<div class="litespeed-desc">
		<?php esc_html_e( 'Example', 'litespeed-cache' ); ?>: <code>https://github.com/litespeedtech/lscache_wp/commit/example_comment_hash_d3ebec0535aaed5c932c0</code>
	</div>

	<input type="text" name="<?php echo esc_attr( Debug2::BETA_TEST_URL ); ?>" class="litespeed-input-long" id="litespeed-beta-test" value="">

	<p>
		<a href="javascript:;" class="button litespeed-btn-success" onclick="document.getElementById('litespeed-beta-test').value='dev';"><?php esc_html_e( 'Use latest GitHub Dev commit', 'litespeed-cache' ); ?></a> <code>dev</code>
	</p>

	<p>
		<a href="javascript:;" class="button litespeed-btn-success" onclick="document.getElementById('litespeed-beta-test').value='master';"><?php esc_html_e( 'Use latest GitHub Master commit', 'litespeed-cache' ); ?></a> <code>master</code>
	</p>

	<p>
		<a href="javascript:;" class="button litespeed-btn-success" onclick="document.getElementById('litespeed-beta-test').value='latest';"><?php esc_html_e( 'Use latest WordPress release version', 'litespeed-cache' ); ?></a> <code><?php echo esc_attr( Debug2::BETA_TEST_URL_WP ); ?></code> <?php esc_html_e( 'OR', 'litespeed-cache' ); ?> <code>latest</code>
	</p>

	<p>
		<?php foreach ( $v_list as $v ) : ?>
			<a href="javascript:;" class="button <?php echo '3.6.4' === $v ? 'litespeed-btn-danger' : 'litespeed-btn-success'; ?>" onclick="document.getElementById('litespeed-beta-test').value='<?php echo esc_attr( $v ); ?>';"><?php echo esc_html( $v ); ?></a>
		<?php endforeach; ?>
		<span class="litespeed-danger">
			🚨 <?php esc_html_e( 'Downgrade not recommended. May cause fatal error due to refactored code.', 'litespeed-cache' ); ?>
		</span>
	</p>

	<div class="litespeed-desc">
		<?php printf( esc_html__( 'Press the %s button to use the most recent GitHub commit. Master is for release candidate & Dev is for experimental testing.', 'litespeed-cache' ), '<code>' . esc_html__( 'Use latest GitHub Dev/Master commit', 'litespeed-cache' ) . '</code>' ); ?>
	</div>
	<div class="litespeed-desc">
		<?php printf( esc_html__( 'Press the %s button to stop beta testing and go back to the current release from the WordPress Plugin Directory.', 'litespeed-cache' ), '<code>' . esc_html__( 'Use latest WordPress release version', 'litespeed-cache' ) . '</code>' ); ?>
	</div>

	<p class="litespeed-danger">
		🚨 <?php printf( esc_html__( 'In order to avoid an upgrade error, you must be using %1$s or later before you can upgrade to %2$s versions.', 'litespeed-cache' ), '<code>v3.6.4</code>', '<code>dev/master/v4+</code>' ); ?>
	</p>

	<button type="submit" class="button button-primary"><?php esc_html_e( 'Upgrade', 'litespeed-cache' ); ?></button>
</form>