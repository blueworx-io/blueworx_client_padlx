<?php
/**
 * Markup helpers for the sign-up form. Each prints one field in the design's style.
 */

namespace BlueWorx\PadlX\Form;

use function BlueWorx\PadlX\fields;

defined( 'ABSPATH' ) || exit;

function value( array $state, $key ) {
	return isset( $state['values'][ $key ] ) ? (string) $state['values'][ $key ] : '';
}

function error( array $state, $key ) {
	if ( empty( $state['errors'][ $key ] ) ) {
		return '';
	}
	return sprintf( '<p class="bwpx-error bwpx-small" id="bwpx-%1$s-error">%2$s</p>', esc_attr( $key ), esc_html( $state['errors'][ $key ] ) );
}

function invalid_attrs( array $state, $key ) {
	return empty( $state['errors'][ $key ] ) ? '' : sprintf( ' aria-invalid="true" aria-describedby="bwpx-%s-error"', esc_attr( $key ) );
}

/**
 * Text-style input. $args: label, helper, placeholder, type, autocomplete, inputmode.
 */
function input( array $state, $key, array $args = array() ) {
	$field    = fields()[ $key ];
	$label    = $args['label'] ?? $field['label'];
	$type     = $args['type'] ?? 'text';
	$required = $field['required'] ? ' required' : '';
	?>
	<div class="bwpx-field">
		<label class="bwpx-label" for="bwpx-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
		<input class="bwpx-input" type="<?php echo esc_attr( $type ); ?>" id="bwpx-<?php echo esc_attr( $key ); ?>" name="bwpx[<?php echo esc_attr( $key ); ?>]"
			value="<?php echo esc_attr( value( $state, $key ) ); ?>"
			<?php if ( ! empty( $args['placeholder'] ) ) : ?>placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>"<?php endif; ?>
			<?php if ( ! empty( $args['autocomplete'] ) ) : ?>autocomplete="<?php echo esc_attr( $args['autocomplete'] ); ?>"<?php endif; ?>
			<?php if ( ! empty( $args['inputmode'] ) ) : ?>inputmode="<?php echo esc_attr( $args['inputmode'] ); ?>"<?php endif; ?>
			<?php if ( ! empty( $args['maxlength'] ) ) : ?>maxlength="<?php echo esc_attr( $args['maxlength'] ); ?>"<?php endif; ?>
			<?php echo $required . invalid_attrs( $state, $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php if ( ! empty( $args['helper'] ) ) : ?>
			<p class="bwpx-small bwpx-muted"><?php echo esc_html( $args['helper'] ); ?></p>
		<?php endif; ?>
		<?php echo error( $state, $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<?php
}

function textarea( array $state, $key, array $args = array() ) {
	$field = fields()[ $key ];
	?>
	<div class="bwpx-field">
		<label class="bwpx-label" for="bwpx-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $args['label'] ?? $field['label'] ); ?></label>
		<textarea class="bwpx-input bwpx-input--textarea" id="bwpx-<?php echo esc_attr( $key ); ?>" name="bwpx[<?php echo esc_attr( $key ); ?>]"<?php echo invalid_attrs( $state, $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_textarea( value( $state, $key ) ); ?></textarea>
		<?php if ( ! empty( $args['helper'] ) ) : ?>
			<p class="bwpx-small bwpx-muted"><?php echo esc_html( $args['helper'] ); ?></p>
		<?php endif; ?>
		<?php echo error( $state, $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<?php
}

function select( array $state, $key, array $args = array() ) {
	$field   = fields()[ $key ];
	$current = value( $state, $key );
	?>
	<div class="bwpx-field">
		<label class="bwpx-label" for="bwpx-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $args['label'] ?? $field['label'] ); ?></label>
		<select class="bwpx-input bwpx-input--select" id="bwpx-<?php echo esc_attr( $key ); ?>" name="bwpx[<?php echo esc_attr( $key ); ?>]"
			<?php if ( ! empty( $args['autocomplete'] ) ) : ?>autocomplete="<?php echo esc_attr( $args['autocomplete'] ); ?>"<?php endif; ?>
			<?php echo ( $field['required'] ? ' required' : '' ) . invalid_attrs( $state, $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<option value="">Select</option>
			<?php foreach ( $field['options'] as $option ) : ?>
				<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $current, $option ); ?>><?php echo esc_html( $option ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php if ( ! empty( $args['helper'] ) ) : ?>
			<p class="bwpx-small bwpx-muted"><?php echo esc_html( $args['helper'] ); ?></p>
		<?php endif; ?>
		<?php echo error( $state, $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<?php
}

/**
 * A single-choice group, shown as pills ('chip') or title-and-hint cards ('card').
 */
function choices( array $state, $key, $style = 'chip', $helper = '' ) {
	$field   = fields()[ $key ];
	$current = value( $state, $key );
	?>
	<fieldset class="bwpx-group"<?php echo empty( $state['errors'][ $key ] ) ? '' : sprintf( ' aria-describedby="bwpx-%s-error"', esc_attr( $key ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<legend class="bwpx-body"><?php echo esc_html( $field['label'] ); ?></legend>
		<?php if ( $helper ) : ?>
			<p class="bwpx-small bwpx-muted"><?php echo esc_html( $helper ); ?></p>
		<?php endif; ?>
		<div class="<?php echo 'card' === $style ? 'bwpx-cards' : 'bwpx-chips'; ?>">
			<?php foreach ( $field['options'] as $i => $option ) : ?>
				<label class="bwpx-choice">
					<input class="bwpx-choice__input" type="radio" name="bwpx[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $option ); ?>"
						<?php checked( $current, $option ); ?><?php echo ( $field['required'] && 0 === $i ) ? ' required' : ''; ?>>
					<?php if ( 'card' === $style ) : ?>
						<span class="bwpx-card">
							<span class="bwpx-card__title bwpx-body"><?php echo esc_html( $option ); ?></span>
							<span class="bwpx-card__hint bwpx-small"><?php echo esc_html( $field['hints'][ $i ] ?? '' ); ?></span>
						</span>
					<?php else : ?>
						<span class="bwpx-chip bwpx-body"><?php echo esc_html( $option ); ?></span>
					<?php endif; ?>
				</label>
			<?php endforeach; ?>
		</div>
		<?php echo error( $state, $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</fieldset>
	<?php
}

function step_header( $number, $title, $intro = '' ) {
	?>
	<div class="bwpx-step__header">
		<span class="bwpx-num bwpx-eyebrow" aria-hidden="true"><?php echo esc_html( $number ); ?></span>
		<div class="bwpx-step__text">
			<h2 class="bwpx-h3"><?php echo esc_html( $title ); ?></h2>
			<?php if ( $intro ) : ?>
				<p class="bwpx-body bwpx-muted"><?php echo esc_html( $intro ); ?></p>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
