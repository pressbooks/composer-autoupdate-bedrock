<?php

use PressbooksNetworkAnalytics\Admin\Stats;

class Admin_StatsTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var Stats
	 */
	protected $stats;

	/**
	 *
	 */
	public function set_up() {
		parent::set_up();
		$this->stats = new Stats();
	}

	public function test_adminEnqueueScripts() {
		global $wp_scripts, $wp_styles;
		$wp_scripts = new WP_Scripts();
		$wp_styles = new WP_Styles();

		$some_other_hook = 'foo';
		$this->stats->adminEnqueueScripts( $some_other_hook );
		$this->assertNotContains( 'pb-network-analytics-admin', $wp_scripts->queue );
		$this->assertNotContains( 'pb-network-analytics-admin', $wp_styles->queue );
		$this->assertArrayNotHasKey( 'jquery-ui-tabs', $wp_styles->registered );
		$this->assertArrayNotHasKey( 'tabulator', $wp_scripts->registered );
		$this->assertArrayNotHasKey( 'tabulator', $wp_styles->registered );

		$hook = get_plugin_page_hookname( 'pb_network_analytics_admin', 'toplevel_page' );
		$this->stats->adminEnqueueScripts( $hook );
		$this->assertContains( 'pb-network-analytics-admin', $wp_scripts->queue );
		$this->assertContains( 'pb-network-analytics-admin', $wp_styles->queue );
		$this->assertArrayNotHasKey( 'jquery-ui-tabs', $wp_styles->registered ); // No Tabs or Tabulator on Stats page
		$this->assertArrayNotHasKey( 'tabulator', $wp_scripts->registered );
		$this->assertArrayNotHasKey( 'tabulator', $wp_styles->registered );
	}

	public function test_addMenu() {
		global $_registered_pages;
		$_registered_pages = [];
		$this->stats->addMenu();
		$this->assertTrue( isset( $_registered_pages['toplevel_page_pb_network_analytics_admin'] ) );
	}

	public function test_printMenuSettings() {
		ob_start();
		$this->stats->printMenu();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<div class="chart chart1">', $buffer );
		$this->assertStringContainsString( '<div class="chart chart2">', $buffer );
		$this->assertStringContainsString( '<div class="chart chart3">', $buffer );
		$this->assertStringContainsString( '<div class="chart chart4">', $buffer );
	}

	public function test_ajaxGetMostActiveUsers() {
		$old_error_reporting = $this->_fakeAjax();
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'pb-network-analytics' );
		ob_start();
		$this->stats->ajaxGetMostActiveUsers();
		$buffer = ob_get_clean();
		$this->_fakeAjaxDone( $old_error_reporting );
		$this->assertJson( $buffer );
	}

	public function test_ajaxGetUsersOverTime() {
		$old_error_reporting = $this->_fakeAjax();
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'pb-network-analytics' );
		ob_start();
		$this->stats->ajaxGetUsersOverTime();
		$buffer = ob_get_clean();
		$this->_fakeAjaxDone( $old_error_reporting );
		$this->assertJson( $buffer );
	}

	public function test_ajaxGetBooksOverTime() {
		$old_error_reporting = $this->_fakeAjax();
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'pb-network-analytics' );
		ob_start();
		$this->stats->ajaxGetBooksOverTime();
		$buffer = ob_get_clean();
		$this->_fakeAjaxDone( $old_error_reporting );
		$this->assertJson( $buffer );
	}

	public function test_ajaxNetworkStorage() {
		$old_error_reporting = $this->_fakeAjax();
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'pb-network-analytics' );
		ob_start();
		$this->stats->ajaxNetworkStorage();
		$buffer = ob_get_clean();
		$this->_fakeAjaxDone( $old_error_reporting );
		$this->assertJson( $buffer );
	}

}
