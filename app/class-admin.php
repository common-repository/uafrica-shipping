<?php

namespace uAfrica_Shipping\app;

/**
 * Class Settings
 *
 * @package uAfrica_Shipping\app
 */
class Admin {

	/**
	 * Registers the menu item in the WordPress admin.
	 */
	public static function register_menu_item() {
		add_submenu_page(
			'options-general.php',
			'Bob Go',
			'Bob Go',
			'manage_options',
			'uafrica-shipping',
			function () {
				require_once UAFRICA_SHIPPING_TEMPLATES_DIR . 'settings-page.php';
			}
		);
	}

	/**
	 * Register settings to save.
	 */
	public static function register_settings() {
		$empty_function = function () {
		};

		// register the global setting.
		register_setting(
			'uafrica-settings',
			'uafrica',
			array( 'sanitize_callback' => array( self::class, 'sanitize_settings' ) )
		);
		add_settings_section(
			'page-section',
			'', // empty section title.
			$empty_function,
			'uafrica-shipping'
		);

		add_settings_field(
			'uafrica_shipping_page',
			__( 'Shipping page', 'uafrica-shipping' ),
			array( self::class, 'render_shippings_page' ),
			'uafrica-shipping',
			'page-section'
		);

		add_settings_field(
			'uafrica_suburb_at_checkout',
			'Show suburb field at checkout',
			[ self::class, 'render_suburb_checkbox' ],
			'uafrica-shipping',
			'page-section'
		);
	}

	/**
	 * Render the suburb_at_checkout checkbox.
	 */
	public static function render_suburb_checkbox() {
		$option = get_option( 'uafrica' );

		$current_val = $option['suburb_at_checkout'] ?? 1;

		$checked = checked( 1, $current_val, false );
		$html    = '<input type="checkbox" name="uafrica[suburb_at_checkout]" value="1" id="suburb_checkbox" ' . $checked . '/>';
		echo $html;
	}

	/**
	 * Render the shipping page setting.
	 */
	public static function render_shippings_page() {
		$option      = get_option( 'uafrica' );
		$current_val = 0;
		if ( ! empty( $option['shipping_page'] ) ) {
			$current_val = $option['shipping_page'];
		}

		// phpcs:disable WordPress.Security.EscapeOutput
		wp_dropdown_pages(
			array(
				'name'              => 'uafrica[shipping_page]',
				// phpcs:ignore WordPress.WP.I18n
				'show_option_none'  => __( '&mdash; Select &mdash;' ),
				'option_none_value' => '0',
				'selected'          => $current_val,
			)
		);
		// phpcs:enable WordPress.Security.EscapeOutput
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param array $settings array of settings given by the settings API.
	 *
	 * @return array
	 */
	public static function sanitize_settings( array $settings ): array {
		if ( ! empty( $settings['shipping_page'] ) ) {
			$possible_page = get_post( $settings['shipping_page'] );
			if ( is_null( $possible_page ) || 'page' !== $possible_page->post_type ) {
				$settings['shipping_page'] = 0;
			} else {
				$settings['shipping_page'] = (int) $possible_page->ID; // Cast to integer.
			}
		} // ifend; save shipping_page.

		if ( ! isset( $settings['suburb_at_checkout'] ) ) {
			$settings['suburb_at_checkout'] = 0;
		}

		return $settings;
	}

	/**
	 * Add a settings link to the the plugin on the plugin page
	 *
	 * @param array $links An array of plugin action links.
	 *
	 * @return array
	 */
	public static function add_plugin_page_settings_link( array $links ): array {
		// Shipping settings.
		if ( function_exists( '\WC' ) ) {
			// @see \uAfrica_Shipping\app\Shipping::$id hardcoded uafrica_shipping phpcs:ignore Squiz.PHP.CommentedOutCode.Found
			$href          = admin_url( 'admin.php?page=wc-settings&tab=shipping&section=uafrica_shipping' );
			$settings_link = '<a href="' . $href . '">' . __( 'Shipping' ) . '</a>'; // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			array_unshift( $links, $settings_link );
		}

		// General settings.
		$href          = admin_url( 'options-general.php?page=uafrica-shipping' );
		$settings_link = '<a href="' . $href . '">' . __( 'Settings' ) . '</a>'; // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * On the post overview page label the shipping page as such.
	 *
	 * @param array $post_states current poststates this page might have.
	 * @param \WP_Post|null $post current post.
	 *
	 * @return array
	 */
	public static function label_page_as_shipping( array $post_states, \WP_Post $post = null ): array {
		$option = get_option( 'uafrica' );
		if ( ! empty( $post->ID ) && ! empty( $option['shipping_page'] ) && $post->ID === $option['shipping_page'] ) {
			$post_states['uafrica_shipping_page'] = __( 'Bob Go page', 'uafrica-shipping' );
		}

		return $post_states;
	}

	/**
	 * Add a new column to the order overview.
	 *
	 * @param array $order_columns array of current admin column names.
	 *
	 * @return array
	 */
	public static function order_column_name( array $order_columns ): array {
		$order_columns['uafrica_shipping'] = _x( 'Track Order', 'column name', 'uafrica-shipping' );

		return $order_columns;
	}

	/**
	 * Display the shipping link.
	 *
	 * @param string $column_name the column name.
	 * @param WC_Order|int $order_or_order_id
	 */
	public static function order_column_content( string $column_name, $order_or_order_id ) {
		if ( 'uafrica_shipping' !== $column_name ) {
			return; // not the uAfrica column, no continue.
		}

		$option = get_option( 'uafrica' );
		if ( empty( $option['shipping_page'] ) ) {
			return; // no shipping page set, no continue.
		}

		$page_link = get_permalink( $option['shipping_page'] );

		$order_number = "";

		// Legacy CPT-based order compatibility
		$order = $order_or_order_id instanceof WC_Order ? $order_or_order_id : wc_get_order( $order_or_order_id );
		if ( $order ) {
			// Use the order number for the tracking link
			$order_number = $order->get_order_number();
		}

		$full_link = $page_link;
		if ( ! empty( $order_number ) ) {
			$full_link = add_query_arg( array( 'order-number' => $order_number ), $page_link );
		}

		//phpcs:ignore  WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "<a href='{$full_link}'>" . _x( 'Track order', 'link in woo admin', 'uafrica-shipping' ) . '</a>';
	}

	/**
	 * Register the new shipping method.
	 *
	 * @param array $methods list of already available methods.
	 *
	 * @return array mixed
	 */
	public static function register_shipping_method( array $methods ): array {

		$methods['your_shipping_method'] = '\uAfrica_Shipping\app\Shipping';

		return $methods;
	}

	/**
	 * Check the rates, if at least one uAfrica shipping method is available only return those.
	 *
	 * @param array $rates Current rates.
	 *
	 * @return array
	 */
	public static function filter_shipping_methods( array $rates ): array {
		if ( ! isset( $rates['uafrica_shipping:1'] ) ) {
			return $rates; // No uAfrica rate is set, ignore.
		}
		// Converts uAfrica Shipping rates from cents to Rands.
		foreach ( $rates as $key => $value ) {
			$key_parts = explode( ':', $key )[0];
			if ( 'uafrica_shipping' === $key_parts ) {
				$rates[$key]->cost = $rates[$key]->cost;
			}
		}
		$uafrica_shipping_settings = get_option( 'woocommerce_uafrica_shipping_settings' );
		// Removes non uAfrica rates if hidden is enabled.
		if ( ! empty( $uafrica_shipping_settings['hide'] ) && 'yes' === $uafrica_shipping_settings['hide'] ) {
			foreach ( $rates as $key => $value ) {
				$key_parts = explode( ':', $key )[0];
				if ( 'uafrica_shipping' !== $key_parts ) {
					unset( $rates[$key] );
				}
			}
		}

		return $rates;
	}

	/**
	 * Get a formatted url for API calls.
	 *
	 * @return string
	 */
	public static function get_api_domain() {
		$uafrica_shipping_settings = get_option( 'woocommerce_uafrica_shipping_settings' );
		if ( ! empty( $uafrica_shipping_settings['Site_Address'] ) && $uafrica_shipping_settings['Site_Address'] === 'yes' ) {
			// home_url() refers to Site Address (URL) as configured via Setting->General
			$domain = wp_parse_url( home_url(), PHP_URL_HOST ) . wp_parse_url( home_url(), PHP_URL_PATH );
		} else {
			// site_url() refers to Wordpress Address (URL) as configured via Setting->General
			$domain = wp_parse_url( site_url(), PHP_URL_HOST );
		}

		/**
		 * Filters the domain.
		 *
		 * @param string $domain the domain to be checked against.
		 *
		 * @since 0.3.0
		 */
		return apply_filters( 'uafrica_shipping_domain', $domain );
	}
}
