<?php
/*
 * Plugin Name: Shoptype Vendor Integration
 * Plugin URI:  https://shoptype.com
 * Description: Shoptype's CoCart Pro Integration completes the fulfillment and shipping integration.
 * Author:      Shoptype
 * Author URI:  https://shoptype.com
 * Version:     1.0.0-rc.4
 * Text Domain: Shoptype
 * Domain Path: /languages/
 
 * WC requires at least: 4.0.0
 * WC tested up to: 4.5.2
 *
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! class_exists( 'CoCart_Pro' ) ) {
	class CoCart_Pro {

		/**
		 * @var CoCart_Pro - the single instance of the class.
		 *
		 * @access protected
		 * @static
		 */
		protected static $_instance = null;

		/**
		 * Plugin Version
		 *
		 * @access public
		 * @static
		 */
		public static $version = '1.0.0-rc.4';

		/**
		 * Required CoCart Version
		 *
		 * @access public
		 * @static
		 */
		public static $required_cocart = '2.0.1';

		/**
		 * Cart Support instance.
		 *
		 * @var CoCart_Pro_Cart_Support
		 */
		public $cart_support = null;

		/**
		 * Main CoCart Pro Instance.
		 *
		 * Ensures only one instance of CoCart Pro is loaded or can be loaded.
		 *
		 * @access  public
		 * @static
		 * @see     CoCart_Pro()
		 * @return  CoCart_Pro - Main instance
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Cloning is forbidden.
		 *
		 * @access public
		 * @return void
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'Cloning this object is forbidden.', 'cocart-pro' ), self::$version );
		} // END __clone()

		/**
		 * Unserializing instances of this class is forbidden.
		 *
		 * @access public
		 * @return void
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'cocart-pro' ), self::$version );
		} // END __wakeup()

		/**
		 * Load the plugin.
		 *
		 * @access public
		 */
		public function __construct() {
			// Setup Constants.
			$this->setup_constants();

			// Include admin classes to handle all back-end functions.
			$this->admin_includes();

			// Include required files.
			add_action( 'init', array( $this, 'includes' ) );

			// Load translation files.
			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		} // END __construct()

		/**
		 * Setup Constants
		 *
		 * @access public
		 */
		public function setup_constants() {
			$this->define('COCART_PRO_VERSION', self::$version);
			$this->define('COCART_PRO_FILE', __FILE__);
			$this->define('COCART_PRO_SLUG', 'cocart-pro');

			$this->define('COCART_PRO_URL_PATH', untrailingslashit( plugins_url( '/', __FILE__ ) ) );
			$this->define('COCART_PRO_FILE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

			$this->define('COCART_STORE_URL', 'https://cocart.xyz/');
			$this->define('COCART_PRO_REVIEW_URL', 'https://cocart.xyz/submit-review/');
			$this->define('COCART_PRO_DOCUMENTATION_URL', 'https://docs.cocart.xyz/pro.html');
			$this->define('COCART_PRO_TRANSLATION_URL', 'https://translate.cocart.xyz/projects/cocart-pro/');
		} // END setup_constants()

		/**
		 * Define constant if not already set.
		 *
		 * @access private
		 * @param  string $name
		 * @param  string|bool $value
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		} // END define()

		/**
		 * Includes more REST-API for CoCart.
		 *
		 * @access public
		 * @return void
		 */
		public function includes() {
			include_once( COCART_PRO_FILE_PATH . '/includes/class-cocart-pro-autoloader.php' );
			include_once( COCART_PRO_FILE_PATH . '/includes/class-cocart-pro-cart-support.php' ); // Adds additional support for the cart.

			if ( is_null( $this->cart_support ) ) {
				$this->cart_support = new CoCart_Pro_Cart_Support();
			}

			include_once( COCART_PRO_FILE_PATH . '/includes/class-cocart-pro-init.php' ); // Loads the controllers for the REST-API.
		} // END includes()

		/**
		 * Include admin class to handle all back-end functions.
		 *
		 * @access public
		 * @return void
		 */
		public function admin_includes() {
			if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
				include_once( COCART_PRO_FILE_PATH . '/includes/admin/class-cocart-pro-admin.php' );
				require_once( COCART_PRO_FILE_PATH . '/includes/class-cocart-pro-install.php' ); // Install CoCart Pro.
			}
		} // END admin_includes()

		/**
		 * Make the plugin translation ready.
		 *
		 * Translations should be added in the WordPress language directory:
		 *      - WP_LANG_DIR/plugins/cocart-pro-LOCALE.mo
		 *
		 * @access public
		 * @return void
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'cocart-pro', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		} // END load_plugin_textdomain()

	} // END class

} // END if class exists

/**
 * Returns the main instance of CoCart Pro.
 *
 * @return CoCart Pro
 */
function CoCart_Pro() {
	return CoCart_Pro::instance();
}

// Run CoCart Pro
CoCart_Pro();