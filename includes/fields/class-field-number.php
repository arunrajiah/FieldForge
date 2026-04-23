<?php
/**
 * Number field type.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Number extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$value       = $this->load( $post_id );
		$attrs       = $this->input_attrs();
		$min         = '' !== ( $this->field['min'] ?? '' ) ? ' min="' . esc_attr( $this->field['min'] ) . '"' : '';
		$max         = '' !== ( $this->field['max'] ?? '' ) ? ' max="' . esc_attr( $this->field['max'] ) . '"' : '';
		$step        = '' !== ( $this->field['step'] ?? '' ) ? ' step="' . esc_attr( $this->field['step'] ) . '"' : '';
		$placeholder = esc_attr( $this->field['placeholder'] ?? '' );

		$html = sprintf(
			'<input type="number" %s value="%s" placeholder="%s" class="fieldforge-input fieldforge-input--number"%s%s%s />',
			$attrs,
			esc_attr( (string) $value ),
			$placeholder,
			$min,
			$max,
			$step
		);

		$this->render_wrapper( $html );
	}

	public function sanitize( $value ): string {
		if ( '' === $value || null === $value ) {
			return '';
		}
		return (string) floatval( $value );
	}
}
