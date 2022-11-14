<div class="wrap">
	<h1>{{ __( 'Book List', 'pressbooks-network-analytics') }}</h1>
	<p class="search-box">
		<label class="screen-reader-text" for="search-input">{{ __( 'Search Books', 'pressbooks-network-analytics') }}:</label>
		<input type="search" id="search-input" name="s" value="">
		<button id="search-apply" class="button">{{ __( 'Search Books', 'pressbooks-network-analytics') }}</button>
	</p>
	<p>
		<b>{{ sprintf( __('There are %s books on this network. They use %s of storage.', 'pressbooks-network-analytics'), $total_books, $total_storage ) }}</b><br>
		<em>{{ __('Data was last synchronized on', 'pressbooks-network-analytics') }}: {{ $last_sync }} (GMT/UTC)</em>
	</p>
	<div id="tabs">
		<ul>
			<li><a href="#tabs-1">{{ __( 'Book Properties', 'pressbooks-network-analytics') }} (<span id="tabs-1-counter">0</span>)</a></li>
			<li><a href="#tabs-2">{{ __( 'Language & Subject', 'pressbooks-network-analytics') }} (<span id="tabs-2-counter">0</span>)</a></li>
			<li><a href="#tabs-3">{{ __( 'Size & Storage', 'pressbooks-network-analytics') }} (<span id="tabs-3-counter">0</span>)</a></li>
			<li><a href="#tabs-4">{{ __( 'Themes & Exports', 'pressbooks-network-analytics') }} (<span id="tabs-4-counter">0</span>)</a></li>
		</ul>
		<div id="tabs-1" class="table-controls">
			<fieldset>
				<legend>{{ __( 'Book Status', 'pressbooks-network-analytics') }}</legend>
				<div class="grid-container">
					<label><input name="is-public" type="radio" value="1"> {{ __( 'Is Public', 'pressbooks-network-analytics') }}</label>
					<label><input name="is-public" type="radio" value="0"> {{ __( 'Is Private', 'pressbooks-network-analytics') }}</label>
					<label><input name="is-cloned" type="radio" value="0"> {{ __( 'Is Original', 'pressbooks-network-analytics') }}</label>
					<label><input name="is-cloned" type="radio" value="1"> {{ __( 'Is Cloned', 'pressbooks-network-analytics') }}</label>
				</div>
			</fieldset>
			<fieldset>
				<legend>{{ __( 'License', 'pressbooks-network-analytics') }}</legend>
				<div class="grid-container">
					@foreach ( $licenses as $license => $license_desc )
						<label><input name="currentLicense[]" type="checkbox" value="{!! esc_attr($license) !!}"> {{ $license_desc }}</label>
					@endforeach
				</div>
			</fieldset>
			<fieldset>
				<legend>{{ __( 'Plugins & Features', 'pressbooks-network-analytics') }}</legend>
				<div class="grid-container">
					<label><input id="akismet-activated" type="checkbox" value="1"> {{ __( 'Akismet Activated', 'pressbooks-network-analytics') }}</label>
					<label><input id="parsedown-party-activated" type="checkbox" value="1"> {{ __( 'Parsedown Party Activated', 'pressbooks-network-analytics') }}</label>
					<label><input id="wp-quicklatex-activated" type="checkbox" value="1"> {{ __( 'WP QuickLaTeX Activated', 'pressbooks-network-analytics') }}</label>
					<label>{{ __( 'Glossary Terms', 'pressbooks-network-analytics') }}:
						<select id="glossary-terms-dropdown">
							<option value=">">&gt;</option>
							<option value=">=">&ge;</option>
							<option value="<">&lt;</option>
							<option value="<=">&le;</option>
						</select>
						<input id="glossary-terms-number" type="number" min="0" max="999999">
					</label>
					<label>{{ __( 'H5P Activities', 'pressbooks-network-analytics') }}:
						<select id="h5p-activities-dropdown">
							<option value=">">&gt;</option>
							<option value=">=">&ge;</option>
							<option value="<">&lt;</option>
							<option value="<=">&le;</option>
						</select>
						<input id="h5p-activities-number" type="number" min="0" max="999999">
					</label>
					<label>{{ __( 'TablePress Tables', 'pressbooks-network-analytics') }}:
						<select id="tablepress-tables-dropdown">
							<option value=">">&gt;</option>
							<option value=">=">&ge;</option>
							<option value="<">&lt;</option>
							<option value="<=">&le;</option>
						</select>
						<input id="tablepress-tables-number" type="number" min="0" max="999999">
					</label>
				</div>
			</fieldset>
		</div>
		<div id="tabs-2" class="table-controls">
			<fieldset>
				<legend>{{ __( 'Book Language', 'pressbooks-network-analytics') }}</legend>
				<div class="grid-container">
					@php
						$supported_languages = \Pressbooks\L10n\supported_languages();
					@endphp
					@foreach ( $languages as $language )
						<label><input name="bookLanguage[]" type="checkbox" value="{!! esc_attr($language) !!}">
							@php
								$language_label = strtolower($language);
								if (isset($supported_languages[$language_label])) $language_label = $supported_languages[$language_label];
								else $language_label = strtoupper($language);
							@endphp
							{{ $language_label }}
						</label>
					@endforeach
				</div>
			</fieldset>
			<fieldset>
				<legend>{{ __( 'Book Subject', 'pressbooks-network-analytics') }}</legend>
				<div class="grid-container">
					@foreach ( $subjects as $subject )
						<label><input name="bookSubject[]" type="checkbox" value="{!! esc_attr($subject) !!}"> {{ $subject }}</label>
					@endforeach
				</div>
			</fieldset>
		</div>
		<div id="tabs-3" class="table-controls">
			<fieldset>
				<legend>{{ __( 'Size & Storage', 'pressbooks-network-analytics') }}</legend>
				<div class="grid-container">
					<label>{{ __( 'Word Count', 'pressbooks-network-analytics') }}:
						<select id="word-count-dropdown">
							<option value=">">&gt;</option>
							<option value=">=">&ge;</option>
							<option value="<">&lt;</option>
							<option value="<=">&le;</option>
						</select>
						<input id="word-count-number" type="number" min="0" max="999999">
					</label>

					<label>{{ __( 'Book Storage', 'pressbooks-network-analytics') }}:
						<select id="book-storage-dropdown">
							<option value=">">&gt;</option>
							<option value=">=">&ge;</option>
							<option value="<">&lt;</option>
							<option value="<=">&le;</option>
						</select>
						<input id="book-storage-number" type="number" min="0" max="999999"> {{ __( 'MB', 'pressbooks-network-analytics') }}
					</label>
				</div>
			</fieldset>
		</div>
		<div id="tabs-4" class="table-controls">
			<fieldset>
				<legend>{{ __( 'Current Theme', 'pressbooks-network-analytics') }}</legend>
				<div class="grid-container">
					@foreach ( $themes as $theme )
						<label><input name="currentTheme[]" type="checkbox" value="{!! esc_attr($theme) !!}"> {{ $theme }}</label>
					@endforeach
				</div>
			</fieldset>
			<fieldset>
				<legend>{{ __( 'Export Availability', 'pressbooks-network-analytics') }}</legend>
				<div class="grid-container">
					<label><input id="has-exports" type="checkbox" value="1"> {{ __( 'Has produced exports', 'pressbooks-network-analytics') }}</label>
					<label><input id="allows-downloads" type="checkbox" value="1"> {{ __( 'Allows downloads of exports', 'pressbooks-network-analytics') }}</label>
					<div id="last-export-label">
						{{ __( 'Has produced exports since', 'pressbooks-network-analytics') }}: <br>
						<input id="last-export-date" type="date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" aria-labelledby="last-export-label">
					</div>
					<div id="last-edited-label">
						{{ __( 'Edited since', 'pressbooks-network-analytics') }}: <br>
						<input id="last-edited-date" type="date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" aria-labelledby="last-edited-label">
					</div>
				</div>
			</fieldset>
			<fieldset>
				<legend>{{ __( 'Exports by Format', 'pressbooks-network-analytics') }}</legend>
				<div class="grid-container">
					@foreach ( $exports_by_format as $format )
						<label><input name="exportsByFormat[]" type="checkbox" value="{!! esc_attr($format) !!}"> {{ $format }}</label>
					@endforeach
				</div>
			</fieldset>
		</div>
	</div>
	<p>
		<button class="button" id="filter-apply">{{ __( 'Apply Filters', 'pressbooks-network-analytics') }}</button>
		<button class="button" id="filter-clear">{{ __( 'Reset Filters', 'pressbooks-network-analytics') }}</button>
		<button class="button" id="filter-csv-apply">{{ __( 'Download CSV', 'pressbooks-network-analytics') }}</button>
		<span class="row-count">{{ __( 'Results', 'pressbooks-network-analytics') }}: <span id="filter-row-count">0</span></span>
	</p>
	<div id="booklist"></div>
</div>
