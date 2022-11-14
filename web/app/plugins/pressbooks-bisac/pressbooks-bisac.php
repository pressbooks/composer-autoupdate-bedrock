<?php
/**
 * Plugin Name:         Pressbooks BISAC
 * Plugin URI:          https://github.com/pressbooks/pressbooks-bisac
 * Description:         Adds the BISAC Subject Headings List and the BISAC Regional Themes List to the Pressbooks Book Information page.
 * Version:             2.4.0
 * Requires at least:   6.0.3
 * Requires PHP:        7.4
 * Author:              Pressbooks (Book Oven Inc.)
 * Author URI:          https://pressbooks.com
 * License:             GPL 2.0+
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:         pressbooks-bisac
 * Domain Path:         /languages
 *
 * @package             Pressbooks_BISAC
 */

// -------------------------------------------------------------------------------------------------------------------
// Check minimum requirements
// -------------------------------------------------------------------------------------------------------------------

 if ( ! function_exists( 'pb_meets_minimum_requirements' ) && ! @include_once( WP_PLUGIN_DIR . '/pressbooks/compatibility.php' ) ) { // @codingStandardsIgnoreLine
	add_action('admin_notices', function () {
		echo '<div id="message" class="error fade"><p>' . esc_html__( 'Cannot find Pressbooks install.', 'pressbooks-cg' ) . '</p></div>';
	});
	return;
} elseif ( ! pb_meets_minimum_requirements() ) {
		return;
}

// -------------------------------------------------------------------------------------------------------------------
// Setup some defaults
// -------------------------------------------------------------------------------------------------------------------
if ( ! defined( 'PB_BISAC_PLUGIN_DIR' ) ) {
		define( 'PB_BISAC_PLUGIN_DIR', __DIR__ . '/' ); // Must have trailing slash!
}

if ( ! defined( 'PB_BISAC_PLUGIN_URL' ) ) {
		define( 'PB_BISAC_PLUGIN_URL', plugins_url( 'pressbooks-bisac/' ) ); // Must have trailing slash!
}

// -------------------------------------------------------------------------------------------------------------------
// Composer Autoloader
// -------------------------------------------------------------------------------------------------------------------
if ( ! class_exists( 'League\Csv\Reader' ) ) {
	require_once( dirname( __FILE__ ) . '/vendor/autoload.php' );
}

// -------------------------------------------------------------------------------------------------------------------
// Require namespaced functions
// -------------------------------------------------------------------------------------------------------------------
require_once( dirname( __FILE__ ) . '/inc/filters/namespace.php' );

// -------------------------------------------------------------------------------------------------------------------
// Add filters
// -------------------------------------------------------------------------------------------------------------------
add_filter( 'pb_bisac_subject_field_args', '\Pressbooks_BISAC\Filters\bisac_subjects_field_arguments', 10, 1 );
add_filter( 'pb_bisac_regional_theme_field_args', '\Pressbooks_BISAC\Filters\bisac_regional_theme_field_arguments', 10, 1 );
add_filter( 'get_invalidated_codes_alternatives_mapped', '\Pressbooks_BISAC\Filters\get_invalidated_codes_alternatives_mapped', 10, 1 );
