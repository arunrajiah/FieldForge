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

	public function validate( $value ) {
		$parent = parent::validate( $value );
		if ( true !== $parent ) {
			return $parent;
		}
		if ( '' === $value || null === $value ) {
			return true;
		}
		$num = floatval( $value );
		$min = $this->field['min'] ?? '';
		$max = $this->field['max'] ?? '';
		if ( '' !== $min && $num < floatval( $min ) ) {
			return sprintf(
				/* translators: 1: field label, 2: min value */
				__( '"%1$s" must be at least %2$s.', 'fieldforge' ),
				$this->field['label'] ?? $this->field['name'],
				$min
			);
		}
		if ( '' !== $max && $num > floatval( $max ) ) {
			return sprintf(
				/* translators: 1: field label, 2: max value */
				__( '"%1$s" must be no greater than %2$s.', 'fieldforge' ),
				$this->field['label'] ?? $this->field['name'],
				$max
			);
		}
		return true;
	}
}
