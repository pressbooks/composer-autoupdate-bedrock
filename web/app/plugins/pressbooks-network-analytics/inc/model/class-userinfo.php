<?php

namespace PressbooksNetworkAnalytics\Model;

use Pressbooks\DataCollector\User;

class UserInfo {

	// ------------------------------------------------------------------------
	// Public
	// ------------------------------------------------------------------------

	public function __construct() {
	}

	/**
	 * @param int $user_id
	 *
	 * @return array
	 */
	public function get( $user_id ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return [];
		}

		// User info
		$user_info['id'] = $user_id;
		$user_info['username'] = $user->user_login;
		$user_info['first_name'] = $user->first_name;
		$user_info['last_name'] = $user->last_name;
		$user_info['created_on'] = ! empty( $user->user_registered ) ? $user->user_registered : __( 'Unknown', 'pressbooks-network-analytics' );
		$last_login = get_user_meta( $user_id, User::LAST_LOGIN, true );
		$user_info['last_login'] = ! empty( $last_login ) ? $last_login : __( 'Unknown', 'pressbooks-network-analytics' );

		// Books
		$user_info = array_merge( $user_info, $this->books( $user_id ) );

		// TODO: Ranking ## of ### users on network

		return $user_info;
	}

	// ------------------------------------------------------------------------
	// Private
	// ------------------------------------------------------------------------

	/**
	 * All metadata for user
	 *
	 * @param int $user_id
	 *
	 * @return array
	 */
	private function metadata( $user_id ) {
		$meta = get_user_meta( $user_id );
		if ( is_array( $meta ) ) {
			return array_map(
				function ( $a ) {
					return $a[0];
				},
				$meta
			);
		} else {
			return [];
		}
	}

	/**
	 * Number of revisions made
	 *
	 * @param int $user_id
	 *
	 * @return int
	 */
	private function revisions( $user_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author = %d", $user_id ) );
	}

	/**
	 * Date of last revision
	 *
	 * @param $user_id
	 *
	 * @return string int
	 */
	private function dateOfLastRevision( $user_id ) {
		global $wpdb;
		return (string) $wpdb->get_var( $wpdb->prepare( "SELECT MAX(post_modified_gmt) FROM {$wpdb->posts} WHERE post_author = %d", $user_id ) );
	}

	/**
	 * @param int $user_id
	 *
	 * @return array{books: array, total_books: int, total_revision: int}
	 */
	private function books( $user_id ) {
		global $wpdb;
		$books = [];
		$metadata = $this->metadata( $user_id );
		$total_books = 0;
		$total_revisions = 0;
		$regex = "~{$wpdb->base_prefix}(\d+)_capabilities~";
		foreach ( $metadata as $meta_key => $meta_value ) {
			if ( preg_match( $regex, $meta_key, $matches ) ) {
				$roles = maybe_unserialize( $meta_value );
				if ( is_iterable( $roles ) ) {
					$blog_details = get_blog_details( $matches[1], false );
					if ( ! $blog_details->spam && ! $blog_details->deleted && ! $blog_details->archived ) {
						switch_to_blog( $matches[1] );
						$revisions = $this->revisions( $user_id );
						$last_revision = $this->dateOfLastRevision( $user_id );
						foreach ( $roles as $role => $bool ) {
							$details = [
								'blog_id' => $matches[1],
								'blogname' => get_option( 'blogname' ),
								'siteurl' => get_option( 'siteurl' ),
								'revisions' => $revisions,
								'last_revision' => $last_revision,
							];
							$books['books'][ $role ][] = $details;
						}
						$total_books++;
						$total_revisions += $revisions;
						restore_current_blog();
					}
				}
			}
		}
		if ( ! isset( $books['books'] ) ) {
			$books['books'] = [];
		}
		$books['total_books'] = $total_books;
		$books['total_revision'] = $total_revisions;
		return $books;
	}

}
