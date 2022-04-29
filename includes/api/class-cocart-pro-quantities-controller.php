<?php
/**
 * CoCart Pro - Cart Item Quantities controller
 *
 * Handles requests to the /quantities endpoint.
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
 * REST API Cart Item Quantities controller class.
 *
 * @package CoCart Pro/API
 */
class CoCart_Pro_Quantities_Controller extends CoCart_API_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'quantities';

	/**
	 * Register routes.
	 *
	 * @access public
	 */
	public function register_routes() {
		// Get Quantities of Cart - cocart/v1/quantities (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item_quantities' ),
				'permission_callback' => '__return_true',
			)
		) );
	} // register_routes()

	/**
	 * Cart Item Quantities
	 *
	 * @access public
	 * @return WP_REST_Response
	 */
	public function get_item_quantities() {
		$cart_quantities = apply_filters( 'cocart_quantities', WC()->cart->get_cart_item_quantities() );

		return new WP_REST_Response( $cart_quantities, 200 );
	} // END get_quantities()

} // END class
