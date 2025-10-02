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
		/************** Initialize dark mode toggle before accesskey mapping **************/
		litespeed_init_dark_mode();

		/************** Common LiteSpeed JS **************/
		// Link confirm
		$('[data-litespeed-cfm]').on('click', function (event) {
			var cfm_txt = $.trim($(this).data('litespeed-cfm')).replace(/\\n/g, '\n');
			if (cfm_txt === '') {
				return true;
			}
			if (confirm(cfm_txt)) {
				return true;
			}
			event.preventDefault();
			event.stopImmediatePropagation();
			return false;
		});

		/************** LSWCP JS ****************/
		// page tab switch functionality
		(function () {
			var hash = window.location.hash.substr(1);
			var $tabs = $('[data-litespeed-tab]');
			var $subtabs = $('[data-litespeed-subtab]');

			// Handle tab and subtab events
			var tab_action = function ($elems, type) {
				type = litespeed_tab_type(type);
				var data = 'litespeed-' + type;
				$elems.on('click', function (_event) {
					litespeed_display_tab($(this).data(data), type);
					document.cookie = 'litespeed_' + type + '=' + $(this).data(data);
					$(this).blur();
				});
			};
			tab_action($tabs);
			tab_action($subtabs, 'subtab');

			if (!$tabs.length > 0) {
				// No tabs exist
				return;
			}

			// Find hash in tabs and subtabs
			var $hash_tab = $tabs.filter('[data-litespeed-tab="' + hash + '"]:first');
			var $hash_subtab = $subtabs.filter('[data-litespeed-subtab="' + hash + '"]:first');

			// Find tab name
			var $subtab;
			var $tab;
			var tab_name;
			if ($hash_subtab.length > 0) {
				// Hash is a subtab
				$tab = $hash_subtab.closest('[data-litespeed-layout]');
				if ($tab.length > 0) {
					$subtab = $hash_subtab;
					tab_name = $tab.data('litespeed-layout');
				}
			}
			if (typeof $tab === 'undefined' || $tab.length < 1) {
				// Maybe hash is a tab
				$tab = $hash_tab;
				if ($tab.length < 1) {
					// Maybe tab cookie exists
					$tab = litespeed_tab_cookie($tabs);
					if ($tab.length < 1) {
						// Use the first tab by default
						$tab = $tabs.first();
					}
				}
				if (typeof tab_name === 'undefined') {
					tab_name = $tab.data('litespeed-tab');
				}
			}

			// Always display a tab
			litespeed_display_tab(tab_name);

			// Find subtab name
			if (typeof $subtab === 'undefined' || $subtab.length < 1) {
				$subtab = litespeed_tab_cookie($subtabs, 'subtab');
			}
			if ($subtab.length > 0) {
				var subtab_name = $subtab.data('litespeed-subtab');
				// Display a subtab
				litespeed_display_tab(subtab_name, 'subtab');
			}
		})();

		/******************** Clear whm msg ********************/
		$(document).on('click', '.lscwp-whm-notice .notice-dismiss', function () {
			$.get(litespeed_data.ajax_url_dismiss_whm);
		});
		/******************** Clear rule conflict msg ********************/
		$(document).on('click', '.lscwp-notice-ruleconflict .notice-dismiss', function () {
			$.get(litespeed_data.ajax_url_dismiss_ruleconflict);
		});

		/** Accesskey **/
		$('[litespeed-accesskey]').map(function () {
			var thiskey = $(this).attr('litespeed-accesskey');
			if (thiskey == '') {
				return;
			}
			// Append shortcut info to existing title or set default
			var currentTitle = $(this).attr('title');
			if (currentTitle) {
				$(this).attr('title', currentTitle + ' (Shortcut: ' + thiskey.toLocaleUpperCase() + ')');
			} else {
				$(this).attr('title', 'Shortcut : ' + thiskey.toLocaleUpperCase());
			}
			var that = this;
			$(document).on('keydown', function (e) {
				if ($(':input:focus').length) return;
				if (e.metaKey || e.ctrlKey || e.altKey || e.shiftKey) return;

				if (e.key && e.key.toLowerCase() === thiskey.toLowerCase()) {
					$(that)[0].click();
				}
			});
		});

		/** Lets copy one more submit button **/
		if ($('input[name="LSCWP_CTRL"]').length > 0) {
			var btn = $('input.litespeed-duplicate-float');
			btn.clone().addClass('litespeed-float-submit').removeAttr('id').insertAfter(btn);
		}
		if ($('input[id="LSCWP_NONCE"]').length > 0) {
			$('input[id="LSCWP_NONCE"]').removeAttr('id');
		}

		/**
		 * Human readable time conversation
		 * @since  3.0
		 */
		if ($('[data-litespeed-readable]').length > 0) {
			$('[data-litespeed-readable]').each(function (index, el) {
				var that = this;
				var $input = $(this).siblings('input[type="text"]');

				var txt = litespeed_readable_time($input.val());
				$(that).html(txt ? '= ' + txt : '');

				$input.on('keyup', function (event) {
					var txt = litespeed_readable_time($(this).val());
					$(that).html(txt ? '= ' + txt : '');
				});
			});
		}

		/**
		 * Click only once
		 */
		if ($('[data-litespeed-onlyonce]').length > 0) {
			$('[data-litespeed-onlyonce]').on('click', function (e) {
				if ($(this).hasClass('disabled')) {
					e.preventDefault();
				}
				$(this).addClass('disabled');
			});
		}
	});
})(jQuery);

/**
 * Plural handler
 */
function litespeed_plural($num, $txt) {
	if ($num > 1) return $num + ' ' + $txt + 's';

	return $num + ' ' + $txt;
}

/**
 * Convert seconds to readable time
 */
function litespeed_readable_time(seconds) {
	if (seconds < 60) {
		return '';
	}

	var second = Math.floor(seconds % 60);
	var minute = Math.floor((seconds / 60) % 60);
	var hour = Math.floor((seconds / 3600) % 24);
	var day = Math.floor((seconds / 3600 / 24) % 7);
	var week = Math.floor(seconds / 3600 / 24 / 7);

	var str = '';
	if (week) str += ' ' + litespeed_plural(week, 'week');
	if (day) str += ' ' + litespeed_plural(day, 'day');
	if (hour) str += ' ' + litespeed_plural(hour, 'hour');
	if (minute) str += ' ' + litespeed_plural(minute, 'minute');
	if (second) str += ' ' + litespeed_plural(second, 'second');

	return str;
}

/**
 * Normalize specified tab type
 * @since  4.7
 */
function litespeed_tab_type(type) {
	return 'subtab' === type ? type : 'tab';
}

/**
 * Sniff cookies for tab and subtab
 * @since  4.7
 */
function litespeed_tab_cookie($elems, type) {
	type = litespeed_tab_type(type);
	var re = new RegExp('(?:^|.*;)\\s*litespeed_' + type + '\\s*=\\s*([^;]*).*$|^.*$', 'ms');
	var name = document.cookie.replace(re, '$1');
	return $elems.filter('[data-litespeed-' + type + '="' + name + '"]:first');
}

function litespeed_display_tab(name, type) {
	type = litespeed_tab_type(type);
	var $tabs;
	var $layouts;
	var classname;
	var layout_type;
	if ('subtab' === type) {
		classname = 'focus';
		layout_type = 'sublayout';
		$tabs = jQuery('[data-litespeed-subtab="' + name + '"]')
			.siblings('[data-litespeed-subtab]')
			.addBack();
		$layouts = jQuery('[data-litespeed-sublayout="' + name + '"]')
			.siblings('[data-litespeed-sublayout]')
			.addBack();
	} else {
		// Maybe handle subtabs
		var $subtabs = jQuery('[data-litespeed-layout="' + name + '"] [data-litespeed-subtab]');
		if ($subtabs.length > 0) {
			// Find subtab name
			var $subtab = litespeed_tab_cookie($subtabs, 'subtab');
			if ($subtab.length < 1) {
				$subtab = jQuery('[data-litespeed-layout="' + name + '"] [data-litespeed-subtab]:first');
			}
			if ($subtab.length > 0) {
				var subtab_name = $subtab.data('litespeed-subtab');
				// Display a subtab
				litespeed_display_tab(subtab_name, 'subtab');
			}
		}
		classname = 'nav-tab-active';
		layout_type = 'layout';
		$tabs = jQuery('[data-litespeed-tab]');
		$layouts = jQuery('[data-litespeed-layout]');
	}
	$tabs.removeClass(classname);
	$tabs.filter('[data-litespeed-' + type + '="' + name + '"]').addClass(classname);
	$layouts.hide();
	$layouts.filter('[data-litespeed-' + layout_type + '="' + name + '"]').show();
}

function litespeed_copy_to_clipboard(elementId, clickedElement) {
	var range = document.createRange();
	range.selectNode(document.getElementById(elementId));
	window.getSelection().removeAllRanges();
	window.getSelection().addRange(range);
	document.execCommand('copy');
	window.getSelection().removeAllRanges();

	clickedElement.setAttribute('aria-label', 'Copied!');
}

// Dark mode toggle functionality
function litespeed_init_dark_mode() {
	'use strict';

	// Only add toggle on LiteSpeed pages
	if (window.location.search.indexOf('page=litespeed') === -1) return;

	// Create toggle button
	var toggleBtn = document.createElement('button');
	toggleBtn.className = 'litespeed-dark-mode-toggle';
	toggleBtn.setAttribute('title', 'Toggle Dark Mode');
	toggleBtn.setAttribute('aria-label', 'Toggle Dark Mode');
	toggleBtn.setAttribute('litespeed-accesskey', 'z');
	toggleBtn.setAttribute('data-litespeed-noprefix', true);

	function applyDarkMode() {
		var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
		var savedPreference = localStorage.getItem('litespeed-dark-preference');
		var isDark = savedPreference ? savedPreference === 'dark' : prefersDark;

		// Determine needed class (only when overriding browser preference)
		var needsClass;
		if (savedPreference === 'dark' && !prefersDark) {
			needsClass = 'litespeed-darkmode';
		} else if (savedPreference === 'light' && prefersDark) {
			needsClass = 'litespeed-lightmode';
		}

		// Only update DOM if class needs to change
		if (needsClass) {
			if (!document.body.classList.contains(needsClass)) {
				document.body.classList.remove('litespeed-darkmode', 'litespeed-lightmode');
				document.body.classList.add(needsClass);
			}
		} else {
			if (document.body.classList.contains('litespeed-darkmode') || document.body.classList.contains('litespeed-lightmode')) {
				document.body.classList.remove('litespeed-darkmode', 'litespeed-lightmode');
			}
		}

		// Update button icon
		toggleBtn.innerHTML = isDark ? '☀️' : '🌙';
	}

	// Initialize
	applyDarkMode();

	// Toggle handler
	toggleBtn.addEventListener('click', function() {
		var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
		var savedPreference = localStorage.getItem('litespeed-dark-preference');
		var currentlyDark = savedPreference ? savedPreference === 'dark' : prefersDark;

		// Toggle and store only if different from browser preference
		if (!currentlyDark === prefersDark) {
			localStorage.removeItem('litespeed-dark-preference');
		} else {
			localStorage.setItem('litespeed-dark-preference', currentlyDark ? 'light' : 'dark');
		}

		applyDarkMode();
	});

	// Listen for system theme changes
	if (window.matchMedia) {
		window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', applyDarkMode);
	}

	// Add to page
	document.body.appendChild(toggleBtn);
}
