<?php

namespace PressbooksNetworkAnalytics\Collector;

/**
 * DataCollector for visits
 */
class Visits {
	/**
	 * Database collation
	 */
	public const CHARSET = 'latin1';

	/**
	 * This table is the aggregated number of visits for all the sites.
	 * @const string
	 */
	public const VISITS_TABLE = 'network_aggregated_stats';

	/**
	 * This table is the aggregated number of referrers for all the sites.
	 * @const string
	 */
	public const REFERER_TABLE = 'network_aggregated_referrers';

	public static $IS_TESTING = false;

	public $today = '';

	public function __construct() {
		$this->today = $date = gmdate( 'Y-m-d', time() + get_option( 'gmt_offset' ) * 3600 );
	}

	/**
	 * @param $network_wide
	 */
	public static function install( $network_wide ) {
		if ( $network_wide && is_multisite() ) { // only run on network activation
			self::createTables();
		}
	}

	/**
	 *
	 */
	public static function checkInstallation() {
		global $wpdb;
		$aggregated_table = $wpdb->base_prefix . self::VISITS_TABLE;
		// check if the table exists for the aggregated stats
		if ( is_multisite() && $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$aggregated_table
			)
		) !== $aggregated_table ) {
					self::install( true );
		}
	}

	/**
	 * This function creates the tables for the aggregated stats.
	 */
	public static function createTables() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;

		$engine = 'ENGINE=InnoDB DEFAULT CHARSET=' . self::CHARSET;
		$visitors_table = $wpdb->base_prefix . self::VISITS_TABLE;
		$referrers_table = $wpdb->base_prefix . self::REFERER_TABLE;

		dbDelta(
			"CREATE TABLE IF NOT EXISTS {$visitors_table} (
				  id int(10) unsigned NOT NULL AUTO_INCREMENT,
				  blog_id int(10) unsigned NOT NULL,
				  visitors int(10) unsigned NOT NULL,
				  pageviews int(10) unsigned NOT NULL,
				  created_at date NOT NULL,
				  PRIMARY KEY  (id)
			) {$engine};"
		);

		dbDelta(
			"CREATE TABLE IF NOT EXISTS {$referrers_table} (
				  id int(10) unsigned NOT NULL AUTO_INCREMENT,
				  blog_id int(10) unsigned NOT NULL,
				  visitors int(10) unsigned NOT NULL,
				  pageviews int(10) unsigned NOT NULL,
				  url varchar(150) NOT NULL,
				  created_at date NOT NULL,
				  PRIMARY KEY  (id)
			) {$engine};"
		);

		( new self )->populatePreviousData(); // populate previous data only on network activation or if the tables are empty

	}

	/**
	 * This function updates the table with the historical stats.
	 */
	public function populatePreviousData() {
		foreach ( $this->getAllBooks() as $blog_id ) {
			$this->aggregateVisits( $blog_id, true );
			$this->aggregateReferrers( $blog_id, true );
		}
	}

	/**
	 * Returns a list of all the blogs in the network.
	 * @return array
	 */
	public function getAllBooks() : array {
		global $wpdb;
		$main_site_id = get_network()->site_id;
		return $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM {$wpdb->blogs} WHERE archived = 0 AND spam = 0 AND blog_id != %d ", $main_site_id ) );
	}

	/**
	 * This function needs to be called with the WP Cron job for each site (maybe the cron loop).
	 *
	 * @param $blog_id
	 * @param  bool  $since_beginning
	 */
	public function aggregateVisits( $blog_id, $since_beginning = false ) {
		global $wpdb;
		$source_table = $wpdb->base_prefix . $blog_id . '_koko_analytics_site_stats';
		$aggregated_table = $wpdb->base_prefix . self::VISITS_TABLE;
		$where = '';

		if ( self::$IS_TESTING || $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $source_table ) ) === $source_table ) { // check if koko table exists
			// delete today's data if exists
			if ( ! $since_beginning ) {
				$this->purgeOldData( $blog_id, $aggregated_table );
				$where = 'WHERE `date` = "' . $this->today . '"';
			}

			$sql = "INSERT INTO {$aggregated_table} (blog_id, visitors, pageviews, created_at)
				SELECT {$blog_id}, visitors, pageviews, `date` FROM {$source_table} $where";

			$wpdb->query( $sql );
		}
	}

	/**
	 * This function needs to be called with the WP Cron job for each site (maybe the cron loop).
	 *
	 * @param $blog_id
	 * @param  bool  $since_beginning
	 */
	public function aggregateReferrers( $blog_id, $since_beginning = false ) {
		global $wpdb;
		$source_table = $wpdb->base_prefix . $blog_id . '_koko_analytics_referrer_stats';
		$source_url_table = $wpdb->base_prefix . $blog_id . '_koko_analytics_referrer_urls';
		$aggregated_table = $wpdb->base_prefix . self::REFERER_TABLE;
		$where = '';

		// delete today's data if exists
		if ( ! $since_beginning ) {
			$this->purgeOldData( $blog_id, $aggregated_table );
			$where = 'WHERE `date` = "' . $this->today . '"';
		}

		if ( self::$IS_TESTING || $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $source_table ) ) === $source_table ) { // check if koko table exists
			$sql = "INSERT INTO {$aggregated_table} (blog_id, visitors, pageviews, url, created_at)
				SELECT {$blog_id}, visitors, pageviews, u.url, s.date FROM {$source_table} s JOIN $source_url_table u ON s.id = u.id $where";

			$result = $wpdb->query( $sql );
		}

	}

	/**
	 * This function will purge today's book data from the aggregated tables, useful if the cron runs more than 1 time per day.
	 * @param $blog_id
	 * @param $table
	 */
	private function purgeOldData( $blog_id, $table ) {
		// delete today's data if it exists
		global $wpdb;
		$wpdb->delete(
			$table,
			[
				'blog_id' => $blog_id,
				'created_at' => $this->today,
			],
			[
				'%d',
				'%s',
			]
		);
	}

}
