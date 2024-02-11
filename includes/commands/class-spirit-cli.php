<?php

class SpiritCli extends \WP_CLI_Command {

	/**
	 * Displays General Info about Spirit CLI and WordPress
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function __invoke( $args, $assoc_args ) {
		WP_CLI::line( 'Spirit CLI version: %Yv' . SDA_CLI_VERSION . '%n' );
		WP_CLI::line();
		WP_CLI::line( 'Created by Evangelos Pallis at Spirit Digital' );
		WP_CLI::line( 'Github: https://github.com/spiritdigitalagency/wp-spirit-cli' );
		WP_CLI::line();
	}
}

WP_CLI::add_command( 'spirit info', SpiritCli::class );
