import { getData, formatBytes, addAriaLabel, sortUsers, gotoLink } from '../utility.js';

let Chart = require( 'chart.js' );
require( 'chartjs-chart-treemap' );
let Color = require( 'color' );

export async function getNetworkStorageChart(){
	let networkStorageCtx = document.getElementById( 'network-storage' );

	let storageChartData = await getData( PB_Network_AnalyticsToken.networkStorageAjaxUrl ).then( result => {
		return result.networkStorage.map( ( item ) => {
			item.nested = { value: ( parseInt( item.totalSize ) / 1024 ) / 1024 };
			return item;
		} );
	} )

	await sortUsers( storageChartData, 'totalSize' );

	addAriaLabel( networkStorageCtx, 'This chart shows network storage based on book size' );

	// Make sure getting the username on bar click is possible (it is)
	function getBoxInfo( e, array ){
		if ( array[0] ){
			let index = array[0]._index;
			setBookLink( index );
		}
	}

	function setBookLink( index ) {
		gotoLink( storageChartData[ index ].bookLink );
	}

	let networkStorageChart = new Chart( networkStorageCtx, {
		type: 'treemap',
		data: {
			datasets: [ {
				tree: storageChartData,
				key: 'storagePercent',
				label: [ 'bookName', 'totalSize' ],
				backgroundColor: function ( networkStorageCtx ) {
					let value = networkStorageCtx.dataset.tree[networkStorageCtx.dataIndex];
					let alpha = ( Math.log( value.nested.value ) + 5 ) / 10;
					let color = Color( '#008744' ).alpha( alpha ).rgb().string();
					return color;
				},
				pointBackgroundColor: 'white',
			} ],
		},
		options: {
			showAllTooltips: false,
			legend: {
				display: false,
			},
			layout: {
				padding: {
				   top: 30,
				},
			 },
			tooltips: {
				caretPadding: 20,
				bodySpacing: 50,
				yAlign: 'bottom',
				callbacks: {
					title: function ( item, data ) {
						item = item[0];
						let set = data.datasets[item.datasetIndex].data[item.index];
						return set._data.bookName;
					  },
					label: function ( item, data ) {
						let dataset = data.datasets[item.datasetIndex];
						let dataItem = dataset.data[item.index];
						let obj = dataItem._data;
						return 'Size: ' + formatBytes( obj.totalSize ) + ' (' + obj.storagePercent + '%)';
					  },
				},
			},
			title: {
				display: false,
				text: 'Network Storage',
			  },
			maintainAspectRatio: false,
			events: [ 'mousemove', 'click' ],
			onClick: getBoxInfo,
		},
	} );
}
