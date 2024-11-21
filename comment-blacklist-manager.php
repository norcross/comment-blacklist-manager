<?php
/**
 * Plugin Name: Comment Blacklist Manager
 * Plugin URI:  https://github.com/norcross/comment-blacklist-manager
 * Description: Add known terms into the WordPress blacklist keys to manage spam
 * Version:     2.0.0
 * Author:      Andrew Norcross
 * Author URI:  https://andrewnorcross.com
 * Text Domain: comment-blacklist-manager
 * Domain Path: /languages
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 *
 * @package     CommentBlacklistManager
 */

// Call our namepsace.
namespace Norcross\CommentBlacklistManager;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Define our version.
define( __NAMESPACE__ . '\VERS', '2.0.0' );

// Plugin root file.
define( __NAMESPACE__ . '\FILE', __FILE__ );

// Define our file base.
define( __NAMESPACE__ . '\BASE', plugin_basename( __FILE__ ) );

// Plugin Folder URL and directory.
define( __NAMESPACE__ . '\URL', plugin_dir_url( __FILE__ ) );
define( __NAMESPACE__ . '\DIR', plugin_dir_path( __FILE__ ) );

// Set our assets path constants.
define( __NAMESPACE__ . '\ASSETS_URL', URL . 'assets' );
define( __NAMESPACE__ . '\ASSETS_PATH', __DIR__ . '/assets' );

// Set a handful of prefixes.
define( __NAMESPACE__ . '\HOOK_PREFIX', 'cblm_' );
define( __NAMESPACE__ . '\OPTION_PREFIX', 'blacklist_' );

// Define the default blacklist source.
define( __NAMESPACE__ . '\LIST_SRC', 'https://raw.githubusercontent.com/splorp/wordpress-comment-blacklist/master/blacklist.txt' );


// Go and load our files.
require_once __DIR__ . '/includes/data-calls.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/process.php';
require_once __DIR__ . '/includes/settings-api.php';
require_once __DIR__ . '/includes/triggers.php';

// Load the triggered file loads.
require_once __DIR__ . '/includes/activate.php';
require_once __DIR__ . '/includes/deactivate.php';
require_once __DIR__ . '/includes/uninstall.php';
