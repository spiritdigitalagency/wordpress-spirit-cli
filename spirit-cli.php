<?php
/**
 * Plugin Name: Spirit Digital Blocks
 * Plugin URI: https://github.com/spiritdigitalagency/wp-spirit-cli/
 * Description: Getting things done!
 * Version: 0.1.0
 * Author: Spirit Digital
 * Author URI: https://spiritdigital.agency/
 * Text Domain: sda-wp-cli
 * Domain Path: /languages
 *
 * @package spiritdigitalagency/wp-spirit-cli
 */
if ( ! defined( 'SDA_CLI_VERSION' ) ) {
	define( 'SDA_CLI_VERSION', '0.1.0' );
	define( 'SDA_CLI_COMMANDS_PATH', 'includes/commands/' );
}

// Only load this plugin once and bail if WP CLI is not present
if (  ! defined( 'WP_CLI' ) ) {
	return;
}

if ( version_compare( PHP_VERSION, '7.4.0' ) < 0 ) {
	WP_CLI::error( 'Spirit Digital CLI tools requires PHP >= 7.4' );
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once( 'vendor/autoload.php' );
}

require_once( SDA_CLI_COMMANDS_PATH . 'class-spirit-cli.php' );
require_once( SDA_CLI_COMMANDS_PATH . 'class-spirit-cli-base.php' );
require_once( SDA_CLI_COMMANDS_PATH . 'class-spirit-cli-wordfence.php' );
