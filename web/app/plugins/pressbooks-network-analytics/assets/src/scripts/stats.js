import { getBooksOverTimeChart } from './charts/books-over-time';
import { getNetworkStorageChart } from './charts/network-storage';
import { getMostActiveUsersChart } from './charts/user-revisions';
import { getUsersOverTimeChart } from './charts/users-over-time';

let Chart = require( 'chart.js' );
let ChartDataLabels = require( 'chartjs-plugin-datalabels' );
Chart.plugins.unregister( ChartDataLabels );
require( 'chartjs-chart-treemap' );

// Function for any global defaults that need to be set on all Charts before indivual chart loads
/**
 *
 */
function setChartGlobalDefaults(){
	Chart.scaleService.updateScaleDefaults( 'category', {
		ticks: {
			  /**
					 * @param tick
					 */
			  callback: function ( tick ) {
				let characterLimit = 12;
				if ( tick.length >= characterLimit ) {
						  return tick.slice( 0, tick.length ).substring( 0, characterLimit -1 ).trim() + '...';
				}
				return tick;
			  },
		},
	} );
}

window.addEventListener( 'load', async function () {
	await setChartGlobalDefaults();
	getUsersOverTimeChart();
	getMostActiveUsersChart();
	getBooksOverTimeChart();
	getNetworkStorageChart();
} );
