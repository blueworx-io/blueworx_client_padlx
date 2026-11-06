<?php
/**
 * Settings screen (Settings → PADLX Membership Documents): document links, schedules, under-18 wording, and the record of acceptances and emails.
 */

namespace BlueWorx\PadlX\Documents;

defined( 'ABSPATH' ) || exit;

const OPTION = 'padlx_sf_settings';
const LOG    = 'padlx_sf_log';

/**
 * SureCart membership products, by product ID.
 */
function memberships() {
	return array(
		'384835d8-569b-4e93-8519-55506ba75e6d' => 'Founding Membership',
		'3c64d784-91cf-45c9-b609-ebdbc3437f34' => 'Premier Membership - Burleigh Heads',
		'c4b7557e-2347-462e-9f21-970abedd53f8' => 'Premier Membership - Arundel',
		'62d2a07f-ab05-432c-b16b-b95d28baef9c' => 'Social Membership - Burleigh Heads',
		'9164594b-feea-418f-b4e6-6c35ee6ce8fe' => 'Social Membership - Arundel',
	);
}

const FOUNDING_PRODUCT = '384835d8-569b-4e93-8519-55506ba75e6d';
const UNDER_18_TEXT    = 'You must be over 18 to register for a membership, or have a parent or legal guardian do so on your behalf.';

function settings() {
	$saved = get_option( OPTION, array() );
	$all   = wp_parse_args(
		is_array( $saved ) ? $saved : array(),
		array(
			'terms_url'     => home_url( '/terms-and-conditions/' ),
			'privacy_url'   => home_url( '/privacy-policy/' ),
			'under_18_text' => UNDER_18_TEXT,
			'schedules'     => array(),
		)
	);
	// The first version could save its placeholder wording, so replace that with the final wording.
	if ( '' === trim( $all['under_18_text'] ) || false !== strpos( $all['under_18_text'], '[PLACEHOLDER' ) ) {
		$all['under_18_text'] = UNDER_18_TEXT;
	}
	return $all;
}

/**
 * Annexure A of the Membership Terms and Conditions dated 6 November 2026.
 */
function founding_schedule() {
	$rows = array(
		'Price (GST-inclusive)'                      => '$229/month, or $2,519/year paid annually (1 month free)',
		'Minimum term'                               => '12 months, either payment method',
		'Venue access &amp; availability'            => 'Both venues, plus all future PadlX venues — capped at 150 members total',
		'Court hire discount'                        => 'Free off-peak; $15 peak per hour your share',
		'Advance booking window'                     => '21 days',
		'Food, beverage &amp; pro shop'              => '20% off at any PadlX venue',
		'Wellness centre (ice baths, infrared sauna)' => 'Unlimited access, any time',
		'Club lounge / deck'                         => 'Yes — Arundel and future venues',
		'Events'                                     => 'Exclusive founder-only events; priority spectator passes for major competitions',
		'Other perks'                                => 'Welcome gift; numbered founding member recognition; rate locked for life of membership',
		'Court booking cancellation'                 => 'Up to 24 hours before play',
		'Membership cancellation'                    => 'Part 4 and Part 7 apply',
	);
	$html = '<table>';
	foreach ( $rows as $label => $value ) {
		$html .= '<tr><td><strong>' . $label . '</strong></td><td>' . $value . '</td></tr>';
	}
	return $html . '</table><p>Full Terms and Conditions apply — see the PadlX Membership Terms and Conditions. This Schedule may be updated by PadlX from time to time in accordance with clause 12.</p>';
}

function schedule_text( $product_id ) {
	$schedules = settings()['schedules'];
	if ( ! empty( $schedules[ $product_id ] ) && false === strpos( $schedules[ $product_id ], '[PLACEHOLDER' ) ) {
		return $schedules[ $product_id ];
	}
	if ( FOUNDING_PRODUCT === $product_id ) {
		return founding_schedule();
	}
	$name = memberships()[ $product_id ] ?? 'this membership';
	return 'The membership schedule for ' . $name . ' is not available yet.';
}

add_action(
	'admin_init',
	function () {
		register_setting(
			'padlx_sf',
			OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => __NAMESPACE__ . '\sanitize_settings',
			)
		);
	}
);

function sanitize_settings( $input ) {
	$input     = is_array( $input ) ? $input : array();
	$schedules = array();
	foreach ( array_keys( memberships() ) as $id ) {
		$schedules[ $id ] = wp_kses_post( $input['schedules'][ $id ] ?? '' );
	}
	return array(
		'terms_url'     => esc_url_raw( trim( $input['terms_url'] ?? '' ) ),
		'privacy_url'   => esc_url_raw( trim( $input['privacy_url'] ?? '' ) ),
		'under_18_text' => sanitize_textarea_field( $input['under_18_text'] ?? '' ),
		'schedules'     => $schedules,
	);
}

/**
 * Adds a line to the permanent record shown on the settings screen.
 */
function add_log_row( $what, $email, $order, $detail ) {
	$log   = get_option( LOG, array() );
	$log   = is_array( $log ) ? $log : array();
	$log[] = array(
		'time'   => time(),
		'what'   => $what,
		'email'  => $email,
		'order'  => $order,
		'detail' => $detail,
	);
	update_option( LOG, $log, false );
}

add_action(
	'admin_menu',
	function () {
		add_options_page( 'PADLX Membership Documents', 'PADLX Membership Documents', 'manage_options', 'padlx-membership-documents', __NAMESPACE__ . '\render_settings_page' );
	}
);

function render_settings_page() {
	$s = settings();
	?>
	<div class="wrap">
		<h1>PADLX Membership Documents</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'padlx_sf' ); ?>

			<h2>Document links</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="padlx-terms">Terms &amp; Conditions page</label></th>
					<td><input type="url" id="padlx-terms" class="regular-text" name="<?php echo esc_attr( OPTION ); ?>[terms_url]" value="<?php echo esc_attr( $s['terms_url'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="padlx-privacy">Privacy Policy page</label></th>
					<td><input type="url" id="padlx-privacy" class="regular-text" name="<?php echo esc_attr( OPTION ); ?>[privacy_url]" value="<?php echo esc_attr( $s['privacy_url'] ); ?>"></td>
				</tr>
			</table>

			<h2>Membership schedules</h2>
			<p>Shown at checkout for the membership being bought, and included in the documents email.</p>
			<table class="form-table" role="presentation">
				<?php foreach ( memberships() as $id => $name ) : ?>
					<tr>
						<th scope="row"><label for="padlx-schedule-<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $name ); ?></label></th>
						<td><textarea id="padlx-schedule-<?php echo esc_attr( $id ); ?>" class="large-text" rows="6" name="<?php echo esc_attr( OPTION ); ?>[schedules][<?php echo esc_attr( $id ); ?>]"><?php echo esc_textarea( $s['schedules'][ $id ] ?? '' ); ?></textarea></td>
					</tr>
				<?php endforeach; ?>
			</table>

			<h2>Under-18 pop-up</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="padlx-under-18">Wording</label></th>
					<td><textarea id="padlx-under-18" class="large-text" rows="3" name="<?php echo esc_attr( OPTION ); ?>[under_18_text]"><?php echo esc_textarea( $s['under_18_text'] ); ?></textarea></td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>

		<h2>Record of acceptances and emails</h2>
		<?php
		$log = get_option( LOG, array() );
		if ( ! is_array( $log ) || ! $log ) :
			?>
			<p>Nothing yet.</p>
		<?php else : ?>
			<table class="widefat striped">
				<thead><tr><th>When</th><th>What</th><th>Email</th><th>Order</th><th>Details</th></tr></thead>
				<tbody>
				<?php foreach ( array_reverse( $log ) as $row ) : ?>
					<tr>
						<td><?php echo esc_html( wp_date( 'j M Y, g:i:s a', (int) $row['time'] ) ); ?></td>
						<td><?php echo esc_html( $row['what'] ); ?></td>
						<td><?php echo esc_html( $row['email'] ); ?></td>
						<td><?php echo esc_html( $row['order'] ); ?></td>
						<td><?php echo esc_html( $row['detail'] ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
}
