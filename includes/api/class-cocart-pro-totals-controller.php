<?php
/**
 * CoCart Pro - Totals controller
 *
 * Handles requests to the /totals endpoint.
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
 * REST API Totals controller class.
 *
 * @package CoCart Pro/API
 */
class CoCart_Pro_Totals_Controller extends CoCart_API_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'totals';

	/**
	 * Register routes.
	 *
	 * @access public
	 */
	public function register_routes() {
		// Get Cart Totals - cocart/v1/totals (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_totals' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'html' => array(
					'default' => false,
				),
			),
		), true );

		// Get Discount Total - cocart/v1/totals/discount (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/discount', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_discount_total' ),
				'permission_callback' => '__return_true',
			)
		) );

		// Get Discount Totals - cocart/v1/totals/discount/coupon-totals (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/discount/coupon-totals', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_coupon_discount_totals' ),
				'permission_callback' => '__return_true',
			)
		) );

		// Get Discount Tax Totals - cocart/v1/totals/discount/coupon-tax (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/discount/coupon-tax', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_coupon_discount_tax_totals' ),
				'permission_callback' => '__return_true',
			)
		) );

		// Get Fee Total - cocart/v1/totals/fee/total (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/fee', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_fee_total' ),
				'permission_callback' => '__return_true',
			)
		) );

		// Get Fee Tax - cocart/v1/totals/fee/tax (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base  . '/fee/tax', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_fee_tax' ),
				'permission_callback' => '__return_true',
			)
		) );

		// Get Shipping Total - cocart/v1/totals/shipping (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/shipping', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_shipping_total' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'calculate' => array(
						'default' => false,
					),
				),
			)
		) );

		// Get Shipping Tax - cocart/v1/totals/shipping/tax (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/shipping/tax', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_shipping_tax' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'calculate' => array(
						'default' => false,
					),
				),
			)
		) );

		// Get Subtotal - cocart/v1/totals/subtotal (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/subtotal', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_subtotal' ),
				'permission_callback' => '__return_true',
			)
		) );

		// Get Subtotal Tax - cocart/v1/totals/subtotal/tax (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base  . '/subtotal/tax', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_subtotal_tax' ),
				'permission_callback' => '__return_true',
			)
		) );

		// Get Cart Total Tax - cocart/v1/totals/tax (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/tax', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_tax' ),
			'permission_callback' => '__return_true',
		) );

		// Get Cart Total - cocart/v1/totals/total (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/total', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_total' ),
			'permission_callback' => '__return_true',
		) );
	} // register_routes()

	/**
	 * Returns all calculated totals including fees.
	 *
	 * @access public
	 * @param  array $data
	 * @return WP_REST_Response
	 */
	public function get_totals( $data =array() ) {
		if ( ! empty( WC()->cart->totals ) ) {
			$totals = WC()->cart->get_totals();
		} else {
			$totals = WC()->session->get( 'cart_totals' );
		}

		// Add cart fees to the totals to view their details.
		$totals['fees'] = array_merge( WC()->cart->get_fees(), CoCart_Pro()->cart_support->get_all_fees() );

		$pre_formatted = ! empty( $data['html'] ) ? wc_string_to_bool( $data['html'] ) : false;

		if ( $pre_formatted ) {
			$new_totals = array();

			$ignore_convert = array(
				'shipping_taxes',
				'cart_contents_taxes',
				'fee_taxes',
				'fees'
			);

			foreach( $totals as $type => $sum ) {
				if ( in_array( $type, $ignore_convert ) ) {
					$new_totals[$type] = $sum;
				} else {
					if ( is_string( $sum ) ) {
						$new_totals[$type] = html_entity_decode( strip_tags( wc_price( $sum ) ) );
					}
					else {
						$new_totals[$type] = html_entity_decode( strip_tags( wc_price( strval( $sum ) ) ) );
					}
				}
			}

			$totals = $new_totals;
		}

		return new WP_REST_Response( apply_filters( 'cocart_totals', $totals ), 200 );
	} // END get_totals()

	/**
	 * Returns discount total.
	 *
	 * @access public
	 * @return WP_REST_Response
	 */
	public function get_discount_total() {
		$discount_total = html_entity_decode( strip_tags( wc_price( WC()->cart->get_discount_total() ) ) );

		return new WP_REST_Response( $discount_total, 200 );
	} // END get_discount_total()

	/**
	 * Returns all coupons applied and their discount total.
	 *
	 * @access public
	 * @return WP_REST_Response
	 */
	public function get_coupon_discount_totals() {
		if ( ! empty( WC()->cart->get_coupon_discount_totals() ) ) {
			$discount_totals = WC()->cart->get_coupon_discount_totals();
		} else {
			$discount_totals = WC()->session->get( 'coupon_discount_totals' );
		}

		$formatted_discount_totals = array();

		foreach( $discount_totals as $coupon => $discount_total ) {
			$formatted_discount_totals[$coupon] = html_entity_decode( strip_tags( wc_price( $discount_total ) ) );
		}

		return new WP_REST_Response( $formatted_discount_totals, 200 );
	} // END get_coupon_discount_totals()

	/**
	 * Returns all coupon discount total tax.
	 *
	 * @access public
	 * @return WP_REST_Response
	 */
	public function get_coupon_discount_tax_totals() {
		if ( ! empty( WC()->cart->get_coupon_discount_tax_totals() ) ) {
			$totals = WC()->cart->get_coupon_discount_tax_totals();
		} else {
			$totals = WC()->session->get( 'coupon_discount_tax_totals' );
		}

		return new WP_REST_Response( html_entity_decode( strip_tags( $totals ) ), 200 );
	} // END get_coupon_discount_tax_totals()

	/**
	 * Returns cart fee total.
	 *
	 * @access public
	 * @return WP_REST_Response
	 */
	public function get_fee_total() {
		$total = html_entity_decode( strip_tags( wc_price( WC()->cart->get_fee_total() ) ) );

		return new WP_REST_Response( $total, 200 );
	} // END get_fee_total()

	/**
	 * Returns cart fee tax.
	 *
	 * @access public
	 * @return WP_REST_Response
	 */
	public function get_fee_tax() {
		$fee = html_entity_decode( strip_tags( WC()->cart->get_fee_tax() ) );

		return new WP_REST_Response( $fee, 200 );
	} // END get_fee_tax()

	/**
	 * Returns shipping total.
	 *
	 * @access public
	 * @param  array $data
	 * @return WP_REST_Response
	 */
	public function get_shipping_total( $data = array() ) {
		// Calculate shipping again before returning total if requested.
		if ( $data['calculate'] ) {
			$cart_customer = WC()->cart->get_customer();

			if ( $cart_customer->has_calculated_shipping() ) {
				WC()->cart->calculate_shipping();
			}

			WC()->cart->calculate_totals();
		}

		$total = html_entity_decode( strip_tags( wc_price( WC()->cart->get_shipping_total() ) ) );

		return new WP_REST_Response( $total, 200 );
	} // END get_shipping_total()

	/**
	 * Returns cart shipping tax.
	 *
	 * @access public
	 * @param  array $data
	 * @return WP_REST_Response
	 */
	public function get_shipping_tax( $data = array() ) {
		// Calculate shipping again before returning total if requested.
		if ( $data['calculate'] ) {
			$cart_customer = WC()->cart->get_customer();

			if ( $cart_customer->has_calculated_shipping() ) {
				WC()->cart->calculate_shipping();
			}

			WC()->cart->calculate_totals();
		}

		$tax = html_entity_decode( strip_tags( WC()->cart->get_shipping_tax() ) );

		return new WP_REST_Response( $tax, 200 );
	} // END get_shipping_tax()

	/**
	 * Returns subtotal.
	 *
	 * @access public
	 * @return WP_REST_Response
	 */
	public function get_subtotal() {
		$subtotal = html_entity_decode( strip_tags( wc_price( WC()->cart->get_subtotal() ) ) );

		return new WP_REST_Response( $subtotal, 200 );
	} // END get_subtotal()

	/**
	 * Returns subtotal tax.
	 *
	 * @access public
	 * @return WP_REST_Response
	 */
	public function get_subtotal_tax() {
		$tax = html_entity_decode( strip_tags( WC()->cart->get_subtotal_tax() ) );

		return new WP_REST_Response( $tax, 200 );
	} // END get_subtotal_tax()

	/**
	 * Returns cart total after calculation.
	 *
	 * @access public
	 * @return WP_REST_Response
	 */
	public function get_total() {
		$total = html_entity_decode( strip_tags( WC()->cart->get_total() ) );

		return new WP_REST_Response( $total, 200 );
	} // END get_total()

} // END class
