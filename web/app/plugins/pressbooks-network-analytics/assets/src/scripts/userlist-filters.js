/* global PB_Network_Analytics_UserListToken */

import { emptyArrayOrObject, gotoLink } from './utility.js';

/**
 *
 * @param {HTMLElement} span
 */
function filterCountIncrement( span ) {
	let i = span.textContent;
	i = Number( i );
	i++;
	span.textContent = i.toString();
}

/**
 *
 * @param {Tabulator} table
 * @param {object} ajaxParams Tabulator ajax params, @see http://tabulator.info/docs/4.4/data#ajax
 */
export function filters( table, ajaxParams ) {

	let filter = {};
	let tabsCounter1 = document.getElementById( 'tabs-1-counter' );

	/**
	 * Shared function for Adjust Tabs Counters
	 */
	function adjustTabsCounters() {
		let tabs1 = [
			'addedSince',
			'hasRoleAbove',
			'lastLoggedInAfter',
			'lastLoggedInBefore',
			'numberOfBooks',
		];

		tabsCounter1.textContent = '0';

		for ( let [ key, value ] of Object.entries( filter ) ) {
			if ( ! emptyArrayOrObject( value ) ) {
				if ( tabs1.includes( key ) ) filterCountIncrement( tabsCounter1 );
			}
		}
	}

	// Added Since

	let addedSinceDate = document.getElementById( 'added-since-date' );
	addedSinceDate.addEventListener( 'change', event => {
		event.preventDefault();
		filter.addedSince = addedSinceDate.value;
		adjustTabsCounters();
	} );

	// Last Logged In

	/**
	 * Shared function for Last Logged In
	 */
	function lastLoggedInAction() {
		if ( beforeAfterDropdown.value === 'before' ) {
			delete filter.lastLoggedInAfter;
			filter.lastLoggedInBefore = beforeAfterDate.value;
		} else {
			delete filter.lastLoggedInBefore;
			filter.lastLoggedInAfter = beforeAfterDate.value;
		}
	}

	let beforeAfterDate = document.getElementById( 'before-after-date' );
	let beforeAfterDropdown = document.getElementById( 'before-after-dropdown' );
	beforeAfterDate.addEventListener( 'change', event => {
		event.preventDefault();
		lastLoggedInAction();
		adjustTabsCounters();
	} );
	beforeAfterDropdown.addEventListener( 'change', event => {
		event.preventDefault();
		lastLoggedInAction();
		adjustTabsCounters();
	} );

	// Is Role In X Number Of Books

	/**
	 * Shared function for Is Role In X Number Of Books
	 */
	function isRoleAction() {
		filter.isRole = isRoleDropdown.value === 'collaborator' ? 'contributor' : isRoleDropdown.value;
		filter.numberOfBooks = isRoleNumber.value;
	}

	let isRoleDropdown = document.getElementById( 'is-role-dropdown' );
	let isRoleNumber = document.getElementById( 'is-role-number' );
	isRoleDropdown.addEventListener( 'change', event => {
		event.preventDefault();
		isRoleAction();
		adjustTabsCounters();
	} );
	isRoleNumber.addEventListener( 'change', event => {
		event.preventDefault();
		isRoleAction();
		adjustTabsCounters();
	} );

	// Has Role Above

	let hasRoleAboveDropdown = document.getElementById( 'has-role-above-dropdown' );
	hasRoleAboveDropdown.addEventListener( 'change', event => {
		event.preventDefault();
		filter.hasRoleAbove = hasRoleAboveDropdown.value;
		adjustTabsCounters();
	} );

	// Apply Filters

	let filterApply = document.getElementById( 'filter-apply' );
	filterApply.addEventListener( 'click', event => {
		event.preventDefault();
		document.getElementById( 'search-input' ).value = ''; // Clear search box to avoid any confusion that it affects filters
		table.setData( PB_Network_Analytics_UserListToken.ajaxUrl, {
			...ajaxParams,
			...filter,
		} );
	} );

	// Apply Filters & Download CSV

	let filterCsvApply = document.getElementById( 'filter-csv-apply' );
	filterCsvApply.addEventListener( 'click', event => {
		event.preventDefault();
		document.getElementById( 'search-input' ).value = ''; // Clear search box to avoid any confusion that it affects filters
		let ajaxParamsCsv = {
			action: PB_Network_Analytics_UserListToken.ajaxActionCsv,
			_wpnonce: PB_Network_Analytics_UserListToken.ajaxNonce,
		};
		let url = PB_Network_Analytics_UserListToken.ajaxUrl + '?' + jQuery.param( {
			...ajaxParamsCsv,
			...filter,
		} );
		gotoLink( url );
	} );

	// Clear Filters

	/**
	 * Shared function for Clear filters
	 */
	function clearFilters() {
		// Reset global filter variable
		filter = {};
		// Reset JavaScript Variables
		addedSinceDate.value = '';
		beforeAfterDate.value = '';
		beforeAfterDropdown.value = 'before';
		isRoleDropdown.value = '';
		isRoleNumber.value = '';
		hasRoleAboveDropdown.value = '';
		adjustTabsCounters();
	}

	let filterClear = document.getElementById( 'filter-clear' );
	filterClear.addEventListener( 'click', event => {
		event.preventDefault();
		// Clear search box to avoid any confusion that it affects filters
		document.getElementById( 'search-input' ).value = '';
		// Reset input boxes
		clearFilters();
		// Reset Tabulator
		table.setData( PB_Network_Analytics_UserListToken.ajaxUrl, ajaxParams );
	} );

	// Fulltext Search

	let searchApply = document.getElementById( 'search-apply' );
	searchApply.addEventListener( 'click', event => {
		event.preventDefault();
		clearFilters();
		table.setData( PB_Network_Analytics_UserListToken.ajaxUrl, {
			...ajaxParams,
			...{ searchInput: document.getElementById( 'search-input' ).value },
		} );
	} );
	document.getElementById( 'search-input' ).addEventListener( 'keyup', event => {
		event.preventDefault();
		if ( event.key === 'Enter' ) {
			searchApply.click();
		}
	} );

}
