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
        var tabs = jQuery("#lsc-tabs");
        tabs.tabs();
        tabs.removeClass('ui-widget');
    });

    jQuery(document).ready( function() {
        jQuery(".litespeedcache-postbox-button").on('click', function() {
            var pbDiv = jQuery(this).parent().get(0);
            jQuery(pbDiv).toggleClass('closed');
            jQuery(this).attr('aria-expanded',
                jQuery(this).attr('aria-expanded') === 'true' ? 'false' : 'true');
        });
    });

    jQuery(document).ready( function() {
        jQuery('#litespeedcache-purgeall').click( function() {
            if (confirm(jQuery('#litespeedcache-purgeall-confirm').val())) {
                jQuery(this).submit();
            }
            else {
                return false;
            }
        });
    });

    jQuery(document).ready( function() {
        jQuery('#litespeedcache-clearcache').click( function() {
            if (confirm(jQuery('#litespeedcache-clearcache-confirm').val())) {
                jQuery(this).submit();
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

