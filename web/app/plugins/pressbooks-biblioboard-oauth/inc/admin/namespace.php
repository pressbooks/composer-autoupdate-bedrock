<?php

namespace PressbooksBiblioBoardOAuth\Admin;

use function \Pressbooks\Admin\Dashboard\init_network_integrations_menu;
use PressbooksBiblioBoardOAuth\OAuth;
use PressbooksMix\Assets;

function oauth_menu() {
	$parent_slug = init_network_integrations_menu();
	add_submenu_page( $parent_slug, __( 'BiblioBoard', 'pressbooks-biblioboard-oauth' ), __( 'BiblioBoard', 'pressbooks-biblioboard-oauth' ), 'manage_network_options', 'pressbooks_biblioboard_oauth', __NAMESPACE__ . '\display_oauth' );
}

function display_oauth() {
	require( PB_BB_OAUTH_PLUGIN_DIR . 'templates/admin/oauth.php' );
}

function oauth_css_js() {
	if ( isset( $_REQUEST['page'] ) && 'pressbooks_biblioboard_oauth' === esc_attr( $_REQUEST['page'] ) ) { // @codingStandardsIgnoreLine
		$assets = new Assets( 'pressbooks-biblioboard-oauth', 'plugin' );
		$assets->setSrcDirectory( 'assets' )->setDistDirectory( 'dist' );
		wp_enqueue_style( 'pb-bb-oauth-admin', $assets->getPath( 'styles/oauth.css' ), [], null );
	}
}

/**
 * Note to self and future time sink travelers going crazy: Form submission is in template, sanitize functions don't do anything.
 *
 * @see templates/admin/oauth.php
 */
function oauth_options_init() {

	$_page = 'pressbooks_biblioboard_oauth';
	$_option = 'pressbooks_oauth_options';

	add_settings_section(
		'oauth_registration',
		__( 'Registration Settings', 'pressbooks-biblioboard-oauth' ),
		__NAMESPACE__ . '\oauth_registration_callback',
		$_page
	);

	add_settings_field(
		'enable_standard_registration',
		__( 'Standard Registration', 'pressbooks-biblioboard-oauth' ),
		__NAMESPACE__ . '\oauth_enable_standard_registration_callback',
		$_page,
		'oauth_registration',
		[
			'description' => __( 'Enable standard user registration in addition to registration via configured OAuth 2.0 providers.', 'pressbooks-biblioboard-oauth' ),
		]
	);

	register_setting(
		$_page,
		'enable_standard_registration',
		[ 'sanitize_callback' => __NAMESPACE__ . '\oauth_enable_standard_registration_sanitize' ]
	);

	$providers = OAuth::$providers;

	foreach ( $providers as $k => $v ) {
		add_settings_section(
			"oauth_provider_$k",
			/* translators: provider name */
			sprintf( _x( '%s OAuth 2.0 Provider', 'A string representing an OAuth 2.0 provider, e.g. "BiblioBoard OAuth 2.0 Provider"', 'pressbooks' ), $v ),
			__NAMESPACE__ . '\oauth_provider_callback',
			$_page
		);

		add_settings_field(
			$k . '_client_id',
			/* translators: provider name */
			sprintf( _x( '%s Client ID', 'A string representing the label for the Client ID field, e.g. "BiblioBoard Client ID"', 'pressbooks-biblioboard-oauth' ), $v ),
			__NAMESPACE__ . '\oauth_client_id_callback',
			$_page,
			"oauth_provider_$k",
			[ 'provider' => $k ]
		);

		if ( OAuth::requiresSecret( $k ) ) {
			add_settings_field(
				$k . '_client_secret',
				/* translators: provider name */
				sprintf( _x( '%s Client Secret', 'A string representing the label for the Client Secret field, e.g. "BiblioBoard Client Secret"', 'pressbooks-biblioboard-oauth' ), $v ),
				__NAMESPACE__ . '\oauth_client_secret_callback',
				$_page,
				"oauth_provider_$k",
				[ 'provider' => $k ]
			);
		}

		add_settings_field(
			$k . '_bypass',
			__( 'Bypass', 'pressbooks-biblioboard-oauth' ),
			__NAMESPACE__ . '\oauth_bypass',
			$_page,
			"oauth_provider_$k",
			[
				'provider' => $k,
				'description' => __( 'Bypass email checks against admin-provided domain whitelists and blacklists', 'pressbooks-biblioboard-oauth' ),
			]
		);

		add_settings_field(
			$k . '_customize_login_button',
			__( 'Customize Login Button', 'pressbooks-biblioboard-oauth' ),
			__NAMESPACE__ . '\oauth_customize_login_button_callback',
			$_page,
			"oauth_provider_$k",
			[
				'provider' => $k,
				'description' => __( 'Change the default text on the login button.', 'pressbooks-biblioboard-oauth' ),
			]
		);

		register_setting(
			$_page,
			$k . '_client_id',
			[ 'sanitize_callback' => __NAMESPACE__ . '\oauth_client_id_sanitize' ]
		);
		register_setting(
			$_page,
			$k . '_client_secret',
			[ 'sanitize_callback' => __NAMESPACE__ . '\oauth_client_secret_sanitize' ]
		);
		register_setting(
			$_page,
			$k . '_bypass',
			[ 'sanitize_callback' => __NAMESPACE__ . '\oauth_bypass_sanitize' ]
		);
		register_setting(
			$_page,
			$k . '_customize_login_button',
			[ 'sanitize_callback' => __NAMESPACE__ . '\oauth_customize_login_button_sanitize' ]
		);
	}
}

function oauth_provider_callback( $args ) {
	?>
	<p>
	<?php
	/* translators: provider */
	printf( _x( 'Enter the relevant information for the %s here.', 'A string prompting for details on an OAuth 2.0 provider, e.g. "Enter the relevant information for the BiblioBoard OAuth 2.0 Provider here."', 'pressbooks-biblioboard-oauth' ), $args['title'] );
	?>
	<?php
	$option = get_site_option( 'pressbooks_oauth_options', [] );
	$provider = str_replace( 'oauth_provider_', '', $args['id'] );
	if ( $provider === 'biblioboard' ) {
		_e( 'When BiblioBoard is configured the login and logout pages will be hidden.', 'pressbooks-biblioboard-oauth' );
	}
	echo '</p>';

	if ( OAuth::requiresSecret( $provider ) && ! empty( $option[ $provider . '_client_id' ] ) && ! empty( $option[ $provider . '_client_secret' ] ) ) {
		?>
		<div id="<?php echo $args['id']; ?>_status" class="provider-status active">
			<p>
			<?php
			/* translators: provider status */
			printf( _x( 'The %s is active.', 'A string representing an OAuth 2.0 provider\'s status, e.g. "The BiblioBoard OAuth 2.0 Provider is active."', 'pressbooks-biblioboard-oauth' ), $args['title'] );
			?>
			</p>
		</div>
	<?php } elseif ( ! OAuth::requiresSecret( $provider ) && ! empty( $option[ $provider . '_client_id' ] ) ) { ?>
		<div id="<?php echo $args['id']; ?>_status" class="provider-status active">
			<p>
			<?php
				/* translators: provider status */
				printf( _x( 'The %s is active.', 'A string representing an OAuth 2.0 provider\'s status, e.g. "The BiblioBoard OAuth 2.0 Provider is active."', 'pressbooks-biblioboard-oauth' ), $args['title'] );
			?>
				</p>
		</div>
	<?php } else { ?>
		<div id="<?php echo $args['id']; ?>_status" class="provider-status inactive">
			<p>
			<?php
				/* translators: provider status */
				printf( _x( 'The %s is inactive.', 'A string representing an OAuth 2.0 provider\'s status, e.g. "The BiblioBoard OAuth 2.0 Provider is active."', 'pressbooks-biblioboard-oauth' ), $args['title'] );
			?>
				</p>
		</div>
	<?php } ?>
	<?php
}

function oauth_registration_callback( $args ) {
	?>
	<p><?php __( 'Adjust settings for the OAuth 2.0 registration flow here.', 'pressbooks-biblioboard-oauth' ); ?></p>
	<?php
}

function oauth_enable_standard_registration_callback( $args ) {
	$option = get_site_option( 'registration', false, false );
	switch ( $option ) :
		case 'user':
		case 'all':
			$enable_standard_registration = 1;
			break;
		case 'blog':
		case 'none':
		default:
			$enable_standard_registration = 0;
	endswitch;
	$html = '<input id="enable_standard_registration" name="enable_standard_registration" type="checkbox" value="1" ' . checked( $enable_standard_registration, 1, false ) . '/>';
	$html .= '<p class="description">' . $args['description'] . '</p>';
	echo $html;
}

function oauth_client_id_callback( $args ) {
	$option = get_site_option( 'pressbooks_oauth_options', [] );
	$provider_client_id = isset( $option[ $args['provider'] . '_client_id' ] ) ? $option[ $args['provider'] . '_client_id' ] : '';
	$html = '<input id="' . $args['provider'] . '_client_id" name="pressbooks_oauth_options[' . $args['provider'] . '_client_id]" type="text" value="' . $provider_client_id . '" />';
	echo $html;
}

function oauth_client_secret_callback( $args ) {
	$option = get_site_option( 'pressbooks_oauth_options', [] );
	$provider_client_secret = isset( $option[ $args['provider'] . '_client_secret' ] ) ? $option[ $args['provider'] . '_client_secret' ] : '';
	$html = '<input id="' . $args['provider'] . '_client_secret" name="pressbooks_oauth_options[' . $args['provider'] . '_client_secret]" type="text" value="' . $provider_client_secret . '" />';
	echo $html;
}

function oauth_bypass( $args ) {
	$option = get_site_option( 'pressbooks_oauth_options', [] );
	$provider_bypass = isset( $option[ $args['provider'] . '_bypass' ] ) ? $option[ $args['provider'] . '_bypass' ] : '';
	$html = '<input id="' . $args['provider'] . '_bypass" name="pressbooks_oauth_options[' . $args['provider'] . '_bypass]" type="checkbox" value="1" ' . checked( $provider_bypass, 1, false ) . '/>';
	$html .= '<p class="description">' . $args['description'] . '</p>';
	echo $html;
}

function oauth_customize_login_button_callback( $args ) {
	$option = get_site_option( 'pressbooks_oauth_options', [] );
	$provider_customize_login_button = isset( $option[ $args['provider'] . '_customize_login_button' ] ) ? $option[ $args['provider'] . '_customize_login_button' ] : '';
	$html = '<input id="' . $args['provider'] . '_customize_login_button" name="pressbooks_oauth_options[' . $args['provider'] . '_customize_login_button]" type="text" class="regular-text" value="' . $provider_customize_login_button . '" />';
	$html .= '<p class="description">' . $args['description'] . '</p>';
	echo $html;
}

function oauth_enable_standard_registration_sanitize( $input ) {
	return absint( $input );
}

function oauth_client_id_sanitize( $input ) {
	return sanitize_text_field( $input );
}

function oauth_client_secret_sanitize( $input ) {
	return sanitize_text_field( $input );
}

function oauth_bypass_sanitize( $input ) {
	return absint( $input );
}

function oauth_customize_login_button_sanitize( $input ) {
	return wp_unslash( wp_kses( $input, [
		'br' => [],
	] ) );
}

function show_password_fields( $show, $profileuser ) {
	if ( ! current_user_can( 'manage_network' ) ) {
		$pressbooks_oauth2_identity = get_user_meta( $profileuser->ID, 'pressbooks_oauth2_identity', true );
		if ( $pressbooks_oauth2_identity ) {
			$show = false;
		}
	}
	return $show;
}

function show_reset_link( $profileuser ) {
	$oauth = $GLOBALS['pressbooks_biblioboard_oauth'];
	$pressbooks_oauth2_identity = get_user_meta( $profileuser->ID, 'pressbooks_oauth2_identity', true );
	if ( $pressbooks_oauth2_identity ) {
		$provider = explode( '|', $pressbooks_oauth2_identity )[0];
		$oauth->outputProfileReset( $provider );
	}
}
