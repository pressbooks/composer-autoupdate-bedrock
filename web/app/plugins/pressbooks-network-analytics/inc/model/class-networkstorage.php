<?php

namespace PressbooksNetworkAnalytics\Model;

use Pressbooks\DataCollector\Book;

class NetworkStorage {

	/**
	 * @var object[]
	 */
	private $booklist = [];

	// ------------------------------------------------------------------------
	// Public
	// ------------------------------------------------------------------------

	/**
	 * Constructor
	 */
	public function __construct() {
	}

	/**
	 * @return array
	 */
	public function get() {
		try {
			$this->queryBlogMetaTable();
			return $this->groupIntoDataSet();
		} catch ( \LengthException $e ) {
			return [];
		}
	}

	// ------------------------------------------------------------------------
	// Private
	// ------------------------------------------------------------------------

	private function queryBlogMetaTable() {
		global $wpdb;

		// Pivot table / Cross-tabulated table
		$title = Book::TITLE;
		$url = BOOK::MEDIA_LIBRARY_URL;
		$storage_size = Book::STORAGE_SIZE;
		$sql = "
				SELECT
					b.blog_id AS id,   
					MAX(IF(b.meta_key='{$title}',b.meta_value,null)) AS bookTitle,
					MAX(IF(b.meta_key='{$url}',b.meta_value,null)) AS bookLink,
					MAX(IF(b.meta_key='{$storage_size}',CAST(b.meta_value AS UNSIGNED),null)) AS storageSize
				FROM {$wpdb->blogmeta} b
				GROUP BY id 
				ORDER BY storageSize DESC";

		// Results
		$this->booklist = $wpdb->get_results( $sql );

		if ( empty( $this->booklist ) ) {
			throw new \LengthException( 'Unexpected empty result set' );
		}
	}

	/**
	 * @return array
	 */
	private function groupIntoDataSet() {
		$total_size_of_all_books = 0;
		$dataset = [];
		foreach ( $this->booklist as $book ) {
			$dataset[] = [
				'id' => $book->id,
				'bookName' => $book->bookTitle,
				'bookLink' => $book->bookLink,
				'totalSize' => $book->storageSize,
			];
			$total_size_of_all_books += $book->storageSize;
		}
		foreach ( $dataset as $k => $v ) {
			$dataset[ $k ]['storagePercent'] = round( ( $v['totalSize'] * 100 ) / $total_size_of_all_books, 0, PHP_ROUND_HALF_DOWN );
		}
		return $dataset;
	}

}
