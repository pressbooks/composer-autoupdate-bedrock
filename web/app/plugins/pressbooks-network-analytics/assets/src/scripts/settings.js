jQuery( document ).ready( function ( $ ) {
	let pb_network_analytics_options = window.pb_network_analytics_options;

	$( '#tabs' ).tabs( {
		active: localStorage.getItem( 'pressbooks-network-settings-tab-idx' ),
		/**
		 * @param event
		 * @param ui
		 */
		activate: function ( event, ui ) {
			localStorage.setItem( 'pressbooks-network-settings-tab-idx', $( this ).tabs( 'option', 'active' ) );
		},
	} );

	// pressbooks-whitelabel/assets/scripts/whitelabel.js
	$( '#pressbooks_require_tos_optin' ).change( function () {
		if ( this.checked ) {
			$( '#pressbooks_tos_page_id' ).removeAttr( 'disabled' );
		} else {
			$( '#pressbooks_tos_page_id' ).attr( 'disabled', true );
			$( '#pressbooks_tos_page_id' ).val( '' );
		}
	} );

	// Default PDF Page Size
	let pdfPageSizesSelection = $( '#pdf_page_sizes_selection' );
	if ( pdfPageSizesSelection.val() === 'custom' ) {
		$( '#pdf_page_sizes' ).show();
	} else {
		$( '#pdf_page_sizes' ).hide();
	}
	pdfPageSizesSelection.change( function () {
		let s = this.value;
		if ( s === 'custom' ) {
			$( '#pb_pdf_page_width_default' ).val( '' );
			$( '#pb_pdf_page_height_default' ).val( '' );
			$( '#pdf_page_sizes' ).show();
		} else {
			$( '#pdf_page_sizes' ).hide();
			let [ w, h ] = s.split( '_' );
			$( '#pb_pdf_page_width_default' ).val( w );
			$( '#pb_pdf_page_height_default' ).val( h );
		}
	} );

	// Highlight error in forms with tabs
	$( '.ui-tabs-panel input, .ui-tabs-panel textarea' ).on( 'invalid', function () {
		// Find the tab-pane that this element is inside, and get the id
		let closest = $( this ).closest( '.ui-tabs-panel' );
		// Switch to selected tab
		let id = closest.attr( 'id' );
		let index = $( '#tabs a[href="#' + id + '"]' ).parent().index();
		$( '#tabs' ).tabs( 'option', 'active', index );
	} );

	// Retrieve LTI 1.3 connections count
	getLtiConnections();

	$( '#update-lti-usage-count' ).click( function () {
		$( this ).prop( 'disabled', true );
		getLtiConnections( 1 );
	} );

	/**
	 * @param update
	 */
	function getLtiConnections( update = 0 ) {
		if ( ! pb_network_analytics_options.lti_1p3_enabled ) {
			return;
		}

		let data = {
			action: pb_network_analytics_options.lti_usage_stats_action,
			ajaxNonce: pb_network_analytics_options.lti_usage_nonce,
			update,
		};

		$.ajax( {
			data,
			method: 'POST',
			url: pb_network_analytics_options.ajax_url,
			/**
			 * @param response
			 */
			success: response => {
				$( '#lti-connections' ).html( response.connections );
				$( '#lti-connections-last-updated' ).html( response.last_updated );
				$( '#update-lti-usage-count' ).prop( 'disabled', false );
			},
		} );
	}
} );
