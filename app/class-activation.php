<?php


namespace uAfrica_Shipping\app;

/**
 * Class Activation
 *
 * @package uAfrica_Shipping\app
 */
class Activation {

	/**
	 * Display the activation notice
	 */
	public static function notice() {
		if ( 'plugins' !== get_current_screen()->id ) {
			return; // only check this on the plugins page.
		}
		$option = get_option( 'uafrica' );
		if ( empty( $option['activate_message'] ) ) {
			return; // no message to display.
		}
		$message = $option['activate_message'];

		echo '<div class="updated notice is-dismissible"><p>' . nl2br( esc_html( $message ) ) . '</p></div>';

		unset( $option['activate_message'] ); // display notice only once, so we clear it.
		update_option( 'uafrica', $option );
	}

	/**
	 * On activation do stuff
	 *
	 * @param bool $network_wide if the plugin in installed network wide.
	 */
	public static function activation( $network_wide ) {
		try {
			if ( is_multisite() && $network_wide ) {
				$blog_ids = get_sites( array( 'fields' => 'ids' ) );
				foreach ( $blog_ids as $blog_id ) {
					switch_to_blog( $blog_id );
					self::create_page();
					self::enable_suburb_fields( true );
					restore_current_blog();
				}
			} else {
				self::create_page();
				self::enable_suburb_fields( true );
			}
		} catch ( Exception $e ) {
			// Deactivate plugin in case of any error and show a meaningful error message
			deactivate_plugins( plugin_basename( plugin_basename( UAFRICA_SHIPPING_DIR ) . '/uafrica-shipping.php' ) );
			if ( is_admin() ) {
				error_log('Plugin activation failed: ' . $e->getMessage() );
				add_action( 'admin_notices', function() {
					?>
					<div class="notice notice-error">
						<p><?php esc_html_e( 'Plugin activation failed.', 'uafrica-shipping' ); ?></p>
					</div>
					<?php
				});
			}
            return;
		}
	}

	/**
	 * Fire on createing a new blog.
	 *
	 * @param int $blog_id the blog id of the new blog.
	 */
	public static function create_new_blog( $blog_id ) {
		if ( is_plugin_active_for_network( plugin_basename( UAFRICA_SHIPPING_DIR ) . '/uafrica-shipping.php' ) ) {
			switch_to_blog( $blog_id );
			self::create_page();
			self::enable_suburb_fields( true );
			restore_current_blog();
		}
	}

	/**
	 * Create a page and set the shortcode or add the gutenbergblock.
	 */
	public static function create_page() {
		$option = get_option( 'uafrica' );
		
		// Check if the 'shipping_page' option exists and is a valid page ID.
		if ( ! empty( $option['shipping_page'] ) && get_post_status( $option['shipping_page'] ) ) {
			// There already appears to be an older shipping page from a previous activation.
			// No need to create a new shipping page.
			$option['activate_message']  = __( 'A shipping page was already found from a previous activation.', 'uafrica-shipping' ) . PHP_EOL;
			$option['activate_message'] .= __( 'You can change this page in the settings.', 'uafrica-shipping' );
			update_option( 'uafrica', $option );
			
			return;
		}
		
		// Determine content based on editor type.
		if ( ! use_block_editor_for_post_type( 'page' ) ) {
			$content = '[uafrica bg_color="#000000" text_color="#ffffff"]';
		} else {
			$content = '<!-- wp:uafrica/shipping /-->';
		}
		
		$arr = [];
		$arr['post_title'] = __( 'Shipping details', 'uafrica-shipping' );
		$arr['post_content'] = $content;
		$arr['post_type'] = 'page';
		$arr['post_status'] = 'publish';
		
		$option['shipping_page']     = wp_insert_post( $arr );
		$option['activate_message']  = __( 'Created a new shipping page.', 'uafrica-shipping' ) . PHP_EOL;
		$option['activate_message'] .= __( 'You can change this page in the settings.', 'uafrica-shipping' );
		update_option( 'uafrica', $option );
	}
	

	/**
	 * Enable suburb fields at checkout by default.
	 */
	public static function enable_suburb_fields( $force = false ) {
		$option = get_option( 'uafrica' );
		if ( $force || ! isset( $option['suburb_at_checkout'] ) ) {
			$option['suburb_at_checkout'] = 1;
			update_option( 'uafrica', $option );
		}
	}
}
