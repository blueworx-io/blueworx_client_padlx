<?php
/**
 * Founding waitlist page: shown in place of the sign-up form once every founding spot is taken.
 */

namespace BlueWorx\PadlX;

defined( 'ABSPATH' ) || exit;

require_once DIR . 'templates/parts/form-fields.php';

$state  = form_state();
$joined = waitlist_joined();

$included = array(
	'Free off-peak play at all centres',
	'$15 per hour peak court hire',
	'21-day advance booking window',
	'Unlimited Wellness Centre access (indoor centres)',
	'20% off food, drinks & pro shop',
	'Welcome gift box & numbered founding member recognition',
	"Founding member-only events and members' WhatsApp group",
);

part( 'document-start' );
?>

<div class="bwpx-photo">
	<img src="<?php echo esc_url( asset( 'signup-photo.jpg' ) ); ?>" alt="" style="object-position: 50% 40%">
</div>

<section class="bwpx-intro">
	<p class="bwpx-tag">FOUNDING MEMBERSHIP · ALL 150 SPOTS TAKEN</p>
	<h1 class="bwpx-h1 bwpx-intro__title">Join the founding waitlist.</h1>
	<p class="bwpx-intro__text">All 150 founding memberships have been taken. Leave your details and we'll be in touch as soon as a spot opens up.</p>
</section>

<div class="bwpx-main">
	<?php if ( $joined ) : ?>
		<div class="bwpx-steps" id="bwpx-waitlist">
			<section class="bwpx-step">
				<h2 class="bwpx-h3">You're on the waitlist.</h2>
				<p class="bwpx-body">Thank you. We'll be in touch as soon as a founding spot opens up.</p>
				<p class="bwpx-small">Questions? Email <a href="mailto:info@padlx.com.au">info@padlx.com.au</a></p>
			</section>
		</div>
	<?php else : ?>
		<form class="bwpx-steps" id="bwpx-waitlist" method="post" action="<?php echo esc_url( waitlist_url() ); ?>">
			<input type="hidden" name="bwpx_action" value="<?php echo esc_attr( WAITLIST_ACTION ); ?>">
			<?php wp_nonce_field( WAITLIST_ACTION, '_bwpx_nonce' ); ?>
			<div class="bwpx-hp" aria-hidden="true">
				<label>Leave this empty <input type="text" name="bwpx_website" tabindex="-1" autocomplete="off"></label>
			</div>

			<?php if ( $state['message'] ) : ?>
				<div class="bwpx-notice bwpx-body" role="alert"><?php echo esc_html( $state['message'] ); ?></div>
			<?php endif; ?>

			<section class="bwpx-step">
				<h2 class="bwpx-h3">Your details</h2>
				<div class="bwpx-row">
					<?php Form\input( $state, 'first_name', array( 'autocomplete' => 'given-name' ) ); ?>
					<?php Form\input( $state, 'last_name', array( 'autocomplete' => 'family-name' ) ); ?>
				</div>
				<div class="bwpx-row">
					<?php Form\input( $state, 'email', array( 'type' => 'email', 'autocomplete' => 'email' ) ); ?>
					<?php Form\input( $state, 'mobile', array( 'type' => 'tel', 'autocomplete' => 'tel' ) ); ?>
				</div>
				<button type="submit" class="bwpx-btn bwpx-btn--full">Join the waitlist</button>
				<p class="bwpx-small bwpx-muted">No payment is taken. We'll only use your details to contact you about a founding membership.</p>
			</section>
		</form>
	<?php endif; ?>

	<aside class="bwpx-summary" aria-label="Founding membership">
		<img class="bwpx-ball" src="<?php echo esc_url( asset( 'padel-ball-32.svg' ) ); ?>" width="32" height="32" alt="">
		<p class="bwpx-eyebrow">FOUNDING MEMBERSHIP</p>
		<p class="bwpx-small">Burleigh Heads &amp; Arundel (+ future centres)</p>
		<p class="bwpx-eyebrow">WHAT'S INCLUDED</p>
		<ul class="bwpx-included">
			<?php foreach ( $included as $item ) : ?>
				<li class="bwpx-small"><img src="<?php echo esc_url( asset( 'tick.svg' ) ); ?>" width="14" height="14" alt=""><span><?php echo esc_html( $item ); ?></span></li>
			<?php endforeach; ?>
		</ul>
		<p class="bwpx-small">Questions? Email <a href="mailto:info@padlx.com.au">info@padlx.com.au</a></p>
	</aside>
</div>

<?php
part( 'document-end' );
