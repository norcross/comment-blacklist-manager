Comment Blacklist Manager
========================

## Contributors
* [Andrew Norcross](https://github.com/norcross)
* [Grant Hutchinson](https://github.com/splorp)

## About

Remotely add known terms to the WordPress Comment Blacklist to manage spam.

## Features

Comment Blacklist Manager retrieves a list of terms from a remote source and updates the `blacklist_keys` setting in WordPress. The plugin will automatically fetch a list of terms on a regular schedule and update the contents of the “Comment Blacklist” field. Terms added manually via the “Local Blacklist” field will be retained during the scheduled updates. Terms added manually to the “Excluded Terms” field will be removed from the list.

The default list of terms is fetched from a [GitHub](https://github.com/splorp/wordpress-comment-blacklist/ "Comment Blacklist for WordPress") repository maintained by [Grant Hutchinson](https://splorp.com/ "Interface considerations. Gadget accumulation. Typography. Scotch.").

## Frequently Asked Questions

#### What is the source for the default blacklist?

The default blacklist is maintained by [Grant Hutchinson](https://splorp.com/ "Interface considerations. Gadget accumulation. Typography. Scotch.") on [GitHub](https://github.com/splorp/wordpress-comment-blacklist/ "Comment Blacklist for WordPress").

#### Can I provide my own blacklist sources?

Yes, you can. Use the filter `cblm_sources` to add different source URLs.

~~~php
/**
 * Replace the existing source file with our own.
 *
 * @param  array $sources  The current URL sets.
 *
 * @return array           Our new one.
 */
function prefix_replace_blacklist_sources( $sources ) {
	return ['https://example.com/some-source.txt'];
}
add_filter( 'cblm_sources', 'prefix_replace_blacklist_sources' );
~~~

~~~php
/**
 * Add to the existing source files.
 *
 * @param  array $sources  The current URL sets.
 *
 * @return array           Our new ones.
 */
function prefix_amend_blacklist_sources( $sources ) {

	$sources[]	= 'http://example.com/blacklist-1.txt';

	return $sources;
}
add_filter( 'cblm_sources', 'prefix_amend_blacklist_sources' );
~~~

#### My custom source is in a different format. How do I use it?

The plugin expects the list of terms to be in plain text format with each entry on its own line. If the source is provided in a different format (eg: a JSON feed or serialized array), then the result must be run through the `cblm_parse_data_result` filter, which parses the source as a list of terms and the source URL.

#### What is the default update schedule?

Currently set to 1 week. However, that can be changed with the `cblm_update_schedule` filter. The return data should be specified using WordPress [Transient Time Constants](https://codex.wordpress.org/Transients_API#Using_Time_Constants "Transients API: Using Time Constants") or an integer representing the time amount.

~~~php
/**
 * Set the update time to one day.
 *
 * @param  integer $duration  The currently set duration.
 *
 * @return integer            Our new one.
 */
function prefix_change_update_duration( $duration ) {
	return DAY_IN_SECONDS;
}
add_filter( 'cblm_update_schedule', 'prefix_change_update_duration' );
~~~

#### Can I add my own terms to the blacklist?

Yes. Individual terms can be added to the “Local Blacklist” field in the “Settings > Discussion” area of WordPress. Each term must be entered on its own line.

#### Can I exclude terms from the blacklist?

Yes. Individual terms can be excluded from the automatically fetched blacklist by adding them to the “Excluded Terms” field in the “Settings > Discussion” area of WordPress. Each term must be entered on its own line.
