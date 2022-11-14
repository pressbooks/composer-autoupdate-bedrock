<?php
/*
Plugin Name: User Activation Keys
Plugin URI: http://dsader.snowotherway.org/wordpress-plugins/user-activation-keys/
Description: WP Network Multisite user activation key removal or approval Network Plugin. See Network-->Users->"User Activation Keys" to delete activation keys - to allow immediate (re)signup of users who otherwise get the "try again in two days" message. Also, users waiting to be activated (or can't because the email with the generated activation link is "gone") can be approved manually.
Author: D. Sader
Version: 4.6
Author URI: http://dsader.snowotherway.org
Network: true

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

*/
class DS_User_Activation_Keys {

	function __construct() {
		add_action('network_admin_menu', array($this, 'ds_uak_admin_page'));
	}

	function ds_uak_admin_page() {
        add_submenu_page('users.php', 'User Activation Keys', 'User Activation Keys', 'edit_users', 'act_keys', array($this, 'ds_delete_stale'));
	}

	function ds_delete_stale() {
		global $wpdb;
 		$query = "SELECT * FROM {$wpdb->signups} ORDER BY registered DESC";
		$results = $wpdb->get_results($query, ARRAY_A);
		if(isset($_GET['delete'])) {
			$delete = $_GET['delete'];
		}

		$location = network_admin_url('users.php?page=act_keys');

		if ( !empty($delete) ) {
			check_admin_referer('activation_key');
  			$wpdb->delete( $wpdb->signups, array( 'activation_key' => $delete ) );
         	echo '<meta http-equiv="refresh" content="0;url='.$location.'" />';
            exit;
		}
	
		echo '<div class="wrap">';
		echo "<h2>User Activation Keys</h2>";
		if ( $results ) {
		echo '<p>The following is a list of user activation keys from $wpdb->signups. Delete a key to allow the username to (re)signup and bypass the "couple days" it takes WP to free up its hold on a user name. You can also manually approve users that for whatever reason have not completed their activation.</p>';

		echo '<table class="widefat"><tbody>';
		echo '<thead><th>#</th><th>Registered</th><th>User</th><th>Email</th><th>Approve</th></thead>';
		foreach ( $results as $rows ) {
			global $ct;
			echo '<tr><td>' . ++$ct . '</td><td>'.$rows['registered'].'</td><td>'.$rows['user_login'].'</td><td>'.$rows['user_email'].'</td>';
			if($rows['active'] != '1') {
			echo '<td><a href="' . site_url('wp-activate.php?key='.$rows['activation_key']) . '" target="_blank">approve</a> | <a href="' . wp_nonce_url( $location . '&delete='.$rows['activation_key'], 'activation_key' ) . '">delete unused key</a></td>';
		} else {
		echo '<td>User Activated '.$rows['activated'].' | <a href="' .  wp_nonce_url( $location . '&delete='.$rows['activation_key'] , 'activation_key' ).'">delete unnecessary key</a></td>';
		}
		echo '</tr>';
		}
		echo '</tbody></table>';
		} else {
		echo '<p>No user activation keys in $wpdb->signups. If you delete a user, you should be able to reuse the username immediatley. If the user still had a registration key, it would need to be deleted before you could signup again right away with the same username. You can also manually approve users that for whatever reason have not completed their activation.</p>';
		}
		echo '</div>';
	}
}

new DS_User_Activation_Keys();
?>