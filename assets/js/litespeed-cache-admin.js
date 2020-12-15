var _litespeed_meta ;
var _litespeed_shell_interval = 3 ;// seconds
var _litespeed_shell_interval_range = [3, 60] ;
var _litespeed_shell_handle ;
var _litespeed_shell_display_handle ;
var _litespeed_crawler_url ;
var _litespeed_dots ;

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
			litespeed_display_tab(litespeed_tab_current) ;
			// tab switch
			$('[data-litespeed-tab]').click(function(event) {
				litespeed_display_tab($(this).data('litespeed-tab')) ;
				document.cookie = 'litespeed_tab='+$(this).data('litespeed-tab') ;
				$(this).blur() ;
			}) ;
		}

		// Manage page -> purge by
		$('[name=purgeby]').change(function(event) {
			$('[data-purgeby]').hide() ;
			$('[data-purgeby='+this.value+']').show() ;
		}) ;

		/*************** crawler ******************/
		$('#litespeed-crawl-url-btn').click(function () {
			if( ! $(this).data('url') ){
				return false ;
			}
			$('.litespeed-shell').removeClass( 'litespeed-hide' ) ;
			_litespeed_dots = window.setInterval(_litespeed_loading_dots, 300) ;
			_litespeed_crawler_url = $(this).data('url') ;
			litespeed_fetch_meta() ;
			$(this).hide() ;
		}) ;

		$('#litespeed_manual_trigger').click(function(event) {
			$('#litespeed-loading-dot').before('<li>Manually Started</li>') ;
			_litespeed_shell_interval = _litespeed_shell_interval_range[0] ;
			litespeed_fetch_meta() ;
		}) ;

		/******************** Clear whm msg ********************/
		$(document).on('click', '.lscwp-whm-notice .notice-dismiss', function () {
			$.get(litespeed_data.ajax_url_dismiss_whm) ;
		});
		/******************** Clear rule conflict msg ********************/
		$(document).on('click', '.lscwp-notice-ruleconflict .notice-dismiss', function () {
			$.get(litespeed_data.ajax_url_dismiss_ruleconflict) ;
		});

		/** Accesskey **/
		$( '[litespeed-accesskey]' ).map( function() {
			var thiskey = $( this ).attr( 'litespeed-accesskey' ) ;
			$( this ).attr( 'title', 'Shortcut : ' + thiskey.toLocaleUpperCase() ) ;
			var that = this ;
			$( document ).on( 'keydown', function( e ) {
				if( $(":input:focus").length > 0 ) return ;
				if( event.metaKey ) return ;
				if( event.ctrlKey ) return ;
				if( event.altKey ) return ;
				if( event.shiftKey ) return ;
				if( litespeed_keycode( thiskey.charCodeAt( 0 ) ) ) $( that )[ 0 ].click() ;
			});
		});

		/** Lets copy one more submit button **/
		if ( $( 'input[name="LSCWP_CTRL"]' ).length > 0 ) {
			var btn = $( 'input.litespeed-duplicate-float' ) ;
			btn.clone().addClass( 'litespeed-float-submit' ).removeAttr( 'id' ).insertAfter( btn ) ;
		}
		if ( $( 'input[id="LSCWP_NONCE"]' ).length > 0 ) {
			$( 'input[id="LSCWP_NONCE"]' ).removeAttr( 'id' );
		}

		/** Promo banner **/
		$( '#litespeed-promo-done' ).click( function( event ) {
			$( '.litespeed-banner-promo-full' ).slideUp() ;
			$.get( litespeed_data.ajax_url_promo + '&done=1' ) ;
		} ) ;
		$( '#litespeed-promo-later' ).click( function( event ) {
			$( '.litespeed-banner-promo-full' ).slideUp() ;
			$.get( litespeed_data.ajax_url_promo ) ;
		} ) ;

		/**
		 * Human readable time conversation
		 * @since  3.0
		 */
		if ( $( '[data-litespeed-readable]' ).length > 0 ) {
			$( '[data-litespeed-readable]' ).each(function(index, el) {
				var that = this ;
				var $input = $( this ).siblings( 'input[type="text"]' ) ;

				var txt = litespeed_readable_time( $input.val() ) ;
				$( that ).html( txt ? '= ' + txt : '' ) ;

				$input.keyup(function(event) {
					var txt = litespeed_readable_time( $( this ).val() ) ;
					$( that ).html( txt ? '= ' + txt : '' ) ;
				});
			});
		}

		/**
		 * Get server IP
		 * @since  3.0
		 */
		$( '#litespeed_get_ip' ).click( function( e ) {
			$.ajax( {
				url: litespeed_data.ajax_url_getIP,
				dataType: 'json',
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', litespeed_data.nonce ) ;
				},
				success: function( data ) {
					$( '#litespeed_server_ip' ).html( data ) ;
				}
			} ) ;
		} ) ;

		/**
		 * Click only once
		 */
		if ( $( '[data-litespeed-onlyonce]' ).length > 0 ) {
			$( '[data-litespeed-onlyonce]' ).click( function ( e ) {
				if ( $( this ).hasClass( 'disabled' ) ) {
					e.preventDefault();
				}
				$( this ).addClass( 'disabled' );
			} );
		}
	}) ;
})(jQuery) ;

/**
 * Plural handler
 */
function litespeed_plural( $num, $txt )
{
	if ( $num > 1 ) return $num + ' ' + $txt + 's' ;

	return $num + ' ' + $txt ;
}

/**
 * Convert seconds to readable time
 */
function litespeed_readable_time( seconds )
{
	if ( seconds < 60 ) {
		return '' ;
	}

	var second = Math.floor( seconds % 60 ) ;
	var minute = Math.floor( ( seconds / 60 ) % 60 ) ;
	var hour = Math.floor( ( seconds / 3600 ) % 24 ) ;
	var day = Math.floor( ( seconds / 3600 / 24 ) % 7 ) ;
	var week = Math.floor( seconds / 3600 / 24 / 7 ) ;

	var str = '' ;
	if ( week ) str += ' ' + litespeed_plural( week, 'week' ) ;
	if ( day ) str += ' ' + litespeed_plural( day, 'day' ) ;
	if ( hour ) str += ' ' + litespeed_plural( hour, 'hour' ) ;
	if ( minute ) str += ' ' + litespeed_plural( minute, 'minute' ) ;
	if ( second ) str += ' ' + litespeed_plural( second, 'second' ) ;

	return str ;
}

/**
 * Trigger a click event on an element
 * @since  1.8
 */
function litespeed_trigger_click( selector )
{
	jQuery( selector ).trigger( 'click' ) ;
}

function litespeed_keycode( num ) {
	var num = num || 13 ;
	var code = window.event ? event.keyCode : event.which ;
	if( num == code ) return true ;
	return false ;
}

function litespeed_display_tab(tab) {
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
function litespeed_append_param(uri, key, val) {
	var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i") ;
	var separator = uri.indexOf('?') !== -1 ? "&" : "?" ;
	if (uri.match(re)) {
		return uri.replace(re, '$1' + key + "=" + val + '$2') ;
	}
	else {
		return uri + separator + key + "=" + val ;
	}
}

function litespeed_pulse() {
	jQuery('#litespeed-shell-icon').animate({
		width: 27, height: 34,
		opacity: 1
	}, 700, function() {
		jQuery('#litespeed-shell-icon').animate({
			width: 23, height: 29,
			opacity: 0.5
		}, 700) ;
	}) ;
}

function litespeed_fetch_meta() {
	window.clearTimeout(_litespeed_shell_handle) ;
	jQuery('#litespeed-loading-dot').text('') ;
	jQuery.ajaxSetup({ cache: false }) ;
	jQuery.getJSON(_litespeed_crawler_url, function( meta ) {
		litespeed_pulse() ;
		var changed = false ;
		if ( meta && 'list_size' in meta ) {
			new_meta = meta.list_size + ' ' + meta.file_time + ' ' + meta.curr_crawler + ' ' + meta.last_pos + ' ' + meta.last_count + ' ' + meta.last_start_time + ' ' + meta.is_running ;
			if ( new_meta != _litespeed_meta ) {
				_litespeed_meta = new_meta ;
				changed = true ;
				string = _litespeed_build_meta(meta);
				jQuery('#litespeed-loading-dot').before(string) ;
				// remove first log elements
				log_length = jQuery('.litespeed-shell-body li').length;
				if ( log_length > 50) {
					jQuery('.litespeed-shell-body li:lt(' + (log_length - 50) + ')').remove();
				}
				// scroll to end
				jQuery('.litespeed-shell-body').stop().animate({
					scrollTop: jQuery('.litespeed-shell-body')[0].scrollHeight
				}, 800) ;
			}

			// dynamic adjust the interval length
			_litespeed_adjust_interval(changed) ;
		}
		// display interval counting
		litespeed_display_interval_reset() ;
		_litespeed_shell_handle = window.setTimeout(_litespeed_dynamic_timeout, _litespeed_shell_interval*1000) ;
	}) ;
}

/**
 * Dynamic adjust interval
 */
function _litespeed_adjust_interval(changed) {
	if ( changed ) {
		_litespeed_shell_interval -= Math.ceil(_litespeed_shell_interval/2) ;
	}
	else{
		_litespeed_shell_interval ++ ;
	}

	if(_litespeed_shell_interval < _litespeed_shell_interval_range[0]) {
		_litespeed_shell_interval = _litespeed_shell_interval_range[0] ;
	}
	if(_litespeed_shell_interval > _litespeed_shell_interval_range[1]) {
		_litespeed_shell_interval = _litespeed_shell_interval_range[1] ;
	}
}

function _litespeed_build_meta(meta) {
	var string = '<li>' + litespeed_date(meta.last_update_time) +
					'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Size: ' + meta.list_size +
					'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Crawler: #' + (meta.curr_crawler*1+1) +
					'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Position: ' + (meta.last_pos*1+1) +
					'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Threads: ' + meta.last_count +
					'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Status: '
				 ;
	if ( meta.is_running ) {
		string += 'crawling, ' + meta.last_status;
	}
	else{
		string += meta.end_reason ? meta.end_reason : '-' ;
	}
	string += '</li>' ;
	return string;
}

function _litespeed_dynamic_timeout() {
	litespeed_fetch_meta() ;
}

function litespeed_display_interval_reset() {
	window.clearInterval(_litespeed_shell_display_handle) ;
	jQuery('.litespeed-shell-header-bar').data('num', _litespeed_shell_interval) ;
	_litespeed_shell_display_handle = window.setInterval(_litespeed_display_interval, 1000) ;

	jQuery('.litespeed-shell-header-bar').stop().animate({width: '100%'}, 500, function(){
		jQuery('.litespeed-shell-header-bar').css('width', '0%') ;
	}) ;
}

function _litespeed_display_interval() {
	var num = jQuery('.litespeed-shell-header-bar').data('num') ;
	jQuery('.litespeed-shell-header-bar').stop().animate({width: litespeed_get_percent(num, _litespeed_shell_interval) + '%'}, 1000) ;
	if(num > 0) num-- ;
	if(num < 0) num = 0 ;
	jQuery('.litespeed-shell-header-bar').data('num', num) ;
}

function litespeed_get_percent(num1, num2){
	num1 = num1 * 1;
	num2 = num2 * 1;
	num = (num2 - num1) / num2;
	return num * 100;
}

function _litespeed_loading_dots() {
	jQuery('#litespeed-loading-dot').append('.') ;
}

function litespeed_date(timestamp) {
	var a = new Date(timestamp * 1000) ;
	var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] ;
	var year = a.getFullYear() ;
	var month = months[a.getMonth()] ;
	var date = litespeed_add_zero(a.getDate()) ;
	var hour = litespeed_add_zero(a.getHours()) ;
	var min = litespeed_add_zero(a.getMinutes()) ;
	var sec = litespeed_add_zero(a.getSeconds()) ;
	var time = date + ' ' + month + ' ' + year + ' ' + hour + ':' + min + ':' + sec  ;
	return time ;
}

function litespeed_add_zero(i) {
	if (i < 10) {
		i = "0" + i;
	}
	return i;
}
