<?php
/*
Plugin Name: Pressbooks Network Analytics
Plugin URI: https://pressbooks.org
Description: A network analytics dashboard for pressbooks users.
Version: 2.1.0
Author: Pressbooks (Book Oven Inc.)
Author URI: https://pressbooks.org
Text Domain: pressbooks-network-analytics
License: GPL v3 or later
Network: True
*/

// -------------------------------------------------------------------------------------------------------------------
// Check requirements
// -------------------------------------------------------------------------------------------------------------------

if ( ! function_exists( 'pb_meets_minimum_requirements' ) && ! @include_once( WP_PLUGIN_DIR . '/pressbooks/compatibility.php' ) ) { // @codingStandardsIgnoreLine
	add_action(
		'admin_notices', function () {
			echo '<div id="message" role="alert" class="error fade"><p>' . __( 'Cannot find Pressbooks install.', 'pressbooks-network-analytics' ) . '</p></div>';
		}
	);
	return;
} elseif ( ! pb_meets_minimum_requirements() ) {
	return;
}

// -------------------------------------------------------------------------------------------------------------------
// Class autoloader
// -------------------------------------------------------------------------------------------------------------------

\HM\Autoloader\register_class_path( 'PressbooksNetworkAnalytics', __DIR__ . '/inc' );

// -------------------------------------------------------------------------------------------------------------------
// Composer autoloader
// -------------------------------------------------------------------------------------------------------------------



// -------------------------------------------------------------------------------------------------------------------
// Requires
// -------------------------------------------------------------------------------------------------------------------

require( __DIR__ . '/inc/namespace.php' );

// -------------------------------------------------------------------------------------------------------------------
// Hooks
// -------------------------------------------------------------------------------------------------------------------

// TODO: init
// load_plugin_textdomain( 'pressbooks-network-analytics', false, 'pressbooks-network-analytics/languages/' );
register_activation_hook( __FILE__, [ '\PressbooksNetworkAnalytics\Collector\Network', 'install' ] );
register_activation_hook( __FILE__, [ '\PressbooksNetworkAnalytics\Collector\Visits', 'install' ] );
add_action( 'plugins_loaded', function() {
	\Pressbooks\Container::get( 'Blade' )->addNamespace( 'PressbooksNetworkAnalytics', __DIR__ . '/templates' );
});
add_action( 'plugins_loaded', [ '\PressbooksNetworkAnalytics\Collector\Network', 'install' ] );
add_action( 'plugins_loaded', [ '\PressbooksNetworkAnalytics\Admin\Stats', 'init' ] );
add_action( 'plugins_loaded', [ '\PressbooksNetworkAnalytics\Admin\Books', 'init' ] );
add_action( 'plugins_loaded', [ '\PressbooksNetworkAnalytics\Admin\Users', 'init' ] );
add_action( 'plugins_loaded', [ '\PressbooksNetworkAnalytics\Admin\Options', 'init' ] );
add_action( 'plugins_loaded', [ '\PressbooksNetworkAnalytics\Admin\Menus', 'init' ] );
add_action( 'plugins_loaded', [ '\PressbooksNetworkAnalytics\Collector\Visits', 'checkInstallation' ] );

// -------------------------------------------------------------------------------------------------------------------
// Notify Network Manager
// -------------------------------------------------------------------------------------------------------------------
add_filter( 'init', [ '\PressbooksNetworkAnalytics\NetworkManagersNotification', 'init' ], 10, 2 );
