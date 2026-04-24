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
		// The editor saves the content under 'message_content'; 'message' is the
		// legacy / ACF-import key. Support both so neither path is lost.
		$content   = $this->field['message_content'] ?? $this->field['message'] ?? $this->field['default_value'] ?? '';
		$new_lines = $this->field['new_lines'] ?? 'wpautop';

		if ( 'wpautop' === $new_lines ) {
			$content = wpautop( $content );
		} elseif ( 'br' === $new_lines ) {
			$content = nl2br( $content );
		}

		$html = '<div class="fieldforge-message">' . wp_kses_post( $content ) . '</div>';
		$this->render_wrapper( $html );
	}

	public function sanitize( $value ): string {
		return '';
	}

	public function save( int $post_id, $value ): void {
		// Nothing to save.
	}
}
