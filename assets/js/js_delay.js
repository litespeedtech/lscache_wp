const litespeed_ui_events = [
	'mouseover',
	'click',
	'keydown',
	'wheel',
	"touchmove",
	"touchstart",
];

const litespeed_js_delay_timer = setTimeout( litespeed_load_delayed_js, 4000 );

litespeed_ui_events.forEach( function( e ) {
	window.addEventListener( e, litespeed_load_delayed_js_forced, { passive: true } );
} );

function litespeed_load_delayed_js_forced() {
	console.log( 'start delay load js' );
	clearTimeout( litespeed_js_delay_timer );
	litespeed_ui_events.forEach( function( e ) {
		window.removeEventListener( e, litespeed_load_delayed_js_forced, { passive: true } );
	} );
	litespeed_load_delayed_js( true );
}

function litespeed_load_delayed_js( is_forced ) {
	if ( is_forced ) {
		console.log( 'Force runing delay JS' );
	}

	document.querySelectorAll( 'script[type="litespeed/javascript"]' ).forEach( function( el ) {
		//console.log( 'load ' + el.getAttribute( 'src' ), '-----',el );
		el2 = el.cloneNode(true)
		el2.setAttribute( 'type', 'text/javascript' );
		el2.setAttribute( 'data-delayed', '1' );
		el.parentNode.replaceChild( el2, el);
	} );

	document.querySelectorAll( 'iframe[litespeed-src]' ).forEach( function( el ) {
		el.setAttribute( 'src', el.getAttribute( 'litespeed-src' ) );
	} );
}