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

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'Litespeed Cache Profiles', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/general/#profiles-tab' ); ?>
</h3>

<p><?php esc_html_e( 'Litespeed Cache Profiles are a pre-defined set of Profiles which can be used for Optimizing any website using Litespeed Cache easily.', 'litespeed-cache' ); ?></p>

<div class="litespeed-comparison-cards">
	<div class="litespeed-comparison-card postbox">
		<div class="litespeed-card-content">
			<div class="litespeed-card-header">
				<h3 class="litespeed-h3"><?php esc_html_e( 'Essentials', 'litespeed-cache' ); ?></h3>
			</div>
			<div class="litespeed-card-body">
				<ul>
					<li><?php esc_html_e( 'Default Cache', 'litespeed-cache' ); ?></li>
					<li><?php esc_html_e( 'Higher TTL', 'litespeed-cache' ); ?></li>
					<li><?php esc_html_e( 'Browser Cache', 'litespeed-cache' ); ?></li>
				</ul>
			</div>
			<div class="litespeed-card-footer">
				<h4><?php esc_html_e( 'For whom this profile is good?', 'litespeed-cache' ); ?></h4>
				<p><?php esc_html_e( 'This profile has just the most basic number of features enabled, and is good for any website. This profile can be utilized without a Domain key, Good for cache-oriented development aswell.', 'litespeed-cache' ); ?></p>
			</div>
		</div>
		<div class="litespeed-card-action">
			<a href="<?php apply_url( 'essentials' ); ?>" class="button button-secondary" data-litespeed-cfm="<?php printf( 
				esc_html__('This will replace your current settings and apply %1$s Profile. Do you want to continue?', 'litespeed-cache' ),
				'Essentials'
			); ?>"><?php esc_html_e( 'Apply Profile', 'litespeed-cache' ); ?></a>
		</div>
	</div>
	<div class="litespeed-comparison-card postbox">
		<div class="litespeed-card-content">
			<div class="litespeed-card-header">
				<h3 class="litespeed-h3"><?php esc_html_e( 'Basic', 'litespeed-cache' ); ?></h3>
			</div>
			<div class="litespeed-card-body">
				<ul>
					<li><?php esc_html_e( 'Everything in Essentials, Plus', 'litespeed-cache' ); ?></li>
					<li><?php esc_html_e( 'Guest Mode and Optimization', 'litespeed-cache' ); ?></li>
					<li><?php esc_html_e( 'Image Optimization', 'litespeed-cache' ); ?></li>
					<li><?php esc_html_e( 'Mobile Cache', 'litespeed-cache' ); ?></li>
				</ul>
			</div>
			<div class="litespeed-card-footer">
				<h4><?php esc_html_e( 'For whom this profile is good?', 'litespeed-cache' ); ?></h4>
				<p><?php esc_html_e( 'This Profile enables the very essentials, plus some basic optimizations which will improve Google Pagespeed Score, and also not hamper user experience on website.', 'litespeed-cache' ); ?></p>
				<p><?php esc_html_e( 'It is good for any website, with a LSCWP Domain key.', 'litespeed-cache' ); ?></p>
			</div>
		</div>
		<div class="litespeed-card-action">
		<a href="<?php apply_url( 'basic' ); ?>" class="button button-secondary" data-litespeed-cfm="<?php printf( 
				esc_html__('This will replace your current settings and apply %1$s Profile. Do you want to continue?', 'litespeed-cache' ),
				'Basic'
			); ?>"><?php esc_html_e( 'Apply Profile', 'litespeed-cache' ); ?></a>
		</div>
	</div>
	<div class="litespeed-comparison-card litespeed-comparison-card-rec postbox">
		<div class="litespeed-card-content">
			<div class="litespeed-card-header">
				<h3 class="litespeed-h3"><?php esc_html_e( 'Advanced', 'litespeed-cache' ); ?></h3>
			</div>
			<div class="litespeed-card-body">
				<ul>
					<li><?php esc_html_e( 'Everything in Basic, Plus', 'litespeed-cache' ); ?></li>
					<li><?php esc_html_e( 'CSS, JS and HTML Minification', 'litespeed-cache' ); ?></li>
					<li><?php esc_html_e( 'Font Display Optimization', 'litespeed-cache' ); ?></li>
					<li><?php esc_html_e( 'JS Defer for both external and inline JS', 'litespeed-cache' ); ?></li>
					<li><?php esc_html_e( 'DNS Prefetch for static files', 'litespeed-cache' ); ?></li>
					<li><?php esc_html_e( 'Gravatar Cache', 'litespeed-cache' ); ?></li>
					<li><?php esc_html_e( 'Remove Query Strings from Static Files', 'litespeed-cache' ); ?></li>
					<li><?php esc_html_e( 'Remove WordPress Emoji', 'litespeed-cache' ); ?></li>
					<li><?php esc_html_e( 'Remove Noscript Tags', 'litespeed-cache' ); ?></li>
				</ul>
			</div>
			<div class="litespeed-card-footer">
				<h4><?php esc_html_e( 'For whom this profile is good?', 'litespeed-cache' ); ?></h4>
				<p><?php esc_html_e( 'This profile is the most recommended one, and is enabled with most non-conflicting options, and is good for most websites.' ); ?></p>
				<p><?php esc_html_e( 'Domain Key must be set to use this profile. Also improves your Pagespeed Score.', 'litespeed-cache' ); ?></p>
				
			</div>
		</div>
		<div class="litespeed-card-action">
		<a href="<?php apply_url( 'advanced' ); ?>" class="button button-primary" data-litespeed-cfm="<?php printf( 
				esc_html__('This will replace your current settings and apply %1$s Profile. Do you want to continue?', 'litespeed-cache' ),
				'Advanced'
			); ?>"><?php esc_html_e( 'Apply Profile', 'litespeed-cache' ); ?></a>
		</div>
	</div>
	<div class="litespeed-comparison-card postbox">
		<div class="litespeed-card-content">
			<div class="litespeed-card-header">
				<h3 class="litespeed-h3"><?php esc_html_e( 'Aggressive', 'litespeed-cache' ); ?></h3>
			</div>
			<div class="litespeed-card-body">
				<ul>
					<li><?php esc_html_e( 'Everything in Advanced, Plus', 'litespeed-cache' ); ?></li>
					<li><?php esc_html_e( 'CSS & JS Combine', 'litespeed-cache' ); ?></li>
					<li><?php esc_html_e( 'Asynchronous CSS Loading with Critical CSS', 'litespeed-cache' ); ?></li>
					<li><?php esc_html_e( 'Removed Unused CSS for Users', 'litespeed-cache' ); ?></li>
					<li><?php esc_html_e( 'Lazy Load for Iframes', 'litespeed-cache' ); ?></li>
				</ul>
			</div>
			<div class="litespeed-card-footer">
				<h4><?php esc_html_e( 'For whom this profile is good?', 'litespeed-cache' ); ?></h4>
				<p><?php esc_html_e( 'This profile while might work out of box in some websites, but might require some excludes in the Page Optimization >> Tuning section. ' ); ?></p>
				<p><?php esc_html_e( 'Domain Key must be set to use this profile. Also improves your Pagespeed Score.', 'litespeed-cache' ); ?></p>
				
			</div>
		</div>
		<div class="litespeed-card-action">
			<a href="<?php apply_url( 'aggressive' ); ?>" class="button button-secondary" data-litespeed-cfm="<?php printf( 
				esc_html__('This will replace your current settings and apply %1$s Profile. Do you want to continue?', 'litespeed-cache' ),
				'Aggressive'
			); ?>"><?php esc_html_e( 'Apply Profile', 'litespeed-cache' ); ?></a>
		</div>
	</div>
	<div class="litespeed-comparison-card postbox">
		<div class="litespeed-card-content">
			<div class="litespeed-card-header">
				<h3 class="litespeed-h3"><?php esc_html_e( 'Extreme', 'litespeed-cache' ); ?></h3>
			</div>
			<div class="litespeed-card-body">
				<ul>
					<li><?php esc_html_e( 'Everything in Aggressive, Plus', 'litespeed-cache' ); ?></li>
					<li><?php esc_html_e( 'LazyLoad for Images', 'litespeed-cache' ); ?></li>
					<li><?php esc_html_e( 'JS Delayed', 'litespeed-cache' ); ?></li>
					<li><?php esc_html_e( 'Inline JS added to Combine', 'litespeed-cache' ); ?></li>
					<li><?php esc_html_e( 'Inline CSS added to Combine', 'litespeed-cache' ); ?></li>
				</ul>
			</div>
			<div class="litespeed-card-footer">
				<h4><?php esc_html_e( 'For whom this profile is good?', 'litespeed-cache' ); ?></h4>
				<p><?php esc_html_e( 'This profile will most likely require the website owner to work with Lazy Load Exclusions specially for images such as Logo, or HTML based Slider Images on Top.' ); ?></p>
				<p><?php esc_html_e( 'Domain Key must be set to use this profile. Also improves your Pagespeed Score.', 'litespeed-cache' ); ?></p>
				
			</div>
		</div>
		<div class="litespeed-card-action">
		<a href="<?php apply_url( 'extreme' ); ?>" class="button button-secondary" data-litespeed-cfm="<?php printf( 
				esc_html__('This will replace your current settings and apply %1$s Profile. Do you want to continue?', 'litespeed-cache' ),
				'Extreme'
			); ?>"><?php esc_html_e( 'Apply Profile', 'litespeed-cache' ); ?></a>
		</div>
	</div>
</div>

<h3 class="litespeed-title-short">
	<?php esc_html_e( 'Profile Revision', 'litespeed-cache' ); ?>
</h3>

<?php if ( ! empty( $summary['profile'] ) ) : ?>
<p>
	<?php
	printf(
		esc_html__( 'Last applied the %1$s profile %2$s', 'litespeed-cache' ),
		'<strong>' . ucwords( $summary['profile'] ) . '</strong>',
		Utility::readable_time( $summary['profile_time'] )
	);
	?>
</p>

<div><a
		href="<?php echo Utility::build_url( Router::ACTION_PROFILES, Profiles::TYPE_REVERT ); ?>"
		class="button button-secondary" data-litespeed-cfm="<?php esc_html_e( 'This will revert to settings before this profile has been applied. Any changes made after will be lost. Do you want to continue?', 'litespeed-cache' ) ; ?>"
	>
	<?php esc_html_e( 'Revert to Previous Settings', 'litespeed-cache' ); ?>
</a></div>

<?php else: ?>
	<p class="litespeed-desc"><?php esc_html_e( 'No revisions are available', 'litespeed-cache' ); ?></p>
<?php endif; ?>

