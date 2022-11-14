<?php

use PressbooksNetworkAnalytics\Admin\Users;

class Admin_UsersTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var Users
	 */
	protected $users;

	/**
	 *
	 */
	public function set_up() {
		parent::set_up();
		$this->users = new Users();
	}

	public function test_adminEnqueueScripts() {
		global $wp_scripts, $wp_styles;
		$wp_scripts = new WP_Scripts();
		$wp_styles = new WP_Styles();

		$some_other_hook = 'foo';
		$this->users->adminEnqueueScripts( $some_other_hook );
		$this->assertNotContains( 'pb-network-analytics-userlist', $wp_scripts->queue );
		$this->assertNotContains( 'pb-network-analytics-userlist', $wp_styles->queue );
		$this->assertArrayNotHasKey( 'jquery-ui-tabs', $wp_styles->registered );
		$this->assertArrayNotHasKey( 'tabulator', $wp_scripts->registered );
		$this->assertArrayNotHasKey( 'tabulator', $wp_styles->registered );

		$hook = get_plugin_page_hookname( 'pb_network_analytics_userlist', $this->users->parentPage );
		$this->users->adminEnqueueScripts( $hook );
		$this->assertContains( 'pb-network-analytics-userlist', $wp_scripts->queue );
		$this->assertContains( 'pb-network-analytics-userlist', $wp_styles->queue );
		$this->assertArrayHasKey( 'jquery-ui-tabs', $wp_styles->registered );
		$this->assertArrayHasKey( 'tabulator', $wp_scripts->registered );
		$this->assertArrayHasKey( 'tabulator', $wp_styles->registered );
	}

	public function test_addMenu() {
		global $_wp_submenu_nopriv;
		$_wp_submenu_nopriv = [];
		$this->users->addMenu();
		$this->assertTrue( isset( $_wp_submenu_nopriv['users.php']['pb_network_analytics_userlist'] ) );
	}

	public function test_printMenuSettings() {
		ob_start();
		$this->users->printMenuUserList();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<h1>User List</h1>', $buffer );
		$this->assertStringContainsString( '<div id="userlist"></div>', $buffer );
	}

	public function test_printMenuUserInfo() {
		ob_start();
		$user_id = $this->factory()->user->create( [ 'role' => 'contributor', 'first_name' => 'Joey', 'last_name' => 'Joe Joe' ] );
		$_REQUEST['id'] = $user_id;
		$this->users->printMenuUserInfo();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<h1>User Info</h1>', $buffer );
		$this->assertStringContainsString( 'Edit This User', $buffer );
	}

	public function test_ajaxGetUsersJson() {
		$old_error_reporting = $this->_fakeAjax();
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'pb-network-analytics-userlist' );
		ob_start();
		$this->users->ajaxGetUsersJson();
		$buffer = ob_get_clean();
		$this->_fakeAjaxDone( $old_error_reporting );
		$this->assertJson( $buffer );
	}

}
