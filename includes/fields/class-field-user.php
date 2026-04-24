<?php
/**
 * User field type — AJAX-backed searchable picker.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_User extends FieldForge_Field_Base {

	public function render( int $post_id ): void {
		$saved     = $this->load( $post_id );
		$multiple  = ! empty( $this->field['multiple'] );
		$role      = sanitize_text_field( $this->field['role'] ?? '' );
		$name      = esc_attr( $this->field['name'] );
		$field_id  = esc_attr( 'fieldforge_field_' . $this->field['name'] );
		$name_attr = $multiple ? $name . '[]' : $name;

		$saved_ids = $multiple ? (array) $saved : ( $saved ? array( (int) $saved ) : array() );

		$selected_items = array();
		foreach ( $saved_ids as $uid ) {
			$uid = (int) $uid;
			if ( $uid ) {
				$u = get_userdata( $uid );
				if ( $u ) {
					$selected_items[] = array(
						'id'    => $uid,
						'title' => $u->display_name . ' (' . $u->user_email . ')',
					);
				} else {
					// Referenced user no longer exists — log and return empty state.
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( sprintf( 'FieldForge: user field "%s" references missing user ID %d.', $this->field['name'], $uid ) );
				}
			}
		}

		$html  = '<div class="fieldforge-picker" data-type="user"'
			. ' data-multiple="' . ( $multiple ? '1' : '0' ) . '"'
			. ' data-role="' . esc_attr( $role ) . '"'
			. ' data-field-name="' . esc_attr( $name_attr ) . '">';
		$html .= '<input type="text" class="fieldforge-picker-search widefat" placeholder="' . esc_attr__( 'Search users…', 'fieldforge' ) . '" autocomplete="off" />';
		$html .= '<div class="fieldforge-picker-dropdown" style="display:none"></div>';
		$html .= '<div class="fieldforge-picker-tags">';
		foreach ( $selected_items as $item ) {
			$html .= $this->tag_html( $name_attr, $item['id'], $item['title'] );
		}
		if ( ! $multiple && empty( $selected_items ) ) {
			$html .= '<input type="hidden" name="' . $name_attr . '" id="' . $field_id . '" value="" />';
		}
		$html .= '</div>';
		$html .= '</div>';

		$this->render_wrapper( $html );
	}

	private function tag_html( string $name_attr, int $id, string $title ): string {
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
					__( '"%s" contains one or more invalid user IDs.', 'fieldforge' ),
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
				return array_values( array_filter( array_map( 'get_userdata', $ids ) ) );
			}
			if ( 'array' === $format ) {
				return array_values( array_filter( array_map( array( $this, 'user_to_array' ), $ids ) ) );
			}
			return array_values( $ids );
		}

		$id = (int) $value;
		if ( ! $id ) {
			return 'object' === $format ? null : ( 'array' === $format ? array() : 0 );
		}
		if ( 'object' === $format ) {
			return get_userdata( $id ) ?: null;
		}
		if ( 'array' === $format ) {
			return $this->user_to_array( $id );
		}
		return $id;
	}

	/**
	 * @param int $user_id
	 * @return array|null
	 */
	private function user_to_array( int $user_id ): ?array {
		$u = get_userdata( $user_id );
		if ( ! $u ) {
			return null;
		}
		return array(
			'id'           => $u->ID,
			'display_name' => $u->display_name,
			'email'        => $u->user_email,
			'login'        => $u->user_login,
			'roles'        => $u->roles,
		);
	}

	/**
	 * AJAX: search users for the picker.
	 */
	public static function ajax_search(): void {
		check_ajax_referer( 'fieldforge_admin', 'nonce' );
		if ( ! current_user_can( 'list_users' ) ) {
			wp_send_json_error( null, 403 );
		}

		$search = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$role   = sanitize_text_field( wp_unslash( $_POST['role'] ?? '' ) );

		$args = array(
			'number' => 20,
			'search' => $search ? '*' . $search . '*' : '',
		);
		if ( $role ) {
			$args['role'] = $role;
		}
		$users = get_users( $args );

		$results = array_map(
			function ( $u ) {
				return array(
					'id'    => $u->ID,
					'title' => $u->display_name . ' (' . $u->user_email . ')',
				);
			},
			$users
		);

		wp_send_json_success( $results );
	}
}
