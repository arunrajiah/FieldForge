<?php
/**
 * Checkbox field type.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Checkbox extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$saved   = (array) $this->load( $post_id );
		$choices = $this->field['choices'] ?? array();
		$name    = esc_attr( $this->field['name'] );

		$html = '<ul class="fieldforge-checkbox-list">';
		foreach ( $choices as $val => $label ) {
			$checked = in_array( (string) $val, $saved, true ) ? ' checked' : '';
			$html   .= sprintf(
				'<li><label><input type="checkbox" name="%s[]" value="%s"%s /> %s</label></li>',
				$name,
				esc_attr( $val ),
				$checked,
				esc_html( $label )
			);
		}
		$html .= '</ul>';

		// Warn about any saved values no longer present in choices.
		$orphans = array_diff( $saved, array_map( 'strval', array_keys( $choices ) ) );
		if ( ! empty( $orphans ) ) {
			$html .= '<p class="fieldforge-orphaned-warning"><span class="dashicons dashicons-warning"></span> '
				. sprintf(
					/* translators: %s: comma-separated orphaned values */
					esc_html__( 'Saved value(s) "%s" are no longer valid choices.', 'fieldforge' ),
					esc_html( implode( '", "', $orphans ) )
				)
				. '</p>';
		}

		$this->render_wrapper( $html );
	}

	public function sanitize( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}
		return array_map( 'sanitize_text_field', $value );
	}

	public function get_empty_value(): array {
		return array();
	}
}
