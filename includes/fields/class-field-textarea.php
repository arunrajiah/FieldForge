<?php
/**
 * Textarea field type.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Textarea extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$value       = (string) $this->load( $post_id );
		$placeholder = esc_attr( $this->field['placeholder'] ?? '' );
		$rows        = (int) ( $this->field['rows'] ?? 4 );

		$html = sprintf(
			'<textarea %s placeholder="%s" rows="%d" class="fieldforge-input fieldforge-input--textarea widefat">%s</textarea>',
			$this->input_attrs(),
			$placeholder,
			$rows,
			esc_textarea( $value )
		);

		$this->render_wrapper( $html );
	}

	public function sanitize( $value ): string {
		return sanitize_textarea_field( (string) $value );
	}
}
