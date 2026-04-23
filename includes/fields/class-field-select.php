<?php
/**
 * Select field type.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Select extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$saved      = $this->load( $post_id );
		$choices    = $this->field['choices'] ?? array();
		$allow_null = ! empty( $this->field['allow_null'] );
		$multiple   = ! empty( $this->field['multiple'] );
		$name       = esc_attr( $this->field['name'] );
		$id         = esc_attr( 'fieldforge_field_' . $this->field['name'] );

		$multi_attr = $multiple ? ' multiple' : '';
		$name_attr  = $multiple ? $name . '[]' : $name;

		$html  = '<select name="' . $name_attr . '" id="' . $id . '" class="fieldforge-select widefat"' . $multi_attr . '>';
		if ( $allow_null ) {
			$html .= '<option value="">' . esc_html__( '— Select —', 'fieldforge' ) . '</option>';
		}
		foreach ( $choices as $val => $label ) {
			$selected = $multiple
				? ( in_array( (string) $val, (array) $saved, true ) ? ' selected' : '' )
				: selected( $saved, (string) $val, false );
			$html .= '<option value="' . esc_attr( $val ) . '"' . $selected . '>' . esc_html( $label ) . '</option>';
		}
		$html .= '</select>';

		// Warn if saved single value is no longer in choices.
		if ( ! $multiple && '' !== (string) $saved && ! isset( $choices[ (string) $saved ] ) ) {
			$html .= '<p class="fieldforge-orphaned-warning"><span class="dashicons dashicons-warning"></span> '
				. sprintf(
					/* translators: %s: the orphaned saved value */
					esc_html__( 'Saved value "%s" is no longer a valid choice.', 'fieldforge' ),
					esc_html( $saved )
				)
				. '</p>';
		}

		$this->render_wrapper( $html );
	}

	public function sanitize( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		}
		return sanitize_text_field( (string) $value );
	}

	public function get_empty_value() {
		return ! empty( $this->field['multiple'] ) ? array() : '';
	}
}
