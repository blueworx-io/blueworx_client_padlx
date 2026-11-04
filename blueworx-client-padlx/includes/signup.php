<?php
/**
 * Handles the sign-up form: check the answers, save them to the member's profile, email the team, then go to checkout.
 */

namespace BlueWorx\PadlX;

defined( 'ABSPATH' ) || exit;

const ACTION = 'bwpx_founding_signup';

// The form posts to its own page, because the site sends logged-out visitors away from wp-admin.
add_action(
	'template_redirect',
	function () {
		if ( isset( $_POST['bwpx_action'] ) && ACTION === $_POST['bwpx_action'] && TEMPLATE_SIGNUP === current_template() ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked in handle_signup().
			handle_signup();
		}
	},
	1
);

function handle_signup() {
	$back = get_permalink( get_queried_object_id() );

	if ( ! isset( $_POST['_bwpx_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_bwpx_nonce'] ), ACTION ) ) {
		wp_safe_redirect( add_query_arg( 'bwpx', 'expired', $back ) . '#bwpx-form' );
		exit;
	}

	// Bots fill in the hidden field; people never see it.
	if ( ! empty( $_POST['bwpx_website'] ) ) {
		wp_safe_redirect( $back );
		exit;
	}

	$raw    = isset( $_POST['bwpx'] ) && is_array( $_POST['bwpx'] ) ? $_POST['bwpx'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- cleaned per field below.
	$values = array();
	$errors = array();
	foreach ( fields() as $key => $field ) {
		list( $value, $error ) = clean_field( $field, $raw[ $key ] ?? '' );
		$values[ $key ]        = $value;
		if ( $error ) {
			$errors[ $key ] = $error;
		}
	}

	if ( $errors ) {
		$token = strtolower( wp_generate_password( 16, false ) );
		set_transient( 'bwpx_form_' . $token, array( 'values' => $values, 'errors' => $errors ), 30 * MINUTE_IN_SECONDS );
		wp_safe_redirect( add_query_arg( 'bwpx', $token, $back ) . '#bwpx-form' );
		exit;
	}

	$user_id = save_member( $values );
	if ( is_wp_error( $user_id ) ) {
		wp_safe_redirect( add_query_arg( 'bwpx', 'failed', $back ) . '#bwpx-form' );
		exit;
	}

	notify_team( $values, $user_id );
	set_member_cookie( $user_id );

	$checkout = add_query_arg(
		array(
			'email'      => rawurlencode( $values['email'] ),
			'first_name' => rawurlencode( $values['first_name'] ),
			'last_name'  => rawurlencode( $values['last_name'] ),
		),
		checkout_url( $values['plan'] )
	);

	wp_redirect( $checkout ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- checkout link is set by an admin.
	exit;
}

function notify_team( array $values, $user_id ) {
	$to = setting( 'notify_email' );
	if ( ! is_email( $to ) ) {
		return;
	}

	$body = '';
	foreach ( answer_lines( $values ) as $label => $value ) {
		$body .= $label . ': ' . $value . "\n";
	}
	$body .= "\nMember number: " . format_number( $user_id ) . "\n";
	$body .= "They have been sent to checkout.\n";
	$body .= admin_url( 'user-edit.php?user_id=' . $user_id ) . "\n";

	wp_mail(
		$to,
		sprintf( 'Founding member sign-up: %s %s (%s)', $values['first_name'], $values['last_name'], 'monthly' === $values['plan'] ? 'monthly' : 'annual' ),
		$body,
		array( 'Reply-To: ' . $values['first_name'] . ' ' . $values['last_name'] . ' <' . $values['email'] . '>' )
	);
}

/**
 * What to show when the form comes back: earlier answers and any problems with them.
 */
function form_state() {
	$state = array(
		'values'  => array( 'plan' => 'annual' ),
		'errors'  => array(),
		'message' => '',
	);

	$flag = isset( $_GET['bwpx'] ) ? sanitize_key( wp_unslash( $_GET['bwpx'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( 'expired' === $flag ) {
		$state['message'] = 'This page was open for a while, so please submit the form again.';
	} elseif ( 'failed' === $flag ) {
		$state['message'] = "Sorry, something went wrong saving your answers. Please try again, or email info@padlx.com.au.";
	} elseif ( $flag ) {
		$saved = get_transient( 'bwpx_form_' . $flag );
		if ( is_array( $saved ) ) {
			$state['values']  = $saved['values'];
			$state['errors']  = $saved['errors'];
			$state['message'] = 'A few answers need another look. They are marked below.';
		}
	}

	return $state;
}
