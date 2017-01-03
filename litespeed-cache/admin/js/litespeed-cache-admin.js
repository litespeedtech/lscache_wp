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
        activate: function(event, ui){
            event.preventDefault();

            //get the active tab index
            var active = $("#lsc-tabs").tabs("option", "active");

            //save it to hidden field
            $("input[name=active_tab]").val(active);
            var referer = $("input[name=_wp_http_referer]").val();
            var new_url = referer + '&tab='+ active;
            $("input[name=_wp_http_referer]").val(new_url);
        }
        });

        //read the hidden field
        var activeTabIndex = $("input[name=active_tab]").val();

        //make active needed tab
        if( activeTabIndex !== undefined ) {
            tabs.tabs("option", "active", activeTabIndex);
        }

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

function lscwpEsiEnabled(the_checkbox, esi_ids) {
    var rdonly = the_checkbox.checked ? false : true;
    var len = esi_ids.length;
    for (var i = 0; i < len; i++) {
        var node_id = 'saved_' + esi_ids[i].getAttribute('id');
        var node_val = esi_ids[i].getAttribute('value');
        var prev = document.getElementById(node_id);
        if (rdonly === false) {
            esi_ids[i].removeAttribute('disabled');
            if (prev) {
                esi_ids[i].removeChild(prev);
            }
            continue;
        }
        esi_ids[i].setAttribute('disabled', true);
        if (prev !== null) {
            if (esi_ids[i].checked) {
                prev.setAttribute("value", node_val);
            }
            else {
                esi_ids[i].removeChild(prev);
            }
            continue;
        }
        else if (esi_ids[i].checked === false) {
            continue;
        }
        var hid = document.createElement("INPUT");
        hid.setAttribute("type", "hidden");
        hid.setAttribute("name", esi_ids[i].getAttribute('name'));
        hid.setAttribute("value", node_val);
        hid.setAttribute("id", node_id);
        esi_ids[i].appendChild(hid);
    }
}

