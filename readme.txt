=== Comment Blacklist Manager ===
Contributors: norcross, grantsplorp
Tags: comments, spam, blacklist
Website Link: https://github.com/norcross/comment-blacklist-manager
Donate link: http://andrewnorcross.com/donate
Requires at least: 3.7
Tested up to: 5.3.2
Stable tag: 1.0.2-dev
Requires PHP: 5.2.4
License: MIT
License URI: https://norcross.mit-license.org/

Remotely add known terms to the WordPress Comment Blacklist to manage spam.

== Description ==

Comment Blacklist Manager retrieves a list of terms from a remote source and updates the `blacklist_keys` setting in WordPress. The plugin will automatically fetch a list of terms on a regular schedule and update the contents of the “Comment Blacklist” field. Terms added manually via the “Local Blacklist” field will be retained during the scheduled updates. Terms added manually to the “Excluded Terms” field will be removed from the list.

The default list of terms is fetched from a [GitHub](https://github.com/splorp/wordpress-comment-blacklist/ "Comment Blacklist for WordPress") repository maintained by [Grant Hutchinson](https://splorp.com/ "Interface considerations. Gadget accumulation. Typography. Scotch.").

== Installation ==

**To install the plugin using the WordPress dashboard:**

1. Go to the “Plugins > Add New” page
2. Search for “Comment Blacklist Manager”
3. Click the “Install Now” button
4. Activate the plugin on the “Plugins” page
5. (Optional) Add terms to the “Local Blacklist” field in “Settings > Discussion”
6. (Optional) Add terms to the “Excluded Terms” field in “Settings > Discussion”

**To install the plugin manually:**

1. Download the plugin and decompress the archive
2. Upload the `comment-blacklist-manager` folder to the `/wp-content/plugins/` directory on the server
3. Activate the plugin on the “Plugins” page
4. (Optional) Add terms to the “Local Blacklist” field in “Settings > Discussion”
5. (Optional) Add terms to the “Excluded Terms” field in “Settings > Discussion”

== Frequently Asked Questions ==

= What is the source for the default blacklist? =

The default blacklist is maintained by [Grant Hutchinson](https://splorp.com/ "Interface considerations. Gadget accumulation. Typography. Scotch.") on [GitHub](https://github.com/splorp/wordpress-comment-blacklist/ "Comment Blacklist for WordPress").

= Can I provide my own blacklist sources? =

Yes, you can. Use the filter `cblm_sources` to add different source URLs.

**To replace the default source completely:**
`
add_filter( 'cblm_sources', 'rkv_cblm_replace_blacklist_sources' );

function rkv_cblm_replace_blacklist_sources( $list ) {

	return array(
		'http://example.com/blacklist-1.txt'
		'http://example.com/blacklist-2.txt'
	);

}
`

**To add a new source to the existing sources:**
`
add_filter( 'cblm_sources', 'rkv_cblm_add_blacklist_source' );

function rkv_cblm_add_blacklist_source( $list ) {

	$list[]	= 'http://example.com/blacklist-1.txt';

	return $list;

}
`

The plugin expects the list of terms to be in plain text format with each entry on its own line. If the source is provided in a different format (eg: a JSON feed or serialized array), then the result must be run through the `cblm_parse_data_result` filter, which parses the source as a list of terms and the source URL.

= What is the default update schedule? =

The plugin will update the list of terms from the specified sources every 24 hours.

= Can I change the update schedule? =

Yes, you can. Use the filter `cblm_update_schedule` to modify the time between updates.

`add_filter( 'cblm_update_schedule', 'rkv_cblm_custom_schedule' );

function rkv_cblm_custom_schedule( $time ) {

	return DAY_IN_SECONDS;

}`

The `return` data should be specified using WordPress [Transient Time Constants](https://codex.wordpress.org/Transients_API#Using_Time_Constants "Transients API: Using Time Constants").

= Can I add my own terms to the blacklist? =

Yes. Individual terms can be added to the “Local Blacklist” field in the “Settings > Discussion” area of WordPress. Each term must be entered on its own line.

= Can I exclude terms from the blacklist? =

Yes. Individual terms can be excluded from the automatically fetched blacklist by adding them to the “Excluded Terms” field in the “Settings > Discussion” area of WordPress. Each term must be entered on its own line.

== Screenshots ==

1. The “Discussion Settings” screen showing the various blacklist fields

== Changelog ==

= 1.0.2 - 2020/XX/XX
* TBD

= 1.0.1 - 2020/03/23
* updating admin notice display to properly clear when manual update is run
* minor code cleanup

= 1.0.0
* Initial release

== Upgrade Notice ==

= 1.0.0
* Initial release
