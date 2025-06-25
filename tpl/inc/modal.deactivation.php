<?php
/**
 * LiteSpeed Cache Deactivation Modal
 *
 * Renders the deactivation modal interface for LiteSpeed Cache, allowing users to send reason of deactivation.
 *
 * @package LiteSpeed
 * @since 1.0.0
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
                <p><?php esc_attr_e('Why do you deactivate the plugin?', 'litespeed-cache'); ?></p>
                <div class="deactivate-reason-wrapper">
                    <?php 
                        foreach ($reasons as $reason) {
                            echo '<label for="litespeed-deactivate-reason-' .
                                esc_html__( $reason['id'] ) .
                                '">
                                <input type="radio" 
                                    id="litespeed-deactivate-reason-' .
                                esc_html__( $reason['id'] ) .
                                '" 
                                    value="' .
                                esc_html__( $reason['value'] ) .
                                '" 
                                    ' .
                                (isset($reason['selected']) && $reason['selected'] ? ' checked="checked"' : '') .
                                '
                                    name="litespeed-reason" 
                                />
                                ' .
                                esc_html__( $reason['text'] ) .
                                '
                            </label>';
                        }
                    ?>
                </div>
                <div class="deactivate-clear-settings-wrapper">
                    <label for="litespeed-deactivate-clear">
                        <input
                            type="checkbox"
                            id="litespeed-deactivate-clear"
                            name="lsc-clear"
                            value="1" />
                        <?php
                            esc_attr_e('On Uninstall, delete settings created by plugin?', 'litespeed-cache');
                        ?>
                    </label>
                    <?php 
                        if (is_multisite()) {
                    ?>
                        <i style="font-size: 0.9em;">
                            <?php 
                                esc_html__('Deleting plugin settings, will delete all subsites settings too.', 'litespeed-cache');
                            ?>
                        </i>
                    <?php 
                        }
                    ?>
                    <i style="font-size: 0.9em;">
                        <?php 
                            printf(
                                esc_html__('If you have Image Optimization used, you need to destroy all optm first, go to this %spage%s', 'litespeed-cache'),
                                '<a href="admin.php?page=litespeed-img_optm#litespeed-imageopt-destroy" target="_blank">',
                                '</a>'
                            );
                        ?>
                    </i>
                </div>
                <div class="deactivate-actions">
                    <input
                        type="button"
                        id="litespeed-deactivation-form-cancel"
                        class="button litespeed-btn-warning"
                        value="<?php esc_attr_e('Cancel', 'litespeed-cache'); ?>"
                        title="<?php esc_attr_e('Close popup', 'litespeed-cache'); ?>" />
                    <input
                        type="submit"
                        id="litespeed-deactivation-form-submit"
                        class="button button-primary"
                        value="<?php esc_attr_e('Deactivate', 'litespeed-cache'); ?>"
                        title="<?php esc_attr_e('Deactivate plugin', 'litespeed-cache'); ?>" />
                    <br />
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
                    let deleteSettings = $(container).find('#litespeed-deactivate-clear').is(':checked');

                    // Save selected data
                    var data = {
                        id: lscId,
                        siteLink: window.location.hostname,
                        reason: $(container).find('[name=litespeed-reason]:checked').val(),
                        deleteSettings: deleteSettings,
                    };

                    $.ajax({
                        url: 'https://wpapi.quic.cloud/survey',
                        dataType: 'json',
                        method: 'POST',
                        cache: false,
                        data: data,
                        beforeSend: function (xhr) {
                            //xhr.setRequestHeader('X-WP-Nonce', litespeed_data.nonce);
                        },
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
