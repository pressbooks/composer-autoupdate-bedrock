<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace PressbooksNetworkAnalytics\Collector;

use Pressbooks\DataCollector\Book;
use Pressbooks\DataCollector\User;

class Network {
	const TABLE_NAME = 'pressbooks_network_stats';
	const DATE_FIELD = 'date';
	const NETWORK_FIELD = 'network_url';
	const PUBLIC_BOOKS_FIELD = 'public_books';
	const PRIVATE_BOOKS_FIELD = 'private_books';
	const CLONED_BOOKS_FIELD = 'cloned_books';
	const BOOKS_IN_CATALOG_FIELD = 'books_in_catalog';
	const BOOKS_ALLOW_GRADING_FIELD = 'books_allow_grading';
	const USERS_FIELD = 'users';
	const USERS_COLLAB_OR_HIGHER_FIELD = 'users_collab_or_higher';
	const DAILY_ACTIVE_USERS_FIELD = 'daily_active_users';
	const WEEKLY_ACTIVE_USERS_FIELD = 'weekly_active_users';
	const MONTHLY_ACTIVE_USERS_FIELD = 'monthly_active_users';
	const STORAGE_FIELD = 'storage';

	/**
	 * @var int
	 */
	private $date;

	/**
	 * @var string
	 */
	private $network_url;

	/**
	 * @var int
	 */
	private $private_books;

	/**
	 * @var int
	 */
	private $public_books;

	/**
	 * @var int
	 */
	private $cloned_books;

	/**
	 * @var int
	 */
	private $books_in_catalog;

	/**
	 * @var int
	 */
	private $books_allow_grading;

	/**
	 * @var int
	 */
	private $users;

	/**
	 * @var int
	 */
	private $users_collab_higher;

	/**
	 * @var int
	 */
	private $daily_active_users;

	/**
	 * @var int
	 */
	private $weekly_active_users;

	/**
	 * @var int
	 */
	private $monthly_active_users;

	/**
	 * @var int
	 */
	private $storage;

	/**
	 * Create network stats table if not present
	 *
	 * @return void
	 */
	public static function install() {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_NAME;
		if (
			is_multisite() &&
			$wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table
		) {
			self::createTable();
		}
	}

	/**
	 * Create network stats table
	 *
	 * @return void
	 */
	public static function createTable() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;

		// BI stats data table, see: https://github.com/pressbooks/private/issues/697
		$table_name = $wpdb->base_prefix . self::TABLE_NAME;

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				" . self::DATE_FIELD . ' timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				' . self::NETWORK_FIELD . ' varchar(256) NOT NULL,
				' . self::PUBLIC_BOOKS_FIELD . ' int(10) NOT NULL,
				' . self::PRIVATE_BOOKS_FIELD . ' int(10) NOT NULL,
				' . self::CLONED_BOOKS_FIELD . ' int(10) NOT NULL,
				' . self::BOOKS_IN_CATALOG_FIELD . ' int(10) NOT NULL,
				' . self::BOOKS_ALLOW_GRADING_FIELD . ' int(10) NOT NULL,
				' . self::USERS_FIELD . ' int(10) NOT NULL,
				' . self::USERS_COLLAB_OR_HIGHER_FIELD . ' int(10) NOT NULL,
				' . self::DAILY_ACTIVE_USERS_FIELD . ' int(10) NOT NULL,
				' . self::WEEKLY_ACTIVE_USERS_FIELD . ' int(10) NOT NULL,
				' . self::MONTHLY_ACTIVE_USERS_FIELD . ' int(10) NOT NULL,
				' . self::STORAGE_FIELD . ' int(10) NOT NULL,
				PRIMARY KEY  (id)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;';

		dbDelta( $sql );
	}

	/**
	 * Populate blogmeta and usermeta required data into the new network stats table.
	 *
	 * @return bool|int
	 */
	public function populateTable() {
		$this->getStats();

		global $wpdb;
		$table_name = $wpdb->base_prefix . self::TABLE_NAME;
		$columns_values = [
			self::NETWORK_FIELD => $this->network_url,
			self::PUBLIC_BOOKS_FIELD => $this->public_books,
			self::PRIVATE_BOOKS_FIELD => $this->private_books,
			self::CLONED_BOOKS_FIELD => $this->cloned_books,
			self::BOOKS_IN_CATALOG_FIELD => $this->books_in_catalog,
			self::BOOKS_ALLOW_GRADING_FIELD => $this->books_allow_grading,
			self::USERS_FIELD => $this->users,
			self::USERS_COLLAB_OR_HIGHER_FIELD => $this->users_collab_higher,
			self::DAILY_ACTIVE_USERS_FIELD => $this->daily_active_users,
			self::WEEKLY_ACTIVE_USERS_FIELD => $this->weekly_active_users,
			self::MONTHLY_ACTIVE_USERS_FIELD => $this->monthly_active_users,
			self::STORAGE_FIELD => $this->storage,
		];
		$keys = array_keys( $columns_values );
		$sql_columns = '(' . implode( ', ', $keys ) . ')';
		$sql_values = '(' . implode( ',', array_fill( 0, count( $keys ), '%s' ) ) . ')';
		$sql = "INSERT INTO {$table_name} {$sql_columns} VALUES {$sql_values}";
		return $wpdb->query( $wpdb->prepare( $sql, array_values( $columns_values ) ) );
	}

	/**
	 * Fill properties with blogmeta and usermeta required data
	 *
	 * @return void
	 */
	public function getStats() {
		$scheme = is_ssl() ? 'https' : 'http';
		$this->network_url = network_home_url( '', $scheme );
		$book_stats = $this->getBooksStats( [
			Book::PUBLIC,
			Book::IS_CLONE,
			Book::IN_CATALOG,
			Book::LTI_GRADING_ENABLED,
		] );
		$this->public_books = 0;
		$this->private_books = 0;
		$this->cloned_books = 0;
		$this->books_in_catalog = 0;
		$this->books_allow_grading = 0;
		foreach ( $book_stats as $stat ) {
			$stat->meta_value = (int) $stat->meta_value;
			switch ( $stat->meta_key ) {
				case Book::PUBLIC:
					if ( $stat->meta_value === 1 ) {
						$this->public_books = $stat->total;
					} else {
						$this->private_books = $stat->total;
					}
					break;
				case Book::IS_CLONE:
					if ( $stat->meta_value === 1 ) {
						$this->cloned_books = $stat->total;
					}
					break;
				case Book::IN_CATALOG:
					if ( $stat->meta_value === 1 ) {
						$this->books_in_catalog = $stat->total;
					}
					break;
				case Book::LTI_GRADING_ENABLED:
					if ( $stat->meta_value === 1 ) {
						$this->books_allow_grading = $stat->total;
					}
					break;
			}
		}
		$this->users = 0;
		$this->users_collab_higher = 0;
		$users = $this->getCountUsersAndByRoles( [ 'administrator', 'author', 'editor', 'contributor' ] );
		if ( $users ) {
			$this->users = $users->total_users;
			$this->users_collab_higher = $users->total_users_roles_included;
		}
		$active_users = $this->getLastActiveUsers();
		$this->daily_active_users = $active_users->daily ?? 0;
		$this->weekly_active_users = $active_users->weekly ?? 0;
		$this->monthly_active_users = $active_users->monthly ?? 0;
		$this->storage = $this->getTotalStorage();
	}

	/**
	 * Get blogmeta required data
	 *
	 * @param array $meta_keys
	 * @return array|object|null
	 */
	public function getBooksStats( array $meta_keys ) {
		global $wpdb;
		$in_clause = array_fill( 0, count( $meta_keys ), '%s' );
		$in_clause_string = implode( ', ', $in_clause );
		$prepare_args = $meta_keys;
		$prepare_args[] = get_network()->site_id;
		$sql = "SELECT meta_key, meta_value, COUNT(*) AS total FROM {$wpdb->blogmeta} WHERE meta_key IN ({$in_clause_string}) AND blog_id != %s GROUP BY meta_key, meta_value";
		return $wpdb->get_results( $wpdb->prepare( $sql, $prepare_args ) );
	}

	/**
	 * Get total users independently of the roles and total users according to the roles included in $roles_included array
	 *
	 * @param array $roles_included - ['administrator', 'collaborator', ...]
	 * @return false|mixed - Object keys: 'total_users' and 'total_users_roles_included'
	 */
	public function getCountUsersAndByRoles( array $roles_included ) {
		global $wpdb;
		$in_clause = implode( ', ', array_fill( 0, count( $roles_included ), '%s' ) );
		$meta_key_highest_role = User::HIGHEST_ROLE;
		$sql = "SELECT COUNT(1) AS total_users,
			COUNT(CASE WHEN um.meta_value IN ({$in_clause}) THEN 1 END) AS total_users_roles_included
			FROM
				{$wpdb->users} u
				LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
				AND um.meta_key = '{$meta_key_highest_role}'";
		return $wpdb->get_row( $wpdb->prepare( $sql, $roles_included ) );
	}

	/**
	 * Get last active users daily, weekly and monthly.
	 *
	 * @return mixed
	 */
	public function getLastActiveUsers() {
		global $wpdb;
		$last_active_date_field = User::USER_DATE_LAST_ACTIVE;
		$sql = "
			 SELECT COUNT(IF( UNIX_TIMESTAMP(meta_value) BETWEEN UNIX_TIMESTAMP(subdate(current_date, 1)) AND UNIX_TIMESTAMP(), 1, NULL )) as daily,
				COUNT(IF( UNIX_TIMESTAMP(meta_value) BETWEEN UNIX_TIMESTAMP(subdate(current_date, 7)) AND UNIX_TIMESTAMP(), 1, NULL )) as weekly,
				COUNT(IF( UNIX_TIMESTAMP(meta_value) BETWEEN UNIX_TIMESTAMP(subdate(current_date, 30)) AND UNIX_TIMESTAMP(), 1, NULL )) as monthly
				FROM {$wpdb->usermeta}
				WHERE meta_key = '{$last_active_date_field}'
		";
		return $wpdb->get_row( $sql );
	}

	/**
	 * Get summarized book storage from blogmeta table.
	 *
	 * @return mixed
	 */
	public function getTotalStorage() {
		global $wpdb;
		$storage_key = Book::STORAGE_SIZE;
		$blog_id = get_network()->site_id;
		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(meta_value) FROM {$wpdb->blogmeta} WHERE meta_key = '{$storage_key}' AND blog_id != %s",
				$blog_id
			)
		);
	}
}
