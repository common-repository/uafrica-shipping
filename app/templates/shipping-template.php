<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound,WordPress.Security.NonceVerification.Recommended
/**
 * The attributes passed on through the shortcode/block.
 *
 * @var array $atts
 */
$style     = "background-color: {$atts['bg_color']}; color: {$atts['text_color']};";
$id_suffix = random_int( 1000, 9999 ); // Keep id's unique in case someone includes multiple shipping forms one page. Still not recommended.
$value     = '';
if ( ! empty( $_GET['order-number'] ) && is_numeric( $_GET['order-number'] ) ) {
    $value = absint( $_GET['order-number'] );
}
$svg_url = UAFRICA_SHIPPING_URL . 'assets/icons/';
?>
<div class="uafrica-shipping-container">
    <div class="shipping-step-form">
        <form action="" method="get">
            <label>
                <span class="screen-reader-text"><?php esc_html_e( 'Order number', 'uafrica-shipping' ); ?></span>
                <input type="text" name="order-number" required aria-required="true" class="order-nr-input"
                       value="<?php echo esc_attr( $value ); ?>" placeholder="Enter your tracking reference or order number"/>
            </label><br/>
            <div class="validation hidden"></div>
            <button type="submit" style="<?php echo esc_attr( $style ); ?>">
                <?php esc_html_e( 'Track order', 'uafrica-shipping' ); ?>
            </button>
        </form>
    </div><!-- .shipping-step-form -->
    <div class="shipping-step-status frontend hidden">
        <header class="shipping-header">
            <h5 class="global-status"
                style="<?php echo esc_attr( $style ); ?>">
                Shipping details
            </h5>
            <div class="shipping-details">
                <div>
                    <span class="name"><?php esc_html_e( 'Shipment:', 'uafrica-shipping' ); ?></span>
                    <span data-uafrica="id"></span>
                </div>
                <div>
                    <span class="name"><?php esc_html_e( 'Order:', 'uafrica-shipping' ); ?></span>
                    <span data-uafrica="custom_order_name"></span>
                </div>
                <div>
                    <span class="name"><?php esc_html_e( 'Courier:', 'uafrica-shipping' ); ?></span>
                    <span data-uafrica="courier_name"></span>
                </div>
            </div>

        </header>

        <div id="table_checkpoints" class="shipping-header" >
            <h5 class="global-status"
                style="<?php echo esc_attr( $style ); ?>">
                <span data-uafrica="time"></span>
            </h5>
            <table id="delivery_steps">
            </table>
        </div>

        <div id="table_else" class="shipping-header" >
            <h5 class="global-status"
                style="<?php echo esc_attr( $style );?>">
                <span data-uafrica="delivery_heading"></span>
            </h5>
            <div class="delivery-message">
                <span data-uafrica="delivery_message"></span>
            </div>
        </div>

        <div class="follow-up" >
            <?php
            printf(
            // translators: %1$s Courier name %2$s Courier phone number %3$s order ID.
                __( 'For additional information, please contact <strong>%1$s (%2$s)</strong> and quote tracking reference <strong>%3$s.</strong>', 'uafrica-shipping' ),
                '<span data-uafrica="courier_name"></span>',
                '<span data-uafrica="courier_phone"></span>',
                '<span data-uafrica="id"></span>'
            );
            ?>
        </div>

        <div class="branding">
            <img id="show_branding" class="image"
                 src="https://ik.imagekit.io/z1viz85yxs/prod-v3/bobgo/corporate-identity/bobgo_logo_smart_shipping_black.png?tr=w-400"
                 alt="Bob Go logo">
        </div>
    </div><!-- .shipping-step-status -->
</div><!-- .uafrica-shipping-container -->
