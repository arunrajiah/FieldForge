<?php
/**
 * URL field type.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Url extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$value       = (string) $this->load( $post_id );
		$placeholder = esc_attr( $this->field['placeholder'] ?? 'https://' );

		$html = sprintf(
			'<input type="url" %s value="%s" placeholder="%s" class="fieldforge-input fieldforge-input--url widefat" />',
			$this->input_attrs(),
			esc_attr( $value ),
			$placeholder
		);

		$this->render_wrapper( $html );
	}

	public function sanitize( $value ): string {
		return esc_url_raw( (string) $value );
	}
}
