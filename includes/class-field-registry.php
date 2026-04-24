<?php
/**
 * Field type registry.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps track of all registered field type classes keyed by type slug.
 */
class FieldForge_Field_Registry {

	/** @var array<string, string> type_slug => class_name */
	private array $types = array();

	public function register( string $type, string $class_name ): void {
		$this->types[ $type ] = $class_name;
	}

	public function get_class( string $type ): ?string {
		return $this->types[ $type ] ?? null;
	}

	/** @return array<string, string> */
	public function get_all_types(): array {
		return $this->types;
	}

	public function make_field( array $field_config ): ?FieldForge_Field_Base {
		$type  = $field_config['type'] ?? '';
		$class = $this->get_class( $type );
		if ( ! $class || ! class_exists( $class ) ) {
			return null;
		}
		return new $class( $field_config );
	}

	public function register_core_fields(): void {
		$core = array(
			'text'             => 'FieldForge_Field_Text',
			'textarea'         => 'FieldForge_Field_Textarea',
			'number'           => 'FieldForge_Field_Number',
			'select'           => 'FieldForge_Field_Select',
			'checkbox'         => 'FieldForge_Field_Checkbox',
			'radio'            => 'FieldForge_Field_Radio',
			'true_false'       => 'FieldForge_Field_True_False',
			'date_picker'      => 'FieldForge_Field_Date',
			'time_picker'      => 'FieldForge_Field_Time',
			'color_picker'     => 'FieldForge_Field_Color',
			'url'              => 'FieldForge_Field_Url',
			'email'            => 'FieldForge_Field_Email',
			'password'         => 'FieldForge_Field_Password',
			'file'             => 'FieldForge_Field_File',
			'image'            => 'FieldForge_Field_Image',
			'gallery'          => 'FieldForge_Field_Gallery',
			'post_object'      => 'FieldForge_Field_Post_Object',
			'taxonomy'         => 'FieldForge_Field_Taxonomy',
			'user'             => 'FieldForge_Field_User',
			'link'             => 'FieldForge_Field_Link',
			'wysiwyg'          => 'FieldForge_Field_Wysiwyg',
			'message'          => 'FieldForge_Field_Message',
			'tab'              => 'FieldForge_Field_Tab',
			'accordion'        => 'FieldForge_Field_Accordion',
			'repeater'         => 'FieldForge_Field_Repeater',
			'flexible_content' => 'FieldForge_Field_Flexible_Content',
		);

		foreach ( $core as $type => $class ) {
			$this->register( $type, $class );
		}

		do_action( 'fieldforge_register_fields', $this );
	}
}
