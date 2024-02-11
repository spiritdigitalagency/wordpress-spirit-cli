# Spirit Digital WordPress CLI tools

> This WP-CLI plugin makes the process of managing WordPress sites much easier.

## Install

### Requirements

- PHP >= 7.4
- WP-CLI >= 2.9

### Via WP-CLI Package Manager (requires wp-cli >= 2.9)

Just run `wp package install spiritdigitalagency/wordpress-spirit-cli` or `wp package install git@github.com:spiritdigitalagency/wordpress-spirit-cli.git`.


### Installing as a plugin

Clone this repo onto `plugins/` folder, run `composer install` to fetch dependencies and activate the plugin.

## How it works

With a simple command you can export all wordfence login attempts

```
$ wp spirit wordfence logins --all --format=json
```

## Notes

This plugin is meant for internal use.
