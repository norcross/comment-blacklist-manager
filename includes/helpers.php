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
 * Runs through the list data and makes sure the line breaks are done
 * properly, which is due to how Windows servers store stuff. then
 * explodes it into an array for various comparison functions later.
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
 * Compare two arrays and remove any matching elements.
 *
 * @param  array $source   The source array.
 * @param  array $compare  The array to run the comparison against.
 *
 * @return array
 */
function datalist_compare( $source = [], $compare = [] ) {
	return array_diff( $source, $compare );
}
