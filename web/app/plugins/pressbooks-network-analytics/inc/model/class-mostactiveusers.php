<?php

namespace PressbooksNetworkAnalytics\Model;

use function Pressbooks\Utility\str_lreplace;

class MostActiveUsers {

	/**
	 * @var object[]
	 */
	private $userlist = [];

	/**
	 * @var int
	 */
	private $maxPerPage = 100;

	// ------------------------------------------------------------------------
	// Public
	// ------------------------------------------------------------------------

	/**
	 * Constructor
	 */
	public function __construct() {
	}

	/**
	 * @return array
	 */
	public function get() {
		try {
			$this->queryUsersTable();
			$this->queryUserMetaTable();
			$sort['revisionsYear'] = 'DESC';
			$this->userlist = wp_list_sort( $this->userlist, $sort );
			$this->userlist = array_chunk( $this->userlist, $this->maxPerPage, false ); // Re-index
			return $this->userlist[0];
		} catch ( \LengthException $e ) {
			return [];
		}
	}

	// ------------------------------------------------------------------------
	// Private
	// ------------------------------------------------------------------------

	/**
	 * @uses MostActiveUsers::$userlist
	 * @throws \LengthException
	 */
	private function queryUsersTable() {
		global $wpdb;
		$sql = "SELECT ID AS id, user_login as username FROM {$wpdb->users} WHERE deleted = 0 AND spam = 0 ";
		$this->userlist = $wpdb->get_results( $sql );

		if ( empty( $this->userlist ) ) {
			throw new \LengthException( 'Unexpected empty result set' );
		}
	}

	/**
	 * @uses MostActiveUsers::$userlist
	 * @throws \LengthException
	 */
	private function queryUserMetaTable() {
		global $wpdb;
		$sql = "SELECT user_id, meta_key, meta_value FROM {$wpdb->usermeta} WHERE ( meta_key REGEXP '({$wpdb->base_prefix}[0-9]+)_capabilities' ) ";
		$sql .= 'AND user_id IN (' . implode( ',', array_column( $this->userlist, 'id' ) ) . ')';

		$users_meta = [];
		foreach ( $wpdb->get_results( $sql ) as $v ) {
			$users_meta[ $v->user_id ][ $v->meta_key ] = $v->meta_value; // @codingStandardsIgnoreLine
		}

		$regex = "~{$wpdb->base_prefix}(\d+)_capabilities~";
		foreach ( $this->userlist as $key => $user ) {
			if ( isset( $users_meta[ $user->id ] ) ) {
				$union = [];
				foreach ( $users_meta[ $user->id ] as $meta_key => $meta_value ) {
					if (
						preg_match( $regex, $meta_key, $matches ) && // Has wp_<id>_capabilities
						strpos( $meta_value, 's:10:"subscriber";b:1;' ) === false // Optimization: Exclude subscribers
					) {
						$union[] = $matches[1];
					}
				}
				$revisions = $this->revisions( $user->id, $union );
				if (
					! $revisions['revisionsWeek'] &&
					! $revisions['revisionsMonth'] &&
					! $revisions['revisionsThreeMonths'] &&
					! $revisions['revisionsYear']
				) {
					// Remove users without contributions
					unset( $this->userlist[ $key ] );
				} else {
					$this->userlist[ $key ]->revisionsWeek = $revisions['revisionsWeek'];
					$this->userlist[ $key ]->revisionsMonth = $revisions['revisionsMonth'];
					$this->userlist[ $key ]->revisionsThreeMonths = $revisions['revisionsThreeMonths'];
					$this->userlist[ $key ]->revisionsYear = $revisions['revisionsYear'];
				}
			} else {
				unset( $this->userlist[ $key ] );
			}
		}

		if ( empty( $this->userlist ) ) {
			throw new \LengthException( 'Unexpected empty result set' );
		}
	}

	/**
	 * Number of revisions made
	 *
	 * @param int $user_id
	 * @param int[] $blog_ids
	 *
	 * @return array
	 */
	private function revisions( $user_id, $blog_ids ) {
		$week = 0;
		$month = 0;
		$three_months = 0;
		$year = 0;
		if ( ! empty( $blog_ids ) ) {
			// Create SQL sub-queries
			global $wpdb;
			$sql_week = '';
			$sql_month = '';
			$sql_three_months = '';
			$sql_year = '';
			foreach ( $blog_ids as $blog_id ) {
				$posts_table = "{$wpdb->prefix}{$blog_id}_posts";
				$sql_year .= $wpdb->prepare( "( SELECT COUNT(*) FROM {$posts_table} WHERE post_author = %d AND post_modified_gmt > (DATE(NOW()) - INTERVAL 1 YEAR) ) + ", $user_id );
				$sql_three_months .= $wpdb->prepare( "( SELECT COUNT(*) FROM {$posts_table} WHERE post_author = %d AND post_modified_gmt > (DATE(NOW()) - INTERVAL 3 MONTH) ) + ", $user_id );
				$sql_month .= $wpdb->prepare( "( SELECT COUNT(*) FROM {$posts_table} WHERE post_author = %d AND post_modified_gmt > (DATE(NOW()) - INTERVAL 1 MONTH) ) + ", $user_id );
				$sql_week .= $wpdb->prepare( "( SELECT COUNT(*) FROM {$posts_table} WHERE post_author = %d AND post_modified_gmt > (DATE(NOW()) - INTERVAL 1 WEEK) ) + ", $user_id );
			}
			$sql_year = str_lreplace( ' ) + ', ' ) ', $sql_year );
			$sql_three_months = str_lreplace( ' ) + ', ' ) ', $sql_three_months );
			$sql_month = str_lreplace( ' ) + ', ' ) ', $sql_month );
			$sql_week = str_lreplace( ' ) + ', ' ) ', $sql_week );

			// Optimization: Only run next query if they have at revisions in the previous query
			$year = $wpdb->get_var( "SELECT {$sql_year} AS total" );
			if ( $year ) {
				$three_months = $wpdb->get_var( "SELECT {$sql_three_months} AS total" );
				if ( $three_months ) {
					$month = $wpdb->get_var( "SELECT {$sql_month} AS total" );
					if ( $month ) {
						$week = $wpdb->get_var( "SELECT {$sql_week} AS total" );
					}
				}
			}
		}
		return [
			'revisionsYear' => $year,
			'revisionsThreeMonths' => $three_months,
			'revisionsMonth' => $month,
			'revisionsWeek' => $week,
		];
	}

}
