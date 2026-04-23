<?php
/**
 * Date picker field type.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Date extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$value       = (string) $this->load( $post_id );
		$placeholder = esc_attr( $this->field['placeholder'] ?? 'YYYY-MM-DD' );

		$html = sprintf(
			'<input type="date" %s value="%s" placeholder="%s" class="fieldforge-input fieldforge-input--date" />',
			$this->input_attrs(),
			esc_attr( $value ),
			$placeholder
		);

		$this->render_wrapper( $html );
	}

	public function sanitize( $value ): string {
		$value = sanitize_text_field( (string) $value );
		// Accept Ymd or Y-m-d formats; normalize to Ymd for ACF compatibility.
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			return str_replace( '-', '', $value );
		}
		if ( preg_match( '/^\d{8}$/', $value ) ) {
			return $value;
		}
		return '';
	}

	public function load( int $post_id ) {
		$val = get_post_meta( $post_id, $this->field['name'], true );
		// Convert Ymd stored value to Y-m-d for HTML date input.
		if ( preg_match( '/^\d{8}$/', (string) $val ) ) {
			return substr( $val, 0, 4 ) . '-' . substr( $val, 4, 2 ) . '-' . substr( $val, 6, 2 );
		}
		return (string) $val;
	}
}
