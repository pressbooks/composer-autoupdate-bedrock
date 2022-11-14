<?php

namespace PressbooksNetworkAnalytics\Admin;

use function Pressbooks\Admin\NetworkManagers\is_restricted;
use function Pressbooks\Utility\str_ends_with;
use Pressbooks\Book;

class Menus extends Admin {

	/**
	 * @var Menus
	 */
	private static $instance = null;

	/**
	 * @return Menus
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param Menus $obj
	 */
	static public function hooks( Menus $obj ) {
		if ( Book::isBook() === false ) {
			add_action( 'network_admin_menu', [ $obj, 'shuffle' ], 1000 );
			add_filter( 'submenu_file', [ $obj, 'fixSubMenuFile' ], 10, 2 );
		}
		add_action( 'admin_bar_menu', [ $obj, 'fixAdminBar' ], 22 ); // Priorities... (Must come after Pressbooks \Pressbooks\Admin\Laf\replace_menu_bar_my_sites)
		add_filter( 'network_admin_url', [ $obj, 'fixNetworkAdminUrl' ], 10, 2 );
	}

	/**
	 * Network Manager Menu Shuffle
	 */
	public function shuffle() {
		if ( is_restricted() ) {
			remove_submenu_page( 'sites.php', 'sites.php' );
			remove_submenu_page( 'users.php', 'users.php' );
			remove_submenu_page( 'settings.php', 'pb_analytics' );
			remove_submenu_page( 'settings.php', 'pb_whitelabel_settings' );
			remove_submenu_page( 'settings.php', 'pressbooks_sharingandprivacy_options' );
		}
		$this->moveMenuItemToTop( 'sites.php', 'pb_network_analytics_booklist' );
		$this->moveMenuItemToTop( 'users.php', 'pb_network_analytics_userlist' );
		$this->moveMenuItemToTop( 'settings.php', 'pb_network_analytics_options' );
	}

	/**
	 * Move Menu Item To Top
	 *
	 * @param string $menu_key
	 * @param string $submenu_key
	 */
	private function moveMenuItemToTop( $menu_key, $submenu_key ) {
		global $submenu;
		if ( isset( $submenu[ $menu_key ] ) ) {
			$index = false;
			foreach ( $submenu[ $menu_key ] as $key => $item ) {
				if ( $item[2] === $submenu_key ) {
					$index = $key;
				}
			}
			if ( $index !== false ) {
				$tmp = $submenu[ $menu_key ][ $index ];
				unset( $submenu[ $menu_key ][ $index ] );
				reset( $submenu[ $menu_key ] );
				$first_key = key( $submenu[ $menu_key ] ) - 1;
				$submenu[ $menu_key ] = [ $first_key => $tmp ] + $submenu[ $menu_key ];
			}
		}
	}

	/**
	 * More menu output hacks (fixes selected menus)
	 *
	 * @param string $submenu_file The submenu file.
	 * @param string $parent_file The submenu item's parent file.
	 *
	 * @return string
	 *
	 * @see \Pressbooks\Admin\Laf\fix_submenu_file
	 */
	public function fixSubMenuFile( $submenu_file, $parent_file ) {
		if ( empty( $submenu_file ) ) {
			if ( $parent_file === 'settings.php' && isset( $_GET['page'] ) && $_GET['page'] === 'pb_network_analytics_options' ) {
				return 'pb_network_analytics_options';
			}
			if ( $parent_file === 'sites.php' && isset( $_GET['page'] ) && $_GET['page'] === 'pb_network_analytics_booklist' ) {
				return 'pb_network_analytics_booklist';
			}
			if ( $parent_file === 'users.php' && isset( $_GET['page'] ) && $_GET['page'] === 'pb_network_analytics_userlist' ) {
				return 'pb_network_analytics_userlist';
			}
		}

		return $submenu_file;
	}

	/**
	 * Change Network Admin menu
	 * Hooked into admin_bar_menu
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar
	 */
	public function fixAdminBar( $wp_admin_bar ) {
		if ( is_restricted() ) {
			// Remove Pressbooks Menus
			$wp_admin_bar->remove_node( 'pb-network-admin-s' );
			$wp_admin_bar->remove_node( 'pb-network-admin-u' );

			// Replace with Network Analytics Menus
			$wp_admin_bar->add_node(
				[
					'parent' => 'pb-network-admin',
					'id' => 'pb-network-analytics-books',
					'title' => __( 'Books', 'pressbooks-network-analytics' ),
					'href' => network_admin_url( '/admin.php?page=pb_network_analytics_booklist' ),
				]
			);
			$wp_admin_bar->add_node(
				[
					'parent' => 'pb-network-admin',
					'id' => 'pb-network-analytics-users',
					'title' => __( 'Users', 'pressbooks-network-analytics' ),
					'href' => network_admin_url( '/admin.php?page=pb_network_analytics_userlist' ),
				]
			);
		}
		$wp_admin_bar->add_node(
			[
				'parent' => 'pb-network-admin',
				'id' => 'pb-network-analytics-settings',
				'title' => __( 'Settings', 'pressbooks-network-analytics' ),
				'href' => network_admin_url( '/admin.php?page=pb_network_analytics_options' ),
			]
		);
		$wp_admin_bar->add_node(
			[
				'parent' => 'pb-network-admin',
				'id' => 'pb-network-analytics-stats',
				'title' => __( 'Stats', 'pressbooks-network-analytics' ),
				'href' => network_admin_url( '/admin.php?page=pb_network_analytics_admin' ),
			]
		);
	}

	/**
	 * Hooked into network_admin_url
	 *
	 * @param string $url The complete network admin URL including scheme and path.
	 * @param string $path Path relative to the network admin URL. Blank string if no path is specified.
	 *
	 * @return string
	 */
	public function fixNetworkAdminUrl( $url, $path ) {
		if ( is_restricted() ) {
			if ( str_ends_with( $url, '/users.php' ) ) {
				return network_admin_url( '/admin.php?page=pb_network_analytics_userlist' );
			}
			if ( str_ends_with( $url, '/sites.php' ) ) {
				return network_admin_url( '/admin.php?page=pb_network_analytics_booklist' );
			}
		}
		return $url;
	}

}
