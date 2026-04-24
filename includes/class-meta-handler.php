<?php
/**
 * Saves field values on post save.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Meta_Handler {

	public function __construct() {
		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'show_validation_errors' ) );
	}

	/**
	 * Hook: save_post — iterates all matching field groups and saves submitted values.
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
	public function save_post( int $post_id, WP_Post $post ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( FieldForge_Field_Group::CPT === $post->post_type ) {
			return;
		}
		if ( 'auto-draft' === $post->post_status ) {
			return;
		}

		$ff       = FieldForge::get_instance();
		$groups   = $ff->field_group->get_all_groups();
		$registry = $ff->registry;
		$errors   = array();

		foreach ( $groups as $group ) {
			$nonce_key = 'fieldforge_nonce_' . $group['ID'];
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( empty( $_POST[ $nonce_key ] ) || ! wp_verify_nonce( sanitize_key( $_POST[ $nonce_key ] ), 'fieldforge_save_' . $group['ID'] ) ) {
				continue;
			}

			foreach ( $group['fields'] as $field_config ) {
				if ( ! FieldForge_Conditional_Logic::field_is_visible( $field_config, $post_id ) ) {
					continue;
				}

				$field = $registry->make_field( $field_config );
				if ( ! $field ) {
					continue;
				}

				$name = $field_config['name'] ?? '';
				// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified above; value passed through field sanitize().
				$value = isset( $_POST[ $name ] ) ? $field->sanitize( wp_unslash( $_POST[ $name ] ) ) : $field->get_empty_value();

				$result = $field->validate( $value );
				if ( true !== $result ) {
					$errors[] = $result;
					continue;
				}

				$field->save( $post_id, $value );
			}
		}

		if ( ! empty( $errors ) ) {
			set_transient( 'fieldforge_validation_errors_' . $post_id, $errors, 60 );
		}
	}

	/**
	 * Display any queued validation errors as an admin notice.
	 */
	public function show_validation_errors(): void {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->base, array( 'post', 'post-new' ), true ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$post_id = isset( $_GET['post'] ) ? (int) wp_unslash( $_GET['post'] ) : 0;
		if ( ! $post_id ) {
			return;
		}

		$errors = get_transient( 'fieldforge_validation_errors_' . $post_id );
		if ( empty( $errors ) ) {
			return;
		}

		delete_transient( 'fieldforge_validation_errors_' . $post_id );

		echo '<div class="notice notice-error">';
		echo '<p><strong>' . esc_html__( 'FieldForge could not save some field values:', 'fieldforge' ) . '</strong></p>';
		echo '<ul style="list-style:disc;margin-left:1.5em">';
		foreach ( (array) $errors as $error ) {
			echo '<li>' . esc_html( (string) $error ) . '</li>';
		}
		echo '</ul>';
		echo '</div>';
	}
}
