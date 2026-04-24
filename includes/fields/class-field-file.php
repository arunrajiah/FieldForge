<?php
/**
 * File field type.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_File extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$attachment_id = (int) $this->load( $post_id );
		$name          = esc_attr( $this->field['name'] );
		$id            = esc_attr( 'fieldforge_field_' . $this->field['name'] );
		$file_url      = $attachment_id ? wp_get_attachment_url( $attachment_id ) : '';
		$file_name     = $attachment_id ? basename( $file_url ) : '';

		$html  = '<div class="fieldforge-file-field" data-field-name="' . $name . '">';
		$html .= '<input type="hidden" name="' . $name . '" id="' . $id . '" value="' . esc_attr( (string) $attachment_id ) . '" class="fieldforge-file-id" />';
		if ( $file_url ) {
			$html .= '<p class="fieldforge-file-info"><a href="' . esc_url( $file_url ) . '" target="_blank">' . esc_html( $file_name ) . '</a></p>';
		} else {
			$html .= '<p class="fieldforge-file-info" style="display:none"></p>';
		}
		$html .= '<button type="button" class="button fieldforge-file-select">' . esc_html__( 'Select File', 'fieldforge' ) . '</button> ';
		$html .= '<button type="button" class="button fieldforge-file-remove"' . ( $attachment_id ? '' : ' style="display:none"' ) . '>' . esc_html__( 'Remove', 'fieldforge' ) . '</button>';
		$html .= '</div>';

		$this->render_wrapper( $html );
	}

	public function sanitize( $value ): int {
		return absint( $value );
	}

	public function load( int $post_id ) {
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
			$url  = (string) wp_get_attachment_url( $id );
			return array(
				'id'        => $id,
				'url'       => $url,
				'filename'  => basename( get_attached_file( $id ) ),
				'title'     => get_the_title( $id ),
				'filesize'  => (int) ( $meta['filesize'] ?? @filesize( get_attached_file( $id ) ) ), // phpcs:ignore
				'mime_type' => get_post_mime_type( $id ),
			);
		}
		return $id; // 'id' (default)
	}
}
