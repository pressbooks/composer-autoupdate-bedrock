<?php

namespace PressbooksNetworkAnalytics\Model;

use Pressbooks\DataCollector\User;

class UserList {

	/**
	 * @var object[]
	 */
	private $userlist = [];

	// ------------------------------------------------------------------------
	// Pagination & Sorters
	// ------------------------------------------------------------------------

	/**
	 * @var int
	 */
	private $maxPerPage = 25;

	/**
	 * @var int
	 */
	private $currentPage = 1;

	/**
	 * @var int
	 */
	private $lastPage = 1;

	/**
	 * @var int
	 */
	private $rowCount = 1;

	/**
	 * @var array
	 */
	private $sorters = [];

	// ------------------------------------------------------------------------
	// Filters
	// ------------------------------------------------------------------------

	/**
	 * @var \DateTime
	 */
	private $addedSince;

	/**
	 * @var \DateTime
	 */
	private $lastLoggedInBefore;

	/**
	 * @var \DateTime
	 */
	private $lastLoggedInAfter;

	/**
	 * @var string
	 */
	private $isRole;

	/**
	 * @var string
	 */
	private $numberOfBooks;

	/**
	 * @var string
	 */
	private $hasRoleAbove;

	/**
	 * @var string
	 */
	private $fullTextSearch;

	// ------------------------------------------------------------------------
	// Helpers
	// ------------------------------------------------------------------------

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
			if ( $this->isFilteredByPHP() ) {
				// MySQL did not paginate the results, manually paginate ourselves...
				if ( ! empty( $this->sorters ) ) {
					$this->userlist = wp_list_sort( $this->userlist, $this->sorters );
				}
				$this->rowCount = count( $this->userlist );
				$this->lastPage = (int) ceil( $this->rowCount / $this->maxPerPage );
				$this->userlist = array_chunk( $this->userlist, $this->maxPerPage, false ); // Re-index
				$this->userlist = $this->userlist[ $this->currentPage - 1 ];
			}
			return $this->userlist;
		} catch ( \LengthException $e ) {
			$this->rowCount = 0;
			$this->lastPage = 1;
			return [];
		}
	}

	/**
	 * @return int
	 */
	public function getLastPage() {
		return $this->lastPage;
	}

	/**
	 * @return int
	 */
	public function getRowCount() {
		return $this->rowCount;
	}

	/**
	 * @param int $page
	 */
	public function setCurrentPage( $page ) {
		$page = (int) $page;
		if ( $page < 1 ) {
			$page = 1;
		}
		$this->currentPage = $page;
	}

	/**
	 * @param int $max
	 */
	public function setMaxPerPage( $max ) {
		$max = (int) $max;
		if ( $max < 1 ) {
			$max = 1;
		}
		$this->maxPerPage = $max;
	}

	/**
	 * @param array $sorters
	 */
	public function setSorters( array $sorters ) {
		$s = [];
		foreach ( $sorters as $v ) {
			if ( isset( $v['field'], $v['dir'] ) ) {
				$s[ $v['field'] ] = strtoupper( $v['dir'] ) === 'ASC' ? 'ASC' : 'DESC';
			}
		}
		$this->sorters = $s;
	}

	// ------------------------------------------------------------------------
	// Filters
	// ------------------------------------------------------------------------

	/**
	 * @param string $date
	 */
	public function filterAddedSince( $date ) {
		$this->addedSince = date_create( $date );
	}

	/**
	 * @param string $date
	 */
	public function filterLastLoggedInBefore( $date ) {
		$this->lastLoggedInBefore = date_create( $date );
	}

	/**
	 * @param string $date
	 */
	public function filterLastLoggedInAfter( $date ) {
		$this->lastLoggedInAfter = date_create( $date );
	}

	/**
	 * @param string $role
	 * @param int $x
	 */
	public function filterIsRoleInXNumberOfBooks( $role, $x ) {
		$this->isRole = $role;
		$this->numberOfBooks = $x;
	}

	/**
	 * @param string $role
	 */
	public function filterHasRoleAbove( $role ) {
		$this->hasRoleAbove = $role;
	}

	/**
	 * @param string $str
	 */
	public function filterFullTextSearch( $str ) {
		$this->fullTextSearch = trim( $str );
	}

	/**
	 * @param int $user_id
	 * @param string $key
	 * @param string $val
	 */
	public function action( $user_id, $key, $val ) {
		// TODO: Placeholder until we need more bulk actions
	}

	/**
	 * @param array $user_ids
	 * @param string $key
	 * @param string $val
	 */
	public function bulkAction( array $user_ids, $key, $val ) {
		foreach ( $user_ids as $user_id ) {
			$this->action( $user_id, $key, $val );
		}
	}

	// ------------------------------------------------------------------------
	// Private
	// ------------------------------------------------------------------------

	/**
	 * Used to determine if we can add LIMIT to the {$wpdb->users} query
	 *
	 * @return bool
	 */
	private function isFilteredByPHP() {
		// Used to determine if we can add LIMIT to the {$wpdb->users} query
		// If a filter only affects the {$wpdb->users} table then DO NOT ADD IT!
		// If a filter affects the {$wpdb->usermeta} table then add it.
		if (
			$this->lastLoggedInBefore ||
			$this->lastLoggedInAfter ||
			$this->isRole ||
			$this->numberOfBooks ||
			$this->hasRoleAbove
		) {
			return true;
		}
		// When we use wp_list_sort(), don't LIMIT
		if ( ! empty( $this->sorters ) ) {
			return true;
		}
		return false;
	}

	/**
	 * @uses UserList::$userlist
	 * @throws \LengthException
	 */
	private function queryUsersTable() {
		global $wpdb;

		$sql = "SELECT SQL_CALC_FOUND_ROWS ID AS id, user_login AS username, user_email AS email, user_registered AS registered FROM {$wpdb->users} WHERE deleted = 0 AND spam = 0 ";
		if ( $this->addedSince ) {
			$sql .= $wpdb->prepare( 'AND user_registered >= %s ', $this->addedSince->format( 'Y-m-d H:i:s' ) );
		}
		if ( $this->fullTextSearch ) {
			$like = '%' . $wpdb->esc_like( $this->fullTextSearch ) . '%';
			$sql .= $wpdb->prepare( 'AND (user_login LIKE %s OR user_email LIKE %s) ', $like, $like );
		}
		if ( ! $this->isFilteredByPHP() ) {
			// No PHP filters were applied, paginate MySQL results to improve performance
			$offset = ( $this->currentPage - 1 ) * $this->maxPerPage;
			$sql .= $wpdb->prepare( 'LIMIT %d OFFSET %d ', $this->maxPerPage, $offset );
		}
		$this->userlist = $wpdb->get_results( $sql );
		$this->rowCount = (int) $wpdb->get_var( 'SELECT FOUND_ROWS()' );
		$this->lastPage = (int) ceil( $this->rowCount / $this->maxPerPage );

		if ( empty( $this->userlist ) ) {
			throw new \LengthException( 'Unexpected empty result set' );
		}
	}

	/**
	 * @uses UserList::$userlist
	 * @throws \LengthException
	 */
	private function queryUserMetaTable() {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT user_id, meta_key, meta_value FROM {$wpdb->usermeta} WHERE ( meta_key = 'first_name' OR meta_key = 'last_name' OR meta_key REGEXP '({$wpdb->base_prefix}[0-9]+)_capabilities' OR meta_key = %s ) ", User::LAST_LOGIN );
		$sql .= 'AND user_id IN (' . implode( ',', array_column( $this->userlist, 'id' ) ) . ')';
		$users_meta = [];
		foreach ( $wpdb->get_results( $sql ) as $v ) {
			$users_meta[ $v->user_id ][ $v->meta_key ] = $v->meta_value; // @codingStandardsIgnoreLine
		}

		$deleted_books = $this->deletedBooks();

		foreach ( $this->userlist as $key => $user ) {
			// Defaults
			$last_login = '';
			$name = '';
			$total_books = 0;
			$administrator = 0;
			$editor = 0;
			$author = 0;
			$contributor = 0;
			$subscriber = 0;
			if ( isset( $users_meta[ $user->id ] ) ) {
				foreach ( $users_meta[ $user->id ] as $meta_key => $meta_value ) {
					if ( $meta_key === User::LAST_LOGIN ) {
						// Last Login
						$last_login = $meta_value;
					} elseif ( $meta_key === 'first_name' ) {
						// First Name
						$name = $meta_value . $name;
					} elseif ( $meta_key === 'last_name' ) {
						// Last Name
						$name = $name . ' ' . $meta_value;
					} elseif (
						preg_match( "~{$wpdb->base_prefix}(\d+)_capabilities~", $meta_key, $matches ) &&
						array_key_exists( $matches[1], $deleted_books ) === false
					) {
						// Books
						$total_books++;
						$roles = maybe_unserialize( $meta_value );
						if ( is_iterable( $roles ) ) {
							foreach ( $roles as $role => $bool ) {
								$$role++;
							}
						}
					} else {
						continue;
					}
				}
			}
			$this->userlist[ $key ]->lastLogin = $last_login;
			$this->userlist[ $key ]->name = trim( $name );
			$this->userlist[ $key ]->totalBooks = $total_books;
			$this->userlist[ $key ]->administrator = $administrator;
			$this->userlist[ $key ]->editor = $editor;
			$this->userlist[ $key ]->author = $author;
			$this->userlist[ $key ]->contributor = $contributor;
			$this->userlist[ $key ]->subscriber = $subscriber;
		}

		if ( $this->lastLoggedInBefore ) {
			$this->applyLastLoggedInBefore();
		}
		if ( $this->lastLoggedInAfter ) {
			$this->applyLastLoggedInAfter();
		}
		if ( $this->isRole && $this->numberOfBooks ) {
			$this->applyIsRoleInXNumberOfBooks();
		}
		if ( $this->hasRoleAbove ) {
			$this->applyHasRoleAbove();
		}

		if ( empty( $this->userlist ) ) {
			throw new \LengthException( 'Unexpected empty result set' );
		}
	}

	/**
	 * Blog_ids of archived, spam, & deleted books. Flipped.
	 *
	 * @return array
	 */
	private function deletedBooks() {
		global $wpdb;
		$deleted_books = $wpdb->get_col( "SELECT blog_id from {$wpdb->blogs} WHERE archived = 1 OR spam = 1 OR deleted = 1 " );
		if ( is_array( $deleted_books ) ) {
			$deleted_books = array_flip( $deleted_books );
		} else {
			$deleted_books = [];
		}
		return $deleted_books;
	}

	/**
	 * Last logged in before
	 *
	 * @uses UserList::$userlist
	 */
	private function applyLastLoggedInBefore() {
		foreach ( $this->userlist as $key => $user ) {
			if ( empty( $user->lastLogin ) ) {
				unset( $this->userlist[ $key ] );
			} elseif ( date_create( $user->lastLogin ) > $this->lastLoggedInBefore ) {
				unset( $this->userlist[ $key ] );
			}
		}
	}

	/**
	 * Last logged in after
	 *
	 * @uses UserList::$userlist
	 */
	private function applyLastLoggedInAfter() {
		foreach ( $this->userlist as $key => $user ) {
			if ( empty( $user->lastLogin ) ) {
				unset( $this->userlist[ $key ] );
			} elseif ( date_create( $user->lastLogin ) < $this->lastLoggedInAfter ) {
				unset( $this->userlist[ $key ] );
			}
		}
	}

	/**
	 * Is X in Y number of books
	 *
	 * @uses UserList::$userlist
	 */
	private function applyIsRoleInXNumberOfBooks() {
		foreach ( $this->userlist as $key => $user ) {
			if ( isset( $user->{$this->isRole} ) ) {
				if ( $this->numberOfBooks > $user->{$this->isRole} ) {
					unset( $this->userlist[ $key ] );
				}
			}
		}
	}

	/**
	 * Filter out all users without at least one book in which they are role X
	 *
	 * @uses UserList::$userlist
	 */
	private function applyHasRoleAbove() {
		$check_against = $this->summaryOfRoles[ $this->hasRoleAbove ] ?? 0;
		$above = array_filter(
			$this->summaryOfRoles, function ( $value ) use ( $check_against ) {
				return ( (int) $value >= $check_against );
			}
		);
		foreach ( $this->userlist as $key => $user ) {
			$ok = false;
			foreach ( $above as $role => $weight ) {
				if ( isset( $user->{$role} ) && $user->{$role} ) {
					$ok = true;
					break;
				}
			}
			if ( ! $ok ) {
				unset( $this->userlist[ $key ] );
			}
		}
	}

}
