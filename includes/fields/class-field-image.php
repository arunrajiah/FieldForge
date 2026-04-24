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
		if ( null !== $this->prefilled_value ) {
			return (int) $this->prefilled_value;
		}
		return (int) get_post_meta( $post_id, $this->field['name'], true );
	}

	public function format_value( $value, int $post_id ) {
		$id     = (int) $value;
		$format = $this->field['return_format'] ?? 'id';

		if ( ! $id ) {
			return 'array' === $format ? array() : ( 'url' === $format ? '' : 0 );
		}
		if ( 'url' === $format ) {
			return (string) wp_get_attachment_url( $id );
		}
		if ( 'array' === $format ) {
			$meta = wp_get_attachment_metadata( $id );
			return array(
				'id'        => $id,
				'url'       => (string) wp_get_attachment_url( $id ),
				'width'     => (int) ( $meta['width'] ?? 0 ),
				'height'    => (int) ( $meta['height'] ?? 0 ),
				'alt'       => get_post_meta( $id, '_wp_attachment_image_alt', true ),
				'title'     => get_the_title( $id ),
				'caption'   => wp_get_attachment_caption( $id ),
				'mime_type' => get_post_mime_type( $id ),
				'filesize'  => (int) ( $meta['filesize'] ?? 0 ),
				'sizes'     => $meta['sizes'] ?? array(),
			);
		}
		return $id; // 'id' (default)
	}
}
