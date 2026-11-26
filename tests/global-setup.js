// Runs once before the suite. Writes a small test-only mu-plugin into the
// disposable test site, which lives in .wp-test/ and so is never part of the plugin.
//
// It lets a test choose how many founding spots are left, keeps founding sales
// open (they close by date in real life), and catches emails instead of sending them.

const fs = require('fs');
const path = require('path');

const WP_CONTENT = path.join(__dirname, '..', '.wp-test', 'wp', 'wp-content');

const MU_PLUGIN = `<?php
/**
 * Plugin Name: PadlX test harness helpers
 * Description: Written by tests/global-setup.js into the disposable test site only. Never shipped.
 */

// The number of founding spots left, as set by the test. No value means SureCart is not limiting them.
add_filter( 'bwpx_founding_spots', function ( $spots ) {
	$test = get_option( 'bwpx_test_spots', null );
	return null === $test ? null : (int) $test;
} );

// Founding sales stay open unless the test says they have closed.
add_filter( 'bwpx_founding_closes', function ( $when ) {
	return get_option( 'bwpx_test_closed' ) ? '2000-01-01 00:00' : '2099-01-01 00:00';
} );

// Keep every email the plugin sends, and send none.
add_filter( 'pre_wp_mail', function ( $pre, $atts ) {
	$mail   = get_option( 'bwpx_test_mail', array() );
	$mail[] = array( 'to' => $atts['to'], 'subject' => $atts['subject'], 'message' => $atts['message'] );
	update_option( 'bwpx_test_mail', $mail, false );
	return true;
}, 10, 2 );
`;

module.exports = async () => {
  if (!fs.existsSync(WP_CONTENT)) {
    throw new Error('No test site found in .wp-test. Run `npm run wp:up` first.');
  }
  const dir = path.join(WP_CONTENT, 'mu-plugins');
  fs.mkdirSync(dir, { recursive: true });
  fs.writeFileSync(path.join(dir, 'padlx-test-harness.php'), MU_PLUGIN);
};
