<?php

use PressbooksNetworkAnalytics\NetworkManagersNotification;
use PressbooksNetworkAnalytics\Airtable\AirtableClient;
use PressbooksNetworkAnalytics\Airtable\AirtableSynchronizer;

class NetworkManagersNotificationTest extends \WP_UnitTestCase {

	/**
	 * @var NetworkManagersNotification
	 */
	protected $nm_notification;

	/**
	 * @var \PHPUnit\Framework\MockObject\MockBuilder
	 */
	protected $airclient_mock;

	/**
	 * @var \WP_User
	 */
	protected $users;

	/**
	 * @var array
	 */
	protected $contact_records;

	/**
	 * @var array
	 */
	protected $contact_records_with_managers;

	/**
	 * @var array
	 */
	protected $network_record;

	/**
	 * @var array
	 */
	protected $new_contact;

	/**
	 * @var AirtableSynchronizer
	 */
	protected $airtable_syncronizer;

	const RECORDS_MOCK_FILE_PATHS = [
		'contacts' => __DIR__.'/mock/airtable/contact-records.json',
		'network' => __DIR__.'/mock/airtable/network-info.json',
		'new_contact' => __DIR__.'/mock/airtable/contact-record.json',
		'contact_with_network_managers' => __DIR__.'/mock/airtable/contact-records-with-manager.json',
	];

	/**
	 * Test set_up
	 */
	public function set_up() {
		parent::set_up();

		$users_ids = $this->factory()->user->create_many( 2, [ 'role' => 'administrator' ] );
		foreach ( $users_ids as $user_id ) {
			$this->users[] = [
				'userObject' => get_userdata( $user_id ),
				'isNetworkManager' => false
			];
		}

		$this->contact_records = json_decode(
			file_get_contents( self::RECORDS_MOCK_FILE_PATHS['contacts'] ),
			true
		);
		$this->network_record = json_decode(
			file_get_contents( self::RECORDS_MOCK_FILE_PATHS['network'] ),
			true
		);
		$this->new_contact = json_decode(
			file_get_contents( self::RECORDS_MOCK_FILE_PATHS['new_contact'] ),
			true
		);
		$this->contact_records_with_managers = json_decode(
			file_get_contents( self::RECORDS_MOCK_FILE_PATHS['contact_with_network_managers'] ),
			true
		);

		$this->setEnvironmentVariables();
		$this->airclient_mock = $this
			->getMockBuilder( AirtableClient::class )
			->setMethods([
				'getRecordsListDecodedByTable',
				'addRecordsToATable',
				'updateTableRecords',
			])
			->getMock();
		$this->airtable_syncronizer = new AirtableSynchronizer( $this->airclient_mock );
		$this->nm_notification = new NetworkManagersNotification( $this->airclient_mock );
	}

	private function setEnvironmentVariables() {
		putenv( 'AIRTABLE_API_URL=fakeUrl' );
		putenv( 'AIRTABLE_BASE_ID=fakeBaseId' );
		putenv( 'AIRTABLE_API_KEY=fakeApiKey' );
	}

	private function setUsersAsANetworkManager( int $number_of_users_to_set ) {
		if ( count( $this->users ) >= $number_of_users_to_set ) {
			$number_of_users_added = 0;
			foreach ( $this->users as &$user ) {
				if ( $number_of_users_added < $number_of_users_to_set ) {
					grant_super_admin( $user['userObject']->data->ID );
					update_site_option( 'pressbooks_network_managers', [ intval( $user['userObject']->data->ID ) ] );
					$user['isNetworkManager'] = true;
					$number_of_users_added ++;
				} else {
					return true;
				}
			}
		}
		return false;
	}

	private function removeAllNetworkManagers() {
		foreach ( $this->users as &$user)  {
			if ( $user['isNetworkManager'] ) {
				update_site_option( 'pressbooks_network_managers', [] );
				revoke_super_admin( $user['userObject']->data->ID );
				$user['isNetworkManager'] = false;
			}
		}
		return true;
	}

	/**
	 * @group networkmanagersnotification
	 */
	public function test_getInstance() {
		$network_managers_notitication = $this->nm_notification->init();
		$this->assertInstanceOf( '\PressbooksNetworkAnalytics\NetworkManagersNotification', $network_managers_notitication );
	}

	/**
	 * @group networkmanagersnotification
	 */
	public function test_hook() {
		global $wp_filter;
		$result = $this->nm_notification->init();
		$this->nm_notification->hooks( $result );
		$this->assertNotEmpty( $wp_filter );
	}

	/**
	 * @group networkmanagersnotification
	 */
	public function test_add_network_manager_in_airtable() {
		$this->airclient_mock = $this
			->getMockBuilder( AirtableClient::class )
			->setMethods([
				'getRecordsListDecodedByTable',
				'addRecordsToATable',
				'updateTableRecords',
			])
			->getMock();
		$this->airclient_mock->expects( $this->any() )
			->method( 'getRecordsListDecodedByTable' )
			->will( $this->onConsecutiveCalls( $this->network_record, $this->contact_records ) );
		$this->airclient_mock->expects( $this->any() )
			->method( 'addRecordsToATable' )
			->willReturn( $this->new_contact );
		$this->airclient_mock->expects( $this->any() )
			->method( 'updateTableRecords' )
			->will( $this->onConsecutiveCalls( $this->new_contact, $this->network_record ) );

		$number_of_new_network_managers = 1;
		$this->setUsersAsANetworkManager( $number_of_new_network_managers );
		$this->nm_notification = new NetworkManagersNotification( $this->airclient_mock );
		$this->assertTrue( $this->nm_notification->updateNetworkManagers( 'pressbooks_network_managers', [ ] ) );
		$synchronizer = $this->nm_notification->getAirtableSynchronizer();
		$this->assertCount( 2, $synchronizer->getUnlinkedNetworkManagers() );
		$linked_network_managers = $synchronizer->getLinkedNetworkManagers();
		$this->assertCount( $number_of_new_network_managers, $linked_network_managers );

		foreach ($linked_network_managers as $linked_network_manager) {
			$this->assertTrue( $linked_network_manager->getNetworkManager() );
			$this->assertContains( $this->network_record['records'][0]['id'], $linked_network_manager->getNetworkIds() );
		}
	}

	/**
	 * @group networkmanagersnotification
	 */
	public function test_remove_network_manager_in_airtable() {
		// Clean any network manager present
		$this->removeAllNetworkManagers();
		$number_of_new_network_managers = 1;
		$this->setUsersAsANetworkManager( $number_of_new_network_managers );

		$this->airclient_mock = $this
			->getMockBuilder( AirtableClient::class )
			->setMethods([
				'getRecordsListDecodedByTable',
				'addRecordsToATable',
				'updateTableRecords',
			])
			->getMock();
		$this->airclient_mock->expects( $this->any() )
			->method( 'getRecordsListDecodedByTable' )
			->will( $this->onConsecutiveCalls( $this->network_record, $this->contact_records_with_managers ) );
		$this->airclient_mock->expects( $this->any() )
			->method( 'addRecordsToATable' )
			->willReturn( $this->new_contact );
		$this->airclient_mock->expects( $this->any() )
			->method( 'updateTableRecords' )
			->will( $this->onConsecutiveCalls( $this->contact_records_with_managers, $this->network_record ) );

		$this->removeAllNetworkManagers();
		$this->nm_notification = new NetworkManagersNotification( $this->airclient_mock );
		$this->assertTrue( $this->nm_notification->updateNetworkManagers( 'pressbooks_network_managers', [] ) );
		$synchronizer = $this->nm_notification->getAirtableSynchronizer();

		$unlinked_network_managers = $synchronizer->getUnlinkedNetworkManagers();
		$this->assertCount( $number_of_new_network_managers, $unlinked_network_managers );
		$this->assertCount( 0, $synchronizer->getLinkedNetworkManagers() );

		 foreach ( $unlinked_network_managers as $net_manager ) {
		 	$this->assertFalse( $net_manager->getNetworkManager() );
		 	$this->assertNotContains( $this->network_record['records'][0]['id'], $net_manager->getNetworkIds() );
		 }

	}
}
