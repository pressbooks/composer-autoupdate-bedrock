<?php

namespace PressbooksNetworkAnalytics\Admin;

use function PressbooksNetworkAnalytics\blade;
use function PressbooksNetworkAnalytics\is_truthy;
use function PressbooksNetworkAnalytics\objects_2_csv;
use PressbooksMix\Assets;
use PressbooksNetworkAnalytics\Model\UserInfo;
use PressbooksNetworkAnalytics\Model\UserList;

class Users extends Admin {

	/**
	 * The slug name for the parent menu (or the file name of a standard WordPress admin page).
	 *
	 * @var string
	 */
	public $parentPage = 'users.php';

	/**
	 * @var Users
	 */
	private static $instance = null;

	/**
	 * @return Users
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param Users $obj
	 */
	static public function hooks( Users $obj ) {
		add_action( 'admin_enqueue_scripts', [ $obj, 'adminEnqueueScripts' ] );
		add_action( 'network_admin_menu', [ $obj, 'addMenu' ] );
		// Users
		add_action( 'wp_ajax_pb_network_analytics_users', [ $obj, 'ajaxGetUsersJson' ] );
		add_action( 'wp_ajax_pb_network_analytics_users_csv', [ $obj, 'ajaxGetUsersCsv' ] );
		add_action( 'wp_ajax_pb_network_analytics_users_bulk', [ $obj, 'ajaxUsersBulkAction' ] );
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
		$assets = new Assets( 'pressbooks-network-analytics', 'plugin' );

		// Users
		if ( $hook === get_plugin_page_hookname( 'pb_network_analytics_userlist', $this->parentPage ) ) {
			if ( ! empty( $_GET['id'] ) ) {
				// User Info
				wp_enqueue_style( 'pb-network-analytics-userinfo', $assets->getPath( 'styles/userinfo.css' ) );
				wp_enqueue_script( 'pb-network-analytics-userinfo', $assets->getPath( 'scripts/userinfo.js' ), [ 'jquery', 'wp-i18n' ] );
			} else {
				// User List
				$this->registerTabulatorAssets();
				wp_enqueue_style( 'pb-network-analytics-userlist', $assets->getPath( 'styles/userlist.css' ), [ 'jquery-ui-tabs', 'tabulator' ] );
				wp_enqueue_script( 'pb-network-analytics-userlist', $assets->getPath( 'scripts/userlist.js' ), [ 'jquery-ui-tabs', 'moment', 'tabulator', 'wp-i18n' ] );
				wp_localize_script(
					'pb-network-analytics-userlist', 'PB_Network_Analytics_UserListToken', [
						'ajaxUrl' => admin_url( 'admin-ajax.php' ),
						'ajaxAction' => 'pb_network_analytics_users',
						'ajaxActionCsv' => 'pb_network_analytics_users_csv',
						'ajaxActionBulk' => 'pb_network_analytics_users_bulk',
						'ajaxNonce' => wp_create_nonce( 'pb-network-analytics-userlist' ),
					]
				);
			}
		}
	}

	/**
	 * Hooked into network_admin_menu
	 */
	public function addMenu() {
		// Users List
		add_submenu_page(
			$this->parentPage,
			__( 'User List', 'pressbooks-network-analytics' ),
			__( 'User List', 'pressbooks-network-analytics' ),
			'manage_network',
			'pb_network_analytics_userlist',
			[ $this, 'printMenuUserList' ]
		);

	}

	/**
	 * User List
	 */
	public function printMenuUserList() {
		$this->maybePrintUpdateMessage();
		if ( ! empty( $_GET['id'] ) ) {
			$this->printMenuUserInfo();
		} else {
			echo blade()->render(
				'PressbooksNetworkAnalytics::userlist', [
					'last_sync' => get_site_option( 'pb_book_sync_cron_timestamp', __( 'Unknown', 'pressbooks-network-analytics' ) ),
					'total_users' => get_user_count(),
				]
			);
		}
	}

	/**
	 * Flag notification that appears at the top
	 *
	 * @see wp-admin/network/users.php
	 */
	public function maybePrintUpdateMessage() {
		if ( isset( $_REQUEST['updated'] ) && is_truthy( $_REQUEST['updated'] ) && ! empty( $_REQUEST['action'] ) ) {
			?>
			<div id="message" class="updated notice is-dismissible"><p>
					<?php
					switch ( $_REQUEST['action'] ) {
						case 'delete':
							_e( 'User deleted.' );
							break;
						case 'all_spam':
							_e( 'Users marked as spam.' );
							break;
						case 'all_notspam':
							_e( 'Users removed from spam.' );
							break;
						case 'all_delete':
							_e( 'Users deleted.' );
							break;
						case 'add':
							_e( 'User added.' );
							break;
					}
					?>
				</p></div>
			<?php
		}
	}

	/**
	 * User Info
	 */
	public function printMenuUserInfo() {
		$id = (int) ( $_REQUEST['id'] ?? 0 );
		$info = ( new UserInfo() )->get( $id );
		if ( empty( $info ) ) {
			return;
		}
		// TODO: Ranking ## of ### users on network
		echo blade()->render(
			'PressbooksNetworkAnalytics::userinfo', [
				'info' => $info,
			]
		);
	}

	/**
	 * @return UserList
	 */
	public function filteredUserList() {
		check_ajax_referer( 'pb-network-analytics-userlist' );

		$userlist = new UserList();

		// Pagination
		if ( ! empty( $_GET['page'] ) ) {
			$userlist->setCurrentPage( (int) $_GET['page'] );
		}
		if ( ! empty( $_GET['size'] ) ) {
			$userlist->setMaxPerPage( (int) $_GET['size'] );
		}

		// Sorters
		if ( ! empty( $_GET['sorters'] ) && is_array( $_GET['sorters'] ) ) {
			$userlist->setSorters( $_GET['sorters'] );
		}

		// Filters
		if ( ! empty( $_GET['lastLoggedInBefore'] ) ) {
			$userlist->filterLastLoggedInBefore( $_GET['lastLoggedInBefore'] );
		}
		if ( ! empty( $_GET['lastLoggedInAfter'] ) ) {
			$userlist->filterLastLoggedInAfter( $_GET['lastLoggedInAfter'] );
		}
		if ( ! empty( $_GET['addedSince'] ) ) {
			$userlist->filterAddedSince( $_GET['addedSince'] );
		}
		if ( ! empty( $_GET['isRole'] ) && ! empty( $_GET['numberOfBooks'] ) ) {
			$userlist->filterIsRoleInXNumberOfBooks( $_GET['isRole'], (int) $_GET['numberOfBooks'] );
		}
		if ( ! empty( $_GET['hasRoleAbove'] ) ) {
			$userlist->filterHasRoleAbove( $_GET['hasRoleAbove'] );
		}
		if ( ! empty( $_GET['searchInput'] ) ) {
			$userlist->filterFullTextSearch( $_GET['searchInput'] );
		}

		return $userlist;
	}

	/**
	 * Hooked into wp_ajax_pb_network_analytics_users
	 */
	public function ajaxGetUsersJson() {
		$userlist = $this->filteredUserList();
		$data = $userlist->get();

		// HTML-ize some cells
		$super_admins = get_super_admins();
		foreach ( $data as $i => $obj ) {
			// @see \WP_MS_Users_List_Table::handle_row_actions
			$info_href = '<a href="' . esc_url( network_admin_url( '/admin.php?page=pb_network_analytics_userlist&id=' . $obj->id ) ) . '">' . __( 'Info' ) . '</a>';
			$edit_href = '<a href="' . esc_url( network_admin_url( add_query_arg( '_wp_http_referer', rawurlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '/user-edit.php?user_id=' . $obj->id ) ) ) . '">' . __( 'Edit' ) . '</a>';
			$delete_href = '<a href="' . esc_url( network_admin_url( add_query_arg( '_wp_http_referer', rawurlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ), wp_nonce_url( 'users.php', 'deleteuser' ) . '&amp;action=deleteuser&amp;id=' . $obj->id ) ) ) . '" class="delete">' . __( 'Delete' ) . '</a>';
			$html = "<div style='font-weight:bold;white-space:pre-wrap;'>{$obj->username}</div>";
			$html .= "$info_href ";
			$html .= " | $edit_href ";
			if ( ! in_array( $obj->username, $super_admins, true ) ) {
				$html .= "| $delete_href";
			}
			$html .= '</div>';
			$data [ $i ]->username = $html;
		}

		$response = [
			'data' => $data,
			'last_page' => $userlist->getLastPage(), // After $userlist->get(), else no calculations are done
			'row_count' => $userlist->getRowCount(), // Ditto
		];
		wp_send_json( $response );
	}

	/**
	 * Hooked into wp_ajax_pb_network_analytics_users_csv
	 */
	public function ajaxGetUsersCsv() {
		$userlist = $this->filteredUserList();
		// Override pagination, get up to a million rows
		$userlist->setCurrentPage( 1 );
		$userlist->setMaxPerPage( 1000000 );
		// Convert to CSV
		$tmp_file = \Pressbooks\Utility\create_tmp_file();
		$csv = objects_2_csv( $userlist->get() );
		\Pressbooks\Utility\put_contents( $tmp_file, $csv );
		// Download
		$download_filename = 'userlist-filtered-results-' . $userlist->getRowCount() . '.csv';
		\Pressbooks\Redirect\force_download( $tmp_file, false, $download_filename );
		if ( ! defined( 'WP_TESTS_MULTISITE' ) ) {
			exit;
		}
	}

	/**
	 * Hooked into wp_ajax_pb_network_analytics_users_bulk
	 */
	public function ajaxUsersBulkAction() {
		check_admin_referer( 'pb-network-analytics-userlist' );

		$user_ids = explode( ',', $_POST['user_ids'] );
		$user_ids = array_filter( $user_ids, 'is_numeric' );

		[ $key, $val ] = explode( '_', $_POST['do'], 2 );

		if ( $key === 'delete' && is_truthy( $val ) ) {
			// Special case, redirect to confirmation page
			$this->delete( $user_ids );
		} else {
			$userlist = new UserList();
			$userlist->bulkAction( $user_ids, $key, $val );
		}
	}

	/**
	 * Special case, redirect to WordPress built-in confirmation page
	 *
	 * @param array $user_ids
	 */
	protected function delete( array $user_ids ) {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<script type="text/javascript">
				function closethisasap() {
					document.forms[ 'redirectpost' ].submit();
				}
			</script>
			<title><?php _e( 'Redirecting to user deletion confirmation page...', 'pressbooks-network-analytics' ); ?></title>
		</head>
		<body onload="closethisasap();">
		<form name="redirectpost" method="post" action="<?php echo network_admin_url( 'users.php?action=allusers' ) ?>">
			<?php
			echo '<input type="hidden" name="_wp_http_referer" value="' . esc_attr( network_admin_url( '/admin.php?page=pb_network_analytics_userlist' ) ) . '">';
			echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( wp_create_nonce( 'bulk-users-network' ) ) . '">';
			echo '<input type="hidden" name="mode" value="list">';
			echo '<input type="hidden" name="action" value="delete">';
			foreach ( $user_ids as $id ) {
				if ( false === is_super_admin( $id ) ) {
					echo '<input type="hidden" name="allusers[]" value="' . (int) $id . '"> ';
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

}
