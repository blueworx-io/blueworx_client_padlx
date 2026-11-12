<?php
/**
 * Beck's list of founding member numbers: pasted in once, then each member gets their number from their email when they sign up.
 */

namespace BlueWorx\PadlX;

defined( 'ABSPATH' ) || exit;

const NUMBER_LIST_OPTION = 'bwpx_number_list';
const NUMBER_LIST_REPORT = 'bwpx_number_list_report';

function number_list() {
	$list = get_option( NUMBER_LIST_OPTION, array() );
	return is_array( $list ) ? $list : array();
}

/**
 * Reads pasted lines holding a number and an email, in either order.
 *
 * @return array The list (email => number) and the lines that couldn't be used.
 */
function parse_number_list( $text ) {
	$list     = array();
	$problems = array();

	foreach ( preg_split( '/\R/', (string) $text ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		if ( ! preg_match( '/[A-Z0-9._%+\'-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $line, $email ) ) {
			$problems[] = $line . ' (no email)';
			continue;
		}
		$email = strtolower( $email[0] );
		$rest  = str_ireplace( $email, ' ', $line );
		// A line with no number (Beck marks these "O") is someone past the 150: keep them, so they can still sign up.
		$number = preg_match( '/(?<![\d.])0*([1-9]\d{0,3})(?![\d.])/', $rest, $m ) ? (int) $m[1] : 0;
		if ( isset( $list[ $email ] ) ) {
			$problems[] = $line . ' (email is already on the list)';
			continue;
		}
		if ( $number && in_array( $number, $list, true ) ) {
			$problems[] = $line . ' (number is already on the list)';
			continue;
		}
		$list[ $email ] = $number;
	}

	return array( $list, $problems );
}

/**
 * Whether this email is on the list without a number yet (registered after the first 150).
 */
function on_waiting_list( $email ) {
	$list  = number_list();
	$email = strtolower( trim( (string) $email ) );
	return array_key_exists( $email, $list ) && 0 === (int) $list[ $email ];
}

/**
 * Gives a user their number from the list, unless they have one or someone else holds it.
 *
 * @return bool Whether a number was given.
 */
function assign_number_from_list( $user_id, $email ) {
	$list  = number_list();
	$email = strtolower( trim( (string) $email ) );
	if ( member_number( $user_id ) || empty( $list[ $email ] ) || number_taken_by( $list[ $email ], $user_id ) ) {
		return false;
	}
	update_user_meta( $user_id, META_NUMBER, (int) $list[ $email ] );
	return true;
}

add_action(
	'admin_post_bwpx_save_number_list',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'blueworx-client-padlx' ) );
		}
		check_admin_referer( 'bwpx_save_number_list' );

		list( $list, $problems ) = parse_number_list( sanitize_textarea_field( wp_unslash( $_POST['bwpx_number_list'] ?? '' ) ) );
		update_option( NUMBER_LIST_OPTION, $list, false );

		// Anyone who already has an account gets their number now.
		$given = 0;
		foreach ( array_keys( $list ) as $email ) {
			$user = get_user_by( 'email', $email );
			if ( $user && assign_number_from_list( $user->ID, $email ) ) {
				++$given;
			}
		}

		set_transient(
			NUMBER_LIST_REPORT,
			array(
				'saved'    => count( $list ),
				'waiting'  => count( array_filter( $list, function ( $n ) { return 0 === (int) $n; } ) ),
				'given'    => $given,
				'problems' => $problems,
			),
			MINUTE_IN_SECONDS * 5
		);
		wp_safe_redirect( admin_url( 'users.php?page=bwpx-settings' ) );
		exit;
	}
);

function render_number_list_form() {
	$lines = array();
	foreach ( number_list() as $email => $number ) {
		$lines[] = ( $number ? $number : 'waiting' ) . ',' . $email;
	}
	$report = get_transient( NUMBER_LIST_REPORT );
	delete_transient( NUMBER_LIST_REPORT );
	?>
	<h2><?php esc_html_e( 'Founding member numbers', 'blueworx-client-padlx' ); ?></h2>
	<p><?php esc_html_e( 'Paste the number and email columns from the founding member list, one member per line. Each member gets their number when they sign up with that email. A line with an email but no number is someone past the 150: they can sign up while founding spots are left, and the sign-up email flags them as waiting for a number. Once the spots are gone they join the waitlist like everyone else. A number typed on a user\'s profile is never overwritten.', 'blueworx-client-padlx' ); ?></p>

	<?php if ( is_array( $report ) ) : ?>
		<div class="notice notice-<?php echo $report['problems'] ? 'warning' : 'success'; ?> inline">
			<p>
				<?php
				/* translators: 1: people saved, 2: existing members given their number, 3: people waiting for a number */
				echo esc_html( sprintf( __( '%1$d people saved (%3$d waiting for a number). %2$d existing members were given their number.', 'blueworx-client-padlx' ), $report['saved'], $report['given'], $report['waiting'] ) );
				?>
			</p>
			<?php if ( $report['problems'] ) : ?>
				<p><?php esc_html_e( 'These lines were not used:', 'blueworx-client-padlx' ); ?></p>
				<ul>
					<?php foreach ( $report['problems'] as $problem ) : ?>
						<li><?php echo esc_html( $problem ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="bwpx_save_number_list">
		<?php wp_nonce_field( 'bwpx_save_number_list' ); ?>
		<textarea name="bwpx_number_list" class="large-text code" rows="10" aria-label="<?php esc_attr_e( 'Founding member numbers', 'blueworx-client-padlx' ); ?>"><?php echo esc_textarea( implode( "\n", $lines ) ); ?></textarea>
		<?php submit_button( __( 'Save numbers', 'blueworx-client-padlx' ) ); ?>
	</form>
	<?php
}
