<?php

namespace PressbooksBiblioBoardOAuth;

function rewrite_rules_for_oauth() {
	global $wp;
	$wp->add_query_var( 'pb_oauth_provider' );
	$query = 'index.php?signup=true&pb_oauth_provider=$matches[1]';
	add_rewrite_rule( '^oauth/(.*)', $query, 'top' );
	if ( class_exists( '\Roots\Bedrock\URLFixer' ) ) {
		/** @see \Roots\Bedrock\URLFixer::fixNetworkSiteURL */
		add_rewrite_rule( '^wp/oauth/(.*)', $query, 'top' );
	}
	add_filter( 'template_include', __NAMESPACE__ . '\template_path_hack', 999 ); // Must come after \Roots\Sage\Wrapper\SageWrapping (to override)
	if ( ! get_option( 'pressbooks_flushed_oauth_url' ) ) {
		flush_rewrite_rules( false );
		update_option( 'pressbooks_flushed_oauth_url', true );
	}
}

/**
 * @param string $template
 *
 * @return string
 */
function template_path_hack( $template ) {
	if ( get_query_var( 'pb_oauth_provider' ) ) {
		return ABSPATH . 'wp-signup.php'; // Return signup page as template
	}
	return $template;
}

/**
 * Change wp_login_url() to include an action param we use to trigger: do_action( "login_form_{$action}" )
 *
 * Hooked into filter: 'login_url'
 *
 * @param string $login_url The login URL. Not HTML-encoded.
 *
 * @return string
 */
function login_url( $login_url ) {
	$login_url = add_query_arg( 'action', 'pb_oauth', $login_url );
	return $login_url;
}

/**
 * If there's only one provider and it's biblioboard, then redirect to their authorization url
 *
 * Hooked into action: "login_form_{$action}"
 *
 * @see wp-login.php
 *
 * @param string $redirect_to (optional)
 *
 * @return mixed
 */
function maybe_redirect_away_from_login_form( $redirect_to = null ) {
	$oauth = new OAuth();
	$active = $oauth->getActiveClients();
	if ( count( $active ) === 1 ) {
		foreach ( $active as $slug => $client ) {
			if ( $slug === 'biblioboard' ) {
				$url = $client->getAuthorizationUrl( $oauth->getAuthorizationUrlConfig( $slug ) );
				\Pressbooks\Redirect\location( $url );
			}
		}
	}
	if ( $redirect_to !== null ) {
		return $redirect_to;
	}
}

/**
 * @param array $options
 *
 * @return array
 */
function session_configuration( $options = [] ) {
	if ( strpos( $_SERVER['REQUEST_URI'], '/oauth/' ) !== false ) {
		if ( isset( $options['read_and_close'] ) ) {
			unset( $options['read_and_close'] );
		}
	}
	return $options;
}
