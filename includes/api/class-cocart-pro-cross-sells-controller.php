<?php
/**
 * CoCart Pro - Cross Sells controller
 *
 * Handles requests to the /cross-sells endpoint.
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
 * REST API Cross Sells controller class.
 *
 * @package CoCart Pro/API
 */
class CoCart_Pro_Cross_Sells_Controller extends CoCart_API_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'cross-sells';

	/**
	 * Register the routes for cart.
	 *
	 * @access public
	 */
	public function register_routes() {
		// Get Cross Sells - cocart/v1/cross-sells (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_cross_sells' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'thumb' => array(
						'description' => __( 'Returns the URL of the product image thumbnail.', 'cocart-pro' ),
						'default'     => false,
						'type'        => 'boolean',
					),
				),
			)
		) );
	} // register_routes()

	/**
	 * Returns Cross Sells items based on the items in the cart.
	 *
	 * @access public
	 * @param  array  $data
	 * @return WP_REST_Response
	 */
	public function get_cross_sells( $data = array() ) {
		$cross_sells = WC()->cart->get_cross_sells();

		$items = array();

		$show_thumb = ! empty( $data['thumb'] ) ? wc_string_to_bool( $data['thumb'] ) : false;

		foreach( $cross_sells as $item ) {
			$product_data = wc_get_product( $item );

			$items[$item] = array(
				'id'            => $product_data->get_id(),
				'product_name'  => $product_data->get_name(),
				'product_title' => $product_data->get_title(),
				'price'         => html_entity_decode( strip_tags( wc_price( $product_data->get_price() ) ) ),
				'regular_price' => html_entity_decode( strip_tags( wc_price( $product_data->get_regular_price() ) ) ),
				'sale_price'    => html_entity_decode( strip_tags( wc_price( $product_data->get_sale_price() ) ) )
			);

			// If the product thumbnail is requested then add it to each cross sell item.
			if ( $show_thumb ) {
				$thumbnail_id = apply_filters( 'cocart_cross_sell_item_thumbnail', $product_data->get_image_id() );

				$thumbnail_src = wp_get_attachment_image_src( $thumbnail_id, apply_filters( 'cocart_cross_sell_item_thumbnail_size', 'woocommerce_thumbnail' ) );

				// Add product image as a new variable.
				$items[$item]['product_image'] = esc_url( $thumbnail_src[0] );
			}

			// This filter allows additional data to be returned for a specific cross sell item.
			$items = apply_filters( 'cocart_cross_sells_data', $items, $item, $product_data );
		}

		// The cross sells are returned and can be filtered.
		return new WP_REST_Response( apply_filters( 'cocart_cross_sells', $items ), 200 );
	} // END get_cross_sells()

} // END class
