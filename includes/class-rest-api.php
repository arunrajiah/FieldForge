<?php
/**
 * REST API integration — exposes FieldForge field values in WP REST API responses.
 *
 * For every public post type, registers a `fieldforge_fields` property on the
 * REST response object. That property is a key→value map of all FieldForge
 * field values for the post.
 *
 * Individual fields can be filtered from read responses:
 *   add_filter( 'fieldforge/rest/expose_field', function( $expose, $field_config, $post ) {
 *       return 'secret_token' !== $field_config['name']; // hide a sensitive field
 *   }, 10, 3 );
 *
 * Writing: send `fieldforge_fields` as a JSON object in a PUT/POST request.
 * Only fields that belong to a registered field group are accepted; unknown keys are ignored.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_REST_API {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_fields' ) );
	}

	/**
	 * Register `fieldforge_fields` on all public REST-enabled post types.
	 */
	public function register_fields(): void {
		$post_types = get_post_types( array( 'show_in_rest' => true ), 'names' );

		foreach ( $post_types as $post_type ) {
			register_rest_field(
				$post_type,
				'fieldforge_fields',
				array(
					'get_callback'    => array( $this, 'get_fields_for_post' ),
					'update_callback' => array( $this, 'update_fields_for_post' ),
					'schema'          => array(
						'description' => __( 'FieldForge custom field values for this post.', 'fieldforge' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit' ),
					),
				)
			);
		}
	}

	/**
	 * Callback: return all FieldForge field values for the given post.
	 *
	 * @param array           $post_arr  REST API post data array.
	 * @param string          $attr      Field attribute name ('fieldforge_fields').
	 * @param WP_REST_Request $request   Current REST request.
	 * @return array Key → value map of field values.
	 */
	public function get_fields_for_post( array $post_arr, string $attr, WP_REST_Request $request ): array {
		$post_id = (int) ( $post_arr['id'] ?? 0 );
		if ( ! $post_id ) {
			return array();
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		$ff       = FieldForge::get_instance();
		$groups   = $ff->field_group->get_all_groups();
		$registry = $ff->registry;
		$values   = array();

		foreach ( $groups as $group ) {
			foreach ( $group['fields'] as $field_config ) {
				$name = $field_config['name'] ?? '';
				if ( ! $name ) {
					continue;
				}

				// Allow consumers to exclude specific fields from the REST response.
				if ( ! apply_filters( 'fieldforge/rest/expose_field', true, $field_config, $post ) ) {
					continue;
				}

				$field = $registry->make_field( $field_config );
				if ( ! $field ) {
					$values[ $name ] = get_post_meta( $post_id, $name, true );
					continue;
				}

				$raw             = $field->load( $post_id );
				$values[ $name ] = $field->format_value( $raw, $post_id );
			}
		}

		return $values;
	}

	/**
	 * REST update callback — write FieldForge field values from a REST request.
	 *
	 * @param mixed   $value   The incoming `fieldforge_fields` value (should be an associative array).
	 * @param WP_Post $post    The post being updated.
	 * @param string  $attr    Field attribute name ('fieldforge_fields').
	 * @return true|WP_Error
	 */
	public function update_fields_for_post( $value, WP_Post $post, string $attr ) {
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return new WP_Error(
				'fieldforge_rest_forbidden',
				__( 'You do not have permission to edit this post.', 'fieldforge' ),
				array( 'status' => 403 )
			);
		}

		if ( ! is_array( $value ) ) {
			return new WP_Error(
				'fieldforge_rest_invalid',
				__( 'fieldforge_fields must be an object.', 'fieldforge' ),
				array( 'status' => 400 )
			);
		}

		$ff       = FieldForge::get_instance();
		$groups   = $ff->field_group->get_all_groups();
		$registry = $ff->registry;

		foreach ( $groups as $group ) {
			foreach ( $group['fields'] as $field_config ) {
				$name = $field_config['name'] ?? '';
				if ( ! $name || ! array_key_exists( $name, $value ) ) {
					continue;
				}
				$field = $registry->make_field( $field_config );
				if ( ! $field ) {
					continue;
				}
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by field->sanitize()
				$clean = $field->sanitize( $value[ $name ] );
				$field->save( $post->ID, $clean );
			}
		}

		return true;
	}
}
