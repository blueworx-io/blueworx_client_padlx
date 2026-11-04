<?php
/**
 * Founding member welcome page, shown after payment (Figma: PadlX / Founding member · Confirmation).
 */

namespace BlueWorx\PadlX;

defined( 'ABSPATH' ) || exit;

$member = welcome_member();
$email  = $member ? $member->user_email : '';
$number = $member ? $member->ID : 0;

$phases = array(
	array( 'PHASE 1 · NOVEMBER', 'Burleigh Heads', "Four outdoor courts. You're on first." ),
	array( 'PHASE 2 · DECEMBER', 'Arundel, part one', 'The first four indoor courts.' ),
	array( 'PHASE 3 · JANUARY 2027', 'Arundel, complete', "Courts 5 to 8 open, along with the Wellness Centre. (Until then, we've got you covered with a temporary guest pass to Breathe, just around the corner from Arundel.)" ),
);

part( 'document-start', array( 'secure' => false ) );
?>

<div class="bwpx-photo">
	<img src="<?php echo esc_url( asset( 'welcome-photo.jpg' ) ); ?>" alt="">
</div>

<section class="bwpx-hero">
	<div class="bwpx-hero__text">
		<p class="bwpx-tag">PAYMENT RECEIVED</p>
		<h1 class="bwpx-display">Welcome to the 150.</h1>
		<p class="bwpx-lede">You're officially a PADLX founding member. Your rate is locked in for life, and we can't wait to see you on court.</p>
	</div>
	<div class="bwpx-member-card">
		<img src="<?php echo esc_url( asset( 'logo.svg' ) ); ?>" width="130" height="26" alt="PADLX">
		<div class="bwpx-member-card__number">
			<p class="bwpx-eyebrow">FOUNDING MEMBER</p>
			<?php if ( $number ) : ?>
				<p class="bwpx-h2"><?php echo esc_html( format_number( $number ) ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</section>

<section class="bwpx-next">
	<div class="bwpx-next__card">
		<span class="bwpx-num bwpx-eyebrow" aria-hidden="true">01</span>
		<h2 class="bwpx-h3">Check your inbox</h2>
		<p class="bwpx-body bwpx-muted">
			<?php if ( $email ) : ?>
				Your tax invoice and membership details are on their way to <?php echo esc_html( $email ); ?>.
			<?php else : ?>
				Your tax invoice and membership details are on their way to your inbox.
			<?php endif; ?>
		</p>
	</div>
	<div class="bwpx-next__card">
		<span class="bwpx-num bwpx-eyebrow" aria-hidden="true">02</span>
		<h2 class="bwpx-h3">Get set up on Playtomic</h2>
		<p class="bwpx-body bwpx-muted">Sign in with the same email so your member rates, booking window and any wallet credit land in the right place.</p>
			<?php welcome_button( setting( 'playtomic_url' ), 'Download the Playtomic app', 'Playtomic app link' ); ?>
	</div>
	<div class="bwpx-next__card">
		<span class="bwpx-num bwpx-eyebrow" aria-hidden="true">03</span>
		<h2 class="bwpx-h3">Join the members' group</h2>
		<p class="bwpx-body bwpx-muted">Founding member news, events and games, straight to your phone.</p>
		<?php welcome_button( setting( 'whatsapp_url' ), 'Join on WhatsApp', 'WhatsApp group link' ); ?>
	</div>
</section>

<section class="bwpx-band bwpx-band--phases">
	<img class="bwpx-ball" src="<?php echo esc_url( asset( 'padel-ball-64.svg' ) ); ?>" width="64" height="64" alt="">
	<p class="bwpx-eyebrow">STAY TUNED</p>
	<h2 class="bwpx-h2 bwpx-band__title">We'll get you on court as soon as we can.</h2>
	<p class="bwpx-body bwpx-band__intro">We'll be in touch with opening details and your invitation to the opening party. We're opening in three phases, and founding members get first access every time.</p>
	<div class="bwpx-phases__list">
		<?php foreach ( $phases as list( $eyebrow, $title, $body ) ) : ?>
			<div class="bwpx-phase">
				<p class="bwpx-eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
				<p class="bwpx-body"><?php echo esc_html( $title ); ?></p>
				<p class="bwpx-small"><?php echo esc_html( $body ); ?></p>
			</div>
		<?php endforeach; ?>
	</div>
	<p class="bwpx-small">Your first payment has been taken, and your 12-month membership starts on 15 December 2026. If opening dates move, your start date moves with them.</p>
</section>

<section class="bwpx-band bwpx-band--perk">
	<p class="bwpx-display bwpx-band__value">20% OFF</p>
	<div class="bwpx-band__perk-text">
		<h2 class="bwpx-h3">Your first perk starts now: Mohr</h2>
		<p class="bwpx-body">Mohr, located on Hibiscus Avenue in Burleigh, is curating the menus at PADLX: chef-prepared, fresh, delicious, healthy food, so the quality carries from the courts straight through to our cafe and bar. As a founding member you get an exclusive 20% off food and drinks at Mohr, Hibiscus Avenue, Burleigh, every time you visit. Excludes fresh and frozen take-home meals.</p>
	</div>
</section>

<section class="bwpx-note">
	<p class="bwpx-lede">Your welcome box is being packed. We'll be in touch about collection before opening.</p>
</section>

<?php
part( 'document-end' );
