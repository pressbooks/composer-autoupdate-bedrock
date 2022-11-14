<?php

namespace PressbooksBiblioBoardOAuth;

use PressbooksMix\Assets;

class OAuth {

	// Configs ----------------------------------------------------------------

	/**
	 * Supported providers
	 *
	 * @var array
	 */
	static $providers = [
		'biblioboard' => 'BiblioBoard',
	];

	/**
	 * Does a given provider require a secret? Default is true.
	 *
	 * @var array
	 */
	static $providerRequiresSecret = [
		'biblioboard' => false,
	];

	/**
	 * Authorization Url Configs
	 *
	 * @var array
	 */
	static $providerAuthorizationUrlConfigs = [
		'biblioboard' => [],
	];

	// Regularly scheduled program --------------------------------------------

	/**
	 * @var array
	 */
	protected $provider_names = [];

	/**
	 * @var array
	 */
	protected $provider_urls = [];

	/**
	 * @var array
	 */
	protected $client_ids = [];

	/**
	 * @var array
	 */
	protected $client_secrets = [];

	/**
	 * @var array
	 */
	protected $client_bypass = [];

	/**
	 * @var array
	 */
	protected $client_customize_login_buttons = [];

	/**
	 * @var bool
	 */
	protected $header_already_happened = false;

	/**
	 * @var bool
	 */
	protected $connect_already_happened = false;

	/**
	 * Constructor
	 */
	function __construct() {
		$option = get_site_option( 'pressbooks_oauth_options', [] );

		foreach ( $this->getActiveProviders() as $provider => $name ) {
			$this->provider_names[ $provider ] = $name;
			$this->provider_urls[ $provider ] =
				( $provider === 'biblioboard' )
				? 'https://library.biblioboard.com/welcome'
				: esc_url( 'https://' . $provider . '.com' ); // Fake it for non-Biblioboard providers.
			$this->client_ids[ $provider ] = isset( $option[ $provider . '_client_id' ] ) ? $option[ $provider . '_client_id' ] : '';
			$this->client_secrets[ $provider ] = isset( $option[ $provider . '_client_secret' ] ) ? $option[ $provider . '_client_secret' ] : '';
			$this->client_bypass[ $provider ] = ! empty( $option[ $provider . '_bypass' ] ) ? true : false;
			$this->client_customize_login_buttons[ $provider ] = isset( $option[ $provider . '_customize_login_button' ] ) ? $option[ $provider . '_customize_login_button' ] : '';
		}

		add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Init hook
	 */
	function init() {
		if ( ! empty( $this->client_ids ) ) {
			add_action( 'before_signup_header', [ $this, 'oauthHeader' ] );
			add_action( 'before_signup_form', [ $this, 'oauthConnect' ] );
			add_action( 'wp_logout', [ $this, 'clearSession' ] );
			add_filter( 'login_message', [ $this, 'oauthConnect' ] );
			add_action( 'pressbooks_oauth_connect', [ $this, 'oauthConnect' ] );
			add_action( 'pressbooks_oauth_buttons', [ $this, 'loginButtons' ] );
			add_action( 'login_enqueue_scripts', [ $this, 'loginEnqueueScripts' ] );
			add_action( 'login_form', [ $this, 'loginForm' ] );
		}
	}

	/**
	 * Print a button to the screen
	 *
	 * @param $provider
	 * @param $context
	 * @param $url
	 */
	function outputButton( $provider, $context, $url ) {
		/**
		 * @since 2.0.2
		 *
		 * @param bool $value
		 */
		if ( ! apply_filters( 'pb_oauth_output_button', true ) ) {
			return;
		}

		if ( $provider === 'biblioboard' ) {
			// Biblioboard have requested a copy change to the login button on the homepage of their networks
			$name = __( 'your local library', 'pressbooks-biblioboard-oauth' ); // TODO
		} else {
			$name = $this->provider_names[ $provider ];
		}

		if ( $this->client_customize_login_buttons[ $provider ] ) {
			$button_string = $this->client_customize_login_buttons[ $provider ];
		} else {
			switch ( $context ) {
				case 'register':
					/* translators: provider name */
					$button_string = sprintf( __( 'Register via %s', 'pressbooks-biblioboard-oauth' ), $name );
					break;
				case 'login':
					/* translators: provider name */
					$button_string = sprintf( __( 'Connect via %s', 'pressbooks-biblioboard-oauth' ), $name );
					break;
				default:
					$button_string = '';
			}
		}

		echo sprintf(
			'<div class="oauth oauth-' . $context . '"><a href="%s" class="button button-hero oauth">%s</a></div>',
			$url,
			$button_string
		);
	}

	/**
	 * Print a reset password link to the screen
	 *
	 * @param $provider
	 */
	function outputResetLink( $provider ) {
		if ( ! apply_filters( 'pb_oauth_output_button', true ) ) {
			return;
		}

		if ( $provider === 'biblioboard' ) {
			$link_string = __( 'Lost your password?', 'pressbooks-biblioboard-oauth' );
		} else {
			/* translators: provider name */
			$link_string = sprintf( __( 'Lost your %s password?', 'pressbooks-biblioboard-oauth' ), $this->provider_names[ $provider ] );
		}

		$url = $this->provider_urls[ $provider ];

		echo sprintf(
			'<p class="oauth-reset"><a href="%s">%s</a></p>',
			$url,
			$link_string
		);
	}

	/**
	 * Print a reset password string to the user profile screen
	 *
	 * @param $provider
	 */
	function outputProfileReset( $provider ) {
		if ( $provider === 'biblioboard' ) {
			$name = __( 'your local library', 'pressbooks-biblioboard-oauth' );
		} else {
			$name = $this->provider_names[ $provider ];
		}

		$url = $this->provider_urls[ $provider ];

		echo sprintf(
			'<table class="form-table" role="none"><tr class="reset-oauth-identity-password"><th>%1$s</th><td><p>%2$s</p><p class="description">%3$s</p></td></tr></table>',
			__( 'Reset Password', 'pressbooks-biblioboard-oauth' ),
			sprintf(
				/* translators: link to external password reset page */
				__( 'You can reset your password via %s.', 'pressbooks-biblioboard-oauth' ),
				sprintf(
					'<a href="%1$s">%2$s%3$s</a>',
					$url,
					$name,
					sprintf( '<span class="screen-reader-text"> %s</span>', __( '(opens in new window)', 'pressbooks-biblioboard-oauth' ) )
				)
			),
			''
		);
	}

	/**
	 * Oauth Header
	 */
	function oauthHeader() {

		if ( $this->header_already_happened ) {
			return;
		}

		$provider = $this->determineProvider();
		if ( $provider === false ) {
			return; // Bail!
		}

		if ( ! is_user_logged_in() && ! isset( $_GET['code'] ) ) { // @codingStandardsIgnoreLine
			// OAuth code required.
			$code_missing_error = __( 'Authorization code missing. Please try again.', 'pressbooks-biblioboard-oauth' );
			if ( ! empty( $_SESSION['oauth2result'] ) ) {
				$_SESSION['oauth2result'] .= " {$code_missing_error}"; // Append to previous message(s)
			} else {
				$_SESSION['oauth2result'] = $code_missing_error;
			}
		} elseif ( isset( $_GET['code'] ) ) { // @codingStandardsIgnoreLine
			try {
				$clients = $this->getActiveClients();

				// Try to get an access token using the authorization code grant.
				$access_token = $clients[ $provider ]->getAccessToken( 'authorization_code', [ 'code' => $_GET['code'] ] ); // @codingStandardsIgnoreLine

				// Using the access token, we may look up details about the resource owner.
				$resource_owner = $clients[ $provider ]->getResourceOwner( $access_token );

				// ID
				$identity = $resource_owner->getId();

				// Username
				if ( method_exists( $resource_owner, 'getUserName' ) ) {
					$username = $resource_owner->getUserName();
				} elseif ( method_exists( $resource_owner, 'getNickname' ) ) {
					$username = $resource_owner->getNickname();
				} elseif ( method_exists( $resource_owner, 'getName' ) ) {
					$username = $resource_owner->getName();
				} else {
					$username = 'oauth';
				}

				// Email
				if ( method_exists( $resource_owner, 'getEmail' ) ) {
					$email = $resource_owner->getEmail();
				}
				if ( empty( $email ) ) {
					$email = $this->getEmailAgain( $clients[ $provider ], $provider, $access_token, $identity );
				}

				$this->handleLoginAttempt( $username, $email, $identity, $provider );

			} catch ( \League\OAuth2\Client\Provider\Exception\IdentityProviderException $e ) {
				$this->endLogin( $e->getMessage() );
			}
		}

		$this->header_already_happened = true;
	}

	/**
	 * Try to get an email using other means
	 *
	 * @param \League\OAuth2\Client\Provider\AbstractProvider $client
	 * @param string $provider
	 * @param string $access_token
	 * @param string $identity
	 *
	 * @return string;
	 */
	protected function getEmailAgain( $client, $provider, $access_token, $identity ) {

		// TODO: Add code to get emails using other means here

		if ( empty( $email ) ) {
			// Make up some email like: oauth-id-1234@noreply.github.com
			$domain = wp_parse_url( $client->getBaseAuthorizationUrl(), PHP_URL_HOST );
			if ( preg_match( '/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs ) ) {
				$domain = $regs['domain']; // Strip subdomain
			}
			$email = "oauth-id-{$identity}@noreply.{$domain}";
		}

		return $email;
	}

	/**
	 * Different configurations for different providers
	 *
	 * @param string
	 *
	 * @return array
	 */
	function getAuthorizationUrlConfig( $provider ) {
		if ( isset( static::$providerAuthorizationUrlConfigs[ $provider ] ) ) {
			return static::$providerAuthorizationUrlConfigs[ $provider ];
		}
		return [];
	}

	/**
	 *
	 */
	function loginEnqueueScripts() {
		$assets = new Assets( 'pressbooks-biblioboard-oauth', 'plugin' );
		$assets->setSrcDirectory( 'assets' )->setDistDirectory( 'dist' );
		wp_enqueue_style( 'pb-bb-oauth-login', $assets->getPath( 'styles/login-form.css' ) );
		wp_enqueue_script( 'pb-bb-oauth-login', $assets->getPath( 'scripts/login-form.js' ), [ 'jquery' ] );
	}

	/**
	 *
	 */
	function loginForm() {
		if ( strpos( $_SERVER['REQUEST_URI'], 'wp-signup.php' ) ) {
			$context = 'register';
		} else {
			$context = 'login';
		}
		?>
		<div id="pb-oauth-wrap">
			<div class="pb-oauth-or">
				<span><?php esc_html_e( 'Or', 'pressbooks-biblioboard-oauth' ); ?></span>
			</div>
			<?php $this->loginButtons( $context ); ?>
		</div>
		<?php
	}

	/**
	 * @param string $context
	 */
	function loginButtons( $context ) {
		if ( empty( $context ) ) {
			$context = 'login';
		}
		foreach ( $this->getActiveClients() as $slug => $client ) {
			// Fetch the authorization URL, generating and applying any necessary parameters.
			$authorization_url = $client->getAuthorizationUrl( $this->getAuthorizationUrlConfig( $slug ) );
			$this->outputButton( $slug, $context, $authorization_url );
			$this->outputResetLink( $slug );
		}
	}

	/**
	 * Oauth Connect
	 *
	 * @param string $message
	 */
	function oauthConnect( $message = '' ) {

		if ( $this->connect_already_happened ) {
			return;
		}

		if ( isset( $_SESSION['oauth2result'] ) ) {
			echo '<div id="login_error">' . $_SESSION['oauth2result'] . '<br></div>' . $message;
			unset( $_SESSION['oauth2result'] );
		}
		foreach ( [ 'error', 'error_description' ] as $e ) {
			if ( ! empty( $_GET[ $e ] ) ) { // @codingStandardsIgnoreLine
				echo '<div id="login_error">' . esc_html( $_GET[ $e ] ) . '<br></div>' . $message; // @codingStandardsIgnoreLine
			}
		}

		$provider = $this->determineProvider();

		if ( isset( $_GET['code'] ) ) { // @codingStandardsIgnoreLine
			if ( empty( $_GET['state'] ) || ( $provider && $_GET['state'] !== $_SESSION[ "oauth2state_{$provider}" ] ) ) { // @codingStandardsIgnoreLine
				$this->clearSessionState();
				echo '<div id="login_error">' . __( 'Invalid state. Please try again.', 'pressbooks-biblioboard-oauth' ) . '</div>' . $message;
			}
		}

		if ( ! is_user_logged_in() ) {
			foreach ( $this->getActiveClients() as $slug => $client ) {
				if ( ! isset( $_GET['code'] ) ) { // @codingStandardsIgnoreLine
					// Get the state generated for you and store it to the session.
					$_SESSION[ "oauth2state_{$slug}" ] = $client->getState();
				}
			}
		}

		echo $message;

		$this->connect_already_happened = true;
	}

	/**
	 * Normalize a URL by removing the subdomain.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	function normalizeUrl( $url ) {
		$tmp = explode( '.', wp_parse_url( $url, PHP_URL_HOST ) );
		$tmp = array_reverse( $tmp );
		return $tmp[1] . '.' . $tmp[0];
	}

	/**
	 * Login (or register and login) a WordPress user based on their oauth identity.
	 *
	 * @param string $username
	 * @param string $email
	 * @param string $identity
	 * @param string $provider
	 */
	function handleLoginAttempt( $username, $email, $identity, $provider ) {
		// Try to find a matching WordPress user for the now-authenticated user's OAuth identity
		$user = $this->matchUser( $identity, $provider );

		if ( $user ) {
			// If a matching user was found, log it in
			$logged_in = $this->programmaticLogin( $user->user_login );
			if ( $logged_in === true ) {
				$this->endLogin( 'Logged in!' );
			}
		} else {
			// handle the logged out user or no matching user (register the user):
			try {
				$this->associateUser( $username, $email, $identity, $provider );
			} catch ( \Exception $e ) {
				$this->endLogin( "Sorry, we couldn't log you in. The login flow terminated in an unexpected way. Please notify the admin or try again later." );
			}
		}
	}

	/**
	 * Attempt to match a WordPress user to the oauth identity.
	 *
	 * @param string $identity
	 * @param string $provider
	 *
	 * @return false|\WP_User
	 */
	function matchUser( $identity, $provider ) {
		global $wpdb;
		$condition = "%{$provider}|{$identity}%";
		$query_result = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'pressbooks_oauth2_identity' AND meta_value LIKE %s", $condition ) );
		// attempt to get a WordPress user with the matched id:
		$user = get_user_by( 'id', $query_result );
		return $user;
	}

	/**
	 * Multisite has more restrictions on user login character set
	 *
	 * @see https://core.trac.wordpress.org/ticket/17904
	 *
	 * @param string $username
	 *
	 * @return string
	 */
	function sanitizeUser( $username ) {
		$unique_username = sanitize_user( $username, true );
		$unique_username = strtolower( $unique_username );
		$unique_username = preg_replace( '/[^a-z0-9]/', '', $unique_username );
		if ( preg_match( '/^[0-9]*$/', $unique_username ) ) {
			$unique_username = "u{$unique_username}"; // usernames must have letters too
		}
		return $unique_username;
	}

	/**
	 * Associate user
	 *
	 * @param string $username
	 * @param string $email
	 * @param string $identity
	 * @param string $provider
	 */
	function associateUser( $username, $email, $identity, $provider ) {

		$user = get_user_by( 'email', $email );
		if ( $user ) {
			// Associate existing users with OAuth accounts
			$user_id = $user->ID;
			$username = $user->user_login;
		} else {
			list( $user_id, $username ) = $this->createUser( $username, $email, $provider );
		}

		// Registration was successful, the user account was created (or associated), proceed to login the user automatically...
		// associate the WordPress user account with the now-authenticated third party account:
		$this->linkAccount( $user_id, $identity, $provider );

		// Attempt to login the new user (this could be error prone):
		$logged_in = $this->programmaticLogin( $username );
		if ( $logged_in === true ) {
			$this->endLogin( 'Registered and logged in!' );
		}
	}

	/**
	 * Create user (redirects if there is an error)
	 *
	 * @param string $username
	 * @param string $email
	 * @param string $provider
	 *
	 * @return array [ (int) user_id, (string) sanitized username ]
	 */
	function createUser( $username, $email, $provider ) {
		$i = 1;
		$unique_username = $this->sanitizeUser( $username );
		while ( username_exists( $unique_username ) ) {
			$unique_username = $this->sanitizeUser( "{$username}{$i}" );
			++$i;
		}

		// Validate
		if ( ! $this->client_bypass[ $provider ] ) {
			remove_all_filters( 'wpmu_validate_user_signup' );
			$user_result = wpmu_validate_user_signup( $unique_username, $email );
			$username = $user_result['user_name'];
			$email = $user_result['user_email'];
			$errors = $user_result['errors'];
		} else {
			$username = $unique_username;
			$email = sanitize_email( $email );
			$errors = null;
		}

		/** @var \WP_Error $errors */
		if ( ! empty( $errors->errors ) ) {
			$error = '';
			foreach ( $errors->get_error_messages() as $message ) {
				$error .= "{$message} ";
			}
			$_SESSION['oauth2result'] = $error;
			header( 'Location: ' . get_admin_url() );
			exit;
		}

		// Attempt to generate the user and get the user id
		// we use wp_create_user instead of wp_insert_user so we can handle the error when the user being registered already exists
		$user_id = wp_create_user( $username, wp_generate_password(), $email );

		// Check if the user was actually created:
		if ( is_wp_error( $user_id ) ) {
			// there was an error during registration, redirect and notify the user:
			$_SESSION['oauth2result'] = $user_id->get_error_message();
			header( 'Location: ' . get_admin_url() );
			exit;
		}

		remove_user_from_blog( $user_id, 1 );

		return [ $user_id, $username ];
	}

	/**
	 * Link a user to their oauth identity
	 *
	 * @param int $user_id
	 * @param string $identity
	 * @param string $provider
	 */
	function linkAccount( $user_id, $identity, $provider ) {
		add_user_meta( $user_id, 'pressbooks_oauth2_identity', $provider . '|' . $identity . '|' . time() );
	}

	/**
	 * Clear all session variables
	 */
	function clearSession() {
		$this->clearSessionState();
		unset( $_SESSION['oauth2result'] );
	}

	/**
	 * Clear session state variables
	 */
	function clearSessionState() {
		foreach ( static::$providers as $provider => $name ) {
			unset( $_SESSION[ "oauth2state_{$provider}" ] );
		}
	}

	/**
	 * Ends the login request by redirecting to the desired page
	 *
	 * @param string $msg
	 */
	function endLogin( $msg ) {
		$this->clearSession();
		$_SESSION['oauth2result'] = $msg;
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			$blog = get_active_blog_for_user( $user->ID );
			if ( $blog ) {
				wp_safe_redirect( get_admin_url( $blog->blog_id ) );
				exit;
			} else {
				wp_safe_redirect( wp_registration_url() );
				exit;
			}
		} else {
			wp_safe_redirect( wp_registration_url() );
			exit;
		}
	}

	/**
	 * Programmatically logs a user in
	 *
	 * @param string $username
	 * @return bool True if the login was successful; false if it wasn't
	 */
	function programmaticLogin( $username ) {

		if ( is_user_logged_in() ) {
			wp_logout();
		}

		add_filter( 'authenticate', [ $this, 'allowProgrammaticLogin' ], 10, 3 ); // hook in earlier than other callbacks to short-circuit them
		$user = wp_signon( [ 'user_login' => $username ] );
		remove_filter( 'authenticate', [ $this, 'allowProgrammaticLogin' ], 10 );

		if ( is_a( $user, 'WP_User' ) ) {
			wp_set_current_user( $user->ID, $user->user_login );
			if ( is_user_logged_in() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * An 'authenticate' filter callback that authenticates the user using only the username.
	 *
	 * To avoid potential security vulnerabilities, this should only be used in the context of a programmatic login,
	 * and unhooked immediately after it fires.
	 *
	 * @param \WP_User $user
	 * @param string $username
	 * @param string $password
	 * @return bool|\WP_User a WP_User object if the username matched an existing user, or false if it didn't
	 */
	function allowProgrammaticLogin( $user, $username, $password ) {
		return get_user_by( 'login', $username );
	}

	/**
	 * @return array
	 */
	function getActiveProviders() {
		$option = get_site_option( 'pressbooks_oauth_options', [] );
		$tmp = static::$providers;
		$providers = [];
		foreach ( $tmp as $provider => $name ) {
			if ( false === self::requiresSecret( $provider ) && ! empty( $option[ $provider . '_client_id' ] ) ) {
				$providers[ $provider ] = $tmp[ $provider ];
			} elseif ( ! empty( $option[ $provider . '_client_id' ] ) && ! empty( $option[ $provider . '_client_secret' ] ) ) {
				$providers[ $provider ] = $tmp[ $provider ];
			}
		}
		return $providers;
	}

	/**
	 * Get active League\Oauth2\Client objects
	 *
	 * @return \League\OAuth2\Client\Provider\AbstractProvider[]
	 */
	function getActiveClients() {
		static $clients = [];
		if ( empty( $clients ) ) {
			foreach ( $this->getActiveProviders() as $slug => $name ) {
				if ( in_array( $slug, [ 'biblioboard' ], true ) ) {
					$redirect_uri = network_site_url( '/wp-signup.php' ); // Classic rock
				} else {
					$redirect_uri = network_site_url( "/oauth/{$slug}" );
				}
				$class = '\League\OAuth2\Client\Provider\\' . $name;
				$clients[ $slug ] = new $class(
					[
						'clientId' => $this->client_ids[ $slug ],
						'clientSecret' => $this->client_secrets[ $slug ],
						'redirectUri' => $redirect_uri,
					]
				);
			}
		}
		return $clients;
	}

	/**
	 * Determine the provider
	 *
	 * @return bool|string
	 */
	function determineProvider() {
		$provider = false;
		$clients = $this->getActiveClients();

		$who_is_this = get_query_var( 'pb_oauth_provider' );
		if ( $who_is_this ) {
			foreach ( $clients as $slug => $client ) {
				if ( $slug === $who_is_this ) {
					return $slug;
				}
			}
		}

		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			foreach ( $clients as $slug => $client ) {
				$url = $client->getAuthorizationUrl( $this->getAuthorizationUrlConfig( $slug ) );
				if ( 0 === strcmp( $this->normalizeUrl( $_SERVER['HTTP_REFERER'] ), $this->normalizeUrl( $url ) ) ) {
					return $slug;
				}
			}
		}

		return $provider;
	}

	/**
	 * Doest the provider require a secret?
	 *
	 * @param string $provider
	 *
	 * @return bool
	 */
	static function requiresSecret( $provider ) {
		if ( isset( static::$providerRequiresSecret[ $provider ] ) ) {
			return static::$providerRequiresSecret[ $provider ];
		}
		return true;
	}

	/**
	 * Login Stylesheets
	 */
	static function loginStylesheet() {
		$assets = new Assets( 'pressbooks-biblioboard-oauth', 'plugin' );
		$assets->setSrcDirectory( 'assets' )->setDistDirectory( 'dist' );
		echo '<link rel="stylesheet" type="text/css" href="' . $assets->getPath( 'styles/oauth.css' ) . '" />';
	}

	/**
	 * Registration Stylesheets
	 */
	static function registrationStylesheet() {
		$assets = new Assets( 'pressbooks-biblioboard-oauth', 'plugin' );
		$assets->setSrcDirectory( 'assets' )->setDistDirectory( 'dist' );
		echo '<link rel="stylesheet" type="text/css" href="' . $assets->getPath( 'styles/oauth.css' ) . '" />';
	}
}
