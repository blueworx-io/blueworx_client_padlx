<?php
/**
 * Once every founding spot is sold, or founding sales close, the sign-up page becomes a short
 * form to leave details, so the leads are kept.
 */

namespace BlueWorx\PadlX;

defined( 'ABSPATH' ) || exit;

const WAITLIST_ACTION = 'bwpx_founding_waitlist';
const WAITLIST_OPTION = 'bwpx_waitlist';
const SPOTS_CACHE     = 'bwpx_founding_spots';

/**
 * Founding spots left, from SureCart's stock count. Null when SureCart can't say, or isn't limiting them.
 */
function founding_spots() {
	$cached = get_transient( SPOTS_CACHE );
	if ( is_array( $cached ) ) {
		return $cached['spots'];
	}

	$spots = null;
	if ( class_exists( '\SureCart\Models\Product' ) ) {
		try {
			$product = \SureCart\Models\Product::find( Documents\FOUNDING_PRODUCT );
			if ( ! is_wp_error( $product ) && ! empty( $product->stock_enabled ) && empty( $product->allow_out_of_stock_purchases ) && is_numeric( $product->available_stock ?? null ) ) {
				$spots = (int) $product->available_stock;
			}
		} catch ( \Throwable $e ) {
			$spots = null;
		}
	}

	// Checked at most once a minute, so the page stays quick.
	set_transient( SPOTS_CACHE, array( 'spots' => $spots ), MINUTE_IN_SECONDS );
	return $spots;
}

/**
 * True once a 'Y-m-d H:i' moment in the site's timezone has passed.
 */
function has_passed( $when ) {
	$moment = \DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $when, wp_timezone() );
	return $moment && time() >= $moment->getTimestamp();
}

/**
 * Admins can see the closed page early by adding ?bwpx_closed=1.
 */
function closed_preview() {
	return isset( $_GET['bwpx_closed'] ) && current_user_can( 'manage_options' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

function founding_closed() {
	return closed_preview() || has_passed( FOUNDING_CLOSES );
}

/**
 * The $300 wallet offer shows until it ends. Admins can see the page without it by adding ?bwpx_offer_ended=1.
 */
function offer_open() {
	$preview = isset( $_GET['bwpx_offer_ended'] ) && current_user_can( 'manage_options' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return ! $preview && ! has_passed( OFFER_ENDS );
}

function founding_full() {
	$spots = founding_spots();
	return null !== $spots && $spots <= 0;
}

// A sale changes the count, so look again straight away.
add_action(
	'surecart/purchase_created',
	function () {
		delete_transient( SPOTS_CACHE );
	}
);

/**
 * Admins can see the waitlist page before the spots run out by adding ?bwpx_waitlist=1.
 */
function waitlist_preview() {
	return isset( $_GET['bwpx_waitlist'] ) && current_user_can( 'manage_options' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

function waitlist_joined() {
	return isset( $_GET['bwpx'] ) && 'waitlisted' === sanitize_key( wp_unslash( $_GET['bwpx'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

function waitlist_showing() {
	return waitlist_preview() || waitlist_joined() || founding_full() || founding_closed();
}

function waitlist_url() {
	$url = get_permalink( get_queried_object_id() );
	if ( closed_preview() ) {
		return add_query_arg( 'bwpx_closed', 1, $url );
	}
	return waitlist_preview() ? add_query_arg( 'bwpx_waitlist', 1, $url ) : $url;
}

function waitlist() {
	$list = get_option( WAITLIST_OPTION, array() );
	return is_array( $list ) ? $list : array();
}

/**
 * Saves someone to the waitlist and tells the team, once per email address.
 */
function add_to_waitlist( array $values ) {
	$list  = waitlist();
	$email = strtolower( $values['email'] );
	$new   = ! isset( $list[ $email ] );

	$list[ $email ] = array(
		'time'       => $new ? time() : $list[ $email ]['time'],
		'first_name' => $values['first_name'],
		'last_name'  => $values['last_name'],
		'mobile'     => $values['mobile'],
	);
	update_option( WAITLIST_OPTION, $list, false );

	$to = setting( 'notify_email' );
	if ( ! $new || ! is_email( $to ) ) {
		return;
	}
	$closed = founding_closed();
	wp_mail(
		$to,
		sprintf( $closed ? 'Membership enquiry: %s %s' : 'Founding waitlist: %s %s', $values['first_name'], $values['last_name'] ),
		sprintf(
			"%s\n\nName: %s %s\nEmail: %s\nMobile: %s\n\nPeople on the waitlist: %d\n%s\n",
			$closed ? 'Founding memberships have closed, so this person left their details to hear about membership.' : 'All founding spots are taken, so this person joined the waitlist.',
			$values['first_name'],
			$values['last_name'],
			$email,
			$values['mobile'],
			count( $list ),
			admin_url( 'users.php?page=bwpx-settings' )
		),
		array( 'Reply-To: ' . $values['first_name'] . ' ' . $values['last_name'] . ' <' . $email . '>' )
	);
}

/**
 * Cleans the four waitlist answers. Returns array( values, errors ).
 */
function clean_waitlist( array $raw ) {
	$values = array();
	$errors = array();
	foreach ( array( 'first_name', 'last_name', 'email', 'mobile' ) as $key ) {
		list( $value, $error ) = clean_field( fields()[ $key ], $raw[ $key ] ?? '' );
		$values[ $key ]        = $value;
		if ( $error ) {
			$errors[ $key ] = $error;
		}
	}
	return array( $values, $errors );
}

add_action(
	'template_redirect',
	function () {
		if ( isset( $_POST['bwpx_action'] ) && WAITLIST_ACTION === $_POST['bwpx_action'] && TEMPLATE_SIGNUP === current_template() ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked in handle_waitlist().
			handle_waitlist();
		}
	},
	1
);

function handle_waitlist() {
	$back = waitlist_url();

	if ( ! isset( $_POST['_bwpx_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_bwpx_nonce'] ), WAITLIST_ACTION ) ) {
		wp_safe_redirect( add_query_arg( 'bwpx', 'expired', $back ) . '#bwpx-waitlist' );
		exit;
	}

	// Bots fill in the hidden field; people never see it.
	if ( ! empty( $_POST['bwpx_website'] ) ) {
		wp_safe_redirect( $back );
		exit;
	}

	$raw                     = isset( $_POST['bwpx'] ) && is_array( $_POST['bwpx'] ) ? $_POST['bwpx'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- cleaned per field in clean_waitlist().
	list( $values, $errors ) = clean_waitlist( $raw );

	// Blocked emails are quietly sent back to the page, with nothing saved or emailed.
	if ( blocked_email( $raw['email'] ?? '' ) ) {
		wp_safe_redirect( $back );
		exit;
	}

	$problem = human_check_problem();
	if ( $problem ) {
		back_to_form( $back, $values, $problem, '#bwpx-waitlist' );
	}

	if ( $errors ) {
		$token = strtolower( wp_generate_password( 16, false ) );
		set_transient( 'bwpx_form_' . $token, array( 'values' => $values, 'errors' => $errors ), 30 * MINUTE_IN_SECONDS );
		wp_safe_redirect( add_query_arg( 'bwpx', $token, $back ) . '#bwpx-waitlist' );
		exit;
	}

	add_to_waitlist( $values );
	wp_safe_redirect( add_query_arg( 'bwpx', 'waitlisted', $back ) . '#bwpx-waitlist' );
	exit;
}

function render_waitlist_admin() {
	$spots = founding_spots();
	$list  = waitlist();
	$pages = get_option( PAGES_OPTION, array() );
	?>
	<h2><?php esc_html_e( 'Founding waitlist', 'blueworx-client-padlx' ); ?></h2>
	<p>
		<?php
		if ( null === $spots ) {
			esc_html_e( 'SureCart is not limiting the Founding Membership, so the sign-up page stays open.', 'blueworx-client-padlx' );
		} elseif ( $spots > 0 ) {
			/* translators: %d: founding spots left */
			echo esc_html( sprintf( __( 'Founding spots left: %d. When this reaches 0, the sign-up page turns into a waitlist form.', 'blueworx-client-padlx' ), $spots ) );
		} else {
			esc_html_e( 'All founding spots are taken. The sign-up page is now a waitlist form.', 'blueworx-client-padlx' );
		}
		?>
	</p>
	<p>
		<?php esc_html_e( 'The $300 wallet offer hides itself at 9pm on 25 November. Founding sales close at 9pm on 30 November, when the sign-up page becomes a form to leave details.', 'blueworx-client-padlx' ); ?>
		<?php if ( ! empty( $pages['signup'] ) && get_post( $pages['signup'] ) ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'bwpx_offer_ended', 1, get_permalink( $pages['signup'] ) ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Preview without the offer', 'blueworx-client-padlx' ); ?></a>
			&nbsp;·&nbsp;<a href="<?php echo esc_url( add_query_arg( 'bwpx_closed', 1, get_permalink( $pages['signup'] ) ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Preview the closed page', 'blueworx-client-padlx' ); ?></a>
		<?php endif; ?>
		<?php if ( ! empty( $pages['signup'] ) && get_post( $pages['signup'] ) ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'bwpx_waitlist', 1, get_permalink( $pages['signup'] ) ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Preview the waitlist page', 'blueworx-client-padlx' ); ?></a>
		<?php endif; ?>
	</p>
	<?php if ( ! $list ) : ?>
		<p><?php esc_html_e( 'Nobody is on the waitlist yet.', 'blueworx-client-padlx' ); ?></p>
	<?php else : ?>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Joined', 'blueworx-client-padlx' ); ?></th><th><?php esc_html_e( 'Name', 'blueworx-client-padlx' ); ?></th><th><?php esc_html_e( 'Email', 'blueworx-client-padlx' ); ?></th><th><?php esc_html_e( 'Mobile', 'blueworx-client-padlx' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( $list as $email => $row ) : ?>
				<tr>
					<td><?php echo esc_html( wp_date( 'j M Y, g:i a', (int) $row['time'] ) ); ?></td>
					<td><?php echo esc_html( $row['first_name'] . ' ' . $row['last_name'] ); ?></td>
					<td><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></td>
					<td><?php echo esc_html( $row['mobile'] ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
	<?php
}
