<?php
/**
 * Flexible Content field type.
 *
 * Storage format (ACF-compatible):
 *   {name}                        => (int) number of layout rows
 *   {name}_{i}_acf_fc_layout      => layout name for row i
 *   {name}_{i}_{sub_field_name}   => sub-field value for row i
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Flexible_Content extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$name         = $this->field['name'];
		$layouts      = $this->field['layouts'] ?? array();
		$button_label = esc_html( $this->field['button_label'] ?? __( 'Add Layout', 'fieldforge' ) );
		$min          = (int) ( $this->field['min'] ?? 0 );
		$max          = (int) ( $this->field['max'] ?? 0 );
		$rows         = $this->load( $post_id );

		$registry = FieldForge::get_instance()->registry;

		$html  = '<div class="fieldforge-flexible-content" ';
		$html .= 'data-name="' . esc_attr( $name ) . '" ';
		$html .= 'data-min="' . esc_attr( (string) $min ) . '" ';
		$html .= 'data-max="' . esc_attr( (string) $max ) . '">';

		// Existing rows.
		$html .= '<div class="fieldforge-fc-rows">';
		if ( ! empty( $rows ) ) {
			foreach ( $rows as $i => $row ) {
				$layout_name = $row['acf_fc_layout'] ?? '';
				$layout      = $this->get_layout( $layout_name );
				if ( ! $layout ) {
					continue;
				}
				$html .= $this->render_row( (int) $i, $row, $layout, $registry, $name );
			}
		} else {
			$html .= '<p class="fieldforge-fc-empty">' . esc_html__( 'No layouts added yet.', 'fieldforge' ) . '</p>';
		}
		$html .= '</div>'; // .fieldforge-fc-rows

		// "Add Layout" button + dropdown.
		if ( ! empty( $layouts ) ) {
			$html .= '<div class="fieldforge-fc-add">';
			$html .= '<button type="button" class="button fieldforge-fc-add-btn">' . $button_label . '</button>';
			$html .= '<ul class="fieldforge-fc-layout-picker" style="display:none">';
			foreach ( $layouts as $layout ) {
				$html .= '<li><button type="button" class="fieldforge-fc-pick-layout" data-layout="' . esc_attr( $layout['name'] ) . '">' . esc_html( $layout['label'] ) . '</button></li>';
			}
			$html .= '</ul>';
			$html .= '</div>';
		}

		// JSON template for each layout (used by JS to build new rows).
		$html .= '<script type="application/json" class="fieldforge-fc-layouts-template">' . wp_json_encode( $layouts ) . '</script>';

		$html .= '</div>'; // .fieldforge-flexible-content

		$this->render_wrapper( $html );
	}

	/**
	 * Render a single layout row.
	 */
	private function render_row( int $i, array $row, array $layout, FieldForge_Field_Registry $registry, string $name ): string {
		$layout_name = $layout['name'];

		$html  = '<div class="fieldforge-fc-row" data-row="' . esc_attr( (string) $i ) . '" data-layout="' . esc_attr( $layout_name ) . '">';
		$html .= '<div class="fieldforge-fc-row-header">';
		$html .= '<span class="dashicons dashicons-menu fieldforge-fc-drag"></span>';
		$html .= '<strong class="fieldforge-fc-layout-label">' . esc_html( $layout['label'] ) . '</strong>';
		$html .= '<button type="button" class="button button-link fieldforge-fc-toggle">' . esc_html__( 'Collapse', 'fieldforge' ) . '</button>';
		$html .= '<button type="button" class="button button-link-delete fieldforge-fc-remove">' . esc_html__( 'Remove', 'fieldforge' ) . '</button>';
		$html .= '</div>'; // .fieldforge-fc-row-header

		// Hidden field: stores which layout this row uses.
		$html .= '<input type="hidden" name="' . esc_attr( $name . '_' . $i . '_acf_fc_layout' ) . '" value="' . esc_attr( $layout_name ) . '" />';

		$html .= '<div class="fieldforge-fc-row-body">';
		foreach ( $layout['sub_fields'] ?? array() as $sub_config ) {
			$sub_name    = $sub_config['name'] ?? '';
			$namespaced  = array_merge( $sub_config, array( 'name' => $name . '_' . $i . '_' . $sub_name ) );
			$sub_field   = $registry->make_field( $namespaced );
			if ( ! $sub_field ) {
				continue;
			}
			$sub_value = $row[ $sub_name ] ?? '';
			ob_start();
			$sub_field->render_with_value( $sub_value );
			$html .= ob_get_clean();
		}
		$html .= '</div>'; // .fieldforge-fc-row-body

		$html .= '</div>'; // .fieldforge-fc-row
		return $html;
	}

	/**
	 * Find a layout definition by name.
	 */
	private function get_layout( string $name ): ?array {
		foreach ( $this->field['layouts'] ?? array() as $layout ) {
			if ( ( $layout['name'] ?? '' ) === $name ) {
				return $layout;
			}
		}
		return null;
	}

	public function sanitize( $value ): array {
		return is_array( $value ) ? $value : array();
	}

	/**
	 * Override save: writes ACF-compatible multi-key storage.
	 */
	public function save( int $post_id, $value ): void {
		$name     = $this->field['name'];
		$layouts  = $this->field['layouts'] ?? array();
		$registry = FieldForge::get_instance()->registry;

		// Count submitted rows.
		$row_count = 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		foreach ( array_keys( $_POST ) as $key ) {
			if ( preg_match( '/^' . preg_quote( $name, '/' ) . '_(\d+)_acf_fc_layout$/', $key, $m ) ) {
				$row_count = max( $row_count, (int) $m[1] + 1 );
			}
		}

		// Store row count and field key reference.
		update_post_meta( $post_id, $name, $row_count );
		update_post_meta( $post_id, '_' . $name, $this->field['key'] ?? '' );

		// Clean up stale rows.
		$old_count = (int) get_post_meta( $post_id, $name . '_old_count', true );
		for ( $i = $row_count; $i < $old_count; $i++ ) {
			delete_post_meta( $post_id, $name . '_' . $i . '_acf_fc_layout' );
			foreach ( $layouts as $layout ) {
				foreach ( $layout['sub_fields'] ?? array() as $sub ) {
					delete_post_meta( $post_id, $name . '_' . $i . '_' . ( $sub['name'] ?? '' ) );
				}
			}
		}

		// Save each row.
		for ( $i = 0; $i < $row_count; $i++ ) {
			$layout_key = $name . '_' . $i . '_acf_fc_layout';
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$layout_name = isset( $_POST[ $layout_key ] ) ? sanitize_key( wp_unslash( $_POST[ $layout_key ] ) ) : '';
			update_post_meta( $post_id, $layout_key, $layout_name );

			$layout = $this->get_layout( $layout_name );
			if ( ! $layout ) {
				continue;
			}

			foreach ( $layout['sub_fields'] ?? array() as $sub_config ) {
				$sub_name  = $sub_config['name'] ?? '';
				$meta_key  = $name . '_' . $i . '_' . $sub_name;
				$sub_field = $registry->make_field( array_merge( $sub_config, array( 'name' => $meta_key ) ) );
				if ( ! $sub_field ) {
					continue;
				}
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$raw   = isset( $_POST[ $meta_key ] ) ? wp_unslash( $_POST[ $meta_key ] ) : $sub_field->get_empty_value();
				$clean = $sub_field->sanitize( $raw );
				update_post_meta( $post_id, $meta_key, $clean );
				update_post_meta( $post_id, '_' . $meta_key, $sub_config['key'] ?? '' );
			}
		}

		update_post_meta( $post_id, $name . '_old_count', $row_count );
	}

	/**
	 * Load all rows as [ ['acf_fc_layout' => 'layout_name', 'field' => value, ...], ... ]
	 */
	public function load( int $post_id ): array {
		$name      = $this->field['name'];
		$row_count = (int) get_post_meta( $post_id, $name, true );
		$layouts   = $this->field['layouts'] ?? array();
		$rows      = array();

		for ( $i = 0; $i < $row_count; $i++ ) {
			$layout_name = get_post_meta( $post_id, $name . '_' . $i . '_acf_fc_layout', true );
			$layout      = $this->get_layout( $layout_name );
			$row         = array( 'acf_fc_layout' => $layout_name );

			if ( $layout ) {
				foreach ( $layout['sub_fields'] ?? array() as $sub ) {
					$sub_name       = $sub['name'] ?? '';
					$row[ $sub_name ] = get_post_meta( $post_id, $name . '_' . $i . '_' . $sub_name, true );
				}
			}
			$rows[] = $row;
		}
		return $rows;
	}

	public function get_empty_value(): array {
		return array();
	}
}
