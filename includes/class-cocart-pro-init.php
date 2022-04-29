<?php
/**
 * CoCart Pro REST API
 *
 * Extends and handles additional cart endpoints requests for CoCart Pro.
 *
 * @author   Sébastien Dumont
 * @category API
 * @package  CoCart Pro/API
 * @license  GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CoCart Pro REST API class.
 */
class CoCart_Pro_Rest_API {

	/**
	 * Setup class.
	 *
	 * @access public
	 */
	public function __construct() {
		// CoCart Pro REST API.
		$this->cocart_pro_rest_api_init();
	} // END __construct()

	/**
	 * Init CoCart Pro REST API.
	 *
	 * @access private
	 */
	private function cocart_pro_rest_api_init() {
		// REST API was included starting WordPress 4.4.
		if ( ! class_exists( 'WP_REST_Server' ) ) {
			return;
		}

		// If CoCart does not exists then do nothing!
		if ( ! class_exists( 'CoCart' ) ) {
			return;
		}

		

		// Include REST API Controllers.
		add_action( 'rest_api_init', array( $this, 'rest_api_includes' ), 6 );

		// Supports WooCommerce extensions by filtering the cart to apply additional data.
		add_action( 'rest_api_init', array( $this, 'support_extensions' ), 8 );

		// Register CoCart Pro REST API routes.
		add_action( 'rest_api_init', array( $this, 'register_cart_pro_routes' ), 12 );
	} // cart_rest_api_init()

	/**
	 * Include CoCart Pro REST API controllers.
	 *
	 * @access public
	 */
	public function rest_api_includes() {
		include_once( dirname( __FILE__ ) . '/api/class-cocart-pro-coupon-controller.php' ); // Coupon
		include_once( dirname( __FILE__ ) . '/api/class-cocart-pro-calculate-controller.php' ); // Calculate
		include_once( dirname( __FILE__ ) . '/api/class-cocart-pro-cross-sells-controller.php' ); // Cross Sells
		include_once( dirname( __FILE__ ) . '/api/class-cocart-pro-customer-controller.php' ); // Customer
		include_once( dirname( __FILE__ ) . '/api/class-cocart-pro-fees-controller.php' ); // Fees
		include_once( dirname( __FILE__ ) . '/api/class-cocart-pro-payment-methods-controller.php' ); // Payment Methods
		include_once( dirname( __FILE__ ) . '/api/class-cocart-pro-quantities-controller.php' ); // Cart Item Quantities
		include_once( dirname( __FILE__ ) . '/api/class-cocart-pro-removed-items-controller.php' ); // Removed Items
		include_once( dirname( __FILE__ ) . '/api/class-cocart-pro-shipping-methods-controller.php' ); // Shipping Methods
		include_once( dirname( __FILE__ ) . '/api/class-cocart-pro-totals-controller.php' ); // Totals
		include_once( dirname( __FILE__ ) . '/api/class-cocart-pro-weight-controller.php' ); // Cart Weight
	} // rest_api_includes()

	/**
	 * Includes filtered data for WooCommerce Extensions.
	 *
	 * @access public
	 */
	public function support_extensions() {
		// Subscriptions
		if ( class_exists( 'WC_Subscriptions' ) ) {
			include_once( dirname( __FILE__) . '/extensions/class-cocart-pro-subscriptions.php' );
		}

		include_once( dirname( __FILE__) . '/extensions/class-cocart-pro-accommodation-bookings.php' ); // Accommodation Bookings
	} // support_extensions()

	/**
	 * Register CoCart Pro REST API routes.
	 *
	 * @access public
	 */
	public function register_cart_pro_routes() {
		$controllers = array(
			'CoCart_Pro_Coupon_Controller',
			'CoCart_Pro_Calculate_Controller',
			'CoCart_Pro_Cross_Sells_Controller',
			'CoCart_Pro_Cart_Owner_Controller',
			'CoCart_Pro_Fees_Controller',
			'CoCart_Pro_Payment_Methods_Controller',
			'CoCart_Pro_Quantities_Controller',
			'CoCart_Pro_Removed_Items_Controller',
			'CoCart_Pro_Shipping_Methods_Controller',
			'CoCart_Pro_Totals_Controller',
			'CoCart_Pro_Weight_Controller'
		);

		sort( $controllers );

		foreach ( $controllers as $controller ) {
			$this->$controller = new $controller();
			$this->$controller->register_routes();
		}
	} // END register_cart_pro_routes()

} // END class

return new CoCart_Pro_Rest_API();
