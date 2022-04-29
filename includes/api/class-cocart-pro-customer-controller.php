<?php
/**
 * CoCart Pro - Cart Owner controller
 *
 * Handles requests to the /customer endpoint.
 *
 * @author   Sébastien Dumont
 * @category API
 * @package  CoCart Pro/API
 * @since    1.0.0
 * @version  1.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Cart Owner controller class.
 *
 * @package CoCart Pro/API
 */
class CoCart_Pro_Cart_Owner_Controller extends CoCart_API_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'customer';

	/**
	 * Register routes.
	 *
	 * @access public
	 */
	public function register_routes() {
		// Get Owner of Cart - cocart/v1/customer (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_customer' ),
				'permission_callback' => '__return_true',
			)
		) );
	} // register_routes()

	/**
	 * Cart Owner
	 *
	 * @access public
	 * @return WP_REST_Response
	 */
	public function get_customer() {
		$cart_customer = WC()->cart->get_customer();

		$response = apply_filters( 'cocart_customer_details', array(
			'user' => array(
				'ID'         => get_current_user_id(),
				'first_name' => $cart_customer->get_first_name(),
				'last_name'  => $cart_customer->get_last_name(),
			),
			'billing' => array(
				'first_name' => $cart_customer->get_billing_first_name(),
				'last_name'  => $cart_customer->get_billing_last_name(),
				'company'    => $cart_customer->get_billing_company(),
				'email'      => $cart_customer->get_billing_email(),
				'phone'      => $cart_customer->get_billing_phone(),
				'country'    => $cart_customer->get_billing_country(),
				'state'      => $cart_customer->get_billing_state(),
				'postcode'   => $cart_customer->get_billing_postcode(),
				'city'       => $cart_customer->get_billing_city(),
				'address'    => $cart_customer->get_billing_address(),
				'address_1'  => $cart_customer->get_billing_address_1(), // Provide both address and address_1 for backwards compatibility.
				'address_2'  => $cart_customer->get_billing_address_2(),
			),
			'shipping' => array(
				'first_name' => $cart_customer->get_shipping_first_name(),
				'last_name'  => $cart_customer->get_shipping_last_name(),
				'company'    => $cart_customer->get_shipping_company(),
				'country'    => $cart_customer->get_shipping_country(),
				'state'      => $cart_customer->get_shipping_state(),
				'postcode'   => $cart_customer->get_shipping_postcode(),
				'city'       => $cart_customer->get_shipping_city(),
				'address'    => $cart_customer->get_shipping_address(),
				'address_1'  => $cart_customer->get_shipping_address_1(), // Provide both address and address_1 for backwards compatibility.
				'address_2'  => $cart_customer->get_shipping_address_2(),
			),
			'has_calculated_shipping' => $cart_customer->has_calculated_shipping(),
			'is_vat_exempt'           => $cart_customer->get_is_vat_exempt() ? 'yes' : 'no',
		), $cart_customer );

		return new WP_REST_Response( $response, 200 );
	} // END get_customer()

} // END class
