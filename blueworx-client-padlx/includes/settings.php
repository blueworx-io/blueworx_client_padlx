<?php
/**
 * Plugin settings: checkout links, notification email, WhatsApp link, membership start date.
 */

namespace BlueWorx\PadlX;

defined( 'ABSPATH' ) || exit;

const OPTION = 'bwpx_settings';

function settings() {
	$saved = get_option( OPTION, array() );
	$all   = wp_parse_args(
		is_array( $saved ) ? $saved : array(),
		array(
			'annual_checkout_url'  => '',
			'monthly_checkout_url' => '',
			'notify_email'         => 'info@padlx.com.au',
			'whatsapp_url'         => '',
			'playtomic_url'        => '',
			'start_date_on'        => false,
			'start_date'           => '',
			'annual_price_id'      => PRICE_ANNUAL,
			'monthly_price_id'     => PRICE_MONTHLY,
		)
	);
	// The PADLX WhatsApp community, unless a different link has been saved on the settings screen.
	if ( '' === $all['whatsapp_url'] ) {
		$all['whatsapp_url'] = 'https://chat.whatsapp.com/HIqvYY8YDAf2bwqwizfzaC';
	}
	return $all;
}

function setting( $key ) {
	$all = settings();
	return isset( $all[ $key ] ) ? $all[ $key ] : '';
}

function checkout_url( $plan ) {
	$saved = setting( 'monthly' === $plan ? 'monthly_checkout_url' : 'annual_checkout_url' );
	if ( $saved ) {
		return $saved;
	}
	return 'monthly' === $plan ? CHECKOUT_MONTHLY : CHECKOUT_ANNUAL;
}

add_action(
	'admin_init',
	function () {
		register_setting(
			'bwpx_settings',
			OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => __NAMESPACE__ . '\sanitize_settings',
			)
		);
	}
);

function sanitize_settings( $input ) {
	$input = is_array( $input ) ? $input : array();
	$date  = sanitize_text_field( $input['start_date'] ?? '' );
	return array(
		'annual_checkout_url'  => esc_url_raw( trim( $input['annual_checkout_url'] ?? '' ) ),
		'monthly_checkout_url' => esc_url_raw( trim( $input['monthly_checkout_url'] ?? '' ) ),
		'notify_email'         => sanitize_email( $input['notify_email'] ?? '' ),
		'whatsapp_url'         => esc_url_raw( trim( $input['whatsapp_url'] ?? '' ) ),
		'playtomic_url'        => esc_url_raw( trim( $input['playtomic_url'] ?? '' ) ),
		'start_date_on'        => ! empty( $input['start_date_on'] ),
		'start_date'           => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : '',
		'annual_price_id'      => sanitize_text_field( trim( $input['annual_price_id'] ?? '' ) ),
		'monthly_price_id'     => sanitize_text_field( trim( $input['monthly_price_id'] ?? '' ) ),
	);
}

add_action(
	'admin_menu',
	function () {
		add_users_page(
			__( 'Founding Member Settings', 'blueworx-client-padlx' ),
			__( 'Founding Members', 'blueworx-client-padlx' ),
			'manage_options',
			'bwpx-settings',
			__NAMESPACE__ . '\render_settings_page'
		);
	}
);

function render_settings_page() {
	$s     = settings();
	$pages = get_option( PAGES_OPTION, array() );
	$field = function ( $key, $label, $type = 'url', $help = '' ) use ( $s ) {
		printf(
			'<tr><th scope="row"><label for="bwpx-%1$s">%2$s</label></th><td><input type="%3$s" id="bwpx-%1$s" name="%4$s[%1$s]" value="%5$s" class="regular-text">%6$s</td></tr>',
			esc_attr( $key ),
			esc_html( $label ),
			esc_attr( $type ),
			esc_attr( OPTION ),
			esc_attr( $s[ $key ] ),
			$help ? '<p class="description">' . esc_html( $help ) . '</p>' : ''
		);
	};
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Founding Member Settings', 'blueworx-client-padlx' ); ?></h1>

		<p><a href="<?php echo esc_url( add_query_arg( 'bwpx_founding', 1, admin_url( 'users.php' ) ) ); ?>"><?php esc_html_e( 'See all founding members', 'blueworx-client-padlx' ); ?></a></p>

		<?php if ( ! empty( $pages['signup'] ) || ! empty( $pages['welcome'] ) ) : ?>
			<p>
				<?php if ( ! empty( $pages['signup'] ) && get_post( $pages['signup'] ) ) : ?>
					<a href="<?php echo esc_url( get_edit_post_link( $pages['signup'] ) ); ?>"><?php esc_html_e( 'Sign-up page', 'blueworx-client-padlx' ); ?></a>
				<?php endif; ?>
				<?php if ( ! empty( $pages['welcome'] ) && get_post( $pages['welcome'] ) ) : ?>
					&nbsp;·&nbsp;<a href="<?php echo esc_url( get_edit_post_link( $pages['welcome'] ) ); ?>"><?php esc_html_e( 'Welcome page', 'blueworx-client-padlx' ); ?></a>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'bwpx_settings' ); ?>
			<table class="form-table" role="presentation">
				<?php
				$field( 'annual_checkout_url', __( 'Annual checkout link', 'blueworx-client-padlx' ), 'url', __( 'Leave blank to use the $2,519 annual Founding price.', 'blueworx-client-padlx' ) );
				$field( 'monthly_checkout_url', __( 'Monthly checkout link', 'blueworx-client-padlx' ), 'url', __( 'Leave blank to use the $229 monthly Founding price.', 'blueworx-client-padlx' ) );
				$field( 'notify_email', __( 'Send new sign-ups to', 'blueworx-client-padlx' ), 'email' );
				$field( 'playtomic_url', __( 'Playtomic app link', 'blueworx-client-padlx' ), 'url', __( 'The "Download the Playtomic app" button on the welcome page is hidden until this is set.', 'blueworx-client-padlx' ) );
				$field( 'whatsapp_url', __( 'WhatsApp group link', 'blueworx-client-padlx' ), 'url', __( 'The "Join on WhatsApp" button on the welcome page is hidden until this is set.', 'blueworx-client-padlx' ) );
				?>
			</table>

			<h2><?php esc_html_e( 'Membership start date', 'blueworx-client-padlx' ); ?></h2>
			<p><?php esc_html_e( 'Founding members who pay before this date have their first renewal moved to one month or one year after it, instead of after the day they paid. Nobody is charged extra. Anyone who pays on or after the start date renews as normal.', 'blueworx-client-padlx' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Move renewal dates', 'blueworx-client-padlx' ); ?></th>
					<td><label><input type="checkbox" name="<?php echo esc_attr( OPTION ); ?>[start_date_on]" value="1" <?php checked( $s['start_date_on'] ); ?>> <?php esc_html_e( 'On for new founding sign-ups', 'blueworx-client-padlx' ); ?></label></td>
				</tr>
				<?php
				$field( 'start_date', __( 'Membership starts on', 'blueworx-client-padlx' ), 'date' );
				$field( 'annual_price_id', __( 'Annual Founding price ID', 'blueworx-client-padlx' ), 'text', __( 'From SureCart. Leave as is unless the Founding prices are replaced.', 'blueworx-client-padlx' ) );
				$field( 'monthly_price_id', __( 'Monthly Founding price ID', 'blueworx-client-padlx' ), 'text' );
				?>
			</table>
			<?php submit_button(); ?>
		</form>

		<?php render_number_list_form(); ?>

		<?php render_waitlist_admin(); ?>

		<?php render_start_date_tools(); ?>
	</div>
	<?php
}
