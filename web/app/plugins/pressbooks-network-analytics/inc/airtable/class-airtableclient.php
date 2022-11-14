<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace PressbooksNetworkAnalytics\Airtable;

use function \Pressbooks\Utility\debug_error_log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use GuzzleHttp\RequestOptions;

class AirtableClient {

	/**
	 * @var GuzzleHttp\Client
	 */
	private $guzzle_client;

	/**
	 * @var string
	 */
	private $url_base;

	/**
	 * @var array
	 */
	private $headers;

	/**
	 * Constructor
	 *
	 * @param
	 *
	 * @return void
	 */
	public function __construct() {
		$this->guzzle_client = new Client();
		try {
			$this->url_base = env( 'AIRTABLE_API_URL' ) . env( 'AIRTABLE_BASE_ID' );
			$this->headers = [ 'Authorization' => 'Bearer ' . env( 'AIRTABLE_API_KEY' ) ];
		} catch ( \Exception $e ) {
			$this->url_base = '';
			$this->headers = [];
			debug_error_log( 'Airtable initialization error: ' . $e->getMessage() );
		}
	}

	private function getHttpGuzzleResponse( string $method, array $additional_headers = null, string $airtable_table, array $http_body ) {
		try {
			$additional_headers = is_null( $additional_headers ) ? [] : $additional_headers;
			$http_headers = array_merge( $this->headers, $additional_headers );
			return $this->guzzle_client->request(
				$method, $this->url_base . '/' . $airtable_table,
				array_merge( [ 'headers' => $http_headers ], $http_body )
			);
		} catch ( RequestException $e ) {
			$error = 'Airtable Request: ' . Psr7\Message::toString( $e->getRequest() ) . '\n';
			if ( $e->hasResponse() ) {
				$error .= 'Airtable Response: ' . Psr7\Message::toString( $e->getResponse() ) . '\n';
			}
			debug_error_log( $error );
		}
		return false;
	}

	private function getGuzzleJsonDecode( \GuzzleHttp\Psr7\Response $guzzle_response ) {
		try {
			return \GuzzleHttp\json_decode( $guzzle_response->getBody()->getContents(), true );
		} catch ( InvalidArgumentException $e ) {
			debug_error_log( 'Error parsing HTTP Guzzle response: ' . $e->getMessage() );
			return false;
		}
	}

	private function thereAreRecordsInArray( array $airtable_records_array ) {
		return (
			$airtable_records_array !== false &&
			array_key_exists( 'records', $airtable_records_array ) && count( $airtable_records_array['records'] ) > 0
		);
	}

	public function getRecordsListDecodedByTable(
		string $airtable_table,
		array $table_fields,
		int $max_records = 100,
		$offset = null,
		$filter = null
	) {
		$http_body = [
			'query' => [
				'fields' => $table_fields,
				'maxRecords' => $max_records,
				'offset' => $offset,
				'filterByFormula' => $filter,
			],
		];
		return $this->getRecordsInJsonFormat( 'GET', [], $airtable_table, $http_body );
	}

	private function getRecordsInJsonFormat( string $method, array $additional_headers = null, string $airtable_table, array $http_body ) {
		$guzzle_response = $this->getHttpGuzzleResponse( $method, $additional_headers, $airtable_table, $http_body );
		if ( $guzzle_response ) {
			$records = $this->getGuzzleJsonDecode( $guzzle_response );
			if ( ! $this->thereAreRecordsInArray( $records ) ) {
				return false;
			}
			return $records;
		}
		return false;
	}

	public function addRecordsToATable( string $airtable_table, array $records ) {
		$http_body = [ RequestOptions::JSON => [ 'records' => [ [ 'fields' => $records ] ] ] ];
		return $this->getRecordsInJsonFormat( 'POST', [], $airtable_table, $http_body );
	}

	public function updateTableRecords( string $airtable_table, array $records ) {
		$http_body = [ RequestOptions::JSON => [ 'records' => $records ] ];
		$additional_http_header = [ 'Content-Type' => 'application/json' ];
		return $this->getRecordsInJsonFormat( 'PATCH', $additional_http_header, $airtable_table, $http_body );
	}

}
