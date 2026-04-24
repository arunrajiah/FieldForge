<?php
/**
 * Taxonomy field type.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Taxonomy extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$saved      = $this->load( $post_id );
		$taxonomy   = sanitize_key( $this->field['taxonomy'] ?? 'category' );
		$field_type = $this->field['field_type'] ?? 'checkbox'; // checkbox | radio | select | multi_select.
		$name       = esc_attr( $this->field['name'] );
		$id         = esc_attr( 'fieldforge_field_' . $this->field['name'] );

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			$terms = array();
		}

		$html = '';
		if ( in_array( $field_type, array( 'select', 'multi_select' ), true ) ) {
			$multiple = 'multi_select' === $field_type;
			$html    .= '<select name="' . $name . ( $multiple ? '[]' : '' ) . '" id="' . $id . '" class="fieldforge-select widefat"' . ( $multiple ? ' multiple size="6"' : '' ) . '>';
			$html    .= '<option value="">' . esc_html__( '— Select —', 'fieldforge' ) . '</option>';
			foreach ( $terms as $term ) {
				$sel   = $multiple
					? ( in_array( (int) $term->term_id, (array) $saved, true ) ? ' selected' : '' )
					: selected( $saved, $term->term_id, false );
				$html .= '<option value="' . esc_attr( $term->term_id ) . '"' . $sel . '>' . esc_html( $term->name ) . '</option>';
			}
			$html .= '</select>';
		} else {
			$input_type = 'radio' === $field_type ? 'radio' : 'checkbox';
			$is_multi   = 'checkbox' === $input_type;
			$html      .= '<ul class="fieldforge-' . esc_attr( $input_type ) . '-list">';
			foreach ( $terms as $term ) {
				$checked = $is_multi
					? ( in_array( (int) $term->term_id, (array) $saved, true ) ? ' checked' : '' )
					: checked( $saved, $term->term_id, false );
				$n       = $is_multi ? $name . '[]' : $name;
				$html   .= '<li><label><input type="' . $input_type . '" name="' . $n . '" value="' . esc_attr( $term->term_id ) . '"' . $checked . ' /> ' . esc_html( $term->name ) . '</label></li>';
			}
			$html .= '</ul>';
		}

		$this->render_wrapper( $html );
	}

	public function sanitize( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'absint', $value );
		}
		return absint( $value );
	}

	public function validate( $value ) {
		$parent = parent::validate( $value );
		if ( true !== $parent ) {
			return $parent;
		}
		$ids = is_array( $value ) ? $value : ( '' !== $value ? array( $value ) : array() );
		foreach ( $ids as $id ) {
			if ( '' !== $id && ( ! is_numeric( $id ) || (int) $id <= 0 ) ) {
				return sprintf(
					/* translators: %s: field label */
					__( '"%s" contains one or more invalid term IDs.', 'fieldforge' ),
					$this->field['label'] ?? $this->field['name']
				);
			}
		}
		return true;
	}

	public function get_empty_value() {
		return in_array( $this->field['field_type'] ?? '', array( 'checkbox', 'multi_select' ), true ) ? array() : 0;
	}

	public function format_value( $value, int $post_id ) {
		$multiple = in_array( $this->field['field_type'] ?? '', array( 'checkbox', 'multi_select' ), true );
		$format   = $this->field['return_format'] ?? 'id';

		if ( $multiple ) {
			$ids = array_filter( array_map( 'absint', (array) $value ) );
			if ( 'object' === $format ) {
				return array_values( array_filter( array_map( 'get_term', $ids ) ) );
			}
			if ( 'name' === $format ) {
				$get_name = function ( $id ) {
					$t = get_term( $id );
					return $t && ! is_wp_error( $t ) ? $t->name : null;
				};
				return array_values( array_filter( array_map( $get_name, $ids ) ) );
			}
			if ( 'slug' === $format ) {
				$get_slug = function ( $id ) {
					$t = get_term( $id );
					return $t && ! is_wp_error( $t ) ? $t->slug : null;
				};
				return array_values( array_filter( array_map( $get_slug, $ids ) ) );
			}
			return array_values( $ids );
		}

		$id = (int) $value;
		if ( ! $id ) {
			return 'object' === $format ? null : ( in_array( $format, array( 'name', 'slug' ), true ) ? '' : 0 );
		}
		$term = get_term( $id );
		if ( ! $term || is_wp_error( $term ) ) {
			return 'object' === $format ? null : ( in_array( $format, array( 'name', 'slug' ), true ) ? '' : 0 );
		}
		if ( 'object' === $format ) {
			return $term;
		}
		if ( 'name' === $format ) {
			return $term->name;
		}
		if ( 'slug' === $format ) {
			return $term->slug;
		}
		return $id;
	}

	/**
	 * AJAX: search taxonomy terms for pickers.
	 */
	public static function ajax_search(): void {
		check_ajax_referer( 'fieldforge_admin', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( null, 403 );
		}

		$search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$taxonomy = sanitize_key( wp_unslash( $_POST['taxonomy'] ?? 'category' ) );

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'search'     => $search,
				'number'     => 20,
			)
		);

		if ( is_wp_error( $terms ) ) {
			$terms = array();
		}

		$results = array_map(
			function ( $t ) {
				return array(
					'id'    => $t->term_id,
					'title' => $t->name,
				);
			},
			$terms
		);

		wp_send_json_success( $results );
	}
}
