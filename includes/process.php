<?php
/**
 * Our processing functions.
 *
 * @package CommentBlacklistManager
 */

// Declare our namespace.
namespace Norcross\CommentBlacklistManager\Process;

// Set our aliases.
use Norcross\CommentBlacklistManager as Core;

/**
 * Start our engines.
 */
add_action( 'cblm_activate_process', __NAMESPACE__ . '\run_initial_process' );

/**
 * Takes any existing terms in the native blacklist
 * field and copies them over to our new 'local' field.
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
}
