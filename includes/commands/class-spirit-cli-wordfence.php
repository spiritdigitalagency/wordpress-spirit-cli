<?php

class WordfenceCommand extends SpiritCliBase {

	/**
	 * Retrieve wordfence failed login attempts data.
	 *
	 * @synopsis [--all] [--format=<format>]
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function logins( $args = array(), $assoc_args = array() ) {
		$this->process_args(
			array(),
			$args,
			array(
				'all'    => null,
				'fields' => array( 'IP', 'UA', 'username', 'at' ),
				'format' => 'table',
			),
			$assoc_args
		);

		global $wpdb;
		if ( isset( $this->assoc_args['all'] ) ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT SUBSTRING_INDEX(inet6_ntoa(IP),'::ffff:',-1) as IP, UA, username, FROM_UNIXTIME(ctime) as at FROM {$wpdb->prefix}wflogins"
				)
			);
		} else {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT SUBSTRING_INDEX(inet6_ntoa(IP),'::ffff:',-1) as IP, UA, username, FROM_UNIXTIME(ctime) as at FROM {$wpdb->prefix}wflogins WHERE fail = 1 AND FROM_UNIXTIME(ctime) >= NOW() - INTERVAL 7 DAY;"
				)
			);
		}

		WP_CLI\Utils\format_items(
			$this->assoc_args['format'],
			$results,
			$this->assoc_args['fields']
		);
	}

	public function config() {
		if ( ! class_exists( "wfConfig" ) ) {
			return;
		}
		wfConfig::set( "alertEmails", "" );
		wfConfig::set( "email_summary_enabled", false );
		wfConfig::set( "email_summary_dashboard_widget_enabled", false );
		wfConfig::set( "ssl_verify", true );
		wfConfig::set( "startScansRemotely", false );
		wfConfig::set( "autoUpdate", true );
		wfConfig::set( "addCacheComment", false );
		wfConfig::set( "displayTopLevelOptions", true );
		wfConfig::set( "displayTopLevelBlocking", true );

		wfConfig::set( "other_pwStrengthOnUpdate", true );
		wfConfig::set( "other_scanComments", true );
		wfConfig::set( "other_hideWPVersion", true );
		wfConfig::set( "other_blockBadPOST", true );
		wfConfig::set( "loginSec_maskLoginErrors", true );
		wfConfig::set( "loginSec_disableAuthorScan", true );
		wfConfig::set( "loginSec_disableOEmbedAuthor", true );
		wfConfig::set( "loginSec_strongPasswds_enabled", true );

		wfConfig::set( "notification_updatesNeeded", false );
		wfConfig::set( "notification_promotions", false );
		wfConfig::set( "notification_blogHighlights", false );
		wfConfig::set( "notification_productUpdates", false );
		wfConfig::set( "notification_scanStatus", false );
		wfConfig::set( "alertOn_update", false );
		wfConfig::set( "alertOn_scanIssues", false );
		wfConfig::set( "alertOn_loginLockout", false );
		wfConfig::set( "alertOn_adminLogin", false );
		wfConfig::set( "alertOn_block", false );
		wfConfig::set( "alertOn_lostPasswdForm", false );

		wfConfig::set( "loginSec_lockInvalidUsers", false );
		wfConfig::set( "loginSec_lockoutMins", 2880 );
		wfConfig::set( "loginSec_maxFailures", "3" );
		wfConfig::set( "loginSec_maxForgotPasswd", "3" );
		wfConfig::set( "loginSec_strongPasswds", "pubs" );
		wfConfig::set( "liveTrafficEnabled", false );
		wfConfig::set( "liveTraf_maxRows", 1000 );
		wfConfig::set( "liveTraf_maxAge", 15 );

		wfConfig::set( "lowResourceScansEnabled", true );
		wfConfig::set( "scanType", "custom" );
		wfConfig::set( "scansEnabled_checkReadableConfig", true );
		wfConfig::set( "scansEnabled_comments", true );
		wfConfig::set( "scansEnabled_core", true );
		wfConfig::set( "scansEnabled_fileContents", true );
		wfConfig::set( "scansEnabled_malware", true );
		wfConfig::set( "scansEnabled_oldVersions", true );
		wfConfig::set( "scansEnabled_options", true );
		wfConfig::set( "scansEnabled_passwds", true );
		wfConfig::set( "scansEnabled_posts", true );
		wfConfig::set( "scansEnabled_scanImages", true );
		wfConfig::set( "scansEnabled_suspectedFiles", true );
		wfConfig::set( "scansEnabled_suspiciousAdminUsers", true );
		wfConfig::set( "scansEnabled_suspiciousOptions", true );
		wfConfig::set( "scansEnabled_themes", true );
		wfConfig::set( "scansEnabled_plugins", true );
		wfConfig::set( "scansEnabled_wafStatus", true );
		wfConfig::set( "scansEnabled_wpscan_fullPathDisclosure", true );
		wfConfig::set( "scansEnabled_wpscan_directoryListingEnabled", true );
	}

}

WP_CLI::add_command( 'spirit wordfence', WordfenceCommand::class );
