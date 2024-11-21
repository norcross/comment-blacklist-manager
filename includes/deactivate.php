<?php
/**
 * Our deactivation call.
 *
 * @package CommentBlacklistManager
 */

// Declare our namespace.
namespace Norcross\CommentBlacklistManager\Deactivate;

// Set our aliases.
use Norcross\CommentBlacklistManager as Core;

/**
 * Delete various options when deactivating the plugin.
 *
 * @return void
 */
function deactivate() {

	// Delete our two options.
	delete_option( Core\OPTION_PREFIX . 'local' );
	delete_option( Core\OPTION_PREFIX . 'exclude' );

	// Delete the transient.
	delete_transient( Core\TRANSIENT_PREFIX . 'update' );

	// Include our action so that we may add to this later.
	do_action( Core\HOOK_PREFIX . 'deactivate_process' );
}
register_deactivation_hook( Core\FILE, __NAMESPACE__ . '\deactivate' );
