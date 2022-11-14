/* global PB_Network_Analytics_UserListToken */

import { post, emptyArrayOrObject } from './utility.js';

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
			let userIds = selectedData.map( a => a.id );
			if ( ! emptyArrayOrObject( userIds ) ) {
				let data = {
					action: PB_Network_Analytics_UserListToken.ajaxActionBulk,
					user_ids: userIds,
					do: bulkAction,
					_wpnonce: PB_Network_Analytics_UserListToken.ajaxNonce,
				};
				post( PB_Network_Analytics_UserListToken.ajaxUrl, data );
			}
		}
	} );
}
