<?php

// -------------------------------------------------------------------------------------------------------------------
// Includes
// -------------------------------------------------------------------------------------------------------------------

require( dirname( __FILE__ ) . '/inc/admin/network/settings/namespace.php' );

// -------------------------------------------------------------------------------------------------------------------
// Actions
// -------------------------------------------------------------------------------------------------------------------

add_action( 'network_admin_menu', '\PressbooksWhitelabel\Admin\Network\Settings\add_network_menu' );
add_action( 'admin_init', '\PressbooksWhitelabel\Admin\Network\Settings\whitelabel_settings_init' );
