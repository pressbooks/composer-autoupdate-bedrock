# Pressbooks Plugins Config

**Contributors:** arzola, fdalcin
**Tags:** pressbooks, plugins, config
**Requires at least:** 6.0.3
**Tested up to:** 6.0.3
**Stable tag:** 1.6.0
**License:** GPLv3 or later
**License URI:** https://www.gnu.org/licenses/gpl-3.0.html

## Description

This plugin allows configuring third party plugins without touching PB core.

## Installation

```
composer require pressbooks/pressbooks-plugins-configure
```

Or, download the latest version from the releases page and unzip it into your WordPress plugin
directory): https://github.com/pressbooks/pressbooks-plugins-config/releases

Then, activate and configure the plugin at the Network level.

## How to use

If you want to change the behavior of any third party plugin you can inject your custom hooks inside of the `hooks()`
method int the `class-plugins-config.php`

For example

```php
<?php
public function hooks() {
// Hide redirect plugin if not super admin
	add_filter(
		'redirection_role', function() {
			return $this->minimumRole;
		}
	);
}
```

## Notes

+ This plugin is only visible for super admins

## Changelog

### 1.6.0

* See: https://github.com/pressbooks/pressbooks-plugins-config/releases/tag/1.6.0
* Full release history available at: https://github.com/pressbooks/pressbooks-plugins-config/releases
