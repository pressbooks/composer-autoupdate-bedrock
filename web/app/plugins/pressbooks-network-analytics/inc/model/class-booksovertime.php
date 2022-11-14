<?php

namespace PressbooksNetworkAnalytics\Model;

use Pressbooks\DataCollector\Book;

class BooksOverTime {

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
		$created = Book::CREATED;
		$public = Book::PUBLIC;
		$is_clone = Book::IS_CLONE;
		$sql = "
				SELECT
					b.blog_id AS id,   
					MAX(IF(b.meta_key='{$created}',b.meta_value,null)) AS created,
					MAX(IF(b.meta_key='{$public}',CAST(b.meta_value AS UNSIGNED),null)) AS isPublic,
					MAX(IF(b.meta_key='{$is_clone}',b.meta_value,null)) AS isCloned
				FROM {$wpdb->blogmeta} b
				GROUP BY id 
				ORDER BY created ";

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

		// IMPORTANT! $this->booklist is expected to be ORDER(ed) BY created
		$grouped = [];
		foreach ( $this->booklist as $book ) {
			$year_month_1 = substr( $book->created, 0, 7 ) . '-01';
			$grouped[ $year_month_1 ][ $book->id ] = [
				'is_public' => ! empty( $book->isPublic ),
				'is_cloned' => ! empty( $book->isCloned ),
			];
		}

		$total_books = 0;
		$total_public_books = 0;
		$total_cloned_books = 0;
		$total_private_books = 0;
		$calculations = [];
		foreach ( $grouped as $year_month_1 => $arr ) {
			$total_books += count( $arr );
			foreach ( $arr as $stat ) {
				if ( $stat['is_public'] ) {
					$total_public_books++;
				} else {
					$total_private_books++;
				}
				if ( $stat['is_cloned'] ) {
					$total_cloned_books++;
				}
			}
			$calculations[ $year_month_1 ] = [
				'totalBooks' => $total_books,
				'totalPublicBooks' => $total_public_books,
				'totalClonedBooks' => $total_cloned_books,
				'totalPrivateBooks' => $total_private_books,

			];
		}
		if ( empty( $calculations ) ) {
			return [];
		}

		// Fix gaps in between missing months
		reset( $calculations );
		$first_date = key( $calculations );
		$i = new \DateInterval( 'P1M' );
		$period = new \DatePeriod( date_create( $first_date ), $i, date_create( date( 'Y-m-t' ) ) );
		$dataset = [];
		$current_calculation = [];
		foreach ( $period as $d ) {
			$year_month_1 = $d->format( 'Y-m-d' );
			if ( isset( $calculations[ $year_month_1 ] ) ) {
				$current_calculation = $calculations[ $year_month_1 ];
			}
			$current_calculation['date'] = $year_month_1;
			$current_calculation['dateLabel'] = date( 'M Y', strtotime( $year_month_1 ) );
			$dataset[] = $current_calculation;
		}
		return $dataset;
	}

}
