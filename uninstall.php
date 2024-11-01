<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) || ! WP_UNINSTALL_PLUGIN ) {
	wp_die(); // Don't trigger uninstall on accident.

}
// Uninstall all traces of the uAfrica plugin.
delete_option( 'woocommerce_uafrica_shipping_settings' );
if ( is_multisite() ) {
	$uafrica_shipping_blog_ids = get_sites( array( 'fields' => 'ids' ) );
	foreach ( $uafrica_shipping_blog_ids as $uafrica_shipping_blog_id ) {
		switch_to_blog( $uafrica_shipping_blog_id );
		delete_option( 'uafrica' );
		restore_current_blog();
	}
} else {
	delete_option( 'uafrica' );
}



