<?php
/**
 * @author Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -------------------------------------------------------------------------------------------------------------------
// Includes
// -------------------------------------------------------------------------------------------------------------------

if ( ! class_exists( 'League\OAuth2\Client\Provider\BiblioBoard' ) ) {
	if ( file_exists( PB_BB_OAUTH_PLUGIN_DIR . '/vendor/autoload.php' ) ) {
		require_once PB_BB_OAUTH_PLUGIN_DIR . '/vendor/autoload.php';
	} else {
		$title = __( 'Dependencies Missing', 'pressbooks' );
		$body = __( 'Please run <code>composer install</code> from the root of the Pressbooks BiblioBoard OAuth plugin directory.', 'pressbooks' );
		$message = "<h1>{$title}</h1><p>{$body}</p>";
		wp_die( $message, $title );
	}
}

require( PB_BB_OAUTH_PLUGIN_DIR . 'inc/namespace.php' );

// -------------------------------------------------------------------------------------------------------------------
// Login Hooks
// -------------------------------------------------------------------------------------------------------------------

add_filter( 'init', 'PressbooksBiblioBoardOAuth\rewrite_rules_for_oauth', 1 );
add_action( 'login_head', [ '\PressbooksBiblioBoardOAuth\OAuth', 'loginStylesheet' ] );
add_action( 'signup_header', [ '\PressbooksBiblioBoardOAuth\OAuth', 'registrationStylesheet' ], 11 );

// TODO:
// This hijacks the same logic as seen in the shibboleth plugin.
// If we want to support both shibboleth & oauth on the same site, then we'll need to handle the 'login_form_shibboleth' action ourselves.

add_filter( 'login_url', '\PressbooksBiblioBoardOAuth\login_url', 999 );
add_action( 'login_form_pb_oauth', '\PressbooksBiblioBoardOAuth\maybe_redirect_away_from_login_form', 999 );
add_filter( 'logout_redirect', '\PressbooksBiblioBoardOAuth\maybe_redirect_away_from_login_form', 999 );
add_filter( 'pb_session_configuration', '\PressbooksBiblioBoardOAuth\session_configuration' );
