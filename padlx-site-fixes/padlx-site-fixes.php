<?php
/**
 * Plugin Name:       PADLX Site Fixes
 * Description:       Membership schedule and tick box at checkout, under-18 pop-up, and the documents email after purchase.
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            BlueWorx
 * Author URI:        https://blueworx.io
 * Text Domain:       padlx-site-fixes
 */

namespace PADLX\SiteFixes;

defined( 'ABSPATH' ) || exit;

const VERSION = '1.0.0';

define( __NAMESPACE__ . '\DIR', plugin_dir_path( __FILE__ ) );
define( __NAMESPACE__ . '\URL', plugin_dir_url( __FILE__ ) );

require_once DIR . 'includes/settings.php';
require_once DIR . 'includes/checkout.php';
require_once DIR . 'includes/under-18.php';
require_once DIR . 'includes/documents.php';
