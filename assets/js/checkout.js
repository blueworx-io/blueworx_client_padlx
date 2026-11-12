/* Checkout: show only the schedule for the membership being bought, and note when the box was ticked. */
( function () {
	function productIds( checkout ) {
		var items = checkout && checkout.line_items && checkout.line_items.data;
		return ( Array.isArray( items ) ? items : [] ).map( function ( item ) {
			var product = item.price && item.price.product;
			return product && product.id ? product.id : product;
		} );
	}

	function showSchedules( event ) {
		var detail = event.detail || {};
		var ids = productIds( detail.checkout || detail );
		document.querySelectorAll( '.padlx-schedule' ).forEach( function ( schedule ) {
			schedule.hidden = ids.indexOf( schedule.getAttribute( 'data-product' ) ) === -1;
		} );
	}

	document.addEventListener( 'scCheckoutInitiated', showSchedules );
	document.addEventListener( 'scCheckoutUpdated', showSchedules );

	// The value is what SureCart saves on the order.
	document.addEventListener( 'scChange', function ( event ) {
		var box = event.target;
		if ( box && box.classList && box.classList.contains( 'padlx-accept' ) ) {
			box.value = new Date().toLocaleString( 'en-AU', { timeZone: 'Australia/Brisbane', dateStyle: 'medium', timeStyle: 'medium' } ) + ' Brisbane time';
		}
	} );
} )();
