<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace PressbooksNetworkAnalytics\Airtable\Records;

use PressbooksNetworkAnalytics\Airtable\AirtableClient;

class NetworkRecord extends AirtableRecord implements RecordInterface {

	const AIRTABLE_TABLE_NAME = 'Networks';
	const NETWORK_MANAGERS_FIELD = 'Network Managers';
	const NAME_FIELD = 'Network Name';

	/**
	 * @var
	 */
	protected $name;

	/**
	 * @var array
	 */
	protected $network_managers_id;

	public function __construct() {
		$this->map_field_property = [
			self::NAME_FIELD => 'name',
			self::NETWORK_MANAGERS_FIELD => 'network_managers_id',
		];
	}

	public function create( array $record_array ) {
		$this->id = $record_array['id'];
		$this->name = $record_array['fields'][ self::NAME_FIELD ];
		$this->network_managers_id = array_key_exists( self::NETWORK_MANAGERS_FIELD, $record_array['fields'] ) ?
			$record_array['fields'][ self::NETWORK_MANAGERS_FIELD ] : [];
		$this->airtable_client = new AirtableClient();
		return $this;
	}

	public function getName() {
		return $this->name;
	}

	public static function getFields() {
		return [
			self::NAME_FIELD,
			self::NETWORK_MANAGERS_FIELD,
		];
	}

	public function getId() {
		return $this->id;
	}

	public function getNetworkManagersId() {
		return $this->network_managers_id;
	}

	public static function getTableName() {
		return self::AIRTABLE_TABLE_NAME;
	}

	public function saveInAirtable() {
		return parent::saveRecordInAirtable( self::AIRTABLE_TABLE_NAME, self::getFields() );
	}

}
