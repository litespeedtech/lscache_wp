<?php
// phpcs:ignoreFile

/**
 * The class to optimize image.
 *
 * @since       2.0
 * @package     LiteSpeed
 */

namespace LiteSpeed;

use WpOrg\Requests\Autoload;
use WpOrg\Requests\Requests;

defined('WPINC') || exit();

class Img_Optm extends Base {
	use Img_Optm_Send;
	use Img_Optm_Pull;
	use Img_Optm_Manage;

	const LOG_TAG = '🗜️';

	const CLOUD_ACTION_NEW_REQ         = 'new_req';
	const CLOUD_ACTION_TAKEN           = 'taken';
	const CLOUD_ACTION_REQUEST_DESTROY = 'imgoptm_destroy';
	const CLOUD_ACTION_CLEAN           = 'clean';

	const TYPE_NEW_REQ           = 'new_req';
	const TYPE_RESCAN            = 'rescan';
	const TYPE_DESTROY           = 'destroy';
	const TYPE_RESET_COUNTER     = 'reset_counter';
	const TYPE_CLEAN             = 'clean';
	const TYPE_PULL              = 'pull';
	const TYPE_BATCH_SWITCH_ORI  = 'batch_switch_ori';
	const TYPE_BATCH_SWITCH_OPTM = 'batch_switch_optm';
	const TYPE_CALC_BKUP         = 'calc_bkup';
	const TYPE_RESET_ROW         = 'reset_row';
	const TYPE_RM_BKUP           = 'rm_bkup';

	const STATUS_NEW        = 0; // 'new';
	const STATUS_RAW        = 1; // 'raw';
	const STATUS_REQUESTED  = 3; // 'requested';
	const STATUS_NOTIFIED   = 6; // 'notified';
	const STATUS_DUPLICATED = 8; // 'duplicated';
	const STATUS_PULLED     = 9; // 'pulled';
	const STATUS_FAILED     = -1; // 'failed';
	const STATUS_MISS       = -3; // 'miss';
	const STATUS_ERR_FETCH  = -5; // 'err_fetch';
	const STATUS_ERR_404    = -6; // 'err_404';
	const STATUS_ERR_OPTM   = -7; // 'err_optm';
	const STATUS_XMETA      = -8; // 'xmeta';
	const STATUS_ERR        = -9; // 'err';
	const DB_SIZE           = 'litespeed-optimize-size';
	const DB_SET            = 'litespeed-optimize-set';

	const DB_NEED_PULL = 'need_pull';

	private $wp_upload_dir;
	private $tmp_pid;
	private $tmp_type;
	private $tmp_path;
	private $_img_in_queue     = [];
	private $_existed_src_list = [];
	private $_pids_set         = [];
	private $_thumbnail_set    = '';
	private $_table_img_optm;
	private $_table_img_optming;
	private $_cron_ran = false;
	private $_sizes_skipped     = [];

	private $__media;
	private $__data;
	protected $_summary;
	private $_format = '';

	/**
	 * Init
	 *
	 * @since  2.0
	 */
	public function __construct() {
		Debug2::debug2('[ImgOptm] init');

		$this->wp_upload_dir      = wp_upload_dir();
		$this->__media            = $this->cls('Media');
		$this->__data             = $this->cls('Data');
		$this->_table_img_optm    = $this->__data->tb('img_optm');
		$this->_table_img_optming = $this->__data->tb('img_optming');

		$this->_summary = self::get_summary();
		if (empty($this->_summary['next_post_id'])) {
			$this->_summary['next_post_id'] = 0;
		}
		if ($this->conf(Base::O_IMG_OPTM_WEBP)) {
			$this->_format = 'webp';
			if ($this->conf(Base::O_IMG_OPTM_WEBP) == 2) {
				$this->_format = 'avif';
			}
		}

		// Allow users to ignore custom sizes.
		$this->_sizes_skipped = apply_filters( 'litespeed_imgoptm_sizes_skipped', $this->conf( Base::O_IMG_OPTM_SIZES_SKIPPED ) );
	}

	/**
	 * Handle all request actions from main cls
	 *
	 * @since  2.0
	 * @access public
	 */
	public function handler() {
		$type = Router::verify_type();

		switch ($type) {
			case self::TYPE_RESET_ROW:
            $this->reset_row(!empty($_GET['id']) ? $_GET['id'] : false);
				break;

			case self::TYPE_CALC_BKUP:
            $this->_calc_bkup();
				break;

			case self::TYPE_RM_BKUP:
            $this->rm_bkup();
				break;

			case self::TYPE_NEW_REQ:
            $this->new_req();
				break;

			case self::TYPE_RESCAN:
            $this->_rescan();
				break;

			case self::TYPE_RESET_COUNTER:
            $this->_reset_counter();
				break;

			case self::TYPE_DESTROY:
            $this->_destroy();
				break;

			case self::TYPE_CLEAN:
            $this->clean();
				break;

			case self::TYPE_PULL:
            self::start_async();
				break;

			case self::TYPE_BATCH_SWITCH_ORI:
			case self::TYPE_BATCH_SWITCH_OPTM:
            $this->batch_switch($type);
				break;

			case substr($type, 0, 4) === 'avif':
			case substr($type, 0, 4) === 'webp':
			case substr($type, 0, 4) === 'orig':
            $this->_switch_optm_file($type);
				break;

			default:
				break;
		}

		Admin::redirect();
	}
}
