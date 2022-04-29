<?php
/**
 * CoCart Pro - Coupon controller
 *
 * Handles requests to the /coupon endpoint.
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
 * REST API Coupon controller class.
 *
 * @package CoCart Pro/API
 */
class CoCart_Pro_Coupon_Controller extends CoCart_API_Controller {

	/**
	 * Register routes.
	 *
	 * @access public
	 */
	public function register_routes() {
		// Get Applied Coupons, Apply Coupon, Remove Coupon - cocart/v1/coupon (GET, POST, DELETE)
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/coupon', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_applied_coupons' ),
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'apply_coupon' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'coupon' => array(
						'required'          => true,
						'description'       => __( 'Coupon to apply to the cart.', 'cocart-pro' ),
						'type'              => 'string',
						'validate_callback' => function( $param, $request, $key ) {
							return is_string( $param );
						}
					),
				),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'remove_coupon' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'coupon' => array(
						'required'          => true,
						'description'       => __( 'Coupon to remove from the cart.', 'cocart-pro' ),
						'type'              => 'string',
						'validate_callback' => function( $param, $request, $key ) {
							return is_string( $param );
						}
					),
				),
			)
		) );

		// Checks all applied coupons to see if they are still valid. - cocart/v1/check-coupons (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/check-coupons', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'check_coupons' ),
				'permission_callback' => '__return_true',
			)
		) );
	} // register_routes()

	/**
	 * Checks if coupons are enabled in WooCommerce.
	 *
	 * @access public
	 * @return bool
	 */
	public function are_coupons_enabled() {
		return wc_coupons_enabled();
	} // END are_coupons_enabled()

	/**
	 * Get Applied Coupons.
	 *
	 * @access public
	 * @param  array $data
	 * @return array|WP_REST_Response
	 */
	public function get_applied_coupons( $data = array() ) {
		// Check if coupons are enabled first!
		if ( ! $this->are_coupons_enabled() ) {
			return new WP_Error( 'cocart_pro_coupons_not_enabled', __( 'Coupons are not enabled for this store!', 'cocart-pro' ), array( 'status' => 500 ) );
		}

		if ( ! empty( WC()->cart->get_applied_coupons() ) ) {
			$applied_coupons = WC()->cart->get_applied_coupons();
		} else {
			$applied_coupons = WC()->session->get( 'applied_coupons' );
		}

		$show_raw = ! empty( $data['raw'] ) ? $data['raw'] : false;

		// Return applied coupons raw if requested.
		if ( $show_raw ) {
			return $applied_coupons;
		}

		return new WP_REST_Response( $applied_coupons, 200 );
	} // END get_applied_coupons()

	/**
	 * Apply coupon to cart.
	 *
	 * @access public
	 * @param  array $data
	 * @return WP_Error|WP_REST_Response
	 */
	public function apply_coupon( $data = array() ) { 
		// Check if coupons are enabled first!
		if ( ! $this->are_coupons_enabled() ) {
			return new WP_Error( 'cocart_pro_coupons_not_enabled', __( 'Coupons are not enabled for this store!', 'cocart-pro' ), array( 'status' => 500 ) );
		}

		$coupon = isset( $data['coupon'] ) ? wc_format_coupon_code( wp_unslash( $data['coupon'] ) ) : '';

		if ( ! empty( $coupon ) ) {
			$applied_coupons = $this->get_applied_coupons( array( 'raw' => true ) );

			foreach ( $applied_coupons as $code ) {
				if ( $coupon == $code ) {
					return new WP_REST_Response( array(
						'message'  => __( 'Coupon already applied to cart.', 'cocart-pro' ),
						'coupon'   => $coupon,
						'response' => true
					), 200 );
				}
			}

			$apply_coupon = WC()->cart->apply_coupon( $coupon );

			if ( $apply_coupon ) {
				return new WP_REST_Response( array(
					'message'  => __( 'Coupon was successfully added to cart.', 'cocart-pro' ),
					'coupon'   => $coupon,
					'response' => true
				), 200 );
			} else {
				return new WP_Error( 'cocart_pro_coupon_failed_to_apply', __( 'Coupon failed to apply to cart.', 'cocart-pro' ), array( 'status' => 500 ) );
			}
		} else {
			return new WP_Error( 'cocart_pro_coupon_required', __( 'Coupon is required in order to apply!', 'cocart-pro' ), array( 'status' => 500 ) );
		}
	} // END apply_coupon()
	
	/**
	 * Remove existing coupon from cart.
	 *
	 * @access public
	 * @param  array $data
	 * @return WP_Error|WP_REST_Response
	 */
	public function remove_coupon( $data = array() ) { 
		// Check if coupons are enabled first!
		if ( ! $this->are_coupons_enabled() ) {
			return new WP_Error( 'cocart_pro_coupons_not_enabled', __( 'Coupons are not enabled for this store!', 'cocart-pro' ), array( 'status' => 500 ) );
		}

		$coupon = isset( $data['coupon'] ) ? wc_format_coupon_code( wp_unslash( $data['coupon'] ) ) : '';

		if ( ! empty( $coupon ) ) {
			$remove_coupon = WC()->cart->remove_coupon( $coupon );

			if ( $remove_coupon ) {
				return new WP_REST_Response( array(
					'message'  => __( 'Coupon was successfully removed from cart.', 'cocart-pro' ),
					'coupon'   => $coupon,
					'response' => true
				), 200 );
			} else {
				return new WP_Error( 'cocart_pro_coupon_failed_to_remove', __( 'Coupon failed to remove from cart.', 'cocart-pro' ), array( 'status' => 500 ) );
			}
		} else {
			return new WP_Error( 'cocart_pro_coupon_required', __( 'Coupon is required in order to apply!', 'cocart-pro' ), array( 'status' => 500 ) );
		}
	} // END remove_coupon()

	/**
	 * Check all applied coupons to see if they are still valid.
	 *
	 * @access public
	 * @return WP_Error|WP_REST_Response
	 */
	public function check_coupons() {
		// Check if coupons are enabled first!
		if ( ! $this->are_coupons_enabled() ) {
			return new WP_Error( 'cocart_pro_coupons_not_enabled', __( 'Coupons are not enabled for this store!', 'cocart-pro' ), array( 'status' => 500 ) );
		}

		$all_valid       = true;
		$invalid_coupons = array();

		$applied_coupons = $this->get_applied_coupons( array( 'raw' => true ) );

		foreach ( $applied_coupons as $code ) {
			$coupon = new WC_Coupon( $code );

			// If coupon is not valid remove coupon.
			if ( ! $coupon->is_valid() ) {
				$all_valid = false;
				$invalid_coupons[] = $code;

				WC()->cart->remove_coupon( $code );
			}
		}

		$invalid_coupons = implode( ", ", $invalid_coupons );

		if ( $all_valid ) {
			return new WP_REST_Response( array(
				'message'  => __( 'Coupons applied are still valid!', 'cocart-pro' ),
				'response' => true
			), 200 );
		} else {
			return new WP_Error( 'cocart_pro_coupons_expired', sprintf( __( 'The following coupons have expired or no longer valid: %s', 'cocart-pro' ), $invalid_coupons ), array( 'status' => 500 ) );
		}
	} // END check_coupons()

} // END class
