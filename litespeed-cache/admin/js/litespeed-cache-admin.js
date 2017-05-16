var _litespeedMeta;
var _litespeedInterval = 3;// seconds
var _litespeedIntervalRange = [3, 60];
var _litespeedIntervalHandle;
var _litespeedIntervalDisplayHandle;

(function ($) {
	'use strict' ;

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
	 * }) ;
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * }) ;
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	jQuery(document).ready(function () {
		/************** Common LiteSpeed JS **************/
		// Arrow transform 
		$('.litespeed-down, .litespeed-up').click(function(event) {
			$(this).toggleClass('litespeed-up litespeed-down') ;
		}) ;

		// Link confirm
		$('[data-litespeed-cfm]').click(function(event) {
			if(confirm($.trim($(this).data('litespeed-cfm')).replace(/\\n/g,"\n"))) {
				return true ;
			}
			event.preventDefault() ;
			event.stopImmediatePropagation() ;
			return false ;
		}) ;

		/************** LSWCP JS ****************/
		// FAQ show and hide
		$('.litespeed-answer').hide() ;
		$('.litespeed-question').click(function(event) {
			$(this).next('.litespeed-answer').slideToggle('fast') ;
		}) ;
		$('[data-litespeed-expend-all]').click(function(event) {
			var range = $(this).data('litespeed-expend-all') ;
			if($(this).html() == '+'){
				$(this).html('-') ;
				$('[data-litespeed-layout="'+range+'"] .litespeed-answer').slideDown('fast') ;
				$('[data-litespeed-layout="'+range+'"] .litespeed-question').addClass('litespeed-up').removeClass('litespeed-down') ;
			}else{
				$(this).html('+') ;
				$('[data-litespeed-layout="'+range+'"] .litespeed-answer').slideUp('fast') ;
				$('[data-litespeed-layout="'+range+'"] .litespeed-question').addClass('litespeed-down').removeClass('litespeed-up') ;
			}
		}) ;

		// page tab switch functionality
		if($('[data-litespeed-tab]').length > 0){
			// display default tab
			var litespeed_tab_current = document.cookie.replace(/(?:(?:^|.*;\s*)litespeed_tab\s*\=\s*([^;]*).*$)|^.*$/, "$1") ;
			if(window.location.hash.substr(1)) {
				litespeed_tab_current = window.location.hash.substr(1) ;
			}
			if(!litespeed_tab_current || !$('[data-litespeed-tab="'+litespeed_tab_current+'"]').length) {
				litespeed_tab_current = $('[data-litespeed-tab]').first().data('litespeed-tab') ;
			}
			litespeedDisplayTab(litespeed_tab_current) ;
			// tab switch
			$('[data-litespeed-tab]').click(function(event) {
				litespeedDisplayTab($(this).data('litespeed-tab')) ;
				document.cookie = 'litespeed_tab='+$(this).data('litespeed-tab') ;
			}) ;
		}

		// Manage page -> purge by
		$('[name=purgeby]').change(function(event) {
			$('[data-purgeby]').hide() ;
			$('[data-purgeby='+this.value+']').show() ;
		}) ;

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
		}) ;

		// Select All and Copy to Clipboard
		$("#litespeed_cache_report_copy").click(function() {
			$('#litespeed-report').select() ;
			document.execCommand('copy') ;
			$('#copy_select_all_span').fadeIn('slow').delay(1000).fadeOut('slow') ;
		}) ;

		// Settings->General->Enable mobile view
		$('#conf_mobileview_enabled_1').click(function() {
			if($(this).is(':checked')){
				if(!$('#litespeed-mobileview-rules').val()){
					$('#litespeed-mobileview-rules').val($('#litespeed-mobileview-rules-default').val()) ;
				}
				$('#litespeed-mobileview-rules').prop('readonly', false) ;
			}
		}) ;
		$('#conf_mobileview_enabled_0').click(function() {
			if($(this).is(':checked')){
				// $('#litespeed-mobileview-rules').val('') ;
				$('#litespeed-mobileview-rules').prop('readonly', true) ;
			}
		}) ;

		/*************** crawler ******************/
		$('#litespeedBtnCrawlUrl').click(function () {
			if( ! $(this).data('url') ){
				return false ;
			}
			$(this).attr('disabled', true) ;
			$('.shell-wrap').css('display','block') ;
			$.ajaxSetup({ cache: false }) ;
			litespeedGetMeta($(this).data('url')) ;
			_litespeedIntervalHandle = window.setTimeout('litespeedDynamicTimeout()', _litespeedInterval*1000) ;
			litespeedPulse() ;
		}) ;
	}) ;
})(jQuery) ;



function litespeedDisplayTab(tab) {
	// setting page -> display submit button
	if ( jQuery('#litespeed-submit').length > 0 ){
		jQuery('#litespeed-submit').toggle(tab != 'compatibilities') ;
	}
	jQuery('[data-litespeed-tab]').removeClass('nav-tab-active') ;
	jQuery('[data-litespeed-tab="'+tab+'"]').addClass('nav-tab-active') ;
	jQuery('[data-litespeed-layout]').hide() ;
	jQuery('[data-litespeed-layout="'+tab+'"]').show() ;
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

// Append params to uri
function litespeedAppendParam(uri, key, val) {
	var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i") ;
	var separator = uri.indexOf('?') !== -1 ? "&" : "?" ;
	if (uri.match(re)) {
		return uri.replace(re, '$1' + key + "=" + val + '$2') ;
	}
	else {
		return uri + separator + key + "=" + val ;
	}
}

function litespeedPulse() {
	jQuery('#litespeedIconPulse').animate({
		width: 27, height: 34, 
		opacity: 1
	}, 700, function() {
		jQuery('#litespeedIconPulse').animate({
			width: 23, height: 29, 
			opacity: 0.5
		}, 700) ;
	}) ; 
}

function litespeedGetMeta(url) {
	jQuery.getJSON(url, function( meta ) {
		litespeedPulse() ;
		var changed = false ;
		if ( meta != _litespeedMeta ) {
			_litespeedMeta = meta ;
			changed = true ;
			var string = '<li>' +
							'List size: ' + meta.listSize +
							',		Meta file last modified at : ' + meta.fileTime +
							',		Last crawled line: ' + meta.lastPos +
							',		Last crawled threads: ' + meta.lastCount +
							',		Last started at: ' + meta.listSize +
							',		Last ended reason: ' + meta.endReason +
							',		Is crawling: ' + ( meta.isRunning == 1 ? 'Yes' : 'No' ) +
						'</li>' ;
			jQuery('.litespeed-shell-body').html(string) ;
		}

		// dynamic adjust the interval length
		if ( changed ) {
			_litespeedInterval -= Math.ceil(_litespeedInterval/2) ;
		}
		else{
			_litespeedInterval ++  ;
		}
		if(_litespeedInterval < _litespeedIntervalRange[0]) {
			_litespeedInterval = _litespeedIntervalRange[0] ;
		}
		if(_litespeedInterval > _litespeedIntervalRange[1]) {
			_litespeedInterval = _litespeedIntervalRange[1] ;
		}
		// display interval counting
		litespeedResetIntervalDisplay() ;
		_litespeedIntervalHandle = window.setTimeout('litespeedDynamicTimeout()', _litespeedInterval*1000) ;
	}) ;
}

function litespeedDynamicTimeout() {
	window.clearTimeout(_litespeedIntervalHandle) ;
	getWPcount() ;
}

function litespeedResetIntervalDisplay() {
	window.clearInterval(_litespeedIntervalDisplayHandle) ;
	jQuery('#litespeedInterval').text(_litespeedInterval) ;
	_litespeedIntervalDisplayHandle = window.setInterval('litespeedDisplayInterval()', 1000) ;
}

function litespeedDisplayInterval() {
	var num = jQuery('#litespeedInterval').text() ;
	if(num > 0) num-- ;
	if(num < 0) num = '.' ;
	jQuery('#litespeedInterval').text(num) ;
}

