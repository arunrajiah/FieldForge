<?php
/**
 * Image field type.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Image extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$attachment_id = (int) $this->load( $post_id );
		$name          = esc_attr( $this->field['name'] );
		$id            = esc_attr( 'fieldforge_field_' . $this->field['name'] );
		$preview_url   = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'thumbnail' ) : '';

		if ( $attachment_id && ! $preview_url ) {
			// Referenced attachment no longer exists.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf( 'FieldForge: image field "%s" references missing attachment ID %d.', $this->field['name'], $attachment_id ) );
			$attachment_id = 0;
		}

		$html  = '<div class="fieldforge-image-field" data-field-name="' . $name . '">';
		$html .= '<input type="hidden" name="' . $name . '" id="' . $id . '" value="' . esc_attr( (string) $attachment_id ) . '" class="fieldforge-image-id" />';
		if ( $preview_url ) {
			$html .= '<img src="' . esc_url( $preview_url ) . '" class="fieldforge-image-preview" style="max-width:150px;display:block;margin-bottom:6px;" />';
		} else {
			$html .= '<img src="" class="fieldforge-image-preview" style="max-width:150px;display:none;margin-bottom:6px;" />';
		}
		$html .= '<button type="button" class="button fieldforge-image-select">' . esc_html__( 'Select Image', 'fieldforge' ) . '</button> ';
		$html .= '<button type="button" class="button fieldforge-image-remove"' . ( $attachment_id ? '' : ' style="display:none"' ) . '>' . esc_html__( 'Remove', 'fieldforge' ) . '</button>';
		$html .= '</div>';

		$this->render_wrapper( $html );
	}

	public function sanitize( $value ): int {
		return absint( $value );
	}

	public function load( int $post_id ) {
		return (int) get_post_meta( $post_id, $this->field['name'], true );
	}
}
