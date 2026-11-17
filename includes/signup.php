<?php
/**
 * Handles the sign-up form: check the answers, save them to the member's profile, then go to checkout.
 * The team is emailed once the member has paid, so people who stop at checkout don't reach their inbox.
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

	// Blocked emails are quietly sent back to the page, with nothing saved or emailed.
	if ( blocked_email( $raw['email'] ?? '' ) ) {
		wp_safe_redirect( $back );
		exit;
	}

	$problem = human_check_problem();
	if ( $problem ) {
		back_to_form( $back, $values, $problem, '#bwpx-form' );
	}

	// The last spot went, or sales closed, while they were filling in the form: keep them as a lead instead.
	if ( founding_full() || founding_closed() ) {
		list( $lead, $lead_errors ) = clean_waitlist( $raw );
		if ( $lead_errors ) {
			wp_safe_redirect( $back . '#bwpx-waitlist' );
			exit;
		}
		add_to_waitlist( $lead );
		wp_safe_redirect( add_query_arg( 'bwpx', 'waitlisted', $back ) . '#bwpx-waitlist' );
		exit;
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

// Emails the team when a Founding Membership is paid for. SureCart fires this when checkout
// finishes and again from its webhook, so each purchase is only emailed once.
add_action(
	'surecart/purchase_created',
	function ( $purchase ) {
		try {
			$product = $purchase->product ?? '';
			$product = is_object( $product ) ? ( $product->id ?? '' ) : (string) $product;
			if ( empty( $purchase->id ) || Documents\FOUNDING_PRODUCT !== $product ) {
				return;
			}
			// Test-mode payments are never real members.
			if ( isset( $purchase->live_mode ) && ! $purchase->live_mode ) {
				return;
			}
			if ( ! add_option( 'bwpx_paid_emailed_' . $purchase->id, time(), '', false ) ) {
				return;
			}

			$customer = $purchase->customer ?? '';
			$customer = is_object( $customer ) ? ( $customer->id ?? '' ) : (string) $customer;
			$found    = $customer ? \SureCart\Models\Customer::find( $customer ) : null;
			if ( ! $found || is_wp_error( $found ) || ! is_email( (string) ( $found->email ?? '' ) ) ) {
				return;
			}

			notify_team( $found, Documents\plan_for_price( $purchase->price ?? '' ) );
		} catch ( \Throwable $e ) {
			error_log( 'PadlX paid email failed: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	},
	25
);

/**
 * The team email for one paid founding member, with their sign-up answers when they filled in the form.
 */
function notify_team( $customer, $plan ) {
	$to = setting( 'notify_email' );
	if ( ! is_email( $to ) ) {
		return;
	}

	$email  = strtolower( (string) $customer->email );
	$user   = get_user_by( 'email', $email );
	$values = $user ? get_user_meta( $user->ID, META_ANSWERS, true ) : '';
	$values = is_array( $values ) ? $values : array();
	if ( ! $plan ) {
		$plan = $values['plan'] ?? ( $user ? get_user_meta( $user->ID, META_PLAN, true ) : '' );
	}

	$first = $values['first_name'] ?? (string) ( $customer->first_name ?? '' );
	$last  = $values['last_name'] ?? (string) ( $customer->last_name ?? '' );
	$name  = trim( $first . ' ' . $last );
	if ( '' === $name ) {
		$name = trim( (string) ( $customer->name ?? '' ) );
	}

	$body = '';
	if ( $values ) {
		$values['plan'] = $plan ? $plan : ( $values['plan'] ?? '' );
		foreach ( answer_lines( $values ) as $label => $value ) {
			$body .= $label . ': ' . $value . "\n";
		}
	} else {
		$body .= "They paid without filling in the sign-up form first, so we only have their checkout details.\n\n";
		$body .= 'Name: ' . ( '' === $name ? '—' : $name ) . "\n";
		$body .= 'Email: ' . $email . "\n";
		$body .= 'Payment plan: ' . plan_label( $plan ) . "\n";
	}

	if ( $user ) {
		$number = member_number( $user->ID );
		if ( $number ) {
			$body .= "\nMember number: " . format_number( $number ) . "\n";
		} elseif ( on_waiting_list( $email ) ) {
			$body .= "\nMember number: WAITING. They registered after the first 150. Give them a free number on their user profile:\n";
		} else {
			$body .= "\nMember number: Not assigned yet (email not on the founding list). Add it on their user profile:\n";
		}
		$body .= admin_url( 'user-edit.php?user_id=' . $user->ID ) . "\n";
	}
	$body .= "\nThey have paid for their Founding Membership.\n";

	wp_mail(
		$to,
		sprintf( 'Founding member sign-up: %s (%s)', '' === $name ? $email : $name, 'monthly' === $plan ? 'monthly' : 'annual' ),
		$body,
		array( 'Reply-To: ' . ( '' === $name ? '' : $name . ' ' ) . '<' . $email . '>' )
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
			$state['message'] = $saved['message'] ?? 'A few answers need another look. They are marked below.';
		}
	}

	return $state;
}
