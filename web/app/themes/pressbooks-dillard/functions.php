<?php
/**
 * @author  Pressbooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */

use Pressbooks\Container;
use Pressbooks\Options;

 /**
 * Add global theme option for title decoration.
 *
 * @since 2.0.0
 *
 * @param string $_page The settings identifier, e.g. pressbooks_theme_options_global
 * @param string $_section The settings section identifier, e.g. web_options_section
 *
 * @return null
 */
function dillard_add_title_decoration_setting( $_page, $_section ) {
	add_settings_field(
		'dillard_settings_section',
		sprintf( '<h3>%s</h3>', __( 'Dillard Settings', 'pressbooks-dillard' ) ),
		'dillard_render_section_header',
		$_page,
		$_section,
		[
			__( 'Customize settings specific to the Dillard theme below.', 'pressbooks-dillard' ),
		]
	);

	add_settings_field(
		'enable_title_decoration',
		__( 'Enable Title Decoration', 'pressbooks-dillard' ),
		'dillard_render_title_decoration_setting_field',
		$_page,
		$_section,
		[
			__( 'Show decoration on title page and section titles', 'pressbooks-dillard' ),
		]
	);
}

function dillard_render_section_header( $args ) {
	printf( $args[0] );
}

/**
 * Render the title decoration setting field.
 *
 * @since 2.0.0
 *
 * @param array $args The arguments for the field.
 *
 * @return null
 */
function dillard_render_title_decoration_setting_field( $args ) {
	$options = get_option( 'pressbooks_theme_options_global' );
	Options::renderCheckbox(
		[
			'id' => 'enable_title_decoration',
			'name' => 'pressbooks_theme_options_global',
			'option' => 'enable_title_decoration',
			'value' => ( isset( $options['enable_title_decoration'] ) ) ? $options['enable_title_decoration'] : '',
			'label' => $args[0],
		]
	);
}

/**
 * Add title decoration setting to sanitization hook.
 *
 * @since 2.0.0
 *
 * @param array $settings
 */
function dillard_add_title_decoration_to_settings( $settings ) {
	$settings[] = 'enable_title_decoration';
	return $settings;
}

function dillard_scss_overrides( $scss ) {
	$styles = Container::get( 'Styles' );
	$options = get_option( 'pressbooks_theme_options_global' );
	$enable_title_decoration = $options['enable_title_decoration'] ?? false;
	if ( $enable_title_decoration ) {
		$styles->getSass()->setVariables( [
			'chapter-title-decoration-display' => 'block',
			'title-title-decoration-display' => 'block',
		] );
	}

	return $scss;
}

add_action( 'pb_theme_options_global_add_settings_fields', 'dillard_add_title_decoration_setting', 10, 2 );
add_filter( 'pb_theme_options_global_booleans', 'dillard_add_title_decoration_to_settings' );
add_filter( 'pb_epub_css_override', 'dillard_scss_overrides' );
add_filter( 'pb_pdf_css_override', 'dillard_scss_overrides' );
add_filter( 'pb_web_css_override', 'dillard_scss_overrides' );
