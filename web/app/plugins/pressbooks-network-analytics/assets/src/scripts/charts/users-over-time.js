import { getData, returnValues, addAriaLabel, getDefaultInputValues, setMinMaxInputValues, getSliceByDate, setInputValues, addChartData } from '../utility.js';

let Chart = require( 'chart.js' );

export async function getUsersOverTimeChart(){
	let usersOverTimeCtx = document.getElementById( 'users-over-time' );
	let beginInput = document.getElementById( 'begin-date-users' );
	let endInput = document.getElementById( 'end-date-users' );
	let infoArray = [];
	let timeArray = [];
	let infoObj = {};
	let dataLength = 0;
	let usersOverTimeChartOptions = {
		elements: {
			line: {
				tension: 0,
			},
		},
		maintainAspectRatio: false,
		title: {
			display: false,
			text: 'Users Over Time',
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
					autoSkip: true,
					maxTicksLimit: 10,
				},
			} ],
		},
	}
	let userDatasetArray = [ {
		data: [],
		label: 'Total Users',
		borderColor: '#0050c7',
		backgroundColor: '#b4c2d6',
		fill: false,
	},
	{
		data: [],
		label: 'Total Subscribers',
		borderColor: '#BB0028',
		backgroundColor: '#ffdee5',
		fill: false,
	},
	{
		data: [],
		label: 'Total Collaborators',
		borderColor: '#e8d102',
		backgroundColor: '#fffbd6',
		fill: false,
	},
	]

	let usersOverTimeChart = new Chart( usersOverTimeCtx, {
		type: 'line',
		data: {
			labels: [],
			datasets: [ {
				data: [],
				label: 'Total Users',
				borderColor: '#0050c7',
				backgroundColor: '#b4c2d6',
				fill: false,
			},
			{
				data: [],
				label: 'Total Subscribers',
				borderColor: '#BB0028',
				backgroundColor: '#ffdee5',
				fill: false,
			},
			{
				data: [],
				label: 'Total Collaborators',
				borderColor: '#e8d102',
				backgroundColor: '#fffbd6',
				fill: false,
			},
			],
		},
		options: usersOverTimeChartOptions,
	} );

	let users = await getData( PB_Network_AnalyticsToken.usersOverTimeAjaxUrl ).then( result => {
		return result
	} )

	 infoArray = users.usersOverTime;
	 infoObj = await returnValues( infoArray );
	 timeArray = await getDefaultInputValues( infoArray );
	 dataLength = await infoObj.dateLabels.length;
	 setInputValues( timeArray, beginInput, endInput );
	 setMinMaxInputValues( timeArray, beginInput, endInput );

	userDatasetArray = [ {
		data: infoObj.totalUsers,
		label: 'Total Users',
		borderColor: '#0050c7',
		backgroundColor: '#b4c2d6',
		fill: false,
	},
	{
		data: infoObj.totalSubscribers,
		label: 'Total Subscribers',
		borderColor: '#BB0028',
		backgroundColor: '#ffdee5',
		fill: false,
	},
	{
		data: infoObj.totalContributors,
		label: 'Total Collaborators',
		borderColor: '#e8d102',
		backgroundColor: '#fffbd6',
		fill: false,
	},
	]
	setChart();

	addAriaLabel( usersOverTimeCtx, 'This chart shows the users over time' );

	document.getElementById( 'apply-data-button-users' ).addEventListener( 'click', e => addHandler( e ) );
	document.getElementById( 'reset-data-button-users' ).addEventListener( 'click', e => resetHandler( e ) );

	async function addHandler( e ){
		let startValue = document.getElementById( 'begin-date-users' ).value;
		let endValue = document.getElementById( 'end-date-users' ).value;

		let array = await getSliceByDate( infoArray, startValue, endValue )
		addChartData( e, usersOverTimeChart, infoObj, array[0], array[1] );
	}

	async function resetHandler( e ){

		await addChartData( e, usersOverTimeChart, infoObj, 0, dataLength );
		timeArray = await getDefaultInputValues( infoArray );
		setInputValues( timeArray, beginInput, endInput );
	}

	function setChart(){
		usersOverTimeChart.destroy();
		let newChart = new Chart( usersOverTimeCtx, {
			type: 'line',
			data: {
				labels: infoObj.dateLabels,
				datasets: userDatasetArray,
			},
			options: usersOverTimeChartOptions,
		} );
		usersOverTimeChart = newChart;
	}
}

