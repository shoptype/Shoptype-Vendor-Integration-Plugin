<?php
/**
 * CoCart Pro - Subscriptions Extension
 *
 * Returns subscription details of each added subscription 
 * and adds recurring shipping methods.
 *
 * @author   Sébastien Dumont
 * @category API
 * @package  CoCart Pro/Extensions
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Subscriptions Extension class.
 *
 * @package CoCart Pro/Extensions
 */
class CoCart_Pro_Subscriptions_Extension {

	/**
	 * Setup class.
	 *
	 * @access public
	 */
	public function __construct() {
		add_filter( 'cocart_cart_contents', array( $this, 'subscription_data' ), 10, 4 );
		add_filter( 'cocart_available_shipping_methods', array( $this, 'get_available_recurring_methods' ), 10, 4 );
		add_filter( 'cocart_add_to_cart_handler', array( $this, 'add_to_cart_handler' ), 10, 2 );
	} // END __construct()

	/**
	 * Returns subscription details of each added subscription.
	 *
	 * @access public
	 * @param  array  $cart_contents
	 * @param  int    $item_key
	 * @param  array  $cart_item
	 * @param  object $_product
	 * @return array  $cart_contents
	 */
	public function subscription_data( $cart_contents, $item_key, $cart_item, $_product ) {
		if ( WC_Subscriptions_Product::is_subscription( $_product ) ) {

			$cart_contents[$item_key]['subscription_data'] = array();

			// Get Product ID
			$product_id            = wcs_get_canonical_product_id( $_product );

			// Get Subscription Price
			$price                 = $_product->get_price();

			// Get Billing Period
			$interval              = WC_Subscriptions_Product::get_interval( $cart_item['data'] );
			$period                = WC_Subscriptions_Product::get_period( $cart_item['data'] );
			$length                = WC_Subscriptions_Product::get_length( $cart_item['data'] );
			$trial_period          = WC_Subscriptions_Product::get_trial_period( $cart_item['data'] );
			$trial_length          = WC_Subscriptions_Product::get_trial_length( $cart_item['data'] );
			$trial_expiration_date = WC_Subscriptions_Product::get_trial_expiration_date( $cart_item['data'] );
			$expiration_date       = WC_Subscriptions_Product::get_expiration_date( $cart_item['data'] );

			$cart_contents[$item_key]['subscription_data']['billing_period'] = array(
				'interval'              => $interval,
				'period'                => $period,
				'length'                => $length,
				'trial_period'          => $trial_period,
				'trial_length'          => $trial_length,
				'trial_expiration_date' => $trial_expiration_date,
				'expiration_date'       => $expiration_date
			);

			// Get Sign Up Fee
			$sign_up_fee = WC_Subscriptions_Product::get_sign_up_fee( $cart_item['data'] );

			// Extra check to make sure that the sign up fee is numeric before using it.
			$sign_up_fee = is_numeric( $sign_up_fee ) ? (float) $sign_up_fee : 0;

			$cart_contents[$item_key]['subscription_data']['sign_up_fee'] = html_entity_decode( strip_tags( wc_price( $sign_up_fee ) ) );

			// Set what to pay now according to trial length and sign up fee.
			if ( $trial_length > 0 ) {
				$cart_contents[$item_key]['subscription_data']['pay_now'] = html_entity_decode( strip_tags( wc_price( $sign_up_fee ) ) );
			} else {
				$cart_contents[$item_key]['subscription_data']['pay_now'] = html_entity_decode( strip_tags( wc_price( $price + $sign_up_fee ) ) );
			}

			// Get Need One Time Shipping
			$cart_contents[$item_key]['subscription_data']['needs_one_time_shipping'] = WC_Subscriptions_Product::needs_one_time_shipping( $cart_item['data'] );

			// Get Price String
			$cart_contents[$item_key]['subscription_data']['price_string'] = html_entity_decode( strip_tags( WC_Subscriptions_Product::get_price_string( $cart_item['data'], array(
				'price' => wc_price( $price ),
			) ) ) );

			// First Renewal
			$renewal_date = WC_Subscriptions_Product::get_first_renewal_payment_date( $product_id );
			$renewal_time = WC_Subscriptions_Product::get_first_renewal_payment_time( $product_id );

			$cart_contents[$item_key]['subscription_data']['first_renewal'] = array(
				'date'   => $renewal_date,
				'time'   => $renewal_time,
				'string' => self::first_renewal_payment_date( $renewal_date )
			);
		}

		return $cart_contents;
	} // END subscription_data()

	/**
	 * Returns the first renewal payment date.
	 *
	 * @access public
	 * @param  string $date
	 * @return string $payment_date
	 */
	public function first_renewal_payment_date( $date ) {
		$payment_date = '';

		if ( 0 !== $date ) {
			$first_renewal_date = date_i18n( wc_date_format(), wcs_date_to_time( get_date_from_gmt( $date ) ) );

			// translators: placeholder is a date
			$payment_date = sprintf( __( 'First renewal: %s', 'cocart-pro' ), $first_renewal_date );
		}

		return $payment_date;
	}

	/**
	 * Returns available recurring shipping methods.
	 *
	 * @access public
	 * @param  array  $available_methods
	 * @param  array  $chosen_shipping_methods
	 * @param  array  $rates
	 * @param  string $recurring_cart_key
	 * @return array  $available_methods
	 */
	public function get_available_recurring_methods( $available_methods, $chosen_shipping_methods, $rates, $recurring_cart_key ) {
		if ( ! empty( $recurring_cart_key ) && array_key_exists( $recurring_cart_key, $chosen_shipping_methods ) ) {
			$recurring_methods = array();

			foreach ( $rates as $id => $method ) {
				$method_data = array(
					'id'            => $id,
					'method_id'     => $method->get_method_id(),
					'instance_id'   => $method->instance_id,
					'label'         => $method->get_label(),
					'cost'          => $method->cost,
					'html'          => html_entity_decode( strip_tags( wc_cart_totals_shipping_method_label( $method ) ) ),
					'taxes'         => $method->taxes,
					'chosen_method' => ($chosen_shipping_methods[$recurring_cart_key] === $id)
				);

				// Add recurring method to return.
				$recurring_methods[] = $method_data;
			}

			$available_methods = $recurring_methods;
		}

		return $available_methods;
	}

	/**
	 * Use CoCart core add-to-cart handlers for subscription products.
	 *
	 * @param string     $handler - The name of the handler to use when adding product to the cart
	 * @param WC_Product $product
	 */
	public static function add_to_cart_handler( $handler, $product ) {
		if ( WC_Subscriptions_Product::is_subscription( $product ) ) {
			switch ( $handler ) {
				case 'variable-subscription' :
					$handler = 'variable';
					break;
				case 'subscription' :
					$handler = 'simple';
					break;
			}
		}

		return $handler;
	}

} // END class

return new CoCart_Pro_Subscriptions_Extension();