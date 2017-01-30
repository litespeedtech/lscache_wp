(function ($) {
    'use strict';

    /**
     * All of the code for your public-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */

    jQuery(document).ready(function () {

        var tabs = $("#lsc-tabs").tabs({
            activate: function (event, ui) {
                event.preventDefault();

                //get the active tab index
                var active = $("#lsc-tabs").tabs("option", "active");

                //save it to hidden field
                $("input[name=active_tab]").val(active);
                var action = $("form").attr("action");
                if (action == 'options.php') {
                    var referer = $("input[name=_wp_http_referer]").val();
                    var new_url = referer + '&tab=' + active;
                    $("input[name=_wp_http_referer]").val(new_url);
                }
                else {
                    var new_url = action + '&tab=' + active;
                    $("form").attr("action", new_url);
                }
            }
        });

        //read the hidden field
        var activeTabIndex = $("input[name=active_tab]").val();

        //make active needed tab
        if (activeTabIndex !== undefined) {
            tabs.tabs("option", "active", activeTabIndex);
        }

        tabs.removeClass('ui-widget');

        $(".postbox .hndle").click(function () {
            $(this).parent().toggleClass("closed");
        });

        $('.litespeed-cache-jquery-button').html($("#litespeed-cache-jquery-button-expand-val").val());
        $(".litespeed-cache-jquery-button").click(function () {
            if ($('.litespeed-cache-jquery-button').html() == $("#litespeed-cache-jquery-button-expand-val").val()) {
                $('.litespeed-cache-jquery-button').html($("#litespeed-cache-jquery-button-collapse-val").val());
                $('div.postbox').removeClass('closed');
            } else {
                $('.litespeed-cache-jquery-button').html($("#litespeed-cache-jquery-button-expand-val").val());
                $('div.postbox').addClass('closed');
            }
        });

        var purgebyValue = purgebySelect($('#purgeby').val());
        $('.litespeed-cache-purgeby-text').html(purgebyValue);

        $('#purgeby').change(function () {
            purgebyValue = purgebySelect($(this).val());
            $('.litespeed-cache-purgeby-text').html(purgebyValue);
        });

        //WHM Notice
        $(document).on('click', '.lscwp-whm-notice .notice-dismiss', function () {
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'lscache_dismiss_whm',
                    LSCWP_CTRL: lscwp_data.lscwpctrl,
                    _wpnonce: lscwp_data.nonce
                }
            })
        })

        // Select All and Copy to Clipboard
        $(".litespeed-cache-select-all-button").click(function() {
            $('#litespeed-report').select();
            document.execCommand('copy');
            $('span.copy-select-all-span').css('display','inline-block');
            $('span.copy-select-all-span').fadeIn('slow').delay(1000).fadeOut('slow');
        });

        //Toggle postbox class Buttons
        $(".litespeedcache-postbox-button").on('click', function () {
            var pbDiv = $(this).parent().get(0);
            $(pbDiv).toggleClass('closed');
            $(this).attr('aria-expanded',
                $(this).attr('aria-expanded') === 'true' ? 'false' : 'true');
        });


        //Purge All Button
        $('#litespeedcache-purgeall').click(function () {
            if (confirm($('#litespeedcache-purgeall-confirm').val())) {
                $(this).submit();
            }
            else {
                return false;
            }
        });

        //Confirm Clear Cache
        $('#litespeedcache-clearcache').click(function () {
            if (confirm(jQuery('#litespeedcache-clearcache-confirm').val())) {
                $(this).submit();
            }
            else {
                return false;
            }
        });
    });
})(jQuery);

function lscwpCheckboxConfirm(the_checkbox, list_id) {
    var id = the_checkbox.id;
    var default_id = id.concat("_default");
    var warning_id = id.concat("_warning");
    var the_list = document.getElementById(list_id);
    if (the_checkbox.checked) {
        the_list.value = document.getElementById(default_id).value;
        the_list.readOnly = false;
        return;
    }
    if (!confirm(document.getElementById(warning_id).value)) {
        the_checkbox.checked = !the_checkbox.checked;
        return;
    }
    the_list.value = '';
    the_list.readOnly = true;
}

function purgebySelect(value) {
    var category = jQuery('#purgeby-category').val();
    var postid = jQuery('#purgeby-postid').val();
    var tag = jQuery('#purgeby-tag').val();
    var url = jQuery('#purgeby-url').val();

    if (value == 0) {
        var purgebyValue = category;
    }
    else if (value == 1) {
        var purgebyValue = postid;
    }
    else if (value == 2) {
        var purgebyValue = tag;
    }
    else if (value == 3) {
        var purgebyValue = url;
    }
    return purgebyValue;
}
