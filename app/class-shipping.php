<?php

namespace uAfrica_Shipping\app;

/**
 * Class Shipping
 *
 * @package uAfrica_Shipping\app
 */
class Shipping extends \WC_Shipping_Method {

	/**
	 * Constructor for your shipping class
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		// phpcs:ignore WordPress.WP.I18n.NoHtmlWrappedStrings
		$description              = __( '<div>When <strong>Bob Go rates at checkout</strong> is enabled, your buyers will receive the rates that you have configured on Bob Go. For this to work you need an active <a href="https://www.bobgo.co.za" target="_new">Bob Go account</a>.</div>', 'uafrica-shipping' );
		$this->id                 = 'uafrica_shipping';
		$this->title              = __( 'Bob Go', 'uafrica-shipping' );
		$this->method_title       = __( 'Bob Go rates at checkout', 'uafrica-shipping' );
		$this->method_description = $description;
		$this->enabled            = 'yes'; // This can be added as an setting but for this example its forced enabled.
		$this->init();
	}

	/**
	 * Init your settings
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		// Load the settings API.
		$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings.
		$this->init_settings(); // This is part of the settings API. Loads settings you previously init.
		// Save settings in admin if you have any defined.
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Init the fields for the admin.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'       => array(
				'title'       => __( 'Enable', 'uafrica-shipping' ),
				'type'        => 'checkbox',
				'description' => __(
					'When this setting is enabled, your customers will be
				presented with shipping rates at checkout, as configured on the Bob Go platform under Rates at checkout',
					'uafrica-shipping'
				),
				'default'     => 'no',
			),
			'hide'          => array(
				'title'       => __( 'Hide WooCommerce Shipping Rates', 'uafrica-shipping' ),
				'type'        => 'checkbox',
				'description' => __( 'Hide other WooCommerce shipping rates if Bob Go returns rates.', 'uafrica-shipping' ),
				'default'     => 'yes',
			),
			'Test_API_Call' => array(
				'title'       => __( 'Makes a test call to the Bob Go API', 'uafrica-shipping' ),
				'type'        => 'custom_display',
				'description' => __( 'Makes a test call to the Bob Go API to ensure that the connection is set up properly.', 'uafrica-shipping' ),
				'value'       => 'Test API',
			),
			'Site_Address'          => array(
				'title'       => __( 'Use Site Address (URL) when requesting rates', 'uafrica-shipping' ),
				'type'        => 'checkbox',
				'description' => __( 'When this setting is enabled <strong>' . wp_parse_url( home_url(), PHP_URL_HOST ) .
					wp_parse_url( home_url(), PHP_URL_PATH ) .'</strong> will be used, instead of <strong>' . wp_parse_url( site_url(), PHP_URL_HOST ) .
					'</strong>, when making requests for shipping rates.<br><span style="color:#d97426;">Only enable this option if your WooCommerce store is accessed via a subfolder,
                     e.g. /shop.</span>', 'uafrica-shipping' ),
				'default'     => 'no',
			),
			'Additional_rate_info'  => array(
                'title'       => __( 'Show additional rate information', 'uafrica-shipping' ),
                'type'        => 'checkbox',
                'description' => __( 'Displays the delivery timeframe and additional service level description, as configured on Bob Go, below each shipping rate.', 'uafrica-shipping' ),
                'default'     => 'no',
            ),
		);
	}

	/**
	 * Generates an HTML template for displaying our custom API test call result
	 * that can be called through type custom_display
	 */
	public function generate_custom_display_html() {
		?>
		<div class="wrap">
			<div class="cmp-flex-tabs__content" style="">
				<?php $this->test_shipping(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Calculate the shipping costs of the uAfrica shipping.
	 * Might return multiple couriers with different rates.
	 *
	 * @param array $package The shopping cart details.
	 */
	public function calculate_shipping( $package = array() ) {
		$uafrica_shipping_settings = get_option( 'woocommerce_uafrica_shipping_settings' );
		// Is uAfrica shipping enabled?
		if ( empty( $uafrica_shipping_settings['enabled'] ) || 'no' === $uafrica_shipping_settings['enabled'] ) {
			return;
		}

		$url = UAFRICA_SHIPPING_API_SHIPPING_METHODS_V3;
		$post_options = [
			'headers' 	  => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'        => json_encode( $this->get_api_formatted_body( $package ) ),
			'redirection' => 0,
			'timeout'     => 12, // 12 seconds before timeout
		];
		$r = wp_remote_post( $url, $post_options );
		$response_code = (int)wp_remote_retrieve_response_code( $r );

		if ( $response_code === 200 ) {
			$this->process_rates_results( $r );
		}
	}

	/**
	 * Process results received from  V3.
	 *
	 * @param array|\WP_Error $response
	 *
	 * @return void
	 */
	protected function process_rates_results( $response ) {
		$rates = json_decode( wp_remote_retrieve_body( $response ) );
		if ( empty( $rates->rates ) ) {
			return; // There are no rates in the body.
		}
		$i = 1;
		foreach ( $rates->rates as $rate ) {
			$rate = [
				'id'    => "{$this->id}:{$i}",
				'label' => $rate->service_name,
				'cost'  => (string) ( $rate->total_price / 100 ),
				'meta_data' => array("uafrica_service_code" => $rate->service_code,
								"method_description" => $rate->description, "min_delivery_date" => $rate->min_delivery_date,
								"max_delivery_date" => $rate->max_delivery_date)
			];
			$this->add_rate( $rate );
			$i ++;
		}

	}

	/**
	 * Format package data into a valid format for API call.
	 *
	 * @param array $package The shopping cart details.
	 *
	 * @return array[]
	 */
	protected function get_api_formatted_body( array $package ): array {
		$formatted_data = array(
			'identifier' => Admin::get_api_domain(),
			'rate' => array(
				'origin'      => $this->format_origin(),
				'destination' => $this->format_destination( $package ),
				'currency'    => 'ZAR',
				'locale'      => substr( get_locale(), 0, 2 ),
			),
		);

		// Add products to the cart.
		if ( ! empty( $package['contents'] ) ) {
			$formatted_data['rate']['items'] = $this->format_cart_items( $package['contents'] );
		}

		return $formatted_data;
	}

	/**
	 * Format the destination for the API.
	 *
	 * @param array $package raw cart data.
	 *
	 * @return array
	 */
	protected function format_destination(array $package): array {
		$formatted_destination = [
			'country'      => $package['destination']['country'],
			'postal_code'  => $package['destination']['postcode'],
			'province'     => '',
			'city'         => $package['destination']['city'],
			'name'         => null,
			'address1'     => $package['destination']['address_1'],
			'address2'     => $package['destination']['address_2'],
			'address3'     => null,
			'phone'        => '',
			'fax'          => '',
			'email'        => null,
			'address_type' => null,
			'company_name' => '',
		];

		// Dynamically check if Checkout Blocks are used
		$is_checkout_blocks = isset( $package['destination']['is_checkout_blocks'] ) && $package['destination']['is_checkout_blocks'] === 'true';

		if ( $is_checkout_blocks ) {
			// Checkout Blocks Suburb
			$formatted_destination['cb_shipping_suburb'] = $package['destination']['cb_shipping_suburb'];
			$formatted_destination['is_checkout_blocks'] = 'true';
		} else {
			// Classic checkout suburb
			if ( isset( $package['destination']['shipping_suburb'] ) ) {
				$formatted_destination['shipping_suburb'] = $package['destination']['shipping_suburb'];
			}
		}

		// Provinces
		if ( ! empty( $package['destination']['state'] ) ) {
			$formatted_destination['province'] = $package['destination']['state'];
		}

		return $formatted_destination;
	}


	/**
	 * Format the origin for the API.
	 * The origin is where the store is located.
	 *
	 * @return array
	 */
	protected function format_origin(): array {
		$store_address   = get_option( 'woocommerce_store_address' );
		$store_address_2 = get_option( 'woocommerce_store_address_2' );
		$store_city      = get_option( 'woocommerce_store_city' );
		$store_postcode  = get_option( 'woocommerce_store_postcode' );

		// The country/state.
		$store_raw_country = get_option( 'woocommerce_default_country' );

		// Split the country/state.
		$split_country = explode( ':', $store_raw_country );

		$store_country  = ( ! empty( $split_country[0] ) ) ? $split_country[0] : '';
		$store_province = ( ! empty( $split_country[1] ) ) ? $split_country[1] : '';

		$formatted_origin = array(
			'country'      => $store_country,
			'postal_code'  => $store_postcode,
			'province'     => $store_province,
			'city'         => $store_city,
			'name'         => null,
			'address1'     => $store_address,
			'address2'     => $store_address_2,
			'address3'     => null,
			'phone'        => '',
			'fax'          => '',
			'email'        => null,
			'address_type' => null,
			'company_name' => '',
		);

		return $formatted_origin;
	}

	/**
	 * Format the items in the cart for the API.
	 *
	 * @param array $cart_items Raw cart items.
	 *
	 * @return array
	 */
	protected function format_cart_items( array $cart_items ): array {
		$formatted_cart_items = array();
		foreach ( $cart_items as $item ) {
			/**
			 * Holds the product.
			 *
			 * @var \WC_Product $wc_product
			 */
			$wc_product = $item['data'];

			$requires_shipping = (bool) $wc_product->needs_shipping();
			$taxable           = (bool) $wc_product->is_taxable();

			$formatted_item = array(
				'name'                => $wc_product->get_name(),
				'sku'                 => $wc_product->get_sku(),
				'quantity'            => (int) $item['quantity'],
				'price'               => (int) ( ( (int) $wc_product->get_price() ) * 100 ), // convert to cents.
				'vendor'              => '',
				'requires_shipping'   => $requires_shipping,
				'taxable'             => $taxable,
				'fulfillment_service' => 'manual',
				'properties'          => null,
				'product_id'          => $wc_product->get_id(),
				'grams'               => (int) wc_get_weight( $wc_product->get_weight(), 'g' ),
			);

			// Dimensions
			if ( !empty(trim($wc_product->get_height())) ) {
				$formatted_item['height'] = (int) wc_get_dimension( $wc_product->get_height(), 'cm' );
			}
			if ( !empty(trim($wc_product->get_length())) ) {
				$formatted_item['length'] = (int) wc_get_dimension( $wc_product->get_length(), 'cm' );
			}
			if ( !empty(trim($wc_product->get_width())) ) {
				$formatted_item['width'] = (int) wc_get_dimension( $wc_product->get_width(), 'cm' );
			}

			// If it has a parent it's a variation or configurable.
			if ( $wc_product->get_parent_id() ) {
				$formatted_item['product_id'] = $wc_product->get_parent_id();
				$formatted_item['variant_id'] = $wc_product->get_id();
			}

			// Save the product/variant shipping class info
			$formatted_item['shipping_class'] = $wc_product->get_shipping_class();
			$formatted_item['shipping_class_id'] = $wc_product->get_shipping_class_id();

			$formatted_cart_items[] = $formatted_item;
		}

		return $formatted_cart_items;
	}

	/**
	 * Run a test call to uAfrica shipping Rates API to see if the API can be reached and give a
	 * usefull error if it can't
	 */
	public function test_shipping() {
		$uafrica_shipping_settings = get_option( 'woocommerce_uafrica_shipping_settings' );
		// Is uAfrica shipping enabled?
		if ( empty( $uafrica_shipping_settings['enabled'] ) || 'no' === $uafrica_shipping_settings['enabled'] ) {
			return;
		}

		$url = UAFRICA_SHIPPING_API_SHIPPING_METHODS_V3;
		$post_options = [
			'headers' 	  => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'   	  => json_encode( $this->format_test_package() ),
			'redirection' => 0,
			'timeout'     => 12, // 12 seconds before timeout
		];
		$r = wp_remote_post( $url, $post_options );

		if(!is_wp_error( $r )){
            $this->print_test_result($r['response']['code']);
        }
		else {
			$this->print_test_error($r);
        }
	}

	/**
	 * @param array|\WP_Error $response
	 *
	 * @return void
	 */
	protected function print_test_result( $response ) {
		// Inspect the returned API data to see if rates are returned.
        switch ($response){
            case 200:
                echo "<div style='font-size: 1rem; color: green; margin-top:10px; border: solid 1px green; padding: 15px 12px; border-radius: 10px;'> ";
                echo '&check; Rates at checkout connected';
                echo '</div>';
                break;

            case 404:
                echo "<div style='font-size: 1rem; color: red; margin-top:10px; border: solid 1px red; padding: 15px 12px; border-radius: 10px;'> ";
                echo '&#10060; Your WooCommerce channel is not installed on Bob Go. Please visit ';
                echo '<a href="https://my.bobgo.co.za/sales-channels" target="_new">Bob Go</a>';
                echo ' and install your WooCommerce channel to be able to receive rates.';
                echo '</div>';
                break;

            case 401:
                echo "<div style='font-size: 1rem; color: red; margin-top:10px; border: solid 1px red; padding: 15px 12px; border-radius: 10px;'> ";
                echo '&#10060; Rates at checkout is not enabled for your channel on Bob Go. Please visit ';
                echo '<a href="https://my.bobgo.co.za/rates-at-checkout?tab=settings" target="_new">Bob Go</a>';
                echo ' and enable your WooCommerce channel to be able to receive rates.';
                echo '</div>';
                break;

            default:
                echo "<div style='color: red; margin-top:10px'> ";
                echo '&#10060; Failed to connect to rates at checkout. Please check your internet connection and make sure Rates at checkout is enabled for your channel on Bob Go. Please visit ';
                echo '<a href="https://my.bobgo.co.za/rates-at-checkout?tab=settings" target="_new">Bob Go</a>';
                echo ' and make sure your WooCommerce channel is enabled to receive rates.';
				echo '</div>';
        }
	}

	/**
	 * @param array|\WP_Error $response
	 *
	 * @return void
	 */
	protected function print_test_error( $response ) {
		echo "<div style='color: red; margin-top:10px'> ";
		echo 'Failed to connect to rates at checkout';
		echo $response->get_error_message(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}

	/**
	 * Format test package data into a valid format for API call.
	 *
	 * @return array[]
	 */
	protected function format_test_package() {
		return array(
			'identifier' => Admin::get_api_domain(),
			'rate' => array(
				'origin'      => array(
					'country'      => 'ZA',
					'postal_code'  => '0081',
					'province'     => 'GT',
					'city'         => 'Pretoria',
					'name'         => null,
					'address1'     => '36 Marelu Street',
					'address2'     => 'Six Fountains Estate',
					'address3'     => null,
					'phone'        => '0836574497',
					'fax'          => '',
					'email'        => null,
					'address_type' => null,
					'company_name' => 'Jamie Ds Emporium',
				),
				'destination' => array(
					'country'      => 'ZA',
					'postal_code'  => '0081',
					'province'     => 'GT',
					'city'         => 'Pretoria',
					'name'         => null,
					'address1'     => '36 Marelu Street',
					'address2'     => 'Six Fountains Estate',
					'address3'     => null,
					'phone'        => '0836574497',
					'fax'          => '',
					'email'        => null,
					'address_type' => null,
					'company_name' => 'Jamie Ds Emporium',
				),
				'items'       => array(
					'0' => array(
						'name'                => 'Short Sleeve T - Shirt',
						'sku'                 => null,
						'quantity'            => 1,
						'price'               => 19990,
						'vendor'              => 'Jamie Ds Emporium',
						'requires_shipping'   => true,
						'taxable'             => true,
						'fulfillment_service' => 'manual',
						'properties'          => null,
						'product_id'          => '48447225880',
						'variant_id'          => '258644705304',
						'grams'				  => 1000,
					),
				),

				'currency'    => 'ZAR',
				'locale'      => 'en',
			),
		);
	}

}
