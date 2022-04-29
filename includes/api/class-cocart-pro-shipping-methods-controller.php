<?php
/**
 * CoCart Pro - Shipping Methods controller
 *
 * Handles requests to the /shipping-methods endpoint.
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
 * REST API Shipping Methods controller class.
 *
 * @package CoCart Pro/API
 */
class CoCart_Pro_Shipping_Methods_Controller extends CoCart_API_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'shipping-methods';

	/**
	 * Register routes.
	 *
	 * @access public
	 */
	public function register_routes() {
		// Get and Set Shipping Methods for Cart - cocart/v1/shipping-methods (GET, POST)
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_shipping_methods' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'recurring_cart_key' => array(
						'description'       => __( 'Recurring cart key is required only to get shipping methods for that subscription.', 'cocart-pro' ),
						'type'              => 'string',
						'validate_callback' => function( $param, $request, $key ) {
							return is_string( $param );
						}
					),
				),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'set_shipping_method' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'key' => array(
						'required'          => true,
						'validate_callback' => function( $param, $request, $key ) {
							return is_string( $param );
						}
					),
				)
			),
		) );
	} // register_routes()

	/**
	 * Get shipping methods for the current cart.
	 *
	 * @access public
	 * @static
	 * @param  array $data
	 * @return WP_Error|WP_REST_Response
	 */
	public static function get_shipping_methods( $data = array() ) {
		// Return nothing if the cart is empty.
		/*if ( CoCart_Count_Items_Controller::get_cart_contents_count( array( 'return' => 'numeric' ) ) <= 0 ) {
			return new WP_REST_Response( array(), 200 );
		}*/

		if ( ! wc_shipping_enabled() || 0 === wc_get_shipping_method_count( true ) ) {
			return new WP_Error( 'cocart_pro_shipping_not_enabled', __( 'Shipping has not been enabled for this store!', 'cocart-pro' ), array( 'status' => 500 ) );
		}

		// Are we requesting to get shipping methods for a subscription?
		$recurring_cart_key = isset( $data['recurring_cart_key'] ) ? wc_clean( $data['recurring_cart_key'] ) : '';

		$cart = WC()->cart;

		// Check if the cart needs shipping.
		if ( ! $cart->needs_shipping() ) {
			return new WP_Error( 'cocart_pro_shipping_not_needed', __( 'Cart does not contain an item that requires shipping.', 'cocart-pro' ), array( 'status' => 500 ) );
		}

		if ( 'yes' === get_option( 'woocommerce_shipping_cost_requires_address' ) ) {
			if ( ! $cart->get_customer()->has_calculated_shipping() ) {

				// Sees if the customer has entered enough data to calculate shipping yet.
				if ( ! $cart->get_customer()->get_shipping_country() || ( ! $cart->get_customer()->get_shipping_state() && ! $cart->get_customer()->get_shipping_postcode() ) ) {
					return new WP_Error( 'cocart_pro_shipping_not_calculated', __( 'Customer has not calculated shipping.', 'cocart-pro' ), array( 'status' => 500 ) );
				}
			}
		}

		// Calculate shipping.
		$cart->calculate_shipping();

		// Get chosen shipping methods if any set.
		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

		// Check that the recurring cart key exists before continuing.
		if ( ! empty( $recurring_cart_key ) && ! array_key_exists( $recurring_cart_key, $chosen_shipping_methods ) ) {
			return new WP_Error( 'cocart_pro_no_recurring_cart_key', __( 'The recurring cart key does not exists.', 'cocart-pro' ), array( 'status' => 500 ) );
		}

		// Get shipping packages.
		$packages          = WC()->shipping->get_packages();
		$package           = $packages[0];
		$rates             = $package['rates'];
		$available_methods = array();

		// Check that there are rates available.
		if ( count( $rates ) < 1 ) {
			return new WP_Error( 'cocart_pro_no_shipping_methods', __( 'There are no shipping methods available!', 'cocart-pro' ), array( 'status' => 500 ) );
		}

		foreach ( $rates as $key => $method ) {
			$method_data = array(
				'key'           => $key,
				'method_id'     => $method->get_method_id(),
				'instance_id'   => $method->instance_id,
				'label'         => $method->get_label(),
				'cost'          => $method->cost,
				'html'          => html_entity_decode( strip_tags( wc_cart_totals_shipping_method_label( $method ) ) ),
				'taxes'         => $method->taxes,
				'chosen_method' => ($chosen_shipping_methods[0] === $key)
			);

			// Add available method to return.
			$available_methods[$key] = $method_data;
		}

		// Was it requested to return just available methods?
		if ( $data['return'] ) {
			return $available_methods;
		}

		return new WP_REST_Response( apply_filters( 'cocart_available_shipping_methods', $available_methods, $chosen_shipping_methods, $rates, $recurring_cart_key ), 200 );
	} // END get_shipping_methods()

	/**
	 * Set shipping method for the current cart.
	 *
	 * @access public
	 * @param  array $data
	 * @return WP_REST_Response
	 */
	public function set_shipping_method( $data = array() ) {
		// Return nothing if the cart is empty.
		/*if ( CoCart_Count_Items_Controller::get_cart_contents_count( array( 'return' => 'numeric' ) ) <= 0 ) {
			return new WP_REST_Response( array(), 200 );
		}*/

		if ( ! isset( $data['key'] ) ) {
			return new WP_Error( 'cocart_pro_set_shipping_method_failed', __( 'The shipping key is required to set a shipping method.', 'cocart-pro' ), array( 'status' => 500 ) );
		}

		// Shipping key is the shipping package you have chosen to set for the shipping method... if you get what I mean! :)
		$shipping_key       = wc_clean( wp_unslash( $data['key'] ) );

		// Are we requesting to set a chosen shipping method for a subscription?
		$recurring_cart_key = isset( $data['recurring_cart_key'] ) ? wc_clean( wp_unslash( $data['recurring_cart_key'] ) ) : '';

		// Get available shipping methods.
		$available_methods  = $this->get_shipping_methods( array( 'return' => true ) );

		// Validates the shipping method to see if it exists before setting it.
		if ( is_array( $available_methods ) && ! isset( $available_methods[ $shipping_key ] ) ) {
			return new WP_Error( 'cocart_pro_shipping_method_incorrect', __( 'The shipping method is either incorrect or does not exist.', 'cocart-pro' ), array( 'status' => 500 ) );
		}

		if ( ! empty( $recurring_cart_key ) ) {
			WC()->session->set( 'chosen_shipping_methods', array( $recurring_cart_key => $shipping_key ) );
		} else {
			WC()->session->set( 'chosen_shipping_methods', array( $shipping_key ) );
		}

		do_action( 'cocart_set_shipping_method', $shipping_key, $recurring_cart_key );

		return new WP_REST_Response( true, 200 );
	} // END set_shipping_method()

} // END class
