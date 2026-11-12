<?php
/**
 * The plugin keeps itself up to date.
 *
 * The update checker in the main plugin file tells WordPress a newer version
 * exists. This makes WordPress install it, rather than leaving the offer on
 * the Plugins screen for somebody to press.
 */

namespace BlueWorx\PadlX;

defined( 'ABSPATH' ) || exit;

// Turn automatic updates on for this plugin only; every other plugin, theme and translation is left as it was.
add_filter(
	'auto_update_plugin',
	function ( $update, $item ) {
		if ( is_object( $item ) && isset( $item->plugin ) && plugin_basename( FILE ) === $item->plugin ) {
			return true;
		}
		return $update;
	},
	10,
	2
);
