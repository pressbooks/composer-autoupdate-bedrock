<?php

use PressbooksNetworkAnalytics\Admin\Menus;

class Admin_MenusTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var Menus
	 */
	protected $menus;

	/**
	 *
	 */
	public function set_up() {
		parent::set_up();
		$this->menus = new Menus();
	}

	public function test_shuffle() {
		// set_up
		global $submenu;
		$old_submenu = $submenu;
		$submenu['settings.php'] = [
			[
				2 => 'pb_analytics',
			],
			[
				2 => 'pb_whitelabel_settings',
			],
			[
				2 => 'pressbooks_sharingandprivacy_options',
			],
		];

		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		grant_super_admin( $user_id );
		wp_set_current_user( $user_id );

		$this->menus->shuffle();
		$this->assertEquals( 'pb_analytics', $submenu['settings.php'][0][2] );
		$this->assertEquals( 'pb_whitelabel_settings', $submenu['settings.php'][1][2] );
		$this->assertEquals( 'pressbooks_sharingandprivacy_options', $submenu['settings.php'][2][2] );

		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'pb-network-managers' );
		$_POST['admin_id'] = $user_id;
		$_POST['status'] = '1';
		\Pressbooks\Admin\NetworkManagers\update_admin_status();

		$this->menus->shuffle();
		$this->assertTrue( ! isset( $submenu['settings.php'][0][2] ) );
		$this->assertTrue( ! isset( $submenu['settings.php'][1][2] ) );
		$this->assertTrue( ! isset( $submenu['settings.php'][2][2] ) );


		// Tear down
		delete_site_option( 'pressbooks_network_managers' );
		$submenu = $old_submenu;
	}


}

