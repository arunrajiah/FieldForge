<?php
/**
 * Attaches meta boxes to post edit screens based on field group location rules.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Renderer {

	/** @var FieldForge_Field_Registry */
	private FieldForge_Field_Registry $registry;

	/** @var FieldForge_Field_Group */
	private FieldForge_Field_Group $field_group;

	public function __construct( FieldForge_Field_Registry $registry, FieldForge_Field_Group $field_group ) {
		$this->registry    = $registry;
		$this->field_group = $field_group;

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Hook: add_meta_boxes — registers one meta box per matching field group.
	 *
	 * @param string  $post_type
	 * @param WP_Post $post
	 */
	public function add_meta_boxes( string $post_type, WP_Post $post ): void {
		if ( FieldForge_Field_Group::CPT === $post_type ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$groups = $this->field_group->get_groups_for_screen( $screen, $post->ID );

		foreach ( $groups as $group ) {
			add_meta_box(
				'fieldforge_group_' . $group['ID'],
				esc_html( $group['title'] ),
				array( $this, 'render_meta_box' ),
				$post_type,
				esc_attr( $group['position'] ),
				'default',
				array( 'group' => $group )
			);
		}
	}

	/**
	 * Render a single field group meta box.
	 *
	 * @param WP_Post $post
	 * @param array   $meta_box  Args array containing 'group'.
	 */
	public function render_meta_box( WP_Post $post, array $meta_box ): void {
		$group = $meta_box['args']['group'] ?? array();
		if ( empty( $group['fields'] ) ) {
			echo '<p>' . esc_html__( 'No fields defined.', 'fieldforge' ) . '</p>';
			return;
		}

		wp_nonce_field( 'fieldforge_save_' . $group['ID'], 'fieldforge_nonce_' . $group['ID'] );

		echo '<div class="fieldforge-meta-box" data-group-id="' . esc_attr( $group['ID'] ) . '">';

		foreach ( $group['fields'] as $field_config ) {
			$field = $this->registry->make_field( $field_config );
			if ( $field ) {
				$field->render( $post->ID );
			}
		}

		echo '</div>';
	}

	public function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		wp_enqueue_style(
			'fieldforge-admin',
			FIELDFORGE_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			FIELDFORGE_VERSION
		);
		wp_enqueue_script(
			'fieldforge-admin',
			FIELDFORGE_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-util' ),
			FIELDFORGE_VERSION,
			true
		);
		// Build conditional logic data for the current post's field groups.
		$cl_fields = array();
		$post_id   = (int) ( $_GET['post'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $post_id ) {
			$post   = get_post( $post_id );
			$screen = get_current_screen();
			if ( $post && $screen ) {
				foreach ( $this->field_group->get_groups_for_screen( $screen, $post_id ) as $group ) {
					foreach ( $group['fields'] ?? array() as $fc ) {
						if ( ! empty( $fc['name'] ) ) {
							$cl_fields[ $fc['name'] ] = array(
								'key'                    => $fc['key'] ?? '',
								'type'                   => $fc['type'] ?? 'text',
								'conditional_logic'       => (int) ( $fc['conditional_logic'] ?? 0 ),
								'conditional_logic_rules' => $fc['conditional_logic_rules'] ?? array(),
							);
						}
					}
				}
			}
		}

		wp_localize_script(
			'fieldforge-admin',
			'fieldforgeData',
			array(
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'fieldforge_admin' ),
				'conditionalLogic' => $cl_fields,
				'i18n'           => array(
					'addRow'    => __( 'Add Row', 'fieldforge' ),
					'removeRow' => __( 'Remove Row', 'fieldforge' ),
					'noRows'    => __( 'No rows yet.', 'fieldforge' ),
					'maxRows'   => __( 'Maximum number of rows reached.', 'fieldforge' ),
					'minRows'   => __( 'Minimum number of rows required.', 'fieldforge' ),
					'confirmRemove' => __( 'Remove this row?', 'fieldforge' ),
				),
			)
		);
	}
}
