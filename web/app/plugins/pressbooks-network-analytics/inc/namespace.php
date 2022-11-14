<?php

namespace PressbooksNetworkAnalytics;

use Pressbooks\Container;

function blade() {
	return Container::get( 'Blade' );
}

/**
 * Convert an array of Objects (used to make JSON for Tabulator) into CSV
 *
 * @param object[] &$array
 *
 * @return string
 */
function objects_2_csv( array &$array ) {
	if ( count( $array ) === 0 ) {
		return '';
	}
	ob_start();
	$df = fopen( 'php://output', 'w' );
	$row = array_keys( get_object_vars( $array[0] ) );
	fputcsv( $df, $row );
	foreach ( $array as $val ) {
		$row = array_values( get_object_vars( $val ) );
		fputcsv( $df, $row );
	}
	fclose( $df );
	return ob_get_clean();
}

/**
 * @param string $val
 *
 * @return int
 */
function return_kilobytes( $val ) {
	$val = trim( $val );

	if ( is_numeric( $val ) ) {
		return $val;
	}

	$last = strtolower( $val[ strlen( $val ) - 1 ] );
	$val = substr( $val, 0, -1 ); // necessary since PHP 7.1; otherwise optional

	switch ( $last ) {
		// The 'G' modifier is available since PHP 5.1.0
		case 'g':
			$val *= 1000;
			// no break
		case 'm':
			$val *= 1000;
	}

	return $val;
}

/**
 * @param mixed $val
 *
 * @return bool
 */
function is_truthy( $val ) {
	if ( strtolower( $val ) === 'true' || $val === '1' || $val === 1 || $val === true ) {
		return true;
	}
	return false;
}
