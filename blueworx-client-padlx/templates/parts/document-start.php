<?php
/**
 * Opens a full-page document without the theme's header.
 *
 * @var bool $secure Show the "Secure checkout" note.
 */

namespace BlueWorx\PadlX;

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="bwpx">
	<header class="bwpx-header">
		<a class="bwpx-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<img src="<?php echo esc_url( asset( 'logo.svg' ) ); ?>" width="156" height="31" alt="PADLX">
		</a>
		<?php if ( ! empty( $secure ) ) : ?>
			<p class="bwpx-header__secure bwpx-label">
				<img src="<?php echo esc_url( asset( 'lock.svg' ) ); ?>" width="14" height="16" alt="">
				Secure checkout
			</p>
		<?php endif; ?>
	</header>
