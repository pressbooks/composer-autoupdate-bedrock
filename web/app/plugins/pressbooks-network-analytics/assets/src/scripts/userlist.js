/* global PB_Network_Analytics_UserListToken, Tabulator */

import { bulkActions } from './userlist-actions';
import { filters } from './userlist-filters';
const { __ } = wp.i18n;

window.addEventListener( 'load', function () {

	// ------------------------------------------------------------------------
	// Tabulator
	// ------------------------------------------------------------------------

	// Tabulator ajax params, @see http://tabulator.info/docs/4.4/data#ajax

	let ajaxParams = {
		action: PB_Network_Analytics_UserListToken.ajaxAction,
		_wpnonce: PB_Network_Analytics_UserListToken.ajaxNonce,
	};

	// Table

	let bulkActionHtml = '<div class=\'alignleft actions bulkactions\'>' +
		`<label for='bulk-action-selector' class='screen-reader-text'>${ __( 'Select bulk action', 'pressbooks-network-analytics' ) }</label>` +
		'<select name=\'action\' id=\'bulk-action-selector\'>' +
		`<option value=''>${ __( 'Bulk Actions', 'pressbooks-network-analytics' ) }</option>` +
		`<option value='delete_1'>${ __( 'Delete', 'pressbooks-network-analytics' ) }</option>` +
		'</select> ' +
		`<button id='doaction' class='button action'>${ __( 'Apply', 'pressbooks-network-analytics' ) }</button>` +
		'</div>';

	let table = new Tabulator( '#userlist', {
		ajaxURL: PB_Network_Analytics_UserListToken.ajaxUrl,
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
		persistenceID: 'pb_network_analytics_users',
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
				title: 'Username',
				field: 'username',
				formatter: 'html',
			},
			{
				title: 'Name',
				field: 'name',
			},
			{
				title: 'Email',
				field: 'email',
			},
			{
				title: 'Registered',
				field: 'registered',
				formatter: 'datetime',
				formatterParams: {
					inputFormat: 'YYYY-MM-DD hh:mm:ss',
					outputFormat: 'YYYY-MM-DD',
					invalidPlaceholder: 'N/A',
				},
			},
			{
				title: 'Last Login',
				field: 'lastLogin',
				formatter: 'datetime',
				formatterParams: {
					inputFormat: 'YYYY-MM-DD hh:mm:ss',
					outputFormat: 'YYYY-MM-DD',
					invalidPlaceholder: 'N/A',
				},
			},
			{
				title: 'Belongs to X Books',
				field: 'totalBooks',
				formatter: 'link',
				formatterParams: {
					urlPrefix: 'admin.php?page=pb_network_analytics_userlist&id=',
					urlField: 'id',
				},
			},
			{
				title: 'As Admin',
				field: 'administrator',
			},
			{
				title: 'As Editor',
				field: 'editor',
			},
			{
				title: 'As Author',
				field: 'author',
			},
			{
				title: 'As Collaborator',
				field: 'contributor',
			},
			{
				title: 'As Subscriber',
				field: 'subscriber',
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
