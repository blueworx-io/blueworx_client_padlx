/* Founding member sign-up: stop double submits while the browser heads to checkout. */
( function () {
	var form = document.getElementById( 'bwpx-form' );
	if ( ! form ) {
		return;
	}

	// The payment email links here as ?email=... so members pay with the address they
	// reserved with, which is what matches them up in Klaviyo. Done here rather than on
	// the server so a cached page can never show one person's address to another.
	var linked = new URLSearchParams( window.location.search ).get( 'email' );
	var emailBox = form.querySelector( 'input[name="bwpx[email]"]' );
	if ( linked && emailBox && ! emailBox.value ) {
		// A plus sign in an unencoded link arrives as a space.
		linked = linked.trim().replace( / /g, '+' );
		if ( /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( linked ) ) {
			emailBox.value = linked;
		}
	}

	// Date of birth: phone keypads have no slash, so add the slashes as the digits are typed.
	var dob = form.querySelector( 'input[name="bwpx[dob]"]' );
	if ( dob ) {
		dob.addEventListener( 'input', function () {
			var digits = dob.value.replace( /\D+/g, '' ).slice( 0, 8 );
			var out    = digits.slice( 0, 2 );
			if ( digits.length > 2 ) {
				out += '/' + digits.slice( 2, 4 );
			}
			if ( digits.length > 4 ) {
				out += '/' + digits.slice( 4 );
			}
			// Only reformat while typing forwards, so deleting a slash isn't fought.
			if ( dob.value.length >= ( dob.dataset.len || 0 ) && dob.value !== out ) {
				dob.value = out;
			}
			dob.dataset.len = dob.value.length;
		} );
	}

	form.addEventListener( 'submit', function () {
		var button = form.querySelector( 'button[type="submit"]' );
		if ( button ) {
			button.disabled = true;
			button.textContent = 'Taking you to checkout…';
		}
	} );

	// Coming back with the browser's back button should leave the form usable.
	window.addEventListener( 'pageshow', function () {
		var button = form.querySelector( 'button[type="submit"]' );
		if ( button && button.disabled ) {
			button.disabled = false;
			button.textContent = 'Continue to secure checkout';
		}
	} );
} )();
