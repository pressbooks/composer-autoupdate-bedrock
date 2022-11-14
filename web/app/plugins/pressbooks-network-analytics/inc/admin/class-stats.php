<?php

namespace PressbooksNetworkAnalytics\Admin;

use function PressbooksNetworkAnalytics\blade;
use PressbooksMix\Assets;
use PressbooksNetworkAnalytics\Model\BooksOverTime;
use PressbooksNetworkAnalytics\Model\MostActiveUsers;
use PressbooksNetworkAnalytics\Model\NetworkStorage;
use PressbooksNetworkAnalytics\Model\UsersOverTime;

class Stats extends Admin {

	/**
	 * @var Stats
	 */
	private static $instance = null;

	/**
	 * @return Stats
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param Stats $obj
	 */
	static public function hooks( Stats $obj ) {
		add_action( 'admin_enqueue_scripts', [ $obj, 'adminEnqueueScripts' ] );
		add_action( 'network_admin_menu', [ $obj, 'addMenu' ] );
		// Stats
		add_action( 'wp_ajax_pb_most_active_users_chart', [ $obj, 'ajaxGetMostActiveUsers' ] );
		add_action( 'wp_ajax_pb_users_over_time_chart', [ $obj, 'ajaxGetUsersOverTime' ] );
		add_action( 'wp_ajax_pb_books_over_time_chart', [ $obj, 'ajaxGetBooksOverTime' ] );
		add_action( 'wp_ajax_pb_network_storage_chart', [ $obj, 'ajaxNetworkStorage' ] );
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
		// Stats
		if ( $hook === get_plugin_page_hookname( $this->parentPage, 'toplevel_page' ) ) {
			$assets = new Assets( 'pressbooks-network-analytics', 'plugin' );
			// Stats Dashboard
			wp_enqueue_style( 'pb-network-analytics-admin', $assets->getPath( 'styles/stats.css' ) );
			wp_enqueue_script( 'pb-network-analytics-admin', $assets->getPath( 'scripts/stats.js' ), [ 'jquery', 'wp-i18n' ] );
			wp_localize_script(
				'pb-network-analytics-admin', 'PB_Network_AnalyticsToken', [
					'usersOverTimeAjaxUrl' => wp_nonce_url( admin_url( 'admin-ajax.php?action=pb_users_over_time_chart' ), 'pb-network-analytics' ),
					'mostActiveUsersAjaxUrl' => wp_nonce_url( admin_url( 'admin-ajax.php?action=pb_most_active_users_chart' ), 'pb-network-analytics' ),
					'booksOverTimeAjaxUrl' => wp_nonce_url( admin_url( 'admin-ajax.php?action=pb_books_over_time_chart' ), 'pb-network-analytics' ),
					'networkStorageAjaxUrl' => wp_nonce_url( admin_url( 'admin-ajax.php?action=pb_network_storage_chart' ), 'pb-network-analytics' ),
				]
			);
		}
	}

	/**
	 * Hooked into network_admin_menu
	 */
	public function addMenu() {
		// Stats
		add_menu_page(
			__( 'Stats', 'pressbooks-network-analytics' ),
			__( 'Stats', 'pressbooks-network-analytics' ),
			'manage_network',
			$this->parentPage,
			[ $this, 'printMenu' ],
			'dashicons-chart-area'
		);
	}

	/**
	 * Charts
	 */
	public function printMenu() {
		echo blade()->render( 'PressbooksNetworkAnalytics::admin' );
	}

	/**
	 * Hooked into wp_ajax_pb_most_active_users_chart
	 */
	public function ajaxGetMostActiveUsers() {
		check_ajax_referer( 'pb-network-analytics' );
		$data = new MostActiveUsers();
		wp_send_json(
			[
				'activeUsers' => $data->get(),
			]
		);
	}

	/**
	 * Hooked into wp_ajax_pb_users_over_time_chart
	 */
	public function ajaxGetUsersOverTime() {
		check_ajax_referer( 'pb-network-analytics' );
		$data = new UsersOverTime();
		wp_send_json(
			[
				'usersOverTime' => $data->get(),
			]
		);
	}

	/**
	 * Hooked into wp_ajax_pb_books_over_time_chart
	 */
	public function ajaxGetBooksOverTime() {
		check_ajax_referer( 'pb-network-analytics' );
		$data = new BooksOverTime();
		wp_send_json(
			[
				'booksOverTime' => $data->get(),
			]
		);
	}

	/**
	 * Hooked into wp_ajax_pb_network_storage_chart
	 */
	public function ajaxNetworkStorage() {
		check_ajax_referer( 'pb-network-analytics' );
		$data = new NetworkStorage();
		wp_send_json(
			[
				'networkStorage' => $data->get(),
			]
		);
	}

}
