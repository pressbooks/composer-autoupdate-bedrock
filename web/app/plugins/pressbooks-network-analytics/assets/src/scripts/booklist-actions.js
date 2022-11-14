/* global PB_Network_Analytics_BookListToken */

import { post, emptyArrayOrObject } from './utility.js';

/**
 * Inline Edit Ajax
 *
 * @param {number} bookId
 * @param {string} key
 * @param {string} val
 */
export function inlineEditAjax( bookId, key, val ) {
	jQuery.ajax( {
		url: PB_Network_Analytics_BookListToken.ajaxUrl,
		type: 'POST',
		data: {
			action: PB_Network_Analytics_BookListToken.ajaxActionInlineEdit,
			book_id: bookId,
			key: key,
			val: val,
			_ajax_nonce: PB_Network_Analytics_BookListToken.ajaxNonce,
		},
	} );
}

/**
 *
 * @param {Tabulator} table
 * @param {object} ajaxParams Tabulator ajax params, @see http://tabulator.info/docs/4.4/data#ajax
 */
export function bulkActions( table, ajaxParams ) {

	// Bulk Action

	let filterApply = document.getElementById( 'doaction' );
	filterApply.addEventListener( 'click', event => {
		let bulkAction = document.getElementById( 'bulk-action-selector' ).value;
		if ( bulkAction ) {
			let selectedData = table.getSelectedData();
			let bookIds = selectedData.map( a => a.id );
			if ( ! emptyArrayOrObject( bookIds ) ) {
				let data = {
					action: PB_Network_Analytics_BookListToken.ajaxActionBulk,
					book_ids: bookIds,
					do: bulkAction,
					_wpnonce: PB_Network_Analytics_BookListToken.ajaxNonce,
				};
				post( PB_Network_Analytics_BookListToken.ajaxUrl, data );
			}
		}
	} );
}
