<?php
/**
 * Plugin Name:       BlueWorx Client | PadlX
 * Description:       PadlX founding member sign-up and welcome pages, and founding renewal dates.
 * Version:           1.4.3
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            BlueWorx
 * Author URI:        https://blueworx.io
 * Text Domain:       blueworx-client-padlx
 */

namespace BlueWorx\PadlX;

defined( 'ABSPATH' ) || exit;

const VERSION = '1.4.3';
const FILE    = __FILE__;

// SureCart Founding prices, used until others are saved in settings.
const PRICE_ANNUAL  = '5e54565c-3d31-479a-8ebc-45264ad2d738';
const PRICE_MONTHLY = '34d4564f-0f72-4df8-a816-ced2be4a4fb4';

// SureCart checkout links with one Founding price loaded, used until other links are saved in settings.
const CHECKOUT_ANNUAL  = 'https://padlx.com.au/checkout/?line_items%5B0%5D%5Bprice_id%5D=' . PRICE_ANNUAL . '&line_items%5B0%5D%5Bquantity%5D=1';
const CHECKOUT_MONTHLY = 'https://padlx.com.au/checkout/?line_items%5B0%5D%5Bprice_id%5D=' . PRICE_MONTHLY . '&line_items%5B0%5D%5Bquantity%5D=1';

define( __NAMESPACE__ . '\DIR', plugin_dir_path( __FILE__ ) );
define( __NAMESPACE__ . '\URL', plugin_dir_url( __FILE__ ) );

require_once DIR . 'includes/settings.php';
require_once DIR . 'includes/fields.php';
require_once DIR . 'includes/members.php';
require_once DIR . 'includes/pages.php';
require_once DIR . 'includes/signup.php';
require_once DIR . 'includes/start-date.php';

register_activation_hook( __FILE__, __NAMESPACE__ . '\activate' );
