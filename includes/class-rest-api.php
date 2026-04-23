<?php
/**
 * REST API integration — exposes FieldForge field values in WP REST API responses.
 *
 * For every public post type, registers a `fieldforge_fields` property on the
 * REST response object. That property is a key→value map of all FieldForge
 * field values for the post.
 *
 * Individual fields can be filtered:
 *   add_filter( 'fieldforge/rest/expose_field', function( $expose, $field_config, $post ) {
 *       return 'secret_token' !== $field_config['name']; // hide a sensitive field
 *   }, 10, 3 );
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
					'update_callback' => null,
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

				$values[ $name ] = $field->load( $post_id );
			}
		}

		return $values;
	}
}
