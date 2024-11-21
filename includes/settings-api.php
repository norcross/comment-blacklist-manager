<?php
/**
 * Hooking into the WP settings API.
 *
 * @package CommentBlacklistManager
 */

// Call our namepsace.
namespace Norcross\CommentBlacklistManager\SettingsAPI;

// Set our alias items.
use Norcross\CommentBlacklistManager as Core;
use Norcross\CommentBlacklistManager\Helpers as Helpers;

/**
 * Start our engines.
 */
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\load_settings_field_css' );
add_action( 'admin_init', __NAMESPACE__ . '\load_comment_settings' );

/**
 * Include a small bit of CSS for our admin.
 *
 * @param  string $hook_suffix  The hook suffix on admin.
 *
 * @return void
 */
function load_settings_field_css( $hook_suffix ) {

	// Only load this on the comment settings page.
	if ( empty( $hook_suffix ) || 'options-discussion.php' !== $hook_suffix ) {
		return;
	}

	// Set my CSS up.
	$setup_css  = '
		tr.ip-scrub-bulk-action-wrapper th,
		tr.ip-scrub-bulk-action-wrapper td {
			padding-top: 0;
		}

		tr.ip-scrub-bulk-action-wrapper a.ip-scrub-bulk-admin-button {
			margin-bottom: 5px;
		}

		tr.ip-scrub-bulk-action-wrapper p.ip-scrub-bulk-button-explain {
			margin-top: 0;
		}
	';

	// And add the CSS.
	wp_add_inline_style( 'common', $setup_css );
}

/**
 * Add a checkbox to the comment settings for removing IP addresses.
 *
 * @return void
 */
function load_comment_settings() {

	// Define the args for the setting registration.
	$setup_args = [
		'type'              => 'string',
		'show_in_rest'      => false,
		'default'           => 'yes',
		'sanitize_callback' => __NAMESPACE__ . '\sanitize_scrub_setting',
	];

	// Add out checkbox with a sanitiation callback.
	register_setting( 'discussion', Core\OPTION_KEY, $setup_args );

	// Load the actual checkbox field itself.
	add_settings_field( 'ip-scrub-enable', __( 'Scrub Comment IPs', 'scrub-comment-author-ip' ), __NAMESPACE__ . '\display_field', 'discussion',  'default', [ 'class' => 'ip-scrub-enable-wrapper' ] );

	// Get the amount we could fix.
	$get_bulk_nums  = Database\get_count_for_update();

	// Load the button for the bulk action.
	if ( ! empty( $get_bulk_nums ) ) {
		add_settings_field( 'ip-scrub-bulk-action', '', __NAMESPACE__ . '\bulk_action_field', 'discussion',  'default', [ 'class' => 'ip-scrub-bulk-action-wrapper', 'counts' => $get_bulk_nums ] );
	}
}

/**
 * Display a basic checkbox for our setting.
 *
 * @return HTML
 */
function display_field() {

	// Set a label with our default IP.
	$set_label  = sprintf( __( 'Replace the comment author IP address with %s', 'scrub-comment-author-ip' ), '<code>' . esc_html( Helpers\fetch_masked_ip() ) . '</code>' );

	// Add a legend output for screen readers.
	echo '<legend class="screen-reader-text"><span>' . esc_html__( 'Scrub Comment IPs', 'scrub-comment-author-ip' ) . '</span></legend>';

	// We are wrapping the entire thing in a label.
	echo '<label for="ip-scrub-enable-checkbox">';

		// Echo out the input name itself.
		echo '<input name="' . esc_attr( Core\OPTION_KEY ) . '" type="checkbox" id="ip-scrub-enable-checkbox" value="yes" ' . checked( 'yes', Helpers\maybe_scrub_enabled(), false ) . ' />';

		// Echo out the text we just set above.
		echo wp_kses_post( $set_label );

	// Close up the label.
	echo '</label>';
}

/**
 * Display a button for the bulk action.
 *
 * @return HTML
 */
function bulk_action_field( $args ) {

	// Set the bulk args up.
	$set_bulk_args  = [
		'ip-scrub-run-bulk' => 'yes',
		'ip-scrub-nonce'    => wp_create_nonce( 'ip_scrub_bulk' ),
	];

	// Set up the link for runniing the bulk update.
	$set_bulk_link  = Helpers\fetch_settings_url( $set_bulk_args );

	// And show the button link.
	echo '<a class="button button-secondary ip-scrub-bulk-admin-button" href="' . esc_url( $set_bulk_link ) . '">' . esc_html__( 'Bulk Cleanup', 'scrub-comment-author-ip' ) . '</a>';

	// If we have a lot of comments, show the CLI message.
	// @todo decide on a large number.
	if ( ! empty( $args['counts'] ) && 200 < absint( $args['counts'] ) ) {
		echo '<p class="description ip-scrub-bulk-button-explain">' . esc_html__( 'For sites with a large amount of comments, it is suggested to use the WP-CLI command included with this plugin.', 'scrub-comment-author-ip' ) . '</p>';
	}
}

/**
 * Make sure the setting is valid.
 *
 * @param  string $input  The data entered in a settings field.
 *
 * @return string $input  Our cleaned up data.
 */
function sanitize_scrub_setting( $input ) {
	return ! empty( $input ) && 'yes' === sanitize_text_field( $input ) ? 'yes' : 'no';
}
