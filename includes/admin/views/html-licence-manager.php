<?php
/**
 * Admin View: Licence Manager.
 *
 * @author   Sébastien Dumont
 * @category Admin
 * @package  CoCart Pro/Admin/Views
 * @license  GPL-2.0+
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap cocart licence-manager">

	<div class="container">

		<div class="content">
			<div class="st-plugin-header">
				<a href="<?php echo "https://shoptype.freshdesk.com/support/solutions/articles/73000527584-woocommerce-integration"; ?>" target="_blank">
					<img src="<?php echo COCART_PRO_URL_PATH . '/assets/images/logo.jpg'; ?>" alt="<?php echo esc_attr__( 'Shoptype - Woocommerce Integration Documentation', 'cocart-pro' ); ?>" />
				</a>
			</div>

			<h1><?php printf( 'Generating Woocommerce API Keys' ); ?></h1>

			<li><strong><a href="admin.php?page=wc-settings&tab=advanced&section=keys" target="_blank">Go to: WooCommerce > Settings > Advanced > REST API.</a></strong></li>,
			<li><strong><?php _e( 'Select Add Key. You are taken to the Key Details screen' ); ?></strong></li>
			<li><strong><?php _e( 'Add a Description. - "Shoptype"' ); ?></strong></li>
			<li><strong><?php _e( 'Select the User you would like to generate a key for in the dropdown. - Any admin user' ); ?></strong></li>
			<li><strong><?php _e( 'Select the level of access for this API key - "Read/Write" access' ); ?></strong></li>
			<li><strong><?php _e( 'Click Generate API Key, and WooCommerce will create API keys for the specified user.' ); ?></strong></li>
			<li><strong><?php _e( 'Now that the keys have been generated, you should see a Consumer Key and Consumer Secret key, a QRCode, and a Revoke API Key button.' ); ?></strong></li>
<br><br>
			<h2><u><?php printf( 'Please save the following to be used for the integration' ); ?></u></h2><br>
			<h3><?php printf( 'Consumer Key' ); ?></h3>
			<h3><?php printf( 'Consumer Secret' ); ?></h3>
			<h3><?php printf( 'Store host url: ' ); echo get_site_url();?></h3>

			<!--p><?php esc_html_e( 'Enter your licence key or email address above and press "Check Licence".', 'cocart-pro' ); ?></p-->

			<!--p style="text-align: center;">
				<?php printf( '<a class="button button-primary button-large" href="%1$s" target="_blank">%2$s</a>', '#', esc_html__( 'Check Licence', 'cocart-pro' ) ); ?>
			</p-->
		</div>

	</div>

</div>
