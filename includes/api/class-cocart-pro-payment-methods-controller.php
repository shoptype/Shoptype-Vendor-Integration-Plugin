<?php
/**
 * CoCart Pro - Payment Methods controller
 *
 * Handles requests to the /payment-methods endpoint.
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
 * REST API Payment Methods controller class.
 *
 * @package CoCart Pro/API
 */
class CoCart_Pro_Payment_Methods_Controller extends CoCart_API_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'payment-methods';

	/**
	 * Register routes.
	 *
	 * @access public
	 */
	public function register_routes() {
		// Get and Set Payment Methods for Cart - cocart/v1/payment-methods (GET,POST)
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_payment_methods' ),
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'set_payment_method' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'gateway_id' => array(
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
	 * Get available payment methods.
	 *
	 * @access public
	 * @param  array $data
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_payment_methods( $data = array() ) {
		// Return nothing if the cart is empty.
		/*if ( CoCart_Count_Items_Controller::get_cart_contents_count( array( 'return' => 'numeric' ) ) <= 0 ) {
			return new WP_REST_Response( array(), 200 );
		}*/

		// If the cart does not require payment then return response instead.
		if ( ! WC()->cart->needs_payment() ) {
			return new WP_REST_Response( apply_filters( 'cocart_no_payment_required', __( 'The cart does not require payment!', 'cocart-pro' ) ), 200 );
		}

		// Get chosen payment method.
		$chosen_payment_method = WC()->session->get( 'chosen_payment_method' );

		// Get available gateways.
		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

		if ( empty( $available_gateways ) ) {
			return new WP_Error( 'cocart_pro_no_payment_gateways', apply_filters( 'woocommerce_no_available_payment_methods_message', WC()->customer->get_billing_country() ? esc_html__( 'Sorry, it seems that there are no available payment methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.', 'cocart-pro' ) : esc_html__( 'Please fill in your details above to see available payment methods.', 'cocart-pro' ) ) , array( 'status' => 500 ) );
		}

		$gateways = array();

		foreach( $available_gateways as $gateway_id => $gateway ) {
			$gateways[$gateway_id] = array(
				'id'                => $gateway->id,
				'method_title'      => $gateway->method_title,
				'title'             => $gateway->get_title(),
				'description'       => $gateway->get_description(),
				'icon'              => $gateway->get_icon(),
				'icon_urls'         => $this->clean_gateway_icons( $gateway->get_icon() ),
				'has_fields'        => $gateway->has_fields,
				'countries'         => $gateway->countries,
				'availability'      => $gateway->availability,
				'supports'          => $gateway->supports,
				'order_button_text' => $gateway->order_button_text,
				'chosen_gateway'    => ($chosen_payment_method == $gateway_id),
			);
		}

		// Was it requested to return just available gateways?
		if ( $data['return'] ) {
			return $gateways;
		}

		return new WP_REST_Response( apply_filters( 'cocart_available_payment_methods', $gateways, $chosen_payment_method ), 200 );
	} // END get_payment_methods()

	/**
	 * Set payment method for the current cart.
	 *
	 * @access public
	 * @param  array $data
	 * @return WP_REST_Response
	 */
	public function set_payment_method( $data = array() ) {
		// Return nothing if the cart is empty.
		/*if ( CoCart_Count_Items_Controller::get_cart_contents_count( array( 'return' => 'numeric' ) ) <= 0 ) {
			return new WP_REST_Response( array(), 200 );
		}*/

		if ( ! isset( $data['gateway_id'] ) ) {
			return new WP_Error( 'cocart_pro_set_payment_method_failed', __( 'The gateway ID is required to set a payment method.', 'cocart-pro' ), array( 'status' => 500 ) );
		}

		$gateway_id = $data['gateway_id'];

		// Get available payment methods.
		$gateways = $this->get_payment_methods( array( 'return' => true ) );

		// Validates the payment gateway to see if it exists before setting it.
		if ( is_array( $gateways ) && isset( $gateways[ $gateway_id ] ) ) {
			WC()->session->set( 'chosen_payment_method', $gateway_id );

			do_action( 'cocart_set_payment_gateway', $gateway_id );

			return new WP_REST_Response( true, 200 );
		} else {
			return new WP_Error( 'cocart_pro_payment_method_incorrect', __( 'The gateway ID is either incorrect or does not exist.', 'cocart-pro' ), array( 'status' => 500 ) );
		}
	} // END set_payment_method()

	/**
	 * Cleans the source of the gateway icon so it returns 
	 * only the URL's of each icon if more than one.
	 *
	 * @access protected
	 * @param  string $icon - Before the source of the icon is cleaned.
	 * @return array $icon|$cleaned_icons - After the source of the icon is cleaned.
	 */
	protected function clean_gateway_icons( $icon ) {
		$icon = preg_replace('/<a (.*?)href=[\"\'](.*?)\/\/(.*?)[\"\'](.*?)>(.*?)<\/a>/i', "", $icon ); // Removes any links.

		$icons         = array();
		$cleaned_icons = array();

		preg_match_all('/src="([^"]*)"/', $icon, $matches); // Looks for any matching image src.

		if ( ! empty( $matches ) ) {
			$icons = $matches[1];

			// Cleans the URL's
			foreach( $icons as $icon ) {
				$cleaned_icons[] = esc_url( $icon );
			}
		}

		if ( ! empty( $cleaned_icons ) ) {
			return $cleaned_icons;
		}

		return $icon;
	} // END clean_gateway_icons()

} // END class
