window.litespeed_ui_events = Array.isArray(window.litespeed_ui_events) ? window.litespeed_ui_events : ['mouseover', 'click', 'keydown', 'wheel', 'touchmove', 'touchstart', 'pointerup', 'pointerdown'];
var urlCreator = window.URL || window.webkitURL;

// const litespeed_js_delay_timer = setTimeout( litespeed_load_delayed_js, 70 );

window.litespeed_ui_events.forEach(e => {
	window.addEventListener(e, litespeed_load_delayed_js_force, { passive: true }); // Use passive to save GPU in interaction
});

function litespeed_load_delayed_js_force() {
	console.log('[LiteSpeed] Start Load JS Delayed');
	// clearTimeout( litespeed_js_delay_timer );
	window.litespeed_ui_events.forEach(e => {
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
	const pending = new EventTarget(), listeners = new Map();
	const add = window.addEventListener, remove = window.removeEventListener;
	const own_add = Object.prototype.hasOwnProperty.call(window, 'addEventListener');
	const own_remove = Object.prototype.hasOwnProperty.call(window, 'removeEventListener');
	let collecting = true, event;
	// Keep late load listeners separate so excluded scripts are not run again.
	const add_load = window.addEventListener = function(type, listener, options) {
		if (collecting && this === window && type === 'load' && document.readyState === 'complete' && listener && (typeof listener === 'function' || typeof listener === 'object')) {
			if (!listeners.has(listener)) listeners.set(listener, typeof listener === 'function' ? listener.bind(window) : listener);
			return pending.addEventListener(type, listeners.get(listener), options);
		}
		return (own_add ? add : Object.getPrototypeOf(window).addEventListener).apply(this, arguments);
	};
	const remove_load = window.removeEventListener = function(type, listener, options) {
		if (this === window && type === 'load' && listeners.has(listener)) pending.removeEventListener(type, listeners.get(listener), options);
		return (own_remove ? remove : Object.getPrototypeOf(window).removeEventListener).apply(this, arguments);
	};
	try {
		let js_list = [];
		// Prepare all JS
		document.querySelectorAll('script[type="litespeed/javascript"]').forEach(e => {
			js_list.push(e);
		});

		// Load by sequence
		for (let script in js_list) {
			await new Promise(resolve => litespeed_load_one(js_list[script], resolve));
		}

		// Simulate doc.loaded before replaying late window load listeners.
		document.dispatchEvent(new Event('DOMContentLiteSpeedLoaded'));
		window.dispatchEvent(new Event('DOMContentLiteSpeedLoaded'));
		// Let queued ready callbacks register their window load listeners.
		await new Promise(resolve => setTimeout(resolve));
		collecting = false;
		event = new Event('load');
		// Match native window load's document target and window receiver.
		Object.defineProperties(event, {target: {value: document}, currentTarget: {value: window, configurable: true}});
		pending.dispatchEvent(event);
	} finally {
		collecting = false;
		if (event) delete event.currentTarget;
		// Do not shadow prototype instrumentation installed while scripts were loading.
		if (window.addEventListener === add_load) {
			if (own_add) window.addEventListener = add;
			else delete window.addEventListener;
		}
		if (window.removeEventListener === remove_load) {
			if (own_remove) window.removeEventListener = remove;
			else delete window.removeEventListener;
		}
		listeners.forEach(listener => {
			pending.removeEventListener('load', listener, false);
			pending.removeEventListener('load', listener, true);
		});
		listeners.clear();
	}
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
