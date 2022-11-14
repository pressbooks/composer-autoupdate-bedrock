<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wrap">
	<h1><?php _e( 'BiblioBoard OAuth', 'pressbooks-biblioboard-oauth' ); ?></h1>
	<p><?php _e( 'You can configure the BiblioBoard OAuth 2.0 provider as alternatives to username and password-based registration for your Pressbooks network.', 'pressbooks-biblioboard-oauth' ); ?></p>
	<?php
	$nonce = $_REQUEST['_wpnonce'] ?? '';
	if ( ! empty( $_POST ) ) {
		if ( ! wp_verify_nonce( $nonce, 'pressbooks_biblioboard_oauth-options' ) ) {
			die( 'Security check' );
		}
		$pressbooks_oauth_options = get_site_option( 'pressbooks_oauth_options', [] );
		$option = get_site_option( 'registration', false, false );
		$standard_registration = \PressbooksBiblioBoardOAuth\Admin\oauth_enable_standard_registration_sanitize( $_REQUEST['enable_standard_registration'] ?? 0 );
		if ( $option === 'all' || $option === 'blog' ) {
			if ( (int) $standard_registration === 1 ) {
				$registration = 'all';
			} else {
				$registration = 'blog';
			}
		} elseif ( $option === 'user' || $option === 'none' ) {
			if ( (int) $standard_registration === 1 ) {
				$registration = 'user';
			} else {
				$registration = 'none';
			}
		} else {
			$registration = null;
		}
		foreach ( \PressbooksBiblioBoardOAuth\OAuth::$providers as $k => $v ) {
			if ( isset( $_REQUEST['pressbooks_oauth_options'][ $k . '_client_id' ] ) ) {
				$pressbooks_oauth_options[ $k . '_client_id' ] = \PressbooksBiblioBoardOAuth\Admin\oauth_client_id_sanitize( $_REQUEST['pressbooks_oauth_options'][ $k . '_client_id' ] );
			} else {
				unset( $pressbooks_oauth_options[ $k . '_client_id' ] );
			}
			if ( isset( $_REQUEST['pressbooks_oauth_options'][ $k . '_client_secret' ] ) ) {
				$pressbooks_oauth_options[ $k . '_client_secret' ] = \PressbooksBiblioBoardOAuth\Admin\oauth_client_secret_sanitize( $_REQUEST['pressbooks_oauth_options'][ $k . '_client_secret' ] );
			} else {
				unset( $pressbooks_oauth_options[ $k . '_client_secret' ] );
			}
			if ( isset( $_REQUEST['pressbooks_oauth_options'][ $k . '_bypass' ] ) ) {
				$pressbooks_oauth_options[ $k . '_bypass' ] = \PressbooksBiblioBoardOAuth\Admin\oauth_bypass_sanitize( $_REQUEST['pressbooks_oauth_options'][ $k . '_bypass' ] );
			} else {
				unset( $pressbooks_oauth_options[ $k . '_bypass' ] );
			}
			if ( isset( $_REQUEST['pressbooks_oauth_options'][ $k . '_customize_login_button' ] ) ) {
				$pressbooks_oauth_options[ $k . '_customize_login_button' ] = \PressbooksBiblioBoardOAuth\Admin\oauth_customize_login_button_sanitize( $_REQUEST['pressbooks_oauth_options'][ $k . '_customize_login_button' ] );
			} else {
				unset( $pressbooks_oauth_options[ $k . '_customize_login_button' ] );
			}
		}
		update_site_option( 'pressbooks_oauth_options', $pressbooks_oauth_options );
		update_site_option( 'registration', $registration );
		?>
		<div id="message" class="updated notice is-dismissible"><p><strong><?php _e( 'Settings saved.', 'pressbooks-biblioboard-oauth' ); ?></strong></div>
	<?php } ?>
	<form method="POST" action="">
		<?php
		settings_fields( 'pressbooks_biblioboard_oauth' );
		do_settings_sections( 'pressbooks_biblioboard_oauth' );
		submit_button();
		?>
	</form>
</div>
