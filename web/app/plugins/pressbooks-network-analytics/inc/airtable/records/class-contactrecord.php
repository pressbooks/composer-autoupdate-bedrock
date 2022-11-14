<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace PressbooksNetworkAnalytics\Airtable\Records;

use function Pressbooks\Utility\debug_error_log;

class ContactRecord extends AirtableRecord implements  RecordInterface {

	const AIRTABLE_TABLE_NAME = 'Contacts';
	const NAME_FIELD = 'Name';
	const NETWORKS_FIELD = 'Networks';
	const EMAIL_FIELD = 'Email';
	const NETWORK_MANAGER_FIELD = 'Network Manager';

	/**
	 * @var
	 */
	protected $name;

	/***
	 * @var string
	 */
	protected $email;

	/**
	 * @var boolean
	 */
	protected $network_manager;

	/**
	 * @var array
	 */
	protected $networks_id;

	public function __construct() {
		$this->map_field_property = [
			self::NAME_FIELD => 'name',
			self::EMAIL_FIELD => 'email',
			self::NETWORK_MANAGER_FIELD => 'network_manager',
			self::NETWORKS_FIELD => 'networks_id',
		];
	}

	public function create( array $record_array ) {
		$this->name = $record_array['fields'][ self::NAME_FIELD ];
		$this->email = $record_array['fields'][ self::EMAIL_FIELD ];
		$this->network_manager = array_key_exists( self::NETWORK_MANAGER_FIELD, $record_array['fields'] ) ?
			$record_array['fields'][ self::NETWORK_MANAGER_FIELD ] : false;
		$this->networks_id = array_key_exists( self::NETWORKS_FIELD, $record_array['fields'] ) ?
			$record_array['fields'][ self::NETWORKS_FIELD ] : [];
		return $this;
	}

	public static function getTableName() {
		return self::AIRTABLE_TABLE_NAME;
	}

	public static function getFields() {
		return [
			self::NAME_FIELD,
			self::NETWORKS_FIELD,
			self::EMAIL_FIELD,
			self::NETWORK_MANAGER_FIELD,
		];
	}

	public function getEmail() {
		return $this->email;
	}

	public function setEmail( string $email ) {
		$this->email = $email;
	}

	public function addNetwork( string $network_id ) {
		$this->networks_id[] = $network_id;
		$this->network_manager = true;
	}

	public function removeNetwork( string $network_id ) {
		$key = array_search( $network_id, $this->networks_id, true );
		if ( $key !== false ) {
			unset( $this->networks_id[ $key ] );
		}
		if ( empty( $this->networks_id ) ) {
			$this->network_manager = false;
		}
	}

	public function getNetworkManager() {
		return $this->network_manager;
	}

	public function getNetworkIds() {
		return $this->networks_id;
	}

	public function setName( string $name = null ) {
		if ( is_null( $name ) && is_null( $this->email ) ) {
			return false;
		}
		$this->name = ! is_null( $name ) ? $name : $this->getNameFieldByEmail( $this->email );
	}

	private function getNameFieldByEmail( string $email ) {
		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			debug_error_log( 'Error adding contact to Airtable: Email ' . $email . ' not found.' );
			return false;
		}
		$user_field = trim( "{$user->first_name} {$user->last_name}" );
		return empty( $user_field ) ? $user->display_name : $user_field;
	}

	public function updateInAirtable( array $fields = [] ) {
		$fields = $this->filterByValidAirtableFields( $fields );
		return $fields ? parent::updateRecordInAirtable( self::AIRTABLE_TABLE_NAME, $fields ) : false;
	}

	private function filterByValidAirtableFields( array $fields = [] ) {
		$fields_intersect = array_intersect( self::getFields(), $fields );
		return count( $fields_intersect ) > 0 ? $fields_intersect : false;
	}

	public function saveInAirtable() {
		return parent::saveRecordInAirtable( self::AIRTABLE_TABLE_NAME, self::getFields() );
	}
}
