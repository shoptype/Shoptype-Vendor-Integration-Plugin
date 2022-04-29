<?php
/**
 * CoCart Pro - Removed Items controller
 *
 * Handles requests to the /removed-items endpoint.
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
 * REST API Removed Items controller class.
 *
 * @package CoCart Pro/API
 */
class CoCart_Pro_Removed_Items_Controller extends CoCart_API_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'removed-items';

	/**
	 * Register routes.
	 *
	 * @access public
	 */
	public function register_routes() {
		// Get Removed Cart Contents - cocart/v1/removed-items (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_removed_items' ),
				'permission_callback' => '__return_true',
			)
		) );
	} // register_routes()

	/**
	 * Removed Cart Contents
	 *
	 * @access public
	 * @return WP_REST_Response
	 */
	public function get_removed_items() {
		if ( ! empty( WC()->cart->get_removed_cart_contents() ) ) {
			$removed_items = WC()->cart->get_removed_cart_contents();
		} else {
			$removed_items = WC()->session->get( 'removed_cart_contents' );
		}

		return new WP_REST_Response( apply_filters( 'cocart_return_removed_cart_contents', $removed_items ), 200 );
	} // END get_removed_items()

} // END class
