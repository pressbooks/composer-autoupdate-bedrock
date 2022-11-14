<?php
/**
 * Plugin Name: Pressbooks Whitelabel
 * Plugin URI: https://github.com/pressbooks/pressbooks-whitelabel
 * Description: Network-level branding controls for Pressbooks.
 * Version: 1.6.0
 * Author: Pressbooks (Book Oven Inc.)
 * Author URI: https://pressbooks.com
 * License: GPLv3 or later
 * Network: true
 */

// -------------------------------------------------------------------------------------------------------------------
// Check minimum requirements
// -------------------------------------------------------------------------------------------------------------------

if ( ! function_exists( 'pb_meets_minimum_requirements' ) && ! @include_once( WP_PLUGIN_DIR . '/pressbooks/compatibility.php' ) ) { // @codingStandardsIgnoreLine
	add_action( 'admin_notices', function () {
		echo '<div id="message" class="error fade"><p>' . __( 'Cannot find Pressbooks install.', 'pressbooks-cg' ) . '</p></div>';
	} );
	return;
} elseif ( ! pb_meets_minimum_requirements() ) {
	return;
}

// -------------------------------------------------------------------------------------------------------------------
// Hooks
// -------------------------------------------------------------------------------------------------------------------

require( dirname( __FILE__ ) . '/hooks.php' );
if ( is_admin() ) {
	require( dirname( __FILE__ ) . '/hooks-admin.php' );
}

// -------------------------------------------------------------------------------------------------------------------
// Do stuff
// -------------------------------------------------------------------------------------------------------------------

// Hide the PDF promo.
if ( get_site_option( 'pressbooks_hide_pdf_watermarks' ) ) {
	$GLOBALS['PB_SECRET_SAUCE']['TURN_OFF_FREEBIE_NOTICES_PDF'] = true;
}

// Hide the EPUB promo.
if ( get_site_option( 'pressbooks_hide_ebook_watermarks' ) ) {
	$GLOBALS['PB_SECRET_SAUCE']['TURN_OFF_FREEBIE_NOTICES_EPUB'] = true;
}
