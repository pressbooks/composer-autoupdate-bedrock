<div class="wrap">
	<h1>{{ __( 'Network Settings', 'pressbooks-network-analytics') }}</h1>
	@if (count($form_validation_errors))
		<div role="alert" class="error">
			@foreach($form_validation_errors as $form_validation_error)
				<p>{{$form_validation_error}}</p>
			@endforeach
		</div>
	@endif
	<form id="pb-network-analytics-settings" action="" method="post">
		<div id="tabs">
			<ul>
				<li><a href="#tabs-1">{{ __( 'Network Defaults', 'pressbooks-network-analytics') }}</a></li>
				<li><a href="#tabs-2">{{ __( 'Book & User Registration', 'pressbooks-network-analytics') }}</a></li>
				<li><a href="#tabs-3">{{ __( 'Third-Party Tools', 'pressbooks-network-analytics') }}</a></li>
			</ul>
			<div id="tabs-1">
				<h2>{{ __( 'Current Network Managers', 'pressbooks-network-analytics' ) }}</h2>
				<p>{{ sprintf( _n( 'You currently have %d network manager.',  'You currently have %d network managers.', count($current_network_managers), 'pressbooks-network-analytics' ), count($current_network_managers) ) }}</p>
				@if ( count( $current_network_managers ) )
					<ol>
						@foreach($current_network_managers as $username => $email)
							<li>{{$username}}, <a href="mailto:{{$email}}">{{$email}}</a></li>
						@endforeach
					</ol>
				@endif
				<p>{{ __( 'To request a change to this list, email', 'pressbooks-network-analytics' ) }} <a href="mailto:premiumsupport@pressbooks.com">premiumsupport@pressbooks.com</a></p>
				<h2>{{ __( 'LTI 1.3 connection usage:', 'pressbooks-network-analytics' ) }}</h2>

				@if ( $lti_1p3_enabled )
					<p>{{ __( 'Since the beginning of this calendar year, ', 'pressbooks-network-analytics' ) }}<b><span id="lti-connections" >-</span></b>{{ __( ' students have used the grade passback feature. This number was last updated on ', 'pressbooks-network-analytics' )  }}<b><span id="lti-connections-last-updated">-</span></b>.</p>
						<input type="button" id="update-lti-usage-count" value="{{ __('Update LTI 1.3 usage count') }}">
					<p>{{ __( 'Increase the number of student connections available to your network by contacting sales@pressbooks.com.', 'pressbooks-network-analytics' ) }}</p>
				@else
					<p>{{ __( 'You do not currently have our LTI 1.3 plugin installed. This plugin allows you to securely display Pressbooks content and exchange grade information with your LMS Gradebook. ', 'pressbooks-network-analytics' ) }}</p>
					<p>{{ __( 'Opt in to the LTI 1.3 upgrade by contacting sales@pressbooks.com.', 'pressbooks-network-analytics' ) }}</p>
				@endif
				<h2>{{ __( 'Network Defaults', 'pressbooks-network-analytics' ) }}</h2>
				<table class="form-table" role="none">

					{{-- Book upload space --}}
					{{-- wp-admin/network/settings.php, get_site_option( 'upload_space_check_disabled' ),  get_site_option( 'blog_upload_space' ) !! --}}
					<tr>
						<th scope="row">{{ __( 'Book upload space' ) }}</th>
						<td>
							<label><input type="checkbox" id="upload_space_check_disabled" name="upload_space_check_disabled"
										  value="0" @php checked( (bool) get_site_option( 'upload_space_check_disabled' ), false ); @endphp /> @php printf( __( 'Limit total size of files uploaded to %s MB' ), '</label><label><input name="blog_upload_space" type="number" min="0" style="width: 100px" id="blog_upload_space" aria-describedby="blog-upload-space-desc" value="' . esc_attr( get_site_option( 'blog_upload_space', 100 ) ) . '" />' ); @endphp
							</label><br/>
							<p class="screen-reader-text" id="blog-upload-space-desc">
								{{ __( 'Size in megabytes' ) }}
							</p>
						</td>
					</tr>

					{{-- Max upload file size --}}
					{{-- wp-admin/network/settings.php, get_site_option( 'fileupload_maxk' ) !! --}}
					<tr>
						<th scope="row"><label for="fileupload_maxk">{{ __( 'Max upload file size' ) }}</label></th>
						<td>
							@php
								$max_post_size = ini_get( 'post_max_size' );
								$max_post_size_bytes = \PressbooksNetworkAnalytics\return_kilobytes( $max_post_size );
								printf(
								/* translators: %s: File size in kilobytes */
									__( '%s KB' ),
									'<input name="fileupload_maxk" type="number" min="0" max="' . $max_post_size_bytes . '" style="width: 100px" id="fileupload_maxk" aria-describedby="fileupload-maxk-desc" value="' . esc_attr( get_site_option( 'fileupload_maxk', 300 ) ) . '" />'
								);
							@endphp
							<p class="screen-reader-text" id="fileupload-maxk-desc">
								{{ __( 'Size in kilobytes' ) }}
							</p>
							<p class="description">
								{{ sprintf(
								__( 'The default maximum file upload size for this network is %dKB (%s). You can choose a smaller limit, if you prefer.', 'pressbooks-network-analytics' ),
								$max_post_size_bytes,
								$max_post_size
								) }}
							</p>
						</td>
					</tr>

					{{-- Default Book Theme --}}
					{{-- pressbooks-whitelabel/inc/admin/network/settings/namespace.php, get_site_option( 'pressbooks_default_book_theme' ) --}}
					<tr>
						<th scope="row"><label for="pressbooks_default_book_theme">{{ __( 'Default Book Theme', 'pressbooks-network-analytics' ) }}</label></th>
						<td>
							@php
								$default_book_theme_options = '';
								$themes = $GLOBALS['pressbooks']->allowedBookThemes( \WP_Theme::get_allowed_on_network() );
								foreach ( $themes as $theme => $_ ) {
									$default_book_theme_options .= sprintf(
										'<option value="%1$s"%2$s>%3$s</option>',
										$theme,
										selected( get_site_option( 'pressbooks_default_book_theme', 'pressbooks-malala' ), $theme, false ),
										wp_get_theme( $theme )->get( 'Name' )
									);
								}
								printf(
									'<select id="%1$s" name="%1$s">%2$s</select>',
									'pressbooks_default_book_theme',
									$default_book_theme_options
								);
							@endphp
						</td>
					</tr>

					{{-- Default Language --}}
					{{-- wp-admin/network/settings.php, get_site_option( 'WPLANG' ) !! --}}
					@php
						require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );
						$languages = get_available_languages();
						$translations = wp_get_available_translations();
					@endphp
					@if ( ! empty( $languages ) || ! empty( $translations ) )
						<tr>
							<th><label for="WPLANG">{{ __( 'Default Language' ) }}</label></th>
							<td>
								@php
									$lang = get_site_option( 'WPLANG' );
									if ( ! in_array( $lang, $languages ) ) {
										$lang = '';
									}

									wp_dropdown_languages(
										array(
											'name' => 'WPLANG',
											'id' => 'WPLANG',
											'selected' => $lang,
											'languages' => $languages,
											'translations' => $translations,
											'show_available_translations' => current_user_can( 'install_languages' ) && wp_can_install_language_pack(),
										)
									);
								@endphp
							</td>
						</tr>
					@endif

					{{-- Default PDF Page Size --}}
					{{-- pressbooks/inc/modules/themeoptions/class-pdfoptions.php --}}
					@php
						$p_width = get_site_option( 'pb_pdf_page_width_default' );
						$p_height = get_site_option( 'pb_pdf_page_height_default' );
					@endphp
					<tr>
						<th scope="row"><label for="pdf_page_sizes_selection">{{ __( 'Default PDF Page Size', 'pressbooks-network-analytics' ) }}</label></th>
						<td>
							<select id="pdf_page_sizes_selection">
								<option value="custom">{!! __( 'Custom&hellip;', 'pressbooks' ) !!}</option>
								<option value="5.5in_8.5in" @php selected(
									( empty($p_width) && empty($p_height) ) || // Default
									( $p_width === '5.5in' && $p_height === '8.5in' )
								) @endphp >{!! __( 'Digest (5.5&quot; &times; 8.5&quot;)', 'pressbooks' ) !!}
								</option>
								<option value="6in_9in" @php selected($p_width === '6in' && $p_height === '9in') @endphp >{!! __( 'US Trade (6&quot; &times; 9&quot;)', 'pressbooks' ) !!}</option>
								<option value="8.5in_11in" @php selected($p_width === '8.5in' && $p_height === '11in') @endphp >{!! __( 'US Letter (8.5&quot; &times; 11&quot;)', 'pressbooks' ) !!}</option>
								<option value="8.5in_9.25in" @php selected($p_width === '8.5in' && $p_height === '9.25in') @endphp>{!!__( 'Custom (8.5&quot; &times; 9.25&quot;)', 'pressbooks' )  !!}</option>
								<option value="5in_7.75in" @php selected($p_width === '5in' && $p_height === '7.75in') @endphp >{!! __( 'Duodecimo (5&quot; &times; 7.75&quot;)', 'pressbooks' ) !!}</option>
								<option value="4.25in_7in" @php selected($p_width === '4.25in' && $p_height === '7in') @endphp >{!! __( 'Pocket (4.25&quot; &times; 7&quot;)', 'pressbooks' ) !!}</option>
								<option value="21cm_29.7cm" @php selected($p_width === '21cm' && $p_height === '29.7cm') @endphp >{!! __( 'A4 (21cm &times; 29.7cm)', 'pressbooks' ) !!}</option>
								<option value="14.8cm_21cm" @php selected($p_width === '14.8cm' && $p_height === '21cm') @endphp >{!! __( 'A5 (14.8cm &times; 21cm)', 'pressbooks' ) !!}</option>
								<option value="5in_8in" @php selected($p_width === '5in' && $p_height === '8in') @endphp >{!! __( '5&quot; &times; 8&quot;', 'pressbooks' ) !!}</option>
							</select>
						</td>
					</tr>
					<tr id="pdf_page_sizes">
						<th scope="row"></th>
						<td>
							<label><input type="text" id="pb_pdf_page_width_default" name="pb_pdf_page_width_default"
										  value="{!! esc_attr( $p_width ) !!}"> {{ __( 'Width', 'pressbooks-network-analytics' ) }}
							</label><br>
							<label><input type="text" id="pb_pdf_page_height_default" name="pb_pdf_page_height_default"
										  value="{!! esc_attr( $p_height ) !!}"> {{ __( 'Height', 'pressbooks-network-analytics' ) }}
							</label>
							<p class="description">{{ __( 'Page width and height must be expressed in CSS-compatible units, e.g. ‘8.5in’ or ‘10cm’.', 'pressbooks-network-analytics' ) }}</p>
						</td>
					</tr>

					{{-- Iframe whitelist --}}
					{{-- pressbooks/inc/admin/network/class-sharingandprivacyoptions.php, \Pressbooks\Admin\Network\SharingAndPrivacyOptions::getOption( 'iframe_whitelist' ) --}}
					<tr>
						<th scope="row">{{ __( 'Iframe Allowlist', 'pressbooks' ) }}</th>
						<td>
							<textarea id="iframe_whitelist" class="widefat" name="pressbooks_sharingandprivacy_options[iframe_whitelist]"
									  rows="5" cols="45">{!! esc_textarea(\Pressbooks\Admin\Network\SharingAndPrivacyOptions::getOption( 'iframe_whitelist' )) !!}</textarea>
							<label
								for="iframe_whitelist">{!! __( 'To allowlist all content from a domain: <code>guide.pressbooks.com</code> To allowlist a path: <code>//guide.pressbooks.com/some/path/</code> One per line.', 'pressbooks' ) !!}</label>
						</td>
					</tr>

					{{-- Book directory --}}
					{{-- pressbooks/inc/admin/network/class-sharingandprivacyoptions.php, \Pressbooks\Admin\Network\SharingAndPrivacyOptions::getOption( 'network_directory_excluded' ) --}}
					<tr>
						<th scope="row">{{ __( 'Book directory', 'pressbooks' ) }}</th>
						<td>
							<input type="checkbox" id="network_directory_excluded" name="pressbooks_sharingandprivacy_options[network_directory_excluded]" @php checked( $network_directory_excluded ) @endphp />
							<label for="network_directory_excluded">@php echo __('Exclude non-catalogued public books from Pressbooks Directory.'); @endphp</label>
						</td>
					</tr>

				</table>

				<h2>{{ __( 'Dashboard Feed', 'pressbooks-network-analytics' ) }}</h2>
				<p>{{ __( 'To display a custom RSS feed widget on the user dashboard for your network, enter a Feed Title and a valid RSS Feed URL below.', 'pressbooks-network-analytics' ) }}</p>
				<table class="form-table" role="none">
					<tr>
						<th scope="row">
							<label for="pressbooks_dashboard_feed_title">{{ __( 'Feed Title', 'pressbooks-network-analytics' ) }}</label>
						</th>
						<td>
							<input name="pressbooks_dashboard_feed[title]" type="text" id="pressbooks_dashboard_feed_title" value="{{ $feed_options['title'] ?? '' }}" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="pressbooks_dashboard_feed_url">{{ __( 'Feed URL', 'pressbooks-network-analytics' ) }}</label>
						</th>
						<td>
							<input name="pressbooks_dashboard_feed[url]" type="text" id="pressbooks_dashboard_feed_url" value="{{ $feed_options['url'] ?? '' }}" />
						</td>
					</tr>
				</table>
			</div>
			<div id="tabs-2">
				<h2>{{ __('Book & User Registration Settings', 'pressbooks-network-analytics') }}</h2>
				<table class="form-table" role="none">

					{{-- Regisration --}}
					{{-- wp-admin/network/settings.php, get_site_option( 'registration' ) --}}
					<tr>
						<th scope="row"><label for="registration_1">{{ __( 'Allow registered users to create and clone new books', 'pressbooks-network-analytics' ) }}</label></th>
						<td>
							<input type="checkbox" id="registration_1" name="registration_1" value="1" @php checked( (bool) $options['registration_1'] ); @endphp />

						</td>
					</tr>
					<tr>
						<th scope="row"><label for="registration_2">{{ __( 'Allow user self-registration', 'pressbooks-network-analytics' ) }}</label></th>
						<td>
							<input type="checkbox" id="registration_2" name="registration_2" value="1" @php checked( (bool) $options['registration_2'] ); @endphp />

						</td>
					</tr>

					{{-- Add New Users --}}
					{{-- wp-admin/network/settings.php, get_site_option( 'add_new_users' ) --}}
					<tr>
						<th scope="row"><label for="add_new_users">{{ __( 'Allow book administrators to invite new users as collaborators on their book' ) }}</label></th>
						<td>
							<input name="add_new_users" type="checkbox" id="add_new_users" value="1" @php checked( get_site_option( 'add_new_users' ) ); @endphp />
						</td>
					</tr>

					{{-- Limited Email Registrations --}}
					{{-- wp-admin/network/settings.php, get_site_option( 'limited_email_domains' ) --}}
					<tr>
						<th scope="row"><label for="limited_email_domains">{{ __( 'Limited Email Registrations' ) }}</label></th>
						<td>
							@php
								$limited_email_domains = get_site_option( 'limited_email_domains' );
								$limited_email_domains = str_replace( ' ', "\n", $limited_email_domains );
							@endphp
							<textarea name="limited_email_domains" id="limited_email_domains" aria-describedby="limited-email-domains-desc" cols="45"
									  rows="5">{!! esc_textarea( $limited_email_domains == '' ? '' : implode( "\n", (array) $limited_email_domains ) ) !!}</textarea>
							<p class="description" id="limited-email-domains-desc">
								{{ __( 'If you want to limit site registrations to certain domains. One domain per line.' ) }}
							</p>
						</td>
					</tr>

					{{-- Banned Email Registrations --}}
					{{-- wp-admin/network/settings.php, get_site_option( 'banned_email_domains' ) --}}
					<tr>
						<th scope="row"><label for="banned_email_domains">{{ __( 'Banned Email Domains' ) }}</label></th>
						<td>
							<textarea name="banned_email_domains" id="banned_email_domains" aria-describedby="banned-email-domains-desc" cols="45"
									  rows="5">{!! esc_textarea( get_site_option( 'banned_email_domains' ) == '' ? '' : implode( "\n", (array) get_site_option( 'banned_email_domains' ) ) )  !!}</textarea>
							<p class="description" id="banned-email-domains-desc">
								{{ __( 'If you want to ban domains from site registrations. One domain per line.' ) }}
							</p>
						</td>
					</tr>

					{{-- The original feature is a checkbox. WordPress' notification code works with 1 email. --}}
					{{-- Ours hooks into newuser_notify_siteadmin & newblog_notify_siteadmin filters --}}
					{{-- wp-admin/network/settings.php, get_site_option( 'registrationnotification' ) --}}
					<tr>
						<th scope="row">{{ __( 'Email Notifications', 'pressbooks-network-analytics' ) }}</th>
						<td>
							@php
								if ( ! get_site_option( 'registrationnotification' ) ) {
									update_site_option( 'registrationnotification', 'yes' );
								}
							@endphp
							<label><input name="registrationnotification" type="checkbox" id="registrationnotification"
										  value="yes" @php checked( get_site_option( 'registrationnotification' ), 'yes' ); @endphp /> {{ __( 'Send email notifications each time a new book or user account is registered on the network' ) }}
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"></th>
						<td>
							@php
								$pb_registrationnotification = get_site_option( 'pb_registrationnotification' );
								$pb_registrationnotification = str_replace( ' ', "\n", $pb_registrationnotification );
							@endphp
							<textarea name="pb_registrationnotification" id="pb_registrationnotification" aria-describedby="pb_registrationnotification-desc" cols="45"
									  rows="5">{!! esc_textarea( $pb_registrationnotification == '' ? '' : implode( "\n", (array) $pb_registrationnotification ) ) !!}</textarea>
							<p class="description" id="pb_registrationnotification-desc"><label for="pb_registrationnotification">
									{{ __( 'Email notification will be sent to the addresses listed here whenever new books or users are registered on the network. One email address per line.' ) }}
								</label></p>
						</td>
					</tr>

				</table>

				<h2>{{ __('Options for Networks that Allow Self-Registration', 'pressbooks-network-analytics') }}</h2>
				<table class="form-table" role="none">

					{{-- Require users to opt-in  --}}
					{{-- pressbooks-whitelabel/inc/admin/network/settings/namespace.php, get_site_option( 'pressbooks_require_tos_optin' ) --}}
					<tr>
						<th scope="row"><label for="pressbooks_require_tos_optin">{{ __( 'Require users to opt-in to terms of service at registration', 'pressbooks-whitelabel' ) }}</label></th>
						<td>
							<input name="pressbooks_require_tos_optin" type="checkbox" id="pressbooks_require_tos_optin" value="1" @php checked( get_site_option( 'pressbooks_require_tos_optin' ) ); @endphp />
						</td>
					</tr>

					{{-- Terms Of Service Page  --}}
					{{-- pressbooks-whitelabel/inc/admin/network/settings/namespace.php, get_site_option( 'pressbooks_tos_page_id' ) --}}
					<tr>
						<th scope="row"><label for="pressbooks_default_book_theme">{{ __( 'Terms of service page', 'pressbooks-whitelabel' ) }}</label></th>
						<td>
							@php
								$tos_page_id_options = sprintf(
									'<option value="">%s</option>',
									__( '--', 'pressbooks-whitelabel' )
								);
								$pages = get_pages();
								foreach ( $pages as $page ) {
									$tos_page_id_options .= sprintf(
										'<option value="%1$d"%2$s>%3$s</option>',
										$page->ID,
										selected( get_site_option( 'pressbooks_tos_page_id' ), $page->ID, false ),
										$page->post_title
									);
								}
								printf(
									'<select id="%1$s" name="%1$s"%2$s>%3$s</select><p class="description">%4$s</p>',
									'pressbooks_tos_page_id',
									( get_site_option( 'pressbooks_require_tos_optin' ) ) ? '' : ' disabled',
									$tos_page_id_options,
									sprintf(
										/* Translators: %s: link to create a new page */
										__( 'Select the page that contains your Terms of Service, or %s.', 'pressbooks-whitelabel' ),
										sprintf(
											'<a href="%1$s">%2$s</a>',
											admin_url( '/post-new.php?post_type=page' ),
											__( 'create a new page' )
										)
									)
								);
							@endphp
						</td>
					</tr>

					{{-- Welcome Email --}}
					{{-- wp-admin/network/settings.php, get_site_option( 'welcome_email' ) --}}
					<tr>
						<th scope="row"><label for="welcome_email">{{ __( 'Welcome Email' ) }}</label></th>
						<td>
							<textarea name="welcome_email" id="welcome_email" aria-describedby="welcome-email-desc" rows="5" cols="45"
									  class="large-text">{!! esc_textarea( get_site_option( 'welcome_email' ) ) !!}</textarea>
							<p class="description" id="welcome-email-desc">
								{{ __( 'The welcome email sent to new site owners.' ) }}
							</p>
						</td>
					</tr>

					{{-- Welcome User Email --}}
					{{-- wp-admin/network/settings.php, get_site_option( 'welcome_user_email' ) !! --}}
					<tr>
						<th scope="row"><label for="welcome_user_email">{{ __( 'Welcome User Email' ) }}</label></th>
						<td>
							<textarea name="welcome_user_email" id="welcome_user_email" aria-describedby="welcome-user-email-desc" rows="5" cols="45"
									  class="large-text">{!! esc_textarea( get_site_option( 'welcome_user_email' ) ) !!}</textarea>
							<p class="description" id="welcome-user-email-desc">
								{{ __(  'The welcome email sent to new users.' ) }}
							</p>
						</td>
					</tr>

				</table>
			</div>
			<div id="tabs-3">
				<h2>{{ __('Network Web Analytics Settings', 'pressbooks-network-analytics') }}</h2>
				<table class="form-table" role="none">

					{{-- Google Analytics --}}
					{{-- pressbooks/inc/admin/analytics/namespace.php, get_site_option( 'ga_mu_uaid' ) --}}
					<tr>
						<th scope="row"><label for="ga_mu_uaid">{{ __( 'Google Analytics ID', 'pressbooks' ) }}</label></th>
						<td><input type="text" id="ga_mu_uaid" name="ga_mu_uaid" value="{!! esc_attr( get_site_option( 'ga_mu_uaid' ) ) !!}">
							<p class="description">{{ __( 'The Google Analytics ID for your network, e.g. ‘UA-01234567-8’.', 'pressbooks' ) }}</p></td>
					</tr>

				</table>
			</div>
		</div>
		<p class="submit">
			{!! $wp_nonce_field !!}
			<input type="submit" class="button-primary" value="{{ __( 'Save Changes', 'pressbooks' ) }}"/>
		</p>
	</form>
</div>
