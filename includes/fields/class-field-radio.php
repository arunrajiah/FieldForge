<?php
/**
 * Radio button field type.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Radio extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$saved   = (string) $this->load( $post_id );
		$choices = $this->field['choices'] ?? array();
		$name    = esc_attr( $this->field['name'] );
		$i       = 0;

		$html = '<ul class="fieldforge-radio-list">';
		foreach ( $choices as $val => $label ) {
			$id      = esc_attr( 'fieldforge_field_' . $this->field['name'] . '_' . ( $i++ ) );
			$checked = checked( $saved, (string) $val, false );
			$html   .= sprintf(
				'<li><label><input type="radio" name="%s" id="%s" value="%s"%s /> %s</label></li>',
				$name,
				$id,
				esc_attr( $val ),
				$checked,
				esc_html( $label )
			);
		}
		$html .= '</ul>';

		// Warn if saved value no longer exists in choices.
		if ( '' !== $saved && ! array_key_exists( $saved, $choices ) ) {
			$html .= '<p class="fieldforge-orphaned-warning"><span class="dashicons dashicons-warning"></span> '
				. sprintf(
					/* translators: %s: the orphaned saved value */
					esc_html__( 'Saved value "%s" is no longer a valid choice.', 'fieldforge' ),
					esc_html( $saved )
				)
				. '</p>';
		}

		$this->render_wrapper( $html );
	}

	public function sanitize( $value ): string {
		return sanitize_text_field( (string) $value );
	}
}
