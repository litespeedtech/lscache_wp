var litespeed_docref = sessionStorage.getItem( 'litespeed_docref' );
if ( litespeed_docref ) {
	Object.defineProperty(document, "referrer", {get : function(){ return litespeed_docref; }});
	sessionStorage.removeItem( 'litespeed_docref' );
}