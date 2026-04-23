<?php
/**
 * Field Group CPT and location rule evaluation.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages the `fieldforge_group` custom post type and evaluates location rules
 * to determine which field groups apply to the current edit screen.
 */
class FieldForge_Field_Group {

	const CPT = 'fieldforge_group';

	public function __construct() {
		add_action( 'init', array( __CLASS__, 'register_cpt' ) );
	}

	public static function register_cpt(): void {
		$labels = array(
			'name'          => __( 'Field Groups', 'fieldforge' ),
			'singular_name' => __( 'Field Group', 'fieldforge' ),
			'add_new'       => __( 'Add New', 'fieldforge' ),
			'add_new_item'  => __( 'Add New Field Group', 'fieldforge' ),
			'edit_item'     => __( 'Edit Field Group', 'fieldforge' ),
			'menu_name'     => __( 'FieldForge', 'fieldforge' ),
		);

		register_post_type(
			self::CPT,
			array(
				'labels'          => $labels,
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_icon'       => 'dashicons-editor-table',
				'capability_type' => 'post',
				'supports'        => array( 'title' ),
				'rewrite'         => false,
				'query_var'       => false,
				'show_in_rest'    => false,
			)
		);
	}

	/**
	 * Retrieve all active field groups as structured arrays.
	 *
	 * @return array[]
	 */
	public function get_all_groups(): array {
		$posts = get_posts(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);

		$groups = array();
		foreach ( $posts as $post ) {
			$group = $this->get_group( $post->ID );
			if ( $group ) {
				$groups[] = $group;
			}
		}
		return $groups;
	}

	/**
	 * Load a single field group by post ID.
	 *
	 * @param int $post_id
	 * @return array|null
	 */
	public function get_group( int $post_id ): ?array {
		$post = get_post( $post_id );
		if ( ! $post || self::CPT !== $post->post_type ) {
			return null;
		}

		$fields   = get_post_meta( $post_id, '_fieldforge_fields', true );
		$location = get_post_meta( $post_id, '_fieldforge_location', true );

		return array(
			'ID'          => $post_id,
			'key'         => 'group_' . $post_id,
			'title'       => $post->post_title,
			'description' => (string) get_post_meta( $post_id, '_fieldforge_description', true ),
			'fields'      => is_array( $fields ) ? $fields : array(),
			'location'    => is_array( $location ) ? $location : array(),
			'menu_order'  => (int) $post->menu_order,
			'position'    => get_post_meta( $post_id, '_fieldforge_position', true ) ? get_post_meta( $post_id, '_fieldforge_position', true ) : 'normal',
			'active'      => 'publish' === $post->post_status,
		);
	}

	/**
	 * Save a field group from an array.
	 *
	 * @param array $group
	 * @return int Post ID.
	 */
	public function save_group( array $group ): int {
		$post_data = array(
			'post_type'   => self::CPT,
			'post_status' => isset( $group['active'] ) && ! $group['active'] ? 'draft' : 'publish',
			'post_title'  => sanitize_text_field( $group['title'] ?? 'Untitled Group' ),
			'menu_order'  => (int) ( $group['menu_order'] ?? 0 ),
		);

		if ( ! empty( $group['ID'] ) ) {
			$post_data['ID'] = (int) $group['ID'];
			$post_id         = wp_update_post( $post_data );
		} else {
			$post_id = wp_insert_post( $post_data );
		}

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return 0;
		}

		update_post_meta( $post_id, '_fieldforge_fields', $group['fields'] ?? array() );
		update_post_meta( $post_id, '_fieldforge_location', $group['location'] ?? array() );
		update_post_meta( $post_id, '_fieldforge_position', sanitize_text_field( $group['position'] ?? 'normal' ) );
		update_post_meta( $post_id, '_fieldforge_description', sanitize_textarea_field( $group['description'] ?? '' ) );

		return $post_id;
	}

	/**
	 * Find all field groups whose location rules match the given WP_Screen context.
	 *
	 * @param WP_Screen $screen
	 * @param int       $post_id
	 * @return array[]
	 */
	public function get_groups_for_screen( WP_Screen $screen, int $post_id ): array {
		$all    = $this->get_all_groups();
		$result = array();

		foreach ( $all as $group ) {
			if ( $this->matches_location( $group['location'], $screen, $post_id ) ) {
				$result[] = $group;
			}
		}
		return $result;
	}

	/**
	 * Evaluate location rules (OR of AND groups, matching ACF semantics).
	 *
	 * @param array     $location  Multi-dimensional location rules.
	 * @param WP_Screen $screen
	 * @param int       $post_id
	 * @return bool
	 */
	private function matches_location( array $location, WP_Screen $screen, int $post_id ): bool {
		if ( empty( $location ) ) {
			return false;
		}

		foreach ( $location as $or_group ) {
			if ( empty( $or_group ) ) {
				continue;
			}
			$and_match = true;
			foreach ( $or_group as $rule ) {
				if ( ! $this->evaluate_rule( $rule, $screen, $post_id ) ) {
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

	/**
	 * Evaluate a single location rule.
	 *
	 * @param array     $rule     Rule with keys: param, operator, value.
	 * @param WP_Screen $screen
	 * @param int       $post_id
	 * @return bool
	 */
	private function evaluate_rule( array $rule, WP_Screen $screen, int $post_id ): bool {
		$param    = $rule['param'] ?? '';
		$operator = $rule['operator'] ?? '==';
		$value    = $rule['value'] ?? '';

		$actual = null;

		switch ( $param ) {
			case 'post_type':
				$actual = $screen->post_type ?? '';
				break;

			case 'post_status':
				$post   = get_post( $post_id );
				$actual = $post ? $post->post_status : '';
				break;

			case 'post_taxonomy':
				// value is "taxonomy_name:term_slug".
				if ( $post_id && strpos( $value, ':' ) !== false ) {
					list( $tax, $term_slug ) = explode( ':', $value, 2 );
					$terms                   = wp_get_post_terms( $post_id, $tax, array( 'fields' => 'slugs' ) );
					$actual                  = in_array( $term_slug, (array) $terms, true ) ? $value : '';
				}
				break;

			case 'page_parent':
				$post   = get_post( $post_id );
				$actual = $post ? (string) $post->post_parent : '0';
				break;

			case 'page_template':
				$tpl    = get_page_template_slug( $post_id );
				$actual = $tpl ? $tpl : 'default';
				break;

			case 'user_role':
				$user   = wp_get_current_user();
				$actual = ! empty( $user->roles ) ? $user->roles[0] : '';
				break;

			case 'current_user':
				$actual = (string) get_current_user_id();
				break;

			case 'current_user_role':
				$user   = wp_get_current_user();
				$actual = ! empty( $user->roles ) ? $user->roles[0] : '';
				break;

			case 'attachment':
				$actual = 'attachment' === ( $screen->post_type ?? '' ) ? 'attachment' : '';
				break;

			case 'comment':
				$actual = 'comment' === ( $screen->base ?? '' ) ? 'comment' : '';
				break;

			case 'taxonomy':
				// value = taxonomy slug.
				$actual = $screen->taxonomy ?? '';
				break;

			case 'options_page':
				// value = options page slug registered via fieldforge_register_options_page().
				$actual = $screen->id ?? '';
				break;

			case 'nav_menu':
				$actual = 'nav-menus' === ( $screen->base ?? '' ) ? 'nav_menu' : '';
				break;

			case 'post_format':
				if ( $post_id ) {
					$format = get_post_format( $post_id );
					$actual = $format ? $format : 'standard';
				}
				break;
		}

		if ( null === $actual ) {
			return false;
		}

		if ( '==' === $operator ) {
			return (string) $actual === (string) $value;
		}
		if ( '!=' === $operator ) {
			return (string) $actual !== (string) $value;
		}

		return false;
	}

	/**
	 * Export a field group to FieldForge JSON format.
	 *
	 * @param int $post_id
	 * @return string JSON.
	 */
	public function export_json( int $post_id ): string {
		$group = $this->get_group( $post_id );
		if ( ! $group ) {
			return '{}';
		}
		return wp_json_encode( $group, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
	}

	/**
	 * Import a FieldForge JSON string as a new (or updated) field group.
	 *
	 * @param string $json
	 * @return int|WP_Error Post ID on success.
	 */
	public function import_json( string $json ) {
		$data = json_decode( $json, true );
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'invalid_json', __( 'Invalid JSON.', 'fieldforge' ) );
		}
		return $this->save_group( $data );
	}
}
