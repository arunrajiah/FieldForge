<?php
/**
 * Gallery field type.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Gallery extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$ids  = (array) $this->load( $post_id );
		$ids  = array_filter( array_map( 'absint', $ids ) );
		$name = esc_attr( $this->field['name'] );

		$html  = '<div class="fieldforge-gallery-field" data-field-name="' . $name . '">';
		$html .= '<ul class="fieldforge-gallery-list">';
		foreach ( $ids as $attachment_id ) {
			$url   = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
			$html .= '<li data-id="' . esc_attr( (string) $attachment_id ) . '">';
			$html .= '<img src="' . esc_url( (string) $url ) . '" width="80" height="80" />';
			$html .= '<input type="hidden" name="' . $name . '[]" value="' . esc_attr( (string) $attachment_id ) . '" />';
			$html .= '<button type="button" class="fieldforge-gallery-remove" aria-label="' . esc_attr__( 'Remove', 'fieldforge' ) . '">&times;</button>';
			$html .= '</li>';
		}
		$html .= '</ul>';
		$html .= '<button type="button" class="button fieldforge-gallery-add">' . esc_html__( 'Add Images', 'fieldforge' ) . '</button>';
		$html .= '</div>';

		$this->render_wrapper( $html );
	}

	public function sanitize( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}
		return array_values( array_filter( array_map( 'absint', $value ) ) );
	}

	public function load( int $post_id ) {
		$val = get_post_meta( $post_id, $this->field['name'], true );
		return is_array( $val ) ? $val : array();
	}

	public function get_empty_value(): array {
		return array();
	}

	public function format_value( $value, int $post_id ) {
		$ids    = array_filter( array_map( 'absint', (array) $value ) );
		$format = $this->field['return_format'] ?? 'id';

		if ( 'url' === $format ) {
			return array_values( array_map( 'wp_get_attachment_url', $ids ) );
		}
		if ( 'array' === $format ) {
			$items = array();
			foreach ( $ids as $id ) {
				$meta    = wp_get_attachment_metadata( $id );
				$items[] = array(
					'id'        => $id,
					'url'       => (string) wp_get_attachment_url( $id ),
					'width'     => (int) ( $meta['width'] ?? 0 ),
					'height'    => (int) ( $meta['height'] ?? 0 ),
					'alt'       => get_post_meta( $id, '_wp_attachment_image_alt', true ),
					'title'     => get_the_title( $id ),
					'caption'   => wp_get_attachment_caption( $id ),
					'mime_type' => get_post_mime_type( $id ),
					'sizes'     => $meta['sizes'] ?? array(),
				);
			}
			return $items;
		}
		return array_values( $ids ); // 'id' (default)
	}
}
