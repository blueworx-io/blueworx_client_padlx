<?php
/**
 * Keeps junk out of the sign-up and waitlist forms: a list of blocked emails,
 * a check that the form wasn't sent back too fast to be a person, and an
 * optional Cloudflare Turnstile "not a robot" box.
 */

namespace BlueWorx\PadlX;

defined( 'ABSPATH' ) || exit;

// No person fills in even the four-field waitlist form this fast.
const MIN_SECONDS = 3;

/**
 * Whether an email, or its whole domain, is on the blocked list.
 */
function blocked_email( $raw ) {
	$email = strtolower( trim( sanitize_email( is_string( $raw ) ? wp_unslash( $raw ) : '' ) ) );
	if ( '' === $email ) {
		return false;
	}
	foreach ( blocked_list() as $entry ) {
		if ( $entry === $email || ( '@' === $entry[0] && substr( $email, -strlen( $entry ) ) === $entry ) ) {
			return true;
		}
	}
	return false;
}

function blocked_list() {
	return array_filter( array_map( 'trim', explode( "\n", strtolower( (string) setting( 'blocked_emails' ) ) ) ) );
}

/**
 * Tidies the blocked list from the settings screen: one full email or @domain per line.
 */
function sanitize_blocked_list( $raw ) {
	$keep = array();
	foreach ( preg_split( '/[\s,]+/', strtolower( (string) $raw ) ) as $entry ) {
		$entry = trim( $entry );
		if ( '@' === substr( $entry, 0, 1 ) && preg_match( '/^@[a-z0-9.-]+\.[a-z]{2,}$/', $entry ) ) {
			$keep[] = $entry;
		} elseif ( is_email( $entry ) ) {
			$keep[] = $entry;
		}
	}
	return implode( "\n", array_unique( $keep ) );
}

function turnstile_on() {
	return '' !== setting( 'turnstile_site_key' ) && '' !== setting( 'turnstile_secret_key' );
}

/**
 * The hidden time stamp, and the Turnstile box when it is set up. Goes just above the submit button.
 */
function human_check_fields() {
	$time = (string) time();
	printf( '<input type="hidden" name="bwpx_t" value="%s">', esc_attr( $time . ':' . hash_hmac( 'sha256', $time, wp_salt( 'nonce' ) ) ) );

	if ( turnstile_on() ) {
		printf( '<div class="cf-turnstile" data-sitekey="%s" data-theme="light" style="margin-bottom:16px"></div>', esc_attr( setting( 'turnstile_site_key' ) ) );
		echo '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- only on the two form pages.
	}
}

/**
 * Empty when the form looks like it came from a person, otherwise what to tell them.
 */
function human_check_problem() {
	$stamp = isset( $_POST['bwpx_t'] ) ? sanitize_text_field( wp_unslash( $_POST['bwpx_t'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- the form's nonce is checked first.
	$parts = explode( ':', $stamp );
	$ok    = 2 === count( $parts ) && ctype_digit( $parts[0] ) && hash_equals( hash_hmac( 'sha256', $parts[0], wp_salt( 'nonce' ) ), $parts[1] );
	if ( ! $ok || time() - (int) $parts[0] < MIN_SECONDS ) {
		return 'Please check your answers and submit the form again.';
	}

	if ( ! turnstile_on() ) {
		return '';
	}

	$token = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( '' === $token ) {
		return "Please tick the box to show you're not a robot, then submit again.";
	}

	$response = wp_remote_post(
		'https://challenges.cloudflare.com/turnstile/v0/siteverify',
		array(
			'timeout' => 10,
			'body'    => array(
				'secret'   => setting( 'turnstile_secret_key' ),
				'response' => $token,
				'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			),
		)
	);

	// If Cloudflare can't be reached, let the person through rather than lose a member.
	if ( is_wp_error( $response ) ) {
		return '';
	}
	$result = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $result['success'] ) ) {
		return "Please tick the box to show you're not a robot, then submit again.";
	}
	return '';
}

/**
 * Sends the person back to the form with their answers kept and a message at the top.
 */
function back_to_form( $back, array $values, $message, $anchor ) {
	$token = strtolower( wp_generate_password( 16, false ) );
	set_transient(
		'bwpx_form_' . $token,
		array(
			'values'  => $values,
			'errors'  => array(),
			'message' => $message,
		),
		30 * MINUTE_IN_SECONDS
	);
	wp_safe_redirect( add_query_arg( 'bwpx', $token, $back ) . $anchor );
	exit;
}
