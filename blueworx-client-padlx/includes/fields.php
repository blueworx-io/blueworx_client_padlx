<?php
/**
 * Questions on the founding member sign-up form.
 */

namespace BlueWorx\PadlX;

defined( 'ABSPATH' ) || exit;

function states() {
	return array( 'ACT', 'NSW', 'NT', 'QLD', 'SA', 'TAS', 'VIC', 'WA' );
}

/**
 * Every field, in the order it appears. Option fields list the values they accept.
 */
function fields() {
	return array(
		'first_name'      => array( 'label' => 'First name', 'type' => 'text', 'required' => true ),
		'last_name'       => array( 'label' => 'Last name', 'type' => 'text', 'required' => true ),
		'email'           => array( 'label' => 'Email', 'type' => 'email', 'required' => true ),
		'mobile'          => array( 'label' => 'Mobile', 'type' => 'tel', 'required' => true ),
		'dob'             => array( 'label' => 'Date of birth', 'type' => 'dob', 'required' => true ),
		'gender'          => array(
			'label'    => 'Gender',
			'type'     => 'select',
			'required' => false,
			'options'  => array( 'Female', 'Male', 'Non-binary', 'Prefer not to say' ),
		),
		'street'          => array( 'label' => 'Street address', 'type' => 'text', 'required' => true ),
		'suburb'          => array( 'label' => 'Suburb', 'type' => 'text', 'required' => true ),
		'state'           => array(
			'label'    => 'State',
			'type'     => 'select',
			'required' => true,
			'options'  => states(),
		),
		'postcode'        => array( 'label' => 'Postcode', 'type' => 'postcode', 'required' => true ),
		'ec_name'         => array( 'label' => 'Emergency contact name', 'type' => 'text', 'required' => true ),
		'ec_relationship' => array( 'label' => 'Emergency contact relationship', 'type' => 'text', 'required' => true ),
		'ec_mobile'       => array( 'label' => 'Emergency contact mobile', 'type' => 'tel', 'required' => true ),
		'notes'           => array( 'label' => 'Anything we should know?', 'type' => 'textarea', 'required' => false ),
		'venue'           => array(
			'label'    => 'Where will you play most?',
			'type'     => 'choice',
			'required' => true,
			'options'  => array( 'Burleigh Heads (outdoor)', 'Arundel (indoor)', 'Both, equally' ),
		),
		'level'           => array(
			'label'    => "Where's your game at?",
			'type'     => 'choice',
			'required' => true,
			'options'  => array( 'Brand new', 'Had a few hits', 'Social regular', 'Competitive' ),
		),
		'rating'          => array( 'label' => 'Playtomic rating', 'type' => 'text', 'required' => false ),
		'shot'            => array(
			'label'    => 'Your signature shot',
			'type'     => 'choice',
			'required' => true,
			'options'  => array( 'The Smash', 'The Bandeja', 'The Víbora', 'The Lob', 'The Chiquita', 'Still working it out' ),
			'hints'    => array( 'Go big or go home', 'Smooth and steady', 'All about the spin', 'Patience pays', 'Soft and sneaky', 'Best answer on the form' ),
		),
		'tshirt'          => array(
			'label'    => 'T-shirt size',
			'type'     => 'choice',
			'required' => true,
			'options'  => array( 'XS', 'S', 'M', 'L', 'XL', '2XL', '3XL' ),
		),
		'drink'           => array(
			'label'    => 'Post-match drink of choice',
			'type'     => 'choice',
			'required' => true,
			'options'  => array( 'Coffee', 'Smoothie or juice', 'Kombucha', 'Soft drink', 'Cold beer', 'Wine or spritz', 'Water, thanks', 'Other' ),
		),
		'plan'            => array(
			'label'    => 'Payment plan',
			'type'     => 'choice',
			'required' => true,
			'options'  => array( 'annual', 'monthly' ),
		),
	);
}

function plan_label( $plan ) {
	return 'monthly' === $plan ? 'Monthly ($229 a month)' : 'Annual ($2,519 a year)';
}

/**
 * Cleans one submitted value. Returns array( value, error ).
 */
function clean_field( $field, $raw ) {
	$raw = is_string( $raw ) ? wp_unslash( $raw ) : '';

	switch ( $field['type'] ) {
		case 'textarea':
			$value = sanitize_textarea_field( $raw );
			break;
		case 'email':
			$value = sanitize_email( $raw );
			if ( '' !== trim( $raw ) && ! is_email( $value ) ) {
				return array( $value, 'Please enter a valid email address.' );
			}
			break;
		default:
			$value = sanitize_text_field( $raw );
	}

	if ( '' === $value ) {
		return array( '', $field['required'] ? 'Please fill this in.' : '' );
	}

	if ( isset( $field['options'] ) && ! in_array( $value, $field['options'], true ) ) {
		return array( '', 'Please choose one of the options.' );
	}

	if ( 'dob' === $field['type'] ) {
		$parts = preg_split( '/\D+/', $value, -1, PREG_SPLIT_NO_EMPTY );
		if ( count( $parts ) !== 3 || ! checkdate( (int) $parts[1], (int) $parts[0], (int) $parts[2] ) || (int) $parts[2] < 1900 ) {
			return array( $value, 'Please use DD / MM / YYYY.' );
		}
		$value = sprintf( '%02d/%02d/%04d', $parts[0], $parts[1], $parts[2] );
	}

	if ( 'postcode' === $field['type'] && ! preg_match( '/^\d{4}$/', $value ) ) {
		return array( $value, 'Please enter a 4-digit postcode.' );
	}

	return array( $value, '' );
}
