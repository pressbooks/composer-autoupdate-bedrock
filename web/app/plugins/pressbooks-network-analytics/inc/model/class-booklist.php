<?php

namespace PressbooksNetworkAnalytics\Model;

use function PressbooksNetworkAnalytics\is_truthy;
use function Pressbooks\Metadata\get_in_catalog_option;
use function Pressbooks\Utility\str_lreplace;
use Pressbooks\Admin\Network\SharingAndPrivacyOptions;
use Pressbooks\BookDirectory;
use Pressbooks\DataCollector\Book;

class BookList {

	/**
	 * @var array
	 */
	private $booklist = [];

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
	private $rowCount = 0;

	/**
	 * @var array
	 */
	private $sorters = [];

	// ------------------------------------------------------------------------
	// Filters
	// ------------------------------------------------------------------------

	/**
	 * @var bool
	 */
	private $isPublic;

	/**
	 * @var bool
	 */
	private $isClone;

	/**
	 * @var int
	 */
	private $wordCount;

	/**
	 * @var string
	 */
	private $wordCountSymbol;

	/**
	 * @var int
	 */
	private $storageSize;

	/**
	 * @var string
	 */
	private $storageSizeSymbol;

	/**
	 * @var string|array
	 */
	private $currentLicense;

	/**
	 * @var string|array
	 */
	private $currentTheme;

	/**
	 * @var string|array
	 */
	private $bookLanguage;

	/**
	 * @var string|array
	 */
	private $bookSubject;

	/**
	 * @var bool
	 */
	private $hasExports;

	/**
	 * @var bool
	 */
	private $allowsDownloads;

	/**
	 * @var string|array
	 */
	private $exportsByFormat;

	/**
	 * @var \DateTime
	 */
	private $lastExport;

	/**
	 * @var \DateTime
	 */
	private $lastEdited;

	/**
	 * @var bool
	 */
	private $akismetActivated;

	/**
	 * @var bool
	 */
	private $parsedownPartyActivated;

	/**
	 * @var bool
	 */
	private $wpQuicklatexActivated;

	/**
	 * @var int
	 */
	private $glossaryTerms;

	/**
	 * @var string
	 */
	private $glossaryTermsSymbol;

	/**
	 * @var int
	 */
	private $h5pActivities;

	/**
	 * @var string
	 */
	private $h5pActivitiesSymbol;

	/**
	 * @var int
	 */
	private $tablepressTables;

	/**
	 * @var string
	 */
	private $tablepressTablesSymbol;

	/**
	 * @var string
	 */
	private $fullTextSearch;

	/**
	 * @var array
	 */
	private $blogIdsExcluded;

	// ------------------------------------------------------------------------
	// Helpers
	// ------------------------------------------------------------------------

	/**
	 * @var array
	 */
	private $sqlWhere = [];

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
			$this->queryBlogMetaTable();
			return $this->booklist;
		} catch ( \LengthException $e ) {
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
	 * @param bool $is_public
	 */
	public function filterIsPublic( $is_public ) {
		$this->isPublic = (bool) $is_public;
	}

	/**
	 * @param bool $is_clone
	 */
	public function filterIsClone( $is_clone ) {
		$this->isClone = (bool) $is_clone;
	}

	/**
	 * @param int $count
	 * @param string $symbol
	 */
	public function filterWordCount( $count, $symbol = '>' ) {
		$this->wordCount = (int) $count;
		$this->wordCountSymbol = $symbol;
	}

	/**
	 * @param int $bytes
	 * @param string $symbol
	 */
	public function filterStorageSize( $bytes, $symbol = '>' ) {
		$this->storageSize = (int) $bytes;
		$this->storageSizeSymbol = $symbol;
	}

	/**
	 * @param string|array $license
	 */
	public function filterCurrentLicense( $license ) {
		$this->currentLicense = $license;
	}

	/**
	 * @param string|array $theme
	 */
	public function filterCurrentTheme( $theme ) {
		$this->currentTheme = $theme;
	}

	/**
	 * @param string|array $lang
	 */
	public function filterBookLanguage( $lang ) {
		$this->bookLanguage = $lang;
	}

	/**
	 * @param string|array $subject
	 */
	public function filterBookSubject( $subject ) {
		$this->bookSubject = $subject;
	}

	/**
	 * @param bool $has_exports
	 */
	public function filterHasExports( $has_exports ) {
		$this->hasExports = (bool) $has_exports;
	}

	/**
	 * @param bool $allows_downloads
	 */
	public function filterAllowsDownloads( $allows_downloads ) {
		$this->allowsDownloads = (bool) $allows_downloads;
	}

	/**
	 * @param string|array $subject
	 */
	public function filterExportsByFormat( $format ) {
		$this->exportsByFormat = $format;
	}

	/**
	 * @param string $date
	 */
	public function filterLastExport( $date ) {
		$this->lastExport = date_create( $date );
	}

	/**
	 * @param string $date
	 */
	public function filterLastEdited( $date ) {
		$this->lastEdited = date_create( $date );
	}

	/**
	 * @param bool $akismet_activated
	 */
	public function filterAkismetActivated( $akismet_activated ) {
		$this->akismetActivated = (bool) $akismet_activated;
	}

	/**
	 * @param bool $parsedown_party_activated
	 */
	public function filterParsedownPartyActivated( $parsedown_party_activated ) {
		$this->parsedownPartyActivated = (bool) $parsedown_party_activated;
	}

	/**
	 * @param bool $wp_quicklatex_activated
	 */
	public function filterWpQuicklatexActivated( $wp_quicklatex_activated ) {
		$this->wpQuicklatexActivated = (bool) $wp_quicklatex_activated;
	}

	/**
	 * @param int $count
	 * @param string $symbol
	 */
	public function filterGlossaryTerms( $count, $symbol = '>' ) {
		$this->glossaryTerms = (int) $count;
		$this->glossaryTermsSymbol = $symbol;
	}

	/**
	 * @param int $count
	 * @param string $symbol
	 */
	public function filterH5pActivities( $count, $symbol = '>' ) {
		$this->h5pActivities = (int) $count;
		$this->h5pActivitiesSymbol = $symbol;
	}

	/**
	 * @param int $count
	 * @param string $symbol
	 */
	public function filterTablepressTables( $count, $symbol = '>' ) {
		$this->tablepressTables = (int) $count;
		$this->tablepressTablesSymbol = $symbol;
	}

	/**
	 * @param string $str
	 */
	public function filterFullTextSearch( $str ) {
		$this->fullTextSearch = trim( $str );
	}

	/**
	 * @param array $blogs_ids
	 */
	public function filterExcludeBlogIds( $blogs_ids ) {
		$this->blogIdsExcluded = (array) $blogs_ids;
	}

	/**
	 * @param int $book_id
	 * @param string $key
	 * @param string $val
	 */
	public function action( $book_id, $key, $val ) {
		switch_to_blog( $book_id );

		switch ( $key ) {
			case 'public':
				if ( is_truthy( $val ) ) {
					update_option( 'blog_public', 1 );
					update_site_meta( $book_id, Book::PUBLIC, 1 );
				} else {
					update_option( 'blog_public', 0 );
					update_site_meta( $book_id, Book::PUBLIC, 0 );
					// If book is private, you cannot display it in your catalog
					update_option( get_in_catalog_option(), 0 );
					update_site_meta( $book_id, Book::IN_CATALOG, 0 );
				}
				break;

			case 'inCatalog':
				if ( is_truthy( $val ) ) {
					update_option( get_in_catalog_option(), 1 );
					update_site_meta( $book_id, Book::IN_CATALOG, 1 );
					// Book must be public to display in your catalog
					update_option( 'blog_public', 1 );
					update_site_meta( $book_id, Book::PUBLIC, 1 );
				} else {
					update_option( get_in_catalog_option(), 0 );
					update_site_meta( $book_id, Book::IN_CATALOG, 0 );
					// Exclude book when network option book directory non-catalog exclude is enabled
					$option = get_site_option( 'pressbooks_sharingandprivacy_options', [], true );
					if (
						isset( $option[ SharingAndPrivacyOptions::NETWORK_DIRECTORY_EXCLUDED ] ) &&
						( (bool) $option[ SharingAndPrivacyOptions::NETWORK_DIRECTORY_EXCLUDED ] === true )
					) {
						BookDirectory::init()->deleteBookFromDirectory( [ $book_id ] );
					}
				}
				update_blog_details( $book_id, [ 'last_updated' => current_time( 'mysql', true ) ] );
				break;

			case 'gradingEnabled':
				$book_lti_settings_option = get_blog_option( $book_id, 'pressbooks_lti' );
				$book_lti_settings = is_array( $book_lti_settings_option ) ? $book_lti_settings_option : [];
				$lti_grading_enabled = (int) is_truthy( $val );
				$update = [ 'lti_book_grading_enabled' => $lti_grading_enabled ];
				update_blog_option( $book_id, 'pressbooks_lti', array_merge( $book_lti_settings, $update ) );
				update_site_meta( $book_id, Book::LTI_GRADING_ENABLED, $lti_grading_enabled );
				break;
		}

		restore_current_blog();
	}

	/**
	 * @param array $book_ids
	 * @param string $key
	 * @param string $val
	 */
	public function bulkAction( array $book_ids, $key, $val ) {
		foreach ( $book_ids as $book_id ) {
			$this->action( $book_id, $key, $val );
		}
	}

	// ------------------------------------------------------------------------
	// Private
	// ------------------------------------------------------------------------

	private function queryBlogMetaTable() {
		global $wpdb;

		// Pivot table / Cross-tabulated table
		$cover = Book::COVER;
		$title = Book::TITLE;
		$book_url = Book::BOOK_URL;
		$last_edited = Book::LAST_EDITED;
		$created = Book::CREATED;
		$word_count = Book::WORD_COUNT;
		$authors = Book::TOTAL_AUTHORS;
		$readers = Book::TOTAL_READERS;
		$storage_size = Book::STORAGE_SIZE;
		$language = Book::LANGUAGE;
		$subject = Book::SUBJECT;
		$theme = Book::THEME;
		$license = Book::LICENSE;
		$public = Book::PUBLIC;
		$in_catalog = Book::IN_CATALOG;
		// Invisible, for filters
		$is_clone = Book::IS_CLONE;
		$akismet_activated = Book::AKISMET_ACTIVATED;
		$parsedown_party_activated = Book::PARSEDOWN_PARTY_ACTIVATED;
		$wp_quicklatex_activated = Book::WP_QUICK_LATEX_ACTIVATED;
		$glossary_terms = Book::GLOSSARY_TERMS;
		$h5p_activities = Book::H5P_ACTIVITIES;
		$tablepress_tables = Book::TABLEPRESS_TABLES;
		$has_exports = Book::HAS_EXPORTS;
		$allows_downloads = Book::ALLOWS_DOWNLOADS;
		$exports_by_format = Book::EXPORTS_BY_FORMAT;
		$last_export = Book::LAST_EXPORT;
		$deactivated = Book::DEACTIVATED;
		$grading_enabled = Book::LTI_GRADING_ENABLED;

		$sql = "
				SELECT SQL_CALC_FOUND_ROWS
					b.blog_id AS id,
					MAX(IF(b.meta_key='{$cover}',b.meta_value,null)) AS cover,
					MAX(IF(b.meta_key='{$title}',b.meta_value,null)) AS bookTitle,
					MAX(IF(b.meta_key='{$book_url}',b.meta_value,null)) AS bookUrl,
					MAX(IF(b.meta_key='{$last_edited}',CAST(b.meta_value AS DATETIME),null)) AS lastEdited,
					MAX(IF(b.meta_key='{$created}',CAST(b.meta_value AS DATETIME),null)) AS created,
					MAX(IF(b.meta_key='{$word_count}',CAST(b.meta_value AS UNSIGNED),null)) AS words,
					MAX(IF(b.meta_key='{$authors}',CAST(b.meta_value AS UNSIGNED),null)) AS authors,
					MAX(IF(b.meta_key='{$readers}',CAST(b.meta_value AS UNSIGNED),null)) AS readers,
					MAX(IF(b.meta_key='{$storage_size}',CAST(b.meta_value AS UNSIGNED),null)) AS storageSize,
					MAX(IF(b.meta_key='{$language}',b.meta_value,null)) AS language,
					MAX(IF(b.meta_key='{$subject}',b.meta_value,null)) AS subject,
					MAX(IF(b.meta_key='{$theme}',b.meta_value,null)) AS theme,
					MAX(IF(b.meta_key='{$license}',b.meta_value,null)) AS license,
					MAX(IF(b.meta_key='{$public}',CAST(b.meta_value AS UNSIGNED),null)) AS public,
					MAX(IF(b.meta_key='{$in_catalog}',CAST(b.meta_value AS UNSIGNED),null)) AS inCatalog,
					MAX(IF(b.meta_key='{$is_clone}',CAST(b.meta_value AS UNSIGNED),null)) AS isClone,
					MAX(IF(b.meta_key='{$akismet_activated}',CAST(b.meta_value AS UNSIGNED),null)) AS akismetActivated,
					MAX(IF(b.meta_key='{$parsedown_party_activated}',CAST(b.meta_value AS UNSIGNED),null)) AS parsedownPartyActivated,
					MAX(IF(b.meta_key='{$wp_quicklatex_activated}',CAST(b.meta_value AS UNSIGNED),null)) AS wpQuicklatexActivated,
					MAX(IF(b.meta_key='{$glossary_terms}',CAST(b.meta_value AS UNSIGNED),null)) AS glossaryTerms,
					MAX(IF(b.meta_key='{$h5p_activities}',CAST(b.meta_value AS UNSIGNED),null)) AS h5pActivities,
					MAX(IF(b.meta_key='{$tablepress_tables}',CAST(b.meta_value AS UNSIGNED),null)) AS tablepressTables,
					MAX(IF(b.meta_key='{$has_exports}',CAST(b.meta_value AS UNSIGNED),null)) AS hasExports,
					MAX(IF(b.meta_key='{$allows_downloads}',CAST(b.meta_value AS UNSIGNED),null)) AS allowsDownloads,
					MAX(IF(b.meta_key='{$exports_by_format}',b.meta_value,null)) AS exportsByFormat,
					MAX(IF(b.meta_key='{$last_export}',CAST(b.meta_value AS DATETIME),null)) AS lastExport,
					MAX(IF(b.meta_key='{$deactivated}',CAST(b.meta_value AS UNSIGNED),null)) AS deactivated,
					MAX(IF(b.meta_key='{$grading_enabled}',CAST(b.meta_value AS UNSIGNED),null)) AS gradingEnabled
				FROM {$wpdb->blogmeta} b
				GROUP BY id ";

		// Filters
		if ( $this->isPublic !== null ) {
			$this->applyIsPublic();
		}
		if ( $this->isClone !== null ) {
			$this->applyIsClone();
		}
		if ( $this->wordCount ) {
			$this->applyWordCount();
		}
		if ( $this->storageSize ) {
			$this->applyStorageSize();
		}
		if ( $this->currentLicense ) {
			$this->applyCurrentLicense();
		}
		if ( $this->currentTheme ) {
			$this->applyCurrentTheme();
		}
		if ( $this->bookLanguage ) {
			$this->applyBookLanguage();
		}
		if ( $this->bookSubject ) {
			$this->applyBookSubject();
		}
		if ( $this->akismetActivated ) {
			$this->applyAkismetActivated();
		}
		if ( $this->parsedownPartyActivated ) {
			$this->applyParsedownPartyActivated();
		}
		if ( $this->wpQuicklatexActivated ) {
			$this->applyWpQuicklatexActivated();
		}
		if ( $this->glossaryTerms ) {
			$this->applyGlossaryTerms();
		}
		if ( $this->h5pActivities ) {
			$this->applyH5pActivities();
		}
		if ( $this->tablepressTables ) {
			$this->applyTablepressTables();
		}
		if ( $this->hasExports ) {
			$this->applyHasExports();
		}
		if ( $this->allowsDownloads ) {
			$this->applyAllowsDownloads();
		}
		if ( $this->exportsByFormat ) {
			$this->applyExportsByFormat();
		}
		if ( $this->lastExport ) {
			$this->applyLastExport();
		}
		if ( $this->lastEdited ) {
			$this->applyLastEdited();
		}
		if ( $this->fullTextSearch ) {
			$this->applyFullTextSearch();
		}
		if ( $this->blogIdsExcluded && count( $this->blogIdsExcluded ) > 0 ) {
			$this->applyBookIdsExclusion();
		}

		if ( ! empty( $this->sqlWhere ) ) {
			// HAVING is applied after GROUP BY (and can filter on aggregates)
			$sql_where = 'HAVING ';
			foreach ( $this->sqlWhere as $condition ) {
				$sql_where .= "($condition) AND ";
			}
			$sql_where = str_lreplace( ') AND ', ') ', $sql_where );
			$sql .= $sql_where;
		}

		// Sort
		$order_by = '';
		foreach ( $this->sorters as $col => $order ) {
			$order_by .= "{$col} {$order}, ";
		}
		if ( $order_by ) {
			$order_by = str_lreplace( ', ', '', $order_by );
			$sql .= "ORDER BY $order_by ";
		}

		// Limit
		$offset = ( $this->currentPage - 1 ) * $this->maxPerPage;
		$sql .= $wpdb->prepare( 'LIMIT %d OFFSET %d ', $this->maxPerPage, $offset );

		// Results
		$this->booklist = $wpdb->get_results( $sql );
		$this->rowCount = (int) $wpdb->get_var( 'SELECT FOUND_ROWS()' );
		$this->lastPage = (int) ceil( $this->rowCount / $this->maxPerPage );
		$this->addAdminUsersEmailToBookList();

		if ( empty( $this->booklist ) ) {
			throw new \LengthException( 'Unexpected empty result set' );
		}
	}

	/**
	 * Add users email to booklist array.
	 *
	 * @return void
	 */
	public function addAdminUsersEmailToBookList() {
		$blogs = [];
		$users_meta = [];
		$user_ids = [];
		global $wpdb;
		$user_meta_sql = $wpdb->prepare( "SELECT user_id, meta_key FROM {$wpdb->usermeta} WHERE ( meta_key REGEXP '{$wpdb->base_prefix}([0-9]+)_capabilities' AND meta_value LIKE %s ) ", '%administrator%' );
		foreach ( $wpdb->get_results( $user_meta_sql ) as $v ) {
			preg_match_all( '/\d+/', $v->meta_key, $matches );
			$blog_id = (int) $matches[0][0];
			$user_id = (int) $v->user_id;
			$user_ids[] = $user_id;
			if ( ! array_key_exists( $blog_id, $users_meta ) ) {
				$users_meta[ $blog_id ] = [ $user_id ];
			} else {
				$users_meta[ $blog_id ][] = $user_id;
			}
		}
		if ( empty( $user_ids ) ) {
			return false;
		}
		$placeholder = implode( ', ', array_fill( 0, count( $user_ids ), '%d' ) );
		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$user_query = $wpdb->get_results( $wpdb->prepare( "SELECT id, user_email FROM $wpdb->users WHERE id IN ( $placeholder )", $user_ids ) );
		// phpcs:enable
		foreach ( $user_query as $user ) {
			foreach ( $users_meta as $blog_id => $users_array ) {
				if ( in_array( $user->id, $users_array ) ) { // @codingStandardsIgnoreLine
					if ( array_key_exists( $blog_id, $blogs ) ) {
						$blogs[ $blog_id ][] = $user->user_email;
					} else {
						$blogs[ $blog_id ] = [ $user->user_email ];
					}
				}
			}
		}

		$this->booklist = array_map( function( $book ) use ( $blogs ) {
			$book->users_email = array_key_exists( $book->id, $blogs ) ? join( ', ', $blogs[ $book->id ] ) : '';
			return $book;
		}, $this->booklist );

	}

	/**
	 * @uses BookList::$sqlWhere
	 */
	private function applyIsPublic() {
		if ( $this->isPublic ) {
			$sql = 'public = 1';
		} else {
			$sql = 'public = 0';
		}
		$this->sqlWhere[] = $sql;
	}

	/**
	 * @uses BookList::$sqlWhere
	 */
	private function applyIsClone() {
		if ( $this->isClone ) {
			$sql = 'isClone = 1';
		} else {
			$sql = 'isClone = 0';
		}
		$this->sqlWhere[] = $sql;
	}

	/**
	 * @uses BookList::$sqlWhere
	 */
	private function applyWordCount() {
		if ( in_array( $this->wordCountSymbol, [ '<', '<=', '>', '>=' ], true ) ) {
			$symbol = $this->wordCountSymbol;
		} else {
			$symbol = '>=';
		}
		$sql = "words $symbol " . (int) $this->wordCount;
		$this->sqlWhere[] = $sql;
	}

	/**
	 * @uses BookList::$sqlWhere
	 */
	private function applyStorageSize() {
		if ( in_array( $this->storageSizeSymbol, [ '<', '<=', '>', '>=' ], true ) ) {
			$symbol = $this->storageSizeSymbol;
		} else {
			$symbol = '>=';
		}
		$sql = "storageSize $symbol " . (int) $this->storageSize;
		$this->sqlWhere[] = $sql;
	}

	/**
	 * @uses BookList::$sqlWhere
	 */
	private function applyCurrentLicense() {
		global $wpdb;

		$current_license = (array) $this->currentLicense;
		$in = '';
		foreach ( $current_license as $license ) {
			$in .= $wpdb->prepare( '%s', $license ) . ',';
		}
		$in = str_lreplace( ',', '', $in );

		$sql = "license IN ($in)";
		$this->sqlWhere[] = $sql;
	}

	/**
	 * @uses BookList::$sqlWhere
	 */
	private function applyCurrentTheme() {
		global $wpdb;

		$current_theme = (array) $this->currentTheme;
		$in = '';
		foreach ( $current_theme as $theme ) {
			$in .= $wpdb->prepare( '%s', $theme ) . ',';
		}
		$in = str_lreplace( ',', '', $in );

		$sql = "theme IN ($in)";
		$this->sqlWhere[] = $sql;
	}

	/**
	 * @uses BookList::$sqlWhere
	 */
	private function applyBookLanguage() {
		global $wpdb;

		$book_language = (array) $this->bookLanguage;
		$in = '';
		foreach ( $book_language as $lang ) {
			$in .= $wpdb->prepare( '%s', $lang ) . ',';
		}
		$in = str_lreplace( ',', '', $in );

		$sql = "language IN ($in)";
		$this->sqlWhere[] = $sql;
	}

	/**
	 * @uses BookList::$sqlWhere
	 */
	private function applyBookSubject() {
		global $wpdb;

		$book_subject = (array) $this->bookSubject;
		$in = '';
		foreach ( $book_subject as $subject ) {
			$in .= $wpdb->prepare( '%s', $subject ) . ',';
		}
		$in = str_lreplace( ',', '', $in );

		$sql = "subject IN ($in)";
		$this->sqlWhere[] = $sql;
	}

	/**
	 * @uses BookList::$sqlWhere
	 */
	private function applyHasExports() {
		$sql = 'hasExports = 1';
		$this->sqlWhere[] = $sql;
	}

	/**
	 * @uses BookList::$sqlWhere
	 */
	private function applyAllowsDownloads() {
		$sql = 'allowsDownloads = 1';
		$this->sqlWhere[] = $sql;
	}

	/**
	 * @uses BookList::$sqlWhere
	 */
	private function applyExportsByFormat() {
		global $wpdb;

		$exports_by_format = (array) $this->exportsByFormat;
		$sql = '';
		foreach ( $exports_by_format as $format ) {
			$like = '%' . $wpdb->esc_like( $format ) . '%';
			$sql .= $wpdb->prepare( 'exportsByFormat LIKE %s AND ', $like );
		}
		$sql = str_lreplace( ' AND ', '', $sql );

		$this->sqlWhere[] = $sql;
	}

	/**
	 * @uses BookList::$sqlWhere
	 */
	private function applyLastExport() {
		global $wpdb;
		$sql = $wpdb->prepare( 'lastExport >= %s ', $this->lastExport->format( 'Y-m-d H:i:s' ) );
		$this->sqlWhere[] = $sql;
	}

	/**
	 * @uses BookList::$sqlWhere
	 */
	private function applyLastEdited() {
		global $wpdb;
		$sql = $wpdb->prepare( 'lastEdited >= %s ', $this->lastEdited->format( 'Y-m-d H:i:s' ) );
		$this->sqlWhere[] = $sql;
	}

	/**
	 * @uses BookList::$sqlWhere
	 */
	private function applyFullTextSearch() {
		global $wpdb;
		// String in title or URL
		$like = '%' . $wpdb->esc_like( $this->fullTextSearch ) . '%';
		$sql = $wpdb->prepare( '(bookTitle LIKE %s OR bookUrl LIKE %s) ', $like, $like );
		$this->sqlWhere[] = $sql;
	}

	/**
	 * @uses BookList::$sqlWhere
	 */
	private function applyAkismetActivated() {
		$sql = 'akismetActivated = 1';
		$this->sqlWhere[] = $sql;
	}

	/**
	 * @uses BookList::$sqlWhere
	 */
	private function applyParsedownPartyActivated() {
		$sql = 'parsedownPartyActivated = 1';
		$this->sqlWhere[] = $sql;
	}

	/**
	 * @uses BookList::$sqlWhere
	 */
	private function applyWpQuicklatexActivated() {
		$sql = 'wpQuicklatexActivated = 1';
		$this->sqlWhere[] = $sql;
	}

	/**
	 * @uses BookList::$sqlWhere
	 */
	private function applyGlossaryTerms() {
		if ( in_array( $this->glossaryTermsSymbol, [ '<', '<=', '>', '>=' ], true ) ) {
			$symbol = $this->glossaryTermsSymbol;
		} else {
			$symbol = '>=';
		}
		$sql = "glossaryTerms $symbol " . (int) $this->glossaryTerms;
		$this->sqlWhere[] = $sql;
	}

	/**
	 * @uses BookList::$sqlWhere
	 */
	private function applyH5pActivities() {
		if ( in_array( $this->h5pActivitiesSymbol, [ '<', '<=', '>', '>=' ], true ) ) {
			$symbol = $this->h5pActivitiesSymbol;
		} else {
			$symbol = '>=';
		}
		$sql = "h5pActivities $symbol " . (int) $this->h5pActivities;
		$this->sqlWhere[] = $sql;
	}

	/**
	 * @uses BookList::$sqlWhere
	 */
	private function applyTablepressTables() {
		if ( in_array( $this->tablepressTablesSymbol, [ '<', '<=', '>', '>=' ], true ) ) {
			$symbol = $this->tablepressTablesSymbol;
		} else {
			$symbol = '>=';
		}
		$sql = "tablepressTables $symbol " . (int) $this->tablepressTables;
		$this->sqlWhere[] = $sql;
	}

	/**
	 * @uses BookList::$sqlWhere
	 */
	private function applyBookIdsExclusion() {
		$sql = 'id NOT IN (' . implode( ',', $this->blogIdsExcluded ) . ')';
		$this->sqlWhere[] = $sql;
	}

}
