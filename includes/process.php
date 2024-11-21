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
use Norcross\CommentBlacklistManager\DataCalls as DataCalls;
use Norcross\CommentBlacklistManager\Helpers as Helpers;

/**
 * Our actual updating function. is done in 3 parts:
 *
 *  1. Fetches the remote blacklist data.
 *  2. Filters it against our exclusion list.
 *  3. Appends the items in our local list.
 *
 *  Once completed, the disallowed_keys option is updated
 *
 * @return void
 */
function process_blacklist_data() {

	// Run the data collection.
	$maybe_has_data = fetch_all_blacklist_data();

	// Bail if none exists.
	if ( empty( $maybe_has_data ) ) {
		return false;
	}

	// Now handle the exclusion comparison and make it into a string.
	$generate_terms = create_blacklist_string( $maybe_has_data );

	// Bail if none exists.
	if ( empty( $generate_terms ) ) {
		return false;
	}

	// Update the option.
	update_option( 'disallowed_keys', $generate_terms );

	// Return a true.
	return true;
}

/**
 * Fetch the data from each of our blacklist sources, parse and clean it, then return it back
 *
 * @return array $data  A merged array of the combined data lists.
 */
function fetch_all_blacklist_data() {

	// First get all the sources.
	$get_source_array   = Helpers\get_blacklist_sources();

	// Bail if our source is empty.
	if ( empty( $get_source_array ) ) {
		return;
	}

	// Set empty item for appending data source(s).
	$set_blacklist  = '';

	// Now loop the source URLs and grab data.
	foreach ( $get_source_array as $source_url ) {

		// Attempt to get the data.
		$fetch_src_data = DataCalls\fetch_data_from_source( $source_url );

		// Skip any that fail.
		if ( empty( $fetch_src_data ) || is_wp_error( $fetch_src_data ) ) {
			continue;
		}

		// Add it to the running list with a line break.
		$set_blacklist .= $fetch_src_data . "\n";
	}

	// Return false if nothing happened, or run it through the cleaner.
	return empty( $set_blacklist ) ? false : Helpers\clean_source_data( $set_blacklist );
}

/**
 * Take our array of terms and run it against our exclusion list (if one exists)
 * and then bust it into a single string with proper line breaks.
 *
 * @param  array $data  An array of all the terms to add.
 *
 * @return string       The data in a single string with line breaks
 */
function create_blacklist_string( $data = [] ) {

	// Check if we have any exclude terms.
	$maybe_excludes = Helpers\get_blacklist_setting( 'exclude' );

	// If we don't have exclusions, merge it and send it back.
	if ( empty( $maybe_excludes ) ) {
		return merge_data_with_local( $data );
	}

	// Clean the terms.
	$excluded_terms = Helpers\clean_source_data( $maybe_excludes );

	// Run our comparison function.
	$merged_array   = array_diff( $data, $excluded_terms );

	// Merge the existing data and filter duplicates
	$do_final_terms = merge_data_with_local( $merged_array );

	// Run one final sanitation on it and return.
	return stripslashes( $do_final_terms );
}

/**
 * Fetch our existing local blacklist and merge it to the update, then filter out duplicates.
 *
 * @param  array $data  An array of all the terms to add.
 *
 * @return string       The data in a single string with line breaks
 */
function merge_data_with_local( $data = [] ) {

	// Check if we have any local terms to add.
	$maybe_locals   = Helpers\get_blacklist_setting( 'local' );

	// If we don't have local terms, just send it back.
	if ( empty( $maybe_locals ) ) {
		return Helpers\format_list_structure( $data );
	}

	// Ensure proper array casting.
	$define_locals  = (array) $maybe_locals;

	// Merge it to a single array.
	$merge_all_keys = array_merge( $define_locals, $data );

	// Return the list.
	return Helpers\format_list_structure( $merge_all_keys );
}

/**
 * Set a timestamp for the next update.
 *
 * @return void
 */
function set_next_update_time() {

	// Get the duration.
	$get_duration   = Helpers\get_update_duration();

	// Set the time.
	$set_next_check = time() + absint( $get_duration );

	// Update our option.
	update_option( Core\OPTION_PREFIX . 'next_update', $set_next_check, 'no' );
}
