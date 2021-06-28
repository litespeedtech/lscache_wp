/**
 * Lazyload init js
 *
 * @author LiteSpeed
 * @since 1.4
 *
 */

(function( window, document ){
	'use strict' ;

	var instance;
	var update_lazyload;

	var litespeed_finish_callback = function(){
		document.body.classList.add( 'litespeed_lazyloaded' );
	}

	var init = function(){
		console.log( '[LiteSpeed] Start Lazy Load Images' )
		instance = new LazyLoad( { elements_selector: "[data-lazyloaded]", callback_finish: litespeed_finish_callback } );

		update_lazyload = function(){
			instance.update() ;
		};

		if ( window.MutationObserver ) {
			new MutationObserver( update_lazyload ).observe( document.documentElement, { childList: true, subtree: true, attributes: true } ) ;
		}
	};

	window.addEventListener ? window.addEventListener( "load", init, false ) : window.attachEvent( "onload", init ) ;

})( window, document ) ;