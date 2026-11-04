<?php
/**
 * Under-18 pop-up on the founding member sign-up form.
 */

namespace PADLX\SiteFixes;

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_singular( 'page' ) || 'bwpx-founding-signup' !== get_page_template_slug( get_queried_object_id() ) ) {
			return;
		}
		wp_enqueue_style( 'padlx-sf', URL . 'assets/site-fixes.css', array(), VERSION );
		wp_enqueue_script( 'padlx-sf-under-18', URL . 'assets/under-18.js', array(), VERSION, true );
		wp_localize_script( 'padlx-sf-under-18', 'padlxUnder18', array( 'text' => settings()['under_18_text'] ) );
	},
	30
);
