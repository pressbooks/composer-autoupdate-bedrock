<?php

namespace PressbooksNetworkAnalytics\Bin\Sync;

use PressbooksNetworkAnalytics\Collector\Visits;

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

echo "Syncing book's koko tables (into network aggregated tables)... \n";
$data_collector = new Visits();
$progress = 0;
$books = $data_collector->getAllBooks();
$count = count( $books );
foreach ( $books as $blog_id ) {
	$data_collector->aggregateVisits( $blog_id );
	$data_collector->aggregateReferrers( $blog_id );
	$progress++;
	echo "$progress / $count\r";
}
echo "$progress / $count";
echo " Done. \n";


