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
}
