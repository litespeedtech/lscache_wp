<?php
/**
 * LiteSpeed Cache Warning Notice
 *
 * Displays warnings if LiteSpeed Cache functionality is unavailable due to server or plugin configuration issues.
 *
 * @package LiteSpeed
 * @since 1.0.0
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

$reasons = array();

if ( ! defined( 'LITESPEED_ALLOWED' ) ) {
    if ( defined( 'LITESPEED_SERVER_TYPE' ) && LITESPEED_SERVER_TYPE === 'NONE' ) {
        $reasons[] = array(
            'title' => esc_html__( 'To use the caching functions you must have a LiteSpeed web server or be using QUIC.cloud CDN.', 'litespeed-cache' ),
            'link'  => 'https://docs.litespeedtech.com/lscache/lscwp/faq/#why-do-the-cache-features-require-a-litespeed-server',
        );
    } else {
        $reasons[] = array(
            'title' => esc_html__( 'Please enable the LSCache Module at the server level, or ask your hosting provider.', 'litespeed-cache' ),
            'link'  => 'https://docs.litespeedtech.com/lscache/lscwp/#server-level-prerequisites',
        );
    }
} elseif ( ! defined( 'LITESPEED_ON' ) ) {
    $reasons[] = array(
        'title' => esc_html__( 'Please enable LiteSpeed Cache in the plugin settings.', 'litespeed-cache' ),
        'link'  => 'https://docs.litespeedtech.com/lscache/lscwp/cache/#enable-cache',
    );
}

if ( $reasons ) : ?>
    <div class="litespeed-callout notice notice-error inline">
        <h4><?php esc_html_e( 'WARNING', 'litespeed-cache' ); ?></h4>
        <p>
            <?php esc_html_e( 'LSCache caching functions on this page are currently unavailable!', 'litespeed-cache' ); ?>
        </p>
        <ul class="litespeed-list">
            <?php foreach ( $reasons as $reason ) : ?>
                <li>
                    <?php echo esc_html( $reason['title'] ); ?>
                    <a href="<?php echo esc_url( $reason['link'] ); ?>" target="_blank" class="litespeed-learn-more"><?php esc_html_e( 'Learn More', 'litespeed-cache' ); ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
