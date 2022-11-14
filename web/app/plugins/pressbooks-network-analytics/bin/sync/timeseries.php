<?php

namespace PressbooksNetworkAnalytics\Bin\Sync;

use PressbooksNetworkAnalytics\Collector\Network;

if ( ! defined( 'WP_CLI' ) ) {
	$script_name = basename( $argv[0] );
	die( "Run this script with WP-CLI: `wp eval-file bin/$script_name ` \n" );
}

// If plugin is not activated, and we still want to run cron, setup autoloaders
if ( ! class_exists( '\Pressbooks\DataCollector\Book' ) || ! class_exists( '\Pressbooks\DataCollector\User' ) ) {
	\HM\Autoloader\register_class_path( 'PressbooksNetworkAnalytics', __DIR__ . '/../../inc' );
	require_once( __DIR__ . '/../../inc/namespace.php' );
}

// -------------------------------------------------------------------------------------------------------------------
// Procedure
// -------------------------------------------------------------------------------------------------------------------

set_time_limit( 0 );
ini_set( 'memory_limit', -1 );

$network_collector = new Network();
echo "Syncing blog meta table into time series table... \n";
echo $network_collector->populateTable() === false ?
	" Error inserting network data into the time series table. \n" :
	" Done. \n";
