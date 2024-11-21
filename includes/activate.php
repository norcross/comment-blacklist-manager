<?php
/**
 * Our activation call.
 *
 * @package CommentBlacklistManager
 */

// Declare our namespace.
namespace Norcross\CommentBlacklistManager\Activate;

// Set our aliases.
use Norcross\CommentBlacklistManager as Core;

/**
 * Our inital setup function when activated.
 *
 * @return void
 */
function activate() {

	// Include our action so that we may add to this later.
	do_action( Core\HOOK_PREFIX . 'activate_process' );
}
register_activation_hook( Core\FILE, __NAMESPACE__ . '\activate' );
