<?php
/**
 * Post Object field type — AJAX-backed searchable picker.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Post_Object extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$saved      = $this->load( $post_id );
		$post_types = ! empty( $this->field['post_type'] ) ? (array) $this->field['post_type'] : array( 'post', 'page' );
		$multiple   = ! empty( $this->field['multiple'] );
		$name       = esc_attr( $this->field['name'] );
		$field_id   = esc_attr( 'fieldforge_field_' . $this->field['name'] );
		$name_attr  = $multiple ? $name . '[]' : $name;

		$saved_ids = $multiple ? (array) $saved : ( $saved ? array( (int) $saved ) : array() );

		// Build initial selected tags from existing saved IDs.
		$selected_items = array();
		foreach ( $saved_ids as $sid ) {
			$sid = (int) $sid;
			if ( $sid ) {
				$p = get_post( $sid );
				if ( $p ) {
					$selected_items[] = array(
						'id'    => $sid,
						'title' => $p->post_title,
					);
				} else {
					// Referenced post no longer exists — log and return empty state.
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( sprintf( 'FieldForge: post_object field "%s" references missing post ID %d.', $this->field['name'], $sid ) );
				}
			}
		}

		$html  = '<div class="fieldforge-picker" data-type="post_object"'
			. ' data-multiple="' . ( $multiple ? '1' : '0' ) . '"'
			. ' data-post-types="' . esc_attr( implode( ',', $post_types ) ) . '"'
			. ' data-field-name="' . esc_attr( $name_attr ) . '">';
		$html .= '<input type="text" class="fieldforge-picker-search widefat" placeholder="' . esc_attr__( 'Search…', 'fieldforge' ) . '" autocomplete="off" />';
		$html .= '<div class="fieldforge-picker-dropdown" style="display:none"></div>';
		$html .= '<div class="fieldforge-picker-tags">';
		foreach ( $selected_items as $item ) {
			$html .= $this->tag_html( $name_attr, $item['id'], $item['title'], $multiple );
		}
		if ( ! $multiple && empty( $selected_items ) ) {
			$html .= '<input type="hidden" name="' . $name_attr . '" id="' . $field_id . '" value="" />';
		}
		$html .= '</div>';
		$html .= '</div>';

		$this->render_wrapper( $html );
	}

	private function tag_html( string $name_attr, int $id, string $title, bool $multiple ): string {
		return sprintf(
			'<span class="fieldforge-picker-tag">'
			. '<input type="hidden" name="%s" value="%d" />'
			. '%s'
			. '<button type="button" class="fieldforge-picker-tag-remove" aria-label="%s">&times;</button>'
			. '</span>',
			esc_attr( $name_attr ),
			$id,
			esc_html( $title ),
			esc_attr__( 'Remove', 'fieldforge' )
		);
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
		$ids = is_array( $value ) ? $value : ( $value !== '' ? array( $value ) : array() );
		foreach ( $ids as $id ) {
			if ( $id !== '' && ( ! is_numeric( $id ) || (int) $id <= 0 ) ) {
				return sprintf(
					/* translators: %s: field label */
					__( '"%s" contains one or more invalid post IDs.', 'fieldforge' ),
					$this->field['label'] ?? $this->field['name']
				);
			}
		}
		return true;
	}

	public function get_empty_value() {
		return ! empty( $this->field['multiple'] ) ? array() : 0;
	}

	public function format_value( $value, int $post_id ) {
		$multiple = ! empty( $this->field['multiple'] );
		$format   = $this->field['return_format'] ?? 'id';

		if ( $multiple ) {
			$ids = array_filter( array_map( 'absint', (array) $value ) );
			if ( 'object' === $format ) {
				return array_values( array_filter( array_map( 'get_post', $ids ) ) );
			}
			return array_values( $ids );
		}

		$id = (int) $value;
		if ( ! $id ) {
			return 'object' === $format ? null : 0;
		}
		if ( 'object' === $format ) {
			return get_post( $id );
		}
		return $id;
	}

	/**
	 * AJAX: search posts for the picker.
	 */
	public static function ajax_search(): void {
		check_ajax_referer( 'fieldforge_admin', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( null, 403 );
		}

		$search = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized via array_map/sanitize_key below.
		$raw_types  = wp_unslash( $_POST['post_types'] ?? 'post' );
		$post_types = array_map( 'sanitize_key', explode( ',', $raw_types ) );

		$posts = get_posts(
			array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				's'              => $search,
				'orderby'        => $search ? 'relevance' : 'title',
				'order'          => 'ASC',
			)
		);

		$results = array_map(
			function ( $p ) {
				return array(
					'id'    => $p->ID,
					'title' => $p->post_title . ' (' . $p->post_type . ')',
				);
			},
			$posts
		);

		wp_send_json_success( $results );
	}
}
