fetch( 'litespeed_url', {
	method: 'POST',
	cache: 'no-cache',
	redirect: 'follow',
} ).then( response => response.json() ).then( data => {
	console.log(data);
	if ( data.hasOwnProperty( 'reload' ) && data.reload == 'yes' ) {
		window.location.reload( true );
	}
} );
