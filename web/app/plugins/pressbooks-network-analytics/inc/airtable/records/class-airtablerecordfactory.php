<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace PressbooksNetworkAnalytics\Airtable\Records;

use PressbooksNetworkAnalytics\Airtable\AirtableClient;

class AirtableRecordFactory {

	const CONTACTS_TYPE_NAME = 'Contacts';
	const NETWORKS_TYPE_NAME = 'Networks';

	public function create( string $record_type, array $record_array, AirtableClient $airtable_client ) {
		switch ( $record_type ) {
			case self::CONTACTS_TYPE_NAME:
				$record_object = new ContactRecord();
				break;
			case self::NETWORKS_TYPE_NAME:
				$record_object = new NetworkRecord();
				break;
		}
		$id = array_key_exists( 'id', $record_array ) ? $record_array['id'] : null;
		$record_object->setId( $id );
		$airtable_client = is_null( $airtable_client ) ? new AirtableClient() : $airtable_client;
		$record_object->setAirtableClient( $airtable_client );

		$record_object->create( $record_array );
		return $record_object;
	}

}
