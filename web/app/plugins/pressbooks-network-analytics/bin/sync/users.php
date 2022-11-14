<?php

namespace PressbooksNetworkAnalytics\Bin\Sync;

if ( ! defined( 'WP_CLI' ) ) {
	$script_name = basename( $argv[0] );
	die( "Run this script with WP-CLI: `wp eval-file bin/$script_name ` \n" );
}

// If plugin is not activated, and we still want to run cron, setup autoloaders
if ( ! class_exists( '\Pressbooks\DataCollector\User' ) ) {
	\HM\Autoloader\register_class_path( 'PressbooksNetworkAnalytics', __DIR__ . '/../../inc' );
	require_once( __DIR__ . '/../../inc/namespace.php' );
}

// -------------------------------------------------------------------------------------------------------------------
// Procedure
// -------------------------------------------------------------------------------------------------------------------

set_time_limit( 0 );
ini_set( 'memory_limit', -1 );

global $wpdb;
$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users} WHERE spam = 0 AND deleted = 0 " );

echo "Syncing user metadata (into wp_usermeta)... \n";
$data_collector = new \Pressbooks\DataCollector\User();
$progress = 0;
foreach ( $data_collector->updateAllUsersMetadata() as $_ ) {
	$progress++;
	echo "$progress / $count\r";
}
echo "$progress / $count";
$data_collector->updateNetworkManagers();
echo "Done. \n";


