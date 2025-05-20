<?php
/**
 * QUIC.cloud Promotion Banner
 *
 * Displays a promotional banner for QUIC.cloud services with a tweet option to earn credits.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

?>

<div class="litespeed-wrap notice notice-success litespeed-banner-promo-qc">

	<div class="litespeed-banner-promo-qc-content">

		<div class="litespeed-banner-promo-qc-description">
			<h2><?php esc_html_e( 'You just unlocked a promotion from QUIC.cloud!', 'litespeed-cache' ); ?></h2>
			<p>
				<?php
				printf(
					esc_html__( 'Spread the love and earn %s credits to use in our QUIC.cloud online services.', 'litespeed-cache' ),
					'<strong>' . absint($this->_summary['promo'][0]['quota']) . '</strong>'
				);
				?>
				</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url($this->_summary['promo'][0]['url']); ?>" target="_blank">
					<?php
					printf(
						esc_html__( 'Send to twitter to get %s bonus', 'litespeed-cache' ),
						absint($this->_summary['promo'][0]['quota'])
					);
					?>
				</a>
				<a href="https://www.quic.cloud/faq/#credit" target="_blank"><?php esc_html_e( 'Learn more', 'litespeed-cache' ); ?></a>
			</p>
		</div>

		<div class="litespeed-banner-promo-qc-preview">
			<h4 class="litespeed-tweet-preview-title"><?php esc_html_e( 'Tweet preview', 'litespeed-cache' ); ?></h4>
			<div class="litespeed-tweet-preview">

				<div class="litespeed-tweet-img"><img src="<?php echo esc_url($this->_summary['promo'][0]['image']); ?>"></div>

				<div class="litespeed-tweet-preview-content">
					<p class="litespeed-tweet-text"><?php echo esc_html($this->_summary['promo'][0]['content']); ?></p>

					<div class="litespeed-tweet-cta">
						<a href="<?php echo esc_url($this->_summary['promo'][0]['url']); ?>" class="litespeed-tweet-btn" target="_blank"><svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 250 250" xml:space="preserve">
								<path class="st0" d="M78.6,226.6c94.3,0,145.9-78.2,145.9-145.9c0-2.2,0-4.4-0.1-6.6c10-7.3,18.7-16.3,25.6-26.5
								c-9.4,4.1-19.3,6.9-29.5,8.1c10.7-6.4,18.7-16.5,22.5-28.4c-10.1,6-21.1,10.2-32.6,12.4c-19.4-20.7-51.9-21.7-72.6-2.2
								c-13.3,12.5-19,31.2-14.8,49C81.9,84.3,43.4,64.8,17.4,32.8c-13.6,23.4-6.7,53.4,15.9,68.5c-8.2-0.2-16.1-2.4-23.3-6.4
								c0,0.2,0,0.4,0,0.6c0,24.4,17.2,45.4,41.2,50.3c-7.6,2.1-15.5,2.4-23.2,0.9c6.7,20.9,26,35.2,47.9,35.6c-18.2,14.3-40.6,22-63.7,22
								c-4.1,0-8.2-0.3-12.2-0.7C23.5,218.6,50.7,226.6,78.6,226.6" />
							</svg>
							<?php esc_html_e( 'Tweet this', 'litespeed-cache' ); ?>
						</a>
					</div>
				</div>

			</div>

		</div>
	</div>

	<div>
		<?php $dismiss_url = Utility::build_url( Router::ACTION_CLOUD, Cloud::TYPE_CLEAR_PROMO ); ?>
		<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice', 'litespeed-cache' ); ?>.</span>
		<a href="<?php echo esc_url($dismiss_url); ?>" class="litespeed-notice-dismiss">X</a>
	</div>
</div>