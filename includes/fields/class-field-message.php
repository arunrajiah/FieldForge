<?php
/**
 * Message (display-only) field type — renders a block of HTML, stores nothing.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Message extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$message = wp_kses_post( $this->field['message'] ?? $this->field['default_value'] ?? '' );
		$html    = '<div class="fieldforge-message">' . $message . '</div>';
		$this->render_wrapper( $html );
	}

	public function sanitize( $value ): string {
		return '';
	}

	public function save( int $post_id, $value ): void {
		// Nothing to save.
	}
}
