<?php

use PressbooksNetworkAnalytics\Admin\Books;

class Admin_BooksTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var Books
	 */
	protected $books;

	/**
	 *
	 */
	public function set_up() {
		parent::set_up();
		$this->books = new Books();
	}

	public function test_adminEnqueueScripts() {
		global $wp_scripts, $wp_styles;
		$wp_scripts = new WP_Scripts();
		$wp_styles = new WP_Styles();

		$some_other_hook = 'foo';
		$this->books->adminEnqueueScripts( $some_other_hook );
		$this->assertNotContains( 'pb-network-analytics-booklist', $wp_scripts->queue );
		$this->assertNotContains( 'pb-network-analytics-booklist', $wp_styles->queue );
		$this->assertArrayNotHasKey( 'jquery-ui-tabs', $wp_styles->registered );
		$this->assertArrayNotHasKey( 'tabulator', $wp_scripts->registered );
		$this->assertArrayNotHasKey( 'tabulator', $wp_styles->registered );

		$hook = get_plugin_page_hookname( 'pb_network_analytics_booklist', $this->books->parentPage );
		$this->books->adminEnqueueScripts( $hook );
		$this->assertContains( 'pb-network-analytics-booklist', $wp_scripts->queue );
		$this->assertContains( 'pb-network-analytics-booklist', $wp_styles->queue );
		$this->assertArrayHasKey( 'jquery-ui-tabs', $wp_styles->registered );
		$this->assertArrayHasKey( 'tabulator', $wp_scripts->registered );
		$this->assertArrayHasKey( 'tabulator', $wp_styles->registered );
	}

	public function test_addMenu() {
		global $_wp_submenu_nopriv;
		$_wp_submenu_nopriv = [];
		$this->books->addMenu();
		$this->assertTrue( isset( $_wp_submenu_nopriv['sites.php']['pb_network_analytics_booklist'] ) );
		$this->assertTrue( isset( $_wp_submenu_nopriv['sites.php']['pb_network_analytics_booklist_sync'] ) );
	}

	public function test_printMenuSettings() {
		ob_start();
		$this->books->printMenuBookList();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<h1>Book List</h1>', $buffer );
		$this->assertStringContainsString( '<div id="booklist"></div>', $buffer );
	}

	public function test_printMenuBookListSync() {
		ob_start();
		$this->books->printMenuBookListSync();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( 'Finished at:', $buffer );
	}

	public function test_ajaxGetBooksJson() {
		$old_error_reporting = $this->_fakeAjax();
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'pb-network-analytics-booklist' );
		ob_start();
		$this->books->ajaxGetBooksJson();
		$buffer = ob_get_clean();
		$this->_fakeAjaxDone( $old_error_reporting );
		$this->assertJson( $buffer );
		$data = json_decode( $buffer, true );
		foreach ( $data['data'] as $book ) {
			$this->assertNotEquals( 1, intval( $book['id'][1] ) );
			$this->assertArrayHasKey( 'users_email', $book );
		}
	}

	public function test_reflectPrivacyStatus() {
		$book = \WP_Site::get_instance( get_current_blog_id() );

		update_option( \Pressbooks\Metadata\get_in_catalog_option(), 1 );
		$this->books->reflectPrivacyStatus( null, [ 'public' => 0 ], $book );
		$this->assertEmpty( get_option( 'blog_public' ) );
		$this->assertEmpty( get_option( \Pressbooks\Metadata\get_in_catalog_option() ) );

		$this->books->reflectPrivacyStatus( null, [ 'public' => 1 ], $book );
		$this->assertNotEmpty( get_option( 'blog_public' ) );
		$this->assertEmpty( get_option( \Pressbooks\Metadata\get_in_catalog_option() ) );
	}

}
