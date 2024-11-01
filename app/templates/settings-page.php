<?php
/**
 * Displays the HTML of the settings page.
 *
 * @package Uafrica_Shipping
 */

?>
<div class="wrap">
	<h1><?php esc_html_e( 'Bob Go settings', 'uafrica-shipping' ); ?></h1>
	<form action="options.php" method="POST">
		<?php settings_fields( 'uafrica-settings' ); ?>
		<?php do_settings_sections( 'uafrica-shipping' ); ?>
		<?php submit_button( __( 'Save shipping page', 'uafrica-shipping' ) ); ?>
	</form>
</div>
