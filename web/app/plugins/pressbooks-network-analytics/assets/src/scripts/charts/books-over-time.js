import { getData, returnBookValues, addAriaLabel, getDefaultInputValues, setMinMaxInputValues, getSliceByDate, setInputValues, addChartData } from '../utility';

let Chart = require( 'chart.js' );

export async function getBooksOverTimeChart(){
	let booksLineCtx = document.getElementById( 'books-over-time' );
	let beginInput = document.getElementById( 'begin-date' );
	let endInput = document.getElementById( 'end-date' );
	let infoArray = [];
	let timeArray = [];
	let dataLength = 0;
	let labels = [];

	let booksOverTimeChartOptions = {
		maintainAspectRatio: false,
		responsive: true,
		elements: {
			line: {
				tension: 0,
			},
		  },
		  title: {
			display: false,
			text: 'Number of Books on Network',
		  },
		  legend: {
			  display: true,
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

	let booksOverTimeDatasetArray = [ {
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

	let booksLineChart = new Chart( booksLineCtx, {
		type: 'line',
		data: {
			labels: labels,
			datasets: booksOverTimeDatasetArray,
		},
		options: booksOverTimeChartOptions,
	} );

	infoArray = await getData( PB_Network_AnalyticsToken.booksOverTimeAjaxUrl ).then( result => {
		return result.booksOverTime
	} )
	let infoObj = await returnBookValues( infoArray );
	labels = await infoObj.dateLabels;
	timeArray = await getDefaultInputValues( infoArray );
	dataLength = await infoObj.dateLabels.length;
	setInputValues( timeArray, beginInput, endInput );
	setMinMaxInputValues( timeArray, beginInput, endInput );

	booksOverTimeDatasetArray = [ {
		data: infoObj.totalBooks,
		label: 'Total Books',
		borderColor: '#0050c7',
		backgroundColor: '#b4c2d6',
		fill: false,
	},
	{
		data: infoObj.totalCloned,
		label: 'Cloned Books',
		borderColor: '#BB0028',
		backgroundColor: '#ffdee5',
		fill: false,
	},
	{
		data: infoObj.totalPublic,
		label: 'Public Books',
		borderColor: '#e8d102',
		backgroundColor: '#fffbd6',
		fill: false,
	},
	{
		data: infoObj.totalPrivate,
		label: 'Private Books',
		borderColor: 'purple',
		backgroundColor: '#e6c5e6',
		fill: false,
	},
	]
	await setChart();

	addAriaLabel( booksLineCtx, 'This chart shows books over time' );

	function setChart(){
		booksLineChart.destroy();
		let newChart = new Chart( booksLineCtx, {
			type: 'line',
			data: {
				labels: labels,
				datasets: booksOverTimeDatasetArray,
			},
			options: booksOverTimeChartOptions,
		} );
		booksLineChart = newChart;
	}

	document.getElementById( 'apply-data-button' ).addEventListener( 'click', e => addHandler( e ) );
	document.getElementById( 'reset-data-button' ).addEventListener( 'click', e => resetHandler( e ) );

	async function addHandler( e ){
		let startValue = document.getElementById( 'begin-date' ).value;
		let endValue = document.getElementById( 'end-date' ).value;
		let array = await getSliceByDate( infoArray, startValue, endValue )
		addChartData( e, booksLineChart, infoObj, array[0], array[1] );
	}

	async function resetHandler( e ){
		await addChartData( e, booksLineChart, infoObj, 0, dataLength );
		timeArray = await getDefaultInputValues( infoArray );
		setInputValues( timeArray, beginInput, endInput );
	}

}
