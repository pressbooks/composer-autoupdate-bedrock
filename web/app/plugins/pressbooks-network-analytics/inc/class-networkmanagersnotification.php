<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace PressbooksNetworkAnalytics;

use PressbooksNetworkAnalytics\Airtable\AirtableClient;
use PressbooksNetworkAnalytics\Airtable\AirtableSynchronizer;

class NetworkManagersNotification {
	/**
	 * @var NetworkManagersNotification
	 */
	protected static $instance = null;

	/**
	 * @var AirtableClient
	 */
	private $airtable_client;

	/**
	 * @var AirtableSynchronizer
	 */
	private $airtable_synchronizer;

	public function __construct( AirtableClient $airtable_client = null ) {
		$this->airtable_client = is_null( $airtable_client ) ? new AirtableClient() : $airtable_client;
	}

	/**
	 * @return NetworkManagersNotification
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param NetworkManagersNotification $obj
	 */
	static public function hooks( NetworkManagersNotification $obj ) {
		add_action( 'update_site_option', [ $obj, 'updateNetworkManagers' ], 10, 2 );
		add_action( 'delete_site_option', [ $obj, 'updateNetworkManagers' ], 10, 2 );
		add_action( 'add_site_option', [ $obj, 'updateNetworkManagers' ], 10, 2 );
	}

	public function getAirtableSynchronizer() {
		return $this->airtable_synchronizer;
	}

	/**
	 * Sync to Airtable network manager data when user become a network manager
	 *
	 * @param string $option
	 * @param array|mixed $values
	 *
	 * @return array|mixed
	 */
	public function updateNetworkManagers( string $option, $values = null ) {
		if (
			$option !== 'pressbooks_network_managers' ||
			is_null( env( 'AIRTABLE_API_URL' ) ) ||
			is_null( env( 'AIRTABLE_BASE_ID' ) ) ||
			is_null( env( 'AIRTABLE_API_KEY' ) )
		) {
			return false;
		}
		$this->airtable_synchronizer = new AirtableSynchronizer( $this->airtable_client );
		return $this->airtable_synchronizer->synchronize();
	}
}
