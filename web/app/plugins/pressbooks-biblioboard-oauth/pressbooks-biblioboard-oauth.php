<?php
/**
 * Plugin Name: Pressbooks BiblioBoard OAuth
 * Plugin URI:  https://github.com/pressbooks/pressbooks-biblioboard-oauth
 * Description: Allows users to login or register on a Pressbooks network by authenticating via the BiblioBoard OAuth 2.0 provider.
 * Version:     3.2.0
 * Author:      Pressbooks (Book Oven Inc.)
 * License:     GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Textdomain:  pressbooks-biblioboard-oauth
 * Network:     True
 */

// -------------------------------------------------------------------------------------------------------------------
// Check minimum requirements
// -------------------------------------------------------------------------------------------------------------------

if ( ! function_exists( 'pb_meets_minimum_requirements' ) && ! @include_once( WP_PLUGIN_DIR . '/pressbooks/compatibility.php' ) ) { // @codingStandardsIgnoreLine
	add_action(
		'admin_notices', function () {
			echo '<div id="message" class="error fade"><p>' . __( 'Cannot find Pressbooks install.', 'pressbooks-biblioboard-oauth' ) . '</p></div>';
		}
	);
	return;
} elseif ( ! pb_meets_minimum_requirements() ) {
	return;
}
// -------------------------------------------------------------------------------------------------------------------
// Setup some defaults
// -------------------------------------------------------------------------------------------------------------------

if ( ! defined( 'PB_BB_OAUTH_PLUGIN_DIR' ) ) {
	define( 'PB_BB_OAUTH_PLUGIN_DIR', ( is_link( WP_PLUGIN_DIR . '/pressbooks-biblioboard-oauth' ) ? trailingslashit( WP_PLUGIN_DIR . '/pressbooks-biblioboard-oauth' ) : trailingslashit( __DIR__ ) ) ); // Must have trailing slash!
}

if ( ! defined( 'PB_BB_OAUTH_PLUGIN_URL' ) ) {
	define( 'PB_BB_OAUTH_PLUGIN_URL', plugins_url( 'pressbooks-biblioboard-oauth/' ) ); // Must have trailing slash!
}

// -------------------------------------------------------------------------------------------------------------------
// Class autoloader
// -------------------------------------------------------------------------------------------------------------------

\HM\Autoloader\register_class_path( 'PressbooksBiblioBoardOAuth', __DIR__ . '/inc' );

// -------------------------------------------------------------------------------------------------------------------
// Initialize
// -------------------------------------------------------------------------------------------------------------------

$GLOBALS['pressbooks_biblioboard_oauth'] = new \PressbooksBiblioBoardOAuth\OAuth();

// -------------------------------------------------------------------------------------------------------------------
// Hooks
// -------------------------------------------------------------------------------------------------------------------

require( PB_BB_OAUTH_PLUGIN_DIR . 'hooks.php' );

if ( is_admin() ) {
	require( PB_BB_OAUTH_PLUGIN_DIR . 'hooks-admin.php' );
}
