<?php

namespace PressbooksNetworkAnalytics\Bin\Sync;

if ( ! defined( 'WP_CLI' ) ) {
	$script_name = basename( $argv[0] );
	die( "Run this script with WP-CLI: `wp eval-file bin/$script_name ` \n" );
}

// If plugin is not activated, and we still want to run cron, setup autoloaders
if ( ! class_exists( '\Pressbooks\DataCollector\Book' ) ) {
	\HM\Autoloader\register_class_path( 'PressbooksNetworkAnalytics', __DIR__ . '/../../inc' );
	require_once( __DIR__ . '/../../inc/namespace.php' );
}

// -------------------------------------------------------------------------------------------------------------------
// Procedure
// -------------------------------------------------------------------------------------------------------------------

set_time_limit( 0 );
ini_set( 'memory_limit', -1 );

global $wpdb;
$main_site_id = get_network()->site_id;
$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->blogs} WHERE archived = 0 AND spam = 0 AND blog_id != %d ", $main_site_id ) );

echo "Syncing book metadata (into wp_blogmeta)... \n";
$data_collector = new \Pressbooks\DataCollector\Book();
$progress = 0;
foreach ( $data_collector->copyAllBooksIntoSiteTable() as $_ ) {
	$progress++;
	echo "$progress / $count\r";
}
echo "$progress / $count";
echo "Done. \n";


