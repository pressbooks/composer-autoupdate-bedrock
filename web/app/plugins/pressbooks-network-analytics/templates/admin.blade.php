<div class="wrap">
	<div class="network-analytics-wrap">
		<div class="chart chart1">
		<h4 class="chart-title"> {{ __( 'Users Over Time', 'pressbooks-network-analytics') }} </h4>
			<div class="date-input-buttons">
				<input type="month" id="begin-date-users" >
				<input type="month" id="end-date-users" >
				<button id="apply-data-button-users" class="chart-button">{{ __( 'Apply Filter', 'pressbooks-network-analytics') }}</button>
				<button id="reset-data-button-users" class="chart-button">{{ __( 'Reset Filter', 'pressbooks-network-analytics') }}</button>
			</div>
			<div class="canvas-wrapper line-chart-wrapper" >
				<canvas class="chart" id="users-over-time"></canvas>
			</div>
		</div>

		<div class="chart chart2">
				<div id="button-set">
					<h4 id="button-set-title" class="chart-title">{{ __( 'User Revisions Over Last', 'pressbooks-network-analytics') }}: </h4>
					<div id="button-wrapper">
						<button class="chart-button button-active" id="year-button">{{ __( 'Year', 'pressbooks-network-analytics') }}</button>
						<button class="chart-button" id="3month-button">{{ __( '3 Months', 'pressbooks-network-analytics') }}</button>
						<button class="chart-button" id="month-button">{{ __( 'Month', 'pressbooks-network-analytics') }}</button>
						<button class="chart-button" id="week-button">{{ __( 'Week', 'pressbooks-network-analytics') }}</button>
					</div>
				</div>
				<div class="canvas-wrapper" id="canvas-wrapper-with-buttons">
					<canvas class="chart" id="most-active-users"></canvas>
				</div>
				<div id="next-prev-wrapper" >
					<button id="previous-button"><</button>
					<div id="user-amount-display"></div>
					<button id="next-button">></button>
				</div>
		</div>
		 <div class="chart chart3">
		 	<h4 class="chart-title">{{ __( 'Network Storage', 'pressbooks-network-analytics') }}</h4>
		 	<div class="canvas-wrapper">
				<canvas class="chart" id="network-storage"></canvas>
			</div>
		</div>
		<div class="chart chart4">
			<h4 class="chart-title">{{ __( 'Books Over Time', 'pressbooks-network-analytics') }}</h4>
			<div class="date-input-buttons">
				<input type="month" id="begin-date" >
				<input type="month" id="end-date" >
				<button id="apply-data-button" class="chart-button">{{ __( 'Apply Filter', 'pressbooks-network-analytics') }}</button>
				<button id="reset-data-button" class="chart-button">{{ __( 'Reset Filter', 'pressbooks-network-analytics') }}</button>
			</div>
			<div class="canvas-wrapper line-chart-wrapper" >
				<canvas class="chart" id="books-over-time"></canvas>
			</div>

		</div>
	</div>
</div>

