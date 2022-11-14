<?php

namespace PressbooksNetworkAnalytics\Admin;

use function PressbooksNetworkAnalytics\blade;
use function Pressbooks\Admin\NetworkManagers\_restricted_users;
use PressbooksMix\Assets;
use Pressbooks\Admin\Network\SharingAndPrivacyOptions;

class Options extends Admin {

	const LTI_1P3_PLUGIN_LOADER = 'pressbooks-lti-provider-1p3/pressbooks-lti-provider.php';

	/**
	 * The slug name for the parent menu (or the file name of a standard WordPress admin page).
	 *
	 * @var string
	 */
	public $parentPage = 'settings.php';

	/**
	 * @var Options
	 */
	private static $instance = null;

	/**
	 * @var array
	 */
	private $formValidationErrors = [];

	/**
	 * @return Options
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param Options $obj
	 */
	static public function hooks( Options $obj ) {
		add_action( 'admin_enqueue_scripts', [ $obj, 'adminEnqueueScripts' ] );
		add_action( 'network_admin_menu', [ $obj, 'addMenu' ] );
		add_filter( 'newuser_notify_siteadmin', [ $obj, 'newUserNotification' ], 101 );
		add_filter( 'newblog_notify_siteadmin', [ $obj, 'newBlogNotification' ], 101 );
		add_filter( 'pb_theme_options_pdf_defaults', [ $obj, 'pdfDefaults' ] );
	}

	/**
	 *
	 */
	public function __construct() {
	}

	/**
	 * @param string $hook
	 */
	public function adminEnqueueScripts( $hook ) {
		$assets = new Assets( 'pressbooks-network-analytics', 'plugin' );
		$lti_1p3_enabled = $this->lti1p3Enabled();
		$lti_usage_stats_action = $lti_1p3_enabled ? \PressbooksLtiProvider1p3\Admin::ACTION_GET_ASYNC_CONNECTIONS : 'none';

		// Settings
		if ( $hook === get_plugin_page_hookname( 'pb_network_analytics_options', $this->parentPage ) ) {
			$this->registerJqueryTabsAssets();
			wp_enqueue_style( 'pb-network-analytics-settings', $assets->getPath( 'styles/settings.css' ), [ 'jquery-ui-tabs' ] );
			wp_enqueue_script( 'pb-network-analytics-settings', $assets->getPath( 'scripts/settings.js' ), [ 'jquery-ui-tabs', 'wp-i18n' ] );
			wp_localize_script(
				'pb-network-analytics-settings', 'pb_network_analytics_options', [
					'lti_1p3_enabled' => $lti_1p3_enabled,
					'lti_usage_stats_action' => $lti_usage_stats_action,
					'lti_usage_nonce' => wp_create_nonce( $lti_usage_stats_action ),
					'ajax_url' => admin_url( 'admin-ajax.php' ),
				]
			);
		}
	}

	/**
	 * Hooked into network_admin_menu
	 */
	public function addMenu() {
		// Settings
		add_submenu_page(
			$this->parentPage,
			__( 'Network Options', 'pressbooks-network-analytics' ),
			__( 'Network Options', 'pressbooks-network-analytics' ),
			'manage_network',
			'pb_network_analytics_options',
			[ $this, 'printMenuSettings' ]
		);
	}

	/**
	 * Settings
	 */
	public function printMenuSettings(): void {
		if ( $this->saveSettings() ) {
			echo '<div id="message" role="status" class="updated notice is-dismissible"><p>' . __( 'Settings saved.' ) . '</p></div>';
		}

		$options = get_site_option( SharingAndPrivacyOptions::getSlug(), [] );
		$feed_options = get_site_option( 'pressbooks_dashboard_feed', [] );

		echo blade()->render(
			'PressbooksNetworkAnalytics::settings', [
				'wp_nonce_field' => wp_nonce_field( 'save', 'pb-network-analytics-settings-nonce', true, false ),
				'current_network_managers' => $this->getNetworkManagers(),
				'options' => $this->getSettings(),
				'form_validation_errors' => $this->formValidationErrors,
				'lti_1p3_enabled' => $this->lti1p3Enabled(),
				'feed_options' => $feed_options,
				'network_directory_excluded' => $options[ SharingAndPrivacyOptions::NETWORK_DIRECTORY_EXCLUDED ] ?? 0,
			]
		);
	}

	/**
	 * @return array
	 * @see \Pressbooks\Admin\Network_Managers_List_Table::prepare_items
	 */
	public function getNetworkManagers() {
		$restricted_users_ids = _restricted_users();
		$template_data = [];
		foreach ( $restricted_users_ids as $user_id ) {
			$user = get_user_by( 'ID', $user_id );
			if ( $user ) {
				$user_name_display = sprintf( '%s %s', $user->user_firstname, $user->user_lastname );
				$template_data[ ! empty( trim( $user_name_display ) ) ? $user_name_display : $user->user_login ] = $user->user_email;
			}
		}
		return $template_data;
	}

	/**
	 * Network Settings form submission
	 *
	 * @return bool
	 */
	public function saveSettings() {
		if ( ! isset( $_POST['pb-network-analytics-settings-nonce'] ) || ! wp_verify_nonce( $_POST['pb-network-analytics-settings-nonce'], 'save' ) ) {
			return false;
		}

		// --------------------------------------------------------------------
		// Network Defaults
		// --------------------------------------------------------------------

		// wp-admin/network/settings.php
		update_site_option( 'welcome_email', wp_unslash( $_POST['welcome_email'] ) );

		// wp-admin/network/settings.php
		update_site_option( 'welcome_user_email', wp_unslash( $_POST['welcome_user_email'] ) );

		// wp-admin/network/settings.php
		update_site_option( 'upload_space_check_disabled', isset( $_POST['upload_space_check_disabled'] ) ? $_POST['upload_space_check_disabled'] : 1 );

		// wp-admin/network/settings.php
		update_site_option( 'blog_upload_space', wp_unslash( $_POST['blog_upload_space'] ) );

		// wp-admin/network/settings.php
		$max_post_size_bytes = \PressbooksNetworkAnalytics\return_kilobytes( ini_get( 'post_max_size' ) );
		if ( $_POST['fileupload_maxk'] <= $max_post_size_bytes ) {
			update_site_option( 'fileupload_maxk', wp_unslash( $_POST['fileupload_maxk'] ) );
		} else {
			/* translators: %s KB */
			$this->formValidationErrors[] = sprintf( __( 'The default maximum file upload size for this network is %dKB', 'pressbooks-network-analytics' ), $max_post_size_bytes );
		}

		// pressbooks-whitelabel/inc/admin/network/settings/namespace.php,
		update_site_option( 'pressbooks_default_book_theme', wp_unslash( $_POST['pressbooks_default_book_theme'] ) );

		// wp-admin/network/settings.php
		require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );
		if ( ! empty( $_POST['WPLANG'] ) && current_user_can( 'install_languages' ) && wp_can_install_language_pack() ) {
			$language = wp_download_language_pack( $_POST['WPLANG'] );
			if ( $language ) {
				$_POST['WPLANG'] = $language;
			}
		}
		update_site_option( 'WPLANG', wp_unslash( $_POST['WPLANG'] ) );

		// pressbooks/inc/modules/themeoptions/class-pdfoptions.php
		// Hooked into filter: pb_theme_options_pdf_defaults
		update_site_option( 'pb_pdf_page_width_default', wp_unslash( $_POST['pb_pdf_page_width_default'] ) );
		update_site_option( 'pb_pdf_page_height_default', wp_unslash( $_POST['pb_pdf_page_height_default'] ) );

		// pressbooks/inc/admin/network/class-sharingandprivacyoptions.php
		$options = get_site_option( 'pressbooks_sharingandprivacy_options', [] );
		$options['iframe_whitelist'] = sanitize_textarea_field( $_POST['pressbooks_sharingandprivacy_options']['iframe_whitelist'] );

		$old_exclude_option = false;

		if ( isset( $options[ SharingAndPrivacyOptions::NETWORK_DIRECTORY_EXCLUDED ] ) ) {
			$old_exclude_option = (bool) $options[ SharingAndPrivacyOptions::NETWORK_DIRECTORY_EXCLUDED ];
		}

		if ( isset( $_POST['pressbooks_sharingandprivacy_options'][ SharingAndPrivacyOptions::NETWORK_DIRECTORY_EXCLUDED ] ) ) {
			$exclude_option = ( 'on' === $_POST['pressbooks_sharingandprivacy_options'][ SharingAndPrivacyOptions::NETWORK_DIRECTORY_EXCLUDED ] );
		} else {
			$exclude_option = false;
		}

		$options[ SharingAndPrivacyOptions::NETWORK_DIRECTORY_EXCLUDED ] = $exclude_option;

		update_site_option( 'pressbooks_sharingandprivacy_options', $options );

		// Check if there is a change in setting
		if ( $old_exclude_option !== $exclude_option ) {
			SharingAndPrivacyOptions::networkExcludeOption( $options[ SharingAndPrivacyOptions::NETWORK_DIRECTORY_EXCLUDED ] );
		}

		// --------------------------------------------------------------------
		// Dashboard Feed
		// --------------------------------------------------------------------
		$feed_options = array_merge( [ 'display_feed' => true ], $_POST['pressbooks_dashboard_feed'] );

		$feed_options['title'] = wp_kses_post( $feed_options['title'] ?? '' );
		$feed_options['url'] = esc_url( $feed_options['url'] ?? '' );

		if ( ! $feed_options['title'] || ! $feed_options['url'] ) {
			$feed_options['display_feed'] = false;
		}

		delete_site_transient( 'pb_rss_widget' ); // Clean up old cache
		update_site_option( 'pressbooks_dashboard_feed', $feed_options );

		// --------------------------------------------------------------------
		// Book & User Registration
		// --------------------------------------------------------------------

		// wp-admin/network/settings.php
		$registration_1 = isset( $_POST['registration_1'] ) ? $_POST['registration_1'] : 0;
		$registration_2 = isset( $_POST['registration_2'] ) ? $_POST['registration_2'] : 0;
		if ( $registration_1 && ! $registration_2 ) {
			// If users may create & clone new books is checked, same thing happens as when "logged in users may register new sites" is selected now
			$reg = 'blog';
		} elseif ( ! $registration_1 && $registration_2 ) {
			// If checked, and users may create & clone new books is not checked, outcome should be same as when "user accounts may be registered" is selected now
			$reg = 'user';
		} elseif ( $registration_1 && $registration_2 ) {
			// If checked and ‘users may create …’ is checked, outcome should be same as when "Both sites and user accounts can be registered" is selected now.
			$reg = 'all';
		} else {
			// None
			$reg = 'none';
		}
		update_site_option( 'registration', $reg );

		// wp-admin/network/settings.php
		update_site_option( 'add_new_users', isset( $_POST['add_new_users'] ) ? $_POST['add_new_users'] : 0 );

		// wp-admin/network/settings.php
		update_site_option( 'limited_email_domains', wp_unslash( $_POST['limited_email_domains'] ) );

		// wp-admin/network/settings.php
		update_site_option( 'banned_email_domains', wp_unslash( $_POST['banned_email_domains'] ) );

		// pressbooks-whitelabel/inc/admin/network/settings/namespace.php
		update_site_option( 'pressbooks_require_tos_optin', isset( $_POST['pressbooks_require_tos_optin'] ) ? $_POST['pressbooks_require_tos_optin'] : 0 );

		// wp-admin/network/settings.php
		update_site_option( 'pressbooks_tos_page_id', wp_unslash( $_POST['pressbooks_tos_page_id'] ?? 0 ) );

		// wp-admin/network/settings.php
		// The original feature is a checkbox. WordPress' notification code works with 1 email.
		// Ours hooks into newuser_notify_siteadmin & newblog_notify_siteadmin filters
		update_site_option( 'registrationnotification', isset( $_POST['registrationnotification'] ) ? $_POST['registrationnotification'] : 'no' );
		update_site_option( 'pb_registrationnotification', wp_unslash( $_POST['pb_registrationnotification'] ) );

		// --------------------------------------------------------------------
		// Third-Party Tools
		// --------------------------------------------------------------------

		// pressbooks/inc/admin/analytics/namespace.php
		update_site_option( 'ga_mu_uaid', wp_unslash( $_POST['ga_mu_uaid'] ) );

		return empty( $this->formValidationErrors ) ? true : false;
	}

	/**
	 * Network Settings form values
	 *
	 * @return array
	 */
	public function getSettings() {
		// As a result of copying code from WordPress (to make comparing changes easier),
		// most settings are inline inside the blade template using `get_site_option`
		// For settings that aren't, set them up here.
		$options = [];

		$reg = get_site_option( 'registration' );
		if ( $reg === 'blog' ) {
			// If users may create & clone new books is checked, same thing happens as when "logged in users may register new sites" is selected now
			$options['registration_1'] = 1;
			$options['registration_2'] = 0;
		} elseif ( $reg === 'user' ) {
			// If checked, and users may create & clone new books is not checked, outcome should be same as when "user accounts may be registered" is selected now
			$options['registration_1'] = 0;
			$options['registration_2'] = 1;
		} elseif ( $reg === 'all' ) {
			// If checked and ‘users may create …’ is checked, outcome should be same as when "Both sites and user accounts can be registered" is selected now.
			$options['registration_1'] = 1;
			$options['registration_2'] = 1;
		} else {
			// None
			$options['registration_1'] = 0;
			$options['registration_2'] = 0;
		}

		return $options;
	}

	/**
	 * Hooked into newuser_notify_siteadmin
	 *
	 * @param string $msg
	 *
	 * @return string
	 */
	public function newUserNotification( $msg ) {
		$unchanged_msg = $msg;

		$emails = get_site_option( 'pb_registrationnotification' );
		if ( $emails && ! is_array( $emails ) ) {
			$emails = explode( "\n", $emails );
		}
		if ( $emails && is_array( $emails ) ) {
			$old_options_site_url = esc_url( network_admin_url( 'settings.php' ) );
			$new_options_site_url = esc_url( network_admin_url( 'admin.php?page=pb_network_analytics_options' ) );
			$msg = str_replace( $old_options_site_url, $new_options_site_url, $msg );
			foreach ( $emails as $email ) {
				wp_mail( $email, __( 'New User Registration' ), $msg );
			}
		}

		return $unchanged_msg;
	}

	/**
	 * Hooked into newblog_notify_siteadmin
	 *
	 * @param string $msg
	 *
	 * @return string
	 */
	public function newBlogNotification( $msg ) {
		$unchanged_msg = $msg;

		$emails = get_site_option( 'pb_registrationnotification' );
		if ( $emails && ! is_array( $emails ) ) {
			$emails = explode( "\n", $emails );
		}
		if ( $emails && is_array( $emails ) ) {
			$old_options_site_url = esc_url( network_admin_url( 'settings.php' ) );
			$new_options_site_url = esc_url( network_admin_url( 'admin.php?page=pb_network_analytics_options' ) );
			$msg = str_replace( $old_options_site_url, $new_options_site_url, $msg );
			$msg = str_replace( __( 'New Site:', 'pressbooks-network-analytics' ), __( 'New Book:', 'pressbooks-network-analytics' ), $msg );
			foreach ( $emails as $email ) {
				wp_mail( $email, __( 'New Book Registration' ), $msg );
			}
		}

		return $unchanged_msg;
	}

	/**
	 * Hooked into: pb_theme_options_pdf_defaults
	 *
	 * @param array $defaults
	 *
	 * @return array
	 */
	public function pdfDefaults( $defaults ) {
		$width = get_site_option( 'pb_pdf_page_width_default' );
		$height = get_site_option( 'pb_pdf_page_height_default' );
		if ( ! empty( $width ) && ! empty( $height ) ) {
			$defaults['pdf_page_width'] = $width;
			$defaults['pdf_page_height'] = $height;
		}
		return $defaults;
	}

	private function lti1p3Enabled() {
		return is_plugin_active_for_network( self::LTI_1P3_PLUGIN_LOADER );
	}
}
