<?php
/**
 * LiteSpeed Cache Slack Community Banner
 *
 * Displays a promotional banner inviting users to join the LiteSpeed Slack community.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;
?>

<div class="litespeed-wrap notice notice-info litespeed-banner-promo-full" id="litespeed-banner-promo-slack">
	<div class="litespeed-banner-promo-logo"></div>

	<div class="litespeed-banner-promo-content">
		<h3 class="litespeed-banner-title"><?php esc_html_e( 'Welcome to LiteSpeed', 'litespeed-cache' ); ?></h3>
		<div class="litespeed-banner-description">
			<div class="litespeed-banner-description-padding-right-15">
				<p class="litespeed-banner-description-content">
					<?php esc_html_e( 'Want to connect with other LiteSpeed users?', 'litespeed-cache' ); ?>
					<?php
					printf(
						/* translators: %s: Link to LiteSpeed Slack community */
						esc_html__( 'Join the %s community.', 'litespeed-cache' ),
						'<a href="https://join.slack.com/t/golitespeed/shared_invite/enQtMzE5ODgxMTUyNTgzLTNiNWQ1MWZlYmI4YjEzNTM4NjdiODY2YTQ0OWVlMzBlNGZkY2E3Y2E4MjIzNmNmZmU0ZjIyNWM1ZmNmMWRlOTk" target="_blank" class="litespeed-banner-promo-slack-textlink" rel="noopener">LiteSpeed Slack</a>'
					);
					?>
				</p>
				<p class="litespeed-banner-promo-slack-line2">
					golitespeed.slack.com
				</p>
			</div>
			<div>
				<h3 class="litespeed-banner-button-link">
					<a href="https://join.slack.com/t/golitespeed/shared_invite/enQtMzE5ODgxMTUyNTgzLTNiNWQ1MWZlYmI4YjEzNTM4NjdiODY2YTQ0OWVlMzBlNGZkY2E3Y2E4MjIzNmNmZmU0ZjIyNWM1ZmNmMWRlOTk" target="_blank" rel="noopener">
						<?php esc_html_e( 'Join Us on Slack', 'litespeed-cache' ); ?>
					</a>
				</h3>
			</div>
		</div>
	</div>
	<div>
		<?php $dismiss_url = Utility::build_url( Core::ACTION_DISMISS, GUI::TYPE_DISMISS_PROMO, false, null, array( 'promo_tag' => 'slack' ) ); ?>
		<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'litespeed-cache' ); ?></span>
		<a href="<?php echo esc_url( $dismiss_url ); ?>" class="litespeed-notice-dismiss"><?php esc_html_e( 'Dismiss', 'litespeed-cache' ); ?></a>
	</div>
</div>