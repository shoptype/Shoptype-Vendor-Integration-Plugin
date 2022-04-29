<?php
/**
 * CoCart Pro - Cart Support.
 *
 * Handles additional cart support for CoCart Pro.
 *
 * @author   Sébastien Dumont
 * @category Classes
 * @package  CoCart Pro/Classes/Cart Support
 * @license  GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CoCart_Pro_Cart_Support' ) ) {

	/**
	 * CoCart Pro Cart Support class.
	 */
	class CoCart_Pro_Cart_Support {

		/**
		 * Contains an array of fees.
		 *
		 * @var array
		 */
		public $cart_fees = array();

		/**
		 * New fees are made out of these props.
		 *
		 * @var array
		 */
		private $default_fee_props = array(
			'id'        => '',
			'name'      => '',
			'amount'    => 0,
			'taxable'   => false,
			'tax_class' => '',
		);

		/**
		 * Sets up cart fees and calculates them when called.
		 *
		 * @access public
		 */
		public function __construct() {
			// Session.
			add_action( 'woocommerce_load_cart_from_session', array( $this, 'load_session' ) );
			add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'override_totals' ) );

			add_action( 'woocommerce_cart_emptied', array( $this, 'destroy_session' ) );
			add_action( 'woocommerce_cart_updated', array( $this, 'set_session' ) );

			// Calculating fees.
			add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_all_fees' ), 99 );
		} // END __construct()

		/**
		 * Get cart fee data from the PHP session and store it in a class variable.
		 *
		 * @access public
		 */
		public function load_session() {
			$this->set_fees( WC()->session->get( 'cart_fees', null ) );
		} // END load_session()

		/**
		 * Override the cart fees total, fee tax and fee taxes from session.
		 *
		 * @access public
		 */
		public function override_totals() {
			WC()->cart->set_fee_total( WC()->session->get( 'fee_total', null ) );
			WC()->cart->set_fee_tax( WC()->session->get( 'fee_tax', null ) );
			WC()->cart->set_fee_taxes( WC()->session->get( 'fee_taxes', null ) );
		} // END override_totals()

		/**
		 * Destroy cart fees session data.
		 *
		 * @access public
		 */
		public function destroy_session() {
			WC()->session->set( 'cart_fees', null );
			WC()->session->set( 'fee_total', null );
			WC()->session->set( 'fee_tax', null );
			WC()->session->set( 'fee_taxes', null );
		} // END destroy_session()

		/**
		 * Sets the PHP session data for the cart fees.
		 *
		 * @access public
		 */
		public function set_session() {
			WC()->session->set( 'cart_fees', $this->get_all_fees() );
		} // END set_session()

		/**
		 * For each fee stored, we add when calculated.
		 *
		 * @access public
		 */
		public function add_all_fees() {
			if ( empty( $this->cart_fees ) ) {
				return;
			}

			foreach ( $this->cart_fees as $fee ) {
				WC()->cart->add_fee( $fee['name'], $fee['amount'], $fee['taxable'], $fee['tax_class'] );
			}
		} // END add_all_fees()

		/**
		 * Stores the cart fee so it can be added when calculated.
		 *
		 * @access public
		 * @param  array  $args - Array of fee properties.
		 * @return object Either a fee array if stored, or a WP_Error if it failed.
		 */
		public function store_fee( $args = array() ) {
			$fee_props            = (object) wp_parse_args( $args, $this->default_fee_props );
			$fee_props->name      = $fee_props->name ? $fee_props->name : __( 'Fee', 'cocart-pro' );
			$fee_props->tax_class = in_array( $fee_props->tax_class, array_merge( WC_Tax::get_tax_classes(), WC_Tax::get_tax_class_slugs() ), true ) ? $fee_props->tax_class : '';
			$fee_props->taxable   = wc_string_to_bool( $fee_props->taxable );
			$fee_props->amount    = wc_format_decimal( $fee_props->amount );
			$fee_props->total     = wc_add_number_precision_deep( $fee_props->amount );

			// If no fee ID set then create one.
			if ( empty( $fee_props->id ) ) {
				$fee_props->id = 'cocart-' . sanitize_title( $fee_props->name );
			}

			// If fee was already added then return false.
			if ( array_key_exists( $fee_props->id, $this->cart_fees ) ) {
				return false;
			}

			// Combine fee to the array of fees.
			$this->cart_fees[ $fee_props->id ] = (array) $fee_props;

			// Updates the cart fees in session.
			$this->set_fees( $this->cart_fees );

			return $this->cart_fees[ $fee_props->id ];
		} // END store_fee()

		/**
		 * Get's all stored fees.
		 *
		 * @access public
		 */
		public function get_all_fees() {
			if ( ! empty( $this->cart_fees ) ) {
				uasort( $this->cart_fees, array( $this, 'sort_fees_callback' ) );
			}

			return ! empty( $this->cart_fees ) ? $this->cart_fees : array();
		} // END get_all_fees()

		/**
		 * Get's a single variable value from a fee.
		 *
		 * @access public
		 * @param  $id      - The ID of the fee.
		 * @param  $key     - The variable to return.
		 * @return var|null - Returns the variable value if any.
		 */
		public function get_fee_data( $id, $key ) {
			$fees = $this->get_all_fees();

			return isset( $fees[$id] ) ? $fees[$id][$key] : null;
		} // END get_fee_data()

		/**
		 * Sets the cart fees in session.
		 *
		 * @access public
		 */
		public function set_fees( $value ) {
			$this->cart_fees = $value;

			WC()->session->set( 'cart_fees', $this->cart_fees );
		} // END set_fees()

		/**
		 * Removes all stored fees and destroy session.
		 *
		 * @access public
		 */
		public function remove_all_fees() {
			$this->cart_fees = array();

			$this->destroy_session();
		} // END remove_all_fees()

		/**
		 * Sort fees by amount.
		 *
		 * @param $a Fee object.
		 * @param $b Fee object.
		 * @return int
		 */
		protected function sort_fees_callback( $a, $b ) {
			return ( $a['amount'] > $b['amount'] ) ? -1 : 1;
		} // END sort_fees_callback()

	} // END class

} // END if class exists.
