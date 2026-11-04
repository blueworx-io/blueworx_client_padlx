<?php
/**
 * Founding membership start date: founding members pay now, but their first renewal
 * is one billing period after the membership start date, not after the day they paid.
 *
 * SureCart moves a renewal date by moving the end of the subscription's current period
 * (what its "Change Renewal Date" action does). That never charges, prorates or refunds,
 * and SureCart only allows it to move later, never earlier.
 */

namespace BlueWorx\PadlX;

use SureCart\Models\Period;
use SureCart\Models\Subscription;

defined( 'ABSPATH' ) || exit;

const START_LOG    = 'bwpx_start_date_log';
const START_APPLY  = 'bwpx_apply_start_date';
const START_PAGE   = 'users.php?page=bwpx-settings';
const LOG_MAX_ROWS = 200;

function founding_price_ids() {
	return array_values(
		array_unique(
			array_filter(
				array(
					setting( 'annual_price_id' ) ? setting( 'annual_price_id' ) : PRICE_ANNUAL,
					setting( 'monthly_price_id' ) ? setting( 'monthly_price_id' ) : PRICE_MONTHLY,
				)
			)
		)
	);
}

/**
 * Midnight on the start date, in the site's timezone, or null when no date is set.
 */
function start_moment() {
	$date = setting( 'start_date' );
	if ( ! $date ) {
		return null;
	}
	$start = \DateTimeImmutable::createFromFormat( '!Y-m-d', $date, wp_timezone() );
	return $start ? $start : null;
}

function surecart_ready() {
	return class_exists( Subscription::class ) && class_exists( Period::class );
}

function to_timestamp( $value ) {
	if ( is_numeric( $value ) ) {
		return (int) $value;
	}
	return $value ? (int) strtotime( (string) $value ) : 0;
}

/**
 * Works out whether a founding subscription's renewal should move, and to when.
 * Returns the details for the preview and log; 'new' is set only when it should move.
 */
function renewal_plan( $sub ) {
	$period   = $sub->current_period ?? null;
	$price    = $sub->price ?? null;
	$customer = $sub->customer ?? null;
	$interval = is_object( $price ) ? ( $price->recurring_interval ?? '' ) : '';
	$count    = is_object( $price ) ? max( 1, (int) ( $price->recurring_interval_count ?? 1 ) ) : 1;
	$labels   = array(
		'year'  => __( 'Annual', 'blueworx-client-padlx' ),
		'month' => __( 'Monthly', 'blueworx-client-padlx' ),
	);

	$plan = array(
		'subscription' => $sub->id ?? '',
		'period'       => is_object( $period ) ? ( $period->id ?? '' ) : (string) $period,
		'customer'     => is_object( $customer ) ? trim( ( $customer->name ?? '' ) . ' <' . ( $customer->email ?? '' ) . '>' ) : '',
		'plan'         => $labels[ $interval ] ?? $interval,
		'paid'         => to_timestamp( $sub->created_at ?? 0 ),
		'old'          => to_timestamp( $sub->current_period_end_at ?? 0 ),
		'new'          => 0,
		'reason'       => '',
	);

	$price_id = is_object( $price ) ? ( $price->id ?? '' ) : (string) $price;
	$start    = start_moment();

	if ( ! in_array( $price_id, founding_price_ids(), true ) ) {
		$plan['reason'] = __( 'Not a Founding price', 'blueworx-client-padlx' );
	} elseif ( ! $start ) {
		$plan['reason'] = __( 'No start date set', 'blueworx-client-padlx' );
	} elseif ( 'active' !== ( $sub->status ?? '' ) ) {
		$plan['reason'] = __( 'Not active', 'blueworx-client-padlx' );
	} elseif ( ! $plan['period'] || ! $plan['old'] || ! in_array( $interval, array( 'day', 'week', 'month', 'year' ), true ) ) {
		$plan['reason'] = __( 'No renewal date to move', 'blueworx-client-padlx' );
	} elseif ( $plan['paid'] >= $start->getTimestamp() ) {
		$plan['reason'] = __( 'Paid on or after the start date', 'blueworx-client-padlx' );
	} elseif ( is_object( $period ) && ! empty( $period->renewal ) ) {
		$plan['reason'] = __( 'Has already renewed', 'blueworx-client-padlx' );
	}

	if ( $plan['reason'] ) {
		return $plan;
	}

	// Keep the time of day SureCart already renews at, as its own "Change Renewal Date" does.
	$old = ( new \DateTimeImmutable( '@' . $plan['old'] ) )->setTimezone( wp_timezone() );
	$new = $start
		->setTime( (int) $old->format( 'H' ), (int) $old->format( 'i' ), (int) $old->format( 's' ) )
		->modify( "+{$count} {$interval}" )
		->getTimestamp();

	if ( $new <= $plan['old'] ) {
		$plan['reason'] = __( 'Already renews on or after the new date', 'blueworx-client-padlx' );
	} elseif ( $new <= time() ) {
		$plan['reason'] = __( 'New date has already passed', 'blueworx-client-padlx' );
	} else {
		$plan['new'] = $new;
	}
	return $plan;
}

/**
 * Moves the renewal date and logs it. Returns true or a WP_Error.
 */
function move_renewal( array $plan, $source ) {
	$updated = Period::update(
		array(
			'id'     => $plan['period'],
			'end_at' => $plan['new'],
		)
	);
	$error = is_wp_error( $updated ) ? $updated : ( false === $updated ? new \WP_Error( 'bwpx_not_updated', __( 'SureCart did not update the renewal date.', 'blueworx-client-padlx' ) ) : null );

	add_log_row( $plan, $source, $error ? $error->get_error_message() : '' );
	return $error ? $error : true;
}

function add_log_row( array $plan, $source, $error = '' ) {
	$log = get_option( START_LOG, array() );
	$log = is_array( $log ) ? $log : array();
	array_unshift(
		$log,
		array(
			'time'         => time(),
			'source'       => $source,
			'subscription' => $plan['subscription'],
			'customer'     => $plan['customer'],
			'plan'         => $plan['plan'],
			'old'          => $plan['old'],
			'new'          => $plan['new'],
			'error'        => $error,
		)
	);
	update_option( START_LOG, array_slice( $log, 0, LOG_MAX_ROWS ), false );
}

/**
 * Active subscriptions on the Founding prices, in both live and test mode.
 *
 * @return Subscription[]|\WP_Error
 */
function founding_subscriptions( array $where = array() ) {
	$found = array();
	foreach ( array( true, false ) as $live_mode ) {
		$page = 1;
		do {
			$batch = Subscription::where(
				array_merge(
					array(
						'price_ids' => founding_price_ids(),
						'status'    => array( 'active' ),
						'live_mode' => $live_mode,
					),
					$where
				)
			)->with( array( 'customer', 'price', 'current_period' ) )->paginate(
				array(
					'per_page' => 100,
					'page'     => $page,
				)
			);
			if ( is_wp_error( $batch ) ) {
				return $batch;
			}
			foreach ( $batch->data ?? array() as $sub ) {
				$found[ $sub->id ] = $sub;
			}
			++$page;
		} while ( ! empty( $batch->data ) && $batch->hasNextPage() && $page <= 20 );
	}
	return array_values( $found );
}

// New founding sign-ups. SureCart fires this both when checkout finishes and again from its webhook.
add_action(
	'surecart/purchase_created',
	function ( $purchase ) {
		if ( ! setting( 'start_date_on' ) || ! start_moment() || ! surecart_ready() || empty( $purchase->id ) ) {
			return;
		}
		try {
			$where = array( 'purchase_ids' => array( $purchase->id ) );
			if ( isset( $purchase->live_mode ) ) {
				$where['live_mode'] = (bool) $purchase->live_mode;
			}
			$subs = founding_subscriptions( $where );
			if ( is_wp_error( $subs ) ) {
				add_log_row(
					array( 'subscription' => '', 'customer' => '', 'plan' => '', 'old' => 0, 'new' => 0 ),
					'auto',
					sprintf( 'Could not load the subscription for purchase %s: %s', $purchase->id, $subs->get_error_message() )
				);
				return;
			}
			foreach ( $subs as $sub ) {
				$plan = renewal_plan( $sub );
				$lock = 'bwpx_start_' . md5( $sub->id );
				if ( ! $plan['new'] || get_transient( $lock ) ) {
					continue;
				}
				set_transient( $lock, 1, 5 * MINUTE_IN_SECONDS );
				move_renewal( $plan, 'auto' );
			}
		} catch ( \Throwable $e ) {
			add_log_row( array( 'subscription' => '', 'customer' => '', 'plan' => '', 'old' => 0, 'new' => 0 ), 'auto', $e->getMessage() );
		}
	},
	20
);

add_action(
	'admin_post_' . START_APPLY,
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'blueworx-client-padlx' ) );
		}
		check_admin_referer( START_APPLY );

		$moved  = 0;
		$failed = 0;
		$subs   = surecart_ready() && start_moment() ? founding_subscriptions() : array();
		if ( is_wp_error( $subs ) ) {
			wp_safe_redirect( add_query_arg( 'bwpx_apply_error', rawurlencode( $subs->get_error_message() ), admin_url( START_PAGE ) ) );
			exit;
		}
		foreach ( $subs as $sub ) {
			$plan = renewal_plan( $sub );
			if ( ! $plan['new'] ) {
				continue;
			}
			if ( true === move_renewal( $plan, 'existing' ) ) {
				++$moved;
			} else {
				++$failed;
			}
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'bwpx_moved'  => $moved,
					'bwpx_failed' => $failed,
				),
				admin_url( START_PAGE )
			) . '#bwpx-start-tools'
		);
		exit;
	}
);

function format_day( $timestamp ) {
	return $timestamp ? wp_date( get_option( 'date_format' ), $timestamp ) : '—';
}

function subscription_link( $id, $text ) {
	if ( ! $id ) {
		return esc_html( $text );
	}
	return sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'admin.php?page=sc-subscriptions&action=edit&id=' . rawurlencode( $id ) ) ),
		esc_html( $text )
	);
}

/**
 * "Existing founding members" preview and apply button, and the log, under the settings form.
 */
function render_start_date_tools() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only notices and preview.
	$preview = ! empty( $_GET['bwpx_preview'] );
	$moved   = isset( $_GET['bwpx_moved'] ) ? absint( $_GET['bwpx_moved'] ) : null;
	$failed  = isset( $_GET['bwpx_failed'] ) ? absint( $_GET['bwpx_failed'] ) : 0;
	$problem = isset( $_GET['bwpx_apply_error'] ) ? sanitize_text_field( wp_unslash( $_GET['bwpx_apply_error'] ) ) : '';
	// phpcs:enable
	?>
	<h2 id="bwpx-start-tools"><?php esc_html_e( 'Existing founding members', 'blueworx-client-padlx' ); ?></h2>

	<?php if ( null !== $moved ) : ?>
		<div class="notice <?php echo $failed ? 'notice-warning' : 'notice-success'; ?> inline"><p>
			<?php
			/* translators: 1: renewal dates moved, 2: renewal dates that failed */
			echo esc_html( sprintf( __( 'Renewal dates moved: %1$d. Failed: %2$d (see the log below).', 'blueworx-client-padlx' ), $moved, $failed ) );
			?>
		</p></div>
	<?php endif; ?>
	<?php if ( $problem ) : ?>
		<div class="notice notice-error inline"><p><?php echo esc_html( $problem ); ?></p></div>
	<?php endif; ?>

	<?php if ( ! surecart_ready() ) : ?>
		<p><?php esc_html_e( 'SureCart is not active.', 'blueworx-client-padlx' ); ?></p>
	<?php elseif ( ! start_moment() ) : ?>
		<p><?php esc_html_e( 'Save a membership start date above first.', 'blueworx-client-padlx' ); ?></p>
	<?php elseif ( ! $preview ) : ?>
		<p><?php esc_html_e( 'For anyone who paid before this was switched on. Check the list first: nothing changes until you press Apply.', 'blueworx-client-padlx' ); ?></p>
		<p><a class="button" href="<?php echo esc_url( add_query_arg( 'bwpx_preview', 1, admin_url( START_PAGE ) ) . '#bwpx-start-tools' ); ?>"><?php esc_html_e( 'Preview existing founding members', 'blueworx-client-padlx' ); ?></a></p>
	<?php else : ?>
		<?php
		$subs  = founding_subscriptions();
		$plans = is_wp_error( $subs ) ? array() : array_map( __NAMESPACE__ . '\renewal_plan', $subs );
		$ready = array_filter(
			$plans,
			function ( $plan ) {
				return (bool) $plan['new'];
			}
		);
		?>
		<?php if ( is_wp_error( $subs ) ) : ?>
			<div class="notice notice-error inline"><p><?php echo esc_html( $subs->get_error_message() ); ?></p></div>
		<?php elseif ( ! $plans ) : ?>
			<p><?php esc_html_e( 'No active founding subscriptions found.', 'blueworx-client-padlx' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead><tr>
					<th><?php esc_html_e( 'Customer', 'blueworx-client-padlx' ); ?></th>
					<th><?php esc_html_e( 'Plan', 'blueworx-client-padlx' ); ?></th>
					<th><?php esc_html_e( 'Paid', 'blueworx-client-padlx' ); ?></th>
					<th><?php esc_html_e( 'Renews now', 'blueworx-client-padlx' ); ?></th>
					<th><?php esc_html_e( 'Will renew', 'blueworx-client-padlx' ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $plans as $plan ) : ?>
					<tr>
						<td><?php echo subscription_link( $plan['subscription'], $plan['customer'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?></td>
						<td><?php echo esc_html( $plan['plan'] ); ?></td>
						<td><?php echo esc_html( format_day( $plan['paid'] ) ); ?></td>
						<td><?php echo esc_html( format_day( $plan['old'] ) ); ?></td>
						<td><?php echo $plan['new'] ? '<strong>' . esc_html( format_day( $plan['new'] ) ) . '</strong>' : esc_html( __( 'No change', 'blueworx-client-padlx' ) . ' (' . $plan['reason'] . ')' ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( $ready ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="<?php echo esc_attr( START_APPLY ); ?>">
					<?php wp_nonce_field( START_APPLY ); ?>
					<?php
					/* translators: %d: number of members */
					submit_button( sprintf( _n( 'Apply to %d member', 'Apply to %d members', count( $ready ), 'blueworx-client-padlx' ), count( $ready ) ), 'primary', 'submit', false );
					?>
				</form>
			<?php else : ?>
				<p><?php esc_html_e( 'Nothing to change.', 'blueworx-client-padlx' ); ?></p>
			<?php endif; ?>
		<?php endif; ?>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Renewal date log', 'blueworx-client-padlx' ); ?></h2>
	<?php
	$log = get_option( START_LOG, array() );
	if ( ! is_array( $log ) || ! $log ) :
		?>
		<p><?php esc_html_e( 'No renewal dates moved yet.', 'blueworx-client-padlx' ); ?></p>
	<?php else : ?>
		<table class="widefat striped">
			<thead><tr>
				<th><?php esc_html_e( 'When', 'blueworx-client-padlx' ); ?></th>
				<th><?php esc_html_e( 'Customer', 'blueworx-client-padlx' ); ?></th>
				<th><?php esc_html_e( 'Plan', 'blueworx-client-padlx' ); ?></th>
				<th><?php esc_html_e( 'Old renewal', 'blueworx-client-padlx' ); ?></th>
				<th><?php esc_html_e( 'New renewal', 'blueworx-client-padlx' ); ?></th>
				<th><?php esc_html_e( 'Result', 'blueworx-client-padlx' ); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ( array_slice( $log, 0, 50 ) as $row ) : ?>
				<tr>
					<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $row['time'] ) ); ?></td>
					<td><?php echo subscription_link( $row['subscription'], $row['customer'] ? $row['customer'] : '—' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?></td>
					<td><?php echo esc_html( $row['plan'] ); ?></td>
					<td><?php echo esc_html( format_day( $row['old'] ) ); ?></td>
					<td><?php echo esc_html( format_day( $row['new'] ) ); ?></td>
					<td>
						<?php
						if ( $row['error'] ) {
							echo esc_html( __( 'Failed: ', 'blueworx-client-padlx' ) . $row['error'] );
						} else {
							echo esc_html( 'existing' === $row['source'] ? __( 'Moved (existing member)', 'blueworx-client-padlx' ) : __( 'Moved (new sign-up)', 'blueworx-client-padlx' ) );
						}
						?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
	<?php
}
