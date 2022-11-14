<?php

use Pressbooks\DataCollector\Book;
use Pressbooks\DataCollector\User;
use PressbooksNetworkAnalytics\Collector\Network;

/**
 * @group networkcollector
 */
class Collector_NetworkTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var Network
	 */
	protected $network;

	/**
	 * @var int
	 */
	protected $total_books = 5;

	/**
	 * @var int
	 */
	protected $total_users = 7;

	public function set_up() {
		parent::set_up();
		$this->populateMetadata();
		Network::install( true  );
		$this->network = new Network();
	}

	private function populateMetadata() {
		$total_books = $this->total_books;
		$total_users = $this->total_users;
		$roles = [
			'none',
			'subscriber',
			'contributor',
			'author',
			'editor',
			'administrator',
		];
		while( $total_users > 0 ) {
			$user = $this->factory()->user->create_and_get( [ 'role' => array_rand( array_flip( $roles ), 1 ) ]  );
			$activity_date = rand( ( time() - ( 2592000 ) ), time()  ); // Random time between 1 month ago and now
			update_user_meta( $user->ID, User::USER_DATE_LAST_ACTIVE, gmdate( 'Y-m-d H:i:s', $activity_date ) );
			$total_users --;
		}
		while( $total_books > 0 ) {
			$this->_book();
			$book_id = get_current_blog_id();
			update_site_meta( $book_id, Book::PUBLIC, rand( 0, 1 ) );
			update_site_meta( $book_id, Book::IS_CLONE, rand( 0, 1 ) );
			update_site_meta( $book_id, Book::IN_CATALOG, rand( 0, 1 ) );
			update_site_meta( $book_id, Book::LTI_GRADING_ENABLED, rand( 0, 1 ) );
			update_site_meta( $book_id, Book::STORAGE_SIZE, rand( 5000, 150000 ) );
			$total_books --;
		}
	}

	public function test_getBooksStats() {
		$meta_keys = [
			Book::PUBLIC,
			Book::IS_CLONE,
			Book::IN_CATALOG,
			Book::LTI_GRADING_ENABLED,
		];
		$books_stats = $this->network->getBooksStats( $meta_keys );
		$this->assertLessThanOrEqual( count( $meta_keys ) * 2, count( $books_stats ) );
		foreach ( $books_stats as $stat ) {
			$this->assertContains( $stat->meta_key, $meta_keys );
			$this->assertLessThanOrEqual( 1, intval( $stat->meta_value ) );
		}
	}

	public function test_getLastActiveUsers() {
		$active_users = $this->network->getLastActiveUsers();
		$this->assertLessThanOrEqual( $this->total_users, $active_users->daily );
		$this->assertLessThanOrEqual( $this->total_users, $active_users->weekly );
		$this->assertLessThanOrEqual( $this->total_users, $active_users->monthly );
	}

	public function test_getTotalStorage() {
		$storage = intval( $this->network->getTotalStorage() );
		$this->assertGreaterThanOrEqual( 0, $storage );
	}

	public function test_populateTable() {
		$this->network->populateTable();
		global $wpdb;
		$table_name = $wpdb->base_prefix . Network::TABLE_NAME;
		$sql = "SELECT * FROM {$table_name}";
		$result = $wpdb->get_results( $sql)[0];
		$now = gmdate( 'U' );
		$date_record = strtotime( $result->date );
		$this->assertLessThanOrEqual( $now, $date_record );
		$this->assertEquals( $result->{Network::PRIVATE_BOOKS_FIELD} + $result->{Network::PUBLIC_BOOKS_FIELD}, $this->total_books );
		$this->assertLessThanOrEqual(
			$result->{Network::DAILY_ACTIVE_USERS_FIELD} + $result->{Network::WEEKLY_ACTIVE_USERS_FIELD} + $result->{Network::MONTHLY_ACTIVE_USERS_FIELD},
			$this->total_users
		);
	}
}
