<?php

trait utilsTrait {



	/**
	 * @return int
	 */
	private function _fakeAjax() {
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', '__return_false', 1, 1 ); // Override die()
		return error_reporting( error_reporting() & ~E_WARNING ); // Suppress warnings
	}

	/**
	 * @param int $old_error_reporting
	 */
	private function _fakeAjaxDone( $old_error_reporting ) {
		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', '__return_false', 1 );
		error_reporting( $old_error_reporting );
	}
}
