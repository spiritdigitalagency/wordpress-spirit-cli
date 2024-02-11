<?php

class WordfenceCommand extends SpiritCliBase {

	/**
	 * Retrieve wordfence failed login attempts data.
	 *
	 * @synopsis [--all]
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function logins( $args = array(), $assoc_args = array() ) {
		$this->process_args(
			array(),
			$args,
			array(
				'all' => null,
				'format' => 'table',
			),
			$assoc_args
		);

		global $wpdb;
		if ( isset( $this->assoc_args['all'] ) ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT inet6_ntoa(IP) as IP, UA, username, FROM_UNIXTIME(ctime) as at FROM {$wpdb->prefix}_wflogins"
				)
			);
		} else {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT inet6_ntoa(IP) as IP, UA, username, FROM_UNIXTIME(ctime) as at FROM {$wpdb->prefix}_wflogins WHERE fail = 1"
				)
			);
		}

		WP_CLI\Utils\format_items(
			$this->assoc_args['format'],
			$results,
			[ 'IP', 'UA', 'username', 'at' ]
		);
	}

}

WP_CLI::add_command( 'spirit wordfence', WordfenceCommand::class );
