<?php
/**
 * Email field type.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Email extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$value       = (string) $this->load( $post_id );
		$placeholder = esc_attr( $this->field['placeholder'] ?? '' );

		$html = sprintf(
			'<input type="email" %s value="%s" placeholder="%s" class="fieldforge-input fieldforge-input--email widefat" />',
			$this->input_attrs(),
			esc_attr( $value ),
			$placeholder
		);

		$this->render_wrapper( $html );
	}

	public function sanitize( $value ): string {
		return sanitize_email( (string) $value );
	}

	public function validate( $value ) {
		$parent = parent::validate( $value );
		if ( true !== $parent ) {
			return $parent;
		}
		if ( '' === $value || null === $value ) {
			return true;
		}
		if ( ! is_email( (string) $value ) ) {
			return sprintf(
				/* translators: %s: field label */
				__( '"%s" must be a valid email address.', 'fieldforge' ),
				$this->field['label'] ?? $this->field['name']
			);
		}
		return true;
	}
}
