<?php
/**
 * Tab field type — layout/presentational only, stores no data.
 *
 * Renders a tab heading divider in the field group meta box. In the admin JS the
 * tab label is used to visually group subsequent fields until the next tab.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Tab extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$label = esc_html( $this->field['label'] ?? __( 'Tab', 'fieldforge' ) );
		echo '<div class="fieldforge-field fieldforge-field--tab">';
		echo '<div class="fieldforge-tab-label">' . $label . '</div>';
		echo '</div>';
	}

	public function sanitize( $value ) {
		return '';
	}

	public function save( int $post_id, $value ): void {
		// Tab is a layout field — nothing to save.
	}

	public function load( int $post_id ) {
		return '';
	}
}
