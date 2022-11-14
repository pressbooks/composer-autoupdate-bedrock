<?php

namespace PressbooksNetworkAnalytics\Model;

class UsersOverTime {

	/**
	 * @var object[]
	 */
	private $userlist = [];

	/**
	 * @var array role => weight
	 */
	private $summaryOfRoles = [
		'subscriber' => 10,
		'contributor' => 20,
		'author' => 30,
		'editor' => 40,
		'administrator' => 50,
	];

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
			return $this->groupIntoDataSet();
		} catch ( \LengthException $e ) {
			return [];
		}
	}

	// ------------------------------------------------------------------------
	// Private
	// ------------------------------------------------------------------------

	/**
	 * @uses UsersOverTime::$userlist
	 * @throws \LengthException
	 */
	private function queryUsersTable() {
		global $wpdb;
		$sql = "SELECT ID AS id, user_registered FROM {$wpdb->users} WHERE deleted = 0 AND spam = 0 ORDER BY user_registered ";
		$this->userlist = $wpdb->get_results( $sql );
		if ( empty( $this->userlist ) ) {
			throw new \LengthException( 'Unexpected empty result set' );
		}
	}

	/**
	 * @uses UsersOverTime::$userlist
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
			$highest_role = 'none';
			$highest_role_weight = 0;
			if ( isset( $users_meta[ $user->id ] ) ) {
				foreach ( $users_meta[ $user->id ] as $meta_key => $meta_value ) {
					if ( preg_match( $regex, $meta_key, $matches ) ) {
						$roles = maybe_unserialize( $meta_value );
						if ( is_iterable( $roles ) ) {
							foreach ( $roles as $role => $bool ) {
								if ( $this->summaryOfRoles[ $role ] ?? 0 > $highest_role_weight ) {
									$highest_role_weight = $this->summaryOfRoles[ $role ];
									$highest_role = $role;
								}
							}
						}
					}
				}
			}
			$this->userlist[ $key ]->highest_role = $highest_role;
		}

		if ( empty( $this->userlist ) ) {
			throw new \LengthException( 'Unexpected empty result set' );
		}
	}

	/**
	 * @return array
	 */
	private function groupIntoDataSet() {

		// IMPORTANT! $this->userlist is expected to be ORDER(ed) BY user_registered
		$grouped = [];
		foreach ( $this->userlist as $user ) {
			$year_month_1 = substr( $user->user_registered, 0, 7 ) . '-01';
			$grouped[ $year_month_1 ][ $user->id ] = $user->highest_role;
		}

		$total_users = 0;
		$total_subscribers = 0;
		$total_contributors = 0;
		$calculations = [];
		foreach ( $grouped as $year_month_1 => $arr ) {
			$total_users += count( $arr );
			foreach ( $arr as $highest_role ) {
				if ( $highest_role === 'subscriber' ) {
					$total_subscribers++;
				} else {
					$total_contributors++;
				}
			}
			$calculations[ $year_month_1 ] = [
				'totalUsers' => $total_users,
				'totalSubscribers' => $total_subscribers,
				'totalContributors' => $total_contributors,
			];
		}
		if ( empty( $calculations ) ) {
			return [];
		}

		// Fix gaps in between missing months
		reset( $calculations );
		$first_date = key( $calculations );
		$i = new \DateInterval( 'P1M' );
		$period = new \DatePeriod( date_create( $first_date ), $i, date_create( date( 'Y-m-t' ) ) );
		$dataset = [];
		$current_calculation = [];
		foreach ( $period as $d ) {
			$year_month_1 = $d->format( 'Y-m-d' );
			if ( isset( $calculations[ $year_month_1 ] ) ) {
				$current_calculation = $calculations[ $year_month_1 ];
			}
			$current_calculation['date'] = $year_month_1;
			$current_calculation['dateLabel'] = date( 'M Y', strtotime( $year_month_1 ) );
			$dataset[] = $current_calculation;
		}
		return $dataset;
	}
}
