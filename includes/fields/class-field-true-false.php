<?php
/**
 * True / False (toggle) field type.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_True_False extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$saved   = (bool) $this->load( $post_id );
		$name    = esc_attr( $this->field['name'] );
		$id      = esc_attr( 'fieldforge_field_' . $this->field['name'] );
		$message = esc_html( $this->field['message'] ?? $this->field['label'] ?? '' );

		// Hidden input ensures a 0 is posted when unchecked.
		$html  = '<input type="hidden" name="' . $name . '" value="0" />';
		$html .= sprintf(
			'<label class="fieldforge-true-false"><input type="checkbox" name="%s" id="%s" value="1"%s /> %s</label>',
			$name,
			$id,
			checked( $saved, true, false ),
			$message
		);

		$this->render_wrapper( $html );
	}

	public function sanitize( $value ): int {
		return absint( $value ) ? 1 : 0;
	}

	public function load( int $post_id ) {
		return (bool) get_post_meta( $post_id, $this->field['name'], true );
	}
}
