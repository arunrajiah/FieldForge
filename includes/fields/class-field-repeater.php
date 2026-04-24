<?php
/**
 * Repeater field type.
 *
 * Data format (ACF-compatible):
 *   {name}            => (int) number of rows
 *   {name}_{i}_{sub}  => value for row i, sub-field sub
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Repeater extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$rows         = $this->load( $post_id );
		$sub_fields   = $this->field['sub_fields'] ?? array();
		$name         = esc_attr( $this->field['name'] );
		$button_label = esc_html( $this->field['button_label'] ?? __( 'Add Row', 'fieldforge' ) );
		$min          = (int) ( $this->field['min'] ?? 0 );
		$max          = (int) ( $this->field['max'] ?? 0 );
		$layout       = in_array( $this->field['layout'] ?? 'table', array( 'table', 'block', 'row' ), true ) ? $this->field['layout'] : 'table';

		$ff       = FieldForge::get_instance();
		$registry = $ff->registry;

		$html  = '<div class="fieldforge-repeater" ';
		$html .= 'data-name="' . $name . '" ';
		$html .= 'data-min="' . $min . '" ';
		$html .= 'data-max="' . $max . '" ';
		$html .= 'data-layout="' . esc_attr( $layout ) . '">';

		// Table layout header.
		if ( 'table' === $layout && ! empty( $sub_fields ) ) {
			$html .= '<div class="fieldforge-repeater-header">';
			foreach ( $sub_fields as $sub ) {
				$html .= '<div class="fieldforge-repeater-header-cell">' . esc_html( $sub['label'] ?? '' ) . '</div>';
			}
			$html .= '<div class="fieldforge-repeater-header-cell fieldforge-repeater-order">' . esc_html__( 'Order', 'fieldforge' ) . '</div>';
			$html .= '</div>';
		}

		$html .= '<div class="fieldforge-repeater-rows">';

		if ( ! empty( $rows ) ) {
			foreach ( $rows as $row_index => $row_data ) {
				$html .= $this->render_row( (int) $row_index, $row_data, $sub_fields, $registry, $name, $layout );
			}
		} else {
			$html .= '<p class="fieldforge-repeater-empty">' . esc_html__( 'No rows yet.', 'fieldforge' ) . '</p>';
		}

		$html .= '</div>'; // .fieldforge-repeater-rows

		$html .= '<button type="button" class="button fieldforge-repeater-add-row">' . $button_label . '</button>';

		// JSON template for JS to clone new rows.
		$template_row = array();
		foreach ( $sub_fields as $sub ) {
			$template_row[ $sub['name'] ] = '';
		}
		$html .= '<script type="application/json" class="fieldforge-repeater-template">' . wp_json_encode( $sub_fields ) . '</script>';

		$html .= '</div>'; // .fieldforge-repeater

		$this->render_wrapper( $html );
	}

	/**
	 * Render a single repeater row.
	 *
	 * @param int                      $row_index
	 * @param array                    $row_data
	 * @param array                    $sub_fields
	 * @param FieldForge_Field_Registry $registry
	 * @param string                   $name      Parent field name (escaped).
	 * @param string                   $layout
	 * @return string
	 */
	private function render_row( int $row_index, array $row_data, array $sub_fields, FieldForge_Field_Registry $registry, string $name, string $layout ): string {
		$html  = '<div class="fieldforge-repeater-row" data-row="' . esc_attr( (string) $row_index ) . '">';
		$html .= '<div class="fieldforge-repeater-row-header">';
		$html .= '<span class="fieldforge-repeater-drag dashicons dashicons-menu" title="' . esc_attr__( 'Drag to reorder', 'fieldforge' ) . '"></span>';
		/* translators: %d: row number */
		$html .= '<span class="fieldforge-repeater-row-label">' . sprintf( esc_html__( 'Row %d', 'fieldforge' ), $row_index + 1 ) . '</span>';
		$html .= '<div class="fieldforge-repeater-row-actions">';
		$toggle_title = esc_attr__( 'Collapse row', 'fieldforge' );
		$html        .= '<button type="button" class="fieldforge-repeater-row-toggle button-link" title="' . $toggle_title . '">'
			. '<span class="dashicons dashicons-arrow-up-alt2"></span></button>';
		$html .= '<button type="button" class="button fieldforge-repeater-remove-row">' . esc_html__( 'Remove', 'fieldforge' ) . '</button>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '<div class="fieldforge-repeater-row-body">';
		$html .= '<div class="fieldforge-repeater-row-cells">';

		foreach ( $sub_fields as $sub_config ) {
			$sub_name   = $sub_config['name'] ?? '';
			$namespaced = array_merge(
				$sub_config,
				array(
					'name' => $name . '_' . $row_index . '_' . $sub_name,
					'key'  => $sub_config['key'] ?? '',
				)
			);
			$sub_field  = $registry->make_field( $namespaced );
			if ( ! $sub_field ) {
				continue;
			}

			$sub_value = $row_data[ $sub_name ] ?? '';

			// Temporarily store the value so load() can pick it up via postmeta trick.
			// We override the value directly in a render-aware way.
			$html .= '<div class="fieldforge-repeater-cell">';
			// Render sub-field with pre-populated value.
			ob_start();
			$sub_field->render_with_value( $sub_value );
			$cell  = ob_get_clean();
			$html .= $cell;
			$html .= '</div>';
		}

		$html .= '</div>'; // .fieldforge-repeater-row-cells
		$html .= '</div>'; // .fieldforge-repeater-row-body
		$html .= '</div>'; // .fieldforge-repeater-row

		return $html;
	}

	public function sanitize( $value ): array {
		// Rows are extracted from $_POST by save() — this receives the row count.
		return is_array( $value ) ? $value : array();
	}

	/**
	 * Override save to handle the multi-key ACF-compatible storage pattern.
	 *
	 * @param int   $post_id
	 * @param mixed $value  Not used directly; raw data comes from $_POST.
	 */
	public function save( int $post_id, $value ): void {
		$name       = $this->field['name'];
		$sub_fields = $this->field['sub_fields'] ?? array();
		$registry   = FieldForge::get_instance()->registry;

		// Count how many rows were submitted.
		$row_count = 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		foreach ( array_keys( $_POST ) as $key ) {
			if ( preg_match( '/^' . preg_quote( $name, '/' ) . '_(\d+)_/', $key, $m ) ) {
				$row_count = max( $row_count, (int) $m[1] + 1 );
			}
		}

		// Store row count (ACF format).
		update_post_meta( $post_id, $name, $row_count );
		update_post_meta( $post_id, '_' . $name, $this->field['key'] ?? '' );

		// Remove any stale rows beyond current count.
		$existing = (int) get_post_meta( $post_id, $name, true );
		for ( $i = $row_count; $i < $existing; $i++ ) {
			foreach ( $sub_fields as $sub ) {
				delete_post_meta( $post_id, $name . '_' . $i . '_' . $sub['name'] );
				delete_post_meta( $post_id, '_' . $name . '_' . $i . '_' . $sub['name'] );
			}
		}

		// Save each row's sub-fields.
		for ( $i = 0; $i < $row_count; $i++ ) {
			foreach ( $sub_fields as $sub_config ) {
				$sub_name  = $sub_config['name'] ?? '';
				$meta_key  = $name . '_' . $i . '_' . $sub_name;
				$sub_field = $registry->make_field( array_merge( $sub_config, array( 'name' => $meta_key ) ) );
				if ( ! $sub_field ) {
					continue;
				}
				// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified above; value passed through field sanitize().
				$raw   = isset( $_POST[ $meta_key ] ) ? wp_unslash( $_POST[ $meta_key ] ) : $sub_field->get_empty_value();
				$clean = $sub_field->sanitize( $raw );
				update_post_meta( $post_id, $meta_key, $clean );
				update_post_meta( $post_id, '_' . $meta_key, $sub_config['key'] ?? '' );
			}
		}
	}

	/**
	 * Load all rows as a structured array.
	 *
	 * @param int $post_id
	 * @return array[]
	 */
	public function load( int $post_id ) {
		$name       = $this->field['name'];
		$row_count  = (int) get_post_meta( $post_id, $name, true );
		$sub_fields = $this->field['sub_fields'] ?? array();
		$rows       = array();

		for ( $i = 0; $i < $row_count; $i++ ) {
			$row = array();
			foreach ( $sub_fields as $sub ) {
				$sub_name         = $sub['name'] ?? '';
				$row[ $sub_name ] = get_post_meta( $post_id, $name . '_' . $i . '_' . $sub_name, true );
			}
			$rows[] = $row;
		}
		return $rows;
	}

	public function get_empty_value(): array {
		return array();
	}

	/**
	 * Validate min/max row count constraints.
	 *
	 * @param mixed $value  The sanitized value (array of rows or row count).
	 * @return true|string
	 */
	public function validate( $value ) {
		$parent = parent::validate( $value );
		if ( true !== $parent ) {
			return $parent;
		}

		// Count submitted rows from $_POST (same logic as save()).
		$name      = $this->field['name'];
		$row_count = 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		foreach ( array_keys( $_POST ) as $key ) {
			if ( preg_match( '/^' . preg_quote( $name, '/' ) . '_(\d+)_/', $key, $m ) ) {
				$row_count = max( $row_count, (int) $m[1] + 1 );
			}
		}

		$min = (int) ( $this->field['min'] ?? 0 );
		$max = (int) ( $this->field['max'] ?? 0 );

		if ( $min > 0 && $row_count < $min ) {
			return sprintf(
				/* translators: 1: field label, 2: minimum row count */
				_n(
					'"%1$s" requires at least %2$d row.',
					'"%1$s" requires at least %2$d rows.',
					$min,
					'fieldforge'
				),
				$this->field['label'] ?? $this->field['name'],
				$min
			);
		}

		if ( $max > 0 && $row_count > $max ) {
			return sprintf(
				/* translators: 1: field label, 2: maximum row count */
				_n(
					'"%1$s" may have at most %2$d row.',
					'"%1$s" may have at most %2$d rows.',
					$max,
					'fieldforge'
				),
				$this->field['label'] ?? $this->field['name'],
				$max
			);
		}

		return true;
	}

	/**
	 * Format all sub-field values in each row using the sub-field's own format_value().
	 *
	 * @param mixed $value   Raw rows array from load().
	 * @param int   $post_id
	 * @return array
	 */
	public function format_value( $value, int $post_id ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}
		$sub_fields = $this->field['sub_fields'] ?? array();
		$registry   = FieldForge::get_instance()->registry;
		$rows       = array();

		foreach ( $value as $row ) {
			$formatted = array();
			foreach ( $sub_fields as $sub_config ) {
				$sub_name  = $sub_config['name'] ?? '';
				$raw       = $row[ $sub_name ] ?? null;
				$sub_field = $registry->make_field( $sub_config );
				$formatted[ $sub_name ] = $sub_field ? $sub_field->format_value( $raw, $post_id ) : $raw;
			}
			$rows[] = $formatted;
		}
		return $rows;
	}
}
