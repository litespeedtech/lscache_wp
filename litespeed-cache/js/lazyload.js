/**
 * Lazyload init js
 *
 * @author LiteSpeed
 * @since 1.4
 *
 */

(function( window, document ){
	'use strict' ;

	var instance ;
	var update_lazyload ;

	var init = function(){
		instance = new LazyLoad( { elements_selector : "[data-lazyloaded]" } ) ;

		update_lazyload = function(){
			instance.update() ;
		};

		if ( window.MutationObserver ) {
			new MutationObserver( update_lazyload ).observe( document.documentElement, { childList: true, subtree: true, attributes: true } ) ;
		}
	};

	window.addEventListener ? window.addEventListener( "load", init, false ) : window.attachEvent( "onload", init ) ;

})( window, document ) ;