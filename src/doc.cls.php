<?php
// phpcs:ignoreFile

/**
 * The Doc class.
 *
 * @since       2.2.7
 * @package     LiteSpeed
 */

namespace LiteSpeed;

defined('WPINC') || exit();

class Doc {

	// protected static $_instance;

	/**
	 * Show option is actually ON by GM
	 *
	 * @since  5.5
	 * @access public
	 */
	public static function maybe_on_by_gm( $id ) {
		if (apply_filters('litespeed_conf', $id)) {
			return;
		}
		if (!apply_filters('litespeed_conf', Base::O_GUEST)) {
			return;
		}
		if (!apply_filters('litespeed_conf', Base::O_GUEST_OPTM)) {
			return;
		}
		echo '<font class="litespeed-warning">';
		echo '⚠️ ' .
			sprintf(
				__('This setting is %1$s for certain qualifying requests due to %2$s!', 'litespeed-cache'),
				'<code>' . __('ON', 'litespeed-cache') . '</code>',
				Lang::title(Base::O_GUEST_OPTM)
			);
		self::learn_more('https://docs.litespeedtech.com/lscache/lscwp/general/#guest-optimization');
		echo '</font>';
	}

	/**
	 * Changes affect crawler list warning
	 *
	 * @since  4.3
	 * @access public
	 */
	public static function crawler_affected() {
		echo '<font class="litespeed-primary">';
		echo '⚠️ ' . __('This setting will regenerate crawler list and clear the disabled list!', 'litespeed-cache');
		echo '</font>';
	}

	/**
	 * Privacy policy
	 *
	 * @since 2.2.7
	 * @access public
	 */
	public static function privacy_policy() {
		return __(
			'This site utilizes caching in order to facilitate a faster response time and better user experience. Caching potentially stores a duplicate copy of every web page that is on display on this site. All cache files are temporary, and are never accessed by any third party, except as necessary to obtain technical support from the cache plugin vendor. Cache files expire on a schedule set by the site administrator, but may easily be purged by the admin before their natural expiration, if necessary. We may use QUIC.cloud services to process & cache your data temporarily.',
			'litespeed-cache'
		) .
			sprintf(
				__('Please see %s for more details.', 'litespeed-cache'),
				'<a href="https://quic.cloud/privacy-policy/" target="_blank">https://quic.cloud/privacy-policy/</a>'
			);
	}

	/**
	 * Learn more link
	 *
	 * @since  2.4.2
	 * @access public
	 */
	public static function learn_more( $url, $title = false, $self = false, $class = false, $return = false ) {
		if (!$class) {
			$class = 'litespeed-learn-more';
		}

		if (!$title) {
			$title = __('Learn More', 'litespeed-cache');
		}

		$self = $self ? '' : "target='_blank'";

		$txt = " <a href='$url' $self class='$class'>$title</a>";

		if ($return) {
			return $txt;
		}

		echo $txt;
	}

	/**
	 * One per line
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function one_per_line( $return = false ) {
		$str = __('One per line.', 'litespeed-cache');
		if ($return) {
			return $str;
		}
		echo $str;
	}

	/**
	 * One per line
	 *
	 * @since  3.4
	 * @access public
	 */
	public static function full_or_partial_url( $string_only = false ) {
		if ($string_only) {
			echo __('Both full and partial strings can be used.', 'litespeed-cache');
		} else {
			echo __('Both full URLs and partial strings can be used.', 'litespeed-cache');
		}
	}

	/**
	 * Notice to edit .htaccess
	 *
	 * @since  3.0
	 * @access public
	 */
	public static function notice_htaccess() {
		echo '<font class="litespeed-primary">';
		echo '⚠️ ' . __('This setting will edit the .htaccess file.', 'litespeed-cache');
		echo ' <a href="https://docs.litespeedtech.com/lscache/lscwp/toolbox/#edit-htaccess-tab" target="_blank" class="litespeed-learn-more">' .
			__('Learn More', 'litespeed-cache') .
			'</a>';
		echo '</font>';
	}

	/**
	 * Gentle reminder that web services run asynchronously
	 *
	 * @since  5.3.1
	 * @access public
	 */
	public static function queue_issues( $return = false ) {
		$str =
			'<div class="litespeed-desc">' .
			__('The queue is processed asynchronously. It may take time.', 'litespeed-cache') .
			self::learn_more('https://docs.litespeedtech.com/lscache/lscwp/troubleshoot/#quiccloud-queue-issues', false, false, false, true) .
			'</div>';
		if ($return) {
			return $str;
		}
		echo $str;
	}
}
