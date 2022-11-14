// Function that retrieves data using JS Fetch
/**
 * @param url
 */
export async function getData( url ){
	let response = await fetch( url );
	let data = await response.json();
	return data;
}

// Split up and return values for users
/**
 * @param data
 */
export async function returnValues( data ){
	let dateLbl = data.map( data => {
		return data.dateLabel;
	}  );
	let totUsers = data.map( data => {
		return data.totalUsers;
	}  );
	let totSubs = data.map( data => {
		return data.totalSubscribers;
	}   );
	let totContrib = data.map( data => {
		return data.totalContributors;
	}   );

	return {
		dateLabels: dateLbl,
		totalUsers: totUsers,
		totalSubscribers: totSubs,
		totalContributors: totContrib,
	};
}

// Split up and return values for books
/**
 * @param data
 */
export function returnBookValues( data ){

	let dateLbl = data.map( data => {
		return data.dateLabel;
	}  );
	let totBooks = data.map( data => {
		return data.totalBooks;
	}  );
	let totCloned = data.map( data => {
		return data.totalClonedBooks;
	}   );
	let totPub = data.map( data => {
		return data.totalPublicBooks;
	}   );
	let totPriv = data.map( data => {
		return data.totalPrivateBooks;
	}   );

	return {
		dateLabels: dateLbl,
		totalBooks: totBooks,
		totalPrivate: totPriv,
		totalPublic: totPub,
		totalCloned: totCloned,
	};
}

// This Function slices array of data based on whether the userDisplayCounter is true or false
// Allows the `Previous X`/`Next X` buttons to return correct data
// Eventually this function will slice the array based on what number the userDisplayCounter is on
/**
 * @param data
 * @param counter
 * @param displayNumber
 */
export function sliceArray( data, counter, displayNumber ){
	let a = 0;
	let b = displayNumber;
	let c = counter * displayNumber;

	if ( counter === 0 ){
		return data.slice( a, b );
	} else {
		a += c;
		b += c;
		return data.slice( a, b );
	}
}

// Gets counter limit based on the length of data and the amount of users that are displayed
/**
 * @param data
 * @param displayNumber
 */
export function getCounterLimit( data, displayNumber ){
	return Math.floor( data.length / displayNumber );
}

// Function that gets data based on what time span button is clicked
/**
 * @param time
 * @param dataObj
 */
export function getDataByTime( time, dataObj ){
	if ( time === 'week-button' ){
		return dataObj.byWeek;
	}
	if ( time === 'month-button' ){
		return  dataObj.byMonth;
	}
	if ( time === '3month-button' ){
		return dataObj.byThreeMonths;
	}
	if ( time === 'year-button' ){
		return dataObj.byYear;
	}
}

// Filters data by time
/**
 * @param data
 * @param time
 */
function filterByTime( data, time ){
	return data.map( user => {
		return {
			username: user.username,
			revisionsNumber: user[time],
		};
	} );
}

// Sorts User revision data from most revisions to fewest revisions
/**
 * @param data
 * @param key
 */
export function sortUsers( data, key ){
	return data.sort( ( a, b ) => {
		return b[key] - a[key];
	} );
}

// Make sure that `next` and `previous` buttons are activated or disabled
// based on the position of the array
/**
 * @param counterValue
 * @param counterLimit
 */
export function buttonChecker( counterValue, counterLimit ){
	if ( counterValue === 0 && counterLimit > 0 ){
		document.getElementById( 'previous-button' ).disabled = true;
		document.getElementById( 'next-button' ).disabled = false;
	} else if ( counterLimit === 0 && counterValue === 0 ){
		document.getElementById( 'previous-button' ).disabled = true;
		document.getElementById( 'next-button' ).disabled = true;
	} else if ( counterValue === counterLimit && counterLimit !== 0 ){
		document.getElementById( 'previous-button' ).disabled = false;
		document.getElementById( 'next-button' ).disabled = true;
	} else {
		document.getElementById( 'previous-button' ).disabled = false;
		document.getElementById( 'next-button' ).disabled = false;
	}
}

// Function that checks what data set is displayed, and
// sets background color on button that corresponds with selected data.
/**
 * @param event
 */
export function buttonActive( event ){
	let id = event.target.id;
	let weekBtn = document.getElementById( 'week-button' );
	let monthBtn = document.getElementById( 'month-button' );
	let threeMonthBtn = document.getElementById( '3month-button' );
	let yearBtn = document.getElementById( 'year-button' );
	if ( id === weekBtn.id ){
		weekBtn.classList.add( 'button-active' );
		monthBtn.classList.remove( 'button-active' );
		threeMonthBtn.classList.remove( 'button-active' );
		yearBtn.classList.remove( 'button-active' );
	}
	if ( id === monthBtn.id ){
		weekBtn.classList.remove( 'button-active' );
		monthBtn.classList.add( 'button-active' );
		threeMonthBtn.classList.remove( 'button-active' );
		yearBtn.classList.remove( 'button-active' );
	}
	if ( id === threeMonthBtn.id ){
		weekBtn.classList.remove( 'button-active' );
		monthBtn.classList.remove( 'button-active' );
		threeMonthBtn.classList.add( 'button-active' );
		yearBtn.classList.remove( 'button-active' );
	}
	if ( id === yearBtn.id ){
		weekBtn.classList.remove( 'button-active' );
		monthBtn.classList.remove( 'button-active' );
		threeMonthBtn.classList.remove( 'button-active' );
		yearBtn.classList.add( 'button-active' );
	}
}

// Function that displays amount of users and the position in array of which viewers are being displayed in chart
/**
 * @param data
 * @param counter
 * @param displayNumber
 */
export function displayAmount( data, counter, displayNumber ){
	let counterDiv = document.getElementById( 'user-amount-display' );
	let displayString = '';
	let dataLength = data.length;
	let a = 1;
	let b = displayNumber;
	if ( counter > 0 ){
		a += counter * displayNumber;
		b += counter * displayNumber;

		if ( b > dataLength ){
			displayString = a.toString() + ' - ' + dataLength.toString() + ' of ' + dataLength.toString();
			counterDiv.innerHTML= displayString;
		} else {
			displayString = a.toString() + ' - ' + b.toString() + ' of ' + dataLength.toString();
			counterDiv.innerHTML= displayString;
		}

	} else if ( displayNumber >= dataLength ){
		displayString = a.toString() + ' - ' + dataLength.toString() + ' of ' + dataLength.toString();
		counterDiv.innerHTML= displayString;
	} else {
		displayString = a.toString() + ' - ' + b.toString() + ' of ' + dataLength.toString();
		counterDiv.innerHTML= displayString;
	}
}

// Function that splits revisions into time spans, as well as sorts each from most revisions to fewest
/**
 * @param data
 */
export function authorsByTime( data ){
	let byWeek = sortUsers( filterByTime( data, 'revisionsWeek' ), 'revisionsNumber' );
	let byMonth = sortUsers( filterByTime( data, 'revisionsMonth' ), 'revisionsNumber' );
	let byThreeMonths = sortUsers( filterByTime( data, 'revisionsThreeMonths' ), 'revisionsNumber' );
	let byYear = sortUsers( filterByTime( data, 'revisionsYear' ), 'revisionsNumber' );

	return {
		byWeek: byWeek,
		byMonth: byMonth,
		byThreeMonths: byThreeMonths,
		byYear: byYear,
	};
}

// Function that converts bytes to KB, MB, GB...
/**
 * @param bytes
 * @param decimals
 */
export function formatBytes( bytes, decimals = 2 ) {
	if ( bytes === 0 || bytes === '0' ) return '0 Bytes';
	const k = 1024;
	const dm = decimals < 0 ? 0 : decimals;
	const sizes = [ 'Bytes', 'KB', 'MB', 'GB', 'TB' ];
	const i = Math.floor( Math.log( bytes ) / Math.log( k ) );
	return parseFloat( ( bytes / Math.pow( k, i ) ).toFixed( dm ) ) + ' ' + sizes[ i ];
}

/**
 * @param context
 * @param string
 */
export function addAriaLabel( context, string ) {
	context.setAttribute( 'aria-label', string );
}

/**
 * @param link
 */
export function openNewTab( link ){
	window.open( link );
}

/**
 * @param link
 */
export function gotoLink( link ) {
	window.location = link;
}

/**
 * @param e
 * @param chart
 * @param data
 * @param start
 * @param end
 */
export async function addChartData( e, chart, data, start, end ){

	let target = e.target.id;

	await removeChartData( chart );
	if ( target === 'apply-data-button' || target === 'reset-data-button' ) {
		let slicedTotal = data.totalBooks.slice( start, end );
		let slicedCloned = data.totalCloned.slice( start, end );
		let slicedPublic = data.totalPublic.slice( start, end );
		let slicedPrivate = data.totalPrivate.slice( start, end );
		let slicedLabels = data.dateLabels.slice( start, end );

		chart.data.labels = slicedLabels;

		chart.data.datasets.forEach( set => {
			if ( set.label === 'Total Books' ){
				set.data = slicedTotal;
			}
			if ( set.label === 'Cloned Books' ){
				set.data = slicedCloned;
			}
			if ( set.label === 'Public Books' ){
				set.data = slicedPublic;
			}
			if ( set.label === 'Private Books' ){
				set.data = slicedPrivate;
			}
		} );
		chart.update();
	}
	if ( target === 'apply-data-button-users' || target === 'reset-data-button-users' ) {
		let slicedTotal = data.totalUsers.slice( start, end );
		let slicedSubs = data.totalSubscribers.slice( start, end );
		let slicedContributors = data.totalContributors.slice( start, end );
		let slicedLabels = data.dateLabels.slice( start, end );

		chart.data.labels = slicedLabels;

		chart.data.datasets.forEach( set => {
			if ( set.label === 'Total Users' ){
				set.data = slicedTotal;
			}
			if ( set.label === 'Total Subscribers' ){
				set.data = slicedSubs;
			}
			if ( set.label === 'Total Collaborators' ){
				set.data = slicedContributors;
			}
		} );
		chart.update();
	}

}

/**
 * @param chart
 */
export function removeChartData( chart ){
	let chartlabels = [];
	chart.data.labels = chartlabels;
	chart.data.datasets.forEach( set => {
		set.data = [];
	} );
	chart.update();
}

/**
 * @param data
 */
export function getDefaultInputValues( data ){

	let begin = data[0].date.substring( 0, 7 );
	let end = data[data.length - 1].date.substring( 0, 7 );
	return [ begin, end ];
}

/**
 * @param array
 * @param inputBegin
 * @param inputEnd
 */
export function setInputValues( array, inputBegin, inputEnd ){
	let beginDate = array[0];
	let endDate = array[1];
	inputBegin.value = beginDate;
	inputEnd.value = endDate;
}

/**
 * @param array
 * @param inputBegin
 * @param inputEnd
 */
export function setMinMaxInputValues( array, inputBegin, inputEnd ){
	let beginDate = array[0];
	let endDate = array[1];
	inputBegin.min = beginDate;
	inputBegin.setAttribute( 'max', endDate );
	inputEnd.setAttribute( 'min', beginDate );
	inputEnd.setAttribute( 'max', endDate );
}

/**
 * @param data
 * @param startDate
 * @param endDate
 */
export async function getSliceByDate( data, startDate, endDate ){

	let indexStart =  await data.findIndex( date => {
		return date.date.substring( 0, 7 ) === startDate;
	} );
	let indexEnd =  await data.findIndex( date => {
		return date.date.substring( 0, 7 ) === endDate;
	} );

	return [ indexStart, indexEnd + 1 ];

}

// Function to remove a particular element from an array
/**
 * @param array
 * @param elem
 */
export function removeElement( array, elem ) {
	let index = array.indexOf( elem );
	if ( index > -1 ) {
		array.splice( index, 1 );
	}
}

// Function to check if an array or object is empty
/**
 * @param mixedVar
 */
export function emptyArrayOrObject( mixedVar ) {

	let undef;
	let key;
	let i;
	let len;
	let emptyValues = [ undef, null, '' ];

	for ( i = 0, len = emptyValues.length; i < len; i++ ) {
		if ( mixedVar === emptyValues[ i ] ) {
			return true;
		}
	}

	if ( typeof mixedVar === 'object' ) {
		for ( key in mixedVar ) {
			if ( Object.prototype.hasOwnProperty.call( mixedVar, key ) ) {
				return false;
			}
		}
		return true;
	}

	return false;
}

// Function to check if a value is truthy
/**
 * @param val
 */
export function isTruthy( val ) {
	if ( val === false || val === 0 || val === '0' || val === '' || val === null || val === undefined ) {
		return false;
	} else {
		return true;
	}
}

/**
 * Sends a request to the specified url from a form. this will change the window location.
 *
 * @param {string} path the path to send the post request to
 * @param {object} params the paramiters to add to the url
 * @param {string} [method=post] the method to use on the form
 */
export function post( path, params, method = 'post' ) {

	// The rest of this code assumes you are not using a library.
	// It can be made less wordy if you use one.
	const form = document.createElement( 'form' );
	form.method = method;
	form.action = path;

	for ( const key in params ) {
		if ( Object.prototype.hasOwnProperty.call( params, key ) ) {
			const hiddenField = document.createElement( 'input' );
			hiddenField.type = 'hidden';
			hiddenField.name = key;
			hiddenField.value = params[ key ];

			form.appendChild( hiddenField );
		}
	}

	document.body.appendChild( form );
	form.submit();
}
