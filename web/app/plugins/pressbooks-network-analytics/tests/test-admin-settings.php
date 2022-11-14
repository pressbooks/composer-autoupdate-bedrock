<?php

use PressbooksNetworkAnalytics\Admin\Options;

class Admin_SettingsTest extends \WP_UnitTestCase {

	/**
	 * @var Options
	 */
	protected $settings;

	/**
	 *
	 */
	public function set_up() {
		parent::set_up();
		$this->settings = new Options();
	}

	public function test_adminEnqueueScripts() {
		global $wp_scripts, $wp_styles;
		$wp_scripts = new WP_Scripts();
		$wp_styles = new WP_Styles();

		$some_other_hook = 'foo';
		$this->settings->adminEnqueueScripts( $some_other_hook );
		$this->assertNotContains( 'pb-network-analytics-settings', $wp_scripts->queue );
		$this->assertNotContains( 'pb-network-analytics-settings', $wp_styles->queue );
		$this->assertArrayNotHasKey( 'jquery-ui-tabs', $wp_styles->registered );
		$this->assertArrayNotHasKey( 'tabulator', $wp_scripts->registered );
		$this->assertArrayNotHasKey( 'tabulator', $wp_styles->registered );

		$hook = get_plugin_page_hookname( 'pb_network_analytics_options', $this->settings->parentPage );
		$this->settings->adminEnqueueScripts( $hook );
		$this->assertContains( 'pb-network-analytics-settings', $wp_scripts->queue );
		$this->assertContains( 'pb-network-analytics-settings', $wp_styles->queue );
		$this->assertArrayHasKey( 'jquery-ui-tabs', $wp_styles->registered );
		$this->assertArrayNotHasKey( 'tabulator', $wp_scripts->registered ); // No Tabulator on settings page
		$this->assertArrayNotHasKey( 'tabulator', $wp_styles->registered );
	}

	public function test_addMenu() {
		global $_wp_submenu_nopriv;
		$_wp_submenu_nopriv = [];
		$this->settings->addMenu();
		$this->assertTrue( isset( $_wp_submenu_nopriv['settings.php']['pb_network_analytics_options'] ) );
	}

	public function test_printMenuSettings() {
		ob_start();
		$this->settings->printMenuSettings();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<h1>Network Settings</h1>', $buffer );
		$this->assertStringContainsString( '</form>', $buffer );
	}

	public function test_getNetworkManagers() {
		$x = $this->settings->getNetworkManagers();
		$this->assertTrue( is_array( $x ) );
	}

	public function test_getSettings() {
		$x = $this->settings->getSettings();
		$this->assertArrayHasKey( 'registration_1', $x );
		$this->assertArrayHasKey( 'registration_2', $x );
	}

}
