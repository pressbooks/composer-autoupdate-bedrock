<?php

namespace PressbooksNetworkAnalytics\Admin;

use function PressbooksNetworkAnalytics\blade;
use function PressbooksNetworkAnalytics\is_truthy;
use function PressbooksNetworkAnalytics\objects_2_csv;
use function Pressbooks\Admin\NetworkManagers\is_restricted;
use PressbooksMix\Assets;
use PressbooksNetworkAnalytics\Admin\Options;
use PressbooksNetworkAnalytics\Model\BookList;
use Pressbooks\DataCollector\Book as BookDataCollector;
use Pressbooks\DataCollector\User as UserDataCollector;

class Books extends Admin {

	/**
	 * The slug name for the parent menu (or the file name of a standard WordPress admin page).
	 *
	 * @var string
	 */
	public $parentPage = 'sites.php';

	/**
	 * @var Books
	 */
	private static $instance = null;

	/**
	 * @return Books
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param Books $obj
	 */
	public static function hooks( Books $obj ) {
		add_action( 'admin_enqueue_scripts', [ $obj, 'adminEnqueueScripts' ] );
		add_action( 'network_admin_menu', [ $obj, 'addMenu' ] );
		add_action( 'admin_head', [ $obj, 'hideMenu' ] );
		// Any method of making a book private should automatically change the In Catalog status on the Book List
		add_action( 'wp_validate_site_data', [ $obj, 'reflectPrivacyStatus' ], 9999, 3 );
		// Books
		add_action( 'wp_ajax_pb_network_analytics_books', [ $obj, 'ajaxGetBooksJson' ] );
		add_action( 'wp_ajax_pb_network_analytics_books_csv', [ $obj, 'ajaxGetBooksCsv' ] );
		add_action( 'wp_ajax_pb_network_analytics_books_inline_edit', [ $obj, 'ajaxBooksInlineEdit' ] );
		add_action( 'wp_ajax_pb_network_analytics_books_bulk', [ $obj, 'ajaxBooksBulkAction' ] );
	}

	/**
	 *
	 */
	public function __construct() {
	}

	/**
	 * @param string $hook
	 */
	public function adminEnqueueScripts( $hook ) {
		// Books
		if ( $hook === get_plugin_page_hookname( 'pb_network_analytics_booklist', $this->parentPage ) ) {
			$assets = new Assets( 'pressbooks-network-analytics', 'plugin' );
			// Book List
			$this->registerTabulatorAssets();
			$global_grading_enabled = 0;
			$granular_grading_enabled = 0;
			$is_super_admin = 0;
			$is_network_manager = 0;

			if ( is_plugin_active_for_network( Options::LTI_1P3_PLUGIN_LOADER ) ) {
				$global_grading_enabled = get_site_option( \PressbooksLtiProvider1p3\Admin::OPTION, [] )['lti_global_grading_enabled'] ?? 0;
				$granular_grading_enabled = get_site_option( \PressbooksLtiProvider1p3\Admin::OPTION, [] )['network_enable_rlms'] ?? 0;
				$is_super_admin = is_super_admin( get_current_user_id() ) && ! is_restricted();
				$is_network_manager = is_super_admin( get_current_user_id() ) && is_restricted();
			}

			wp_enqueue_style( 'pb-network-analytics-booklist', $assets->getPath( 'styles/booklist.css' ), [ 'jquery-ui-tabs', 'tabulator' ] );
			wp_enqueue_script( 'pb-network-analytics-booklist', $assets->getPath( 'scripts/booklist.js' ), [ 'jquery-ui-tabs', 'moment', 'tabulator', 'wp-i18n' ] );
			wp_localize_script(
				'pb-network-analytics-booklist', 'PB_Network_Analytics_BookListToken', [
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'ajaxAction' => 'pb_network_analytics_books',
					'ajaxActionCsv' => 'pb_network_analytics_books_csv',
					'ajaxActionInlineEdit' => 'pb_network_analytics_books_inline_edit',
					'ajaxActionBulk' => 'pb_network_analytics_books_bulk',
					'ajaxNonce' => wp_create_nonce( 'pb-network-analytics-booklist' ),
					'ajaxGlobalGrade' => $global_grading_enabled,
					'ajaxGranularGrade' => $granular_grading_enabled,
					'ajaxIsSuperAdmin' => $is_super_admin,
					'ajaxIsNetworkManager' => $is_network_manager,
				]
			);
		}
	}

	/**
	 * Hooked into network_admin_menu
	 */
	public function addMenu() {
		// Books List
		add_submenu_page(
			$this->parentPage,
			__( 'Book List', 'pressbooks-network-analytics' ),
			__( 'Book List', 'pressbooks-network-analytics' ),
			'manage_network',
			'pb_network_analytics_booklist',
			[ $this, 'printMenuBookList' ]
		);

		// Books - Hidden Synchronize Menu
		add_submenu_page(
			$this->parentPage,
			__( 'Synchronize 💥', 'pressbooks-network-analytics' ),
			__( 'Synchronize 💥', 'pressbooks-network-analytics' ),
			'manage_network',
			'pb_network_analytics_booklist_sync',
			[ $this, 'printMenuBookListSync' ]
		);

	}

	/**
	 * Hooked into admin_head
	 */
	public function hideMenu() {
		remove_submenu_page( $this->parentPage, 'pb_network_analytics_booklist_sync' );
	}

	public function printMenuBookList() {
		$this->maybePrintUpdateMessage();
		$dc = BookDataCollector::init();
		$licenses = $this->getPossibleValuesForLicenses( $dc );
		$themes = $dc->getPossibleValuesFor( $dc::THEME );
		$languages = $dc->getPossibleValuesFor( $dc::LANGUAGE );
		$subjects = $dc->getPossibleValuesFor( $dc::SUBJECT );
		$exports_by_format = $dc->getPossibleCommaDelimitedValuesFor( $dc::EXPORTS_BY_FORMAT );
		$total_books = $dc->getTotalBooks();
		$total_storage = \Pressbooks\Utility\format_bytes( $dc->getTotalNetworkStorageBytes() );

		echo blade()->render(
			'PressbooksNetworkAnalytics::booklist', [
				'last_sync' => get_site_option( 'pb_book_sync_cron_timestamp', __( 'Unknown', 'pressbooks-network-analytics' ) ),
				'licenses' => $licenses,
				'themes' => $themes,
				'languages' => $languages,
				'subjects' => $subjects,
				'exports_by_format' => $exports_by_format,
				'total_books' => $total_books,
				'total_storage' => $total_storage,
			]
		);
	}

	/**
	 * Flag notification that appears at the top
	 *
	 * @see wp-admin/network/sites.php
	 */
	public function maybePrintUpdateMessage() {
		$msg = '';
		if ( isset( $_GET['updated'] ) ) {
			$action = $_GET['updated'];

			switch ( $action ) {
				case 'all_notspam':
					$msg = __( 'Sites removed from spam.' );
					break;
				case 'all_spam':
					$msg = __( 'Sites marked as spam.' );
					break;
				case 'all_delete':
					$msg = __( 'Sites deleted.' );
					break;
				case 'delete':
					$msg = __( 'Site deleted.' );
					break;
				case 'not_deleted':
					$msg = __( 'Sorry, you are not allowed to delete that site.' );
					break;
				case 'archiveblog':
					$msg = __( 'Site archived.' );
					break;
				case 'unarchiveblog':
					$msg = __( 'Site unarchived.' );
					break;
				case 'activateblog':
					$msg = __( 'Site activated.' );
					break;
				case 'deactivateblog':
					$msg = __( 'Site deactivated.' );
					break;
				case 'unspamblog':
					$msg = __( 'Site removed from spam.' );
					break;
				case 'spamblog':
					$msg = __( 'Site marked as spam.' );
					break;
				default:
					/**
					 * Filters a specific, non-default, site-updated message in the Network admin.
					 *
					 * The dynamic portion of the hook name, `$action`, refers to the non-default
					 * site update action.
					 *
					 * @param string $msg The update message. Default 'Settings saved'.
					 *
					 * @since 3.1.0
					 */
					$msg = apply_filters( "network_sites_updated_message_{$action}", __( 'Settings saved.' ) );
					break;
			}

			if ( ! empty( $msg ) ) {
				$msg = '<div id="message" class="updated notice is-dismissible"><p>' . $msg . '</p></div>';
			}
		}
		echo $msg;
	}

	/**
	 * @param BookDataCollector $dc
	 *
	 * @return array
	 */
	private function getPossibleValuesForLicenses( $dc ) {
		$known_licenses = ( new \Pressbooks\Licensing() )->getSupportedTypes();
		$licenses = [];
		foreach ( $dc->getPossibleValuesFor( $dc::LICENSE ) as $license ) {
			$licenses[ $license ] = isset( $known_licenses[ $license ] ) ? $known_licenses[ $license ]['desc'] : strtoupper( $license );
		}
		ksort( $licenses );
		return $licenses;
	}

	/**
	 * Book Sync
	 */
	public function printMenuBookListSync(): void {
		$html = '<p>Started at:' . date( 'Y-m-d H:i:s' ) . '</p>';

		$book_data_collector = BookDataCollector::init();
		foreach ( $book_data_collector->copyAllBooksIntoSiteTable() as $_ ) {
			// Generator
		}

		$user_data_collector = UserDataCollector::init();
		foreach ( $user_data_collector->updateAllUsersMetadata() as $_ ) {
			// Generator
		}

		$user_data_collector->updateNetworkManagers();

		$html .= '<p>Finished at:' . date( 'Y-m-d H:i:s' ) . '</p>';

		echo $html;
	}

	/**
	 * Hooked into wp_ajax_pb_network_analytics_books
	 */
	public function ajaxGetBooksJson() {
		$booklist = $this->filteredBookList();
		$data = $booklist->get();

		// HTML-ize some cells
		foreach ( $data as $i => $obj ) {
			// @see \WP_MS_Sites_List_Table::handle_row_actions
			$edit_href = '<a href="' . esc_url( network_admin_url( 'site-info.php?id=' . $obj->id ) ) . '">' . __( 'Edit' ) . '</a>';
			$dashboard_href = "<a href='" . esc_url( get_admin_url( $obj->id ) ) . "' class='edit'>" . __( 'Dashboard' ) . '</a>';
			$delete_href = '<a href="' . esc_url( wp_nonce_url( network_admin_url( 'sites.php?action=confirm&amp;action2=deleteblog&amp;id=' . $obj->id ), 'deleteblog_' . $obj->id ) ) . '">' . __( 'Delete' ) . '</a>';
			if ( $obj->deactivated ) {
				$activation_href = '<a href="' . esc_url( wp_nonce_url( network_admin_url( 'sites.php?action=confirm&amp;action2=activateblog&amp;id=' . $obj->id ), 'activateblog_' . $obj->id ) ) . '">' . __( 'Activate' ) . '</a>';
			} else {
				$activation_href = '<a href="' . esc_url( wp_nonce_url( network_admin_url( 'sites.php?action=confirm&amp;action2=deactivateblog&amp;id=' . $obj->id ), 'deactivateblog_' . $obj->id ) ) . '">' . __( 'Deactivate' ) . '</a>';
			}
			$html = "<div style='font-weight:bold;white-space:pre-wrap;'>{$obj->bookTitle}</div>";
			$html .= "<a href='{$obj->bookUrl}'>{$obj->bookUrl}</a><br>";
			$html .= "$edit_href | ";
			$html .= "$dashboard_href | ";
			$html .= "$activation_href | ";
			$html .= "$delete_href";
			$html .= '</div>';
			$data[ $i ]->bookTitle = $html;
		}

		$response = [
			'data' => $data,
			'last_page' => $booklist->getLastPage(), // After $booklist->get(), else no calculations are done
			'row_count' => $booklist->getRowCount(), // Ditto
		];

		wp_send_json( $response );
	}

	/**
	 * Hooked into wp_ajax_pb_network_analytics_books_csv
	 */
	public function ajaxGetBooksCsv() {
		$booklist = $this->filteredBookList();
		// Override pagination, get up to a million rows
		$booklist->setCurrentPage( 1 );
		$booklist->setMaxPerPage( 1000000 );
		// Convert to CSV
		$tmp_file = \Pressbooks\Utility\create_tmp_file();
		$csv = objects_2_csv( $booklist->get() );
		\Pressbooks\Utility\put_contents( $tmp_file, $csv );
		// Download
		$download_filename = 'booklist-filtered-results-' . $booklist->getRowCount() . '.csv';
		\Pressbooks\Redirect\force_download( $tmp_file, false, $download_filename );
		if ( ! defined( 'WP_TESTS_MULTISITE' ) ) {
			exit;
		}
	}

	/**
	 * Hooked into wp_ajax_pb_network_analytics_books_inline_edit
	 */
	public function ajaxBooksInlineEdit() {
		check_ajax_referer( 'pb-network-analytics-booklist' );

		$book_id = $_POST['book_id'];
		$key = $_POST['key'];
		$val = $_POST['val'];

		$booklist = new BookList();
		$booklist->action( $book_id, $key, $val );

		wp_send_json_success();
	}

	/**
	 * Hooked into wp_ajax_pb_network_analytics_books_bulk
	 */
	public function ajaxBooksBulkAction() {
		check_admin_referer( 'pb-network-analytics-booklist' );

		$book_ids = explode( ',', $_POST['book_ids'] );
		$book_ids = array_filter( $book_ids, 'is_numeric' );

		[$key, $val] = explode( '_', $_POST['do'], 2 );

		if ( $key === 'delete' && is_truthy( $val ) ) {
			// Special case, redirect to confirmation page
			$this->delete( $book_ids );
		} else {
			$booklist = new BookList();
			$booklist->bulkAction( $book_ids, $key, $val );
		}

		\Pressbooks\Redirect\location( network_admin_url( '/admin.php?page=pb_network_analytics_booklist' ) );
	}

	/**
	 * Special case, redirect to WordPress built-in confirmation page
	 *
	 * @param array $book_ids
	 */
	protected function delete( array $book_ids ) {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<script type="text/javascript">
				function closethisasap() {
					document.forms[ 'redirectpost' ].submit();
				}
			</script>
			<title><?php _e( 'Redirecting to book deletion confirmation page...', 'pressbooks-network-analytics' ); ?></title>
		</head>
		<body onload="closethisasap();">
		<form name="redirectpost" method="post" action="<?php echo network_admin_url( 'sites.php?action=allblogs' ) ?>">
			<?php
			echo '<input type="hidden" name="_wp_http_referer" value="' . esc_attr( network_admin_url( '/admin.php?page=pb_network_analytics_booklist' ) ) . '">';
			echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( wp_create_nonce( 'bulk-sites' ) ) . '">';
			echo '<input type="hidden" name="mode" value="list">';
			echo '<input type="hidden" name="action" value="delete">';
			$main_site_id = get_main_site_id();
			foreach ( $book_ids as $id ) {
				if ( (int) $id !== (int) $main_site_id ) {
					echo '<input type="hidden" name="allblogs[]" value="' . (int) $id . '"> ';
				}
			}
			?>
		</form>
		</body>
		</html>
		<?php
		if ( ! defined( 'WP_TESTS_MULTISITE' ) ) {
			exit;
		}
	}

	/**
	 * @return BookList
	 */
	public function filteredBookList() {
		check_ajax_referer( 'pb-network-analytics-booklist' );

		$booklist = new BookList();

		// Pagination
		if ( ! empty( $_GET['page'] ) ) {
			$booklist->setCurrentPage( (int) $_GET['page'] );
		}
		if ( ! empty( $_GET['size'] ) ) {
			$booklist->setMaxPerPage( (int) $_GET['size'] );
		}

		// Sorters
		if ( ! empty( $_GET['sorters'] ) && is_array( $_GET['sorters'] ) ) {
			$booklist->setSorters( $_GET['sorters'] );
		}

		// Filters
		if ( isset( $_GET['isPublic'] ) ) {
			$booklist->filterIsPublic( (bool) $_GET['isPublic'] );
		}
		if ( isset( $_GET['isCloned'] ) ) {
			$booklist->filterIsClone( (bool) $_GET['isCloned'] );
		}
		if ( isset( $_GET['akismetActivated'] ) ) {
			$booklist->filterAkismetActivated( (bool) $_GET['akismetActivated'] );
		}
		if ( isset( $_GET['parsedownPartyActivated'] ) ) {
			$booklist->filterParsedownPartyActivated( (bool) $_GET['parsedownPartyActivated'] );
		}
		if ( isset( $_GET['wpQuicklatexActivated'] ) ) {
			$booklist->filterWpQuicklatexActivated( (bool) $_GET['wpQuicklatexActivated'] );
		}
		if ( ! empty( $_GET['glossaryTerms'] ) && ! empty( $_GET['glossaryTermsSymbol'] ) ) {
			$booklist->filterGlossaryTerms( (int) $_GET['glossaryTerms'], $_GET['glossaryTermsSymbol'] );
		}
		if ( ! empty( $_GET['h5pActivities'] ) && ! empty( $_GET['h5pActivitiesSymbol'] ) ) {
			$booklist->filterH5pActivities( (int) $_GET['h5pActivities'], $_GET['h5pActivitiesSymbol'] );
		}
		if ( ! empty( $_GET['tablepressTables'] ) && ! empty( $_GET['tablepressTablesSymbol'] ) ) {
			$booklist->filterTablepressTables( (int) $_GET['tablepressTables'], $_GET['tablepressTablesSymbol'] );
		}
		if ( ! empty( $_GET['wordCount'] ) && ! empty( $_GET['wordCountSymbol'] ) ) {
			$booklist->filterWordCount( (int) $_GET['wordCount'], $_GET['wordCountSymbol'] );
		}
		if ( ! empty( $_GET['storageSize'] ) && ! empty( $_GET['storageSizeSymbol'] ) ) {
			$booklist->filterStorageSize( (int) $_GET['storageSize'] * MB_IN_BYTES, $_GET['storageSizeSymbol'] );
		}
		if ( isset( $_GET['currentLicense'] ) ) {
			$booklist->filterCurrentLicense( $_GET['currentLicense'] );
		}
		if ( isset( $_GET['currentTheme'] ) ) {
			$booklist->filterCurrentTheme( $_GET['currentTheme'] );
		}
		if ( isset( $_GET['bookLanguage'] ) ) {
			$booklist->filterBookLanguage( $_GET['bookLanguage'] );
		}
		if ( isset( $_GET['bookSubject'] ) ) {
			$booklist->filterBookSubject( $_GET['bookSubject'] );
		}
		if ( isset( $_GET['hasExports'] ) ) {
			$booklist->filterHasExports( (bool) $_GET['hasExports'] );
		}
		if ( isset( $_GET['allowsDownloads'] ) ) {
			$booklist->filterAllowsDownloads( (bool) $_GET['allowsDownloads'] );
		}
		if ( isset( $_GET['exportsByFormat'] ) ) {
			$booklist->filterExportsByFormat( $_GET['exportsByFormat'] );
		}
		if ( ! empty( $_GET['lastExport'] ) ) {
			$booklist->filterLastExport( $_GET['lastExport'] );
		}
		if ( ! empty( $_GET['lastEdited'] ) ) {
			$booklist->filterLastEdited( $_GET['lastEdited'] );
		}
		if ( ! empty( $_GET['searchInput'] ) ) {
			$booklist->filterFullTextSearch( $_GET['searchInput'] );
		}

		/*
		 * Exclude main book: https://github.com/pressbooks/pressbooks-network-analytics/issues/99
		 */
		$booklist->filterExcludeBlogIds( [ 1 ] );

		return $booklist;
	}

	/**
	 * Any method of making a book private should automatically change the In Catalog status on the Book List
	 * Hooked into `wp_validate_site_data`
	 *
	 * @param \WP_Error $errors
	 * @param array $data
	 * @param \WP_Site|null $old_site
	 */
	public function reflectPrivacyStatus( $errors, $data, $old_site ) {
		if ( empty( $errors->errors ) && isset( $data['public'] ) && $old_site && $old_site->id ) {
			// Set a static variable to fix infinite hook loop
			static $recursion = false;
			if ( ! $recursion ) {
				$recursion = true;
				$booklist = new BookList();
				if ( ! empty( $data['public'] ) ) {
					$booklist->action( $old_site->id, 'public', 1 );
				} else {
					$booklist->action( $old_site->id, 'public', 0 );
				}
				$recursion = false;
			}
		}
	}

}
