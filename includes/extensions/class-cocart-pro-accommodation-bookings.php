<?php
/**
 * CoCart Pro - Accommodation Bookings Extension
 *
 * Adds the check-in/check-out information to the cart when returned.
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
 * Accommodation Bookings Extension class.
 *
 * @package CoCart Pro/Extensions
 */
class CoCart_Pro_Accommodation_Bookings_Extension {

	/**
	 * Setup class.
	 *
	 * @access public
	 */
	public function __construct() {
		add_filter( 'cocart_cart_contents', array( $this, 'get_item_data' ), 10, 4 );
	} // END __construct()

	/**
	 * Returns the check-in/check-out info when getting the cart.
	 *
	 * @access public
	 * @param  array  $cart_contents
	 * @param  int    $item_key
	 * @param  array  $cart_item
	 * @param  object $_product
	 * @return array  $cart_contents
	 */
	public function get_item_data( $cart_contents, $item_key, $cart_item, $_product ) {
		if ( 'accommodation-booking' === $cart_item['data']->get_type() ) {
			$check_in  = WC_Product_Accommodation_Booking::get_check_times( 'in' );
			$check_out = WC_Product_Accommodation_Booking::get_check_times( 'out' );
			$end_date  = date_i18n( get_option( 'date_format'), $cart_item['booking']['_end_date'] );

			if ( ! empty( $check_in ) ) {
				$cart_contents[$item_key]['check_in'] = esc_html( $cart_item['booking']['date'] . __( ' at ', 'cocart-pro' ) . date_i18n( get_option( 'time_format' ), strtotime( "Today " . $check_in ) ) );
			}

			if ( ! empty( $check_out ) ) {
				$cart_contents[$item_key]['check_out'] = esc_html( $end_date . __( ' at ', 'cocart-pro' ) . date_i18n( get_option( 'time_format' ), strtotime( "Today " . $check_out ) ) );
			}
		}

		return $cart_contents;
	} // END get_item_date()

} // END class

return new CoCart_Pro_Accommodation_Bookings_Extension();