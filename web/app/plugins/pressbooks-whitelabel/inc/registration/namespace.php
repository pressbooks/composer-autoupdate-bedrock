<?php

namespace PressbooksWhitelabel\Registration;

/**
 * Add TOS opt-in
 *
 * @param array $errors Errors
 */
function add_tos_optin( $errors ) {
	if ( $errors && method_exists( $errors, 'get_error_message' ) ) {
		$error = $errors->get_error_message( 'tos_opt_in' );
	} else {
		$error = false;
	}

	// Enable TOS opt-in if necessary configuration is in place.
	if ( get_site_option( 'pressbooks_require_tos_optin' ) ) {
		$tos_id = get_site_option( 'pressbooks_tos_page_id' );
		if ( $tos_id ) {
			if ( get_post( $tos_id ) ) {
				printf(
					'<p><label for="tos_opt_in"><input type="checkbox" id="tos_opt_in" name="tos_opt_in" required /> %s</label></p>',
					sprintf(
						/* Translators: %s: link to terms of service */
						__( 'I agree to the %s', 'pressbooks-whitelabel' ),
						sprintf(
							'<a href="%1$s" target="_blank">%2$s</a>',
							get_permalink( $tos_id ),
							__( 'Terms of Service', 'pressbooks-whitelabel' ) . sprintf( ' <span class="screen-reader-text">%s</span>', __( '(opens in a new window)', 'pressbooks-whitelabel' ) )
						)
					)
				);
				if ( $error ) {
					printf( '<p class="error">%s</p>', $error );
				}
			}
		}
	}
}

/**
 * Validate user submitted passwords
 *
 * @param array $content Content
 *
 * @return array
 */
function validate_tos_optin( $content ) {
	if ( isset( $_POST['_signup_form'] ) && ! wp_verify_nonce( $_POST['_signup_form'], 'signup_form_' . $_POST['signup_form_id'] ) ) {
		wp_die( __( 'Please try again.', 'pressbooks-whitelabel' ) );
	}

	if ( get_site_option( 'pressbooks_require_tos_optin' ) ) {
		$tos_id = get_site_option( 'pressbooks_tos_page_id' );
		if ( $tos_id ) {
			if ( get_post( $tos_id ) ) {
				$tos_opt_in = $_POST['tos_opt_in'] ?? false;

				if ( isset( $_POST['stage'] ) && 'validate-user-signup' === $_POST['stage'] ) {

					// User didn't accept TOS.
					if ( ! $tos_opt_in ) {
						$content['errors']->add( 'tos_opt_in', __( 'You must agree to the terms of service.', 'pressbooks-whitelabel' ) );
						return $content;
					}
				}
			}
		}
	}

	return $content;
}
