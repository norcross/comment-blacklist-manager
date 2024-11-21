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
add_filter( 'removable_query_args', __NAMESPACE__ . '\exclude_custom_args' );
add_action( 'admin_notices', __NAMESPACE__ . '\display_admin_notice' );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\load_settings_field_assets' );
add_action( 'admin_init', __NAMESPACE__ . '\load_comment_settings' );

/**
 * Add our custom strings to the removable args.
 *
 * @param  array $args  The existing array of args.
 *
 * @return array        Our updated args.
 */
function exclude_custom_args( $args ) {

	// Set an array of our args.
	$set_removable_args = [
		'cblm-update',
		'cblm-nonce',
		'cblm-result',
		'cblm-success',
	];

	// Include my new args and return.
	return wp_parse_args( $set_removable_args, $args );
}

/**
 * Check for the result of bulk action.
 *
 * @return void
 */
function display_admin_notice() {

	// Confirm we requested this action.
	$confirm_action = filter_input( INPUT_GET, 'cblm-result', FILTER_SANITIZE_SPECIAL_CHARS ); // phpcs:ignore -- no need for a nonce check.

	// Make sure it is what we want.
	if ( empty( $confirm_action ) || 'run' !== sanitize_text_field( $confirm_action ) ) {
		return;
	}

	// Confirm we requested this action.
	$confirm_result = filter_input( INPUT_GET, 'cblm-success', FILTER_SANITIZE_SPECIAL_CHARS ); // phpcs:ignore -- no need for a nonce check.

	// Handle the failure first.
	if ( empty( $confirm_result ) || 'yes' !== $confirm_result ) {

		// Set the notice text.
		$set_notice = __( 'There was an unknown error updating the blacklist data. Please check your error logs.', 'comment-blacklist-manager' );

		// Set the wrapper around it.
		echo '<div class="notice notice-error is-dismissible">';

			// Display the actual message.
			echo '<p><strong>' . wp_kses_post( $set_notice ) . '</strong></p>';

		// Close the wrapper.
		echo '</div>';

		// And be done.
		return;
	}

	// Set the notice text.
	$set_notice = __( 'Success! The blacklist data has been updated.', 'comment-blacklist-manager' );

	// Set the wrapper around it.
	echo '<div class="notice notice-success is-dismissible">';

		// Display the actual message.
		echo '<p><strong>' . wp_kses_post( $set_notice ) . '</strong></p>';

	// Close the wrapper.
	echo '</div>';

	// And be done.
	return;

	// Nothing left to display.
}

/**
 * Include a small bit of CSS for our admin.
 *
 * @param  string $hook_suffix  The hook suffix on admin.
 *
 * @return void
 */
function load_settings_field_assets( $hook_suffix ) {

	// Only load this on the comment settings page.
	if ( empty( $hook_suffix ) || 'options-discussion.php' !== $hook_suffix ) {
		return;
	}

	// Set my CSS up.
	$setup_css  = '
		.options-discussion-php .indent-children ul.cblm-inline-list {
			margin-left: 0;
		}

		ul.cblm-inline-list li.cblm-inline-list-item {
			padding-bottom: 6px;
		}
	';

	// And add the CSS.
	wp_add_inline_style( 'common', $setup_css );
}

/**
 * Add our new fields for handling the blacklist data.
 *
 * @return void
 */
function load_comment_settings() {

	// Define the args for the local field.
	$local_args = [
		'type'              => 'string',
		'show_in_rest'      => false,
		'default'           => '',
		'sanitize_callback' => __NAMESPACE__ . '\sanitize_setting',
	];

	// Add out checkbox with a sanitiation callback.
	register_setting( 'discussion', Core\OPTION_PREFIX . 'local', $local_args );

	// Define the args for the exclude field.
	$excld_args = [
		'type'              => 'string',
		'show_in_rest'      => false,
		'default'           => '',
		'sanitize_callback' => __NAMESPACE__ . '\sanitize_setting',
	];

	// Add out checkbox with a sanitiation callback.
	register_setting( 'discussion', Core\OPTION_PREFIX . 'exclude', $excld_args );

	// Load the local terms field.
	add_settings_field(
		'blacklist-local',
		__( 'Local Blacklist', 'comment-blacklist-manager' ),
		__NAMESPACE__ . '\local_field',
		'discussion',
		'default',
		[
			'class'   => 'blacklist-local-field-wrapper',
			'dataset' => Helpers\get_blacklist_setting( 'local' ),
		]
	);

	// Add our excluded terms.
	add_settings_field(
		'blacklist-exclude',
		__( 'Excluded Terms', 'comment-blacklist-manager' ),
		__NAMESPACE__ . '\exclude_field',
		'discussion',
		'default',
		[
			'class'   => 'blacklist-exclude-field-wrapper',
			'dataset' => Helpers\get_blacklist_setting( 'exclude' ),
		]
	);

	// Load the sources field.
	add_settings_field(
		'blacklist-source',
		__( 'Blacklist Source', 'comment-blacklist-manager' ),
		__NAMESPACE__ . '\source_field',
		'discussion',
		'default',
		[
			'class'   => 'blacklist-source-field-wrapper',
			'dataset' => Helpers\get_blacklist_sources(),
		]
	);
}

/**
 * Display the individual source URLs.
 *
 * @param  array $args  The passed args for the field.
 *
 * @return HTML
 */
function source_field( $args ) {

	// Set the legend field.
	echo '<legend class="screen-reader-text">';
		echo '<span>' . esc_html__( 'Blacklist Sources', 'comment-blacklist-manager' ) . '</span>';
	echo '</legend>';

	// Show a quick message if no sources exist.
	if ( empty( $args['dataset'] ) ) {

		// Show the paragraph text.
		echo '<p class="description">' . esc_html__( 'No blacklist sources have been defined.', 'comment-blacklist-manager' ) . '</p>';

		// And done.
		return;
	}

	// Set the bulk args up.
	$set_manual_update_args = [
		'cblm-update' => 'manual',
		'cblm-nonce'  => wp_create_nonce( 'cblm_manual_update' ),
	];

	// Create the link for a manual update button.
	$get_manual_update_link = Helpers\fetch_settings_url( $set_manual_update_args );

	// Show our label.
	echo '<p>' . esc_html__( 'Data from the sources below will be loaded into the comment blacklist automatically.', 'comment-blacklist-manager' ) . '</p>';

	// Begin the code list.
	echo '<ul class="cblm-inline-list">';

	// Loop through the sources and display a list with icon to view
	foreach ( $args['dataset'] as $source_url ) {

		// Open the list item.
		echo '<li class="cblm-inline-list-item widefat">';

			// Show a button to view it.
			echo '<a href="' . esc_url( $source_url ) . '" title="' . esc_attr__( 'View external source', 'comment-blacklist-manager' ) . '" target="_blank"><span class="dashicons dashicons-external"></span></a>';

			// Show the actual link.
			echo '<code>' . esc_url( $source_url ) . '</code>';

		// Close the list item.
		echo '</li>';
	}

	// Close the entire list.
	echo '</ul>';

	// Now our manual button for updating.
	echo '<p>';

		// Do the actual button.
		echo '<a class="button button-secondary" href="' . esc_url( $get_manual_update_link ) . '">' . esc_html__( 'Run Manual Update', 'comment-blacklist-manager' ) . '</a>';

	// Close the wrapper for the button.
	echo '</p>';
}

/**
 * Display the textarea for local keywords.
 *
 * @param  array $args  The passed args for the field.
 *
 * @return HTML
 */
function local_field( $args ) {

	// Set the legend field.
	echo '<legend class="screen-reader-text">';
		echo '<span>' . esc_html__( 'Local Blacklist Terms', 'comment-blacklist-manager' ) . '</span>';
	echo '</legend>';

	// Show the explanation.
	echo '<p>';
		echo '<label for="blacklist_local">' . esc_html__( 'Any terms entered below will be added to the data retrieved from the blacklist sources. One word or IP per line. It will match inside words, so &#8220;press&#8221; will match &#8220;WordPress&#8221;.', 'comment-blacklist-manager' ) . '</label>';
	echo '</p>';

	// And the actual field.
	echo '<p>';
		echo '<textarea id="blacklist_local" class="large-text code" cols="50" rows="6" name="blacklist_local">' . esc_textarea( $args['dataset'] ) . '</textarea>';
	echo '</p>';
}

/**
 * Display the textarea for excluded keywords.
 *
 * @param  array $args  The passed args for the field.
 *
 * @return HTML
 */
function exclude_field( $args ) {

	// Set the legend field.
	echo '<legend class="screen-reader-text">';
		echo '<span>' . esc_html__( 'Excluded Terms', 'comment-blacklist-manager' ) . '</span>';
	echo '</legend>';

	// Show the explanation.
	echo '<p>';
		echo '<label for="blacklist_exclude">' . esc_html__( 'Any terms entered below will be excluded from the blacklist updates. One word or IP per line. It will match inside words, so &#8220;press&#8221; will match &#8220;WordPress&#8221;.', 'comment-blacklist-manager' ) . '</label>';
	echo '</p>';

	// And the actual field.
	echo '<p>';
		echo '<textarea id="blacklist_exclude" class="large-text code" cols="50" rows="6" name="blacklist_exclude">' . esc_textarea( $args['dataset'] ) . '</textarea>';
	echo '</p>';
}

/**
 * Make sure the setting is valid.
 *
 * @param  string $input  The data entered in a settings field.
 *
 * @return string $input  Our cleaned up data.
 */
function sanitize_setting( $input ) {
	return stripslashes( $input );
}
