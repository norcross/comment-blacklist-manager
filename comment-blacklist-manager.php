<?php
/**
 * Plugin Name: Comment Blacklist Manager
 * Plugin URI:  https://github.com/norcross/comment-blacklist-manager
 * Description: Add known terms into the WordPress blacklist keys to manage spam
 * Version:     1.0.0
 * Author:      Andrew Norcross
 * Author URI:  http://andrewnorcross.com
 * Text Domain: comment-blacklist-manager
 * Domain Path: /languages
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 */

if( ! defined( 'CBL_MANAGER_BASE ' ) ) {
	define( 'CBL_MANAGER_BASE', plugin_basename(__FILE__) );
}

if( ! defined( 'CBL_MANAGER_DIR' ) ) {
	define( 'CBL_MANAGER_DIR', plugin_dir_path( __FILE__ ) );
}

if( ! defined( 'CBL_MANAGER_VER' ) ) {
	define( 'CBL_MANAGER_VER', '1.0.0' );
}


class CBL_Manager_Core
{

	/**
	 * Static property to hold our singleton instance
	 * @var $instance
	 */
	static $instance = false;

	/**
	 * this is our constructor.
	 * there are many like it, but this one is mine
	 */
	private function __construct() {
		add_action		(	'plugins_loaded',					array(  $this,  'textdomain'					)			);
		add_action		(	'admin_init',						array(	$this,	'load_settings'					)			);
		add_action		(	'admin_init',						array(	$this,	'update_blacklist_admin'		)			);
		add_action		(	'admin_init',						array(	$this,	'update_blacklist_manual'		)			);
		add_action		(	'admin_notices',					array(	$this,	'manual_update_notice'			)			);
		add_filter		(	'removable_query_args',				array(	$this,	'add_removable_args'			)			);
		register_activation_hook	(	__FILE__,				array(	$this,	'run_initial_process'			)			);
		register_deactivation_hook	(	__FILE__,				array(	$this,	'remove_settings'				)			);
	}

	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return $instance
	 */
	public static function getInstance() {

		if ( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * load our textdomain for localization
	 *
	 * @return void
	 */
	public function textdomain() {

		load_plugin_textdomain( 'comment-blacklist-manager', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	 /**
	  * takes any existing terms in the native blacklist field
	  * and copies them over to our new 'local' field. then the update is
	  * run for the first time
	  *
	  * @return void
	  */
	public function run_initial_process() {

		$current	= get_option( 'blacklist_keys' );

		if ( $current ) {
			update_option( 'blacklist_local', $current );
		}

		// now run the updater
		self::blacklist_process_loader();

	}

	/**
	 * remove our custom settings and delete the transient
	 * on plugin deactivation
	 *
	 * @return void
	 */
	public function remove_settings() {

		// delete our two options
		delete_option( 'blacklist_local' );
		delete_option( 'blacklist_exclude' );

		// delete the transient
		delete_transient( 'blacklist_update_process' );

	}

	/**
	 * register our new settings and load our settings fields
	 *
	 * @return void
	 */
	public function load_settings() {

		// add our blacklist info line, custom list, and exclusion field setting with sanitation callback
		register_setting( 'discussion', 'blacklist_local', array( $this, 'blacklist_data_sanitize' ) );
		register_setting( 'discussion', 'blacklist_exclude', array( $this, 'blacklist_data_sanitize' ) );

		// load the source list field
		add_settings_field( 'blacklist-source', __( 'Blacklist Source', 'comment-blacklist-manager' ), array( $this, 'source_field' ), 'discussion', 'default' );

		// load the custom list field
		add_settings_field( 'blacklist-local', __( 'Local Blacklist', 'comment-blacklist-manager' ), array( $this, 'local_field' ), 'discussion', 'default' );

		// load the exclusion field
		add_settings_field( 'blacklist-exclude', __( 'Excluded Terms', 'comment-blacklist-manager' ), array( $this, 'exclude_field' ), 'discussion', 'default' );

	}

	/**
	 * our field to be added to the discussion settings
	 * for listing blacklist source URLs.
	 *
	 * this is for information purposes only and can't
	 * be modified from the settings panel itself
	 *
	 * @return HTML the data field for the list
	 */
	public function source_field() {

		echo '<fieldset>';
			echo '<legend class="screen-reader-text"><span>' . __( 'Blacklist Source', 'comment-blacklist-manager' ) . '</span>';
		echo '</legend>';

		echo '<p>';
			echo '<label>' . __( 'Data from the sources below will be loaded into the comment blacklist automatically.', 'comment-blacklist-manager' ) . '</label>';
		echo '</p>';

		// fetch the sources
		$sources	= self::blacklist_sources();

		// display message if sources are empty
		if ( ! $sources || empty( $sources ) ) {
			echo '<p class="description">' . __( 'No blacklist sources have been defined.', 'comment-blacklist-manager' ) . '</p>';
		}

		echo '<ul>';
		// loop through the sources and display a list with icon to view
		foreach( (array) $sources as $source ) {

			echo '<li class="widefat"><a href="' . esc_url( $source ) . '" title="' . __( 'View external source', 'comment-blacklist-manager' ) . '" target="_blank"><span class="dashicons dashicons-external"></span></a>&nbsp;' . esc_url( $source ) . '</li>';

		}

		echo '</ul>';

		// now show our button for manual updates
		echo '<a class="button button-secondary" href=" ' . admin_url( 'options-discussion.php' ) . '?cblm-update=manual">' . __( 'Run manual update', 'comment-blacklist-manager' ) . '</a>';

	}

	/**
	 * our field to be added to the discussion settings
	 * for user sourced terms to allow remote updates to be
	 * overwritten without losing local changes
	 *
	 * @return HTML the data field for user terms
	 */
	public function local_field() {

		echo '<fieldset>';
			echo '<legend class="screen-reader-text"><span>' . __( 'Local Blacklist', 'comment-blacklist-manager' ) . '</span>';
		echo '</legend>';

		echo '<p>';
			echo '<label for="blacklist_local">' . __( 'Any terms entered below will be added to the data retrieved from the blacklist sources. One word or IP per line. It will match inside words, so &#8220;press&#8221; will match &#8220;WordPress&#8221;.', 'comment-blacklist-manager' ) . '</label>';
		echo '</p>';

		echo '<p>';
			echo '<textarea id="blacklist_local" class="large-text code" cols="50" rows="6" name="blacklist_local">'. esc_textarea( get_option( 'blacklist_local' ) ) . '</textarea>';
		echo '</p>';
	}

	/**
	 * our field to be added to the discussion settings
	 * for terms to be excluded from any remote file source
	 *
	 * @return HTML the data field for excluded terms
	 */
	public function exclude_field() {

		echo '<fieldset>';
			echo '<legend class="screen-reader-text"><span>' . __( 'Excluded Terms', 'comment-blacklist-manager' ) . '</span>';
		echo '</legend>';

		echo '<p>';
			echo '<label for="blacklist_exclude">' . __( 'Any terms entered below will be excluded from the blacklist updates. One word or IP per line. It will match inside words, so &#8220;press&#8221; will match &#8220;WordPress&#8221;.', 'comment-blacklist-manager' ) . '</label>';
		echo '</p>';

		echo '<p>';
			echo '<textarea id="blacklist_exclude" class="large-text code" cols="50" rows="6" name="blacklist_exclude">'. esc_textarea( get_option( 'blacklist_exclude' ) ) . '</textarea>';
		echo '</p>';
	}

	/**
	 * sanitize the user data list inputs (exclusion and local)
	 *
	 * @param  string	$input	the data entered in a settings field
	 * @return string	$input	the exclude list sanitized
	 */
	public function blacklist_data_sanitize( $input ) {

		return stripslashes( $input );

	}

	/**
	 * our updating function run automatically via admin
	 *
	 * @return void
	 */
	public function update_blacklist_admin() {

		// check for the transient
		if( false === get_transient( 'blacklist_update_process' ) ) {
			self::blacklist_process_loader();
		}

	}

	/**
	 * our updating function run via button press
	 *
	 * @return void
	 */
	public function update_blacklist_manual() {

		// check for our query string
		if ( ! isset( $_REQUEST['cblm-update'] ) || isset( $_REQUEST['cblm-update'] ) && $_REQUEST['cblm-update'] !== 'manual' ) {
			return;
		}

		// run the updater itself
		self::blacklist_process_loader();

		// set a query string to redirect to
		$redirect	= add_query_arg( array( 'cblm-update' => 'success' ), admin_url( 'options-discussion.php' ) );

		wp_redirect( $redirect );
		exit();

	}

	/**
	 * display manual update message if triggered
	 *
	 * @return HTML		success message
	 */
	public function manual_update_notice() {

		// check for our query string
		if ( ! isset( $_GET['cblm-update'] ) || sanitize_text_field( $_GET['cblm-update'] ) !== 'success' ) {
			return;
		}

		// Output the actual markup for the message.
		echo '<div class="notice notice-success is-dismissible">';
			echo '<p><strong>' . __( 'Blacklist terms were updated successfully.', 'comment-blacklist-manager' ) . '</strong></p>';
		echo '</div>';

	}

	/**
	 * Add our custom strings to the vars.
	 *
	 * @param  array $args  The existing array of args.
	 *
	 * @return array $args  The modified array of args.
	 */
	public function add_removable_args( $args ) {

		// Set the default args, passing along a filter.
		$set_removable_args = apply_filters( 'cblm_removable_args', array( 'cblm-update' ) );

		// Include my new args and return.
		return wp_parse_args( $set_removable_args, $args );
	}

	/**
	 * our actual updating function. is done in 3 parts
	 *
	 *  1. fetches the remote blacklist data
	 *  2. filters it against our exclusion list
	 *  3. appends the items in our local list
	 *
	 *  once completed, the blacklist_keys option is updated
	 *
	 * @return void
	 */
	static function blacklist_process_loader() {

		// run our data fetch function
		$data	= self::fetch_blacklist_data();

		// bail if we have no data
		if ( ! $data || empty( $data ) ) {
			return;
		}

		// now handle the exclusion comparison and
		// make it into a string
		$list	= self::create_blacklist_string( $data );

		// bail if we have no list
		if ( ! $list || empty( $list ) ) {
			return;
		}

		// update the option
		update_option( 'blacklist_keys', $list );

		// set our transient
		set_transient( 'blacklist_update_process', 1, apply_filters( 'cblm_update_schedule', 60*60*24 ) );

		// and get out
		return;

	}

	/**
	 * fetch the data from each of our blacklist
	 * sources, parse and clean it, then return
	 * it back
	 *
	 * @return array	$data	a merged array of the combined data lists
	 */
	static function fetch_blacklist_data() {

		// fetch our blacklist file
		$sources	= self::blacklist_sources();

		// bail if our source is empty
		if ( ! $sources || empty( $sources ) ) {
			return;
		}

		// set empty item for appending data source(s)
		$data	= '';

		// loop through and fetch each data source
		foreach( $sources as $source ) {
			$data	.= self::parse_data_source( esc_url( $source ) )."\n";
		}

		// bail if no data exists
		if ( ! $data ) {
			return;
		}

		// run it through our cleaner
		$data	= self::datalist_clean( $data );

		// send back our data
		return $data;

	}

	/**
	 * take our array of terms and run it against our exclusion
	 * list (if one exists) and then bust it into a single string with
	 * proper line breaks
	 *
	 * @param  array	$data	an array of all the terms to be added
	 * @return string			the data in a single string with line breaks
	 */
	static function create_blacklist_string( $data ) {

		// check for the exclude list
		$exclude	= get_option( 'blacklist_exclude' );

		// if we don't have exclusions, merge it and send it back
		if ( empty( $exclude ) ) {
			return self::datalist_merge( $data );
		}

		// if we have exclusions, run our comparisons to the exclude list
		$exclude_array	= self::datalist_clean( $exclude );

		// run our comparison function
		$merged_array	= self::datalist_compare( $data, $exclude_array );

		// merge the existing data and filter duplicates
		$listdata		= self::datalist_merge( $merged_array );

		// run one final sanitation on it and return
		return stripslashes( $listdata );

	}

	/**
	 * load the blacklist file URL (with GitHub as default)
	 * and return it. includes filter to change or add additional sources
	 *
	 * @return array 	$lists		blacklist source(s)
	 */
	static function blacklist_sources() {

		$lists	= array(
			'https://raw.githubusercontent.com/splorp/wordpress-comment-blacklist/master/blacklist.txt'
		);

		return apply_filters( 'cblm_sources', (array) $lists );

	}

	/**
	 * take a data source and attempt to retrieve the data from it
	 *
	 * @param  string	$source		the actual data source
	 * @return mixed	$data		an array or string of data based on source method
	 */
	static function parse_data_source( $source ) {

		// bail if we have no data to parse
		if ( ! $source ) {
			return;
		}

		// set our result blank to begin
		$result	= '';

		// set our args for wp_remote_get
		$args	= array(
			'sslverify' => false
		);

		// run the get action itself
		$response   = wp_remote_get( $source, $args );

		// error. bail.
		if( is_wp_error( $response ) ) {
			return;
		}

		// pull out the body result
		$result	= wp_remote_retrieve_body( $response );

		// bail if it's empty
		if ( empty( $result ) ) {
			return;
		}

		// add filter for some other parse method we aren't aware of
		$result	= apply_filters( 'cblm_parse_data_result', $result, $source );

		// convert it to a list if it's an array by chance
		if ( $result && is_array( $result ) ) {
			$result	= implode( "\n", $result );
		}

		// if a method failed, bail
		if ( ! $result ) {
			return;
		}

		// send it back trimmed
		return trim( $result );

	}

	/**
	 * runs through the list data and makes sure the line breaks are done
	 * properly, which is due to how Windows servers store stuff. then
	 * explodes it into an array for various comparison functions later
	 *
	 * @param  string	$text		the actual data file
	 * @return array	$data		a cleaned up array line break style
	 */
	static function datalist_clean( $text ) {

		$data	= preg_replace( '/\n$/', '', preg_replace( '/^\n/', '', preg_replace( '/[\r\n]+/', "\n", $text ) ) );

		return explode( "\n", $data );

	}

	/**
	 * compare two arrays and remove any matching elements
	 *
	 * @param  array	$source		the source array
	 * @param  array	$compare	the array to run the comparison against
	 * @return array				the resulting array
	 */
	static function datalist_compare( $source, $compare ) {

		// run our comparisons and return
		return array_diff( $source, $compare );

	}

	/**
	 * fetch our existing local blacklist and merge it to
	 * the update, then filter out duplicates
	 *
	 * @param  array	$data	the blacklist data array
	 * @return string			the merged list in a line break string
	 */
	static function datalist_merge( $data ) {

		// get our current local data
		$local	= get_option( 'blacklist_local' );

		// run through the cleaning filter if local entries exists
		if ( ! empty( $local ) ) {
			$local	= self::datalist_clean( $local );
		}

		// ensure proper array casting
		$local	= ! empty( $local ) ? (array) $local : array();

		// merge it to a single array
		$listmerge	= array_merge( $local, $data );

		// filter our uniques
		$listunique	= array_unique( $listmerge );

		// implode it back to a list and return it
		return implode( "\n", $listunique );

	}

/// end class
}

// Instantiate our class
$CBL_Manager_Core = CBL_Manager_Core::getInstance();
