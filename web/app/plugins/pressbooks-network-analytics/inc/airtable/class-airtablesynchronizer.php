<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace PressbooksNetworkAnalytics\Airtable;

use PressbooksNetworkAnalytics\Admin\Options;
use PressbooksNetworkAnalytics\Airtable\Records\ContactRecord;
use PressbooksNetworkAnalytics\Airtable\Records\NetworkRecord;

class AirtableSynchronizer {

	/**
	 * @var array
	 */
	private $wp_network_managers;

	/**
	 * @var array
	 */
	private $airtable_network_managers;

	/**
	 * @var NetworkRecord
	 */
	private $airtable_network_record;

	/**
	 * @var array
	 */
	private $unlinked_network_managers;

	/**
	 * @var array
	 */
	private $linked_network_managers;

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @var AirtableClient
	 */
	private $airtable_client;

	public function __construct( AirtableClient $airtable_client = null ) {
		$this->unlinked_network_managers = [];
		$this->linked_network_managers = [];
		$this->airtable_network_managers = [];
		$this->wp_network_managers = [];
		$this->airtable_client = is_null( $airtable_client ) ? new AirtableClient() : $airtable_client;
		$this->options = new Options();
		$this->airtable_network_record = null;
		if ( $this->setAirtableNetworkRecord() ) {
			$this->wp_network_managers = $this->options->getNetworkManagers();
			$this->setNetworkManagersFromAirtable();
		}
	}

	public function setAirtableClient( AirtableClient $airtable_client ) {
		$this->airtable_client = $airtable_client;
	}

	private function setAirtableNetworkRecord() {
		$wp_network_name = get_site_option( 'site_name' );
		$filter_network_name = [ NetworkRecord::NAME_FIELD => $wp_network_name ];
		$airtable_network_records = NetworkRecord::getList(
			NetworkRecord::AIRTABLE_TABLE_NAME,
			$filter_network_name,
			$this->airtable_client,
			NetworkRecord::getFields()
		);
		if ( $airtable_network_records && count( $airtable_network_records ) > 0 ) {
			$this->airtable_network_record = $airtable_network_records[0];
			return true;
		}
		return false;
	}

	private function setNetworkManagersFromAirtable() {
		$filter_network_name_field = [ ContactRecord::NETWORKS_FIELD => $this->airtable_network_record->getName() ];
		$contact_records = ContactRecord::getList(
			ContactRecord::AIRTABLE_TABLE_NAME,
			$filter_network_name_field,
			$this->airtable_client,
			ContactRecord::getFields()
		);
		if ( $contact_records ) {
			foreach ( $contact_records as $contact_record ) {
				if ( $contact_record->getNetworkManager() ) {
					$this->airtable_network_managers[] = $contact_record;
				}
			}
			return ! empty( $this->airtable_network_managers );
		}
		return false;
	}

	public function synchronize() {
		if ( ! is_null( $this->airtable_network_record ) ) {
			$this->unlinkMissingNetworkManagersFromAirtable();
			$this->linkWPNetworkManagersToAirtable();
			return true;
		}
		return false;
	}

	private function unlinkMissingNetworkManagersFromAirtable() {
		$wp_network_managers_emails = array_values( $this->wp_network_managers );
		$this->unlinked_network_managers = [];
		foreach ( $this->airtable_network_managers as $contact_record ) {
			if ( ! in_array( $contact_record->getEmail(), $wp_network_managers_emails, true ) ) {
				$contact_record_updated = $this->unlinkAirtableNetworkIdFromContact( $contact_record );
				if ( $contact_record_updated ) {
					$this->unlinked_network_managers[] = $contact_record_updated;
				}
			}
		}
		return $this->unlinked_network_managers;
	}

	private function unlinkAirtableNetworkIdFromContact( ContactRecord $contact_record ) {
		$contact_record->removeNetwork( $this->airtable_network_record->getId() );
		$contact_record->setAirtableClient( $this->airtable_client );
		return $contact_record->updateInAirtable( [ ContactRecord::NETWORKS_FIELD, ContactRecord::NETWORK_MANAGER_FIELD ] );
	}

	private function linkWPNetworkManagersToAirtable() {
		$airtable_network_managers_email = $this->getEmailsFromAirtableNetworkManagers();
		$this->linked_network_managers = [];
		foreach ( $this->wp_network_managers as $username => $email ) {
			if ( ! in_array( $email, $airtable_network_managers_email, true ) ) {
				$contact_network_manager = $this->getAirtableContactByEmail( $email );
				if ( ! $contact_network_manager ) {
					$contact_network_manager = $this->createAirtableContactNetworkManager(
						$this->airtable_network_record,
						$email,
						$this->airtable_client
					);
				} else {
					$contact_network_manager = $this->setNetworkManagerToAirtableContact(
						$contact_network_manager,
						$this->airtable_network_record
					);
				}
				if ( $contact_network_manager ) {
					$this->linked_network_managers[] = $contact_network_manager;
				}
			}
		}
		return $this->linked_network_managers;
	}

	private function getAirtableContactByEmail( string $email ) {
		$filter_email_name_field = [ ContactRecord::EMAIL_FIELD => $email ];
		$contact_records = ContactRecord::getList(
			ContactRecord::AIRTABLE_TABLE_NAME,
			$filter_email_name_field,
			$this->airtable_client,
			ContactRecord::getFields()
		);
		return $contact_records ? $contact_records[0] : false;
	}

	private function setNetworkManagerToAirtableContact( ContactRecord $contact_record, NetworkRecord $network_record ) {
		$contact_record->addNetwork( $network_record->getId() );
		$contact_record->updateInAirtable( [ ContactRecord::NETWORKS_FIELD, ContactRecord::NETWORK_MANAGER_FIELD ] );
		return $contact_record;
	}

	private function createAirtableContactNetworkManager(
		NetworkRecord $airtable_record,
		string $email,
		AirtableClient $airtable_client = null
	) {
		$contact_network_manager = new ContactRecord();
		$name = $this->getAirtableContactNameByWPUserEmail( $email );
		if ( ! $name ) {
			return false;
		}
		$contact_network_manager = $contact_network_manager->create(
			[
				'fields' => [
					ContactRecord::NAME_FIELD => $name,
					ContactRecord::EMAIL_FIELD => $email,
					ContactRecord::NETWORKS_FIELD => [ $airtable_record->getId() ],
					ContactRecord::NETWORK_MANAGER_FIELD => true,
				],
			]
		);
		if ( ! is_null( $airtable_client ) ) {
			$contact_network_manager->setAirtableClient( $airtable_client );
		}
		$contact_network_manager->saveInAirtable();
		return $contact_network_manager;
	}

	private function getAirtableContactNameByWPUserEmail( string $email ) {
		$wp_user = get_user_by( 'email', $email );
		if ( ! $wp_user ) {
			return false;
		}
		$full_name = trim( "{$wp_user->first_name} {$wp_user->last_name}" );
		return empty( $full_name ) ? $wp_user->display_name : $full_name;
	}

	private function getEmailsFromAirtableNetworkManagers() {
		$contact_emails = [];
		foreach ( $this->airtable_network_managers as $contact_record ) {
			$contact_emails[] = $contact_record->getEmail();
		}
		return $contact_emails;
	}

	public function getUnlinkedNetworkManagers() {
		return $this->unlinked_network_managers;
	}

	public function getLinkedNetworkManagers() {
		return $this->linked_network_managers;
	}

}
