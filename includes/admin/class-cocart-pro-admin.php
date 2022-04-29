<?php
/**
 * CoCart Pro - Admin.
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

if ( ! class_exists( 'CoCart_Pro_Admin' ) ) {

	class CoCart_Pro_Admin {

		/**
		 * Constructor
		 *
		 * @access public
		 */
		public function __construct() {
			// Include classes.
			self::includes();

			// Filters Getting Started Documentation URL.
			add_filter( 'cocart_getting_started_doc_url', function() { return COCART_PRO_DOCUMENTATION_URL; } );

			// Includes the deactivation modal.
			add_action( 'wp_ajax_cocart_pro_feedback_modal', array( $this, 'ajax_cocart_pro_feedback_modal' ) );
			add_action( 'admin_footer', array( $this, 'deactivation_modal' ), 9999 );

			// Licence Manager
			add_filter( 'cocart_page_title_instructions', function() { return sprintf( esc_attr__( 'Licence Manager for %s', 'cocart-pro' ), 'CoCart Pro' ); } );
			add_action( 'cocart_page_section_instructions', array( $this, 'licence_manager' ) );

			// Filter plugins to showing only CoCart Add-ons on the plugins page by status.
			add_action( is_multisite() ? 'views_plugins-network' : 'views_plugins', array( $this, 'cocart_addons_plugin_status_link' ) );
			add_action( 'pre_current_active_plugins', array( $this, 'cocart_addons_filter_plugins_by_status' ) );

		} // END __construct()

		/**
		 * Include any classes we need within admin.
		 *
		 * @access public
		 */
		public function includes() {
			include( COCART_PRO_FILE_PATH . '/includes/admin/class-cocart-pro-admin-action-links.php' ); // Action Links
			include( COCART_PRO_FILE_PATH . '/includes/admin/class-cocart-pro-admin-assets.php' );  // Admin Assets
			include( COCART_PRO_FILE_PATH . '/includes/admin/class-cocart-pro-admin-notices.php' ); // Plugin Notices
			include( COCART_PRO_FILE_PATH . '/includes/admin/class-cocart-pro-admin-updater.php' ); // Plugin Updater
		} // END includes()

		/**
		 * Checks if CoCart is installed.
		 *
		 * @access public
		 * @static
		 */
		public static function is_cocart_installed() {
			$active_plugins = (array) get_option( 'active_plugins', array() );

			if ( is_multisite() ) {
				$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
			}
	
			return in_array( 'cart-rest-api-for-woocommerce/cart-rest-api-for-woocommerce.php', $active_plugins ) || array_key_exists( 'cart-rest-api-for-woocommerce/cart-rest-api-for-woocommerce.php', $active_plugins );
		} // END is_cocart_installed()

		/**
		 * Checks if CoCart Enhanced is installed.
		 *
		 * @access public
		 * @static
		 */
		public static function is_cocart_enhanced_installed() {
			$active_plugins = (array) get_option( 'active_plugins', array() );

			if ( is_multisite() ) {
				$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
			}
	
			return in_array( 'cocart-get-cart-enhanced\cocart-get-cart-enhanced.php', $active_plugins ) || array_key_exists( 'cocart-get-cart-enhanced\cocart-get-cart-enhanced.php', $active_plugins );
		}// END is_cocart_enhanced_installed()


		/**
		 * Licence Manager content.
		 *
		 * @access public
		 */
		public function licence_manager() {
			include_once( dirname( __FILE__ ) . '/views/html-licence-manager.php' );
		} // END licence_manager()

		/**
		 * These are the only screens CoCart will focus 
		 * on displaying notices or equeue scripts/styles.
		 *
		 * @access public
		 * @static
		 * @return array
		 */
		public static function cocart_get_admin_screens() {
			return array(
				'dashboard',
				'plugins',
				'toplevel_page_cocart'
			);
		} // END cocart_get_admin_screens()

		/**
		 * Returns true if CoCart Pro is a beta/pre-release.
		 *
		 * @access public
		 * @static
		 * @return boolean
		 */
		public static function is_cocart_pro_beta() {
			if ( 
				strpos( COCART_PRO_VERSION, 'beta' ) ||
				strpos( COCART_PRO_VERSION, 'rc' )
			) {
				return true;
			}

			return false;
		} // END is_cocart_pro_beta()

		/**
		 * Seconds to words.
		 *
		 * Forked from: https://github.com/thatplugincompany/login-designer/blob/master/includes/admin/class-login-designer-feedback.php
		 *
		 * @access public
		 * @static
		 * @param  string $seconds Seconds in time.
		 * @return string
		 */
		public static function cocart_seconds_to_words( $seconds ) {
			// Get the years.
			$years = ( intval( $seconds ) / YEAR_IN_SECONDS ) % 100;
			if ( $years > 1 ) {
				/* translators: Number of years */
				return sprintf( __( '%s years', 'cocart-pro' ), $years );
			} elseif ( $years > 0 ) {
				return __( 'a year', 'cocart-pro' );
			}

			// Get the months.
			$months = ( intval( $seconds ) / MONTH_IN_SECONDS ) % 52;
			if ( $months > 1 ) {
				return sprintf( __( '%s months ago', 'cocart-pro' ), $months );
			} elseif ( $months > 0 ) {
				return __( '1 month ago', 'cocart-pro' );
			}

			// Get the weeks.
			$weeks = ( intval( $seconds ) / WEEK_IN_SECONDS ) % 52;
			if ( $weeks > 1 ) {
				/* translators: Number of weeks */
				return sprintf( __( '%s weeks', 'cocart-pro' ), $weeks );
			} elseif ( $weeks > 0 ) {
				return __( 'a week', 'cocart-pro' );
			}

			// Get the days.
			$days = ( intval( $seconds ) / DAY_IN_SECONDS ) % 7;
			if ( $days > 1 ) {
				/* translators: Number of days */
				return sprintf( __( '%s days', 'cocart-pro' ), $days );
			} elseif ( $days > 0 ) {
				return __( 'a day', 'cocart-pro' );
			}

			// Get the hours.
			$hours = ( intval( $seconds ) / HOUR_IN_SECONDS ) % 24;
			if ( $hours > 1 ) {
				/* translators: Number of hours */
				return sprintf( __( '%s hours', 'cocart-pro' ), $hours );
			} elseif ( $hours > 0 ) {
				return __( 'an hour', 'cocart-pro' );
			}

			// Get the minutes.
			$minutes = ( intval( $seconds ) / MINUTE_IN_SECONDS ) % 60;
			if ( $minutes > 1 ) {
				/* translators: Number of minutes */
				return sprintf( __( '%s minutes', 'cocart-pro' ), $minutes );
			} elseif ( $minutes > 0 ) {
				return __( 'a minute', 'cocart-pro' );
			}

			// Get the seconds.
			$seconds = intval( $seconds ) % 60;
			if ( $seconds > 1 ) {
				/* translators: Number of seconds */
				return sprintf( __( '%s seconds', 'cocart-pro' ), $seconds );
			} elseif ( $seconds > 0 ) {
				return __( 'a second', 'cocart-pro' );
			}
		} // END cocart_seconds_to_words()

		/**
		 * Sends feedback details from deactivation modal via email.
		 * 
		 * @todo   Include customers licence details if set once licence manager is complete.
		 * @access public
		 */
		public function ajax_cocart_pro_feedback_modal() {
			$reason   = isset( $_POST['fb_reason'] ) ? trim( $_POST['fb_reason'] ) : '';
			$details  = isset( $_POST['fb_details'] ) ? sanitize_text_field( $_POST['fb_details'] ) : '';

			$user_id  = get_current_user_id();
			$userdata = get_userdata( $user_id );

			$from     = get_option('admin_email');
			$send_to  = sanitize_email( "hello@cocart.xyz" );
			$subject  = 'Customer Deactivated CoCart Pro';

			$message  = '<strong>Reason:</strong> ' . $reason;

			if ( ! empty( $details ) ) {
				$message .= '<br><strong>Details:</strong> ' . $details;
			}

			$headers  = array(
				'Content-Type: text/html; charset=UTF-8',
				'From: ' . $userdata->display_name . ' <' . $from . '>'
			);

			$email    = wp_mail( $send_to, $subject, $message, $headers );

			if ( $email ) {
				return true;
			}

			return false;
		} // END ajax_cocart_pro_feedback_modal()

		/**
		 * Includes the deactivation modal.
		 * 
		 * @access public
		 */
		public function deactivation_modal() {
			include_once( COCART_PRO_FILE_PATH . '/includes/admin/views/html-modal-deactivation.php' );
		} // END deactivation_modal()

		/**
		 * Add views for CoCart Add-ons only.
		 *
		 * This is modeled on `WP_Plugins_List_Table::get_views()`.
		 *
		 * @access public
		 * @param  array $status_links - Plugin statuses before.
		 * @return array $status_links - Plugin statuses after.
		 */
		public function cocart_addons_plugin_status_link( $status_links ) {
			if ( ! current_user_can( 'update_plugins' ) ) {
				return $status_links;
			}

			$cocart_addons_installed = get_site_option( 'cocart_addons_installed', array() );
			$count                   = count( $cocart_addons_installed );

			$counts = array(
				'cocart_addons' => $count,
			);

			// we can't use the global $status set in WP_Plugin_List_Table::__construct() because
			// it will be 'all' for our "custom status".
			$status = isset( $_REQUEST['plugin_status'] ) ? $_REQUEST['plugin_status'] : 'all';

			foreach ( $counts as $type => $count ) {
				if ( 0 === $count ) {
					continue;
				}
				switch( $type ) {
					case 'cocart_addons':
						/* translators: %s: Number of add-ons. */
						$text = _n(
							'CoCart Add-ons Installed <span class="count">(%s)</span>',
							'CoCart Add-ons Installed <span class="count">(%s)</span>',
							$count,
							'cocart-pro'
						);
				}

				$status_links[ $type ] = sprintf(
					"<a href='%s'%s>%s</a>",
					add_query_arg( 'plugin_status', $type, 'plugins.php' ),
					( $type === $status ) ? ' class="current" aria-current="page"' : '',
					sprintf( $text, number_format_i18n( $count ) )
				);
			}

			// make the 'all' status link not current if our "custom status" is current.
			if ( in_array( $status, array_keys( $counts ) ) ) {
				$status_links['all'] = str_replace( ' class="current" aria-current="page"', '', $status_links['all'] );
			}

			return $status_links;
		} // END cocart_addons_plugin_status_link()

		/**
		 * Filter plugins shown in the list table when status is 'cocart_addons'.
		 *
		 * This is modeled on `WP_Plugins_List_Table::prepare_items()`.
		 *
		 * @access public
		 * @param  array                 $plugins - List of plugins before they are filtered.
		 * @global WP_Plugins_List_Table $wp_list_table - The global list table object.  Set in `wp-admin/plugins.php`.
		 * @global int                   $page          - The current page of plugins displayed.  Set in WP_Plugins_List_Table::__construct().
		 */
		public function cocart_addons_filter_plugins_by_status( $plugins ) {
			global $wp_list_table, $page;

			$custom_status = 'cocart_addons';

			if ( ! ( isset( $_REQUEST['plugin_status'] ) && $_REQUEST['plugin_status'] == $custom_status ) ) {
				// current request is not for our status.
				// nothing to do, so bail.
				return;
			}

			$cocart_addons_installed = get_site_option( 'cocart_addons_installed', array() );
			$_plugins = array();

			foreach ( $plugins as $plugin_file => $plugin_data ) {
				switch ( $_REQUEST['plugin_status'] ) {
					case 'cocart_addons':
						if ( in_array( $plugin_file, $cocart_addons_installed ) ) {
							$_plugins[ $plugin_file ] = _get_plugin_data_markup_translate( $plugin_file, $plugin_data, false, true );
						}
						break;
				}
			}

			// set the list table's items array to just those plugins with our custom status.
			$wp_list_table->items = $_plugins;

			// now, update the pagination properties of the list table accordingly.
			$total_this_page = count( $_plugins );

			$plugins_per_page = $wp_list_table->get_items_per_page( str_replace( '-', '_', $wp_list_table->screen->id . '_per_page' ), 999 );

			$start = ( $page - 1 ) * $plugins_per_page;

			if ( $total_this_page > $plugins_per_page ) {
				$wp_list_table->items = array_slice( $wp_list_table->items, $start, $plugins_per_page );
			}

			$wp_list_table->set_pagination_args(
				array(
					'total_items' => $total_this_page,
					'per_page'    => $plugins_per_page,
				)
			);

			return;
		} // END cocart_addons_filter_plugins_by_status()

	} // END class

} // END if class exists

return new CoCart_Pro_Admin();
