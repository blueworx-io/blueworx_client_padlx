=== BlueWorx Client | PadlX ===
Requires at least: 6.3
Requires PHP: 7.4
Stable tag: 1.4.1

PadlX founding member sign-up and welcome pages, and founding renewal dates.

== Description ==

On activation this creates two draft pages:

* Founding Membership (/founding-membership) – the sign-up form. Answers are saved to the member's user profile (an account is created if the email doesn't match one), emailed to the team, then the member goes to checkout with their chosen plan.
* Welcome to the 150 (/welcome) – shown after payment, with the member's founding number (their user ID).

Founding members can be filtered on the Users screen. Checkout links, the notification email and the WhatsApp link are under Users → Founding Members.

Membership start date (same screen): founding members who pay before the start date have their first renewal moved to one month or one year after it, so they are not billed again until a full period after opening. Nobody is charged extra. Existing founding members can be previewed and moved with one button, and every change is logged.

== Changelog ==

= 1.4.1 =
* The sign-up page fills in the email from the payment email link, so members pay with the address they reserved with.

= 1.4.0 =
* Welcome page now has a Download the Playtomic app button and a Join on WhatsApp button. Add both links under Users → Founding Members.

= 1.3.1 =
* Fixed the sign-up form sending people to the home page instead of checkout.

= 1.3.0 =
* Founding members who pay before the membership start date now first renew one month or one year after that date, not after the day they paid.
* New "Existing founding members" preview and apply button, and a log of every renewal date moved.

= 1.2.1 =
* Opening phases now show November and December.

= 1.2.0 =
* Sign-up answers are now saved to each member's user profile, with a Founding members filter on the Users screen.
* If the email doesn't match an account, one is created.
* Settings moved to Users → Founding Members.

= 1.1.1 =
* Plugin renamed to BlueWorx Client | PadlX.

= 1.1.0 =
* Member numbers are now each member's user ID.
* Checkout links for the annual and monthly Founding prices are built in.
* The welcome page now lives at /welcome.

= 1.0.0 =
* New founding member sign-up page and welcome page, built from the Figma designs.
