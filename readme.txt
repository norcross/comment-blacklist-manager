=== Comment Blacklist Manager ===
Contributors: norcross, grantsplorp
Tags: comments, spam, blacklist
Website Link: https://github.com/norcross/comment-blacklist-manager
Donate link: http://andrewnorcross.com/donate
Requires at least: 3.7
Tested up to: 5.3.2
Stable tag: 1.0.0
Requires PHP: 5.2.4
License: MIT
License URI: https://norcross.mit-license.org/

Remotely add known terms into the WordPress blacklist keys to manage spam

== Description ==

Comment Blacklist Manager will retrieve a list of blacklist terms from a remote source and update the `blacklist_keys` setting in WordPress. The list will update itself on a schedule to keep your terms current. Any manually added items will be retained, and an exclusions list is also created if there are terms from the source you want to allow.

The default data for the list is fetched from [GitHub](https://github.com/splorp/wordpress-comment-blacklist/ "GitHub") and is managed by [Grant Hutchinson](https://splorp.com/ "Grant Hutchinson"). The source can be changed based using available filters.


== Installation ==

1. Upload the `comment-blacklist-manager` folder to the `/wp-content/plugins/` directory or install from the dashboard
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Add any terms to the exclusions list under the main "Discussions" settings area
1. Add any additional terms in the new "Local Blacklist" field

== Frequently Asked Questions ==

= What is the default source of the blacklist? =

The list is managed by [Grant Hutchinson](https://splorp.com/ "Grant Hutchinson") on [GitHub](https://github.com/splorp/wordpress-comment-blacklist/ "GitHub")

= Can I provide my own blacklist sources? =

Sure can. Use the filter `cblm_sources` to add different source URLs.

*to replace the sources completely*
`
add_filter( 'cblm_sources', 'rkv_cblm_replace_blacklist_sources' );

function rkv_cblm_replace_blacklist_sources( $list ) {

	return array(
		'http://example.com/blacklist-1.txt'
		'http://example.com/blacklist-2.txt'
	);

}
`

*to add a new item to the existing sources*
`
add_filter( 'cblm_sources', 'rkv_cblm_add_blacklist_source' );

function rkv_cblm_add_blacklist_source( $list ) {

	$list[]	= 'http://example.com/blacklist-a.txt';

	return $list;

}
`

The plugin expects the blacklist data to be a plain text format with each entry on it's own line. If the source is provided in a different format (a JSON feed or serialized array) then you will need to run the result through `cblm_parse_data_result`, which passes through the data and the source URL.

= Can I change the update schedule? =

Yep. Use the filter `cblm_update_schedule` to add a new URL.

`add_filter( 'cblm_update_schedule', 'rkv_cblm_custom_schedule' );

function rkv_cblm_custom_schedule( $time ) {

	return DAY_IN_SECONDS;

}`

The return should be provided using the [time constants in transients](https://codex.wordpress.org/Transients_API#Using_Time_Constants "time constants in transients")

== Screenshots ==

1. The new exclusions field


== Changelog ==

= 1.0.0 =
* Initial release


== Upgrade Notice ==

= 1.0.0 =
* Initial release
