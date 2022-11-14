<?php
/**
 * Metadata customization.
 */

namespace Pressbooks_Selfe\Metadata;

use Gmo\Iso639\Languages;

/**
 * Convert $metadata and $options to SELF-e compatible format
 *
 * @param array $metadata
 * @param array $options
 *
 * @return array|bool
 */
function convert_to_selfe( array $metadata, array $options ) {
	$output = [];

	// Title (Required)
	$output['title'] = $metadata['pb_title'] ?? null;

	// Subtitle (Optional)
	if ( isset( $metadata['pb_subtitle'] ) ) {
		$output['subtitle'] = $metadata['pb_subtitle'];
	}

	// ISBN (Optional)
	if ( isset( $metadata['pb_ebook_isbn'] ) ) {
		$output['isbn'] = $metadata['pb_ebook_isbn'];
	}

	// Publication Year (Required)
	if ( isset( $metadata['pb_publication_date'] ) ) {
		$output['publicationYear'] = date( 'Y', (int) $metadata['pb_publication_date'] );
	} else {
		$output['publicationYear'] = date( 'Y' );
	}

	// Publisher (Optional)
	if ( isset( $metadata['pb_publisher'] ) ) {
		$output['publisher'] = $metadata['pb_publisher'];
	}

	// Vendor (Optional)
	if ( isset( $options['vendor'] ) ) {
		$output['vendor'] = $options['vendor'];
	}

	// Volume (Optional)
	if ( isset( $metadata['pb_series_number'] ) ) {
		$output['volume'] = $metadata['pb_series_number'];
	}

	// Edition (Optional)
	// TODO

	// Audience (Required)
	if ( isset( $metadata['pb_audience'] ) ) {
		$output['audience'] = strtoupper( str_replace( '-', '_', $metadata['pb_audience'] ) );
	} else {
		$output['audience'] = 'ADULT';
	}
	// Description (Required)
	$output['description'] = $metadata['pb_about_50'] ?? null;

	// inPrint (Optional)
	$output['inPrint'] = false;

	// Purchase Links (Optional)
	$purchase_links = get_option( 'pressbooks_ecommerce_links' );
	if ( is_array( $purchase_links ) ) {
		foreach ( $purchase_links as $link ) {
			$output['purchaseLinks'][] = $link;
		}
	}

	// InPrint (Optional)
	if ( isset( $output['purchaseLinks'] ) ) {
		$output['inPrint'] = true;
	}

	// Language (Required)
	$languages = new Languages();
	$output['language'] = $languages->findByCode1( substr( $metadata['pb_language'] ?? null, 0, 2 ) )->code2b();

	// Categories (Required)
	if ( isset( $metadata['pb_bisac_subject'] ) ) {
		$metadata['pb_bisac_subject'] = explode( ', ', $metadata['pb_bisac_subject'] );
		foreach ( $metadata['pb_bisac_subject'] as $subject ) {
			$category = \Pressbooks_Selfe\Data\map_categories( $subject );
			if ( $category ) {
				$output['categories'][] = [ 'categoryId' => $category ];
			}
		}
	}

	$contributors_map = [
		'pb_author' => 'AUTHOR',
		'pb_contributing_authors' => 'AUTHOR',
		'pb_editor' => 'EDITOR',
		'pb_translator' => 'TRANSLATOR',
	];
	foreach ( $contributors_map as $type => $role ) {
		if ( ! empty( $metadata[ $type ] ) ) {
			foreach ( $metadata[ $type ] as $contributor ) {
				$output['contributors'][] = [
					'prefix' => isset( $contributor['contributor_prefix'] ) ? $contributor['contributor_prefix'] : '',
					'firstName' => isset( $contributor['contributor_first_name'] ) ? $contributor['contributor_first_name'] : '',
					'middleName' => '',
					'lastName' => isset( $contributor['contributor_last_name'] ) ? $contributor['contributor_last_name'] : '',
					'suffix' => isset( $contributor['contributor_suffix'] ) ? $contributor['contributor_suffix'] : '',
					'role' => $role,
				];
			}
		}
	}
	if ( empty( $output['contributors'] ) ) {
		// Contributors (Required)
		return false;
	}

	// Opt-Ins (Optional)
	if ( isset( $options['submitter_region_module'] ) && 1 === (int) $options['submitter_region_module'] ) {
		$output['optIns'] = [
			[ 'optInId' => '1777e4be-2d33-488b-b258-41051f4001fc' ],
		];
	}

	// Submitter (Required)
	// TODO Use localLibraryId instead of localLibraryOther
	$output['submitter'] = [
		'firstName' => $options['submitter_first_name'] ?? null,
		'lastName' => $options['submitter_last_name'] ?? null,
		'email' => $options['submitter_email'] ?? null,
		'localLibraryOther' => $options['submitter_library_other'] ?? null,
		'countryCode' => $options['submitter_country'] ?? null,
		'regionCode' => ( $options['submitter_country'] ?? null ) . '-' . ( $options['submitter_region'] ?? null ),
	];

	// Project ID (Required)
	$output['projectId'] = \Pressbooks_Selfe\Data\get_project_id();

	// Organization ID
	// TODO

	return $output;
}
