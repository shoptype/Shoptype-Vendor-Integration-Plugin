<?php
/**
 * CoCart Pro - Admin Action Links.
 *
 * Adds links to CoCart Pro on the plugins page.
 *
 * @author   Sébastien Dumont
 * @category Admin
 * @package  CoCart Pro/Admin
 * @license  GPL-2.0+
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CoCart_Pro_Admin_Action_Links' ) ) {

	class CoCart_Pro_Admin_Action_Links {

		/**
		 * Constructor
		 *
		 * @access public
		 */
		public function __construct() {
			add_filter( 'plugin_action_links_' . plugin_basename( COCART_PRO_FILE ), array( $this, 'plugin_action_links' ) );
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta'), 10, 3 );
		} // END __construct()

		/**
		 * Plugin action links.
		 *
		 * @access public
		 * @param  array $links An array of plugin links.
		 * @return array $links
		 */
		public function plugin_action_links( $links ) {
			if ( current_user_can( 'manage_options' ) ) {
				$action_links = array(
					'instructions' => '<a href="' . add_query_arg( array( 'page' => 'cocart', 'section' => 'instructions' ), admin_url( 'admin.php' ) ) . '" aria-label="' . sprintf( esc_attr__( 'Licence Manager for %s', 'cocart-pro' ), 'CoCart Pro' ) . '">' . esc_attr__( 'Instructions', 'cocart-pro' ) . '</a>',
				);

				return array_merge( $action_links, $links );
			}

			return $links;
		} // END plugin_action_links()

		/**
		 * Plugin row meta links
		 *
		 * @access public
		 * @param  array  $metadata An array of the plugin's metadata.
		 * @param  string $file     Path to the plugin file.
		 * @param  array  $data     Plugin Information
		 * @return array  $metadata
		 */
		public function plugin_row_meta( $metadata, $file, $data ) {
			if ( $file == plugin_basename( COCART_PRO_FILE ) ) {
				$metadata[ 1 ] = sprintf( __( 'Developed By %s', 'cocart-pro' ), '<a href="' . $data[ 'AuthorURI' ] . '" aria-label="' . esc_attr__( 'View the developers site', 'cocart-pro' ) . '">' . $data[ 'Author' ] . '</a>' );

				$row_meta = array(
					'docs'      => '<a href="' . esc_url( "https://shoptype.freshdesk.com/support/solutions" ) . '" aria-label="' . sprintf( esc_attr__( 'View %s documentation', 'cocart-pro' ), 'CoCart Pro' ) . '" target="_blank">' . esc_attr__( 'Documentation', 'cocart-pro' ) . '</a>',
				);

				$metadata = array_merge( $metadata, $row_meta );
			}

			return $metadata;
		} // END plugin_row_meta()

	} // END class

} // END if class exists

return new CoCart_Pro_Admin_Action_Links();
