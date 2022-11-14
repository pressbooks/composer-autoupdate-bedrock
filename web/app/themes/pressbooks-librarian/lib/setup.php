<?php

namespace Roots\Sage\Setup;

use Roots\Sage\Assets;

/**
 * Theme setup
 */
function setup() {
	// Make theme available for translation
	// Community translations can be found at https://github.com/roots/sage-translations
	load_theme_textdomain( 'pressbooks-librarian', get_template_directory() . '/lang' );

	// Enable plugins to manage the document title
	// http://codex.wordpress.org/Function_Reference/add_theme_support#Title_Tag
	add_theme_support( 'title-tag' );

	// Add image size for custom logo
	add_image_size( 'pressbooks-librarian-custom-logo', 240, 240 );

	// Enable custom logo support
	add_theme_support( 'custom-logo', [
		'height'      => 200,
		'width'       => 200,
		'flex-width' => true,
	] );

	// Enable HTML5 markup support
	// http://codex.wordpress.org/Function_Reference/add_theme_support#HTML5
	add_theme_support( 'html5', [ 'caption', 'comment-form', 'comment-list', 'gallery', 'search-form' ] );

	// Use main stylesheet for visual editor
	// To add custom styles edit /assets/styles/layouts/_tinymce.scss
	add_editor_style( Assets\asset_path( 'styles/main.css' ) );
}
add_action( 'after_setup_theme', __NAMESPACE__ . '\\setup' );

/**
 * Theme assets
 */
function assets() {
	wp_enqueue_style( 'sage/css', Assets\asset_path( 'styles/main.css' ), false, null );
	wp_enqueue_style( 'google-fonts', 'https://fonts.googleapis.com/css?family=Droid+Sans|Droid+Serif:400,400italic,700|Oswald', false, null );

	if ( is_single() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	wp_enqueue_script( 'sage/js', Assets\asset_path( 'scripts/main.js' ), [ 'jquery' ], null, true );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\assets', 100 );

// Clean up the admin menu
function admin_menu() {
	global $menu, $submenu;

	remove_menu_page( 'index.php' );
	remove_menu_page( 'edit.php' );
	remove_menu_page( 'upload.php' );
	remove_menu_page( 'link-manager.php' );
	remove_menu_page( 'edit-comments.php' );
	remove_submenu_page( 'themes.php', 'nav-menus.php' );
	remove_menu_page( 'plugins.php' );
	remove_menu_page( 'users.php' );
	remove_menu_page( 'tools.php' );
	remove_menu_page( 'options-general.php' );
}
add_action( 'admin_menu', __NAMESPACE__ . '\\admin_menu', 1 );

// Hide the admin bar
add_filter( 'show_admin_bar', function () {
	return false;
} );
