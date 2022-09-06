<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

// Existing public version list
$v_list = array(
	'5.2.1',
	'5.2',
	'5.1',
	'4.6',
	'4.5.0.1',
	'4.4.7',
	'4.4.5',
	'4.4.4',
	'4.4.3',
	'4.4.2',
	'4.4.1',
	'4.3',
	'4.2',
	'4.1',
);

?>

<?php $this->form_action( Router::ACTION_DEBUG2, Debug2::TYPE_BETA_TEST ); ?>

	<h3 class="litespeed-title">
		<?php echo __( 'Try GitHub Version', 'litespeed-cache' ); ?>
		<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/toolbox/#beta-test-tab' ); ?>
	</h3>

	<div class="litespeed-desc"><?php echo __( 'Use this section to switch plugin versions. To beta test a GitHub commit, enter the commit URL in the field below.', 'litespeed-cache' ); ?></div>
	<div class="litespeed-desc"><?php echo __( 'Example', 'litespeed-cache' ); ?>: <code>https://github.com/litespeedtech/lscache_wp/commit/e9cb446dfb66d133264d3ebec0535aaed5c932c0</code></div>

	<input type="text" name="<?php echo Debug2::BETA_TEST_URL; ?>" class="litespeed-input-long" id='litespeed-beta-test'>

	<p><a href="javascript:;" class="button litespeed-btn-success" onclick="document.getElementById('litespeed-beta-test').value='dev';"><?php echo __( 'Use latest GitHub Dev commit', 'litespeed-cache' ); ?></a> <code>dev</code></p>

	<p><a href="javascript:;" class="button litespeed-btn-success" onclick="document.getElementById('litespeed-beta-test').value='master';"><?php echo __( 'Use latest GitHub Master commit', 'litespeed-cache' ); ?></a> <code>master</code></p>

	<p><a href="javascript:;" class="button litespeed-btn-success" onclick="document.getElementById('litespeed-beta-test').value='latest';"><?php echo __( 'Use latest WordPress release version', 'litespeed-cache' ); ?></a> <code><?php echo Debug2::BETA_TEST_URL_WP; ?></code> <?php echo __( 'OR', 'litespeed-cache' ) ?> <code>latest</code></p>

	<p>
	<?php foreach ( $v_list as $v ) : ?>

		<a href="javascript:;" class="button litespeed-btn-success" onclick="document.getElementById('litespeed-beta-test').value='<?php echo $v; ?>';"><?php echo $v; ?></a>

	<?php endforeach; ?>

		<a href="javascript:;" class="button litespeed-btn-danger" onclick="document.getElementById('litespeed-beta-test').value='3.6.4';">3.6.4</a>
		<span class="litespeed-danger">
			🚨 <?php echo __( 'Downgrade not recommended. May cause fatal error due to refactored code.', 'litespeed-cache' ); ?>
		</span>
	</p>

	<div class="litespeed-desc"><?php echo sprintf( __( 'Press the %s button to use the most recent GitHub commit. Master is for release candidate & Dev is for experimental testing.', 'litespeed-cache' ), '<code>' . __( 'Use latest GitHub Dev/Master commit', 'litespeed-cache' ) . '</code>' ); ?></div>
	<div class="litespeed-desc"><?php echo sprintf( __( 'Press the %s button to stop beta testing and go back to the current release from the WordPress Plugin Directory.', 'litespeed-cache' ), '<code>' . __( 'Use latest WordPress release version', 'litespeed-cache' ) . '</code>' ); ?></div>



	<p class="litespeed-danger">
		🚨 <?php echo sprintf( __( 'In order to avoid an upgrade error, you must be using %1$s or later before you can upgrade to %2$s versions.', 'litespeed-cache' ), '<code>v3.6.4</code>', '<code>dev/master/v4+</code>' ); ?>
	</p>

	<button type="submit" class="button button-primary"><?php echo __('Upgrade', 'litespeed-cache'); ?></button>
</form>
