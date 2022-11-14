<div class="wrap">
	<h1>{{ __( 'User List', 'pressbooks-network-analytics') }}</h1>
	<p class="search-box">
		<label class="screen-reader-text" for="search-input">{{ __( 'Search Users', 'pressbooks-network-analytics') }}:</label>
		<input type="search" id="search-input" name="s" value="">
		<button id="search-apply" class="button">{{ __( 'Search Users', 'pressbooks-network-analytics') }}</button>
	</p>
	<p>
		<b>{{ sprintf( __('There are %s users on this network.', 'pressbooks-network-analytics'), $total_users ) }}</b><br>
		<em>{{ __( 'Data was last synchronized on', 'pressbooks-network-analytics') }}: {{ $last_sync }} (GMT/UTC)</em>
	</p>
	<div id="tabs">
		<ul>
			<li><a href="#tabs-1">{{ __( 'User Properties', 'pressbooks-network-analytics') }} (<span id="tabs-1-counter">0</span>)</a></li>
		</ul>
		<div id="tabs-1" class="table-controls">
			<fieldset>
				<legend>{{ __( 'User Properties', 'pressbooks-network-analytics') }}</legend>
				<div class="grid-container">
					<div id="added-since-label">{{ __( 'Added since', 'pressbooks-network-analytics') }}:
						<input id="added-since-date" type="date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" aria-labelledby="added-since-label">
					</div>
					<div id="before-after-label">{{ __( 'Last logged in', 'pressbooks-network-analytics') }}:
						<select id="before-after-dropdown" aria-labelledby="before-after-label">
							<option value="before">Before</option>
							<option value="after">After</option>
						</select>
						<input id="before-after-date" type="date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" aria-labelledby="before-after-label">
					</div>
					<div id="is-role-label">{{ __( 'Is', 'pressbooks-network-analytics') }}:
						<select id="is-role-dropdown" aria-labelledby="is-role-label">
							<option value="">&nbsp;</option>
							<option value="subscriber">{{ __( 'Subscriber', 'pressbooks-network-analytics') }}</option>
							<option value="collaborator">{{ __( 'Collaborator', 'pressbooks-network-analytics') }}</option>
							<option value="author">{{ __( 'Author', 'pressbooks-network-analytics') }}</option>
							<option value="editor">{{ __( 'Editor', 'pressbooks-network-analytics') }}</option>
							<option value="administrator">{{ __( 'Administrator', 'pressbooks-network-analytics') }}</option>
						</select>
						{{ __( 'In x number of books', 'pressbooks-network-analytics') }}:
						<input id="is-role-number" type="number" min="0" max="999" aria-labelledby="is-role-label">
					</div>
					<div id="has-role-above-label">{{ __( 'Has Role &#8805;', 'pressbooks-network-analytics') }}:
						<select id="has-role-above-dropdown" aria-labelledby="has-role-above-label">
							<option value="">&nbsp;</option>
							<option value="subscriber">{{ __( 'Subscriber', 'pressbooks-network-analytics') }}</option>
							<option value="collaborator">{{ __( 'Collaborator', 'pressbooks-network-analytics') }}</option>
							<option value="author">{{ __( 'Author', 'pressbooks-network-analytics') }}</option>
							<option value="editor">{{ __( 'Editor', 'pressbooks-network-analytics') }}</option>
							<option value="administrator">{{ __( 'Administrator', 'pressbooks-network-analytics') }}</option>
						</select>
					</div>
				</div>
			</fieldset>
		</div>
		<p>
			<button class="button" id="filter-apply">{{ __( 'Apply Filters', 'pressbooks-network-analytics') }}</button>
			<button class="button" id="filter-clear">{{ __( 'Reset Filters', 'pressbooks-network-analytics') }}</button>
			<button class="button" id="filter-csv-apply">{{ __( 'Download CSV', 'pressbooks-network-analytics') }}</button>
			<span class="row-count">{{ __( 'Results', 'pressbooks-network-analytics') }}: <span id="filter-row-count">0</span></span>
		</p>
	</div>
	<div id="userlist"></div>
</div>
