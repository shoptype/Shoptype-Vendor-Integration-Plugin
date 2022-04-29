<?php
/**
 * CoCart Pro - Calculate controller
 *
 * Handles requests to the /calculate endpoint.
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
 * REST API Calculate controller class.
 *
 * @package CoCart Pro/API
 */
class CoCart_Pro_Calculate_Controller extends CoCart_API_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'calculate';

	/**
	 * Line items to calculate.
	 *
	 * @var array
	 */
	protected $items = array();

	/**
	 * Fees to calculate.
	 *
	 * @var array
	 */
	protected $fees = array();

	/**
	 * Shipping costs.
	 *
	 * @var array
	 */
	protected $shipping = array();

	/**
	 * Should taxes be calculated?
	 *
	 * @var boolean
	 */
	protected $calculate_tax = true;

	/**
	 * Register routes.
	 *
	 * @access public
	 */
	public function register_routes() {
		// Calculate Cart Total - cocart/v1/calculate (POST)
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'calculate_totals' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'return' => array(
					'default'     => false,
					'description' => __( 'Returns the cart totals once calculated.', 'cocart-pro' ),
					'type'        => 'bool',
				)
			),
		), true );

		// Calculate Cart Fees - cocart/v1/calculate/fees (POST)
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/fees', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'calculate_fees' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'set_session' => array(
					'default'     => false,
					'description' => __( 'Sets the cart fees in session once calculated.', 'cocart-pro' ),
					'type'        => 'bool',
				),
				'return' => array(
					'default'     => false,
					'description' => __( 'Returns the cart fees once calculated.', 'cocart-pro' ),
					'type'        => 'bool',
				)
			),
		) );

		// Calculate Shipping - cocart/v1/calculate/shipping (POST)
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/shipping', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'calculate_shipping' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'country' => array(
					'required'          => true,
					'description'       => __( 'Country is a required minimum to calculate shipping.', 'cocart-pro' ),
					'type'              => 'string',
					'validate_callback' => function( $param, $request, $key ) {
						return is_string( $param );
					}
				),
				'state' => array(
					'description'       => __( 'State is optional but maybe required under some circumstances.', 'cocart-pro' ),
					'type'              => 'string',
					'validate_callback' => function( $param, $request, $key ) {
						return is_string( $param );
					}
				),
				'city' => array(
					'description'       => __( 'Enter City to specify location in country.', 'cocart-pro' ),
					'type'              => 'string',
					'validate_callback' => function( $param, $request, $key ) {
						return is_string( $param );
					}
				),
				'postcode' => array(
					'description'       => __( 'Enter postcode / ZIP to narrow down location for more accurate shipping cost.', 'cocart-pro' ),
					'type'              => 'string',
					'validate_callback' => function( $param, $request, $key ) {
						return is_string( $param );
					}
				),
				'return_methods' => array(
					'default'     => false,
					'description' => __( 'If set to true it will return shipping methods available once shipping is calculated.', 'cocart-pro' ),
					'type'        => 'bool',
				),
			)
		) );
	} // register_routes()

	/**
	 * Calculate Cart Totals.
	 *
	 * @access public
	 * @param  array $data
	 * @return WP_REST_Response
	 */
	public function calculate_totals( $data = array() ) {
		// Return nothing if the cart is empty.
		/*if ( CoCart_Count_Items_Controller::get_cart_contents_count( array( 'return' => 'numeric' ) ) <= 0 ) {
			return new WP_REST_Response( __( 'No items in cart to calculate totals.', 'cocart-pro' ), 200 );
		}*/

		$cart_customer = WC()->cart->get_customer();

		/**
		 * Calculate shipping again before totals. This will ensure any shipping methods that affect things 
		 * like taxes are chosen prior to final totals being calculated.
		 */
		if ( $cart_customer->has_calculated_shipping() ) {
			WC()->cart->calculate_shipping();
		}

		// Calculate totals.
		WC()->cart->calculate_totals();

		// Calculate fees.
		$this->calculate_fees( array( 'set_session' => true ) );

		// Was it requested to return all totals once calculated?
		if ( $data['return'] ) {
			$totals = CoCart_Pro_Totals_Controller::get_totals();

			return new WP_REST_Response( $totals, 200 );
		}

		return new WP_REST_Response( __( 'Cart totals have been calculated.', 'cocart-pro' ), 200 );
	} // END calculate_totals()

	/**
	 * Calculate Cart Fees.
	 *
	 * @access public
	 * @param  array $data
	 * @return WP_REST_Response
	 */
	public function calculate_fees( $data = array() ) {
		// Return nothing if the cart is empty.
		/*if ( CoCart_Count_Items_Controller::get_cart_contents_count( array( 'return' => 'numeric' ) ) <= 0 ) {
			return new WP_REST_Response( __( 'No items in cart to calculate fees.', 'cocart-pro' ), 200 );
		}*/

		$this->fees = array();

		WC()->cart->calculate_fees();

		$fee_running_total = 0;

		$fees = array_merge( WC()->cart->get_fees(), CoCart_Pro()->cart_support->get_all_fees() );

		foreach ( $fees as $fee_key => $fee_object ) {
			$fee            = (object) array(
				'object'    => null,
				'tax_class' => '',
				'taxable'   => false,
				'total_tax' => 0,
				'taxes'     => array(),
				'total'     => 0
			);

			$fee->object    = (array) $fee_object;
			$fee->tax_class = $fee->object['tax_class'];
			$fee->taxable   = $fee->object['taxable'];

			$amount         = CoCart_Pro()->cart_support->get_fee_data( $fee_key, 'amount' );
			$fee->total     = wc_add_number_precision_deep( $amount );

			// Negative fees should not make the order total go negative.
			if ( 0 > $fee->total ) {
				$max_discount = round( $this->get_total( 'items_total', true ) + $fee_running_total + $this->get_total( 'shipping_total', true ) ) * -1;

				if ( $fee->total < $max_discount ) {
					$fee->total = $max_discount;
				}
			}

			$fee_running_total += $fee->total;

			$calculate_tax = wc_tax_enabled() && ! WC()->cart->get_customer()->get_is_vat_exempt();

			if ( $calculate_tax ) {
				if ( 0 > $fee->total ) {
					// Negative fees should have the taxes split between all items so it works as a true discount.
					$tax_class_costs = $this->get_tax_class_costs( $fees );
					$total_cost      = array_sum( $tax_class_costs );

					if ( $total_cost ) {
						foreach ( $tax_class_costs as $tax_class => $tax_class_cost ) {
							if ( 'non-taxable' === $tax_class ) {
								continue;
							}
							$proportion               = $tax_class_cost / $total_cost;
							$cart_discount_proportion = $fee->total * $proportion;
							$fee->taxes               = wc_array_merge_recursive_numeric( $fee->taxes, WC_Tax::calc_tax( $fee->total * $proportion, WC_Tax::get_rates( $tax_class ) ) );
						}
					}
				} elseif ( $fee->taxable ) {
					$fee->taxes = WC_Tax::calc_tax( $fee->total, WC_Tax::get_rates( $fee->tax_class, WC()->cart->get_customer() ), false );
				}
			}

			$fee->total_tax = array_sum( array_map( array( $this, 'round_line_tax' ), $fee->taxes ) );

			// Set totals within object.
			$fee->object['total']    = wc_remove_number_precision_deep( $fee->total );
			$fee->object['tax_data'] = wc_remove_number_precision_deep( $fee->taxes );
			$fee->object['tax']      = wc_remove_number_precision_deep( $fee->total_tax );

			$this->fees[ $fee_key ] = $fee;
		} // END foreach

		//WC()->cart->fees_api()->set_fees( wp_list_pluck( $this->fees, 'object' ) ); // Is this needed?
		WC()->session->set( 'fee_total', wc_remove_number_precision_deep( array_sum( wp_list_pluck( $this->fees, 'total' ) ) ) );
		WC()->session->set( 'fee_tax', wc_remove_number_precision_deep( array_sum( wp_list_pluck( $this->fees, 'total_tax' ) ) ) );
		WC()->session->set( 'fee_taxes', wc_remove_number_precision_deep( $this->combine_item_taxes( wp_list_pluck( $this->fees, 'taxes' ) ) ) );

		// If set, then just update session.
		if ( $data['set_session'] ) {
			return;
		}

		// Was it requested to return all fees once calculated?
		if ( $data['return'] ) {
			$fees = CoCart_Pro_Fees_Controller::get_fees();

			return new WP_REST_Response( $fees, 200 );
		}

		return new WP_REST_Response( __( 'Cart fees have been calculated.', 'cocart-pro' ), 200 );
	} // END calculate_fees()

	// The following functions help calculate the cart fees. //

	/**
	 * Handles a cart or order object passed in for calculation. Normalises data
	 * into the same format for use by this class.
	 *
	 * Each item is made up of the following props, in addition to those returned by get_default_item_props() for totals.
	 *  - key: An identifier for the item (cart item key or line item ID).
	 *  - cart_item: For carts, the cart item from the cart which may include custom data.
	 *  - quantity: The qty for this line.
	 *  - price: The line price in cents.
	 *  - product: The product object this cart item is for.
	 *
	 * Note: This function was forked from the WC_Cart_Totals class and tweaked for CoCart Pro.
	 * 
	 * @access protected
	 */
	protected function get_items_from_cart() {
		$this->items = array();

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$item = (object) array(
				'object'             => null,
				'tax_class'          => '',
				'taxable'            => false,
				'quantity'           => 0,
				'product'            => false,
				'price_includes_tax' => false,
				'subtotal'           => 0,
				'subtotal_tax'       => 0,
				'subtotal_taxes'     => array(),
				'total'              => 0,
				'total_tax'          => 0,
				'taxes'              => array(),
			);

			$item->key                     = $cart_item_key;
			$item->object                  = $cart_item;
			$item->tax_class               = $cart_item['data']->get_tax_class();
			$item->taxable                 = 'taxable' === $cart_item['data']->get_tax_status();
			$item->price_includes_tax      = wc_prices_include_tax();
			$item->quantity                = $cart_item['quantity'];
			$item->price                   = wc_add_number_precision_deep( $cart_item['data']->get_price() * $cart_item['quantity'] );
			$item->product                 = $cart_item['data'];
			$item->tax_rates               = $this->get_item_tax_rates( $item );

			$this->items[ $cart_item_key ] = $item;
		}
	} // END get_items_from_cart()

	/**
	 * Get tax rates for an item. Caches rates in class to avoid multiple look ups.
	 *
	 * Note: This function was forked from the WC_Cart_Totals class.
	 * 
	 * @access protected
	 * @param  object $item Item to get tax rates for.
	 * @return array of taxes
	 */
	protected function get_item_tax_rates( $item ) {
		if ( ! wc_tax_enabled() ) {
			return array();
		}

		$tax_class      = $item->product->get_tax_class();
		$item_tax_rates = isset( $this->item_tax_rates[ $tax_class ] ) ? $this->item_tax_rates[ $tax_class ] : $this->item_tax_rates[ $tax_class ] = WC_Tax::get_rates( $item->product->get_tax_class(), $this->cart->get_customer() );

		// Allow plugins to filter item tax rates.
		return apply_filters( 'woocommerce_cart_totals_get_item_tax_rates', $item_tax_rates, $item, $this->cart );
	} // END get_item_tax_rates()

	/**
	 * Get item costs grouped by tax class.
	 *
	 * Note: This function was forked from the WC_Cart_Totals class.
	 * 
	 * @access protected
	 * @return array
	 */
	protected function get_tax_class_costs( $fees ) {
		$item_tax_classes     = wp_list_pluck( $this->items, 'tax_class' );
		$shipping_tax_classes = wp_list_pluck( $this->shipping, 'tax_class' );
		$fee_tax_classes      = wp_list_pluck( $fees, 'tax_class' );
		$costs                = array_fill_keys( $item_tax_classes + $shipping_tax_classes + $fee_tax_classes, 0 );
		$costs['non-taxable'] = 0;

		foreach ( $items + $fees + $shipping as $item ) {
			if ( 0 > $item->total ) {
				continue;
			}
			if ( ! $item->taxable ) {
				$costs['non-taxable'] += $item->total;
			} elseif ( 'inherit' === $item->tax_class ) {
				$costs[ reset( $item_tax_classes ) ] += $item->total;
			} else {
				$costs[ $item->tax_class ] += $item->total;
			}
		}

		return array_filter( $costs );
	} // END get_tax_class_costs()

	/**
	 * Get shipping methods from the cart and normalise.
	 *
	 * Note: This function was forked from the WC_Cart_Totals class and tweaked for CoCart Pro.
	 * 
	 * @access protected
	 */
	protected function get_shipping_from_cart() {
		$this->shipping = array();

		if ( ! WC()->cart->show_shipping() ) {
			return;
		}

		foreach ( WC()->cart->calculate_shipping() as $key => $shipping_object ) {
			$shipping_line = (object) array(
				'object'    => null,
				'tax_class' => '',
				'taxable'   => false,
				'total'     => 0,
				'total_tax' => 0,
				'taxes'     => array(),
			);

			$shipping_line->object    = $shipping_object;
			$shipping_line->tax_class = get_option( 'woocommerce_shipping_tax_class' );
			$shipping_line->taxable   = true;
			$shipping_line->total     = wc_add_number_precision_deep( $shipping_object->cost );
			$shipping_line->taxes     = wc_add_number_precision_deep( $shipping_object->taxes, false );
			$shipping_line->total_tax = array_sum( array_map( array( $this, 'round_line_tax' ), $shipping_line->taxes ) );

			$this->shipping[ $key ] = $shipping_line;
		}
	} // END get_shipping_from_cart()

	/**
	 * Get a single total with or without precision (in cents).
	 *
	 * Note: This function was forked from the WC_Cart_Totals class.
	 * 
	 * @access public
	 * @param  string $key Total to get.
	 * @param  bool   $in_cents Should the totals be returned in cents, or without precision.
	 * @return int|float
	 */
	public function get_total( $key = 'total', $in_cents = false ) {
		$totals = $this->get_totals( $in_cents );

		return isset( $totals[ $key ] ) ? $totals[ $key ] : 0;
	} // END get_total()

	/**
	 * Get all totals with or without precision (in cents).
	 *
	 * Note: This function was forked from the WC_Cart_Totals class and tweaked for CoCart Pro.
	 * 
	 * @access public
	 * @param  bool $in_cents Should the totals be returned in cents, or without precision.
	 * @return array
	 */
	public function get_totals( $in_cents = false ) {
		$totals = CoCart_Pro_Totals_Controller::get_totals();

		return $in_cents ? $totals : wc_remove_number_precision_deep( $totals );
	} // END get_totals()

	/**
	 * Combine item taxes into a single array, preserving keys.
	 *
	 * Note: This function was forked from the WC_Cart_Totals class.
	 *
	 * @access protected
	 * @param  array $item_taxes   - Taxes to combine.
	 * @return array $merged_taxes - Taxes combined.
	 */
	protected function combine_item_taxes( $item_taxes ) {
		$merged_taxes = array();

		foreach ( $item_taxes as $taxes ) {
			foreach ( $taxes as $tax_id => $tax_amount ) {
				if ( ! isset( $merged_taxes[ $tax_id ] ) ) {
					$merged_taxes[ $tax_id ] = 0;
				}
				$merged_taxes[ $tax_id ] += $tax_amount;
			}
		}

		return $merged_taxes;
	} // END combine_item_taxes()

	/**
	 * Apply rounding to an array of taxes before summing. Rounds to store DP setting, ignoring precision.
	 *
	 * Note: This function was forked from the WC_Cart_Totals class.
	 *
	 * @access protected
	 * @param  float $value Tax value.
	 * @return float $value
	 */
	protected function round_line_tax( $value ) {
		if ( ! $this->round_at_subtotal() ) {
			$value = wc_round_tax_total( $value, 0 );
		}

		return $value;
	} // END round_line_tax()

	/**
	 * Should we round at subtotal level only?
	 *
	 * Note: This function was forked from the WC_Cart_Totals class.
	 *
	 * @access protected
	 * @return bool
	 */
	protected function round_at_subtotal() {
		return 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' );
	} // END round_at_subtotal()

	// The functions above help calculate the cart fees. //

	/**
	 * Calculate Shipping.
	 *
	 * @access public
	 * @param  array $data
	 * @return WP_Error|WP_REST_Response
	 */
	public function calculate_shipping( $data = array() ) {
		// Return nothing if the cart is empty.
		/*if ( CoCart_Count_Items_Controller::get_cart_contents_count( array( 'return' => 'numeric' ) ) <= 0 ) {
			return new WP_REST_Response( array(), 200 );
		}*/

		WC()->shipping()->reset_shipping();

		$address = array();

		$address['country']  = isset( $data['country'] ) ? wc_clean( wp_unslash( $data['country'] ) ) : '';
		$address['state']    = isset( $data['state'] ) ? wc_clean( wp_unslash( $data['state'] ) ) : '';
		$address['city']     = isset( $data['city'] ) ? wc_clean( wp_unslash( $data['city'] ) ) : '';
		$address['postcode'] = isset( $data['postcode'] ) ? wc_clean( wp_unslash( $data['postcode'] ) ) : '';

		if ( $address['postcode'] && ! WC_Validation::is_postcode( $address['postcode'], $address['country'] ) ) {
			return new WP_Error( 'cocart_pro_invalid_postcode', __( 'Please enter a valid postcode / ZIP.', 'cocart-pro' ), array( 'status' => 500 ) );
		} elseif ( $address['postcode'] ) {
			$address['postcode'] = wc_format_postcode( $address['postcode'], $address['country'] );
		}

		if ( $address['country'] ) {
			WC()->customer->set_billing_location( $address['country'], $address['state'], $address['postcode'], $address['city'] );
			WC()->customer->set_shipping_location( $address['country'], $address['state'], $address['postcode'], $address['city'] );
		} else {
			WC()->customer->set_billing_address_to_base();
			WC()->customer->set_shipping_address_to_base();
		}

		WC()->customer->set_calculated_shipping( true );
		WC()->customer->save();

		// Return shipping methods once calculated.
		if ( $data['return_methods'] ) {
			return CoCart_Pro_Shipping_Methods_Controller::get_shipping_methods( $data );
		}

		return new WP_REST_Response( __( 'Shipping costs updated.', 'cocart-pro' ), 200 );
	} // END calculate_shipping()

} // END class
