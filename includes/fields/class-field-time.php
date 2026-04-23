<?php
/**
 * Time picker field type.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Time extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$value       = (string) $this->load( $post_id );
		$placeholder = esc_attr( $this->field['placeholder'] ?? 'HH:MM' );

		$html = sprintf(
			'<input type="time" %s value="%s" placeholder="%s" class="fieldforge-input fieldforge-input--time" />',
			$this->input_attrs(),
			esc_attr( $value ),
			$placeholder
		);

		$this->render_wrapper( $html );
	}

	public function sanitize( $value ): string {
		$value = sanitize_text_field( (string) $value );
		if ( preg_match( '/^\d{2}:\d{2}(:\d{2})?$/', $value ) ) {
			return $value;
		}
		return '';
	}
}
