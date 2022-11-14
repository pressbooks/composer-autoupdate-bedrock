<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -------------------------------------------------------------------------------------------------------------------
// Includes
// -------------------------------------------------------------------------------------------------------------------

require( PB_BB_OAUTH_PLUGIN_DIR . 'inc/admin/namespace.php' );

// -------------------------------------------------------------------------------------------------------------------
// Look & feel of admin interface and Dashboard
// -------------------------------------------------------------------------------------------------------------------

add_action( 'network_admin_menu', '\PressbooksBiblioBoardOAuth\Admin\oauth_menu' );
add_action( 'admin_init', '\PressbooksBiblioBoardOAuth\Admin\oauth_css_js' );
add_action( 'admin_init', '\PressbooksBiblioBoardOAuth\Admin\oauth_options_init' );
add_filter( 'show_password_fields', '\PressbooksBiblioBoardOAuth\Admin\show_password_fields', 10, 2 );
add_action( 'show_user_profile', '\PressbooksBiblioBoardOAuth\Admin\show_reset_link', 10, 1 );
add_action( 'edit_user_profile', '\PressbooksBiblioBoardOAuth\Admin\show_reset_link', 10, 1 );
