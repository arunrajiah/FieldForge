<?php
/**
 * WYSIWYG (rich text editor) field type.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Wysiwyg extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$value    = (string) $this->load( $post_id );
		$name     = $this->field['name'];
		$editor_id = 'fieldforge_wysiwyg_' . sanitize_key( $name );
		$toolbar  = $this->field['toolbar'] ?? 'full';
		$media    = ! isset( $this->field['media_upload'] ) || (bool) $this->field['media_upload'];

		ob_start();
		wp_editor(
			$value,
			$editor_id,
			array(
				'textarea_name'  => $name,
				'textarea_rows'  => 10,
				'media_buttons'  => $media,
				'tinymce'        => array(
					'toolbar1' => 'full' === $toolbar
						? 'formatselect,bold,italic,underline,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,wp_more,undo,redo'
						: 'bold,italic,link,unlink',
				),
				'quicktags'      => true,
			)
		);
		$html = ob_get_clean();

		$this->render_wrapper( $html );
	}

	public function sanitize( $value ): string {
		return wp_kses_post( (string) $value );
	}
}
