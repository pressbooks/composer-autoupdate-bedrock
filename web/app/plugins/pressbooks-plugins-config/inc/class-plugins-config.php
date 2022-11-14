<?php

namespace PressbooksConfig;

use function Pressbooks\Admin\NetworkManagers\is_restricted;
use Pressbooks\Admin\Network\NetworkSettings;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Class Plugins_Config
 *
 * @package PressbooksConfig
 */
class Plugins_Config {

	/**
	 * @var Plugins_Config
	 */
	protected static $instance;

	private $pluginsToHide = [];

	/**
	 * The role that is allowed to handle this plugin
	 *
	 * @var string
	 */
	private $minimumRole = 'manage_sites';

	/**
	 * Singleton factory function
	 *
	 * @return Plugins_Config
	 */
	public static function init(): Plugins_Config {
		if ( is_null( self::$instance ) ) {
			self::$instance = new static();
			self::$instance->hooks();
		}
		return self::$instance;
	}

	/**
	 * Hide this plugin from non admin users.
	 */
	public function hidePlugins() {
		global $wp_list_table;

		if ( is_restricted() || ! current_user_can( $this->minimumRole ) ) {
			$this->pluginsToHide[] = 'pressbooks-plugins-config/pressbooks-plugins-config.php';
			$this->pluginsToHide[] = 'redirection/redirection.php';
		}

		foreach ( $wp_list_table->items as $key => $val ) {
			if ( in_array( $key, $this->pluginsToHide, true ) ) {
				unset( $wp_list_table->items[ $key ] );
			}
		}
	}

	/**
	 * @return void
	 */
	public function hooks() : void {
		add_action( 'pre_current_active_plugins', [ $this, 'hidePlugins' ] );

		if ( is_restricted() ) {
			add_action(
				'admin_init',
				function () {
					if ( is_plugin_active( 'redirection/redirection.php' ) ) {
						remove_submenu_page( 'tools.php', 'redirection.php' );
						$hook_hash = spl_object_hash( \Redirection_Admin::init() );
						remove_action( 'admin_notices', "${hook_hash}update_nag" );
					}
				}
			);
		}

		// Hide redirect plugin if not super admin
		add_filter(
			'redirection_role', function() {
				return $this->minimumRole;
			}
		);

		// Use noreply@pressbooks.com as wp_mail_from value to avoid DMARC problems with transactional emails.
		add_filter(
			'wp_mail_from',
			function ( $email ) {
				return 'noreply@pressbooks.com';
			},
			20 // Higher priority will be executed last
		);

		if ( is_network_admin() ) {
			add_action( 'wp_network_dashboard_setup', [ $this, 'displayNetworkFeed' ] );
		}

		add_action( 'wp_dashboard_setup', [ $this, 'displayDashboardFeed' ] );

		add_filter( 'display_custom_feed', function( $url ) {
			$urls_to_skip = [
				'https://pressbooks.com/feed',
				'https://pressbooks.com/blog/feed',
				'https://pressbooks.community/c/pressbooks-product-updates/19.rss',
			];

			return ! in_array( rtrim( $url, '/' ), $urls_to_skip, true );
		} );

		add_action( 'admin_head', function() {
			$disable_cron_warning = env( 'DISABLE_WP_CRON', true );

			if ( ! $disable_cron_warning ) {
				return;
			}

			echo '<style>.koko-analytics-cron-warning { display: none; }</style>';
		} );

		add_action( 'add_site_option', [ $this, 'setDefaultThemeOption' ], 10, 0 );

		add_action( 'init', [ $this, 'kalturaAddOembedHandlers' ] );
	}

	/**
	 * Set default theme option defined in NetworkSettings.
	 *
	 * @return void
	 */
	public function setDefaultThemeOption() {
		update_site_option( NetworkSettings::DEFAULT_THEME_OPTION, NetworkSettings::getDefaultTheme() );
	}

	/**
	 * Display the Pressbooks feed on the network dashboard
	 */
	public function displayNetworkFeed(): void {
		$this->displayFeeds( 'dashboard-network' );
	}

	/**
	 * Display the Pressbooks feed on the dashboard
	 */
	public function displayDashboardFeed(): void {
		$this->displayFeeds( 'dashboard' );
	}

	/**
	 * Display the Pressbooks feeds on the provided screen
	 *
	 * @param string $screen
	 * @param string|null $context
	 * @param string|null $priority
	 */
	public function displayFeeds( string $screen, string $context = null, string $priority = null ) {
		add_meta_box(
			'pb_dashboard_widget_news',
			__( 'Pressbooks announcements', 'pressbooks-plugins-config' ),
			[ $this, 'displayPressbooksNewsFeed' ],
			$screen,
			$context ?? 'side',
			$priority ?? 'low'
		);

		add_meta_box(
			'pb_dashboard_widget_product',
			__( 'What\'s new with Pressbooks', 'pressbooks-plugins-config' ),
			[ $this, 'displayPressbooksProductFeed' ],
			$screen,
			$context ?? 'side',
			$priority ?? 'low'
		);
	}

	/**
	 * Display the Pressbooks News feed
	 */
	public function displayPressbooksNewsFeed(): void {
		$rss = get_site_transient( 'pb_rss_news_widget' );

		if ( ! $rss ) {
			ob_start();

			wp_widget_rss_output(
				[
					'url' => 'https://pressbooks.com/blog/feed',
					'items' => 3,
					'show_summary' => 1,
					'show_author' => 0,
					'show_date' => 0,
				]
			);

			$rss = ob_get_clean();

			set_site_transient( 'pb_rss_news_widget', $rss, DAY_IN_SECONDS );
		}

		echo $rss;
	}

	/**
	 * Display Pressbooks' Recent product update feed
	 */
	public function displayPressbooksProductFeed(): void {
		$rss = get_site_transient( 'pb_rss_product_update_widget' );

		if ( ! $rss ) {
			ob_start();

			wp_widget_rss_output(
				[
					'url' => 'https://pressbooks.community/c/pressbooks-product-updates/19.rss',
					'items' => 3,
					'show_summary' => 1,
					'show_author' => 0,
					'show_date' => 0,
				]
			);

			$rss = ob_get_clean();

			set_site_transient( 'pb_rss_product_update_widget', $rss, DAY_IN_SECONDS );
		}

		echo $rss;
	}

	// To add oEmbed support for additional Kaltura MediaSpace instances, register them using `wp_ombed_add_provider` and
	// the pattern described in Kaltura's documentation: https://knowledge.kaltura.com/help/mediaspace-oembed-integration
	public function kalturaAddOembedHandlers() {
		wp_oembed_add_provider( 'https://mediaspace.wisc.edu/*', 'https://mediaspace.wisc.edu/oembed', false );
		wp_oembed_add_provider( 'https://media.kpu.ca/*', 'https://media.kpu.ca/oembed/' );
		wp_oembed_add_provider( 'https://iu.mediaspace.kaltura.com/*', 'https://iu.mediaspace.kaltura.com/oembed', false );
		wp_oembed_add_provider( 'https://learning.kaltura.com/*', 'https://learning.kaltura.com/oembed', false );
		wp_oembed_add_provider( 'https://video.uark.edu/*', 'https://video.uark.edu/oembed', false );
		wp_oembed_add_provider( 'https://millersville.mediaspace.kaltura.com/*', 'https://millersville.mediaspace.kaltura.com/oembed', false );
	}
}
