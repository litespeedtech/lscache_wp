var litespeed_vary = document.cookie.replace(/(?:(?:^|.*;\s*)_lscache_vary\s*\=\s*([^;]*).*$)|^.*$/, '$1');
if (!litespeed_vary) {
	// Note: as the vary may be changed in Login Cookie option, even the visitor doesn't have this cookie, it doesn't mean the visitor doesn't have the vary, so still need PHP side to decide if need to set vary or not.
	fetch('litespeed_url', {
		method: 'POST',
		cache: 'no-cache',
		redirect: 'follow',
	})
		.then(response => response.json())
		.then(data => {
			console.log(data);
			if (data.hasOwnProperty('reload') && data.reload == 'yes') {
				// Save doc.ref for organic traffic usage
				sessionStorage.setItem('litespeed_docref', document.referrer);

				window.location.reload(true);
			}
		});
}
