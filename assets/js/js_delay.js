window.litespeed_ui_events = window.litespeed_ui_events || ['mouseover', 'click', 'keydown', 'wheel', 'touchmove', 'touchstart', 'pointerup', 'pointerdown'];
var urlCreator = window.URL || window.webkitURL;

// const litespeed_js_delay_timer = setTimeout( litespeed_load_delayed_js, 70 );

litespeed_ui_events.forEach(e => {
	window.addEventListener(e, litespeed_load_delayed_js_force, { passive: true }); // Use passive to save GPU in interaction
});

function litespeed_load_delayed_js_force() {
	console.log('[LiteSpeed] Start Load JS Delayed');
	// clearTimeout( litespeed_js_delay_timer );
	litespeed_ui_events.forEach(e => {
		window.removeEventListener(e, litespeed_load_delayed_js_force, { passive: true });
	});

	document.querySelectorAll('iframe[data-litespeed-src]').forEach(e => {
		e.setAttribute('src', e.getAttribute('data-litespeed-src'));
	});

	// Prevent early loading
	if (document.readyState == 'loading') {
		window.addEventListener('DOMContentLoaded', litespeed_load_delayed_js);
	} else {
		litespeed_load_delayed_js();
	}
}

async function litespeed_load_delayed_js() {
	let js_list = [];
	// Prepare all JS
	document.querySelectorAll('script[type="litespeed/javascript"]').forEach(e => {
		js_list.push(e);
	});

	// Load by sequence
	for (let script in js_list) {
		await new Promise(resolve => litespeed_load_one(js_list[script], resolve));
	}

	// Simulate doc.loaded
	document.dispatchEvent(new Event('DOMContentLiteSpeedLoaded'));
	window.dispatchEvent(new Event('DOMContentLiteSpeedLoaded'));
}

/**
 * Load one JS synchronously
 */
function litespeed_load_one(e, resolve) {
	console.log('[LiteSpeed] Load ', e);

	var e2 = document.createElement('script');

	// Memory Cleanup & Resolution logic
	var handler = function() {
		if (e2.src.startsWith('blob:')) {
			URL.revokeObjectURL(e2.src); // RECLAIM MEMORY
		}
		resolve();
	};

	e2.addEventListener('load', handler);
	e2.addEventListener('error', handler);

	var attrs = e.getAttributeNames();
	attrs.forEach(aname => {
		if (aname == 'type') return;
		e2.setAttribute(aname == 'data-src' ? 'src' : aname, e.getAttribute(aname));
	});
	e2.type = 'text/javascript';

	// Inline script
	if (!e2.src && e.textContent) {
		e2.src = litespeed_inline2src(e.textContent);
		// e2.textContent = e.textContent;
	}

	// Deploy to dom
	e.after(e2);
	e.remove();
}

/**
 * Prepare inline script
 */
function litespeed_inline2src(data) {
	try {
		var src = urlCreator.createObjectURL(
			new Blob([data.replace(/^(?:<!--)?(.*?)(?:-->)?$/gm, '$1')], {
				type: 'text/javascript',
			}),
		);
	} catch (e) {
		var src = 'data:text/javascript;base64,' + btoa(data.replace(/^(?:<!--)?(.*?)(?:-->)?$/gm, '$1'));
	}

	return src;
}
