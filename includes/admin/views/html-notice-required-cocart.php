<?php
/**
 * Admin View: Required CoCart Notice.
 *
 * @author   Sébastien Dumont
 * @category Admin
 * @package  CoCart/Admin/Views
 * @license  GPL-2.0+
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="notice notice-info cocart-notice">
	<div class="cocart-notice-inner">
		<div class="cocart-notice-icon">
			<img src="<?php echo COCART_PRO_URL_PATH . '/assets/images/logo.jpg'; ?>" alt="<?php echo esc_attr__( 'CoCart, a WooCommerce REST-API extension', 'cocart-pro' ); ?>" />
		</div>

		<div class="cocart-notice-content">
			<h3><?php echo esc_html__( 'Update Required!', 'cocart-pro' ); ?></h3>
			<p><?php echo sprintf( __( '%1$s requires at least %2$s v%3$s or higher.', 'cocart-pro' ), 'CoCart Pro', 'CoCart', CoCart_Pro::$required_cocart ); ?></p>
		</div>

		<?php if ( current_user_can( 'update_plugins' ) ) { ?>
		<div class="cocart-action">
			<?php $upgrade_url = wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=cart-rest-api-for-woocommerce' ), 'upgrade-plugin_cart-rest-api-for-woocommerce' ); ?>

			<p><a href="<?php echo esc_url( $upgrade_url ); ?>" class="button button-primary cocart-button" aria-label="<?php echo esc_html__( 'Update CoCart', 'cocart-pro' ); ?>"><?php echo esc_html__( 'Update CoCart', 'cocart-pro' ); ?></a></p>
		</div>
		<?php } ?>
	</div>
</div>
