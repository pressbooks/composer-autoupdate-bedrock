<?php

use PressbooksNetworkAnalytics\Collector\Visits;

/**
 * @group networkcollector
 */
class Collector_VisitsTest extends \WP_UnitTestCase {
	/**
	 * @var int
	 */
	protected $total_books = 5;

	/**
	 * @var Visits
	 */
	protected $visits;

	private function setTestData() {
		global $wpdb;
		// Set up fake koko tables for each book (site)

		$today = date( 'Y-m-d' );

		foreach(range(2,$this->total_books) as $book) {

			$site_table = "{$wpdb->base_prefix}{$book}_koko_analytics_site_stats";
			$referrers_table = "{$wpdb->base_prefix}{$book}_koko_analytics_referrer_stats";
			$referrers_urls_table = "{$wpdb->base_prefix}{$book}_koko_analytics_referrer_urls";

			$wpdb->query( "CREATE TABLE IF NOT EXISTS {$site_table} (
				  id int(10) unsigned NOT NULL AUTO_INCREMENT,
				  visitors int(10) unsigned NOT NULL,
				  pageviews int(10) unsigned NOT NULL,
				  date date NOT NULL,
				  PRIMARY KEY  (id)
			)" );

			$wpdb->query( "CREATE TABLE IF NOT EXISTS {$referrers_table} (
				  id int(10) unsigned,
				  visitors int(10) unsigned NOT NULL,
				  pageviews int(10) unsigned NOT NULL,
				  date date NOT NULL
			)" );

			$wpdb->query( "CREATE TABLE IF NOT EXISTS {$referrers_urls_table} (
				  id int(10) unsigned NOT NULL AUTO_INCREMENT,
				  url VARCHAR(150) NOT NULL,
				  PRIMARY KEY  (id)
			)" );
		}
		// Fill koko tables with test data
		foreach(range(2,$this->total_books) as $book) {
			$id = $book-1;
			$wpdb->query( "INSERT INTO {$wpdb->blogs} (`blog_id`,`archived`,`spam`) VALUES ($book,0,0)" );
			$wpdb->query( "INSERT INTO {$wpdb->base_prefix}{$book}_koko_analytics_site_stats (`date`, `visitors`, `pageviews`) VALUES ('$today', 1, 99)" );
			$wpdb->query( "INSERT INTO {$wpdb->base_prefix}{$book}_koko_analytics_referrer_urls (id,`url`) VALUES ($id,'https://google{$book}.com')" );
			$wpdb->query( "INSERT INTO {$wpdb->base_prefix}{$book}_koko_analytics_referrer_stats (`date`, `id`, `visitors`, `pageviews`) VALUES ('$today', $id, 10, 99)" );
		}

		// Insert 100 historical days data for one site to test historial data inserting
		// 500 visits & 100 pageviews
		$sample = 3;
		foreach(range(1,100) as $day) {
			$date = date('Y-m-d', strtotime($today. " - $day days"));
			$wpdb->query( "INSERT INTO {$wpdb->base_prefix}{$sample}_koko_analytics_site_stats (`date`, `visitors`, `pageviews`) VALUES ('$date', 5, 1)" );
		}

	}

	public function set_up() {
		parent::set_up();
		global $wpdb;
		$this->setTestData();
		Visits::$IS_TESTING = true;
		Visits::install(true);
		$this->visits = new Visits();
	}

	public function test_if_aggregatedTablesAreGenerated() {
		global $wpdb;
		$this->assertTrue( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->base_prefix}network_aggregated_stats'" ) === "{$wpdb->base_prefix}network_aggregated_stats" );
		$this->assertTrue( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->base_prefix}network_aggregated_referrers'" ) === "{$wpdb->base_prefix}network_aggregated_referrers" );
	}

	public function test_ifPreviousDataWasCollected()
	{
		global $wpdb;
		$this->assertEquals('504',$wpdb->get_var("SELECT SUM(visitors) FROM {$wpdb->base_prefix}network_aggregated_stats" )); // 4 for each site + 500 from previous days
		$this->assertEquals('496',$wpdb->get_var("SELECT SUM(pageviews) FROM {$wpdb->base_prefix}network_aggregated_stats" )); // 99*4 + 100 from previous days
		$this->assertEquals('https://google2.com',$wpdb->get_var("SELECT url FROM {$wpdb->base_prefix}network_aggregated_referrers ORDER BY id ASC" ));
		$this->assertEquals('https://google5.com',$wpdb->get_var("SELECT url FROM {$wpdb->base_prefix}network_aggregated_referrers ORDER BY id DESC" ));
	}

	public function test_if_agreggateTodaysVisitsWorks()
	{
		global $wpdb;
		$book = 2;
		$this->assertEquals('504',$wpdb->get_var("SELECT SUM(visitors) FROM {$wpdb->base_prefix}network_aggregated_stats" )); // historical data
		$today = date('Y-m-d');
		$wpdb->query( "INSERT INTO {$wpdb->base_prefix}{$book}_koko_analytics_site_stats (`visitors`, `pageviews`, `date`) VALUES (100, 1, '$today')" );
		$this->visits->aggregateVisits($book);
		$this->assertEquals('604',$wpdb->get_var("SELECT SUM(visitors) FROM {$wpdb->base_prefix}network_aggregated_stats" )); // historical data + today
	}

	public function test_if_aggregateTodaysReferrersWorks()
	{
		global $wpdb;
		$book = 2;
		// last referrer
		$this->assertEquals('https://google2.com',$wpdb->get_var("SELECT url FROM {$wpdb->base_prefix}network_aggregated_referrers WHERE blog_id = 2 ORDER BY id DESC LIMIT 1" ));
		$today = date('Y-m-d');
		$wpdb->query( "INSERT INTO {$wpdb->base_prefix}{$book}_koko_analytics_referrer_urls (`id`,`url`) VALUES (6,'https://altavista.com')" );
		$wpdb->query( "INSERT INTO {$wpdb->base_prefix}{$book}_koko_analytics_referrer_stats (`id`,`pageviews`,`visitors`,`date`) VALUES (6,10,10,'$today')" );
		$this->visits->aggregateReferrers($book);
		// added new referrer
		$this->assertEquals('https://altavista.com',$wpdb->get_var("SELECT url FROM {$wpdb->base_prefix}network_aggregated_referrers WHERE blog_id = 2 ORDER BY id DESC LIMIT 1" ));
	}

}
