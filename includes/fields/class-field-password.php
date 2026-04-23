<?php
/**
 * Password field type.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Password extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		// Never pre-fill a password field.
		$html = sprintf(
			'<input type="password" %s value="" autocomplete="new-password" class="fieldforge-input fieldforge-input--password widefat" />',
			$this->input_attrs()
		);

		$this->render_wrapper( $html );
	}

	public function sanitize( $value ): string {
		// Store as plain text (hashing is the theme/developer's responsibility).
		return sanitize_text_field( (string) $value );
	}

	public function load( int $post_id ) {
		// Never expose the stored value in the edit UI.
		return '';
	}
}
