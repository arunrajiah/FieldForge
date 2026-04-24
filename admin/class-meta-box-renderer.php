<?php
/**
 * Standalone meta box renderer helper — used for rendering individual field
 * HTML snippets via AJAX (e.g., when adding a new field row in the editor).
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Meta_Box_Renderer {

	/** @var FieldForge_Field_Registry */
	private FieldForge_Field_Registry $registry;

	public function __construct( FieldForge_Field_Registry $registry ) {
		$this->registry = $registry;
		add_action( 'wp_ajax_fieldforge_render_field', array( $this, 'ajax_render_field' ) );
	}

	/**
	 * Render a list of fields for a given post and return the HTML.
	 *
	 * @param array $fields   Array of field config arrays.
	 * @param int   $post_id
	 * @return string
	 */
	public function render_fields( array $fields, int $post_id ): string {
		ob_start();
		foreach ( $fields as $field_config ) {
			$field = $this->registry->make_field( $field_config );
			if ( $field ) {
				$field->render( $post_id );
			}
		}
		return ob_get_clean();
	}

	/**
	 * AJAX: render a single blank field by type (used by the editor UI).
	 */
	public function ajax_render_field(): void {
		check_ajax_referer( 'fieldforge_admin', 'nonce' );
		$ajax_post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above
		$cap = $ajax_post_id ? 'edit_post' : 'edit_posts';
		if ( ! current_user_can( $cap, $ajax_post_id ? $ajax_post_id : null ) ) {
			wp_send_json_error( null, 403 );
		}

		$type  = sanitize_key( $_POST['type'] ?? 'text' );
		$field = $this->registry->make_field(
			array(
				'key'   => 'field_preview',
				'type'  => $type,
				'name'  => 'preview',
				'label' => __( 'New Field', 'fieldforge' ),
			)
		);

		if ( ! $field ) {
			wp_send_json_error( array( 'message' => 'Unknown field type.' ) );
		}

		ob_start();
		$field->render( 0 );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}
}
