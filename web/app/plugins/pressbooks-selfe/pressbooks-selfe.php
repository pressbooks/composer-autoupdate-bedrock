<?php
/**
 * Plugin Name:        Pressbooks SELF-e
 * Plugin URI:         https://github.com/pressbooks/pressbooks-selfe
 * Description:        Indie Author Project integration for Pressbooks. Indie Author Project was formerly known as SELF-e.
 * Version:            1.6.3
 * Requires at least:  6.0
 * Requires PHP:       7.4
 * Author:             Pressbooks (Book Oven Inc.)
 * Author URI:         https://pressbooks.com/
 * License:            GPL v3 or later
 * License URI:        https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:        pressbooks-selfe
 * Domain Path:        /languages
 */

// -------------------------------------------------------------------------------------------------------------------
// Check minimum requirements
// -------------------------------------------------------------------------------------------------------------------

if ( ! function_exists( 'pb_meets_minimum_requirements' ) && ! @include_once( WP_PLUGIN_DIR . '/pressbooks/compatibility.php' ) ) { // @codingStandardsIgnoreLine
	add_action(
		'admin_notices', function () {
			echo '<div id="message" role="alert" class="error fade"><p>' . __( 'Cannot find Pressbooks install.', 'pressbooks-selfe' ) . '</p></div>';
		}
	);
	return;
} elseif ( ! pb_meets_minimum_requirements() ) {
	return;
}

// Composer autoloader.
if ( ! class_exists( 'Gmo\Iso639\Languages' ) ) {
	if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
		include_once dirname( __FILE__ ) . '/vendor/autoload.php';
	} else {
		$title = __( 'Dependencies Missing', 'pressbooks-selfe' );
		$body = __( 'Please run <code>composer install</code> from the root of the Pressbooks SELF-e plugin directory.', 'pressbooks-selfe' );
		$message = "<h1>{$title}</h1><p>{$body}</p>";
		wp_die( $message, $title );
	}
}

\HM\Autoloader\register_class_path( 'Pressbooks_Selfe', __DIR__ . '/inc' );

// Admin page.
require_once( dirname( __FILE__ ) . '/inc/data/namespace.php' );
require_once( dirname( __FILE__ ) . '/inc/metadata/namespace.php' );

// Instantiate classes.
new \Pressbooks_Selfe\Admin\Page;
