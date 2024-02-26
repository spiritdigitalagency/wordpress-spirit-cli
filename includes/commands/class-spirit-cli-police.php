<?php

class PoliceCommand extends SpiritCliBase {

	/**
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function info( $args = array(), $assoc_args = array() ) {
//		$this->wp( "core verify-checksums --include-root" );
		$this->wp( "plugin verify-checksums --strict --all --format=json" );

		$checks = [
			[ '.htaccess', ABSPATH ],
			[ '*.{zip,bz,bz2,tar,sql}', ABSPATH ],
			[ '*.php', WP_CONTENT_DIR . '/languages' ],
			[ '*.php', WP_CONTENT_DIR . '/cache' ],
			[ '*.oti', WP_CONTENT_DIR . '/uploads' ],
		];
		foreach ( $checks as $check ) {
			WP_CLI::line( "Check for {{$check[0]}} @ " . basename( $check[1] ) );
			$files = self::find( $check[0], $check[1] );
			if ( ! empty( $files ) ) {
				WP_CLI::line( implode( "\n", $files ) );
			}
		}
	}

	public function arrest() {
		$version = $this->wp( "core version" );
//		$this->wp( "core download --force --skip-content --version=$version" );
		$plugins = $this->wp( "plugin list --field=name --format=json" );
		foreach ( $plugins as $plugin ) {
			$version = $this->wp( "plugin get $plugin --field=version --format=json" );
			if ( false !== $this->wp( "plugin verify-checksums $plugin --strict --version=$version --format=json" ) ) {
				$this->wp( "plugin install $plugin --force --version=$version" );
			}
		}
		$default_role = $this->wp( "option get default_role" );
		if ( $default_role == 'administrator' || $default_role == 'shop_manager' ) {
			$role_waterfall = [ 'customer', 'subscriber' ];
			foreach ( $role_waterfall as $role ) {
				$role_exists = $this->wp( "role exists $role" );
				if ( str_contains( $role_exists, 'Success: ' ) ) {
					$this->wp( "option update default_role $role" );
					break;
				}
			}
		}
		$this->approve();
		$this->wp( "rewrite flush" );
	}

	public function approve() {
		$this->wp( 'config set DISALLOW_FILE_EDIT true --raw' );
		$this->wp( 'config set FORCE_SSL_ADMIN true --raw' );
		$this->wp( 'option update default_pingback_flag "0"' );
		$this->wp( 'option update default_ping_status "0"' );

		$this->wp( 'option update default_comment_status "0"' );
		$this->wp( 'option update require_name_email "1"' );
		$this->wp( 'option update comment_registration "1"' );
		$this->wp( 'option update close_comments_for_old_posts "0"' );
		$this->wp( 'option update thread_comments "0"' );
		$this->wp( 'option update page_comments "0"' );
		$this->wp( 'option update comments_notify "0"' );
		$this->wp( 'option update moderation_notify "0"' );
		$this->wp( 'option update comment_moderation "1"' );
		$this->wp( 'option update comment_previously_approved "1"' );
		$this->wp( 'option update show_avatars "0"' );

		$files = array(
			'readme.html',
			'wp-config-sample.php',
		);
		foreach ( $files as $file ) {
			if ( @unlink( ABSPATH . '/' . $file ) !== false ) {
				WP_CLI::success( "removed $file" );
			}
		}
		$this->wp( "theme delete --all" );
		$inactive_plugins = $this->wp( "plugin list --status=inactive --field=name --format=json" );
		foreach ( $inactive_plugins as $inactive_plugin ) {
			$this->wp( "wp plugin delete $inactive_plugin" );
		}

		if ( ! self::isNginxServer() && ! self::isIISServer() ) {
			$directories = array(
				ABSPATH . '/wp-includes',
				WP_CONTENT_DIR,
				WP_CONTENT_DIR . '/uploads'
			);
			foreach ( $directories as $directory ) {
				if ( ! is_dir( $directory ) || ! is_writable( $directory ) ) {
					WP_CLI::warning( "Cannot cover $directory" );
					continue;
				}
				$folder  = str_replace( get_home_path(), '', $directory );
				$bpath   = rtrim( get_home_path(), DIRECTORY_SEPARATOR );
				$target  = $bpath . '/' . $folder . '/.htaccess';
				$fhandle = @fopen( $target, 'w' );

				if ( ! $fhandle ) {
					return;
				}

				$deny_rules = array(
					'<FilesMatch "\.(?i:php)$">',
					'  <IfModule !mod_authz_core.c>',
					'    Order allow,deny',
					'    Deny from all',
					'  </IfModule>',
					'  <IfModule mod_authz_core.c>',
					'    Require all denied',
					'  </IfModule>',
					'</FilesMatch>',
				);
				$rules_text = implode( "\n", $deny_rules );
				$written    = @fwrite( $fhandle, "\n" . $rules_text . "\n" );
				@fclose( $fhandle );

				if ( $written !== false ) {
					WP_CLI::success( "Harden directory $directory" );
				}
			}
		}
	}

}

WP_CLI::add_command( 'spirit police', PoliceCommand::class );
