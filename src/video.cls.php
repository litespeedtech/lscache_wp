<?php
/**
 * Video lazy-load helper: provider registry, URL parsing, thumbnail resolution.
 *
 * Used by the "Load Video Image" sub-option of iframe lazy load to convert
 * known video iframes (YouTube, Vimeo, Wistia, Dailymotion) into a thumbnail
 * placeholder with a click-to-play button.
 *
 * @package LiteSpeed
 * @since 7.9
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Class Video
 */
class Video extends Root {

	const LOG_TAG = '🎬';

	const THUMB_TRANSIENT_PREFIX = 'litespeed_video_thumb_';
	const THUMB_TTL              = 30 * DAY_IN_SECONDS;
	const THUMB_MISS_TTL         = 10 * MINUTE_IN_SECONDS;
	const THUMB_MISS_SENTINEL    = '__miss';
	const OEMBED_TIMEOUT         = 3;

	/**
	 * Built-in + filter-extended provider registry, cached per request.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function providers() {
		static $cache = null;
		if ( null !== $cache ) {
			return $cache;
		}
		$providers = array(
			array(
				'name'            => 'youtube',
				'host_match'      => array( 'youtube.com', 'youtu.be', 'youtube-nocookie.com' ),
				'id_pattern'      => '#(?:v=|/embed/|youtu\.be/)([A-Za-z0-9_-]{6,20})#',
				'thumb_resolver'  => 'direct',
				'thumb_url'       => 'https://i.ytimg.com/vi/{id}/hqdefault.jpg',
				'oembed_endpoint' => '',
				'autoplay_param'  => 'autoplay=1',
			),
			array(
				'name'            => 'vimeo',
				'host_match'      => array( 'vimeo.com', 'player.vimeo.com' ),
				'id_pattern'      => '#(?:vimeo\.com/(?:video/)?|player\.vimeo\.com/video/)(\d+)#',
				'thumb_resolver'  => 'oembed',
				'thumb_url'       => '',
				'oembed_endpoint' => 'https://vimeo.com/api/oembed.json?url=https%3A%2F%2Fvimeo.com%2F{id}',
				'autoplay_param'  => 'autoplay=1',
			),
			array(
				'name'            => 'wistia',
				'host_match'      => array( 'wistia.com', 'wistia.net', 'fast.wistia.com', 'fast.wistia.net' ),
				'id_pattern'      => '#/(?:medias|embed/iframe|embed/medias)/([A-Za-z0-9]+)#',
				'thumb_resolver'  => 'oembed',
				'thumb_url'       => '',
				'oembed_endpoint' => 'https://fast.wistia.com/oembed.json?url={url}',
				'autoplay_param'  => 'autoPlay=true',
			),
			array(
				'name'            => 'dailymotion',
				'host_match'      => array( 'dailymotion.com', 'dai.ly' ),
				'id_pattern'      => '#(?:dailymotion\.com/(?:embed/)?video/|dai\.ly/)([A-Za-z0-9]+)#',
				'thumb_resolver'  => 'direct',
				'thumb_url'       => 'https://www.dailymotion.com/thumbnail/video/{id}',
				'oembed_endpoint' => '',
				'autoplay_param'  => 'autoplay=1',
			),
		);

		$cache = apply_filters( 'litespeed_video_lazy_providers', $providers );
		return $cache;
	}

	/**
	 * Match a URL against the registry.
	 *
	 * @param string $url Iframe src URL.
	 * @return array{provider:array<string,mixed>,id:string}|null
	 */
	public static function extract_provider( $url ) {
		if ( ! $url || ! is_string( $url ) ) {
			return null;
		}
		foreach ( self::providers() as $provider ) {
			if ( empty( $provider['host_match'] ) || ! is_array( $provider['host_match'] ) || empty( $provider['id_pattern'] ) ) {
				continue;
			}
			foreach ( $provider['host_match'] as $host ) {
				if ( false === stripos( $url, $host ) ) {
					continue;
				}
				if ( preg_match( $provider['id_pattern'], $url, $m ) ) {
					return array(
						'provider' => $provider,
						'id'       => $m[1],
					);
				}
			}
		}
		return null;
	}

	/**
	 * Append the autoplay query param to a URL on click activation.
	 * Preserves any existing #fragment.
	 *
	 * @param array  $provider Provider record.
	 * @param string $url Iframe src URL.
	 * @return string
	 */
	public static function ensure_autoplay( $provider, $url ) {
		$param    = $provider['autoplay_param'];
		$frag_pos = strpos( $url, '#' );
		$base     = false === $frag_pos ? $url : substr( $url, 0, $frag_pos );
		$frag     = false === $frag_pos ? '' : substr( $url, $frag_pos );

		if ( false !== strpos( $base, $param ) ) {
			return $url;
		}
		$sep = ( false === strpos( $base, '?' ) ) ? '?' : '&';
		return $base . $sep . $param . $frag;
	}

	/**
	 * Resolve a thumbnail URL with transient caching. Returns null on miss.
	 *
	 * @param array  $provider     Provider record.
	 * @param string $id           Extracted video ID.
	 * @param string $original_url Original iframe src (for oEmbed `{url}` substitution).
	 * @return string|null
	 */
	public static function get_thumbnail( $provider, $id, $original_url ) {
		$key = self::THUMB_TRANSIENT_PREFIX . $provider['name'] . '_' . md5( $id );

		$cached = get_transient( $key );
		if ( self::THUMB_MISS_SENTINEL === $cached ) {
			return apply_filters( 'litespeed_video_lazy_thumbnail', null, $provider['name'], $id );
		}
		if ( is_string( $cached ) && $cached ) {
			return apply_filters( 'litespeed_video_lazy_thumbnail', $cached, $provider['name'], $id );
		}

		$url = null;

		if ( 'direct' === $provider['thumb_resolver'] ) {
			$url = str_replace( '{id}', rawurlencode( $id ), $provider['thumb_url'] );
		} elseif ( 'oembed' === $provider['thumb_resolver'] && ! empty( $provider['oembed_endpoint'] ) ) {
			$endpoint = str_replace(
				array( '{id}', '{url}' ),
				array( rawurlencode( $id ), rawurlencode( $original_url ) ),
				$provider['oembed_endpoint']
			);
			$resp     = wp_remote_get( $endpoint, array( 'timeout' => self::OEMBED_TIMEOUT ) );
			if ( ! is_wp_error( $resp ) && 200 === wp_remote_retrieve_response_code( $resp ) ) {
				$body = json_decode( wp_remote_retrieve_body( $resp ), true );
				if ( is_array( $body ) && ! empty( $body['thumbnail_url'] ) ) {
					$url = $body['thumbnail_url'];
				}
			}
		}

		if ( $url ) {
			set_transient( $key, $url, self::THUMB_TTL );
		} else {
			set_transient( $key, self::THUMB_MISS_SENTINEL, self::THUMB_MISS_TTL );
		}

		return apply_filters( 'litespeed_video_lazy_thumbnail', $url, $provider['name'], $id );
	}

	/**
	 * Build the click-to-play facade <div> markup.
	 *
	 * The original <iframe> is removed from the rendered page; on click the JS
	 * reconstructs it using the attributes carried in `data-lsvf-attrs`.
	 *
	 * Aspect ratio is derived from numeric width/height attrs when both are present
	 * so the facade keeps the original video's shape; falls back to 16/9 otherwise.
	 *
	 * @param string      $load_src       Iframe src to use on click (with autoplay ensured).
	 * @param array       $preserve_attrs Attributes to re-apply on swap-in (skipping src).
	 * @param string|null $thumb_url      Resolved thumbnail or null.
	 * @return string
	 */
	public static function build_facade( $load_src, $preserve_attrs, $thumb_url ) {
		// The original iframe is gone from the buffer; only carry the attrs the JS
		// needs to rebuild a fresh iframe on click. src is held separately in
		// data-lsvf-src (with autoplay) so we drop it from the encoded blob.
		unset( $preserve_attrs['src'] );
		$attrs_encoded = base64_encode( wp_json_encode( $preserve_attrs ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		$w     = isset( $preserve_attrs['width'] ) ? trim( $preserve_attrs['width'] ) : '';
		$h     = isset( $preserve_attrs['height'] ) ? trim( $preserve_attrs['height'] ) : '';
		$style = '';
		if ( '' !== $w && '' !== $h && is_numeric( $w ) && is_numeric( $h ) && $h > 0 ) {
			// Preserve native dimensions: max-width keeps responsiveness; aspect-ratio keeps shape.
			$style .= 'max-width:' . intval( $w ) . 'px;aspect-ratio:' . intval( $w ) . '/' . intval( $h ) . ';';
		} else {
			$style .= 'aspect-ratio:16/9;';
		}
		if ( $thumb_url ) {
			$style .= 'background-image:url(' . esc_url( $thumb_url ) . ');';
		}

		$play_button = apply_filters( 'litespeed_video_lazy_facade_play_button', '<button type="button" aria-label="Play video" class="litespeed-video-play"></button>' );

		$html = '<div class="litespeed-video-facade"'
			. ' data-lsvf-src="' . esc_attr( $load_src ) . '"'
			. ' data-lsvf-attrs="' . esc_attr( $attrs_encoded ) . '"'
			. ' style="' . esc_attr( $style ) . '">'
			. $play_button
			. '</div>';

		return apply_filters( 'litespeed_video_lazy_facade_html', $html, $preserve_attrs, $load_src );
	}

	/**
	 * Substring-match check: returns true if $url contains any of the URL fragments
	 * registered via the `litespeed_video_lazy_skip_urls` filter.
	 *
	 * Example use:
	 *   add_filter( 'litespeed_video_lazy_skip_urls', function ( $urls ) {
	 *       $urls[] = 'youtube.com/embed/dQw4w9WgXcQ'; // partial match, no exact-equality required
	 *       $urls[] = '?keep_native=1';
	 *       return $urls;
	 *   } );
	 *
	 * @param string $url Iframe src to test.
	 * @return bool
	 */
	public static function url_skipped( $url ) {
		$list = apply_filters( 'litespeed_video_lazy_skip_urls', array() );
		if ( empty( $list ) || ! is_array( $list ) ) {
			return false;
		}
		foreach ( $list as $needle ) {
			if ( '' === $needle || ! is_string( $needle ) ) {
				continue;
			}
			if ( false !== strpos( $url, $needle ) ) {
				return true;
			}
		}
		return false;
	}
}
