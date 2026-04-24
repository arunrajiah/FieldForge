<?php
/**
 * Accordion field type — layout/presentational only, stores no data.
 *
 * Renders a collapsible section heading in the field group meta box. Subsequent
 * fields are grouped under the accordion until the next accordion or tab field.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Accordion extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$label    = esc_html( $this->field['label'] ?? __( 'Section', 'fieldforge' ) );
		$open     = ! empty( $this->field['open'] );
		$icon     = $open ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2';
		$expanded = $open ? 'true' : 'false';

		echo '<div class="fieldforge-field fieldforge-field--accordion">';
		echo '<button type="button" class="fieldforge-accordion-toggle" aria-expanded="' . esc_attr( $expanded ) . '">';
		echo '<span class="fieldforge-accordion-label">' . esc_html( $this->field['label'] ?? __( 'Section', 'fieldforge' ) ) . '</span>';
		echo '<span class="dashicons ' . esc_attr( $icon ) . ' fieldforge-accordion-icon"></span>';
		echo '</button>';
		echo '</div>';
	}

	public function sanitize( $value ) {
		return '';
	}

	public function save( int $post_id, $value ): void {
		// Accordion is a layout field — nothing to save.
	}

	public function load( int $post_id ) {
		return '';
	}
}
