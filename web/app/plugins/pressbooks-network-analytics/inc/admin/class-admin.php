<?php

namespace PressbooksNetworkAnalytics\Admin;

abstract class Admin {

	/**
	 * The slug name for the parent menu (or the file name of a standard WordPress admin page).
	 *
	 * @var string
	 */
	public $parentPage = 'pb_network_analytics_admin';

	/**
	 * Register Jquery UI tabs scripts and styles
	 */
	public function registerJqueryTabsAssets() {
		global $wp_scripts;
		wp_register_style( 'jquery-ui-tabs', 'https://ajax.googleapis.com/ajax/libs/jqueryui/' . $wp_scripts->registered['jquery-ui-core']->ver . '/themes/smoothness/jquery-ui.css', [], $wp_scripts->registered['jquery-ui-core']->ver );
	}

	/**
	 * Register Tabulator scripts and styles
	 */
	public function registerTabulatorAssets() {
		$tabulator_version = '4.5.2';
		$my_dist = plugins_url( trailingslashit( 'pressbooks-network-analytics' ) . 'assets/dist' );
		wp_register_style( 'tabulator', "{$my_dist}/styles/tabulator/semantic-ui/tabulator_semantic-ui.min.css", [], $tabulator_version );
		wp_register_script( 'tabulator', "{$my_dist}/scripts/tabulator/tabulator.min.js", [], $tabulator_version );
		$this->registerJqueryTabsAssets();
	}

}
