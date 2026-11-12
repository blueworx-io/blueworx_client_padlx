<?php
/**
 * Checkout: swaps SureCart's terms switch for the membership schedule and a required tick box,
 * and records the acceptance when the order completes.
 */

namespace BlueWorx\PadlX\Documents;

use const BlueWorx\PadlX\URL;
use const BlueWorx\PadlX\VERSION;

defined( 'ABSPATH' ) || exit;

// SureCart saves this field on the order, and cuts names off at 40 characters.
const ACCEPTED_FIELD = 'Schedule and Terms accepted';
const META_ACCEPTED  = 'padlx_terms_accepted_at';

add_filter(
	'render_block_surecart/switch',
	function ( $content, $block ) {
		if ( false === stripos( $block['attrs']['name'] ?? '', 'terms and conditions' ) ) {
			return $content;
		}

		wp_enqueue_style( 'padlx-sf', URL . 'assets/css/membership-documents.css', array(), VERSION );
		wp_enqueue_script( 'padlx-sf-checkout', URL . 'assets/js/checkout.js', array(), VERSION, true );

		$s = settings();
		ob_start();
		?>
		<div class="padlx-schedules">
			<p class="padlx-schedules__title">Membership schedule</p>
			<?php foreach ( array_keys( memberships() ) as $id ) : ?>
				<div class="padlx-schedule" data-product="<?php echo esc_attr( $id ); ?>" hidden><?php echo wp_kses_post( wpautop( schedule_text( $id ) ) ); ?></div>
			<?php endforeach; ?>
			<p class="padlx-schedules__links">
				Please also read our <a href="<?php echo esc_url( $s['terms_url'] ); ?>" target="_blank" rel="noopener">Terms &amp; Conditions</a>
				and <a href="<?php echo esc_url( $s['privacy_url'] ); ?>" target="_blank" rel="noopener">Privacy Policy</a>.
			</p>
		</div>
		<sc-checkbox class="padlx-accept" name="<?php echo esc_attr( ACCEPTED_FIELD ); ?>" value="Yes" required>I have read and accept the membership schedule and Terms &amp; Conditions</sc-checkbox>
		<?php
		return ob_get_clean();
	},
	10,
	2
);

/**
 * Finds the customer's ticked acceptance on a completed checkout.
 */
function accepted_value( $checkout ) {
	$meta = (array) ( $checkout->metadata ?? array() );
	return $meta[ ACCEPTED_FIELD ] ?? '';
}

add_action(
	'surecart/checkout_confirmed',
	function ( $checkout ) {
		// Never let record keeping get in the way of someone's purchase.
		try {
			$accepted = accepted_value( $checkout );
			if ( ! $accepted || empty( $checkout->id ) || ! add_option( 'padlx_sf_accepted_' . $checkout->id, time(), '', false ) ) {
				return;
			}

			$email = (string) ( $checkout->email ?? '' );
			add_log_row( 'Accepted schedule and Terms & Conditions', $email, (string) ( $checkout->number ?? $checkout->id ), 'Ticked at ' . $accepted );

			$user = $email ? get_user_by( 'email', $email ) : false;
			if ( $user ) {
				update_user_meta( $user->ID, META_ACCEPTED, time() );
			}
		} catch ( \Throwable $e ) {
			add_log_row( 'Could not record acceptance', '', '', $e->getMessage() );
		}
	}
);
