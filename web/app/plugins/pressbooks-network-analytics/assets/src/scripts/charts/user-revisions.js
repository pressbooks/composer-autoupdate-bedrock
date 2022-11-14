import { authorsByTime, buttonChecker, getData, sliceArray, getDataByTime, getCounterLimit, displayAmount, buttonActive, addAriaLabel, gotoLink } from '../utility.js';

let Chart = require( 'chart.js' );
let ChartDataLabels = require( 'chartjs-plugin-datalabels' );
Chart.plugins.unregister( ChartDataLabels );
require( 'chartjs-chart-treemap' );

export async function getMostActiveUsersChart(){

	let usernameLabels = [];
	let userRevisions = [];
	let slicedArray = [];
	let activeUsersChartData = {};
	let splitDataByTime = [];
	let userDisplayCounter = 0;
	let counterLimit = 0;
	let displayNumber = 20;

	// Context for User Revision Chart
	let mostActiveUsersCtx = document.getElementById( 'most-active-users' );

	// Chart Options to be passed to Chart (these stay the same)
	let userRevisionsChartOptions = {
		plugins: {
			datalabels: {
				color: 'black',
				anchor: 'end',
				align: 'end',
			},
		},
		layout: {
			padding: {
				right: 50,
			},
		},
		legend: {
			display: false,
		},
		maintainAspectRatio: false,
		title: {
			display: false,
			text: 'User Revisions',
		},
		tooltips: {
			callbacks: {
			  title: function ( tooltipItems, data ) {
					return data.labels[tooltipItems[0].index]
				},
		  },
		},
		scales: {
			xAxes: [ {
				ticks: {
					beginAtZero: true,
				},
				minBarLength: 10,
			} ],
		},
		events: [ 'click', 'mousemove' ],
		onClick: getBarInfo,

	}

	// The Chart
	let currentChart = new Chart( mostActiveUsersCtx, {
		type: 'horizontalBar',
		plugins: [ ChartDataLabels ],
		data: {
			labels: usernameLabels,
			datasets: [ {
				data: userRevisions,
				backgroundColor: '#BB0028',
				label: 'Number of Revisions',
			},
			],
		},
		options: userRevisionsChartOptions,
	},
	);

	let mostActiveUsers = await getData( PB_Network_AnalyticsToken.mostActiveUsersAjaxUrl ).then( result => {
		return result.activeUsers;
	} )

	function setUserLink( username ) {
		let found = mostActiveUsers.filter( function( data ) {
			return data.username === username;
		} );
		let link = 'admin.php?page=pb_network_analytics_userlist&id=' + found[ 0 ].id;
		gotoLink( link );
	}

	// Make sure getting the username on bar click is possible (it is)
	function getBarInfo( e, array ){
		if ( array[0] ){
			let index = array[0]._index;
			let username = array[0]._chart.tooltip._data.labels[index];
			setUserLink( username );
		}
	}

	// Add Event Listeners to rebuild chart on button click
	document.getElementById( 'week-button' ).addEventListener( 'click', e => timeHandler( e ) )
	document.getElementById( 'month-button' ).addEventListener( 'click', e => timeHandler( e ) )
	document.getElementById( '3month-button' ).addEventListener( 'click', e => timeHandler( e ) )
	document.getElementById( 'year-button' ).addEventListener( 'click', e => timeHandler( e ) )
	document.getElementById( 'next-button' ).addEventListener( 'click', e => nextHandler( e ) )
	document.getElementById( 'previous-button' ).addEventListener( 'click', e => previousHandler( e ) )

	// Function to call on update of info
	async function updateInfo(){
		slicedArray = await sliceArray( splitDataByTime, userDisplayCounter, displayNumber );
		await splitData( slicedArray );
		displayAmount( mostActiveUsers, userDisplayCounter, displayNumber );
		buttonChecker( userDisplayCounter, counterLimit );
		setChart();
	}

	// Default Chart View on load
	counterLimit = getCounterLimit( mostActiveUsers, displayNumber );
	activeUsersChartData = await authorsByTime( mostActiveUsers );
	splitDataByTime = activeUsersChartData.byYear;
	addAriaLabel( mostActiveUsersCtx, 'This chart shows the most active users sorted by number of revisions' );
	await updateInfo();

	// Splits the Array into Data for chart
	// Always the last function to be called before chart rebuild to maintain correct order
	function splitData( data ){
		usernameLabels = data.map( user => {
			return user.username
		} )
		userRevisions = data.map( user => {
			return user.revisionsNumber
		} )
	}

	// Event Handlers
	// This handler is attached to buttons that represent time span for user revisions
	// Rebuilds the chart when clicked
	async function timeHandler( event ){
		userDisplayCounter = 0;
		buttonActive( event );
		splitDataByTime = await getDataByTime( event.target.id, activeUsersChartData );

		await updateInfo();
	}

	// This handler is attached to the "Next X" button
	// Rebuilds the chart when clicked
	async function nextHandler( event ){
		if ( userDisplayCounter < counterLimit ){
			userDisplayCounter += 1;

			await updateInfo();
		}
	}

	// This handler is attached to the "Previous X" button
	// Rebuilds the chart when clicked
	async function previousHandler( event ){
		if ( userDisplayCounter > 0 ){
			userDisplayCounter = userDisplayCounter - 1;

			await updateInfo();
		}
	}

	// Function that destroys chart and builds new one on button click
	function setChart(){
		currentChart.destroy();
		let newChart = new Chart( mostActiveUsersCtx, {
			plugins: [ ChartDataLabels ],
			type: 'horizontalBar',
			data: {
				labels: usernameLabels,
				datasets: [ {
					data: userRevisions,
					backgroundColor: '#BB0028',
					label: 'Number of Revisions',
				},
				],
			},
			options: userRevisionsChartOptions,
		} );
		currentChart = newChart;
	}
}
