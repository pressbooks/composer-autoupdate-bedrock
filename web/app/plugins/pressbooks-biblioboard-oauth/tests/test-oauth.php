<?php

class OAuthTest extends WP_UnitTestCase {

	/**
	 * @var \PressbooksBiblioBoardOAuth\OAuth()
	 */
	protected $oauth;

	/**
	 *
	 */
	public function set_up() {
		parent::set_up();

		$providers = [
			'biblioboard_client_id' => 'TODO',
			'biblioboard_client_secret' => '',
		];
		update_site_option( 'pressbooks_oauth_options', $providers );

		$this->oauth = new \PressbooksBiblioBoardOAuth\OAuth();
	}


	/**
	 * @covers \PressbooksBiblioBoardOAuth\OAuth::init
	 */
	public function test_init() {
		$msg = '\PressbooksBiblioBoardOAuth\OAuth::init is not registering expected hook or filter.';
		$this->oauth->init();
		$this->assertEquals( 10, has_filter( 'login_message', [ $this->oauth, 'oauthConnect' ] ), $msg );
		$this->assertEquals( 10, has_filter( 'pressbooks_oauth_connect', [ $this->oauth, 'oauthConnect' ] ), $msg );
		$this->assertEquals( 10, has_action( 'before_signup_header', [ $this->oauth, 'oauthHeader' ] ), $msg );
		$this->assertEquals( 10, has_action( 'wp_logout', [ $this->oauth, 'clearSession' ] ), $msg );
		$this->assertEquals( 10, has_action( 'before_signup_form', [ $this->oauth, 'oauthConnect' ] ), $msg );
	}

}
