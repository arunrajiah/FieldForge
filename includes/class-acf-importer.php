<?php
/**
 * Imports ACF JSON exports into FieldForge field groups.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_ACF_Importer {

	/** @var FieldForge_Field_Group */
	private FieldForge_Field_Group $field_group;

	/** Maps ACF field types to FieldForge equivalents. */
	private const TYPE_MAP = array(
		'text'             => 'text',
		'textarea'         => 'textarea',
		'number'           => 'number',
		'range'            => 'number',
		'email'            => 'email',
		'url'              => 'url',
		'password'         => 'password',
		'image'            => 'image',
		'file'             => 'file',
		'wysiwyg'          => 'wysiwyg',
		'oembed'           => 'url',
		'gallery'          => 'gallery',
		'select'           => 'select',
		'checkbox'         => 'checkbox',
		'radio'            => 'radio',
		'button_group'     => 'radio',
		'true_false'       => 'true_false',
		'link'             => 'link',
		'post_object'      => 'post_object',
		'page_link'        => 'url',
		'relationship'     => 'post_object',
		'taxonomy'         => 'taxonomy',
		'user'             => 'user',
		'google_map'       => 'text',
		'date_picker'      => 'date_picker',
		'date_time_picker' => 'date_picker',
		'time_picker'      => 'time_picker',
		'color_picker'     => 'color_picker',
		'message'          => 'message',
		'accordion'        => 'accordion',
		'tab'              => 'tab',
		'group'            => 'repeater',
		'repeater'         => 'repeater',
		'flexible_content' => 'flexible_content',
		'clone'            => 'text',
	);

	public function __construct( FieldForge_Field_Group $field_group ) {
		$this->field_group = $field_group;
		add_action( 'wp_ajax_fieldforge_import_acf', array( $this, 'ajax_import' ) );
	}

	/**
	 * Import one or more ACF field group JSON exports.
	 *
	 * @param string $json Raw JSON string (single object or array of objects).
	 * @return int[]|WP_Error Array of created post IDs.
	 */
	public function import( string $json ) {
		$data = json_decode( $json, true );
		if ( null === $data ) {
			return new WP_Error( 'invalid_json', __( 'Invalid JSON provided.', 'fieldforge' ) );
		}

		// ACF exports a single object or an array.
		if ( isset( $data['key'] ) ) {
			$data = array( $data );
		}

		if ( ! is_array( $data ) ) {
			return new WP_Error( 'invalid_format', __( 'Unexpected JSON structure.', 'fieldforge' ) );
		}

		$ids = array();
		foreach ( $data as $acf_group ) {
			$result = $this->import_group( $acf_group );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$ids[] = $result;
		}
		return $ids;
	}

	/**
	 * Convert a single ACF field group array to FieldForge format and save it.
	 *
	 * @param array $acf
	 * @return int|WP_Error
	 */
	private function import_group( array $acf ) {
		if ( empty( $acf['key'] ) || empty( $acf['title'] ) ) {
			return new WP_Error( 'missing_fields', __( 'ACF group missing key or title.', 'fieldforge' ) );
		}

		$ff_group = array(
			'title'      => sanitize_text_field( $acf['title'] ),
			'fields'     => $this->convert_fields( $acf['fields'] ?? array() ),
			'location'   => $this->convert_location( $acf['location'] ?? array() ),
			'menu_order' => (int) ( $acf['menu_order'] ?? 0 ),
			'position'   => in_array( $acf['position'] ?? '', array( 'normal', 'side', 'acf_after_title' ), true ) ? $acf['position'] : 'normal',
			'active'     => (bool) ( $acf['active'] ?? true ),
		);

		return $this->field_group->save_group( $ff_group );
	}

	/**
	 * Recursively convert an array of ACF field configs.
	 *
	 * @param array $acf_fields
	 * @return array
	 */
	private function convert_fields( array $acf_fields ): array {
		$fields = array();
		foreach ( $acf_fields as $acf_field ) {
			$ff_field = $this->convert_field( $acf_field );
			if ( $ff_field ) {
				$fields[] = $ff_field;
			}
		}
		return $fields;
	}

	/**
	 * Convert a single ACF field config to FieldForge format.
	 *
	 * @param array $acf
	 * @return array|null
	 */
	private function convert_field( array $acf ): ?array {
		$acf_type = $acf['type'] ?? '';
		$ff_type  = self::TYPE_MAP[ $acf_type ] ?? null;

		if ( null === $ff_type ) {
			// Completely unknown type — skip rather than silently downgrade.
			FieldForge_Settings_Page::debug_log(
				sprintf( 'ACF Importer: skipping unsupported field type "%s" (name: %s).', $acf_type, $acf['name'] ?? '' )
			);
			return null;
		}

		// Warn when a lossy type conversion is happening.
		$lossy_types = array(
			'range',
			'oembed',
			'page_link',
			'relationship',
			'button_group',
			'date_time_picker',
			'google_map',
			'group',
			'clone',
		);
		if ( in_array( $acf_type, $lossy_types, true ) ) {
			FieldForge_Settings_Page::debug_log(
				sprintf( 'ACF Importer: field type "%s" (name: %s) converted to "%s".', $acf_type, $acf['name'] ?? '', $ff_type )
			);
		}

		$field = array(
			'key'           => sanitize_key( $acf['key'] ?? 'field_' . uniqid() ),
			'label'         => sanitize_text_field( $acf['label'] ?? '' ),
			'name'          => sanitize_key( $acf['name'] ?? '' ),
			'type'          => $ff_type,
			'instructions'  => wp_kses_post( $acf['instructions'] ?? '' ),
			'required'      => (bool) ( $acf['required'] ?? false ),
			'default_value' => $acf['default_value'] ?? '',
			'placeholder'   => sanitize_text_field( $acf['placeholder'] ?? '' ),
			'wrapper'       => array(
				'width' => sanitize_text_field( $acf['wrapper']['width'] ?? '' ),
				'class' => sanitize_html_class( $acf['wrapper']['class'] ?? '' ),
				'id'    => sanitize_html_class( $acf['wrapper']['id'] ?? '' ),
			),
		);

		// Type-specific properties.
		switch ( $ff_type ) {
			case 'select':
			case 'checkbox':
			case 'radio':
				$field['choices']       = $this->convert_choices( $acf['choices'] ?? array() );
				$field['multiple']      = (bool) ( $acf['multiple'] ?? false );
				$field['allow_null']    = (bool) ( $acf['allow_null'] ?? false );
				$field['return_format'] = sanitize_text_field( $acf['return_format'] ?? 'value' );
				break;

			case 'image':
			case 'file':
				$field['return_format'] = sanitize_text_field( $acf['return_format'] ?? 'array' );
				$field['library']       = sanitize_text_field( $acf['library'] ?? 'all' );
				break;

			case 'gallery':
				$field['return_format'] = sanitize_text_field( $acf['return_format'] ?? 'array' );
				break;

			case 'wysiwyg':
				$field['tabs']         = sanitize_text_field( $acf['tabs'] ?? 'all' );
				$field['toolbar']      = sanitize_text_field( $acf['toolbar'] ?? 'full' );
				$field['media_upload'] = (bool) ( $acf['media_upload'] ?? true );
				break;

			case 'post_object':
				$field['post_type']     = (array) ( $acf['post_type'] ?? array() );
				$field['return_format'] = sanitize_text_field( $acf['return_format'] ?? 'object' );
				$field['multiple']      = (bool) ( $acf['multiple'] ?? false );
				break;

			case 'taxonomy':
				$field['taxonomy']      = sanitize_key( $acf['taxonomy'] ?? 'category' );
				$field['field_type']    = sanitize_text_field( $acf['field_type'] ?? 'checkbox' );
				$field['return_format'] = sanitize_text_field( $acf['return_format'] ?? 'id' );
				break;

			case 'user':
				$field['role']          = sanitize_text_field( $acf['role'] ?? '' );
				$field['return_format'] = sanitize_text_field( $acf['return_format'] ?? 'array' );
				$field['multiple']      = (bool) ( $acf['multiple'] ?? false );
				break;

			case 'repeater':
				$field['sub_fields']   = $this->convert_fields( $acf['sub_fields'] ?? array() );
				$field['min']          = (int) ( $acf['min'] ?? 0 );
				$field['max']          = (int) ( $acf['max'] ?? 0 );
				$field['layout']       = sanitize_text_field( $acf['layout'] ?? 'table' );
				$field['button_label'] = sanitize_text_field( $acf['button_label'] ?? __( 'Add Row', 'fieldforge' ) );
				break;

			case 'number':
				$field['min']  = '' !== ( $acf['min'] ?? '' ) ? (float) $acf['min'] : '';
				$field['max']  = '' !== ( $acf['max'] ?? '' ) ? (float) $acf['max'] : '';
				$field['step'] = '' !== ( $acf['step'] ?? '' ) ? (float) $acf['step'] : '';
				break;

			case 'flexible_content':
				$field['layouts']      = $this->convert_layouts( $acf['layouts'] ?? array() );
				$field['min']          = (int) ( $acf['min'] ?? 0 );
				$field['max']          = (int) ( $acf['max'] ?? 0 );
				$field['button_label'] = sanitize_text_field( $acf['button_label'] ?? __( 'Add Layout', 'fieldforge' ) );
				break;
		}

		// Import conditional logic rules.
		if ( ! empty( $acf['conditional_logic'] ) && is_array( $acf['conditional_logic'] ) ) {
			$has_rules = false;
			$ff_rules  = array();
			foreach ( $acf['conditional_logic'] as $or_group ) {
				foreach ( (array) $or_group as $rule ) {
					if ( ! empty( $rule['field'] ) ) {
						$ff_rules[] = array(
							'field'    => sanitize_key( $rule['field'] ),
							'operator' => sanitize_text_field( $rule['operator'] ?? '==' ),
							'value'    => sanitize_text_field( $rule['value'] ?? '' ),
						);
						$has_rules = true;
					}
				}
			}
			if ( $has_rules ) {
				$field['conditional_logic']       = 1;
				$field['conditional_logic_rules'] = $ff_rules;
			}
		}

		return $field;
	}

	/**
	 * Convert an array of ACF flexible content layout definitions.
	 *
	 * @param array $acf_layouts
	 * @return array
	 */
	private function convert_layouts( array $acf_layouts ): array {
		$layouts = array();
		foreach ( $acf_layouts as $layout ) {
			$name = sanitize_key( $layout['name'] ?? '' );
			if ( ! $name ) {
				continue;
			}
			$layouts[] = array(
				'name'       => $name,
				'label'      => sanitize_text_field( $layout['label'] ?? $name ),
				'sub_fields' => $this->convert_fields( $layout['sub_fields'] ?? array() ),
			);
		}
		return $layouts;
	}

	/**
	 * Convert ACF choices (may be "value : label" strings or key => value pairs).
	 *
	 * @param mixed $choices
	 * @return array<string, string>
	 */
	private function convert_choices( $choices ): array {
		if ( ! is_array( $choices ) ) {
			return array();
		}
		$result = array();
		foreach ( $choices as $k => $v ) {
			$result[ sanitize_text_field( (string) $k ) ] = sanitize_text_field( (string) $v );
		}
		return $result;
	}

	/**
	 * Convert ACF location rules — structure is identical to FieldForge.
	 *
	 * @param array $location
	 * @return array
	 */
	private function convert_location( array $location ): array {
		$result = array();
		foreach ( $location as $or_group ) {
			$rules = array();
			foreach ( (array) $or_group as $rule ) {
				$rules[] = array(
					'param'    => sanitize_key( $rule['param'] ?? '' ),
					'operator' => in_array( $rule['operator'] ?? '', array( '==', '!=' ), true ) ? $rule['operator'] : '==',
					'value'    => sanitize_text_field( $rule['value'] ?? '' ),
				);
			}
			if ( $rules ) {
				$result[] = $rules;
			}
		}
		return $result;
	}

	/**
	 * AJAX handler for the import form in the admin.
	 */
	public function ajax_import(): void {
		check_ajax_referer( 'fieldforge_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'fieldforge' ) ), 403 );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$json = isset( $_POST['json'] ) ? wp_unslash( $_POST['json'] ) : '';

		$result = $this->import( $json );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d number of groups imported */
					_n( '%d field group imported.', '%d field groups imported.', count( $result ), 'fieldforge' ),
					count( $result )
				),
				'ids'     => $result,
			)
		);
	}
}
