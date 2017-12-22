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
		$('#litespeed-crawl-url-btn').click(function () {
			if( ! $(this).data('url') ){
				return false ;
			}
			$('.litespeed-shell').css('display','block') ;
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

		$('#litespeed_crawler_cron_enable').click(function(event) {
			var that = this ;
			$.getJSON( $(that).data('url'), function(json){
				$(that).prop('checked', json.enable) ;
			} ) ;
		}) ;

		$('#litespeed_custom_sitemap').keyup(function(event) {
			$('[data-litespeed-selfsitemap]').toggle(!$(this).val()) ;
		}) ;

		$('[data-litespeed-selfsitemap]').toggle(!$('#litespeed_custom_sitemap').val()) ;

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
		if ( $( '#litespeed_form_options' ).length > 0 ) {
			$( '#litespeed_form_options [type="submit"]' ).clone().addClass( 'litespeed-float-submit' ).prependTo( '#litespeed_form_options' ) ;
		}

		/** Promo banner **/
		$( '#litespeed-promo-done' ).click( function( event ) {
			$( '.litespeed-banner-promo' ).slideUp() ;
			$.get( litespeed_data.ajax_url_promo + '&done=1' ) ;
		} ) ;
		$( '#litespeed-promo-later' ).click( function( event ) {
			$( '.litespeed-banner-promo' ).slideUp() ;
			$.get( litespeed_data.ajax_url_promo + '&later=1' ) ;
		} ) ;

		/** CDN mapping **/
		$( '#litespeed-cdn-mapping-add' ).click(function(event) {
			$( '[data-litespeed-cdn-mapping]:last' ).clone().insertAfter( '[data-litespeed-cdn-mapping]:last' ) ;

			$('input[type=checkbox][data-toggle^=toggle]').bootstrapToggle() ;
		});

	}) ;
})(jQuery) ;

function litespeed_keycode( num ) {
	var num = num || 13 ;
	var code = window.event ? event.keyCode : event.which ;
	if( num == code ) return true ;
	return false ;
}

function litespeed_display_tab(tab) {
	// setting page -> display submit button
	if ( jQuery('#litespeed-submit').length > 0 ){
		jQuery('#litespeed-submit').toggle(tab != 'compatibilities') ;
	}
	jQuery('[data-litespeed-tab]').removeClass('litespeed-tab-active') ;
	jQuery('[data-litespeed-tab="'+tab+'"]').addClass('litespeed-tab-active') ;
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
			new_meta = meta.list_size + ' ' + meta.file_time + ' ' + meta.last_pos + ' ' + meta.last_count + ' ' + meta.last_start_time + ' ' + meta.is_running ;
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


/*! ========================================================================
 * Bootstrap Toggle: bootstrap-toggle.js v2.2.0
 * http://www.bootstraptoggle.com
 * ========================================================================
 * Copyright 2014 Min Hur, The New York Times Company
 * Licensed under MIT
 * ======================================================================== */
/*! ========================================================================
 * Bootstrap Toggle: bootstrap-toggle.js v2.2.0
 * http://www.bootstraptoggle.com
 * ========================================================================
 * Copyright 2014 Min Hur, The New York Times Company
 * Licensed under MIT
 * ======================================================================== */


 +function ($) {
 	'use strict';

	// TOGGLE PUBLIC CLASS DEFINITION
	// ==============================

	var Toggle = function (element, options) {
		this.$element  = $(element)
		this.options   = $.extend({}, this.defaults(), options)
		this.render()
	}

	Toggle.VERSION  = '2.2.0'

	Toggle.DEFAULTS = {
		on: 'On',
		off: 'Off',
		onstyle: 'success',
		offstyle: 'default',
		size: 'normal',
		style: '',
		width: null,
		height: null
	}

	Toggle.prototype.defaults = function() {
		return {
			on: this.$element.attr('data-on') || Toggle.DEFAULTS.on,
			off: this.$element.attr('data-off') || Toggle.DEFAULTS.off,
			onstyle: this.$element.attr('data-onstyle') || Toggle.DEFAULTS.onstyle,
			offstyle: this.$element.attr('data-offstyle') || Toggle.DEFAULTS.offstyle,
			size: this.$element.attr('data-size') || Toggle.DEFAULTS.size,
			style: this.$element.attr('data-style') || Toggle.DEFAULTS.style,
			width: this.$element.attr('data-width') || Toggle.DEFAULTS.width,
			height: this.$element.attr('data-height') || Toggle.DEFAULTS.height
		}
	}

	Toggle.prototype.render = function () {
		this._onstyle = 'litespeed-toggle-btn-' + this.options.onstyle
		this._offstyle = 'litespeed-toggle-btn-' + this.options.offstyle
		var size = this.options.size === 'large' ? 'litespeed-toggle-btn-lg'
			: this.options.size === 'small' ? 'litespeed-toggle-btn-sm'
			: this.options.size === 'mini' ? 'litespeed-toggle-btn-xs'
			: ''
		var $toggleOn = $('<label class="litespeed-toggle-btn">').html(this.options.on)
			.addClass(this._onstyle + ' ' + size)
		var $toggleOff = $('<label class="litespeed-toggle-btn">').html(this.options.off)
			.addClass(this._offstyle + ' ' + size + ' litespeed-toggle-active')
		var $toggleHandle = $('<span class="litespeed-toggle-handle litespeed-toggle-btn litespeed-toggle-btn-default">')
			.addClass(size)
		var $toggleGroup = $('<div class="litespeed-toggle-group">')
			.append($toggleOn, $toggleOff, $toggleHandle)
		var $toggle = $('<div class="litespeed-toggle litespeed-toggle-btn" data-toggle="toggle">')
			.addClass( this.$element.prop('checked') ? this._onstyle : this._offstyle+' off' )
			.addClass(size).addClass(this.options.style)

		this.$element.wrap($toggle)
		$.extend(this, {
			$toggle: this.$element.parent(),
			$toggleOn: $toggleOn,
			$toggleOff: $toggleOff,
			$toggleGroup: $toggleGroup
		})
		this.$toggle.append($toggleGroup)

		var width = this.options.width || Math.max($toggleOn.outerWidth(), $toggleOff.outerWidth())+($toggleHandle.outerWidth()/2)
		var height = this.options.height || Math.max($toggleOn.outerHeight(), $toggleOff.outerHeight())
		// console.log( $toggleOn.outerHeight(), $toggleOff.outerHeight() ) ;
		$toggleOn.addClass('litespeed-toggle-on')
		$toggleOff.addClass('litespeed-toggle-off')
		this.$toggle.css({ width: width, height: '20px' })
		if (this.options.height) {
			$toggleOn.css('line-height', $toggleOn.height() + 'px')
			$toggleOff.css('line-height', $toggleOff.height() + 'px')
		}
		this.update(true)
		this.trigger(true)
	}

	Toggle.prototype.toggle = function () {
		if (this.$element.prop('checked')) this.off()
		else this.on()
	}

	Toggle.prototype.on = function (silent) {
		if (this.$element.prop('disabled')) return false
		this.$toggle.removeClass(this._offstyle + ' off').addClass(this._onstyle)
		this.$element.prop('checked', true)
		if (!silent) this.trigger()
	}

	Toggle.prototype.off = function (silent) {
		if (this.$element.prop('disabled')) return false
		this.$toggle.removeClass(this._onstyle).addClass(this._offstyle + ' off')
		this.$element.prop('checked', false)
		if (!silent) this.trigger()
	}

	Toggle.prototype.enable = function () {
		this.$toggle.removeAttr('disabled')
		this.$element.prop('disabled', false)
	}

	Toggle.prototype.disable = function () {
		this.$toggle.attr('disabled', 'disabled')
		this.$element.prop('disabled', true)
	}

	Toggle.prototype.update = function (silent) {
		if (this.$element.prop('disabled')) this.disable()
		else this.enable()
		if (this.$element.prop('checked')) this.on(silent)
		else this.off(silent)
	}

	Toggle.prototype.trigger = function (silent) {
		this.$element.off('change.bs.toggle')
		if (!silent) this.$element.change()
		this.$element.on('change.bs.toggle', $.proxy(function() {
			this.update()
		}, this))
	}

	Toggle.prototype.destroy = function() {
		this.$element.off('change.bs.toggle')
		this.$toggleGroup.remove()
		this.$element.removeData('bs.toggle')
		this.$element.unwrap()
	}

	// TOGGLE PLUGIN DEFINITION
	// ========================

	function Plugin(option) {
		return this.each(function () {
			var $this   = $(this)
			var data    = $this.data('bs.toggle')
			var options = typeof option == 'object' && option

			if (!data) $this.data('bs.toggle', (data = new Toggle(this, options)))
			if (typeof option == 'string' && data[option]) data[option]()
		})
	}

	var old = $.fn.bootstrapToggle

	$.fn.bootstrapToggle             = Plugin
	$.fn.bootstrapToggle.Constructor = Toggle

	// TOGGLE NO CONFLICT
	// ==================

	$.fn.toggle.noConflict = function () {
		$.fn.bootstrapToggle = old
		return this
	}

	// TOGGLE DATA-API
	// ===============

	$(function() {
		$('input[type=checkbox][data-toggle^=toggle]').bootstrapToggle()
	})

	$(document).on('click.bs.toggle', 'div[data-toggle^=toggle]', function(e) {
		var $checkbox = $(this).find('input[type=checkbox]')
		$checkbox.bootstrapToggle('toggle')
		e.preventDefault()
	})

}(jQuery);
