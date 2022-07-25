<?php
namespace LiteSpeed;
defined( 'WPINC' ) || exit;

$profiles = array(
	'essentials',
	'basic',
	'advanced',
	'aggressive',
	'extreme'
);

$summary = Profiles::get_summary();

function apply_url( $profile ) {
	echo Utility::build_url(
		Router::ACTION_PROFILES,
		Profiles::TYPE_APPLY,
		false,
		null,
		array( 'profile' => $profile )
	);
}

function profile( $name ) {
	if ( ! is_readable( Profiles::builtin( $name ) ) ) {
		return;
	}
	?>
	<div>
		<h4><?php echo ucwords( $name ); ?></h4>
		<a href="<?php apply_url( $name ); ?>" class="button button-primary">
			<?php esc_html_e( 'Apply Profile', 'litespeed-cache' ); ?>
		</a>
	</div>
	<?php
}

?>

<h3 class="litespeed-title">
	<?php esc_html_e( 'LiteSpeed Cache Profiles', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/general/#profiles-tab' ); ?>
</h3>

<?php
	foreach ( $profiles as $name ) {
		profile( $name );
	}
?>

<h3 class="litespeed-title">
	<?php esc_html_e( 'History', 'litespeed-cache' ); ?>
</h3>

<?php if ( ! empty( $summary['profile'] ) ) : ?>
<div class="litespeed-desc">
	<?php
	printf(
		esc_html__( 'Last applied the %1$s profile %2$s', 'litespeed-cache' ),
		'<strong>' . ucwords( $summary['profile'] ) . '</strong>',
		Utility::readable_time( $summary['profile_time'] )
	);
	?>
</div>
<?php endif; ?>

<div><a
		href="<?php echo Utility::build_url( Router::ACTION_PROFILES, Profiles::TYPE_REVERT ); ?>"
		class="button button-primary"
	>
	<?php esc_html_e( 'Revert', 'litespeed-cache' ); ?>
</a></div>
