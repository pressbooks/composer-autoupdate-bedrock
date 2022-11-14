<?php

use function \Pressbooks_Selfe\Metadata\convert_to_selfe;

class MetadataTest extends WP_UnitTestCase {

	function test_filter_metadata() {
		$meta1 = [];
		$meta = convert_to_selfe( $meta1, [] );
		$this->assertFalse( $meta);

		$meta2 = [
			'pb_title' => 'Hello World',
			'pb_about_50' => 'This is your life.',
			'pb_language' => 'fr',
			'pb_author' => [
				[
					'name' => 'Pat Metheny',
					'contributor_prefix' => "Mr.",
					'contributor_first_name' => "Patrick",
					'contributor_last_name' => "Metheny",
					'contributor_suffix' => "PhD",
				]
			],
			'pb_contributing_authors' => [
				[
					'name' => 'John D',
					'contributor_prefix' => "Mr.",
					'contributor_first_name' => "John",
					'contributor_last_name' => "Doe",
					'contributor_suffix' => "PhD",
				]
			]
		];
		$meta = convert_to_selfe( $meta2, [] );
		$this->assertEquals( $meta['title'], 'Hello World' );
		$this->assertEquals( $meta['description'], 'This is your life.' );
		$this->assertEquals( $meta['language'], 'fre' );
		$this->assertEquals( $meta['contributors'][0]['firstName'], 'Patrick' );
		$this->assertEquals( $meta['contributors'][0]['suffix'], 'PhD' );
		$this->assertEquals( $meta['contributors'][1]['prefix'], 'Mr.' );
		$this->assertEquals( $meta['contributors'][1]['lastName'], 'Doe' );

		$meta3['author']['name'] = '# # # # # # # # # # #';
		$meta = convert_to_selfe( $meta3, []);
		$this->assertFalse( $meta );
	}

}
