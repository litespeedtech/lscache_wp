<?php

namespace LiteSpeed;

defined('WPINC') || exit();

// Modal data
$_title = __('Deactivate LiteSpeed Cache', 'litespeed');
$_id = 'litespeed-modal-deactivate';

$reasons = array(
	array(
		'value' => 'Temporary',
		'text' => __('The deactivation is temporary', 'litespeed-cache'),
		'id' => 'temp',
		'selected' => true,
	),
	array(
		'value' => 'Performance worse',
		'text' => __('Site performance is worse', 'litespeed-cache'),
		'id' => 'performance',
	),
	array(
		'value' => 'Plugin complicated',
		'text' => __('Plugin is too complicated', 'litespeed-cache'),
		'id' => 'complicated',
	),
	array(
		'value' => 'Other',
		'text' => __('Other', 'litespeed-cache'),
		'id' => 'other',
	),
);
?>
<script>
    window.lscId = '<?php echo home_url(); ?>';
</script>
<div style="display: none">
    <div id="litespeed-deactivation" class="iziModal">
        <?php require LSCWP_DIR . 'tpl/inc/modal.header.php'; ?>
        <form id="litespeed-deactivation-form" method="post">
            <p><?php _e('Why do you deactivate the plugin?', 'litespeed-cache'); ?></p>
            <div class="deactivate-reason-wrapper">
                <?php foreach ($reasons as $reason) {
                	echo '<label for="litespeed-deactivate-reason-' .
                		$reason['id'] .
                		'">
                        <input type="radio" 
                            id="litespeed-deactivate-reason-' .
                		$reason['id'] .
                		'" 
                            value="' .
                		$reason['value'] .
                		'" 
                            ' .
                		(isset($reason['selected']) && $reason['selected'] ? ' checked="checked"' : '') .
                		'
                            name="litespeed-reason" 
                        />
                        ' .
                		$reason['text'] .
                		'
                    </label>';
                } ?>
            </div>
            <div class="deactivate-clear-settings-wrapper">
                <label for="litespeed-deactivate-clear">
                    <input
                        type="checkbox"
                        id="litespeed-deactivate-clear"
                        name="lsc-clear"
                        value="true" />
                    <?php _e('Delete settings and data created by plugin?', 'litespeed-cache'); ?>
                </label>
                <?php if (is_multisite() && is_network_admin()) { ?>
                    <label for="litespeed-deactivate-network">
                        <input
                            type="checkbox"
                            id="litespeed-deactivate-network"
                            name="lsc-clear-network"
                            value="true" />
                        <?php _e('Delete settings from all other network sites?', 'litespeed-cache'); ?>
                    </label>
                <?php } ?>
                <i style="font-size: 0.9em;">
                      <?php echo sprintf(
                      	__('If you have Image Optimization used, you need to destroy all optm first, go to this %spage%s'),
                      	'<a href="admin.php?page=litespeed-img_optm#litespeed-imageopt-destroy" target="_blank">',
                      	'</a>'
                      ); ?>
                </i>
            </div>
            <div class="deactivate-actions">
                <input
                    type="button"
                    id="litespeed-deactivation-form-cancel"
                    class="button litespeed-btn-warning"
                    value="<?php _e('Cancel', 'litespeed-cache'); ?>"
                    title="<?php _e('Close popup', 'litespeed-cache'); ?>" />
                <input
                    type="submit"
                    id="litespeed-deactivation-form-submit"
                    class="button button-primary"
                    value="<?php _e('Deactivate', 'litespeed-cache'); ?>"
                    title="<?php _e('Deactivate plugin', 'litespeed-cache'); ?>" />
                <br />
            </div>
        </form>
        <?php require LSCWP_DIR . 'tpl/inc/modal.footer.php'; ?>
    </div>
</div>