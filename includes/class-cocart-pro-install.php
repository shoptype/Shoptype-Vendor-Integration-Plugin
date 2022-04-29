<?php
/**
 * CoCart Pro - Installation related functions and actions.
 *
 * @author   Sébastien Dumont
 * @category Classes
 * @package  CoCart Pro/Classes/Install
 * @license  GPL-2.0+
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CoCart_Pro_Install' ) ) {

	class CoCart_Pro_Install {

		/**
		 * Constructor.
		 *
		 * @access public
		 */
		public function __construct() {
			// Checks version of CoCart Pro and install/update if needed.
			add_action( 'init', array( $this, 'check_version' ), 5 );

			// Redirect to Getting Started page once activated.
			add_action( 'activated_plugin', array( $this, 'redirect_getting_started') );
		} // END __construct()

		/**
		 * Check plugin version and run the updater if necessary.
		 *
		 * This check is done on all requests and runs if the versions do not match.
		 *
		 * @access public
		 * @static
		 */
		public static function check_version() {
			if ( ! defined( 'IFRAME_REQUEST' ) && version_compare( get_option( 'cocart_pro_version' ), COCART_PRO_VERSION, '<' ) && current_user_can( 'install_plugins' ) ) {
				self::install();
				do_action( 'cocart_pro_updated' );
			}
		} // END check_version()

		/**
		 * Install CoCart.
		 *
		 * @access public
		 * @static
		 */
		public static function install() {
			if ( ! is_blog_installed() ) {
				return;
			}

			// Check if we are not already running this routine.
			if ( 'yes' === get_transient( 'cocart_pro_installing' ) ) {
				return;
			}

			// If we made it till here nothing is running yet, lets set the transient now for five minutes.
			set_transient( 'cocart_pro_installing', 'yes', MINUTE_IN_SECONDS * 5 );
			if ( ! defined( 'COCART_PRO_INSTALLING' ) ) {
				define( 'COCART_PRO_INSTALLING', true );
			}

			// Set activation date.
			self::set_install_date();

			// Update plugin version.
			self::update_version();

			delete_transient( 'cocart_pro_installing' );

			do_action( 'cocart_pro_installed' );
		} // END install()

		/**
		 * Update plugin version to current.
		 *
		 * @access private
		 * @static
		 */
		private static function update_version() {
			update_option( 'cocart_pro_version', COCART_PRO_VERSION );
		} // END update_version()

		/**
		 * Set the time the plugin was installed.
		 *
		 * @access public
		 * @static
		 */
		public static function set_install_date() {
			add_site_option( 'cocart_pro_install_date', time() );
		} // END set_install_date()

		/**
		 * Redirects to the Getting Started page upon plugin activation.
		 *
		 * @access public
		 * @static
		 * @param  string $plugin The activate plugin name.
		 */
		public static function redirect_getting_started( $plugin ) {
			// Prevent redirect if plugin name does not match or multiple plugins are being activated..
			if ( $plugin !== plugin_basename( COCART_PRO_FILE ) || isset( $_GET['activate-multi'] ) ) {
				return;
			}

			// Prevent redirect if CoCart is not installed.
			if ( ! CoCart_Pro_Admin::is_cocart_installed() ) {
				return;
			}
			

			// If CoCart Pro has already been installed before then don't redirect.
			if ( ! empty( get_option( 'cocart_pro_version' ) ) || ! empty( get_site_option( 'cocart_pro_install_date', time() ) ) ) {
				return;
			}

			$getting_started = add_query_arg( array( 
				'page'    => 'cocart', 
				'section' => 'instructions'
			), admin_url( 'admin.php' ) );

			$licence_manager = add_query_arg( array( 
				'page'    => 'cocart', 
				'section' => 'instructions'
			), admin_url( 'admin.php' ) );

			/**
			 * Should CoCart Pro be installed via WP-CLI,
			 * display a link to the Getting Started page and Licence Manager.
			 */
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				WP_CLI::log(
					WP_CLI::colorize(
						'%y' . sprintf( '🎉 %1$s %2$s', __( 'Get started with %3$s here:', 'cocart-pro' ), $getting_started, 'CoCart Pro' ) . '%n'
					)
				);

				WP_CLI::log(
					WP_CLI::colorize(
						'%y' . sprintf( '🔑 %1$s %2$s', __( 'Don`t forget to register your licence for %3$s to enable updates. ', 'cocart-pro' ), $licence_manager, 'CoCart Pro' ) . '%n'
					)
				);

				return;
			}

			// If activated on a Multi-site, don't redirect.
			if ( is_multisite() ) {
				return;
			}

			wp_safe_redirect( $getting_started );
			exit;
		} // END redirect_getting_started()
	} // END class.

} // END if class exists.

return new CoCart_Pro_Install();
