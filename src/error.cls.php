<?php
// phpcs:ignoreFile
/**
 * The error class.
 *
 * @since       3.0
 * @package     LiteSpeed
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit();

/**
 * Class Error
 *
 * Handles error message translation and throwing for LiteSpeed Cache.
 *
 * @since 3.0
 */
class Error {

	/**
	 * Error code mappings to numeric values.
	 *
	 * @since 3.0
	 * @var array
	 */
	private static $CODE_SET = array(
		'HTA_LOGIN_COOKIE_INVALID' => 4300, // .htaccess did not find.
		'HTA_DNF'                 => 4500, // .htaccess did not find.
		'HTA_BK'                  => 9010, // backup
		'HTA_R'                   => 9041, // read htaccess
		'HTA_W'                   => 9042, // write
		'HTA_GET'                 => 9030, // failed to get
	);

	/**
	 * Throw an error with message
	 *
	 * Throws an exception with the translated error message.
	 *
	 * @since  3.0
	 * @access public
	 * @param string $code Error code.
	 * @param mixed  $args Optional arguments for message formatting.
	 * @throws \Exception Always throws an exception with the error message.
	 */
	public static function t( $code, $args = null ) {
		throw new \Exception( wp_kses_post( self::msg( $code, $args ) ) );
	}

	/**
	 * Translate an error to description
	 *
	 * Converts error codes to human-readable messages.
	 *
	 * @since  3.0
	 * @access public
	 * @param string $code Error code.
	 * @param mixed  $args Optional arguments for message formatting.
	 * @return string Translated error message.
	 */
	public static function msg( $code, $args = null ) {
		switch ( $code ) {
			case 'qc_setup_required':
				$msg =
					sprintf(
						__( 'You will need to finish %s setup to use the online services.', 'litespeed-cache' ),
						'<strong>QUIC.cloud</strong>'
					) .
					Doc::learn_more(
						admin_url( 'admin.php?page=litespeed-general' ),
						__( 'Click here to set.', 'litespeed-cache' ),
						true,
						false,
						true
					);
				break;

			case 'out_of_daily_quota':
				$msg  = __( 'You have used all of your daily quota for today.', 'litespeed-cache' );
				$msg .=
					' ' .
					Doc::learn_more(
						'https://docs.quic.cloud/billing/services/#daily-limits-on-free-quota-usage',
						__( 'Learn more or purchase additional quota.', 'litespeed-cache' ),
						false,
						false,
						true
					);
				break;

			case 'out_of_quota':
				$msg  = __( 'You have used all of your quota left for current service this month.', 'litespeed-cache' );
				$msg .=
					' ' .
					Doc::learn_more(
						'https://docs.quic.cloud/billing/services/#daily-limits-on-free-quota-usage',
						__( 'Learn more or purchase additional quota.', 'litespeed-cache' ),
						false,
						false,
						true
					);
				break;

			case 'too_many_requested':
				$msg = __( 'You have too many requested images, please try again in a few minutes.', 'litespeed-cache' );
				break;

			case 'too_many_notified':
				$msg = __( 'You have images waiting to be pulled. Please wait for the automatic pull to complete, or pull them down manually now.', 'litespeed-cache' );
				break;

			case 'empty_list':
				$msg = __( 'The image list is empty.', 'litespeed-cache' );
				break;

			case 'lack_of_param':
				$msg = __( 'Not enough parameters. Please check if the QUIC.cloud connection is set correctly', 'litespeed-cache' );
				break;

			case 'unfinished_queue':
				$msg = __( 'There is proceeding queue not pulled yet.', 'litespeed-cache' );
				break;

			case 0 === strpos( $code, 'unfinished_queue ' ):
				$msg = sprintf(
					__( 'There is proceeding queue not pulled yet. Queue info: %s.', 'litespeed-cache' ),
					'<code>' . substr( $code, strlen( 'unfinished_queue ' ) ) . '</code>'
				);
				break;

			case 'err_alias':
				$msg = __( 'The site is not a valid alias on QUIC.cloud.', 'litespeed-cache' );
				break;

			case 'site_not_registered':
				$msg = __( 'The site is not registered on QUIC.cloud.', 'litespeed-cache' );
				break;

			case 'err_key':
				$msg = __( 'The QUIC.cloud connection is not correct. Please try to sync your QUIC.cloud connection again.', 'litespeed-cache' );
				break;

			case 'heavy_load':
				$msg = __( 'The current server is under heavy load.', 'litespeed-cache' );
				break;

			case 'redetect_node':
				$msg = __( 'Online node needs to be redetected.', 'litespeed-cache' );
				break;

			case 'err_overdraw':
				$msg = __( 'Credits are not enough to proceed the current request.', 'litespeed-cache' );
				break;

			case 'W':
				$msg = __( '%s file not writable.', 'litespeed-cache' );
				break;

			case 'HTA_DNF':
				if ( ! is_array( $args ) ) {
					$args = array( '<code>' . $args . '</code>' );
				}
				$args[] = '.htaccess';
				$msg    = __( 'Could not find %1$s in %2$s.', 'litespeed-cache' );
				break;

			case 'HTA_LOGIN_COOKIE_INVALID':
				$msg = sprintf( __( 'Invalid login cookie. Please check the %s file.', 'litespeed-cache' ), '.htaccess' );
				break;

			case 'HTA_BK':
				$msg = sprintf( __( 'Failed to back up %s file, aborted changes.', 'litespeed-cache' ), '.htaccess' );
				break;

			case 'HTA_R':
				$msg = sprintf( __( '%s file not readable.', 'litespeed-cache' ), '.htaccess' );
				break;

			case 'HTA_W':
				$msg = sprintf( __( '%s file not writable.', 'litespeed-cache' ), '.htaccess' );
				break;

			case 'HTA_GET':
				$msg = sprintf( __( 'Failed to get %s file contents.', 'litespeed-cache' ), '.htaccess' );
				break;

			case 'failed_tb_creation':
				$msg = __( 'Failed to create table %1$s! SQL: %2$s.', 'litespeed-cache' );
				break;

			case 'crawler_disabled':
				$msg = __( 'Crawler disabled by the server admin.', 'litespeed-cache' );
				break;

			case 'try_later': // QC error code
				$msg = __( 'Previous request too recent. Please try again later.', 'litespeed-cache' );
				break;

			case 0 === strpos( $code, 'try_later ' ):
				$msg = sprintf(
					__( 'Previous request too recent. Please try again after %s.', 'litespeed-cache' ),
					'<code>' . Utility::readable_time( substr( $code, strlen( 'try_later ' ) ), 3600, true ) . '</code>'
				);
				break;

			case 'waiting_for_approval':
				$msg = __( 'Your application is waiting for approval.', 'litespeed-cache' );
				break;

			case 'callback_fail_hash':
				$msg = __( 'The callback validation to your domain failed due to hash mismatch.', 'litespeed-cache' );
				break;

			case 'callback_fail':
				$msg = __( 'The callback validation to your domain failed. Please make sure there is no firewall blocking our servers.', 'litespeed-cache' );
				break;

			case substr( $code, 0, 14 ) === 'callback_fail ':
				$msg =
					__( 'The callback validation to your domain failed. Please make sure there is no firewall blocking our servers. Response code: ', 'litespeed-cache' ) .
					substr( $code, 14 );
				break;

			case 'forbidden':
				$msg = __( 'Your domain has been forbidden from using our services due to a previous policy violation.', 'litespeed-cache' );
				break;

			case 'err_dns_active':
				$msg = __(
					'You cannot remove this DNS zone, because it is still in use. Please update the domain\'s nameservers, then try to delete this zone again, otherwise your site will become inaccessible.',
					'litespeed-cache'
				);
				break;

			default:
				$msg = __( 'Unknown error', 'litespeed-cache' ) . ': ' . $code;
				break;
		}

		if ( null !== $args ) {
			$msg = is_array( $args ) ? vsprintf( $msg, $args ) : sprintf( $msg, $args );
		}

		if ( isset( self::$CODE_SET[ $code ] ) ) {
			$msg = 'ERROR ' . self::$CODE_SET[ $code ] . ': ' . $msg;
		}

		return $msg;
	}
}
