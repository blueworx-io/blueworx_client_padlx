<?php
/**
 * After a membership purchase: emails the member the T&Cs, Privacy Policy and their schedule.
 */

namespace BlueWorx\PadlX\Documents;

use SureCart\Models\Customer;

defined( 'ABSPATH' ) || exit;

// SureCart fires this when checkout finishes and again from its webhook, so each purchase is only emailed once.
add_action(
	'surecart/purchase_created',
	function ( $purchase ) {
		try {
			$product  = $purchase->product ?? '';
			$product  = is_object( $product ) ? ( $product->id ?? '' ) : (string) $product;
			$customer = $purchase->customer ?? '';
			$customer = is_object( $customer ) ? ( $customer->id ?? '' ) : (string) $customer;

			if ( empty( $purchase->id ) || ! isset( memberships()[ $product ] ) || ! $customer || ! add_option( 'padlx_sf_emailed_' . $purchase->id, time(), '', false ) ) {
				return;
			}

			$found = Customer::find( $customer );
			$email = is_wp_error( $found ) ? '' : (string) ( $found->email ?? '' );
			if ( ! is_email( $email ) ) {
				add_log_row( 'Documents email failed', $email, '', 'No email address found for purchase ' . $purchase->id );
				return;
			}

			$name = memberships()[ $product ];
			$sent = wp_mail( $email, 'Your PADLX membership documents', document_email( $name, $product, plan_for_price( $purchase->price ?? '' ) ), array( 'Content-Type: text/html; charset=UTF-8', 'Reply-To: PADLX <info@padlx.com.au>' ) );
			add_log_row( $sent ? 'Documents email sent' : 'Documents email failed', $email, '', $name );
		} catch ( \Throwable $e ) {
			add_log_row( 'Documents email failed', '', '', $e->getMessage() );
		}
	},
	30
);

function document_email( $name, $product_id, $plan = '' ) {
	$s = settings();
	ob_start();
	?>
	<p>Thanks for joining PADLX. Here are the documents for your <?php echo esc_html( $name ); ?>. Please keep this email for your records.</p>
	<ul>
		<li><a href="<?php echo esc_url( $s['terms_url'] ); ?>">Terms &amp; Conditions</a></li>
		<li><a href="<?php echo esc_url( $s['privacy_url'] ); ?>">Privacy Policy</a></li>
	</ul>
	<p><strong>Your membership schedule</strong></p>
	<?php echo wp_kses_post( wpautop( schedule_text( $product_id, $plan ) ) ); ?>
	<p>Questions? Email <a href="mailto:info@padlx.com.au">info@padlx.com.au</a>.</p>
	<?php
	return ob_get_clean();
}
