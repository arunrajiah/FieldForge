<?php
/**
 * Abstract base class for all FieldForge field types.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class FieldForge_Field_Base {

	/** @var array Field configuration array. */
	protected array $field;

	public function __construct( array $field ) {
		$this->field = $field;
	}

	// ------------------------------------------------------------------
	// Abstract interface
	// ------------------------------------------------------------------

	/**
	 * Render the field HTML inside a meta box.
	 *
	 * @param int $post_id Current post ID.
	 */
	abstract public function render( int $post_id ): void;

	/**
	 * Sanitize a raw $_POST value before saving.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	abstract public function sanitize( $value );

	// ------------------------------------------------------------------
	// Common read / write helpers
	// ------------------------------------------------------------------

	/**
	 * Load the stored value for the given post, ready to display.
	 *
	 * Override in subclasses that need special formatting (e.g. images).
	 *
	 * @param int $post_id
	 * @return mixed
	 */
	public function load( int $post_id ) {
		if ( null !== $this->prefilled_value ) {
			return $this->prefilled_value;
		}
		return get_post_meta( $post_id, $this->field['name'], true );
	}

	/**
	 * Persist the sanitized value to postmeta.
	 * Stores an ACF-compatible `_field_name` key reference alongside the value.
	 *
	 * @param int   $post_id
	 * @param mixed $value   Already sanitized.
	 */
	public function save( int $post_id, $value ): void {
		$name = $this->field['name'];
		update_post_meta( $post_id, $name, $value );
		update_post_meta( $post_id, '_' . $name, $this->field['key'] ?? '' );
	}

	/**
	 * Return the appropriate "empty" value for this field type.
	 * Subclasses may override (e.g. checkboxes return []).
	 *
	 * @return mixed
	 */
	public function get_empty_value() {
		return '';
	}

	/**
	 * Format a raw stored value for output (e.g. return_format for image/post fields).
	 * Base implementation returns the value as-is; relational/media subclasses override.
	 *
	 * @param mixed $value   Raw value from load().
	 * @param int   $post_id Source post ID (0 for options context).
	 * @return mixed
	 */
	public function format_value( $value, int $post_id ) {
		return $value;
	}

	// ------------------------------------------------------------------
	// Shared render helpers
	// ------------------------------------------------------------------

	/**
	 * Render the wrapper div and label that surround every field.
	 *
	 * @param string $inner_html Already-escaped HTML for the control.
	 */
	protected function render_wrapper( string $inner_html ): void {
		$required    = ! empty( $this->field['required'] ) ? ' fieldforge-required' : '';
		$width       = ! empty( $this->field['wrapper']['width'] ) ? ' style="width:' . esc_attr( $this->field['wrapper']['width'] ) . '%"' : '';
		$extra_class = ! empty( $this->field['wrapper']['class'] ) ? ' ' . esc_attr( $this->field['wrapper']['class'] ) : '';
		$extra_id    = ! empty( $this->field['wrapper']['id'] ) ? ' id="' . esc_attr( $this->field['wrapper']['id'] ) . '"' : '';
		$type        = $this->field['type'] ?? 'text';

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- $extra_id and $width are pre-escaped above.
		printf(
			'<div class="fieldforge-field fieldforge-field--%s%s%s"%s%s>',
			esc_attr( $type ),
			esc_attr( $required ),
			esc_attr( $extra_class ),
			$extra_id,
			$width
		);

		printf(
			'<label class="fieldforge-label">%s%s</label>',
			esc_html( $this->field['label'] ?? '' ),
			! empty( $this->field['required'] ) ? ' <span class="fieldforge-required-indicator">*</span>' : ''
		);
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( ! empty( $this->field['instructions'] ) ) {
			printf( '<p class="fieldforge-instructions">%s</p>', wp_kses_post( $this->field['instructions'] ) );
		}

		echo '<div class="fieldforge-field-control">';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $inner_html is constructed internally and pre-escaped.
		echo $inner_html;
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Helper: build the HTML id and name attributes for an input.
	 *
	 * @param string $suffix Optional suffix (e.g. for radio options).
	 * @return string
	 */
	protected function input_attrs( string $suffix = '' ): string {
		$name = esc_attr( $this->field['name'] );
		$id   = esc_attr( 'fieldforge_field_' . $this->field['name'] . $suffix );
		return 'name="' . $name . '" id="' . $id . '"';
	}

	public function get_field(): array {
		return $this->field;
	}

	/**
	 * Render the field pre-populated with a given value instead of a DB lookup.
	 * Used by the Repeater to render sub-fields within a row.
	 *
	 * @param mixed $value
	 */
	public function render_with_value( $value ): void {
		// Store the value in a transient instance property so load() returns it.
		$this->prefilled_value = $value;
		$this->render( 0 ); // post_id 0 — load() will return $prefilled_value.
		unset( $this->prefilled_value );
	}

	/** @var mixed|null Prefilled value set by render_with_value(). */
	protected $prefilled_value = null;
}
