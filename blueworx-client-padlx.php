<?php
/**
 * Plugin Name:       BlueWorx Client | PadlX
 * Description:       PadlX founding member sign-up and welcome pages, founding renewal dates, the membership schedule and tick box at checkout, the under-18 pop-up and the documents email.
 * Version:           1.10.1
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            BlueWorx
 * Author URI:        https://blueworx.io
 * Text Domain:       blueworx-client-padlx
 */

namespace BlueWorx\PadlX;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

defined( 'ABSPATH' ) || exit;

const VERSION = '1.10.1';
const FILE    = __FILE__;

// SureCart Founding prices, used until others are saved in settings.
const PRICE_ANNUAL  = '5e54565c-3d31-479a-8ebc-45264ad2d738';
const PRICE_MONTHLY = '34d4564f-0f72-4df8-a816-ced2be4a4fb4';

// SureCart checkout links with one Founding price loaded, used until other links are saved in settings.
const CHECKOUT_ANNUAL  = 'https://padlx.com.au/checkout/?line_items%5B0%5D%5Bprice_id%5D=' . PRICE_ANNUAL . '&line_items%5B0%5D%5Bquantity%5D=1';
const CHECKOUT_MONTHLY = 'https://padlx.com.au/checkout/?line_items%5B0%5D%5Bprice_id%5D=' . PRICE_MONTHLY . '&line_items%5B0%5D%5Bquantity%5D=1';

define( __NAMESPACE__ . '\DIR', plugin_dir_path( __FILE__ ) );
define( __NAMESPACE__ . '\URL', plugin_dir_url( __FILE__ ) );

/*
 * Automatic updates. The site watches this repo's GitHub Releases and installs
 * the zip attached to each one. The library is vendored whole at
 * plugin-update-checker/ and swapped, never edited.
 */
require_once DIR . 'plugin-update-checker/plugin-update-checker.php';

// The third argument must equal the plugin's folder name on the site and the
// folder inside the release zip, or WordPress installs the update as a second copy.
$bwpx_update_checker = PucFactory::buildUpdateChecker(
	'https://github.com/blueworx-io/blueworx_client_padlx/',
	__FILE__,
	'blueworx-client-padlx'
);

// The repo is private, so the site needs a token in wp-config.php to see releases:
// define( 'BLUEWORX_PLUGIN_UPDATE_TOKEN', 'github_pat_...' );
if ( defined( 'BLUEWORX_PLUGIN_UPDATE_TOKEN' ) && BLUEWORX_PLUGIN_UPDATE_TOKEN ) {
	$bwpx_update_checker->setAuthentication( BLUEWORX_PLUGIN_UPDATE_TOKEN );
}

// Install the zip attached to the Release, not GitHub's source tarball.
$bwpx_update_checker->getVcsApi()->enableReleaseAssets();

require_once DIR . 'includes/auto-updates.php';
require_once DIR . 'includes/settings.php';
require_once DIR . 'includes/fields.php';
require_once DIR . 'includes/members.php';
require_once DIR . 'includes/number-list.php';
require_once DIR . 'includes/waitlist.php';
require_once DIR . 'includes/pages.php';
require_once DIR . 'includes/signup.php';
require_once DIR . 'includes/start-date.php';
require_once DIR . 'includes/documents-settings.php';
require_once DIR . 'includes/checkout.php';
require_once DIR . 'includes/under-18.php';
require_once DIR . 'includes/documents-email.php';

register_activation_hook( __FILE__, __NAMESPACE__ . '\activate' );

// Everything from the old PADLX Site Fixes plugin now lives here, so switch it off if it is still on.
add_action(
	'admin_init',
	function () {
		if ( is_plugin_active( 'padlx-site-fixes/padlx-site-fixes.php' ) ) {
			deactivate_plugins( 'padlx-site-fixes/padlx-site-fixes.php' );
		}
	}
);
