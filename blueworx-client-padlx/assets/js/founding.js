/* Founding member sign-up: stop double submits while the browser heads to checkout. */
( function () {
	var form = document.getElementById( 'bwpx-form' );
	if ( ! form ) {
		return;
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
