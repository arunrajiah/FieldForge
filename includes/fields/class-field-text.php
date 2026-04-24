<?php
/**
 * Text field type.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Text extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$value       = (string) $this->load( $post_id );
		$placeholder = esc_attr( $this->field['placeholder'] ?? '' );
		$maxlength   = ! empty( $this->field['maxlength'] ) ? ' maxlength="' . esc_attr( $this->field['maxlength'] ) . '"' : '';

		$html = sprintf(
			'<input type="text" %s value="%s" placeholder="%s" class="fieldforge-input fieldforge-input--text widefat"%s />',
			$this->input_attrs(),
			esc_attr( $value ),
			$placeholder,
			$maxlength
		);

		$this->render_wrapper( $html );
	}

	public function sanitize( $value ): string {
		return sanitize_text_field( (string) $value );
	}

	public function validate( $value ) {
		$parent = parent::validate( $value );
		if ( true !== $parent ) {
			return $parent;
		}
		$maxlength = $this->field['maxlength'] ?? '';
		if ( '' !== $maxlength && strlen( (string) $value ) > (int) $maxlength ) {
			return sprintf(
				/* translators: 1: field label, 2: max length */
				__( '"%1$s" must be no longer than %2$d characters.', 'fieldforge' ),
				$this->field['label'] ?? $this->field['name'],
				(int) $maxlength
			);
		}
		return true;
	}
}
