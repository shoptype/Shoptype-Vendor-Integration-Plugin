<?php
/**
 * Admin View: CoCart Enhanced not installed or activated notice.
 *
 * @author   Sébastien Dumont
 * @category Admin
 * @package  CoCart Enhanced
 * @license  GPL-2.0+
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="notice notice-warning cocart-notice">
	<div class="cocart-notice-inner">
		<div class="cocart-notice-icon">
			<img src="<?php echo COCART_PRO_URL_PATH . '/assets/images/logo.jpg'; ?>" alt="<?php echo esc_attr__( 'CoCart, a WooCommerce REST-API extension', 'cocart-pro' ); ?>" />
		</div>

		<div class="cocart-notice-content">
			<h3><?php echo sprintf( __( 'Shoptype requires Cocart Enhanced to be installed and activated.', 'cocart-pro' ), 'CoCart Pro', 'CoCart' ); ?></h3>

			<p>
			<?php
			if ( ! is_plugin_active( 'cocart-get-cart-enhanced\cocart-get-cart-enhanced.php' ) && file_exists( WP_PLUGIN_DIR . '/cocart-get-cart-enhanced\cocart-get-cart-enhanced.php' ) ) :

				if ( current_user_can( 'activate_plugin', 'cocart-get-cart-enhanced\cocart-get-cart-enhanced.php' ) ) :

					echo sprintf( '<a href="%1$s" class="button button-primary" aria-label="%2$s">%2$s</a>', esc_url( wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=cocart-get-cart-enhanced\cocart-get-cart-enhanced.php&plugin_status=active' ), 'activate-plugin_cocart-get-cart-enhanced\cocart-get-cart-enhanced.php' ) ), esc_html__( 'Activate CoCart Enhanced', 'cocart-pro' ) );

				else :

					echo esc_html__( 'As you do not have permission to activate a plugin. Please ask a site administrator to activate CoCart for you.', 'cocart-pro' );

				endif;

			else:

				if ( current_user_can( 'install_plugins' ) ) {
					$url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=cocart-get-cart-enhanced' ), 'install-plugin_cocart-get-cart-enhanced' );
				} else {
					$url = 'https://wordpress.org/plugins/cocart-get-cart-enhanced/';
				}

				echo '<a href="' . esc_url( $url ) . '" class="button button-primary" aria-label="' . esc_html__( 'Install CoCart', 'cocart-pro' ) . '">' . esc_html__( 'Install CoCart', 'cocart-pro' ) . '</a>';

			endif;

/*			if ( current_user_can( 'deactivate_plugin', 'cocart-pro/cocart-pro.php' ) ) :

				echo sprintf( 
					' <a href="%1$s" class="button button-secondary" aria-label="%2$s">%2$s</a>', 
					esc_url( wp_nonce_url( 'plugins.php?action=deactivate&plugin=cocart-get-cart-enhanced\cocart-get-cart-enhanced.php&plugin_status=inactive', 'deactivate-plugin_cocart-get-cart-enhanced\cocart-get-cart-enhanced.php' ) ),
					esc_html__( 'Turn off the Shoptype plugin', 'cocart-pro' )
				);

			endif; */
			?>
			</p>
		</div>
	</div>
</div>