<?php

class ConfigCommand extends SpiritCliBase {

	/**
	 * Replace all site urls with http to https
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function ssl( $args = array(), $assoc_args = array() ) {
		$urlparts = wp_parse_url( home_url() );
		$host     = $urlparts['host'];

		$replacement_rules = [
			"http://$host"       => "https://$host",
			"http:\/\/$host"     => "https:\/\/$host",
			"http%3A%2F%2F$host" => "https%3A%2F%2F$host",
		];
		foreach ( $replacement_rules as $needle => $replace ) {
			$this->wp( "search-replace '$needle' '$replace' --all-tables --skip-columns=guid" );
		}
		if ( is_plugin_active( 'elementor/elementor.php' ) ) {
			foreach ( $replacement_rules as $needle => $replace ) {
				$this->wp( "elementor replace_urls '$needle' '$replace'" );
			}
			$this->wp( "elementor flush_css" );
		}
		WP_CLI::success( 'HTTPS applied.' );
	}

	/**
	 * Make sure admin user exists
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function user( $args = array(), $assoc_args = array() ) {
		$usernames = [ 'dev', 'spirit', 'spiritdigital' ];
		$emails    = [ 'webmaster@spiritdigital.agency', 'webmaster@spirit.com.gr' ];

		$user_id = 0;
		foreach ( $usernames as $username ) {
			$user = get_user_by( 'login', $username );
			if ( ! empty( $user ) ) {
				$user_id = $user->ID;
				break;
			}
		}
		foreach ( $emails as $email ) {
			$user = get_user_by( 'email', $email );
			if ( ! empty( $user ) ) {
				$user_id = $user->ID;
				break;
			}
		}

		$userdata = array(
			'ID'            => $user_id,
			'user_login'    => 'spiritdigital',
			'user_nicename' => 'spiritdigital',
			'user_url'      => 'https://spiritdigital.agency',
			'user_email'    => 'webmaster@spiritdigital.agency',
			'display_name'  => 'Spirit Digital',
			'nickname'      => 'spiritdigital',
			'first_name'    => 'Spirit',
			'last_name'     => 'Digital',
			'role'          => 'administrator',
			'locale'        => 'en_US'
		);
		$user_id  = wp_insert_user( $userdata );

		if ( ! is_wp_error( $user_id ) ) {
			WP_CLI::success( 'User updated.' );
		} else {
			WP_CLI::warning( 'Something went wrong.' );
		}
	}

	public function wordpress( $args = array(), $assoc_args = array() ) {
		$options = [
			'timezone_string'              => "Europe/Athens",
			'date_format'                  => "d/m/Y",
			'time_format'                  => "H:i",
			'start_of_week'                => 1,
			'default_pingback_flag'        => "0",
			'default_ping_status'          => "0",
			'default_comment_status'       => "0",
			'require_name_email'           => "1",
			'comment_registration'         => "1",
			'close_comments_for_old_posts' => "0",
			'thread_comments'              => "0",
			'page_comments'                => "0",
			'comments_notify'              => "0",
			'moderation_notify'            => "0",
			'comment_moderation'           => "1",
			'comment_previously_approved'  => "1",
			'show_avatars'                 => "0",
		];
		foreach ( $options as $key => $value ) {
			$this->wp( "option update $key '$value'" );
		}
		$this->wp( "language core update" );
		$this->wp( "language core install el" );
		$this->wp( "site switch-language el" );

		$this->wp( "rewrite structure '/%postname%/'" );
		$this->wp( "spirit police approve" );
	}

	/**
	 * Initial setup greek woocommerce store
	 *
	 * @synopsis [--user=<user>]
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function woocommerce( $args = array(), $assoc_args = array() ) {
		$this->process_args(
			array(),
			$args,
			array(
				'user' => 0
			),
			$assoc_args
		);
		if ( empty( get_current_user_id() ) ) {
			WP_CLI::error( 'You need to define a --user.' );

			return;
		}
		$options = [
			'woocommerce_currency'                         => 'EUR',
			'woocommerce_currency_pos'                     => 'right',
			'woocommerce_weight_unit'                      => 'kg',
			'woocommerce_dimension_unit'                   => 'cm',
			'woocommerce_calc_taxes'                       => 'yes',
			'woocommerce_prices_include_tax'               => 'yes',
			'woocommerce_tax_display_shop'                 => 'incl',
			'woocommerce_tax_display_cart'                 => 'incl',
			'woocommerce_enable_coupons'                   => 'yes',
			'woocommerce_manage_stock'                     => 'yes',
			'woocommerce_hold_stock_minutes'               => '',
			'woocommerce_notify_low_stock'                 => 'no',
			'woocommerce_notify_no_stock'                  => 'no',
			'woocommerce_hide_out_of_stock_items'          => 'no',
			'woocommerce_stock_format'                     => 'no_amount',
			'woocommerce_enable_reviews'                   => 'no',
			'woocommerce_enable_review_rating'             => 'no',
			'woocommerce_show_marketplace_suggestions'     => 'no',
			'woocommerce_allow_tracking'                   => 'no',
			'woocommerce_analytics_enabled'                => 'no',
			'woocommerce_email_footer_text'                => '{site_title}',
			'woocommerce_force_ssl_checkout'               => 'yes',
			'woocommerce_myaccount_view_order_endpoint'    => 'order',
			'woocommerce_myaccount_edit_address_endpoint'  => 'address',
			'woocommerce_myaccount_edit_account_endpoint'  => 'edit',
			'woocommerce_myaccount_downloads_endpoint'     => '',
			'woocommerce_myaccount_logout_endpoint'        => 'logout',
			'woocommerce_registration_privacy_policy_text' => 'Τα προσωπικά σας δεδομένα θα χρησιμοποιηθούν για την υποστήριξη της εμπειρίας σας σε αυτόν τον ιστότοπο, για τη διαχείριση της πρόσβασης στον λογαριασμό σας και για άλλους σκοπούς που περιγράφονται στην [privacy_policy] μας.',
			'woocommerce_checkout_privacy_policy_text'     => 'Τα προσωπικά σας δεδομένα θα χρησιμοποιηθούν για την επεξεργασία της παραγγελίας σας, την υποστήριξη της εμπειρίας σας σε αυτόν τον ιστότοπο και για άλλους σκοπούς που περιγράφονται στην [privacy_policy] μας.',
			'woocommerce_permalink_category_base'          => 'c',
			'woocommerce_permalink_product_tag_base'       => 't',
			'woocommerce_permalink_product_base'           => 'p',
			'woocommerce_run_wizard'                       => '1',
		];
//		$this->wp( 'language plugin install woocommerce el' );
		foreach ( $options as $key => $value ) {
//			$this->wp( "option update $key '$value'" );
		}

		$taxes = $this->wp( "wc tax list --field=rate --format=json" );
		if ( ! in_array( 24, $taxes ) ) {
			$this->wp( 'wc tax create --country=GR --city=* --postcode=* --rate=24.0000 --name=ΦΠΑ-24% --priority=1 --compound=0 --shipping=1' );
		}
		if ( ! in_array( 13, $taxes ) ) {
			$this->wp( 'wc tax create --country=GR --city=* --postcode=* --rate=13.0000 --name=ΦΠΑ-13% --class=reduced-rate --priority=1 --compound=0 --shipping=0' );
		}
		if ( ! in_array( 6, $taxes ) ) {
			$this->wp( 'wc tax create --country=GR --city=* --postcode=* --rate=6.0000 --name=ΦΠΑ-6% --class=reduced-rate --priority=1 --compound=0 --shipping=0' );
		}
		if ( ! in_array( 0, $taxes ) ) {
			$this->wp( 'wc tax create --country=GR --city=* --postcode=* --rate=0.0000 --name=ΦΠΑ-0% --class=zero-rate --priority=1 --compound=0 --shipping=0' );
		}

		$zone_id = 0;
		$zones   = $this->wp( "wc shipping_zone list --fields=name,id --format=json" );
		foreach ( $zones as $zone ) {
			if ( $zone['name'] == 'Greece' ) {
				$zone_id = $zone['id'];
			}
		}
		if ( empty( $zone_id ) && count( $zones ) < 2 ) {
			$zone_id = $this->wp( 'wc shipping_zone create --name=Greece --porcelain' );
		}
		$shipping_methods = $this->wp( "wc shipping_zone_method list $zone_id --field=method_id --format=json" );
		if ( ! in_array( 'flat_rate', $shipping_methods ) ) {
			$settings = [
//				'title' => 'Αποστολή με courier',
				'title'      => 'Courier',
				'tax_status' => 'taxable',
				'cost'       => '4'
			];
			$param    = str_replace( '"', '"', json_encode( $settings ) );
			$this->wp( 'wc shipping_zone_method create ' . $zone_id . ' --method_id=flat_rate --enabled=1 --settings=' . $param . '' );
		}
//		if (!in_array('local_pickup', $shipping_methods)){
//			$settings = [
////				'title' => 'Αποστολή με courier',
//				'title' => 'Παραλαβή',
//				'tax_status' => 'taxable',
//				'cost' => '0'
//			];
//			$param = str_replace('"','"', json_encode($settings));
//			$this->wp( 'wc shipping_zone_method create ' . $zone_id . ' --method_id=local_pickup --enabled=1 --settings=' . $param . '' );
//		}

//		$this->wp( 'wc payment_gateway update bacs --enabled=1 --settings=' . str_replace('"','\"', json_encode([
//				'title' => 'Κατάθεση\\ σε\\ λογαριασμό',
////				'description' => 'Πραγματοποιήστε την πληρωμή σας με τραπεζική κατάθεση σε λογαριασμό της εταιρείας.',
//				'instructions' => '',
//			])) . '' );
		$this->wp( 'rewrite flush' );
	}
}

WP_CLI::add_command( 'spirit config', ConfigCommand::class );
