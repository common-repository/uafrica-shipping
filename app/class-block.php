<?php

namespace uAfrica_Shipping\app;

/**
 * Class Block Handles the Gutenberg and Elementor blocks/widgets.
 *
 * @package uAfrica_Shipping\app
 */
class Block {

	const HANDLE = 'uafrica-shipping-block';

	/**
	 * Register all files for the uAfrica block.
	 */
	public static function register_block() {
		$script_asset_path = UAFRICA_SHIPPING_BUILD_DIR . 'index.asset.php';
		// If the script asset file doesn't exist, gracefully exit and notify admin users
		if ( ! file_exists( $script_asset_path ) ) {
			if ( is_admin() ) {
				add_action( 'admin_notices', function() {
					?>
					<div class="notice notice-error">
						<p><?php esc_html_e( '"uafrica/shipping" block not found. Please run `npm start` or `npm run build` to resolve the issue.', 'uafrica-shipping' ); ?></p>
					</div>
					<?php
				});
			}

			return; // Exit early to prevent block registration
		}

		$script_asset = require $script_asset_path;
		wp_register_script(
			self::HANDLE . '-editor',
			UAFRICA_SHIPPING_BUILD_URL . 'index.js',
			$script_asset['dependencies'],
			$script_asset['version'],
			false
		);

		wp_set_script_translations( self::HANDLE . '-editor', 'uafrica-shipping', UAFRICA_SHIPPING_DIR . 'languages/' );

		wp_register_style(
			self::HANDLE . '-editor',
			UAFRICA_SHIPPING_BUILD_URL . 'index.css',
			array(),
			filemtime( UAFRICA_SHIPPING_BUILD_DIR . 'index.css' )
		);

		register_block_type(
			'uafrica/shipping',
			array(
				'editor_script'   => self::HANDLE . '-editor',
				'editor_style'    => self::HANDLE . '-editor',
				'style'           => self::HANDLE,
				'render_callback' => array( '\uAfrica_Shipping\app\Shortcode', 'render' ),
			)
		);
	}

	public static function register_elementor_widget( $widgets_manager ) {
		$uAfricaElementorPlugin = new ElementorWidget();
		if ( method_exists( $widgets_manager, 'register' ) ) {
			// Elementor >= 3.5.0
			$widgets_manager->register( $uAfricaElementorPlugin );
		} else {
			// Elementor < 3.5.0
			$widgets_manager->register_widget_type( $uAfricaElementorPlugin );
		}
	}
}
