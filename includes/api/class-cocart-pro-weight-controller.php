<?php
/**
 * CoCart Pro - Cart Weight controller
 *
 * Handles requests to the /weight endpoint.
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
 * REST API Cart Weight controller class.
 *
 * @package CoCart Pro/API
 */
class CoCart_Pro_Weight_Controller extends CoCart_API_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'weight';

	/**
	 * Register routes.
	 *
	 * @access public
	 */
	public function register_routes() {
		// Get Weight of items in Cart - cocart/v1/weight (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_weight' ),
				'permission_callback' => '__return_true',
			)
		) );
	} // register_routes()

	/**
	 * Cart Weight
	 *
	 * @access public
	 * @return WP_REST_Response
	 */
	public function get_weight() {
		$weight      = WC()->cart->get_cart_contents_weight();
		$weight_unit = get_option( 'woocommerce_weight_unit' );

		$cart_weight = apply_filters( 'cocart_weight', sprintf( $weight . ' %s', $weight_unit ), $weight, $weight_unit );

		return new WP_REST_Response( $cart_weight, 200 );
	} // END get_weight()

} // END class
