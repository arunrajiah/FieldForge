<?php
/**
 * Public template helper functions.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get a field value for the given post (defaults to the current post in the loop).
 *
 * Pass 'option' or 'options' as $post_id to read from an options page instead of postmeta.
 *
 * @param string         $field_name
 * @param int|string|null $post_id   Post ID, 'option'/'options' for global options, or null for current post.
 * @return mixed
 */
function fieldforge_get( string $field_name, $post_id = null ) {
	// Options page context.
	if ( in_array( $post_id, array( 'option', 'options' ), true ) ) {
		$field_config = fieldforge_find_field_config( $field_name, 0 );
		$ff           = FieldForge::get_instance();
		$field        = $field_config ? $ff->registry->make_field( $field_config ) : null;
		$raw          = FieldForge_Options_Page::get_option( 'option', $field_name );
		return $field ? $field->format_value( $raw, 0 ) : $raw;
	}

	if ( null === $post_id ) {
		$post_id = (int) get_the_ID();
	}
	$post_id = (int) $post_id;

	$field_config = fieldforge_find_field_config( $field_name, $post_id );
	if ( ! $field_config ) {
		return get_post_meta( $post_id, $field_name, true );
	}

	$ff    = FieldForge::get_instance();
	$field = $ff->registry->make_field( $field_config );
	if ( ! $field ) {
		return get_post_meta( $post_id, $field_name, true );
	}

	$raw = $field->load( $post_id );
	return $field->format_value( $raw, $post_id );
}

/**
 * Echo a field value, escaping it as plain text.
 *
 * @param string   $field_name
 * @param int|null $post_id
 */
function fieldforge_the( string $field_name, $post_id = null ): void {
	$value        = fieldforge_get( $field_name, $post_id );
	$field_config = fieldforge_find_field_config( $field_name, (int) ( $post_id ?? get_the_ID() ) );
	$type         = $field_config['type'] ?? 'text';

	if ( in_array( $type, array( 'wysiwyg', 'message' ), true ) ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already processed through wp_kses_post on save/format
		echo wp_kses_post( (string) $value );
	} elseif ( 'link' === $type ) {
		if ( is_array( $value ) ) {
			printf(
				'<a href="%s"%s>%s</a>',
				esc_url( $value['url'] ?? '' ),
				! empty( $value['target'] ) ? ' target="' . esc_attr( $value['target'] ) . '"' : '',
				esc_html( $value['title'] ?? $value['url'] ?? '' )
			);
		} else {
			echo esc_url( (string) $value );
		}
	} else {
		echo esc_html( (string) $value );
	}
}

// ---------------------------------------------------------------------------
// Repeater loop helpers
// ---------------------------------------------------------------------------

// @var array Stack of current repeater row data. */
global $fieldforge_repeater_row;
$fieldforge_repeater_row = array();

/**
 * Check whether there are more repeater rows and advance the pointer.
 *
 * Usage: while ( fieldforge_have_rows('gallery_items') ) { ... }
 *
 * @param string   $field_name
 * @param int|null $post_id
 * @return bool
 */
function fieldforge_have_rows( string $field_name, ?int $post_id = null ): bool {
	global $fieldforge_repeater_row;

	if ( null === $post_id ) {
		$post_id = (int) get_the_ID();
	}

	$cache_key = $field_name . '_' . $post_id;

	if ( ! isset( $fieldforge_repeater_row[ $cache_key ] ) ) {
		$rows                                  = fieldforge_get( $field_name, $post_id );
		$fieldforge_repeater_row[ $cache_key ] = array(
			'rows'  => is_array( $rows ) ? array_values( $rows ) : array(),
			'index' => -1,
		);
	}

	$next = $fieldforge_repeater_row[ $cache_key ]['index'] + 1;

	if ( $next < count( $fieldforge_repeater_row[ $cache_key ]['rows'] ) ) {
		$fieldforge_repeater_row[ $cache_key ]['index'] = $next;
		return true;
	}

	// Reset for re-use.
	unset( $fieldforge_repeater_row[ $cache_key ] );
	return false;
}

/**
 * Advance to the next repeater row (no-op — have_rows() already advances the pointer).
 * Required for template loop syntax: while ( have_rows() ) : the_row(); ...
 */
function fieldforge_the_row(): void {}

/**
 * Get the layout name for the current Flexible Content row.
 * Use inside a fieldforge_have_rows() loop on a flexible_content field.
 *
 * @return string Layout name, or empty string if not in a flexible content loop.
 */
function fieldforge_get_row_layout(): string {
	global $fieldforge_repeater_row;

	foreach ( $fieldforge_repeater_row as $data ) {
		$row = $data['rows'][ $data['index'] ] ?? array();
		if ( isset( $row['acf_fc_layout'] ) ) {
			return (string) $row['acf_fc_layout'];
		}
	}
	return '';
}

/**
 * Get the value of a sub-field within the current repeater row.
 *
 * @param string $field_name
 * @return mixed
 */
function fieldforge_sub_field( string $field_name ) {
	global $fieldforge_repeater_row;

	foreach ( $fieldforge_repeater_row as $cache_key => $data ) {
		$row = $data['rows'][ $data['index'] ] ?? array();
		if ( isset( $row[ $field_name ] ) ) {
			return $row[ $field_name ];
		}
	}
	return null;
}

/**
 * Echo a sub-field value, escaped as plain text.
 *
 * @param string $field_name
 */
function fieldforge_the_sub_field( string $field_name ): void {
	echo esc_html( (string) fieldforge_sub_field( $field_name ) );
}

// ---------------------------------------------------------------------------
// Internal helpers
// ---------------------------------------------------------------------------

/**
 * Find the field config array for a given field name in any active group.
 *
 * @param string $field_name
 * @param int    $post_id
 * @return array|null
 */
function fieldforge_find_field_config( string $field_name, int $post_id ): ?array {
	$ff     = FieldForge::get_instance();
	$groups = $ff->field_group->get_all_groups();

	foreach ( $groups as $group ) {
		foreach ( $group['fields'] as $field_config ) {
			if ( ( $field_config['name'] ?? '' ) === $field_name ) {
				return $field_config;
			}
			// Check repeater sub-fields.
			if ( 'repeater' === ( $field_config['type'] ?? '' ) && ! empty( $field_config['sub_fields'] ) ) {
				foreach ( $field_config['sub_fields'] as $sub ) {
					if ( ( $sub['name'] ?? '' ) === $field_name ) {
						return $sub;
					}
				}
			}
			// Check flexible_content layout sub-fields.
			if ( 'flexible_content' === ( $field_config['type'] ?? '' ) && ! empty( $field_config['layouts'] ) ) {
				foreach ( $field_config['layouts'] as $layout ) {
					foreach ( $layout['sub_fields'] ?? array() as $sub ) {
						if ( ( $sub['name'] ?? '' ) === $field_name ) {
							return $sub;
						}
					}
				}
			}
		}
	}
	return null;
}

/**
 * Programmatically update a field value for a post.
 *
 * @param string $field_name  Field name (slug).
 * @param mixed  $value       The new value.
 * @param int    $post_id     Post ID. Defaults to current post.
 * @return bool True on success, false on failure.
 */
function fieldforge_update_field( string $field_name, $value, int $post_id = 0 ): bool {
	if ( ! $post_id ) {
		$post_id = (int) get_the_ID();
	}
	if ( ! $post_id ) {
		return false;
	}

	$field_config = fieldforge_find_field_config( $field_name, $post_id );
	$ff           = FieldForge::get_instance();
	$field        = $field_config ? $ff->registry->make_field( $field_config ) : null;

	if ( $field ) {
		$clean = $field->sanitize( $value );
		$field->save( $post_id, $clean );
	} else {
		update_post_meta( $post_id, $field_name, $value );
	}
	return true;
}

/**
 * Programmatically update a field value on an options page.
 *
 * @param string $field_name  Field name (slug).
 * @param mixed  $value       The new value.
 * @param string $page_slug   Options page slug (defaults to 'option').
 * @return bool
 */
function fieldforge_update_option( string $field_name, $value, string $page_slug = 'option' ): bool {
	$field_config = fieldforge_find_field_config( $field_name, 0 );
	$ff           = FieldForge::get_instance();
	$field        = $field_config ? $ff->registry->make_field( $field_config ) : null;
	$clean        = $field ? $field->sanitize( $value ) : $value;

	FieldForge_Options_Page::update_option( $page_slug, $field_name, $clean );
	return true;
}

/**
 * Get a field value from an options page.
 *
 * @param string $field_name  Field name (slug).
 * @param string $page_slug   Options page slug (defaults to 'option').
 * @return mixed
 */
function fieldforge_get_option( string $field_name, string $page_slug = 'option' ) {
	$field_config = fieldforge_find_field_config( $field_name, 0 );
	$ff           = FieldForge::get_instance();
	$field        = $field_config ? $ff->registry->make_field( $field_config ) : null;
	$raw          = FieldForge_Options_Page::get_option( $page_slug, $field_name );
	return $field ? $field->format_value( $raw, 0 ) : $raw;
}
