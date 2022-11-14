/* global PB_Network_Analytics_BookListToken */
/* eslint-disable no-loop-func */

import { removeElement, emptyArrayOrObject, gotoLink } from './utility.js';

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
	let tabsCounter2 = document.getElementById( 'tabs-2-counter' );
	let tabsCounter3 = document.getElementById( 'tabs-3-counter' );
	let tabsCounter4 = document.getElementById( 'tabs-4-counter' );

	/**
	 * Shared function for Adjust Tabs Counters
	 */
	function adjustTabsCounters() {
		let tabs1 = [
			'akismetActivated',
			'currentLicense',
			'glossaryTerms',
			'h5pActivities',
			'isCloned',
			'isPublic',
			'parsedownPartyActivated',
			'tablepressTables',
			'wpQuicklatexActivated',
		];
		let tabs2 = [
			'bookLanguage',
			'bookSubject',
		];
		let tabs3 = [
			'storageSize',
			'wordCount',
		];
		let tabs4 = [
			'allowsDownloads',
			'currentTheme',
			'exportsByFormat',
			'hasExports',
			'lastEdited',
			'lastExport',
		];

		tabsCounter1.textContent = '0';
		tabsCounter2.textContent = '0';
		tabsCounter3.textContent = '0';
		tabsCounter4.textContent = '0';

		for ( let [ key, value ] of Object.entries( filter ) ) {
			if ( ! emptyArrayOrObject( value ) ) {
				if ( tabs1.includes( key ) ) filterCountIncrement( tabsCounter1 );
				else if ( tabs2.includes( key ) ) filterCountIncrement( tabsCounter2 );
				else if ( tabs3.includes( key ) ) filterCountIncrement( tabsCounter3 );
				else if ( tabs4.includes( key ) ) filterCountIncrement( tabsCounter4 );
			}
		}
	}

	// Is Public / Is Private

	let isPublic = document.querySelectorAll( 'input[name="is-public"]' );
	for ( let i = 0, max = isPublic.length; i < max; i++ ) {
		// arrow functions do not have their own this context
		/**
		 *
		 */
		isPublic[ i ].onclick = function () {
			if ( filter.isPublic === undefined || filter.isPublic !== this.value ) {
				this.checked = true;
				filter.isPublic = this.value;
			} else {
				this.checked = false;
				delete filter.isPublic;
			}
			adjustTabsCounters();
		};
	}

	// Is Original / Is Cloned

	let isCloned = document.querySelectorAll( 'input[name="is-cloned"]' );
	for ( let i = 0, max = isCloned.length; i < max; i++ ) {
		// arrow functions do not have their own this context
		/**
		 *
		 */
		isCloned[ i ].onclick = function () {
			if ( filter.isCloned === undefined || filter.isCloned !== this.value ) {
				this.checked = true;
				filter.isCloned = this.value;
			} else {
				this.checked = false;
				delete filter.isCloned;
			}
			adjustTabsCounters();
		};
	}

	// Akismet Activated

	let akismetActivated = document.getElementById( 'akismet-activated' );
	akismetActivated.addEventListener( 'change', event => {
		event.preventDefault();
		if ( akismetActivated.checked ) {
			filter.akismetActivated = 1;
		} else {
			delete filter.akismetActivated;
		}
		adjustTabsCounters();
	} );

	// Parsedown Party Activated

	let parsedownPartyActivated = document.getElementById( 'parsedown-party-activated' );
	parsedownPartyActivated.addEventListener( 'change', event => {
		event.preventDefault();
		if ( parsedownPartyActivated.checked ) {
			filter.parsedownPartyActivated = 1;
		} else {
			delete filter.parsedownPartyActivated;
		}
		adjustTabsCounters();
	} );

	// WP QuickLaTeX Activated

	let wpQuicklatexActivated = document.getElementById( 'wp-quicklatex-activated' );
	wpQuicklatexActivated.addEventListener( 'change', event => {
		event.preventDefault();
		if ( wpQuicklatexActivated.checked ) {
			filter.wpQuicklatexActivated = 1;
		} else {
			delete filter.wpQuicklatexActivated;
		}
		adjustTabsCounters();
	} );

	// Glossary Terms

	/**
	 * Shared function for Glossary Terms
	 */
	function glossaryTermsAction() {
		filter.glossaryTerms = glossaryTermsNumber.value;
		filter.glossaryTermsSymbol = glossaryTermsDropdown.value;
	}

	let glossaryTermsDropdown = document.getElementById( 'glossary-terms-dropdown' );
	let glossaryTermsNumber = document.getElementById( 'glossary-terms-number' );
	glossaryTermsDropdown.addEventListener( 'change', event => {
		event.preventDefault();
		glossaryTermsAction();
		adjustTabsCounters();
	} );
	glossaryTermsNumber.addEventListener( 'change', event => {
		event.preventDefault();
		glossaryTermsAction();
		adjustTabsCounters();
	} );

	// H5P Activities

	/**
	 * Shared function for H5P Activities
	 */
	function h5pActivitiesAction() {
		filter.h5pActivities = h5pActivitiesNumber.value;
		filter.h5pActivitiesSymbol = h5pActivitiesDropdown.value;
	}

	let h5pActivitiesDropdown = document.getElementById( 'h5p-activities-dropdown' );
	let h5pActivitiesNumber = document.getElementById( 'h5p-activities-number' );
	h5pActivitiesDropdown.addEventListener( 'change', event => {
		event.preventDefault();
		h5pActivitiesAction();
		adjustTabsCounters();
	} );
	h5pActivitiesNumber.addEventListener( 'change', event => {
		event.preventDefault();
		h5pActivitiesAction();
		adjustTabsCounters();
	} );

	// Tablepress Tables

	/**
	 * Shared function for Tablepress Tables
	 */
	function tablepressTablesAction() {
		filter.tablepressTables = tablepressTablesNumber.value;
		filter.tablepressTablesSymbol = tablepressTablesDropdown.value;
	}

	let tablepressTablesDropdown = document.getElementById( 'tablepress-tables-dropdown' );
	let tablepressTablesNumber = document.getElementById( 'tablepress-tables-number' );
	tablepressTablesDropdown.addEventListener( 'change', event => {
		event.preventDefault();
		tablepressTablesAction();
		adjustTabsCounters();
	} );
	tablepressTablesNumber.addEventListener( 'change', event => {
		event.preventDefault();
		tablepressTablesAction();
		adjustTabsCounters();
	} );

	// Word Count

	/**
	 * Shared function for Word Count
	 */
	function wordCountAction() {
		filter.wordCount = wordCountNumber.value;
		filter.wordCountSymbol = wordCountDropdown.value;
	}

	let wordCountDropdown = document.getElementById( 'word-count-dropdown' );
	let wordCountNumber = document.getElementById( 'word-count-number' );
	wordCountDropdown.addEventListener( 'change', event => {
		event.preventDefault();
		wordCountAction();
		adjustTabsCounters();
	} );
	wordCountNumber.addEventListener( 'change', event => {
		event.preventDefault();
		wordCountAction();
		adjustTabsCounters();
	} );

	// Book Storage

	/**
	 * Shared function for Book Storage
	 */
	function bookStorageAction() {
		filter.storageSize = bookStorageNumber.value;
		filter.storageSizeSymbol = bookStorageDropdown.value;
	}

	let bookStorageDropdown = document.getElementById( 'book-storage-dropdown' );
	let bookStorageNumber = document.getElementById( 'book-storage-number' );
	bookStorageDropdown.addEventListener( 'change', event => {
		event.preventDefault();
		bookStorageAction();
		adjustTabsCounters();
	} );
	bookStorageNumber.addEventListener( 'change', event => {
		event.preventDefault();
		bookStorageAction();
		adjustTabsCounters();
	} );

	// Licenses

	let currentLicense = document.querySelectorAll( 'input[name="currentLicense[]"]' );
	for ( let i = 0; i < currentLicense.length; i++ ) {
		// arrow functions do not have their own this context
		currentLicense[ i ].addEventListener( 'change', function ( event ) {
			if ( filter.currentLicense === undefined ) {
				filter.currentLicense = [];
			}
			if ( this.checked ) {
				filter.currentLicense.push( this.value );
			} else {
				removeElement( filter.currentLicense, this.value );
			}
			adjustTabsCounters();
		} );
	}

	// Themes

	let currentTheme = document.querySelectorAll( 'input[name="currentTheme[]"]' );
	for ( let i = 0; i < currentTheme.length; i++ ) {
		// arrow functions do not have their own this context
		currentTheme[ i ].addEventListener( 'change', function ( event ) {
			if ( filter.currentTheme === undefined ) {
				filter.currentTheme = [];
			}
			if ( this.checked ) {
				filter.currentTheme.push( this.value );
			} else {
				removeElement( filter.currentTheme, this.value );
			}
			adjustTabsCounters();
		} );
	}

	// Languages

	let bookLanguage = document.querySelectorAll( 'input[name="bookLanguage[]"]' );
	for ( let i = 0; i < bookLanguage.length; i++ ) {
		// arrow functions do not have their own this context
		bookLanguage[ i ].addEventListener( 'change', function ( event ) {
			if ( filter.bookLanguage === undefined ) {
				filter.bookLanguage = [];
			}
			if ( this.checked ) {
				filter.bookLanguage.push( this.value );
			} else {
				removeElement( filter.bookLanguage, this.value );
			}
			adjustTabsCounters();
		} );
	}

	// Subject

	let bookSubject = document.querySelectorAll( 'input[name="bookSubject[]"]' );
	for ( let i = 0; i < bookSubject.length; i++ ) {
		// arrow functions do not have their own this context
		bookSubject[ i ].addEventListener( 'change', function ( event ) {
			if ( filter.bookSubject === undefined ) {
				filter.bookSubject = [];
			}
			if ( this.checked ) {
				filter.bookSubject.push( this.value );
			} else {
				removeElement( filter.bookSubject, this.value );
			}
			adjustTabsCounters();
		} );
	}

	// Has Produced Exports

	let hasExports = document.getElementById( 'has-exports' );
	hasExports.addEventListener( 'change', event => {
		event.preventDefault();
		if ( hasExports.checked ) {
			filter.hasExports = 1;
		} else {
			delete filter.hasExports;
		}
		adjustTabsCounters();
	} );

	// Allows Downloads

	let allowsDownloads = document.getElementById( 'allows-downloads' );
	allowsDownloads.addEventListener( 'change', event => {
		event.preventDefault();
		if ( allowsDownloads.checked ) {
			filter.allowsDownloads = 1;
		} else {
			delete filter.allowsDownloads;
		}
		adjustTabsCounters();
	} );

	// Exports By Format

	let exportsByFormat = document.querySelectorAll( 'input[name="exportsByFormat[]"]' );
	for ( let i = 0; i < exportsByFormat.length; i++ ) {
		// arrow functions do not have their own this context
		exportsByFormat[ i ].addEventListener( 'change', function ( event ) {
			if ( filter.exportsByFormat === undefined ) {
				filter.exportsByFormat = [];
			}
			if ( this.checked ) {
				filter.exportsByFormat.push( this.value );
			} else {
				removeElement( filter.exportsByFormat, this.value );
			}
			adjustTabsCounters();
		} );
	}

	// Exports Since

	let lastExport = document.getElementById( 'last-export-date' );
	lastExport.addEventListener( 'change', event => {
		event.preventDefault();
		filter.lastExport = lastExport.value;
		adjustTabsCounters();
	} );

	// Edited Since

	let lastEdited = document.getElementById( 'last-edited-date' );
	lastEdited.addEventListener( 'change', event => {
		event.preventDefault();
		filter.lastEdited = lastEdited.value;
		adjustTabsCounters();
	} );

	// Apply Filters

	let filterApply = document.getElementById( 'filter-apply' );
	filterApply.addEventListener( 'click', event => {
		event.preventDefault();
		document.getElementById( 'search-input' ).value = ''; // Clear search box to avoid any confusion that it affects filters
		table.setData( PB_Network_Analytics_BookListToken.ajaxUrl, {
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
			action: PB_Network_Analytics_BookListToken.ajaxActionCsv,
			_wpnonce: PB_Network_Analytics_BookListToken.ajaxNonce,
		};
		let url = PB_Network_Analytics_BookListToken.ajaxUrl + '?' + jQuery.param( {
			...ajaxParamsCsv,
			...filter,
		} );
		gotoLink( url );
	} );

	// Clear Filters

	/**
	 * Shared function for Clear Filters
	 */
	function clearFilters() {
		// Reset global filter variable
		filter = {};
		// Reset JavaScript Variables
		akismetActivated.checked = false;
		allowsDownloads.checked = false;
		bookStorageDropdown.value = '>';
		bookStorageNumber.value = '';
		glossaryTermsDropdown.value = '>';
		glossaryTermsNumber.value = '';
		h5pActivitiesDropdown.value = '>';
		h5pActivitiesNumber.value = '';
		hasExports.checked = false;
		lastEdited.value = '';
		lastExport.value = '';
		parsedownPartyActivated.checked = false;
		tablepressTablesDropdown.value = '>';
		tablepressTablesNumber.value = '';
		wordCountDropdown.value = '>';
		wordCountNumber.value = '';
		wpQuicklatexActivated.checked = false;

		for ( let i = 0; i < bookLanguage.length; i++ ) {
			bookLanguage[ i ].checked = false;
		}
		for ( let i = 0; i < bookSubject.length; i++ ) {
			bookSubject[ i ].checked = false;
		}
		for ( let i = 0; i < currentLicense.length; i++ ) {
			currentLicense[ i ].checked = false;
		}
		for ( let i = 0; i < currentTheme.length; i++ ) {
			currentTheme[ i ].checked = false;
		}
		for ( let i = 0; i < exportsByFormat.length; i++ ) {
			exportsByFormat[ i ].checked = false;
		}
		for ( let i = 0; i < isPublic.length; i++ ) {
			isPublic[ i ].checked = false;
		}
		for ( let i = 0; i < isCloned.length; i++ ) {
			isCloned[ i ].checked = false;
		}
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
		table.setData( PB_Network_Analytics_BookListToken.ajaxUrl, ajaxParams );
	} );

	// Fulltext Search

	let searchApply = document.getElementById( 'search-apply' );
	searchApply.addEventListener( 'click', event => {
		event.preventDefault();
		clearFilters();
		table.setData( PB_Network_Analytics_BookListToken.ajaxUrl, {
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
