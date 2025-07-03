<?php
/**
 * LiteSpeed Cache Purge Interface
 *
 * Renders the purge interface for LiteSpeed Cache, allowing users to clear various cache types and purge specific content.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$_panels = array(
	array(
		'title'      => esc_html__( 'Purge Front Page', 'litespeed-cache' ),
		'desc'       => esc_html__( 'This will Purge Front Page only', 'litespeed-cache' ),
		'icon'       => 'purge-front',
		'append_url' => Purge::TYPE_PURGE_FRONTPAGE,
	),
	array(
		'title'      => esc_html__( 'Purge Pages', 'litespeed-cache' ),
		'desc'       => esc_html__( 'This will Purge Pages only', 'litespeed-cache' ),
		'icon'       => 'purge-pages',
		'append_url' => Purge::TYPE_PURGE_PAGES,
	),
);

foreach ( Tag::$error_code_tags as $code ) {
	$_panels[] = array(
		'title'      => sprintf( esc_html__( 'Purge %s Error', 'litespeed-cache' ), esc_html( $code ) ),
		'desc'       => sprintf( esc_html__( 'Purge %s error pages', 'litespeed-cache' ), esc_html( $code ) ),
		'icon'       => 'purge-' . esc_attr( $code ),
		'append_url' => Purge::TYPE_PURGE_ERROR . esc_attr( $code ),
	);
}

$_panels[] = array(
	'title'      => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - LSCache',
	'desc'       => esc_html__( 'Purge the LiteSpeed cache entries created by this plugin', 'litespeed-cache' ),
	'icon'       => 'purge-all',
	'append_url' => Purge::TYPE_PURGE_ALL_LSCACHE,
);

$_panels[] = array(
	'title'      => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'CSS/JS Cache', 'litespeed-cache' ),
	'desc'       => esc_html__( 'This will purge all minified/combined CSS/JS entries only', 'litespeed-cache' ),
	'icon'       => 'purge-cssjs',
	'append_url' => Purge::TYPE_PURGE_ALL_CSSJS,
);

if ( defined( 'LSCWP_OBJECT_CACHE' ) ) {
	$_panels[] = array(
		'title'      => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'Object Cache', 'litespeed-cache' ),
		'desc'       => esc_html__( 'Purge all the object caches', 'litespeed-cache' ),
		'icon'       => 'purge-object',
		'append_url' => Purge::TYPE_PURGE_ALL_OBJECT,
	);
}

if ( Router::opcache_enabled() ) {
	$_panels[] = array(
		'title'      => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'Opcode Cache', 'litespeed-cache' ),
		'desc'       => esc_html__( 'Reset the entire opcode cache', 'litespeed-cache' ),
		'icon'       => 'purge-opcache',
		'append_url' => Purge::TYPE_PURGE_ALL_OPCACHE,
	);
}

if ( $this->has_cache_folder( 'ccss' ) ) {
	$_panels[] = array(
		'title'      => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'Critical CSS', 'litespeed-cache' ),
		'desc'       => esc_html__( 'This will delete all generated critical CSS files', 'litespeed-cache' ),
		'icon'       => 'purge-cssjs',
		'append_url' => Purge::TYPE_PURGE_ALL_CCSS,
	);
}

if ( $this->has_cache_folder( 'ucss' ) ) {
	$_panels[] = array(
		'title'      => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'Unique CSS', 'litespeed-cache' ),
		'desc'       => esc_html__( 'This will delete all generated unique CSS files', 'litespeed-cache' ),
		'icon'       => 'purge-cssjs',
		'append_url' => Purge::TYPE_PURGE_ALL_UCSS,
	);
}

if ( $this->has_cache_folder( 'localres' ) ) {
	$_panels[] = array(
		'title'      => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'Localized Resources', 'litespeed-cache' ),
		'desc'       => esc_html__( 'This will delete all localized resources', 'litespeed-cache' ),
		'icon'       => 'purge-cssjs',
		'append_url' => Purge::TYPE_PURGE_ALL_LOCALRES,
	);
}

if ( $this->has_cache_folder( 'lqip' ) ) {
	$_panels[] = array(
		'title'      => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'LQIP Cache', 'litespeed-cache' ),
		'desc'       => esc_html__( 'This will delete all generated image LQIP placeholder files', 'litespeed-cache' ),
		'icon'       => 'purge-front',
		'append_url' => Purge::TYPE_PURGE_ALL_LQIP,
	);
}

if ( $this->has_cache_folder( 'avatar' ) ) {
	$_panels[] = array(
		'title'      => esc_html__( 'Purge All', 'litespeed-cache' ) . ' - ' . esc_html__( 'Gravatar Cache', 'litespeed-cache' ),
		'desc'       => esc_html__( 'This will delete all cached Gravatar files', 'litespeed-cache' ),
		'icon'       => 'purge-cssjs',
		'append_url' => Purge::TYPE_PURGE_ALL_AVATAR,
	);
}

$_panels[] = array(
	'title'      => esc_html__( 'Purge All', 'litespeed-cache' ),
	'desc'       => esc_html__( 'Purge the cache entries created by this plugin except for Critical CSS & Unique CSS & LQIP caches', 'litespeed-cache' ),
	'icon'       => 'purge-all',
	'title_cls'  => 'litespeed-warning',
	'newline'    => true,
	'append_url' => Purge::TYPE_PURGE_ALL,
);

if ( ! is_multisite() || is_network_admin() ) {
	$_panels[] = array(
		'title'     => esc_html__( 'Empty Entire Cache', 'litespeed-cache' ),
		'desc'      => esc_html__( 'Clears all cache entries related to this site, including other web applications.', 'litespeed-cache' ) . ' <b>' . esc_html__( 'This action should only be used if things are cached incorrectly.', 'litespeed-cache' ) . '</b>',
		'tag'       => Core::ACTION_PURGE_EMPTYCACHE,
		'icon'      => 'empty-cache',
		'title_cls' => 'litespeed-danger',
		'cfm'       => esc_html__( 'This will clear EVERYTHING inside the cache.', 'litespeed-cache' ) . ' ' . esc_html__( 'This may cause heavy load on the server.', 'litespeed-cache' ) . ' ' . esc_html__( 'If only the WordPress site should be purged, use Purge All.', 'litespeed-cache' ),
	);
}

?>

<?php require_once LSCWP_DIR . 'tpl/inc/check_cache_disabled.php'; ?>

<h3 class="litespeed-title">
	<?php esc_html_e( 'Purge', 'litespeed-cache' ); ?>
	<?php Doc::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/toolbox/#purge-tab' ); ?>
</h3>

<div class="litespeed-panel-wrapper litespeed-cards-wrapper">
	<?php foreach ( $_panels as $panel ) : ?>
		<?php
		$action_tag = ! empty( $panel['tag'] ) ? $panel['tag'] : Router::ACTION_PURGE;
		$append_url = ! empty( $panel['append_url'] ) ? $panel['append_url'] : false;
		$cfm        = ! empty( $panel['cfm'] ) ? Str::trim_quotes( $panel['cfm'] ) : false;
		?>
		<?php if ( ! empty( $panel['newline'] ) ) : ?>
			<div class="litespeed-col-br"></div>
		<?php endif; ?>
		<a class="litespeed-panel postbox" href="<?php echo esc_url( Utility::build_url( $action_tag, $append_url ) ); ?>" data-litespeed-cfm="<?php echo esc_attr( $cfm ); ?>">
			<section class="litespeed-panel-wrapper-icon">
				<span class="litespeed-panel-icon-<?php echo esc_attr( $panel['icon'] ); ?>"></span>
			</section>
			<section class="litespeed-panel-content">
				<div class="litespeed-h3 <?php echo ! empty( $panel['title_cls'] ) ? esc_attr( $panel['title_cls'] ) : ''; ?>">
					<?php echo esc_html( $panel['title'] ); ?>
				</div>
				<span class="litespeed-panel-para"><?php echo wp_kses_post( $panel['desc'] ); ?></span>
			</section>
		</a>
	<?php endforeach; ?>
</div>

<?php
if ( is_multisite() && is_network_admin() ) {
	return;
}
?>

<h3 class="litespeed-title">
	<?php esc_html_e( 'Purge By...', 'litespeed-cache' ); ?>
</h3>
<p class="litespeed-description">
	<?php esc_html_e( 'Select below for "Purge by" options.', 'litespeed-cache' ); ?>
	<?php Doc::one_per_line(); ?>
</p>

<?php $this->form_action( Core::ACTION_PURGE_BY ); ?>
	<div class="litespeed-row">
		<div class="litespeed-switch litespeed-mini litespeed-right20 litespeed-margin-bottom10">
			<?php $val = Admin_Display::PURGEBY_CAT; ?>
			<input type="radio" autocomplete="off" name="<?php echo esc_attr( Admin_Display::PURGEBYOPT_SELECT ); ?>" id="purgeby_option_category" value="<?php echo esc_attr( $val ); ?>" checked />
			<label for="purgeby_option_category"><?php esc_html_e( 'Category', 'litespeed-cache' ); ?></label>

			<?php $val = Admin_Display::PURGEBY_PID; ?>
			<input type="radio" autocomplete="off" name="<?php echo esc_attr( Admin_Display::PURGEBYOPT_SELECT ); ?>" id="purgeby_option_postid" value="<?php echo esc_attr( $val ); ?>" />
			<label for="purgeby_option_postid"><?php esc_html_e( 'Post ID', 'litespeed-cache' ); ?></label>

			<?php $val = Admin_Display::PURGEBY_TAG; ?>
			<input type="radio" autocomplete="off" name="<?php echo esc_attr( Admin_Display::PURGEBYOPT_SELECT ); ?>" id="purgeby_option_tag" value="<?php echo esc_attr( $val ); ?>" />
			<label for="purgeby_option_tag"><?php esc_html_e( 'Tag', 'litespeed-cache' ); ?></label>

			<?php $val = Admin_Display::PURGEBY_URL; ?>
			<input type="radio" autocomplete="off" name="<?php echo esc_attr( Admin_Display::PURGEBYOPT_SELECT ); ?>" id="purgeby_option_url" value="<?php echo esc_attr( $val ); ?>" />
			<label for="purgeby_option_url"><?php esc_html_e( 'URL', 'litespeed-cache' ); ?></label>
		</div>

		<div class="litespeed-cache-purgeby-text litespeed-desc">
			<div class="" data-purgeby="<?php echo esc_attr( Admin_Display::PURGEBY_CAT ); ?>">
				<?php printf( esc_html__( 'Purge pages by category name - e.g. %2$s should be used for the URL %1$s.', 'litespeed-cache' ), '<code>http://example.com/category/category-name/</code>', '<code>category-name</code>' ); ?>
			</div>
			<div class="litespeed-hide" data-purgeby="<?php echo esc_attr( Admin_Display::PURGEBY_PID ); ?>">
				<?php esc_html_e( 'Purge pages by post ID.', 'litespeed-cache' ); ?>
			</div>
			<div class="litespeed-hide" data-purgeby="<?php echo esc_attr( Admin_Display::PURGEBY_TAG ); ?>">
				<?php printf( esc_html__( 'Purge pages by tag name - e.g. %2$s should be used for the URL %1$s.', 'litespeed-cache' ), '<code>http://example.com/tag/tag-name/</code>', '<code>tag-name</code>' ); ?>
			</div>
			<div class="litespeed-hide" data-purgeby="<?php echo esc_attr( Admin_Display::PURGEBY_URL ); ?>">
				<?php esc_html_e( 'Purge pages by relative or full URL.', 'litespeed-cache' ); ?>
				<?php printf( esc_html__( 'e.g. Use %1$s or %2$s.', 'litespeed-cache' ), '<code>/2016/02/24/hello-world/</code>', '<code>http://example.com/2016/02/24/hello-world/</code>' ); ?>
			</div>
		</div>
	</div>

	<p>
		<textarea name="<?php echo esc_attr( Admin_Display::PURGEBYOPT_LIST ); ?>" rows="5" class="litespeed-textarea"></textarea>
	</p>

	<p>
		<button type="submit" class="button button-primary"><?php esc_html_e( 'Purge List', 'litespeed-cache' ); ?></button>
	</p>
</form>
<script>
(function ($) {
	function setCookie(name, value, days) {
		var expires = "";
		if (days) {
			var date = new Date();
			date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
			expires = "; expires=" + date.toUTCString();
		}
		document.cookie = name + "=" + (value || "") + expires + "; path=/; SameSite=Strict";
	}

	function getCookie(name) {
		var nameEQ = name + "=";
		var ca = document.cookie.split(';');
		for (var i = 0; i < ca.length; i++) {
			var c = ca[i];
			while (c.charAt(0) == ' ') {
				c = c.substring(1, c.length);
			}
			if (c.indexOf(nameEQ) == 0) {
				return c.substring(nameEQ.length, c.length);
			}
		}
		return null;
	}

	jQuery(document).ready(function () {
		var savedPurgeBy = getCookie('litespeed_purgeby_option');
		if (savedPurgeBy) {
			$('input[name="<?php echo esc_attr( Admin_Display::PURGEBYOPT_SELECT ); ?>"][value="' + savedPurgeBy + '"]').prop('checked', true);
			$('[data-purgeby]').addClass('litespeed-hide');
			$('[data-purgeby="' + savedPurgeBy + '"]').removeClass('litespeed-hide');
		}
		// Manage page -> purge by
		$('[name=purgeby]').on('change', function (event) {
			$('[data-purgeby]').addClass('litespeed-hide');
			$('[data-purgeby=' + this.value + ']').removeClass('litespeed-hide');
			setCookie('litespeed_purgeby_option', this.value, 30);
		});
	});
})(jQuery);
</script>