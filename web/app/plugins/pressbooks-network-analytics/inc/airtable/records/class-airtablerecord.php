<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace PressbooksNetworkAnalytics\Airtable\Records;

use PressbooksNetworkAnalytics\Airtable\AirtableClient;

abstract class AirtableRecord {

	const AIRTABLE_TABLE_NAME = '';

	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var AirtableClient
	 */
	protected $airtable_client;

	/**
	 * @var array
	 */
	protected $map_field_property;

	public function setId( string $id = null ) {
		$this->id = $id;
	}

	public function setAirtableClient( AirtableClient $airtable_client ) {
		$this->airtable_client = $airtable_client;
	}

	public function getAirtableClient() {
		return $this->airtable_client;
	}

	public function getId() {
		return $this->id;
	}

	public function saveRecordInAirtable( string $airtable_name, array $fields ) {
		$fields_to_save = [];
		foreach ( $fields as $field ) {
			$property = $this->map_field_property[ $field ];
			$fields_to_save[ $field ] = $this->{ $property };
		}
		$contact_updated_array = $this->airtable_client->addRecordsToATable(
			$airtable_name,
			$fields_to_save
		);
		return $contact_updated_array ? $this : false;
	}

	public function updateRecordInAirtable( string $airtable_table_name, array $fields_intersect = [] ) {
		$fields_update = [];
		foreach ( $fields_intersect as $field ) {
			$property = $this->map_field_property[ $field ];
			$fields_update[ $field ] = ! is_array( $this->{ $property } ) ?
				$this->{ $property } :
				array_values( $this->{ $property } );
		}
		if ( count( $fields_intersect ) === 0 ) {
			return false;
		}
		$contact_updated_array = $this->airtable_client->updateTableRecords(
			$airtable_table_name,
			[
				[
					'id' => $this->id,
					'fields' => $fields_update,
				],
			]
		);
		return ( $contact_updated_array ) ? $this : false;
	}

	public static function getList(
		string $airtable_table_name,
		array $field_value_filter,
		AirtableClient $airtable_client = null,
		array $allowed_fields
	) {
		$airtable_client = is_null( $airtable_client ) ? new AirtableClient() : $airtable_client;
		$find_filter = null;
		if (
			count( $field_value_filter ) === 1 &&
			in_array( key( $field_value_filter ), $allowed_fields, true )
		) {
			$field = key( $field_value_filter );
			$find_filter = 'FIND("' . $field_value_filter[ $field ] . '",{' . $field . '})';
		}
		$raw_records = $airtable_client->getRecordsListDecodedByTable(
			$airtable_table_name,
			$allowed_fields,
			1000,
			null,
			$find_filter
		);
		if ( ! $raw_records ) {
			return false;
		}
		$record_objects = [];
		foreach ( $raw_records['records'] as $record ) {
			$record_object = new AirtableRecordFactory();
			$record_objects[] = $record_object->create( $airtable_table_name, $record, $airtable_client );
		}
		return $record_objects;
	}
}
