<?php
/**
 * Helper to render small documentation/tooltips in the UI.
 *
 * @package LiteSpeed
 * @since   2.2.7
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Small utility view helpers for docs/warnings/links.
 */
class Doc {

	/**
	 * Show a notice when an option is effectively forced ON by Guest Mode.
	 *
	 * @since 5.5
	 *
	 * @param string $id Option id.
	 * @return void
	 */
	public static function maybe_on_by_gm( $id ) {
		if ( apply_filters( 'litespeed_conf', $id ) ) {
			return;
		}
		if ( ! apply_filters( 'litespeed_conf', Base::O_GUEST ) ) {
			return;
		}
		if ( ! apply_filters( 'litespeed_conf', Base::O_GUEST_OPTM ) ) {
			return;
		}
		echo '<font class="litespeed-warning">';
		echo wp_kses_post(
			'⚠️ ' .
			sprintf(
				__( 'This setting is %1$s for certain qualifying requests due to %2$s!', 'litespeed-cache' ),
				'<code>' . esc_html__( 'ON', 'litespeed-cache' ) . '</code>',
				esc_html( Lang::title( Base::O_GUEST_OPTM ) )
			)
		);
		self::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/general/#guest-optimization' );
		echo '</font>';
	}

	/**
	 * Warn that changes affect the crawler list.
	 *
	 * @since 4.3
	 * @return void
	 */
	public static function crawler_affected() {
		echo '<span class="litespeed-primary">';
		echo '⚠️ ' . esc_html__( 'This setting will regenerate crawler list and clear the disabled list!', 'litespeed-cache' );
		echo '</span>';
	}

	/**
	 * Privacy policy text for front-end disclosure.
	 *
	 * @since 2.2.7
	 *
	 * @return string Safe HTML string.
	 */
	public static function privacy_policy() {
		$text = esc_html__(
			'This site utilizes caching in order to facilitate a faster response time and better user experience. Caching potentially stores a duplicate copy of every web page that is on display on this site. All cache files are temporary, and are never accessed by any third party, except as necessary to obtain technical support from the cache plugin vendor. Cache files expire on a schedule set by the site administrator, but may easily be purged by the admin before their natural expiration, if necessary. We may use QUIC.cloud services to process & cache your data temporarily.',
			'litespeed-cache'
		);

		$link = sprintf(
			/* translators: %s: QUIC.cloud privacy policy URL */
			esc_html__( 'Please see %s for more details.', 'litespeed-cache' ),
			sprintf(
				'<a href="%1$s" target="_blank" rel="noopener noreferrer">%1$s</a>',
				esc_url( 'https://quic.cloud/privacy-policy/' )
			)
		);

		// Return as HTML (link already escaped).
		return $text . ' ' . $link;
	}

	/**
	 * Render (or return) a "Learn more" link.
	 *
	 * @since 2.4.2
	 *
	 * @param string $url           Destination URL.
	 * @param string $title         Optional link text. Defaults to "Learn More".
	 * @param bool   $self_tab      Open in self tab or new tab (adds target/_blank + rel).
	 * @param string $css_class     CSS class for the anchor.
	 * @param bool   $return_output Return instead of echo.
	 * @return string|void
	 */
	public static function learn_more( $url, $title = '', $self_tab = false, $css_class = '', $return_output = false ) {
		$css_class = $css_class ? $css_class : 'litespeed-learn-more';
		$title     = $title ? $title : esc_html__( 'Learn More', 'litespeed-cache' );

		$target_rel = $self_tab ? '' : ' target="_blank" rel="noopener noreferrer"';
		$anchor     = sprintf(
			' <a href="%s"%s class="%s">%s</a>',
			esc_url( $url ),
			$target_rel, // Already hardcoded/safe.
			esc_attr( $css_class ),
			wp_kses_post( $title )
		);

		if ( $return_output ) {
			return $anchor;
		}

		echo wp_kses_post( $anchor );
	}

	/**
	 * Output "One per line." helper text.
	 *
	 * @since 3.0
	 *
	 * @param bool $return_output Return the string instead of echoing.
	 * @return string|void
	 */
	public static function one_per_line( $return_output = false ) {
		$str = esc_html__( 'One per line.', 'litespeed-cache' );
		if ( $return_output ) {
			return $str;
		}
		echo esc_html( $str );
	}

	/**
	 * Output helper text about full/partial URL support.
	 *
	 * @since 3.4
	 *
	 * @param bool $string_only If true, say "strings" only; otherwise specify URLs/strings.
	 * @return void
	 */
	public static function full_or_partial_url( $string_only = false ) {
		if ( $string_only ) {
			echo esc_html__( 'Both full and partial strings can be used.', 'litespeed-cache' );
		} else {
			echo esc_html__( 'Both full URLs and partial strings can be used.', 'litespeed-cache' );
		}
	}

	/**
	 * Notice that a setting will edit .htaccess.
	 *
	 * @since 3.0
	 * @return void
	 */
	public static function notice_htaccess() {
		echo '<span class="litespeed-primary">';
		echo '⚠️ ' . esc_html__( 'This setting will edit the .htaccess file.', 'litespeed-cache' ) . ' ';
		self::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/toolbox/#edit-htaccess-tab' );
		echo '</span>';
	}

	/**
	 * Gentle reminder that QUIC.cloud queues are asynchronous.
	 *
	 * @since 5.3.1
	 *
	 * @param bool $return_output Return the HTML instead of echoing.
	 * @return string|void
	 */
	public static function queue_issues( $return_output = false ) {
		$link = self::learn_more( 'https://docs.litespeedtech.com/lscache/lscwp/troubleshoot/#quiccloud-queue-issues', '', false, '', true );

		$html = sprintf(
			'<div class="litespeed-desc">%s %s</div>',
			esc_html__( 'The queue is processed asynchronously. It may take time.', 'litespeed-cache' ),
			$link // already escaped.
		);

		if ( $return_output ) {
			return $html;
		}

		echo wp_kses_post( $html );
	}
}
