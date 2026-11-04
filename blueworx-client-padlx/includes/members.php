<?php
/**
 * Saves each sign-up to the member's user profile, and shows it in the admin.
 * A member's founding number is their user ID.
 */

namespace BlueWorx\PadlX;

defined( 'ABSPATH' ) || exit;

const META_ANSWERS  = 'bwpx_founding';
const META_PLAN     = 'bwpx_founding_plan';
const META_DATE     = 'bwpx_founding_date';
const META_WELCOMED = 'bwpx_founding_welcomed';

function format_number( $number ) {
	return 'No. ' . str_pad( (string) absint( $number ), 3, '0', STR_PAD_LEFT );
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

// "Founding member" section on each member's profile screen.
function render_profile_section( $user ) {
	if ( ! current_user_can( 'list_users' ) || ! is_founding_member( $user->ID ) ) {
		return;
	}
	$values = get_user_meta( $user->ID, META_ANSWERS, true );
	?>
	<h2><?php esc_html_e( 'Founding member', 'blueworx-client-padlx' ); ?></h2>
	<table class="form-table" role="presentation"><tbody>
		<tr><th><?php esc_html_e( 'Member number', 'blueworx-client-padlx' ); ?></th><td><?php echo esc_html( format_number( $user->ID ) ); ?></td></tr>
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
		if ( 'bwpx_founding' !== $column || ! is_founding_member( $user_id ) ) {
			return $output;
		}
		return esc_html( format_number( $user_id ) . ' · ' . plan_label( get_user_meta( $user_id, META_PLAN, true ) ) );
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
