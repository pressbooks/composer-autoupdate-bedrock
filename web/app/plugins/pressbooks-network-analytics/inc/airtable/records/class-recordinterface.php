<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace PressbooksNetworkAnalytics\Airtable\Records;

interface RecordInterface {
	public function create( array $record_array );
	public static function getTableName();
	public static function getFields();
	public function saveInAirtable();
}
