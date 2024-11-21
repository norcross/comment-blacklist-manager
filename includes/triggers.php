<?php
/**
 * Our various triggers to handle processing.
 *
 * @package CommentBlacklistManager
 */

// Declare our namespace.
namespace Norcross\CommentBlacklistManager\Triggers;

// Set our aliases.
use Norcross\CommentBlacklistManager as Core;
use Norcross\CommentBlacklistManager\DataCalls as DataCalls;
use Norcross\CommentBlacklistManager\Helpers as Helpers;
use Norcross\CommentBlacklistManager\Process as Process;

/**
 * Start our engines.
 */
add_action( 'cblm_activate_process', __NAMESPACE__ . '\run_initial_process' );
add_action( 'cblm_deactivate_process', __NAMESPACE__ . '\delete_stored_data' );
add_action( 'cblm_uninstall_process', __NAMESPACE__ . '\delete_stored_data' );
add_action( 'wp_loaded', __NAMESPACE__ . '\maybe_run_update' );
add_action( 'admin_init', __NAMESPACE__ . '\maybe_manual_update' );

/**
 * Takes any existing terms in the native blacklist field and copies them over to our new 'local' field.
 * Then the update is run for the first time.
 *
 * @return void
 */
function run_initial_process() {

	// Check if we have the keys.
	$maybe_has_keys = get_option( 'disallowed_keys' );

	// If we have some, add them to our new local list.
	if ( ! empty( $maybe_has_keys ) ) {
		update_option( Core\OPTION_PREFIX . 'local', $maybe_has_keys );
	}

	// Then run the update for the first time.
	Process\process_blacklist_data();

	// Set our next update time.
	Process\set_next_update_time();
}

/**
 * Delete any of the data we've stored.
 *
 * @return void
 */
function delete_stored_data() {

	// Delete our options.
	delete_option( Core\OPTION_PREFIX . 'local' );
	delete_option( Core\OPTION_PREFIX . 'exclude' );
	delete_option( Core\OPTION_PREFIX . 'next_update' );
}

/**
 * Do our timestamp comparison and if expired, run an update.
 *
 * @return void
 */
function maybe_run_update() {

	// Check if it's time.
	$maybe_time = Helpers\maybe_run_update();

	// Bail if it isn't time.
	if ( false === $maybe_time ) {
		return;
	}

	// Then run the update for the first time.
	Process\process_blacklist_data();

	// Set our next update time.
	Process\set_next_update_time();
}

/**
 * Watch for the manual triggered update.
 *
 * @return void
 */
function maybe_manual_update() {

	// Confirm we requested this action.
	$confirm_action = filter_input( INPUT_GET, 'cblm-update', FILTER_SANITIZE_SPECIAL_CHARS ); // phpcs:ignore -- the nonce check is happening after this.

	// Make sure it is what we want.
	if ( empty( $confirm_action ) || 'manual' !== $confirm_action ) {
		return;
	}

	// Make sure we have a nonce.
	$confirm_nonce  = filter_input( INPUT_GET, 'cblm-nonce', FILTER_SANITIZE_SPECIAL_CHARS ); // phpcs:ignore -- the nonce check is happening after this.

	// Handle the nonce check.
	if ( empty( $confirm_nonce ) || ! wp_verify_nonce( $confirm_nonce, 'cblm_manual_update' ) ) {

		// Let them know they had a failure.
		wp_die( esc_html__( 'There was an error validating the nonce.', 'comment-blacklist-manager' ), esc_html__( 'Comment Blacklist Manager', 'comment-blacklist-manager' ), [ 'back_link' => true ] );
	}

	// Run the update.
	$manual_request = Process\process_blacklist_data();

	// Set a flag for the result.
	$set_result_flg = ! empty( $manual_request ) ? 'yes' : 'no';

	// Now set my redirect link.
	$redirect_link  = Helpers\fetch_settings_url( ['cblm-result' => 'run', 'cblm-success' => $set_result_flg ] );

	// Do the redirect.
	wp_safe_redirect( $redirect_link );
	exit;
}
