/* global PB_Network_Analytics_BookListToken, Tabulator */

import { inlineEditAjax, bulkActions } from './booklist-actions';
import { filters } from './booklist-filters.js';
import { formatBytes, isTruthy } from './utility.js';
const { __ } = wp.i18n;

window.addEventListener( 'load', function () {

	// ------------------------------------------------------------------------
	// Tabulator
	// ------------------------------------------------------------------------

	// Tabulator ajax params, @see http://tabulator.info/docs/4.4/data#ajax

	let ajaxParams = {
		action: PB_Network_Analytics_BookListToken.ajaxAction,
		_wpnonce: PB_Network_Analytics_BookListToken.ajaxNonce,
	};

	const networkAdminAllowedToGrade = PB_Network_Analytics_BookListToken.ajaxIsNetworkManager === '1' && PB_Network_Analytics_BookListToken.ajaxGranularGrade === '1';

	// Extra Formatters

	Tabulator.prototype.extendModule( 'format', 'formatters', {
		/**
		 * @param cell
		 * @param formatterParams
		 */
		withBreaks: function ( cell, formatterParams ) {
			cell.getElement().style.whiteSpace = 'pre-wrap';
			return cell.getValue();
		},
		/**
		 * @param cell
		 * @param formatterParams
		 */
		boldWithBreaks: function ( cell, formatterParams ) {
			cell.getElement().style.whiteSpace = 'pre-wrap';
			return '<strong>' + cell.getValue() + '</strong>'; //make the contents of the cell bold
		},
		/**
		 * @param cell
		 * @param formatterParams
		 */
		uppercase: function ( cell, formatterParams ) {
			return cell.getValue().toUpperCase(); //make the contents of the cell uppercase
		},
		/**
		 * @param cell
		 * @param formatterParams
		 */
		formatBytes: function ( cell, formatterParams ) {
			return formatBytes( cell.getValue() );
		},
		/**
		 * @param cell
		 * @param formatterParams
		 */
		numberWithCommas: function ( cell, formatterParams ) {
			return ( cell.getValue() && typeof cell.getValue() === 'string' ) ?
				cell.getValue().replace( /\B(?=(\d{3})+(?!\d))/g, ',' ) : 0;
		},
		/**
		 * @param cell
		 * @param formatterParams
		 */
		licenses: function ( cell, formatterParams ) {
			let val = cell.getValue();
			if ( val === 'public-domain' ) {
				val =  __( 'Public Domain', 'pressbooks-network-analytics' );
			} else if ( val === 'all-rights-reserved' ) {
				val = __( 'All Rights Reserved', 'pressbooks-network-analytics' );
			} else {
				val = val.toUpperCase();
			}
			return val;
		},
		/**
		 * @param cell
		 * @param formatterParams
		 */
		deactivated: function ( cell, formatterParams ) {
			let val = cell.getValue();
			if ( val === '1' ) {
				cell.getRow().getElement().style.backgroundColor = '#ff8573';
			}
			return val;
		},
	} );

	// Table
	let bulkActionHtml = '<div class=\'alignleft actions bulkactions\'>' +
		`<label for='bulk-action-selector' class='screen-reader-text'>${ __( 'Select bulk action', 'pressbooks-network-analytics' ) }</label>` +
		'<select name=\'action\' id=\'bulk-action-selector\'>' +
		`<option value=''>${ __( 'Bulk Actions', 'pressbooks-network-analytics' ) }</option>` +
		`<option value='public_1'>${ __( 'Make public', 'pressbooks-network-analytics' ) }</option>` +
		`<option value='public_0'>${ __( 'Make private', 'pressbooks-network-analytics' ) }</option>` +
		`<option value='inCatalog_1'>${ __( 'Add to catalog', 'pressbooks-network-analytics' ) }</option>` +
		`<option value='inCatalog_0'>${ __( 'Remove from catalog', 'pressbooks-network-analytics' ) }</option>`;

	if ( PB_Network_Analytics_BookListToken.ajaxIsSuperAdmin === '1' || networkAdminAllowedToGrade ) {
		bulkActionHtml += `<option value='gradingEnabled_1'>${ __( 'Allow grading', 'pressbooks-network-analytics' ) }</option>` +
			`<option value='gradingEnabled_0'>${ __( 'Disallow grading', 'pressbooks-network-analytics' ) }</option>`;
	}

	bulkActionHtml += `<option value='delete_1'>${ __( 'Delete', 'pressbooks-network-analytics' ) }</option>` +
		'</select> ' +
		`<button id='doaction' class='button action'>${ __( 'Apply', 'pressbooks-network-analytics' ) }</button>` +
		'</div>';

	let table = new Tabulator( '#booklist', {
		ajaxURL: PB_Network_Analytics_BookListToken.ajaxUrl,
		ajaxParams: ajaxParams,
		footerElement: bulkActionHtml,
		pagination: 'remote',
		ajaxSorting: true,
		layout: 'fitDataFill',
		paginationSize: 25,
		paginationSizeSelector: [ 10, 25, 50, 100 ],
		movableColumns: true,
		persistenceMode: true,
		persistentLayout: true,
		persistenceID: 'pb_network_analytics_books',
		columns: [
			{
				title: 'Bulk Action',
				field: '_bulkAction',
				formatter: 'rowSelection',
				titleFormatter: 'rowSelection',
				align: 'center',
				headerSort: false,
				/**
				 * @param e
				 * @param cell
				 */
				cellClick: function ( e, cell ) {
					cell.getRow().toggleSelect();
				},
			},
			{
				title: 'id',
				field: 'id',
				visible: false,
			},
			{
				title: 'deactivated',
				field: 'deactivated',
				visible: false,
				formatter: 'deactivated',
			},
			{
				title: 'Cover',
				field: 'cover',
				formatter: 'image',
				formatterParams: {
					height: '50px',
				},
				headerSort: false,
			},
			{
				title: 'Book Title',
				field: 'bookTitle',
				formatter: 'html',
			},
			{
				title: 'Last Edited',
				field: 'lastEdited',
				formatter: 'datetime',
				formatterParams: {
					inputFormat: 'YYYY-MM-DD hh:mm:ss',
					outputFormat: 'YYYY-MM-DD',
					invalidPlaceholder: 'N/A',
				},
			},
			{
				title: 'Created',
				field: 'created',
				formatter: 'datetime',
				formatterParams: {
					inputFormat: 'YYYY-MM-DD hh:mm:ss',
					outputFormat: 'YYYY-MM-DD',
					invalidPlaceholder: 'N/A',
				},
			},
			{
				title: 'Words',
				field: 'words',
				formatter: 'numberWithCommas',
			},
			{
				title: 'Authors',
				field: 'authors',
			},
			{
				title: 'Readers',
				field: 'readers',
			},
			{
				title: 'Book Admins',
				field: 'users_email',
				headerSort: false,
			},
			{
				title: 'Storage Size',
				field: 'storageSize',
				formatter: 'formatBytes',
			},
			{
				title: 'Language',
				field: 'language',
				formatter: 'uppercase',
			},
			{
				title: 'Subject',
				field: 'subject',
				formatter: 'withBreaks',
			},
			{
				title: 'Theme',
				field: 'theme',
			},
			{
				title: 'License',
				field: 'license',
				formatter: 'licenses',
			},
			{
				title: 'Allows grading',
				field: 'gradingEnabled',
				formatter: 'tickCross',
				align: 'center',
				/**
				 * @param e
				 * @param cell
				 */
				cellClick: function ( e, cell ) {
					if ( PB_Network_Analytics_BookListToken.ajaxGlobalGrade === '1' ) { //bail if globally enabled
						return;
					}
					cell.setValue( ! isTruthy( cell.getValue() ) );
					let row = cell.getRow();
					let val = cell.getValue();
					inlineEditAjax(
						row.getData().id,
						cell.getColumn().getField(),
						val
					);
					if ( ! isTruthy( val ) ) {
						row.getCell( 'gradingEnabled' ).setValue( false );
					}
				},
			},
			{
				title: 'Public',
				field: 'public',
				formatter: 'tickCross',
				align: 'center',
				/**
				 * @param e
				 * @param cell
				 */
				cellClick: function ( e, cell ) {
					cell.setValue( ! isTruthy( cell.getValue() ) ); // flip
					let row = cell.getRow();
					let val = cell.getValue();
					inlineEditAjax(
						row.getData().id,
						cell.getColumn().getField(),
						val
					);
					if ( ! isTruthy( val ) ) {
						// If book is private, it must not be in catalog
						row.getCell( 'inCatalog' ).setValue( false );
					}
				},
			},
			{
				title: 'In Catalog',
				field: 'inCatalog',
				formatter: 'tickCross',
				align: 'center',
				/**
				 * @param e
				 * @param cell
				 */
				cellClick: function ( e, cell ) {
					cell.setValue( ! isTruthy( cell.getValue() ) ); // flip
					let row = cell.getRow();
					let val = cell.getValue();
					inlineEditAjax(
						row.getData().id,
						cell.getColumn().getField(),
						val
					);
					if ( isTruthy( val ) ) {
						// If book is in catalog, it must be public
						row.getCell( 'public' ).setValue( true );
					}
				},
			},
		],
		/**
		 * @param url
		 * @param params
		 * @param response
		 */
		ajaxResponse: function ( url, params, response ) {
			document.getElementById( 'filter-row-count' ).textContent = response.row_count;
			return response; //return the response data to tabulator (you MUST include this bit)
		},
	} );

	if ( PB_Network_Analytics_BookListToken.ajaxGlobalGrade !== '1' && ( PB_Network_Analytics_BookListToken.ajaxIsSuperAdmin === '1' ||  networkAdminAllowedToGrade ) ) {
		table.showColumn( 'gradingEnabled' );
	} else {
		table.hideColumn( 'gradingEnabled' );
	}

	// ------------------------------------------------------------------------
	// Custom Filters
	// ------------------------------------------------------------------------

	filters( table, ajaxParams );

	// ------------------------------------------------------------------------
	// Bulk Actions
	// ------------------------------------------------------------------------

	bulkActions( table, ajaxParams );

} );

jQuery( document ).ready( function ( $ ) {
	$( '#tabs' ).tabs();
} );
