<?php
/**
 * CoCart Pro - Fees controller
 *
 * Handles requests to the /fees endpoint.
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
 * REST API Fees controller class.
 *
 * @package CoCart Pro/API
 */
class CoCart_Pro_Fees_Controller extends CoCart_API_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'fees';

	/**
	 * Register routes.
	 *
	 * @access public
	 */
	public function register_routes() {
		// Fees - cocart/v1/fees (GET, POST, DELETE)
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_fees' ),
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_fee' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'name' => array(
						'required'          => true,
						'description'       => __( 'Name of the fee.', 'cocart-pro' ),
						'type'              => 'string',
						'validate_callback' => function( $param, $request, $key ) {
							return is_string( $param );
						}
					),
					'amount' => array(
						'required'          => true,
						'description'       => __( 'Amount for the fee.', 'cocart-pro' ),
						'type'              => 'string',
						'validate_callback' => function( $param, $request, $key ) {
							return is_string( $param );
						}
					),
					'taxable' => array(
						'description' => __( 'Determins if the fee is taxable.', 'cocart-pro' ),
						'type'        => 'bool',
						'default'     => false,
					),
					'tax_class' => array(
						'description'       => __( 'The tax class the fee applies to.', 'cocart-pro' ),
						'type'              => 'string',
						'validate_callback' => function( $param, $request, $key ) {
							return is_string( $param );
						}
					),
				)
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'remove_fees' ),
				'permission_callback' => '__return_true',
			)
		) );
	} // register_routes()

	/**
	 * Get Fees
	 *
	 * @access public
	 */
	public function get_fees() {
		return CoCart_Pro()->cart_support->get_all_fees();
	} // END get_fees()

	/**
	 * Add Fee.
	 * 
	 * Stores the fee so it can be added when fees are calculated.
	 *
	 * @access public
	 * @param  array $data
	 * @return WP_Error|WP_REST_Response
	 */
	public function add_fee( $data = array() ) {
		$name      = isset( $data['name'] ) ? trim( $data['name'] ) : '';
		$amount    = isset( $data['amount'] ) ? trim( $data['amount'] ) : '';
		$taxable   = isset( $data['taxable'] ) ? $data['taxable'] : false;
		$tax_class = isset( $data['tax_class'] ) ? trim( $data['tax_class'] ) : '';

		// Validate fee parameters are set first before adding.
		if ( empty( $name ) ) {
			return new WP_Error( 'cocart_pro_add_fee_missing_name', __( 'The name of the fee is missing!', 'cocart-pro' ), array( 'status' => 500 ) );
		}

		if ( empty( $amount ) ) {
			return new WP_Error( 'cocart_pro_add_fee_missing_amount', __( 'The amount for the fee is missing!', 'cocart-pro' ), array( 'status' => 500 ) );
		}

		$count = WC()->cart->get_cart_contents_count();

		// If the cart has no items then return error.
		if ( $count < 1 ) {
			return new WP_Error( 'cocart_pro_cannot_add_fee', __( 'There is nothing in the cart so no fee can be added.', 'cocart-pro' ), array( 'status' => 500 ) );
		}

		$args = array(
			'name'      => $name,
			'amount'    => (float) $amount,
			'taxable'   => $taxable,
			'tax_class' => $tax_class,
		);

		// Stores fee once passed validation.
		$fee_added = CoCart_Pro()->cart_support->store_fee( $args );

		// If fee failed to add then return error.
		if ( ! $fee_added ) {
			return new WP_Error( 'cocart_pro_fee_already_exists', __( 'Fee has already been added.', 'cocart-pro' ), array( 'status' => 500 ) );
		}

		return new WP_REST_Response( sprintf( __( 'Fee "%1$s" for %2$s has been added.', 'cocart-pro' ), $name, html_entity_decode( strip_tags( wc_price( $amount ) ) ) ), 200 );
	} // END add_fee()

	/**
	 * Remove Fees
	 *
	 * @access public
	 * @return WP_Error|WP_REST_Response
	 */
	public function remove_fees() {
		// Removes all fees stored.
		CoCart_Pro()->cart_support->remove_all_fees();

		// Removes all fees added by 3rd parties.
		WC()->cart->fees_api()->remove_all_fees();

		if ( empty( $this->get_fees() ) ) {
			return new WP_REST_Response( __( 'All cart fees have been removed.', 'cocart-pro' ), 200 );
		}

		return new WP_Error( 'cocart_pro_removing_fees_failed', __( 'Cart fees failed to remove.', 'cocart-pro' ), array( 'status' => 500 ) );
	} // END remove_fees()

} // END class
