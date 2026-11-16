var litespeed_vary = document.cookie.replace(/(?:(?:^|.*;\s*)_lscache_vary\s*\=\s*([^;]*).*$)|^.*$/, '$1');
if (!litespeed_vary) {
	// Guard against infinite reload loop when response is served from an external cache (e.g. Archive.org)
	if (sessionStorage.getItem('litespeed_reloaded')) {
		console.log('LiteSpeed: skipping guest vary reload (already reloaded this session)');
	} else {
		(function () {
			// Note: as the vary may be changed in Login Cookie option, even the visitor doesn't have this cookie, it doesn't mean the visitor doesn't have the vary, so still need PHP side to decide if need to set vary or not.
			// The fallback is an empty string unless the primary endpoint was replaced (e.g. WCML); then it is the default endpoint.
			var primary = 'litespeed_url';
			var fallback = 'litespeed_fallback_url';

			// Post to one endpoint; resolves with its JSON reply, rejects on HTTP error, network error, timeout or invalid JSON.
			var request = function (url, withTimeout) {
				var options = {
					method: 'POST',
					cache: 'no-cache',
					mode: 'same-origin',
					credentials: 'same-origin',
					referrerPolicy: 'origin',
					redirect: 'follow',
				};
				var timer = 0;
				if (withTimeout && typeof AbortController !== 'undefined') {
					var controller = new AbortController();
					options.signal = controller.signal;
					timer = setTimeout(function () {
						controller.abort();
					}, 5000);
				}
				// Keep the timer running until the body is read, so a stalled body is aborted as well.
				return fetch(url, options)
					.then(response => {
						if (!response.ok) {
							throw new Error('HTTP ' + response.status);
						}
						return response.json();
					})
					.then(
						data => {
							clearTimeout(timer);
							return data;
						},
						error => {
							clearTimeout(timer);
							throw error;
						}
					);
			};

			// Only an explicit reload request counts as success; anything else is treated as a failure of that endpoint.
			var attempt = function (url, withTimeout) {
				return request(url, withTimeout).then(data => {
					console.log(data);
					if (data === null || typeof data !== 'object' || data.reload != 'yes') {
						throw new Error('no reload requested');
					}
				});
			};

			// Only a request that has a fallback to fall back to is time-limited; the default endpoint behaves as it always did.
			var result = attempt(primary, !!fallback);
			if (fallback) {
				result = result.catch(() => attempt(fallback, false));
			}
			result
				.then(() => {
					// Save doc.ref for organic traffic usage
					sessionStorage.setItem('litespeed_docref', document.referrer);
					sessionStorage.setItem('litespeed_reloaded', '1');

					window.location.reload(true);
				})
				.catch(error => {
					console.log(error);
				});
		})();
	}
}
