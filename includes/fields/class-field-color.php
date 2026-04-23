<?php
/**
 * Color picker field type.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Color extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$value = (string) $this->load( $post_id );
		if ( ! $value ) {
			$value = $this->field['default_value'] ?? '#ffffff';
		}

		$html = sprintf(
			'<input type="color" %s value="%s" class="fieldforge-input fieldforge-input--color" />',
			$this->input_attrs(),
			esc_attr( $value )
		);

		$this->render_wrapper( $html );
	}

	public function sanitize( $value ): string {
		$value = sanitize_hex_color( (string) $value );
		return $value ?: '';
	}
}
