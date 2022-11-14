jQuery( document ).ready( function ( $ ) {
	$( '#pressbooks_require_tos_optin' ).change( function () {
		if ( this.checked ) {
			$( '#pressbooks_tos_page_id' ).removeAttr( 'disabled' );
		} else {
			$( '#pressbooks_tos_page_id' ).attr( 'disabled', true );
			$( '#pressbooks_tos_page_id' ).val( '' );
		}
	} );
} );
