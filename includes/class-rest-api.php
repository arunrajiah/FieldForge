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
		add_action( 'rest_api_init', array( $this, 'register_options_routes' ) );
	}

	/**
	 * Register REST routes for options pages under the fieldforge/v1 namespace.
	 *
	 * GET  /fieldforge/v1/options/{page_slug}       — read all field values
	 * POST /fieldforge/v1/options/{page_slug}       — write field values (JSON body)
	 */
	public function register_options_routes(): void {
		register_rest_route(
			'fieldforge/v1',
			'/options/(?P<page_slug>[a-z0-9_-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_options_page_fields' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'page_slug' => array(
							'sanitize_callback' => 'sanitize_key',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_options_page_fields' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => array(
						'page_slug' => array(
							'sanitize_callback' => 'sanitize_key',
						),
					),
				),
			)
		);
	}

	/**
	 * REST callback: return all field values for a registered options page.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_options_page_fields( WP_REST_Request $request ) {
		$page_slug = $request->get_param( 'page_slug' );
		$page      = FieldForge_Options_Page::get_page( $page_slug );
		if ( ! $page ) {
			return new WP_Error( 'fieldforge_options_not_found', __( 'Options page not found.', 'fieldforge' ), array( 'status' => 404 ) );
		}

		$ff       = FieldForge::get_instance();
		$registry = $ff->registry;
		$groups   = FieldForge_Options_Page::get_groups_for_page( $page_slug );
		$values   = array();

		foreach ( $groups as $group ) {
			foreach ( $group['fields'] as $field_config ) {
				$name = $field_config['name'] ?? '';
				if ( ! $name ) {
					continue;
				}
				if ( ! apply_filters( 'fieldforge/rest/expose_field', true, $field_config, null ) ) {
					continue;
				}
				$field = $registry->make_field( $field_config );
				$raw   = FieldForge_Options_Page::get_option( $page_slug, $name );
				$values[ $name ] = $field ? $field->format_value( $raw, 0 ) : $raw;
			}
		}

		return rest_ensure_response( $values );
	}

	/**
	 * REST callback: write field values to a registered options page.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_options_page_fields( WP_REST_Request $request ) {
		$page_slug = $request->get_param( 'page_slug' );
		$page      = FieldForge_Options_Page::get_page( $page_slug );
		if ( ! $page ) {
			return new WP_Error( 'fieldforge_options_not_found', __( 'Options page not found.', 'fieldforge' ), array( 'status' => 404 ) );
		}

		$body = $request->get_json_params();
		if ( ! is_array( $body ) ) {
			return new WP_Error( 'fieldforge_options_invalid', __( 'Request body must be a JSON object.', 'fieldforge' ), array( 'status' => 400 ) );
		}

		$ff       = FieldForge::get_instance();
		$registry = $ff->registry;
		$groups   = FieldForge_Options_Page::get_groups_for_page( $page_slug );

		foreach ( $groups as $group ) {
			foreach ( $group['fields'] as $field_config ) {
				$name = $field_config['name'] ?? '';
				if ( ! $name || ! array_key_exists( $name, $body ) ) {
					continue;
				}
				$field = $registry->make_field( $field_config );
				if ( ! $field ) {
					continue;
				}
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by field->sanitize()
				$clean = $field->sanitize( $body[ $name ] );
				FieldForge_Options_Page::update_option( $page_slug, $name, $clean, $page['autoload'] ?? false );
			}
		}

		return rest_ensure_response( array( 'updated' => true ) );
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

		$ff         = FieldForge::get_instance();
		$all_groups = $ff->field_group->get_all_groups();
		$registry   = $ff->registry;
		$values     = array();

		// Filter groups to those matching this post's location rules.
		$groups = array_filter(
			$all_groups,
			static function ( array $group ) use ( $post ) {
				$location = $group['location'] ?? array();
				if ( empty( $location ) ) {
					return true; // No rules = show everywhere.
				}
				// OR groups: any group matching means visible.
				foreach ( $location as $or_group ) {
					$and_match = true;
					foreach ( (array) $or_group as $rule ) {
						$param    = $rule['param'] ?? '';
						$operator = $rule['operator'] ?? '==';
						$value    = $rule['value'] ?? '';
						$actual   = '';
						if ( 'post_type' === $param ) {
							$actual = $post->post_type;
						} elseif ( 'post_status' === $param ) {
							$actual = $post->post_status;
						}
						$match = ( '==' === $operator ) ? ( $actual === $value ) : ( $actual !== $value );
						if ( ! $match ) {
							$and_match = false;
							break;
						}
					}
					if ( $and_match ) {
						return true;
					}
				}
				return false;
			}
		);

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
				if ( ! FieldForge_Conditional_Logic::field_is_visible( $field_config, $post->ID ) ) {
					continue;
				}
				$field = $registry->make_field( $field_config );
				if ( ! $field ) {
					continue;
				}
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by field->sanitize()
				$clean  = $field->sanitize( $value[ $name ] );
				$valid  = $field->validate( $clean );
				if ( true !== $valid ) {
					return new WP_Error( 'fieldforge_rest_validation', $valid, array( 'status' => 422 ) );
				}
				$field->save( $post->ID, $clean );
			}
		}

		return true;
	}
}
