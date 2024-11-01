<?php
/**
 * Plugin Name:       Bob Go smart shipping solution for WooCommerce
 * Plugin URI:        https://wordpress.org/plugins/uafrica-shipping/
 * Description:       Display Bob Go shipping information at checkout.
 * Author:            bobgroup
 * Author:            Bob Group
 * Author URI:        https://www.bob.co.za
 * Developer:         Bob Group
 * Developer URI:     http://www.bob.co.za
 * Text Domain:       uafrica-shipping
 * Domain Path:       /languages
 * Requires at least: 5.0
 * Requires PHP:      7.0
 * Version:           3.0.3
 * License:           GPLv2 or later
 *
 * @package Uafrica_Shipping
 */

namespace uAfrica_Shipping;

/**
 * Constants.
 */

define( 'UAFRICA_SHIPPING_VERSION', '3.0.3' );
// Endpoints for tracking orders.
define( 'UAFRICA_SHIPPING_API_TRACKING_V3', 'https://api.bobgo.co.za/tracking?channel=DOMAIN&tracking_reference=NUMBER' );
// Endpoints for shipping methods and rates.
define( 'UAFRICA_SHIPPING_API_SHIPPING_METHODS_V3', 'https://api.bobgo.co.za/rates-at-checkout/woocommerce' );
// Internal Directories.
define( 'UAFRICA_SHIPPING_DIR', plugin_dir_path( __FILE__ ) );
define( 'UAFRICA_SHIPPING_APP_DIR', UAFRICA_SHIPPING_DIR . 'app' . DIRECTORY_SEPARATOR );
define( 'UAFRICA_SHIPPING_TEMPLATES_DIR', UAFRICA_SHIPPING_APP_DIR . 'templates' . DIRECTORY_SEPARATOR );
define( 'UAFRICA_SHIPPING_BUILD_DIR', UAFRICA_SHIPPING_DIR . 'block' . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR );
define( 'UAFRICA_SHIPPING_SLUG', plugin_basename( UAFRICA_SHIPPING_DIR ) );
// Internal URL's.
define( 'UAFRICA_SHIPPING_URL', plugin_dir_url( __FILE__ ) );
define( 'UAFRICA_SHIPPING_BUILD_URL', UAFRICA_SHIPPING_URL . 'block/build/' );

/**
 * Autoload classes.
 */
spl_autoload_register( function ( $class_name ) { //phpcs:ignore PEAR.Functions.FunctionCallSignature
	if ( strpos( $class_name, 'uAfrica_Shipping\app' ) !== 0 ) {
		return; // Not in the uAfrica namespace, don't check.
	}
	$bare_class = str_replace( 'uAfrica_Shipping\app\\', '', $class_name );
	require_once UAFRICA_SHIPPING_APP_DIR . 'class-' . strtolower( $bare_class ) . '.php';
} );//phpcs:ignore PEAR.Functions.FunctionCallSignature

/**
 * Hook everything.
 */
//phpcs:disable PEAR.Functions.FunctionCallSignature

// Plugin activation.
register_activation_hook( __FILE__, array( '\uAfrica_Shipping\app\Activation', 'activation' ) );
add_action( 'admin_notices', array( '\uAfrica_Shipping\app\Activation', 'notice' ) );
add_action( 'wpmu_new_blog', array( '\uAfrica_Shipping\app\Activation', 'create_new_blog' ) );

// Translations.
add_action( 'init', function () { load_plugin_textdomain( 'uafrica-shipping', false, plugin_basename( UAFRICA_SHIPPING_DIR ) . '/languages/' ); } );

// Settings page.
add_action( 'admin_menu', array( '\uAfrica_Shipping\app\Admin', 'register_menu_item' ) );
add_action( 'admin_init', array( '\uAfrica_Shipping\app\Admin', 'register_settings' ) );

// Adds a link to the settings page on the plugin overview.
add_filter( 'plugin_action_links_' . UAFRICA_SHIPPING_SLUG . '/uafrica-shipping.php', array( '\uAfrica_Shipping\app\Admin', 'add_plugin_page_settings_link' ) );

// Add label to pages overview.
add_filter( 'display_post_states', array( '\uAfrica_Shipping\app\Admin', 'label_page_as_shipping' ), 10, 2 );

// Add shortcode.
add_shortcode( 'uafrica', array( 'uAfrica_Shipping\app\Shortcode', 'render' ) );
add_action( 'wp_enqueue_scripts', array( 'uAfrica_Shipping\app\Shortcode', 'styles_scripts' ) );

// Everything needed to register the block.
add_action( 'init', array( '\uAfrica_Shipping\app\Block', 'register_block' ) );
add_action( 'elementor/elements/categories_registered', function ( $elements_manager ) {
	// Add "uafrica" Elementor widget category.
	$elements_manager->add_category( 'uafrica', [
		'title' => 'uAfrica',
	] );
}, 10, 1 );
add_action( 'elementor/widgets/widgets_registered', array( '\uAfrica_Shipping\app\Block', 'register_elementor_widget' ) );

// WooCommerce-specific configs, hooks and filter.
// Track order button - for legacy CPT-based orders
add_filter( 'manage_edit-shop_order_columns', array( '\uAfrica_Shipping\app\Admin', 'order_column_name' ) );
add_action( 'manage_shop_order_posts_custom_column', array( '\uAfrica_Shipping\app\Admin', 'order_column_content' ), 10, 2 );
// Track order - for HPOS-based orders
add_filter( 'manage_woocommerce_page_wc-orders_columns', array( '\uAfrica_Shipping\app\Admin', 'order_column_name' ), 20 );
add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( '\uAfrica_Shipping\app\Admin', 'order_column_content' ), 20, 2 );
// Checkout
add_filter( 'woocommerce_shipping_methods', array( '\uAfrica_Shipping\app\Admin', 'register_shipping_method' ) );
add_filter( 'woocommerce_package_rates', array( '\uAfrica_Shipping\app\Admin', 'filter_shipping_methods' ), 10 );
add_filter( 'woocommerce_after_shipping_rate', [ '\uAfrica_Shipping\app\WooCommerce', 'shipping_methods_description' ], 10, 2 );
add_filter( 'woocommerce_checkout_fields', array( '\uAfrica_Shipping\app\WooCommerce', 'add_suburb_to_checkout_fields' ), 20, 1 );
add_filter( 'woocommerce_checkout_update_order_review', array( '\uAfrica_Shipping\app\WooCommerce', 'save_suburb_in_session_during_order_review' ), 10 );
add_action( 'woocommerce_checkout_update_order_meta', array( '\uAfrica_Shipping\app\WooCommerce', 'save_order_meta_checkout' ), 20, 2 );
add_action( 'woocommerce_cart_shipping_packages', [ '\uAfrica_Shipping\app\WooCommerce', 'modify_shipping_packages' ], 30, 1 );
// Checkout blocks
add_action('woocommerce_init', array( '\uAfrica_Shipping\app\WooCommerce', 'register_checkout_field'));
add_action('woocommerce_set_additional_field_value', array( '\uAfrica_Shipping\app\WooCommerce', 'set_additional_field_value'), 10, 4);
// Order
add_action( 'woocommerce_new_order', [ '\uAfrica_Shipping\app\WooCommerce', 'save_order_meta' ], 10, 2 );
add_action( 'woocommerce_update_order', [ '\uAfrica_Shipping\app\WooCommerce', 'save_order_meta' ], 10, 2 );
add_filter( 'woocommerce_admin_billing_fields', array( '\uAfrica_Shipping\app\WooCommerce', 'add_suburb_to_admin_address_fields' ), 20, 1 );
add_filter( 'woocommerce_admin_shipping_fields', array( '\uAfrica_Shipping\app\WooCommerce', 'add_suburb_to_admin_address_fields' ), 20, 1 );

// Declare compatibility
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true ); // HPOS
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true ); // Blocks
	}
} );
