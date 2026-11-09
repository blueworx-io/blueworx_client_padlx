<?php
/**
 * Founding member sign-up page (Figma: PadlX / Founding member · Conversion).
 */

namespace BlueWorx\PadlX;


defined( 'ABSPATH' ) || exit;

require_once DIR . 'templates/parts/form-fields.php';

$state = form_state();
$plan  = Form\value( $state, 'plan' ) === 'monthly' ? 'monthly' : 'annual';

$included = array(
	'Free off-peak play at all centres',
	'$15 per hour peak court hire',
	'21-day advance booking window',
	'Unlimited Wellness Centre access (indoor centres)',
	'20% off food, drinks & pro shop',
	'20% off food and drinks at Mohr, Hibiscus Avenue, Burleigh (excludes fresh and frozen take-home meals)',
	'Welcome gift box & numbered founding member recognition',
	"Founding member-only events and members' WhatsApp group",
);

$phases = array(
	array( 'PHASE 1 · NOVEMBER', 'Burleigh Heads', 'Four outdoor courts, cafe and bar. Founding members on court first.' ),
	array( 'PHASE 2 · DECEMBER', 'Arundel, part one', 'The first four indoor courts open.' ),
	array( 'PHASE 3 · JANUARY 2027', 'Arundel, complete', "Courts 5 to 8 open, along with the Wellness Centre. (Until then, we've got you covered with a temporary guest pass to Breathe, just around the corner from Arundel.)" ),
);

part( 'document-start', array( 'secure' => true ) );
?>

<div class="bwpx-photo">
	<img src="<?php echo esc_url( asset( 'signup-photo.jpg' ) ); ?>" alt="" style="object-position: 50% 40%">
</div>

<section class="bwpx-intro">
	<p class="bwpx-tag">STRICTLY LIMITED · 150 FOUNDING MEMBERS</p>
	<h1 class="bwpx-h1 bwpx-intro__title">You reserved it. Now make it yours.</h1>
	<p class="bwpx-intro__text">Thank you for being one of our founding members. We're thrilled to welcome you officially to PADLX. So we can get to know you even better, answer a few quick questions, then choose how you'd like to pay. Your founding rate is fixed for life.</p>
	<p class="bwpx-small bwpx-muted">Takes about 4 minutes.</p>
	<div class="bwpx-callout">
		<p class="bwpx-eyebrow">FOUNDING OFFER</p>
		<p class="bwpx-body">Confirm your founding membership within 7 days of receiving your invitation and you'll receive a bonus $300 in your Playtomic wallet to spend on peak-hour play. That's the equivalent of 20 hours of peak-time play included in your founding membership.</p>
	</div>
</section>

<div class="bwpx-main">
	<form class="bwpx-steps" id="bwpx-form" method="post" action="<?php echo esc_url( get_permalink( get_queried_object_id() ) ); ?>">
		<input type="hidden" name="bwpx_action" value="<?php echo esc_attr( ACTION ); ?>">
		<?php wp_nonce_field( ACTION, '_bwpx_nonce' ); ?>
		<div class="bwpx-hp" aria-hidden="true">
			<label>Leave this empty <input type="text" name="bwpx_website" tabindex="-1" autocomplete="off"></label>
		</div>

		<?php if ( $state['message'] ) : ?>
			<div class="bwpx-notice bwpx-body" role="alert"><?php echo esc_html( $state['message'] ); ?></div>
		<?php endif; ?>

		<section class="bwpx-step">
			<?php Form\step_header( '01', 'About you' ); ?>
			<div class="bwpx-row">
				<?php Form\input( $state, 'first_name', array( 'autocomplete' => 'given-name' ) ); ?>
				<?php Form\input( $state, 'last_name', array( 'autocomplete' => 'family-name' ) ); ?>
			</div>
			<?php
			Form\input(
				$state,
				'email',
				array(
					'type'         => 'email',
					'autocomplete' => 'email',
					'helper'       => "Already on Playtomic? Use the same email so your member rates match up. Not yet? All good, we'll help you sign up later.",
				)
			);
			?>
			<div class="bwpx-row">
				<?php Form\input( $state, 'mobile', array( 'type' => 'tel', 'autocomplete' => 'tel' ) ); ?>
				<?php
				Form\input(
					$state,
					'dob',
					array(
						'placeholder'  => 'DD / MM / YYYY',
						'inputmode'    => 'numeric',
						'autocomplete' => 'bday',
						'helper'       => 'Just type the numbers, e.g. 14031990. So we can celebrate with you.',
					)
				);
				?>
			</div>
			<div class="bwpx-row bwpx-row--half">
				<?php Form\select( $state, 'gender', array( 'label' => 'Gender (optional)', 'helper' => "Helps us plan women's, men's and mixed comps." ) ); ?>
			</div>
			<div class="bwpx-group">
				<p class="bwpx-body">Your address</p>
				<?php Form\input( $state, 'street', array( 'autocomplete' => 'street-address' ) ); ?>
				<div class="bwpx-row">
					<?php Form\input( $state, 'suburb', array( 'autocomplete' => 'address-level2' ) ); ?>
					<?php Form\select( $state, 'state', array( 'autocomplete' => 'address-level1' ) ); ?>
					<?php Form\input( $state, 'postcode', array( 'autocomplete' => 'postal-code', 'inputmode' => 'numeric', 'maxlength' => 4 ) ); ?>
				</div>
			</div>
			<div class="bwpx-group">
				<p class="bwpx-body">Emergency contact</p>
				<p class="bwpx-small bwpx-muted">Someone we can call if you need a hand on court.</p>
				<div class="bwpx-row">
					<?php Form\input( $state, 'ec_name', array( 'label' => 'Name' ) ); ?>
					<?php Form\input( $state, 'ec_relationship', array( 'label' => 'Relationship to you' ) ); ?>
					<?php Form\input( $state, 'ec_mobile', array( 'label' => 'Mobile', 'type' => 'tel' ) ); ?>
				</div>
			</div>
			<?php Form\textarea( $state, 'notes', array( 'label' => 'Anything we should know? (optional)', 'helper' => 'Injuries or medical conditions our coaches and staff should be aware of. Kept confidential.' ) ); ?>
			<?php Form\choices( $state, 'venue' ); ?>
		</section>

		<section class="bwpx-step">
			<?php Form\step_header( '02', 'Your game', 'The fun bit. No wrong answers, and first-timers are very welcome.' ); ?>
			<?php Form\choices( $state, 'level' ); ?>
			<div class="bwpx-row bwpx-row--half">
				<?php Form\input( $state, 'rating', array( 'label' => 'Know your Playtomic rating? (optional)', 'placeholder' => 'e.g. 2.5', 'inputmode' => 'decimal' ) ); ?>
			</div>
			<?php Form\choices( $state, 'shot', 'card' ); ?>
			<?php Form\choices( $state, 'tshirt', 'chip', 'For your founding member welcome box.' ); ?>
			<?php Form\choices( $state, 'drink', 'chip', 'So the bar team knows your order.' ); ?>
		</section>

		<section class="bwpx-step">
			<?php Form\step_header( '03', 'How would you like to pay?', 'Same founding membership either way. All prices include GST.' ); ?>
			<fieldset class="bwpx-plans">
				<legend class="screen-reader-text">Payment plan</legend>
				<label class="bwpx-plan">
					<input class="bwpx-choice__input" type="radio" name="bwpx[plan]" value="annual" id="bwpx-plan-annual" <?php checked( $plan, 'annual' ); ?> required>
					<span class="bwpx-plan__box">
						<span class="bwpx-plan__top">
							<span class="bwpx-plan__radio" aria-hidden="true">
								<img class="bwpx-plan__radio-off" src="<?php echo esc_url( asset( 'radio.svg' ) ); ?>" width="22" height="22" alt="">
								<img class="bwpx-plan__radio-on" src="<?php echo esc_url( asset( 'radio-selected.svg' ) ); ?>" width="22" height="22" alt="">
							</span>
							<span class="bwpx-plan__name bwpx-eyebrow">Pay annually</span>
							<span class="bwpx-badge">1 month free · save $229</span>
						</span>
						<span class="bwpx-plan__price">
							<span class="bwpx-h2">$2,519</span>
							<span class="bwpx-small bwpx-muted">/year incl. GST</span>
						</span>
						<span class="bwpx-small bwpx-muted">One payment covers your first 12 months. Works out at just $48.44 a week.</span>
					</span>
				</label>
				<label class="bwpx-plan">
					<input class="bwpx-choice__input" type="radio" name="bwpx[plan]" value="monthly" id="bwpx-plan-monthly" <?php checked( $plan, 'monthly' ); ?>>
					<span class="bwpx-plan__box">
						<span class="bwpx-plan__top">
							<span class="bwpx-plan__radio" aria-hidden="true">
								<img class="bwpx-plan__radio-off" src="<?php echo esc_url( asset( 'radio.svg' ) ); ?>" width="22" height="22" alt="">
								<img class="bwpx-plan__radio-on" src="<?php echo esc_url( asset( 'radio-selected.svg' ) ); ?>" width="22" height="22" alt="">
							</span>
							<span class="bwpx-plan__name bwpx-eyebrow">Pay monthly</span>
						</span>
						<span class="bwpx-plan__price">
							<span class="bwpx-h2">$229</span>
							<span class="bwpx-small bwpx-muted">/month incl. GST</span>
						</span>
						<span class="bwpx-small bwpx-muted">12 monthly payments over your 12-month minimum term. Works out at $52.85 a week.</span>
					</span>
				</label>
			</fieldset>
			<ul class="bwpx-terms">
				<li><span class="bwpx-check" aria-hidden="true">✓</span><span class="bwpx-label">Founding rate fixed for life</span></li>
				<li><span class="bwpx-check" aria-hidden="true">✓</span><span class="bwpx-label">12-month minimum term</span></li>
				<li><span class="bwpx-check" aria-hidden="true">✓</span><span class="bwpx-label">Your 12 months start on 1 December 2026</span></li>
			</ul>
		</section>

		<section class="bwpx-phases">
			<img class="bwpx-ball" src="<?php echo esc_url( asset( 'padel-ball-40.svg' ) ); ?>" width="40" height="40" alt="">
			<p class="bwpx-eyebrow">GOOD TO KNOW</p>
			<div class="bwpx-phases__heading">
				<h2 class="bwpx-h3">Three phases</h2>
				<p class="bwpx-body">We're opening in stages, and founding members get first access at every step.</p>
			</div>
			<div class="bwpx-phases__list">
				<?php foreach ( $phases as list( $eyebrow, $title, $body ) ) : ?>
					<div class="bwpx-phase">
						<p class="bwpx-eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
						<p class="bwpx-body"><?php echo esc_html( $title ); ?></p>
						<p class="bwpx-small"><?php echo esc_html( $body ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
			<p class="bwpx-small">Your first payment is taken when you sign up, but your membership only starts on 1 December 2026. If opening dates move, your start date moves with them.</p>
		</section>

		<section class="bwpx-perk">
			<p class="bwpx-eyebrow">BE THE FIRST TO KNOW</p>
			<h2 class="bwpx-h3">Introducing our partnership with Mohr</h2>
			<p class="bwpx-body">We've teamed up with Mohr, located on Hibiscus Avenue in Burleigh, to curate the menus and food at PADLX. Chef-prepared, fresh, delicious, healthy food, because the quality shouldn't stop at the courts. It carries straight through to our cafe and bar.</p>
			<div class="bwpx-perk__offer">
				<p class="bwpx-h2">20% OFF</p>
				<p class="bwpx-small">Founding member perk: an exclusive 20% off food and drinks at Mohr, Hibiscus Avenue, Burleigh, every time you visit. Excludes fresh and frozen take-home meals.</p>
			</div>
		</section>

		<section class="bwpx-step">
			<?php Form\step_header( '04', 'Review and pay', 'Check your plan in the summary, then continue to our secure checkout to pay by card or bank account.' ); ?>
			<p class="bwpx-small bwpx-muted">Payment is processed securely by Stripe. Your details never touch the PADLX website.</p>
			<p class="bwpx-small">No card fees. No direct debit fees. The price you see is the price you pay.</p>
			<button type="submit" class="bwpx-btn bwpx-btn--full">Continue to secure checkout</button>
			<p class="bwpx-small bwpx-muted">A tax invoice will be emailed to you for your annual payment in advance or, if you pay monthly, for your 12 monthly payments. PADLX is a trading name of Padel 365 Pty Ltd, ABN 13 689 919 079.</p>
		</section>
	</form>

	<aside class="bwpx-summary" aria-label="Your membership">
		<img class="bwpx-ball" src="<?php echo esc_url( asset( 'padel-ball-32.svg' ) ); ?>" width="32" height="32" alt="">
		<p class="bwpx-eyebrow">YOUR MEMBERSHIP</p>
		<p class="bwpx-h3">Founding Membership</p>
		<p class="bwpx-small">Burleigh Heads &amp; Arundel (+ future centres)</p>
		<p class="bwpx-small">Two ways to pay. Same founding membership. All prices include GST.</p>

		<div class="bwpx-sumplan bwpx-sumplan--annual">
			<p class="bwpx-body">Payment plan: annual in advance</p>
			<dl class="bwpx-sumplan__rows bwpx-small">
				<div><dt>Minimum commitment</dt><dd>12 months</dd></div>
				<div><dt>Membership starts</dt><dd>1 December 2026</dd></div>
				<div><dt>Works out at</dt><dd>$48.44 a week</dd></div>
				<div><dt>First payment, on sign-up</dt><dd>$2,519.00</dd></div>
			</dl>
			<p class="bwpx-small">One payment covers your 12 months, including 1 month free.</p>
		</div>

		<div class="bwpx-sumplan bwpx-sumplan--monthly">
			<p class="bwpx-body">Payment plan: monthly in advance</p>
			<dl class="bwpx-sumplan__rows bwpx-small">
				<div><dt>Minimum commitment</dt><dd>12 months</dd></div>
				<div><dt>Membership starts</dt><dd>1 December 2026</dd></div>
				<div><dt>Works out at</dt><dd>$52.85 a week</dd></div>
				<div><dt>First payment, on sign-up</dt><dd>$229.00</dd></div>
			</dl>
			<p class="bwpx-small">Then $229.00 a month for the rest of your 12 months.</p>
		</div>

		<div class="bwpx-summary__fees bwpx-small"><span>Card or bank fees</span><span>$0.00</span></div>

		<p class="bwpx-summary__bonus bwpx-small">Founding bonus: confirm your founding membership within 7 days of receiving your invitation and you'll receive a bonus $300 in your Playtomic wallet to spend on peak-hour play. That's the equivalent of 20 hours of peak-time play included in your founding membership.</p>

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
