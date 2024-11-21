<?php
/**
 * Some shared helper and utility functions.
 *
 * @package CommentBlacklistManager
 */

// Declare our namespace.
namespace Norcross\CommentBlacklistManager\Helpers;

// Set our aliases.
use Norcross\CommentBlacklistManager as Core;

/**
 * Check to see if we need to run an update.
 *
 * @return boolean
 */
function maybe_run_update() {

	// Check for the option.
	$next_to_update = get_option( Core\OPTION_PREFIX . 'next_update', 0 );

	// If no stamp exists, it's time to run.
	if ( empty( $next_to_update ) ) {
		return true;
	}

	// Compare the time and return.
	return time() > absint( $next_to_update ) ? true : false;
}

/**
 * Get the default source for the blacklist data, with a filter.
 *
 * @return array  The array of sources for the blacklist.
 */
function get_blacklist_sources() {

	// Grab our core source file.
	$default_source = (array) Core\LIST_SRC;

	// Return the source array.
	return apply_filters( Core\HOOK_PREFIX . 'sources', $default_source );
}

/**
 * Get one of our settings.
 *
 * @param  string $setting_key  The setting key we wanna get.
 *
 * @return mixed
 */
function get_blacklist_setting( $setting_key = '' ) {

	// Define the larger option key.
	$set_option_key = 'keys' === $setting_key ? 'disallowed_keys' : Core\OPTION_PREFIX . $setting_key;

	// Return the option.
	return get_option( $set_option_key, '' );
}

/**
 * Get the duration for the updates.
 *
 * @return integer
 */
function get_update_duration() {
	return apply_filters( Core\HOOK_PREFIX . 'update_schedule', WEEK_IN_SECONDS );
}

/**
 * Get the URL for our settings page with any custom args.
 *
 * @param  array  $args  The possible array of args.
 *
 * @return string
 */
function fetch_settings_url( $args = [] ) {

	// If we have no args, just do the basic link.
	if ( empty( $args ) ) {
		return admin_url( 'options-discussion.php' );
	}

	// Now return it in args.
	return add_query_arg( $args, admin_url( 'options-discussion.php' ) );
}

/**
 * Runs through the list data and makes sure the line breaks are done properly, which is due to how
 * Windows servers store stuff. Then explodes it into an array for various comparison functions later.
 *
 * @param  string $text  The actual data we wanna clean.
 *
 * @return array  $data  A cleaned up array line break style.
 */
function clean_source_data( $text = '' ) {

	// Bail without text to clean.
	if ( empty( $text ) ) {
		return false;
	}

	// Clean out weird line breaks.
	$set_lined_data = preg_replace( '/\n$/', '', preg_replace( '/^\n/', '', preg_replace( '/[\r\n]+/', "\n", $text ) ) );

	// Turn it into an array and return it.
	return explode( "\n", $set_lined_data );
}

/**
 * Take our array of data and return a broken list.
 *
 * @param  array  $data  The array of data we have.
 *
 * @return string
 */
function format_list_structure( $data = [] ) {

	// Filter our uniques.
	$do_unique_keys = array_unique( $data );

	// Implode it back to a list and return it.
	return implode( "\n", $do_unique_keys );
}
