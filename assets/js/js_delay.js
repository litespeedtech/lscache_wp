const litespeed_ui_events = [
	'mouseover',
	'click',
	'keydown',
	'wheel',
	"touchmove",
	"touchstart",
];

// const litespeed_js_delay_timer = setTimeout( litespeed_load_delayed_js, 4000 );

litespeed_ui_events.forEach( function( e ) {
	window.addEventListener( e, litespeed_load_delayed_js_forced, { passive: true } );
} );

function litespeed_load_delayed_js_forced() {
	console.log( 'start delay load js' );
	// clearTimeout( litespeed_js_delay_timer );
	litespeed_ui_events.forEach( function( e ) {
		window.removeEventListener( e, litespeed_load_delayed_js_forced, { passive: true } );
	} );
	litespeed_load_delayed_js( true );
}

function litespeed_load_delayed_js( is_forced ) {
	if ( is_forced ) {
		console.log( 'Force runing delay JS' );
	}

	document.querySelectorAll( 'script[type="litespeed/javascript"]' ).forEach( function( e ) {
		console.log( 'load i=' + e.getAttribute( 'i' ), '-----',e );
		var e2 = document.createElement( 'script' );
		var attrs = e.getAttributeNames();
		attrs.forEach( function( aname ) {
			if ( aname == 'type') return;
			e2.setAttribute( aname, e.getAttribute( aname ) );
		} );
		if ( ! e.src && e.textContent ) {
			e2.textContent = e.textContent;
		}
		setTimeout(function(){
			e.after( e2 );
			// document.head.appendChild(e2);
			e.remove();
			// console.log('loaded ' + e2.src + ' --- ' + e2.getAttribute('i'));
		},0);
		// e2 = e.cloneNode(true)
		// e2.setAttribute( 'type', 'text/javascript' );
		// e2.setAttribute( 'data-delayed', '1' );
	} );

	document.querySelectorAll( 'iframe[litespeed-src]' ).forEach( function( e ) {
		e.setAttribute( 'src', e.getAttribute( 'litespeed-src' ) );
	} );
}