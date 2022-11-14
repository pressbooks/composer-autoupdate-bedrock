# Pressbooks BiblioBoard OAuth

Contributors: greatislander, conner_bw
Requires PHP: 7.4
Requires at least: 6.0.3
Tested up to: 6.0.3
Stable tag: 3.2.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Allows users to login or register on a Pressbooks network by authenticating via the BiblioBoard OAuth 2.0 provider.

Pressbooks OAuth (for BiblioBoard) 3.0.0 removed GitHub and Google support. This plugin is only for BiblioBoard moving forward.

## Installation

# Download

Clone the repository.

`git clone git@github.com:pressbooks/pressbooks-biblioboard-oauth.git /path/to/wp-content/plugins/pressbooks-biblioboard-oauth`

`cd /path/to/wp-content/plugins/pressbooks-biblioboard-oauth`

Install the required libraries via [Composer](https://getcomposer.org).

`composer install`

Compile the SCSS and JS assets using [Laravel Mix](https://github.com/jeffreyway/laravel-mix).

`yarn && yarn run production`

# Configuration

+ Bibiliolabs: https://auth-test.biblioboard.com/oauth/authorize

## Changelog

### 3.2.0

* See: https://github.com/pressbooks/pressbooks-biblioboard-oauth/releases/tag/3.2.0
* Full release history available at: https://github.com/pressbooks/pressbooks-biblioboard-oauth/releases
