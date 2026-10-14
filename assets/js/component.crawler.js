(function ($) {
	'use strict';

	window.litespeed_crawler_simulation = function (container, list) {
		const lang = litespeed_data.lang;
		const name = litespeed_data.ids.crawler_cookies;
		const $container = $(container);
		const $add = $('<p>').append(
			$('<button>', { type: 'button', class: 'button button-link litespeed-form-action litespeed-link-with-icon' })
				.append($('<span>', { class: 'dashicons dashicons-plus-alt' }), document.createTextNode(lang.add_cookie_simulation_row))
				.on('click', function () { add_row({ name: '', vals: '' }); })
		);
		$container.empty().append($add);

		function add_row(item) {
			const $row = $('<div>', { class: 'litespeed-block' });
			$row.append(
				$('<div>', { class: 'litespeed-col-auto' }).append(
					$('<label>', { class: 'litespeed-form-label' }).text(lang.cookie_name),
					$('<input>', { type: 'text', name: name + '[name][]', class: 'regular-text', 'aria-label': lang.cookie_name })
						.val(item.name)
				),
				$('<div>', { class: 'litespeed-col-auto' }).append(
					$('<label>', { class: 'litespeed-form-label' }).text(lang.cookie_values),
					$('<textarea>', { rows: 5, cols: 40, name: name + '[vals][]', placeholder: lang.one_per_line, 'aria-label': lang.cookie_values })
						.val(Array.isArray(item.vals) ? item.vals.join('\n') : item.vals)
				),
				$('<div>', { class: 'litespeed-col-auto' }).append(
					$('<button>', { type: 'button', class: 'button button-link litespeed-collection-button litespeed-danger' })
						.append($('<span>', { class: 'dashicons dashicons-dismiss' }), $('<span>', { class: 'screen-reader-text' }).text(lang.remove_cookie_simulation))
						.on('click', function () { $row.remove(); })
				)
			).insertBefore($add);
		}

		Object.values(list).forEach(add_row);
	};
})(jQuery);
