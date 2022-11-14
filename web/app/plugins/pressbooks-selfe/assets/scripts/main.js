jQuery( function ( $ ) {
	// Contributing authors
	$( document ).on( 'click', '#pb_contributing_authors .delete-row', function ( e ) {
		e.preventDefault();
		$( this ).parent( '.row' ).remove();
	} );
	$( document ).on( 'click', '#pb_contributing_authors .add-row', function ( e ) {
		e.preventDefault();
		$( this ).before( '<div class="row"><input type="text" name="pb_contributing_authors[]" value="" class="contributing-author regular-text" /> <button class="button button-small delete-row">Delete Row</button></div>' );
	} );

	// Editor
	$( document ).on( 'click', '#pb_editor .delete-row', function ( e ) {
		e.preventDefault();
		$( this ).parent( '.row' ).remove();
	} );
	$( document ).on( 'click', '#pb_editor .add-row', function ( e ) {
		e.preventDefault();
		$( this ).before( '<div class="row"><input type="text" name="pb_editor[]" value="" class="regular-text" /> <button class="button button-small delete-row">Delete Row</button></div>' );
	} );

	// Translator
	$( document ).on( 'click', '#pb_translator .delete-row', function ( e ) {
		e.preventDefault();
		$( this ).parent( '.row' ).remove();
	} );
	$( document ).on( 'click', '#pb_translator .add-row', function ( e ) {
		e.preventDefault();
		$( this ).before( '<div class="row"><input type="text" name="pb_translator[]" value="" class="regular-text" /> <button class="button button-small delete-row">Delete Row</button></div>' );
	} );

	// Publication date
	$( '#pb_publication_date' ).datepicker( {
		dateFormat: 'mm/dd/yy',
	} );

	// Categories
	$( '#pb_bisac_subject' ).select2();
} );
