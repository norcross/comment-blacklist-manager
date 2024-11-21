<?php
/**
 * Our API data related functions.
 *
 * @package CommentBlacklistManager
 */

// Declare our namespace.
namespace Norcross\CommentBlacklistManager\DataCalls;

// Set our aliases.
use Norcross\CommentBlacklistManager as Core;

// And some core ones.
use WP_Error;

/**
 * Fetch data from a single source URL.
 *
 * @param  string $source  The source URL to pull from.
 *
 * @return mixed           False if none, string based on source.
 */
function fetch_data_from_source( $source = '' ) {

	// Bail if we have no data to parse.
	if ( empty( $source ) ) {
		return new WP_Error( 'missing_source_url', __( 'The required source URL was not provided.', 'comment-blacklist-manager' ) );
	}

	// Set the API args up.
	$setup_api_args = [
		'method'      => 'GET',
		'sslverify'   => true,
		'httpversion' => '1.1',
		'timeout'     => 30, // phpcs:ignore -- this is a data-heavy API call and the timeout is needed.
	];

	// Make my data request.
	if ( function_exists( 'vip_safe_wp_remote_get' ) ) {
		$fetch_api_data = vip_safe_wp_remote_get( esc_url( $source ), '', 3, 5, 20, $setup_api_args );
	} else {
		$fetch_api_data = wp_remote_get( esc_url( $source ), $setup_api_args ); // phpcs:ignore -- this is fallback for non-VIP.
	}

	// First check for a WP_Error return since that gives us a message.
	if ( is_wp_error( $fetch_api_data ) ) {
		return $fetch_api_data;
	}

	// Check the response code.
	$response_code  = wp_remote_retrieve_response_code( $fetch_api_data );

	// Bail if we don't have 200.
	if ( 200 !== absint( $response_code ) ) {
		return new WP_Error( 'invalid_http_response', __( 'The API request did not return a valid response code.', 'comment-blacklist-manager' ) );
	}

	// Bail without data to use.
	if ( empty( $fetch_api_data ) || false === $fetch_api_data ) {
		return new WP_Error( 'empty_api_response', __( 'The API return data could not be retrieved.', 'comment-blacklist-manager' ) );
	}

	// Get the body to see the return.
	$setup_return   = wp_remote_retrieve_body( $fetch_api_data );

	// Bail without body data to use.
	if ( empty( $setup_return ) || false === $setup_return ) {
		return new WP_Error( 'empty_body_response', __( 'The API return data was empty.', 'comment-blacklist-manager' ) );
	}

	// Add a filter for some other parse method we aren't aware of.
	$setup_return   = apply_filters( Core\HOOK_PREFIX . 'parse_data_result', $setup_return, $source );

	// Bail without filtered data to use.
	if ( empty( $setup_return ) || false === $setup_return ) {
		return new WP_Error( 'empty_filter_response', __( 'The API return data was empty after being filtered.', 'comment-blacklist-manager' ) );
	}

	// Blow up the array.
	if ( is_array( $setup_return ) ) {

		// Trim each line first.
		$setup_return   = array_map( 'trim', $setup_return );

		// Then explode the whole thing.
		$setup_return   = implode( "\n", $setup_return );
	}

	// And send it back, trimmed.
	return trim( $setup_return );
}
