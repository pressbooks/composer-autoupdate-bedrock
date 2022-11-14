<?php

namespace PressbooksWhitelabel\Admin\Network\Settings;

use PressbooksMix\Assets;

/**
 * Add our form to Network Settings menu
 */
function add_network_menu() {
	$whitelabel_page = add_submenu_page(
		'settings.php',
		__( 'Whitelabel Settings', 'pressbooks-whitelabel' ),
		__( 'Whitelabel Settings', 'pressbooks-whitelabel' ),
		'manage_network_options',
		'pb_whitelabel_settings',
		__NAMESPACE__ . '\display'
	);

	add_action(
		'admin_enqueue_scripts', function ( $hook ) use ( $whitelabel_page ) {
			$assets = new Assets( 'pressbooks-whitelabel', 'plugin' );
			$assets->setSrcDirectory( 'assets' )->setDistDirectory( 'dist' );
			if ( $hook === $whitelabel_page ) {
				wp_enqueue_script( 'pressbooks/whitelabel', $assets->getPath( 'scripts/whitelabel.js' ), [ 'jquery' ], null );
			}
		}
	);
}

/**
 * Callback for add_submenu_page()
 */
function display() {

	// Valid options, set in whitelabel_settings_init().
	$valid_options = [
		'pressbooks_hide_pdf_watermarks',
		'pressbooks_hide_ebook_watermarks',
		'pressbooks_require_tos_optin',
		'pressbooks_tos_page_id',
	];

	?>
	<div class="wrap">
		<h1><?php _e( 'Whitelabel Settings', 'pressbooks-whitelabel' ); ?></h1>
		<?php
		$nonce = ( isset( $_REQUEST['_wpnonce'] ) ) ? $_REQUEST['_wpnonce'] : '';
		if ( ! empty( $_POST ) ) {
			if ( ! wp_verify_nonce( $nonce, 'pb_whitelabel_settings-options' ) ) {
				wp_die( 'Security check' );
			} else {
				foreach ( $valid_options as $option ) {
					if ( isset( $_REQUEST[ $option ] ) && ! empty( $_REQUEST[ $option ] ) ) {
						update_site_option( $option, $_REQUEST[ $option ] );
					} else {
						delete_site_option( $option );
					}
				}
				?>
				<div id="message" class="updated notice is-dismissible"><p>
					<strong><?php _e( 'Settings saved.', 'pressbooks-whitelabel' ); ?></strong>
				</div>
				<?php
			}
		}
		?>
		<form method="POST" action="">
			<?php
			settings_fields( 'pb_whitelabel_settings' ); // Nonce the settings.
			do_settings_sections( 'pb_whitelabel_settings' ); // Echo the settings.
			submit_button(); // Submit button.
			?>
		</form>
	</div>
	<?php
}

/**
 *  Initialize sections, fields, and sanity callbacks for Whitelabel Settings
 */
function whitelabel_settings_init() {

	$page_slug = 'pb_whitelabel_settings';
	$secret_sauce_slug = 'secret_sauce';

	add_settings_section(
		$secret_sauce_slug,
		null,
		function () {
			echo '<p>' . __( 'Modify the following options to customize the branding of your Pressbooks network.', 'pressbooks-whitelabel' ) . '</p>';
		},
		$page_slug
	);

	// Remove Pressbooks credits from PDF exports.
	// The site option pressbooks_hide_pdf_watermarks corresponds to $GLOBALS['PB_SECRET_SAUCE']['TURN_OFF_FREEBIE_NOTICES_PDF'].
	$pdf_option = 'pressbooks_hide_pdf_watermarks';
	$pdf_option_label = __( 'Remove Pressbooks credits from PDF exports', 'pressbooks-whitelabel' );
	add_settings_field(
		$pdf_option,
		$pdf_option_label,
		function ( $args = [] ) use ( $pdf_option, $pdf_option_label ) {
			echo "<input type='checkbox' aria-label='$pdf_option_label' id='$pdf_option' name='{$pdf_option}' value='1' " . checked( get_site_option( $pdf_option ), '1', false ) . '/>';
		},
		$page_slug,
		$secret_sauce_slug,
		[] // If we had $args they'd go here.
	);
	register_setting(
		$page_slug,
		$pdf_option,
		function ( $input ) {
			return absint( $input );
		}
	);

	// Remove Pressbooks credits from Ebook exports.
	// The site option pressbooks_hide_ebook_watermarks corresponds to $GLOBALS['PB_SECRET_SAUCE']['TURN_OFF_FREEBIE_NOTICES_EPUB'].
	$ebook_option = 'pressbooks_hide_ebook_watermarks';
	$ebook_option_label = __( 'Remove Pressbooks credits from Ebook exports', 'pressbooks-whitelabel' );
	add_settings_field(
		$ebook_option,
		$ebook_option_label,
		function ( $args = [] ) use ( $ebook_option, $ebook_option_label ) {
			echo "<input type='checkbox' aria-label='$ebook_option_label' id='$ebook_option' name='{$ebook_option}' value='1' " . checked( get_site_option( $ebook_option ), '1', false ) . '/>';
		},
		$page_slug,
		$secret_sauce_slug,
		[] // If we had $args they'd go here.
	);
	register_setting(
		$page_slug,
		$ebook_option,
		function ( $input ) {
			return absint( $input );
		}
	);

	// Require users to opt-in to terms of service at registration.
	$tos_option = 'pressbooks_require_tos_optin';
	$tos_option_label = __( 'Require users to opt-in to terms of service at registration', 'pressbooks-whitelabel' );
	add_settings_field(
		$tos_option,
		$tos_option_label,
		function ( $args = [] ) use ( $tos_option, $tos_option_label ) {
			echo "<input type='checkbox' aria-label='$tos_option_label' id='$tos_option' name='{$tos_option}' value='1' " . checked( get_site_option( $tos_option ), '1', false ) . '/>';
		},
		$page_slug,
		$secret_sauce_slug,
		[] // If we had $args they'd go here.
	);
	register_setting(
		$page_slug,
		$tos_option,
		function ( $input ) {
			return absint( $input );
		}
	);

	// Terms of service page.
	$tos_page_id_option = 'pressbooks_tos_page_id';
	add_settings_field(
		$tos_page_id_option,
		__( 'Terms of service page', 'pressbooks-whitelabel' ),
		function ( $args ) use ( $tos_page_id_option ) {
			$options = sprintf(
				'<option value="">%s</option>',
				__( '--', 'pressbooks-whitelabel' )
			);
			$pages = get_pages();
			foreach ( $pages as $page ) {
				$options .= sprintf(
					'<option value="%1$d"%2$s>%3$s</option>',
					$page->ID,
					selected( get_site_option( $tos_page_id_option ), $page->ID, false ),
					$page->post_title
				);
			}
			printf(
				'<select id="%1$s" name="%1$s"%2$s>%3$s</select><p class="description">%4$s</p>',
				$tos_page_id_option,
				( get_site_option( 'pressbooks_require_tos_optin' ) ) ? '' : ' disabled',
				$options,
				$args[0]
			);
		},
		$page_slug,
		$secret_sauce_slug,
		[
			sprintf(
				/* Translators: %s: link to create a new page */
				__( 'Select the page that contains your Terms of Service, or %s.', 'pressbooks-whitelabel' ),
				sprintf(
					'<a href="%1$s">%2$s</a>',
					admin_url( '/post-new.php?post_type=page' ),
					__( 'create a new page' )
				)
			),
		]
	);
	register_setting(
		$page_slug,
		$tos_page_id_option,
		function ( $input ) {
			return absint( $input );
		}
	);
}

