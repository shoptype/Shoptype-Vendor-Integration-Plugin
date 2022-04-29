<?php
/**
 * Admin View: Plugin Review Notice.
 *
 * @author   Sébastien Dumont
 * @category Admin
 * @package  CoCart Pro/Admin/Views
 * @license  GPL-2.0+
 */

// Exit if accessed directly.
/*
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_user = wp_get_current_user();

$time = CoCart_Pro_Admin::cocart_seconds_to_words( time() - $install_date );
?>
<div class="notice notice-info cocart-notice">
	<div class="cocart-notice-inner">
		<div class="cocart-notice-icon">
			<img src="<?php echo COCART_PRO_URL_PATH . '/assets/images/logo.jpg'; ?>" alt="<?php echo esc_attr__( 'CoCart, a WooCommerce REST-API extension', 'cocart-pro' ); ?>" />
		</div>

		<div class="cocart-notice-content">
			<h3><?php printf( esc_html__( 'Hi %1$s, are you enjoying %2$s?', 'cocart-pro' ), $current_user->display_name, 'CoCart Pro' ); ?></h3>
			<p><?php printf( esc_html__( 'You have been using %1$s for %2$s now! Mind leaving a review and let me know know what you think of the plugin? I\'d really appreciate it!', 'cocart-pro' ), 'CoCart Pro', esc_html( $time ) ); ?></p>
		</div>

		<div class="cocart-action">
			<?php printf( '<a href="%1$s" class="button button-primary cocart-button" target="_blank">%2$s</a>', esc_url( COCART_PRO_REVIEW_URL ), esc_html__( 'Leave a Review', 'cocart-pro' ) ); ?>
			<a href="<?php echo esc_url( add_query_arg( 'hide_cocart_pro_review_notice', 'true' ) ); ?>" class="no-thanks"><?php echo esc_html__( 'No thank you / I already have', 'cocart-pro' ); ?></a>
		</div>
	</div>
</div>
*/