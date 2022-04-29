<?php
/**
 * Admin Modal: Deactivation intent form template.
 *
 * @author   Sébastien Dumont
 * @category Admin
 * @package  CoCart
 * @license  GPL-2.0+
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plugin_file = plugin_basename( COCART_PRO_FILE );

$deactivation_url = wp_nonce_url( add_query_arg( array(
	'action' => 'deactivate',
	'plugin' => urlencode( $plugin_file )
), admin_url( 'plugins.php' ) ), 'deactivate-plugin_' . $plugin_file );

// Reasons
$reasons = array(
	array(
		'id'           => 'temporary',
		'value'        => __( 'Temporary Deactivation', 'cocart-pro' ),
		'label'        => sprintf( __( '%1$sIt is a temporary deactivation.%2$s I am just debugging an issue.', 'cocart-pro' ), '<strong>', '</strong>' ),
		'hidden_field' => 'no',
	),
	array(
		'id'           => 'too-complicated',
		'value'        => __( 'Too Complicated', 'cocart-pro' ),
		'label'        => sprintf( __( 'The plugin is %1$stoo complicated to configure.%2$s', 'cocart-pro' ), '<strong>', '</strong>' ),
		'hidden_field' => 'no',
		'reason'       => array(
			'title'   => __( 'The plugin is too complicated to configure.', 'cocart-pro' ),
			'content' => '<p>' . __( 'We are sorry to hear you are finding it difficult to use CoCart Pro.', 'cocart-pro' ) . '</p>' .
						'<p>' . __( 'CoCart Pro is the only plugin that enables you to handle the cart via the REST API.', 'cocart-pro' ) . '</p>' .
						'<p>' . sprintf( __( 'We do our best to make CoCart Pro provide all the support you require for handling items in the cart. If there is something we can do to help make it less complicated, %1$splease write to us%2$s and let us know.', 'cocart-pro' ), '<a href="https://cocart.xyz/feedback/" target="_blank">', '</a>' ) . '</p>',
		),
	),
	array(
		'id'           => 'varnish',
		'value'        => __( 'Unable to work with Varnish', 'cocart-pro' ),
		'label'        => sprintf( __( 'I\'m struggerling to get CoCart working with %1$sVarnish%2$s.', 'cocart-pro' ), '<strong>', '</strong>' ),
		'hidden_field' => 'no',
		'reason'       => array(
			'title'   => __( 'I\'m struggerling to get CoCart working with Varnish.', 'cocart-pro' ),
			'content' => '<p>' . __( 'If you are using Varnish you may need to apply a condition to allow CoCart to pass without caching.', 'cocart-pro' ) . '</p>' .
						'<p>' . sprintf( __( 'Press on %1$sApply Condition%2$s to read a guide to configure Varnish with WooCommerce.', 'cocart-pro' ), '<strong>', '</strong>' ) . '</p>' .
						'<div class="text-center"><a class="cocart-pro-button" href="' . esc_url( 'https://docs.woocommerce.com/document/configuring-caching-plugins/#section-4' ) . '" target="_blank">' . __( 'Apply Condition', 'cocart-pro' ) . '</a></div>',
		),
	),
	array(
		'id'                 => 'another-plugin',
		'value'              => __( 'Another Plugin', 'cocart-pro' ),
		'label'              => __( 'I\'m using another plugin I find better.', 'cocart-pro' ),
		'hidden_field'       => 'yes',
		'hidden_placeholder' => __( 'What is the name of this plugin?', 'cocart-pro' ),
	)
);
?>
<!-- Start of CoCart Pro Modal -->
<div class="cocart-pro-modal">

	<div class="cocart-pro-modal-header">
		<div>
			<button class="cocart-pro-modal-return cocart-pro-icon-arrow-left"><?php _e( 'Return', 'cocart-pro' ); ?></button>
			<h2><?php _e( 'CoCart Pro Feedback', 'cocart-pro' ); ?></h2>
		</div>
		<button class="cocart-pro-modal-close cocart-pro-icon-close"><?php _e( 'Close', 'cocart-pro' ); ?></button>
	</div>

	<div class="cocart-pro-modal-content">
		<div class="cocart-pro-modal-question cocart-pro-isOpen">
			<h3><?php _e( 'May we have a little info about why you are deactivating?', 'cocart-pro' ); ?></h3>
			<ul>
				<?php
				// List each possible reason.
				foreach ( $reasons as $reason ) {
					echo '<li>';

					echo '<input type="radio" name="reason" id="reason-' . $reason['id'] . '" value="' . $reason['value'] . '">';
					echo '<label for="reason-' . $reason['id'] . '">' . $reason['label'] . '</label>';

					if ( $reason['hidden_field'] == 'yes' ) {
						echo '<div class="cocart-pro-modal-fieldHidden"><input type="text" name="reason-' . $reason['id'] . '" id="reason-' . $reason['id'] . '" value="" placeholder="' . $reason['hidden_placeholder'] . '"></div>';
					}

					echo '</li>';
				}
				?>
				<li>
					<input type="radio" name="reason" id="reason-other" value="Other">
					<label for="reason-other"><?php _e( 'Other', 'cocart-pro' ); ?></label>
					<div class="cocart-pro-modal-fieldHidden">
						<textarea name="reason-other-details" id="reason-other-details" placeholder="<?php _e( 'Let us know why you are deactivating CoCart Pro so we can improve the plugin.', 'cocart-pro' ); ?>"></textarea>
					</div>
				</li>
			</ul>
			<input id="cocart-pro-reason" type="hidden" value="">
			<input id="cocart-pro-details" type="hidden" value="">
		</div>

		<?php
		// Prepare response to reason if any.
		foreach( $reasons as $reason ) {
			if ( isset(  $reason['reason'] ) ) {
				echo '<div id="reason-' . $reason['id'] . '-panel" class="cocart-pro-modal-hidden">';
				echo '<h3>' . $reason['reason']['title'] . '</h3>';
				echo $reason['reason']['content'];
				echo '</div>';
			}
		}
		?>
	</div>

	<div class="cocart-pro-modal-footer">
		<div>
			<a href="<?php echo esc_attr( $deactivation_url ); ?>" class="button button-primary cocart-pro-isDisabled" disabled id="send-deactivation"><?php _e( 'Send & Deactivate', 'cocart-pro' ); ?></a>
			<button class="cocart-pro-modal-cancel"><?php _e( 'Cancel', 'cocart-pro' ); ?></button>
		</div>
		<a href="<?php echo esc_attr( $deactivation_url ); ?>" class="button button-secondary"><?php _e( 'Skip & Deactivate', 'cocart-pro' ); ?></a>

		<input type="hidden" id="ajax_url" value="<?php echo admin_url( 'admin-ajax.php' ); ?>" />
	</div>

</div>

<div class="cocart-pro-modal-overlay"></div>
<!-- End of CoCart Pro Modal -->
