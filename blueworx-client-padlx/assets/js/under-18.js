/* Sign-up form: explain that under-18s need a parent or guardian. It explains only, and doesn't stop them continuing. */
( function () {
	var input = document.getElementById( 'bwpx-dob' );
	if ( ! input || ! window.padlxUnder18 || typeof HTMLDialogElement === 'undefined' ) {
		return;
	}

	function isUnder18( value ) {
		var parts = value.split( /\D+/ ).filter( Boolean ).map( Number );
		if ( parts.length !== 3 || parts[ 2 ] < 1900 ) {
			return false;
		}
		var born = new Date( parts[ 2 ], parts[ 1 ] - 1, parts[ 0 ] );
		if ( born.getMonth() !== parts[ 1 ] - 1 || born.getDate() !== parts[ 0 ] ) {
			return false;
		}
		var eighteen = new Date( parts[ 2 ] + 18, parts[ 1 ] - 1, parts[ 0 ] );
		return eighteen > new Date();
	}

	var dialog = document.createElement( 'dialog' );
	dialog.className = 'padlx-dialog';
	var heading = document.createElement( 'h2' );
	heading.textContent = 'Under 18?';
	var text = document.createElement( 'p' );
	text.textContent = window.padlxUnder18.text;
	var form = document.createElement( 'form' );
	form.method = 'dialog';
	var button = document.createElement( 'button' );
	button.className = 'bwpx-btn';
	button.textContent = 'OK';
	form.appendChild( button );
	dialog.append( heading, text, form );
	// Inside the page's wrapper, so the button picks up the page's styling.
	( input.closest( '.bwpx' ) || document.body ).appendChild( dialog );

	input.addEventListener( 'change', function () {
		if ( isUnder18( input.value ) && ! dialog.open ) {
			dialog.showModal();
		}
	} );
} )();
