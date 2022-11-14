<?php
/**
 * Internal data.
 */

namespace Pressbooks_Selfe\Data;

use League\Csv\Reader;

function get_project_id() {
	return apply_filters( 'pb_selfe_project_id', '7d27a2b3-47d2-4a1e-b2bf-786577f753b9' );
}

function get_subjects() {
	$csv = file_get_contents( dirname( __FILE__ ) . '/../../data/categories.csv' );
	$reader = Reader::createFromString( $csv );

	$codes = [];

	foreach ( $reader as $key => $value ) {
		if ( $key === 0 ) {
			continue;
		}

		$codes[ $value[2] ] = $value[1];
	}

	return $codes;
}

function map_categories( $category ) {
	$csv = file_get_contents( dirname( __FILE__ ) . '/../../data/categories.csv' );
	$reader = Reader::createFromString( $csv );

	$categories = [];

	foreach ( $reader as $key => $value ) {
		if ( $key === 0 ) {
			continue;
		}

		$categories[ $value[2] ] = $value[0];
	}

	if ( isset( $categories[ $category ] ) ) {
		return $categories[ $category ];
	}

	return false;
}
