<?php
/**
 * LiteSpeed Cache Deactivation Modal
 *
 * Renders the deactivation modal interface for LiteSpeed Cache, allowing users to send reason of deactivation.
 *
 * @package LiteSpeed
 * @since 7.3
 */

namespace LiteSpeed;

defined( 'WPINC' ) || exit;

// Modal data
$_title = esc_html__('Deactivate LiteSpeed Cache', 'litespeed');
$_id    = 'litespeed-modal-deactivate';

$reasons = array(
	array(
		'value' => 'Temporary',
		'text' => esc_html__('The deactivation is temporary', 'litespeed-cache'),
		'id' => 'temp',
		'selected' => true,
	),
	array(
		'value' => 'Performance worse',
		'text' => esc_html__('Site performance is worse', 'litespeed-cache'),
		'id' => 'performance',
	),
	array(
		'value' => 'Plugin complicated',
		'text' => esc_html__('Plugin is too complicated', 'litespeed-cache'),
		'id' => 'complicated',
	),
	array(
		'value' => 'Other',
		'text' => esc_html__('Other', 'litespeed-cache'),
		'id' => 'other',
	),
);
?>
<div style="display: none">
    <div id="litespeed-deactivation" class="iziModal">
        <div id="litespeed-modal-deactivate">
            <form id="litespeed-deactivation-form" method="post">
                <p><?php esc_attr_e('Why are you deactivating the plugin?', 'litespeed-cache'); ?></p>
                <div class="deactivate-reason-wrapper">
                    <?php foreach ($reasons as $reason) : ?>
                    <label for="litespeed-deactivate-reason-<?php esc_attr_e( $reason['id'] ); ?>">
                        <input type="radio" id="litespeed-deactivate-reason-<?php esc_attr_e( $reason['id'] ); ?>" value="<?php esc_attr_e( $reason['value'] ); ?>"
                            <?php isset($reason['selected']) && $reason['selected'] ? ' checked="checked"' : ''; ?> name="litespeed-reason" />
                        <?php esc_html_e( $reason['text'] ); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div class="deactivate-clear-settings-wrapper">
                    <i style="font-size: 0.9em;">
                        <?php
                            esc_html_e('On uninstall, all plugin settings will be deleted.', 'litespeed-cache');
                        ?>
                    </i>
                    <br />
                    <i style="font-size: 0.9em;">

                        <?php
                            printf(
                                esc_html__('If you have used Image Optimization, please %sDestroy All Optimization Data%s first. NOTE: this does not remove your optimized images.', 'litespeed-cache'),
                                '<a href="admin.php?page=litespeed-img_optm#litespeed-imageopt-destroy" target="_blank">',
                                '</a>'
                            );
                        ?>
                    </i>
                </div>
                <div class="deactivate-actions">
                    <input type="submit" id="litespeed-deactivation-form-submit" class="button button-primary" value="<?php esc_attr_e('Deactivate', 'litespeed-cache'); ?>" title="<?php esc_attr_e('Deactivate plugin', 'litespeed-cache'); ?>" />
                    <input type="button" id="litespeed-deactivation-form-cancel" class="button litespeed-btn-warning" value="<?php esc_attr_e('Cancel', 'litespeed-cache'); ?>" title="<?php esc_attr_e('Close popup', 'litespeed-cache'); ?>" />
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    (function ($) {
    'use strict';
        jQuery(document).ready(function () {
            var lscId = '<?php echo home_url(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>';
            var modalesc_attr_element = $('#litespeed-deactivation');
            var deactivateesc_attr_element = $('#deactivate-litespeed-cache');

            if (deactivateesc_attr_element.length > 0 && modalesc_attr_element.length > 0) {
                // Variables
                var modal_formElement = $('#litespeed-deactivation-form');

                deactivateesc_attr_element.on('click', function (e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    modal_formElement.attr('action', decodeURI($(this).attr('href')));
                    modalesc_attr_element.iziModal({
                        radius: '.5rem',
                        width: 550,
                        autoOpen: true,
                    });
                });

                $(document).on('submit', '#litespeed-deactivation-form', function (e) {
                    e.preventDefault();
                    $('#litespeed-deactivation-form-submit').attr('disabled', true);
                    var container = $('#litespeed-deactivation-form');

                    // Save selected data
                    var data = {
                        id: lscId,
                        siteLink: window.location.hostname,
                        reason: $(container).find('[name=litespeed-reason]:checked').val()
                    };

                    $.ajax({
                        url: 'https://wpapi.quic.cloud/survey',
                        dataType: 'json',
                        method: 'POST',
                        cache: false,
                        data: data,
                        success: function (data) {
                            console.log('QC data sent.');
                        },
                        error: function (xhr, error) {
                            console.log('Error sending data to QC.');
                        },
                    });

                    $('#litespeed-deactivation-form')[0].submit();
                });
                $(document).on('click', '#litespeed-deactivation-form-cancel', function (e) {
                    modalesc_attr_element.iziModal('close');
                });
            }
        });
    })(jQuery);
</script>
