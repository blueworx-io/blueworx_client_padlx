// Helpers for the specs. They talk to the disposable test site through PHP
// (wp-load.php), so nothing here depends on how the database is stored.

const path = require('path');
const { execFileSync } = require('child_process');

const WP_LOAD = path.join(__dirname, '..', '.wp-test', 'wp', 'wp-load.php');

// Runs a bit of PHP inside WordPress and returns what it printed.
function wpPhp(code) {
  return execFileSync('php', ['-r', `require ${JSON.stringify(WP_LOAD)}; ${code}`], { encoding: 'utf8' }).trim();
}

// Sets how many founding spots are left: a number, or null for "SureCart is not limiting them".
function setSpots(spots) {
  wpPhp(spots === null ? "delete_option('bwpx_test_spots');" : `update_option('bwpx_test_spots', ${Number(spots)});`);
}

// Founding sales are open until 2099 unless a test says they have closed.
function setClosed(closed) {
  wpPhp(`update_option('bwpx_test_closed', ${closed ? 1 : 0});`);
}

// Makes sure the Founding Membership page exists, is published and uses the sign-up template.
// Returns its address.
function ensureSignupPage() {
  return wpPhp(`
    $pages = get_option('bwpx_pages', array());
    $id = ! empty($pages['signup']) ? (int) $pages['signup'] : 0;
    if ( ! $id || ! get_post($id) ) {
      $id = wp_insert_post(array('post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Founding Membership', 'post_name' => 'founding-membership'));
      $pages['signup'] = $id;
      update_option('bwpx_pages', $pages, false);
    }
    wp_update_post(array('ID' => $id, 'post_status' => 'publish'));
    update_post_meta($id, '_wp_page_template', 'bwpx-founding-signup');
    echo get_permalink($id);
  `);
}

function clearWaitlist() {
  wpPhp("delete_option('bwpx_waitlist'); delete_option('bwpx_test_mail');");
}

// The waitlist as saved: an object keyed by email.
function readWaitlist() {
  return JSON.parse(wpPhp("echo wp_json_encode( (object) get_option('bwpx_waitlist', array()) );"));
}

// Emails WordPress tried to send. The test site's mu-plugin catches them instead of sending.
function readMail() {
  return JSON.parse(wpPhp("echo wp_json_encode( array_values( get_option('bwpx_test_mail', array()) ) );"));
}

module.exports = { setSpots, setClosed, ensureSignupPage, clearWaitlist, readWaitlist, readMail };
