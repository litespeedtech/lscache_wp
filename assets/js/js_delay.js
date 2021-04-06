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
	litespeed_load_delayed_js();
}

function litespeed_load_delayed_js() {
	document.querySelectorAll( 'script[type="litespeed/javascript"]' ).forEach( function( el ) {
		//console.log( 'load ' + el.getAttribute( 'src' ), '-----',el );
		el2 = el.cloneNode(true)
		el2.setAttribute( 'type', 'text/javascript' );
		el.parentNode.insertBefore( el2, el);
	} );
}