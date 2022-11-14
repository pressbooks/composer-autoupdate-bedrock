=== User Activation Keys ===
Contributors: dsader
Donate link: http://dsader.snowotherway.org
Tags: signup, username, activation, network, network user activation, activation keys, multisite, network, users
Requires at least: 3.5
Tested up to: 4.6
Stable tag: Trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A Multisite Network plugin for user activation key removal or approval. 

== Description ==

A [Multisite](http://codex.wordpress.org/Create_A_Network) Network plugin.

Ever signup a user account, delete it, and try to signup up the same username again? 

Ever try to support a new user who created a username, but entered the wrong email address and so can't activate, and can't signup with the same username?

Ever wanted to signup a bunch of users with phony emails so users without email could start blogging right away?

I have, so I made a plugin to help me.

WP Network Multisite "mu-plugin" for user activation key removal or approval. 

See Network-->Users-->"User Activation Keys" to delete activation keys - to allow immediate (re)signup of users who otherwise get the "try again in two days" message. 

Also, users waiting to be activated (or can't because the email with the generated activation link is "gone") can be approved manually.


== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin to your blog, Network Activate it
2. Delete or approve keys at Network-->Users-->"User Activation Keys" page

== Frequently Asked Questions ==

* I deleted an activation key, but I still can't signup with that username? Delete the user from Network->Users and delete the activation key.
* Is it safe to delete keys from otherwise active users? Yes, as far as I know. I don't know why the activation key for activated users remains behind. But they appear to delete automagicaly after "a while" anyway.
* Some users have an activation key in $wpdb->users? That is normal. This plugin doesn't touch those keys, only the ones in $wpdb->signup.

== Screenshots ==

1. Network Admin->Users->"User Activation Keys" page

== Changelog ==
= 4.6 =
* WP 4.6 tests OK, cleanup php notices

== Upgrade Notice ==
= 4.6 =
* WP 4.6 tests OK, cleanup php notices