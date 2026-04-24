<?php
/**
 * Link field type — stores title, URL, and target as an array.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Link extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$saved  = (array) $this->load( $post_id );
		$name   = esc_attr( $this->field['name'] );
		$prefix = $name;

		$url    = esc_attr( $saved['url'] ?? '' );
		$title  = esc_attr( $saved['title'] ?? '' );
		$target = $saved['target'] ?? '';

		$html  = '<div class="fieldforge-link-field">';
		$html .= '<label class="fieldforge-sub-label">' . esc_html__( 'URL', 'fieldforge' ) . '</label>';
		$html .= '<input type="url" name="' . $prefix . '[url]" value="' . $url . '" placeholder="https://" class="fieldforge-input widefat" />';
		$html .= '<label class="fieldforge-sub-label">' . esc_html__( 'Link Text', 'fieldforge' ) . '</label>';
		$html .= '<input type="text" name="' . $prefix . '[title]" value="' . $title . '" class="fieldforge-input widefat" />';
		$html .= '<label class="fieldforge-sub-label">';
		$html .= '<input type="checkbox" name="' . $prefix . '[target]" value="_blank"' . checked( $target, '_blank', false ) . ' /> ';
		$html .= esc_html__( 'Open in new tab', 'fieldforge' );
		$html .= '</label>';
		$html .= '</div>';

		$this->render_wrapper( $html );
	}

	public function sanitize( $value ): array {
		if ( ! is_array( $value ) ) {
			return array(
				'url'    => '',
				'title'  => '',
				'target' => '',
			);
		}
		return array(
			'url'    => esc_url_raw( $value['url'] ?? '' ),
			'title'  => sanitize_text_field( $value['title'] ?? '' ),
			'target' => isset( $value['target'] ) && '_blank' === $value['target'] ? '_blank' : '',
		);
	}

	public function validate( $value ) {
		$parent = parent::validate( $value );
		if ( true !== $parent ) {
			return $parent;
		}
		if ( ! empty( $value ) && is_array( $value ) && ! empty( $value['url'] ) ) {
			if ( ! filter_var( $value['url'], FILTER_VALIDATE_URL ) ) {
				return sprintf(
					/* translators: %s: field label */
					__( '"%s" must contain a valid URL.', 'fieldforge' ),
					$this->field['label'] ?? $this->field['name']
				);
			}
		}
		return true;
	}

	public function get_empty_value(): array {
		return array(
			'url'    => '',
			'title'  => '',
			'target' => '',
		);
	}
}
