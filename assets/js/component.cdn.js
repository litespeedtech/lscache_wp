(function ($) {
	'use strict';

	window.litespeed_cdn_mapping = function (container, list) {
		const lang = litespeed_data.lang;
		const name = litespeed_data.ids.cdn_mapping;
		const $container = $(container);
		const $add = $('<p>').append(
			$('<button>', { type: 'button', class: 'button button-link litespeed-form-action litespeed-link-with-icon' })
				.append($('<span>', { class: 'dashicons dashicons-plus-alt' }), document.createTextNode(lang.add_cdn_mapping_row))
				.on('click', function () { add_row({ url: '', filetype: '' }); })
		);
		$container.empty().append($add);

		function add_row(item) {
			const filetype = Array.isArray(item.filetype) ? item.filetype.join('\n') : (item.filetype || '');
			const $row = $('<div>', { class: 'litespeed-block' });
			const $toggles = $('<div>', { class: 'litespeed-col-auto litespeed-cdn-mapping-col2' });
			['inc_img', 'inc_css', 'inc_js'].forEach(function (key) {
				const $input = $('<input>', { type: 'hidden', name: name + '[' + key + '][]' }).val(item[key] ? 1 : 0);
				const $toggle = $('<div>', { role: 'switch', tabindex: 0, 'aria-label': lang['cdn_mapping_' + key] });
				function update_toggle() {
					const enabled = $input.val() === '1';
					$toggle.attr({
						class: 'litespeed-toggle litespeed-toggle-btn litespeed-toggle-btn-' + (enabled ? 'primary' : 'default litespeed-toggleoff'),
						'aria-checked': enabled ? 'true' : 'false',
					});
				}
				$toggle.append($input, $('<div>', { class: 'litespeed-toggle-group' }).append(
					$('<label>', { class: 'litespeed-toggle-btn litespeed-toggle-btn-primary litespeed-toggle-on' }).text(lang.on),
					$('<label>', { class: 'litespeed-toggle-btn litespeed-toggle-btn-default litespeed-toggle-active litespeed-toggle-off' }).text(lang.off),
					$('<span>', { class: 'litespeed-toggle-handle litespeed-toggle-btn litespeed-toggle-btn-default' })
				)).on('click', function () {
					$input.val($input.val() === '1' ? 0 : 1);
					update_toggle();
				}).on('keydown', function (event) {
					if (event.key === ' ' || event.key === 'Enter') {
						event.preventDefault();
						$toggle.trigger('click');
					}
				});
				update_toggle();
				$toggles.append($('<div>', { class: 'litespeed-row litespeed-toggle-wrapper' }).append(
					$('<div>', { class: 'litespeed-cdn-mapping-inc litespeed-form-label litespeed-form-label--toggle' }).text(lang['cdn_mapping_' + key]), $toggle
				));
			});

			// WordPress supplies this description with an escaped example URL.
			$row.append(
				$('<div>', { class: 'litespeed-cdn-mapping-col1' }).append(
					$('<label>', { class: 'litespeed-form-label' }).text(lang.cdn_mapping_url),
					$('<input>', { type: 'text', name: name + '[url][]', class: 'regular-text litespeed-input-long', 'aria-label': lang.cdn_mapping_url }).val(item.url || ''),
					$('<div>', { class: 'litespeed-desc' }).append($('<span>').html(lang.cdn_mapping_url_desc))
				),
				$toggles,
				$('<div>', { class: 'litespeed-col-auto' }).append(
					$('<label>', { class: 'litespeed-form-label' }).text(lang.cdn_mapping_filetype),
					$('<textarea>', { name: name + '[filetype][]', rows: filetype.split('\n').length + 2, cols: 18, 'aria-label': lang.cdn_mapping_filetype }).val(filetype)
						.on('input', function () { $(this).attr('rows', this.value.split('\n').length + 2); }),
					$('<div>', { class: 'litespeed-desc' }).append(
						$('<a>', { href: lang.cdn_file_types_url, target: '_blank', rel: 'noopener noreferrer' }).text(lang.cdn_file_types_link_text)
					)
				),
				$('<div>', { class: 'litespeed-col-auto' }).append(
					$('<button>', { type: 'button', class: 'button button-link litespeed-collection-button litespeed-danger' })
						.append($('<span>', { class: 'dashicons dashicons-dismiss' }), $('<span>', { class: 'screen-reader-text' }).text(lang.cdn_mapping_remove))
						.on('click', function () { $row.remove(); })
				)
			).insertBefore($add);
		}

		Object.values(list).forEach(add_row);
	};
})(jQuery);
