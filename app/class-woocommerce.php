<?php

namespace uAfrica_Shipping\app;

class WooCommerce {

	/**
	 * Save order meta
	 *
	 * @param int $order_id ID of the order.
	 * @param array|\WC_Order|null Posted order data, or the existing order.
	 *
	 * @return void
	 */
	public static function save_order_meta( $order_id, $order = null ) {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		// Get order if not sent along to hook (Happens when hook fires too fast)
		$order = is_a( $order, 'WC_Order' ) ? $order : wc_get_order( $order_id );
		if ( empty( $order ) || $order === false ) {
			return;
		}

		// To prevent infinite loops, we need to remove the action and add it again after saving the order meta
		remove_action('woocommerce_update_order', [ '\uAfrica_Shipping\app\WooCommerce', 'save_order_meta' ], 10);

		// Total grams
		self::include_total_grams_in_order($order_id, $order);

		// Service code
		self::include_service_code_in_order($order_id, $order);

		// Items meta
		self::include_items_meta($order_id, $order);

		// Plugin version
		self::save_plugin_version($order_id, $order);

		// Save the order meta
		$order->save();
		// We need to add the action again after saving the order meta
		add_action( 'woocommerce_update_order', [ '\uAfrica_Shipping\app\WooCommerce', 'save_order_meta' ], 10, 2 );
	}

	/**
	 * Save order meta during checkout
	 *
	 * @param int $order_id ID of the order.
	 * @param array|\WC_Order|null Posted order data, or the existing order.
	 *
	 * @return void
	 */
	public static function save_order_meta_checkout( $order_id, $order = null ) {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		// Get order if not sent along to hook (Happens when hook fires too fast)
		$order = is_a( $order, 'WC_Order' ) ? $order : wc_get_order( $order_id );

		// Total grams
		self::include_total_grams_in_order($order_id, $order);

		// Save meta
		if ( ! empty( $order ) && $order !== false ) {
			$order->save();
		}
	}

	/**
	 * Save the total weight of the items in the order as part of order meta so that it is included in order webhooks.
	 *
	 * @param int $order_id ID of the order.
	 * @param array|\WC_Order|null Posted order data, or the existing order.
	 *
	 * @return void
	 */
	public static function include_total_grams_in_order( int $order_id, $order = null ) {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		$weight = 0;
		$cart   = WC()->cart;
		if ( $cart !== null ) {
			// Grab total weight from cart.
			$weight = $cart->get_cart_contents_weight();
		} else {
			// Order was likely created/edited on admin or via REST API. Determine total weight from product data.
			$order = is_a( $order, 'WC_Order' ) ? $order : wc_get_order( $order_id );
			if ( empty( $order ) || $order === false ) {
				return;
			}

			foreach ( $order->get_items() as $item ) {
				// Get the product
				$product = $item->get_product();
				if ( $product === false ) {
					continue;
				}

				if ( ! empty( trim( $product->get_weight() ) ) ) {
					$weight += (float) $product->get_weight() * $item->get_quantity();
				}
			}
		}

		// Save the total weight (in grams) in order meta
		$weight = wc_get_weight( $weight, 'g' );
		update_post_meta( $order_id, 'uafrica_total_grams', $weight );

		// HPOS
		if ( ! empty( $order ) && $order !== false ) {
			$order->update_meta_data( 'uafrica_total_grams', $weight );
		}
	}

	/**
	 * Save the weight and dimensions of the items in the order as part of order meta so that it is included in order webhooks.
	 *
	 * @param int $order_id ID of the order.
	 * @param array|\WC_Order|null Posted order data, or the existing order.
	 *
	 * @return void
	 */
	public static function include_items_meta( int $order_id, $order = null ) {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		// Get order if not sent along to hook (Happens when hook fires too fast)
		$order = is_a( $order, 'WC_Order' ) ? $order : wc_get_order( $order_id );
		if ( empty( $order ) || $order === false ) {
			return;
		}

		$formatted_order_items = array();

		// Loop through the items
		foreach ( $order->get_items() as $item ) {
			// Get the product
			$product = $item->get_product();
			if ( $product === false ) {
				continue;
			}

			$formatted_item = array();

			// Save the order item ID
			$formatted_item['id'] = $item->get_id();

			// Save the product/variant dimensions
			if ( ! empty( trim( $product->get_height() ) ) ) {
				$formatted_item['height_cm'] = (int) wc_get_dimension( $product->get_height(), 'cm' );
			}
			if ( ! empty( trim( $product->get_length() ) ) ) {
				$formatted_item['length_cm'] = (int) wc_get_dimension( $product->get_length(), 'cm' );
			}
			if ( ! empty( trim( $product->get_width() ) ) ) {
				$formatted_item['width_cm'] = (int) wc_get_dimension( $product->get_width(), 'cm' );
			}

			// Save the product/variant weight
			if ( ! empty( trim( $product->get_weight() ) ) ) {
				$formatted_item['grams'] = (int) wc_get_weight( $product->get_weight(), 'g' );
			}

			// Save the product/variant shipping class info
			$formatted_item['shipping_class']    = $product->get_shipping_class();
			$formatted_item['shipping_class_id'] = $product->get_shipping_class_id();


			// Save the item to the array
			array_push( $formatted_order_items, $formatted_item );

		}

		// Update the meta for the items
		if ( ! empty( $formatted_order_items ) ) {
			update_post_meta( $order_id, 'bobgo_items_meta', $formatted_order_items );

			// HPOS
			$order->update_meta_data( 'bobgo_items_meta', $formatted_order_items );
		}
	}

	/**
	 * Saves the service_code returned from shipment rates in the order as part of order meta so that it is included in order webhooks.
	 *
	 * @param int $order_id ID of the order.
	 * @param \WC_Order|array|null $order Posted order data, or the existing order.
	 *
	 * @return void
	 */
	public static function include_service_code_in_order( int $order_id, $order = null ) {
		// Get order if not sent along to hook (Happens when hook fires too fast)
		$order = is_a( $order, 'WC_Order' ) ? $order : wc_get_order( $order_id );
		if ( empty( $order ) || $order === false ) {
			return;
		}

		// Loop inside shipping methods of order for service_code
		foreach ($order->get_shipping_methods() as $item => $shipping_item) {
			update_post_meta($order_id, 'uafrica_service_code', $shipping_item->get_meta('uafrica_service_code'));

			// HPOS
			$order->update_meta_data('uafrica_service_code', $shipping_item->get_meta('uafrica_service_code'));
		}
	}

	/**
	 * Gets the number of working days between the two dates
	 *
	 */
	public static function getWorkingDays( $startDate, $endDate ): int {
		$begin = strtotime( $startDate );
		$end   = strtotime( $endDate );

		$no_days  = 0;
		$weekends = 0;
		while ( $begin <= $end ) {
			// number of days in the given interval
			$no_days ++;
			$what_day = date( "N", $begin );
			// 6 and 7 are weekend days
			if ( $what_day > 5 ) {
				$weekends ++;
			};
			// +1 day
			$begin += 86400;
		};

		return $no_days - $weekends;
	}

	/**
	 * Shipping methods description and estimated delivery dates
	 *
	 */
	public static function shipping_methods_description( $method ): void {
		$meta_data                 = $method->get_meta_data();
		$uafrica_shipping_settings = get_option( 'woocommerce_uafrica_shipping_settings' );
		if ( ! empty( $uafrica_shipping_settings['Additional_rate_info'] ) && $uafrica_shipping_settings['Additional_rate_info'] === 'yes' ) {

			$deliveryTimeFrame = "";
			if ( ! empty( $meta_data['min_delivery_date'] ) && ! empty( $meta_data['max_delivery_date'] ) ) {
				$min_work_days = self::getWorkingDays( date( 'Y-m-d' ), $meta_data['min_delivery_date'] );
				$max_work_days = self::getWorkingDays( date( 'Y-m-d' ), $meta_data['max_delivery_date'] );

				if ( $min_work_days != $max_work_days ) {
					$deliveryTimeFrame = $min_work_days . ' to ' . $max_work_days . ' business days<br>';
				} else {
					//don't display range if $min_work_days and $max_work_days are equal
					$deliveryTimeFrame = $min_work_days . ' business days<br>';
					//don't use plural form if its 1 day
					if ( $min_work_days == 1 ) {
						$deliveryTimeFrame = $min_work_days . ' business day<br>';
					}
				}
			}

			$description = "";
			if ( ! empty( $meta_data['method_description'] ) ) {
				$description = $meta_data['method_description'];
			}

			if ( ! empty( $deliveryTimeFrame ) || ! empty( $description ) ) {
				echo "<div style='font-size: 0.8rem; padding-bottom:10px; font-weight: normal;'>" .
				     $deliveryTimeFrame .
				     $description .
				     "</div>";
			}
		}
	}

	/**
	 * Determine whether or not the suburb_at_checkout setting is active.
	 *
	 * @return bool
	 */
	private static function has_suburb_at_checkout(): bool {
		$options            = get_option( 'uafrica' );
		$suburb_at_checkout = $options['suburb_at_checkout'] ?? 1;

		return (bool) $suburb_at_checkout;
	}

	/**
	 * Add a suburb field to admin address fields so that it can be edited per order.
	 *
	 * @param array $fields Admin address edit fields.
	 *
	 * @return array
	 */
	public static function add_suburb_to_admin_address_fields( $fields ) {
		if ( ! is_array( $fields ) || empty( $fields ) ) {
			return [];
		}

		if ( ! self::has_suburb_at_checkout() ) {
			return $fields;
		}

		$out = [];
		foreach ( $fields as $key => $field ) {
			if ( $key === 'city' ) {
				$out['suburb'] = [
					'label' => 'Suburb',
					'show'  => true,
					'style' => '',
				];
			}
			$out[$key] = $field;
		}

		return $out;
	}

	/**
	 * Add suburb fields to checkout.
	 *
	 * @param array $fields Checkout fields.
	 *
	 * @return array
	 */
	public static function add_suburb_to_checkout_fields( $fields ) {
		if ( ! is_array( $fields ) || empty( $fields ) || ! function_exists( 'WC' ) ) {
			return [];
		}

		if ( ! self::has_suburb_at_checkout() ) {
			return $fields;
		}

		$cart = WC()->cart;
		if ( $cart && ! $cart->needs_shipping() ) {
			return $fields;
		}

		// Define suburb field for shipping address
		$shipping_suburb = [
			'id'          => 'shipping_suburb',
			'name'        => 'shipping_suburb',
			'type'        => 'text',
			'label'       => __('Suburb', 'woocommerce'),
			'placeholder' => __('Enter your suburb', 'woocommerce'),
			'required'    => false,
			'class'       => array('form-row-wide'),
			'priority'    => 65,
		];

		// Define suburb field for billing address
		$billing_suburb = [
			'id'          => 'billing_suburb',
			'name'        => 'billing_suburb',
			'type'        => 'text',
			'label'       => __('Suburb', 'woocommerce'),
			'placeholder' => __('Enter your suburb', 'woocommerce'),
			'required'    => false,
			'class'       => array('form-row-wide'),
			'priority'    => 65,
		];

		// Add to shipping fields
		$fields['shipping']['shipping_suburb'] = $shipping_suburb;

		// Add to billing fields
		$fields['billing']['billing_suburb'] = $billing_suburb;

		return $fields;
	}

	public static function save_suburb_in_session_during_order_review($posted_data) {
		try {
			// If the setting is off, unset the session values
			if ( ! self::has_suburb_at_checkout() ) {
				if ( WC()->session ) {
					WC()->session->__unset( 'shipping_suburb' );
					WC()->session->__unset( 'cb_shipping_suburb' );
					WC()->session->__unset( 'is_checkout_blocks' );
				}

				return;
			}

			parse_str( $posted_data, $output );

			if ( WC()->session ) {
				// If this function is triggered, it means we're using classic checkout
				WC()->session->set( 'is_checkout_blocks', false );

				// Determine which field to use
				$hasSuburb = false;
				$suburb = '';
				// ship_to_different_address will be in the post data, and will always be 1 if the shipping address should be used
				if ( isset($output['ship_to_different_address']) && sanitize_text_field($output['ship_to_different_address']) === '1' ) {
					// Use the shipping suburb
					if ( isset( $output['shipping_suburb'] ) ) {
						$suburb = sanitize_text_field( $output['shipping_suburb'] );
						$hasSuburb = true;
					}
				} else {
					// Use the billing suburb
					if ( isset( $output['billing_suburb'] ) ) {
						$suburb = sanitize_text_field( $output['billing_suburb'] );
						$hasSuburb = true;
					}
				}

				// Save the shipping suburb to session
				if ( $hasSuburb ) {
					WC()->session->set( 'shipping_suburb', $suburb );
				} else {
					WC()->session->__unset('shipping_suburb');
				}
			}
		} catch ( Exception $e ) {
			if ( WP_DEBUG ) {
				error_log( 'Failed to save suburb in session during order review: ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Saves the plugin version in order meta
	 *
	 * @param int $order_id ID of the order.
	 *
	 * @return void
	 */
	public static function save_plugin_version( int $order_id, $order = null) {
		// Get order if not sent along to hook (Happens when hook fires too fast)
		$order = is_a( $order, 'WC_Order' ) ? $order : wc_get_order( $order_id );
		if ( empty( $order ) || $order === false ) {
			return;
		}

		// Save the current plugin version in meta
		if ( defined( 'UAFRICA_SHIPPING_VERSION' ) ) {
			update_post_meta( $order_id, 'bobgo_plugin_version', UAFRICA_SHIPPING_VERSION );

			// HPOS
			$order->update_meta_data( 'bobgo_plugin_version', UAFRICA_SHIPPING_VERSION );
		}
	}

	/**
	 * Saves the shipping suburb and product shipping class in the packages payload to be used for rates at checkout
	 *
	 * @param array $packages The packages in the cart
	 *
	 * @return array
	 */
	public static function modify_shipping_packages($packages) {
		if (empty($packages)) {
			return [];
		}

		try {
			// Dynamically check if Checkout Blocks are used
			$is_checkout_blocks = WC()->session ? WC()->session->get( 'is_checkout_blocks' ) : false;

			foreach ( $packages as $index => $package ) {
				// Let's start with clean values
				unset( $packages[$index]['destination']['shipping_suburb'] );
				unset( $packages[$index]['destination']['cb_shipping_suburb'] );
				unset( $packages[$index]['destination']['is_checkout_blocks'] );

				// Add billing/shipping suburb to destination address for new checkout blocks and legacy checkout
				// Don't add it on the cart pages
				if ( self::has_suburb_at_checkout() ) {
					if ( $is_checkout_blocks ) {
						if ( ! is_cart() ) {
							if ( WC()->customer ) {
								$packages[$index]['destination']['cb_shipping_suburb'] = WC()->customer->get_meta( 'cb_shipping_suburb' );
							} else if ( WC()->session ) {
								$packages[$index]['destination']['cb_shipping_suburb'] = WC()->session->get( 'cb_shipping_suburb' );
							}
							$packages[$index]['destination']['is_checkout_blocks'] = 'true';
						}
					} elseif ( is_checkout() ) {
						// Only use POST data if it's available, else fallback to session data

						// First check in POST data
						if ( isset( $_POST['shipping_suburb'] ) ) {
							$packages[$index]['destination']['shipping_suburb'] = sanitize_text_field( $_POST['shipping_suburb'] );
						} elseif ( isset( $_POST['billing_suburb'] ) ) {
							$packages[$index]['destination']['shipping_suburb'] = sanitize_text_field( $_POST['billing_suburb'] );
						} elseif ( WC()->session ) {
							// Fallback to session data if POST is not available
							$packages[$index]['destination']['shipping_suburb'] = WC()->session->get( 'shipping_suburb' );
						}
					}
				}


				// Add product shipping class to the items
				$cart_items = $packages[$index]['contents'];
				foreach ( $cart_items as $key => $item ) {
					/**
					 * Holds the product.
					 *
					 * @var \WC_Product $wc_product
					 */
					$wc_product                            = $item['data'];
					$cart_items[$key]['shipping_class']    = $wc_product->get_shipping_class();
					$cart_items[$key]['shipping_class_id'] = $wc_product->get_shipping_class_id();
				}
				$packages[$index]['contents'] = $cart_items;
			}

		} catch ( Exception $e ) {
			if ( WP_DEBUG ) {
				error_log( 'Failed to determine if checkout blocks are being used: ' . $e->getMessage() );
			}
		}

		return $packages;
	}

	/**
	 * Register an additional checkout field for the suburb for Woocommerce checkout blocks
	 *
	 */
	public static function register_checkout_field() {
		try {
			if ( ! self::has_suburb_at_checkout() ) {
				return;
			}

			woocommerce_register_additional_checkout_field(
				array(
					'id'            => 'namespace/cb_shipping_suburb',
					'label'         => 'Suburb',
					'optionalLabel' => 'Suburb (optional)',
					'location'      => 'address',
					'required'      => false, // Optional field
					'attributes'    => array(
						'autocomplete'     => 'suburb',
						'title'            => 'Suburb',
					),
				)
			);
		} catch ( Exception $e ) {
			if ( WP_DEBUG ) {
				error_log( 'Failed to add suburb field for checkout blocks: ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Save the checkout block shipping_suburb value
	 *
	 * @param string $key The key of the field being saved.
	 * @param mixed $value The value of the field being saved.
	 * @param string $group The group of this location (shipping|billing|other).
	 * @param WC_Customer|WC_Order $wc_object The object to set the field value for.
	 *
	 * @return void
	 */
	public static function set_additional_field_value( $key, $value, $group, $wc_object ) {
		try {
			if ('namespace/cb_shipping_suburb' !== $key) {
				return;
			}

			if ('shipping' === $group) {
				$cb_shipping_suburb = 'cb_shipping_suburb';
			} else {
				return; // We only care about the shipping group in this case
			}

			// If the setting is off, unset the session values
			if ( ! self::has_suburb_at_checkout() ) {
				if ( WC()->session ) {
					WC()->session->__unset( 'shipping_suburb' );
					WC()->session->__unset( 'cb_shipping_suburb' );
					WC()->session->__unset( 'is_checkout_blocks' );
				}

				return;
			}

			// Update the metadata with the checkout block cb_shipping_suburb value for logged-in users
			if ( is_a( $wc_object, 'WC_Customer' ) ) {
				$wc_object->update_meta_data($cb_shipping_suburb, $value, true);
			}

			// Save to session for guest users or during the checkout session
			if ( WC()->session ) {
				WC()->session->set( 'is_checkout_blocks', true );
				WC()->session->set( 'cb_shipping_suburb', $value );
			}
		} catch ( Exception $e ) {
			if ( WP_DEBUG ) {
				error_log( 'Failed to set additional field value: ' . $e->getMessage() );
			}
		}
	}
}
