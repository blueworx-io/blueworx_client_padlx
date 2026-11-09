<?php
/**
 * The sign-up and welcome pages: templates, assets, and the hand-off from SureCart's thank-you page.
 */

namespace BlueWorx\PadlX;

defined( 'ABSPATH' ) || exit;

const PAGES_OPTION     = 'bwpx_pages';
const TEMPLATE_SIGNUP  = 'bwpx-founding-signup';
const TEMPLATE_WELCOME = 'bwpx-founding-welcome';
const COOKIE           = 'bwpx_fm';

function templates() {
	return array(
		TEMPLATE_SIGNUP  => __( 'PadlX – Founding member sign-up', 'blueworx-client-padlx' ),
		TEMPLATE_WELCOME => __( 'PadlX – Founding member welcome', 'blueworx-client-padlx' ),
	);
}

/**
 * Creates both pages as drafts, once.
 */
function activate() {
	$pages = get_option( PAGES_OPTION, array() );
	$pages = is_array( $pages ) ? $pages : array();

	$wanted = array(
		'signup'  => array( 'Founding Membership', 'founding-membership', TEMPLATE_SIGNUP ),
		'welcome' => array( 'Welcome to the 150', 'welcome', TEMPLATE_WELCOME ),
	);

	foreach ( $wanted as $key => list( $title, $slug, $template ) ) {
		if ( ! empty( $pages[ $key ] ) && get_post( $pages[ $key ] ) ) {
			continue;
		}
		$id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'draft',
				'post_title'  => $title,
				'post_name'   => $slug,
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_wp_page_template', $template );
			$pages[ $key ] = $id;
		}
	}

	update_option( PAGES_OPTION, $pages, false );
}

function page_url( $key ) {
	$pages = get_option( PAGES_OPTION, array() );
	return ! empty( $pages[ $key ] ) ? get_permalink( $pages[ $key ] ) : '';
}

function current_template() {
	if ( ! is_singular( 'page' ) ) {
		return '';
	}
	$slug = get_page_template_slug( get_queried_object_id() );
	return isset( templates()[ $slug ] ) ? $slug : '';
}

add_filter(
	'theme_page_templates',
	function ( $list ) {
		return array_merge( $list, templates() );
	}
);

add_filter(
	'template_include',
	function ( $template ) {
		$slug = current_template();
		if ( TEMPLATE_SIGNUP === $slug ) {
			return DIR . ( waitlist_showing() ? 'templates/waitlist.php' : 'templates/signup.php' );
		}
		if ( TEMPLATE_WELCOME === $slug ) {
			return DIR . 'templates/welcome.php';
		}
		return $template;
	},
	99
);

// These pages are for invited members and paying customers, so keep them out of search.
add_filter(
	'wp_robots',
	function ( $robots ) {
		if ( current_template() ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
		}
		return $robots;
	}
);

// Drop the Elementor kit class so site-wide Elementor styles don't leak into the design.
add_filter(
	'body_class',
	function ( $classes ) {
		if ( ! current_template() ) {
			return $classes;
		}
		$classes = array_filter(
			$classes,
			function ( $c ) {
				return 0 !== strpos( $c, 'elementor-kit-' );
			}
		);
		$classes[] = 'bwpx-body';
		return $classes;
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! current_template() ) {
			return;
		}

		wp_enqueue_style( 'bwpx-rubik', 'https://fonts.googleapis.com/css2?family=Rubik:wght@400&display=swap', array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_style( 'bwpx-founding', URL . 'assets/css/founding.css', array( 'bwpx-rubik' ), VERSION );

		// Aero Matics is already in the site's media library; reuse those files.
		$fonts = trailingslashit( wp_get_upload_dir()['baseurl'] ) . '2026/08/';
		wp_add_inline_style(
			'bwpx-founding',
			sprintf(
				"@font-face{font-family:'Aero Matics';src:url('%1\$sAeroMaticsRegular.woff2') format('woff2'),url('%1\$sAeroMaticsRegular.woff') format('woff');font-weight:400;font-style:normal;font-display:swap}" .
				"@font-face{font-family:'Aero Matics';src:url('%1\$sAeroMaticsItalic.woff2') format('woff2'),url('%1\$sAeroMaticsItalic.woff') format('woff');font-weight:400;font-style:italic;font-display:swap}",
				esc_url_raw( $fonts )
			)
		);

		if ( TEMPLATE_SIGNUP === current_template() ) {
			wp_enqueue_script( 'bwpx-founding', URL . 'assets/js/founding.js', array(), VERSION, true );
		}
	},
	20
);

/**
 * Remembers which sign-up this browser made, so the welcome page can greet them.
 */
function set_member_cookie( $user_id ) {
	$value = $user_id . '.' . wp_hash( 'bwpx_fm_' . $user_id );
	setcookie(
		COOKIE,
		$value,
		array(
			'expires'  => time() + 30 * DAY_IN_SECONDS,
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
}

function member_from_cookie() {
	if ( empty( $_COOKIE[ COOKIE ] ) ) {
		return 0;
	}
	$parts = explode( '.', sanitize_text_field( wp_unslash( $_COOKIE[ COOKIE ] ) ), 2 );
	if ( count( $parts ) !== 2 ) {
		return 0;
	}
	$id = absint( $parts[0] );
	if ( ! $id || ! hash_equals( wp_hash( 'bwpx_fm_' . $id ), $parts[1] ) || ! is_founding_member( $id ) ) {
		return 0;
	}
	return $id;
}

/**
 * The member to greet on the welcome page: this browser's sign-up, or the logged-in founding member.
 */
function welcome_member() {
	$id = member_from_cookie();
	if ( ! $id && is_user_logged_in() && is_founding_member( get_current_user_id() ) ) {
		$id = get_current_user_id();
	}
	return $id ? get_userdata( $id ) : false;
}

// After paying, SureCart sends people to its thank-you page. Send founding sign-ups on to ours.
add_action(
	'template_redirect',
	function () {
		if ( ! is_singular( 'page' ) || ! has_block( 'surecart/order-confirmation', get_queried_object() ) ) {
			return;
		}

		$user_id  = member_from_cookie();
		$welcome  = page_url( 'welcome' );
		$pages    = get_option( PAGES_OPTION, array() );
		if ( ! $user_id || ! $welcome || get_user_meta( $user_id, META_WELCOMED, true ) || 'publish' !== get_post_status( $pages['welcome'] ?? 0 ) ) {
			return;
		}

		update_user_meta( $user_id, META_WELCOMED, time() );

		wp_safe_redirect( add_query_arg( map_deep( wp_unslash( $_GET ), 'sanitize_text_field' ), $welcome ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		exit;
	}
);

/**
 * Shared markup for both pages.
 */
function asset( $file ) {
	return URL . 'assets/img/' . $file;
}

/**
 * A welcome page step's button. Until its link is saved, members see nothing and admins see a reminder.
 */
function welcome_button( $url, $text, $setting_label ) {
	if ( $url ) {
		printf( '<a class="bwpx-btn" href="%s" target="_blank" rel="noopener">%s</a>', esc_url( $url ), esc_html( $text ) );
	} elseif ( current_user_can( 'manage_options' ) ) {
		printf( '<p class="bwpx-small">Button hidden from members: add the %s under Users → Founding Members.</p>', esc_html( $setting_label ) );
	}
}

function part( $name, array $args = array() ) {
	extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract
	include DIR . 'templates/parts/' . $name . '.php';
}
