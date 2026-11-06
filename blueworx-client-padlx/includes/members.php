<?php
/**
 * Saves each sign-up to the member's user profile, and shows it in the admin.
 * A member's founding number is entered on their user profile.
 */

namespace BlueWorx\PadlX;

defined( 'ABSPATH' ) || exit;

const META_ANSWERS  = 'bwpx_founding';
const META_PLAN     = 'bwpx_founding_plan';
const META_DATE     = 'bwpx_founding_date';
const META_WELCOMED = 'bwpx_founding_welcomed';
const META_NUMBER   = 'bwpx_member_number';

function format_number( $number ) {
	return 'No. ' . str_pad( (string) absint( $number ), 3, '0', STR_PAD_LEFT );
}

/**
 * The number on the member's profile, or 0 if none has been entered yet.
 */
function member_number( $user_id ) {
	return absint( get_user_meta( $user_id, META_NUMBER, true ) );
}

/**
 * The user who already has this number, other than the one given, or 0.
 */
function number_taken_by( $number, $user_id ) {
	$ids = get_users(
		array(
			'meta_key'   => META_NUMBER, // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value' => (string) $number, // phpcs:ignore WordPress.DB.SlowDBQuery
			'exclude'    => array( $user_id ),
			'fields'     => 'ID',
			'number'     => 1,
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

function is_founding_member( $user_id ) {
	return (bool) get_user_meta( $user_id, META_DATE, true );
}

/**
 * Finds the account for the sign-up email, or creates one, then saves the answers to it.
 */
function save_member( array $values ) {
	$email = strtolower( $values['email'] );
	$user  = get_user_by( 'email', $email );

	if ( $user ) {
		$user_id = $user->ID;
		// Only fill in a missing name: the form isn't signed in, so it shouldn't overwrite an existing one.
		foreach ( array( 'first_name', 'last_name' ) as $key ) {
			if ( '' === (string) get_user_meta( $user_id, $key, true ) ) {
				update_user_meta( $user_id, $key, $values[ $key ] );
			}
		}
	} else {
		$user_id = wp_insert_user(
			array(
				'user_login'   => $email,
				'user_email'   => $email,
				'user_pass'    => wp_generate_password( 24 ),
				'first_name'   => $values['first_name'],
				'last_name'    => $values['last_name'],
				'display_name' => trim( $values['first_name'] . ' ' . $values['last_name'] ),
				'role'         => get_option( 'default_role', 'subscriber' ),
			)
		);
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}
	}

	update_user_meta( $user_id, META_ANSWERS, $values );
	update_user_meta( $user_id, META_PLAN, $values['plan'] );
	update_user_meta( $user_id, META_DATE, time() );
	return $user_id;
}

/**
 * Plain-text list of answers, for the notification email and the profile screen.
 */
function answer_lines( array $values ) {
	$lines = array();
	foreach ( fields() as $key => $field ) {
		$value = $values[ $key ] ?? '';
		if ( 'plan' === $key ) {
			$value = plan_label( $value );
		}
		$lines[ $field['label'] ] = '' === $value ? '—' : $value;
	}
	return $lines;
}

// "Member number" field on every user's profile screen.
function render_number_field( $user ) {
	if ( ! current_user_can( 'list_users' ) ) {
		return;
	}
	$number = member_number( $user->ID );
	?>
	<h2><?php esc_html_e( 'Member number', 'blueworx-client-padlx' ); ?></h2>
	<table class="form-table" role="presentation"><tbody>
		<tr>
			<th><label for="bwpx-member-number"><?php esc_html_e( 'Founding member number', 'blueworx-client-padlx' ); ?></label></th>
			<td>
				<?php if ( current_user_can( 'edit_users' ) ) : ?>
					<input type="number" min="1" step="1" class="small-text" id="bwpx-member-number" name="bwpx_member_number" value="<?php echo esc_attr( $number ? (string) $number : '' ); ?>">
					<p class="description"><?php esc_html_e( 'Shown on the welcome page and in the sign-up email. Each number can only be used once.', 'blueworx-client-padlx' ); ?></p>
				<?php else : ?>
					<?php echo esc_html( $number ? format_number( $number ) : __( 'Not assigned yet', 'blueworx-client-padlx' ) ); ?>
				<?php endif; ?>
			</td>
		</tr>
	</tbody></table>
	<?php
}
add_action( 'show_user_profile', __NAMESPACE__ . '\render_number_field' );
add_action( 'edit_user_profile', __NAMESPACE__ . '\render_number_field' );

// Saves the number. WordPress has already checked the form is genuine by the time this runs.
add_action(
	'user_profile_update_errors',
	function ( $errors, $update, $user ) {
		if ( ! $update || ! isset( $_POST['bwpx_member_number'] ) || ! current_user_can( 'edit_users' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}
		$raw = trim( sanitize_text_field( wp_unslash( $_POST['bwpx_member_number'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( '' === $raw ) {
			delete_user_meta( $user->ID, META_NUMBER );
			return;
		}
		$number = absint( $raw );
		if ( ! ctype_digit( $raw ) || ! $number ) {
			$errors->add( 'bwpx_member_number', __( 'The member number must be a whole number above 0.', 'blueworx-client-padlx' ) );
			return;
		}
		if ( number_taken_by( $number, $user->ID ) ) {
			/* translators: %s: member number, e.g. No. 012 */
			$errors->add( 'bwpx_member_number', sprintf( __( '%s is already used by another member.', 'blueworx-client-padlx' ), format_number( $number ) ) );
			return;
		}
		update_user_meta( $user->ID, META_NUMBER, $number );
	},
	10,
	3
);

// "Founding member" section on each member's profile screen.
function render_profile_section( $user ) {
	if ( ! current_user_can( 'list_users' ) || ! is_founding_member( $user->ID ) ) {
		return;
	}
	$values = get_user_meta( $user->ID, META_ANSWERS, true );
	?>
	<h2><?php esc_html_e( 'Founding member', 'blueworx-client-padlx' ); ?></h2>
	<table class="form-table" role="presentation"><tbody>
		<tr><th><?php esc_html_e( 'Signed up', 'blueworx-client-padlx' ); ?></th><td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) get_user_meta( $user->ID, META_DATE, true ) ) ); ?></td></tr>
		<?php foreach ( answer_lines( is_array( $values ) ? $values : array() ) as $label => $value ) : ?>
			<tr><th><?php echo esc_html( $label ); ?></th><td><?php echo nl2br( esc_html( $value ) ); ?></td></tr>
		<?php endforeach; ?>
	</tbody></table>
	<?php
}
add_action( 'show_user_profile', __NAMESPACE__ . '\render_profile_section' );
add_action( 'edit_user_profile', __NAMESPACE__ . '\render_profile_section' );

// "Founding member" column and filter on the Users list.
add_filter(
	'manage_users_columns',
	function ( $columns ) {
		$columns['bwpx_founding'] = __( 'Founding member', 'blueworx-client-padlx' );
		return $columns;
	}
);

add_filter(
	'manage_users_custom_column',
	function ( $output, $column, $user_id ) {
		if ( 'bwpx_founding' !== $column ) {
			return $output;
		}
		$number  = member_number( $user_id );
		$founder = is_founding_member( $user_id );
		$parts   = array();
		if ( $number ) {
			$parts[] = format_number( $number );
		} elseif ( $founder ) {
			$parts[] = __( 'No number yet', 'blueworx-client-padlx' );
		}
		if ( $founder ) {
			$parts[] = plan_label( get_user_meta( $user_id, META_PLAN, true ) );
		}
		return esc_html( implode( ' · ', $parts ) );
	},
	10,
	3
);

add_filter(
	'views_users',
	function ( $views ) {
		$count   = count( get_users( array( 'meta_key' => META_DATE, 'fields' => 'ID' ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		$current = ! empty( $_GET['bwpx_founding'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$views['bwpx_founding'] = sprintf(
			'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
			esc_url( add_query_arg( 'bwpx_founding', 1, admin_url( 'users.php' ) ) ),
			$current ? ' class="current" aria-current="page"' : '',
			esc_html__( 'Founding members', 'blueworx-client-padlx' ),
			$count
		);
		return $views;
	}
);

add_action(
	'pre_get_users',
	function ( $query ) {
		global $pagenow;
		if ( is_admin() && 'users.php' === $pagenow && ! empty( $_GET['bwpx_founding'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$query->set( 'meta_key', META_DATE );
		}
	}
);
