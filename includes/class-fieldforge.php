<?php
/**
 * Main FieldForge plugin class.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FieldForge singleton. Bootstraps all subsystems.
 */
final class FieldForge {

	/** @var FieldForge|null */
	private static $instance = null;

	/** @var FieldForge_Field_Registry */
	public $registry;

	/** @var FieldForge_Field_Group */
	public $field_group;

	/** @var FieldForge_Field_Renderer */
	public $renderer;

	/** @var FieldForge_Meta_Handler */
	public $meta_handler;

	/** @var FieldForge_Local_JSON */
	public $local_json;

	private function __construct() {
		$this->registry     = new FieldForge_Field_Registry();
		$this->field_group  = new FieldForge_Field_Group();
		$this->meta_handler = new FieldForge_Meta_Handler();
		$this->renderer     = new FieldForge_Field_Renderer( $this->registry, $this->field_group );
		$this->local_json   = new FieldForge_Local_JSON();

		new FieldForge_REST_API();
		new FieldForge_Conditional_Logic();

		// AJAX handlers for searchable pickers.
		add_action( 'wp_ajax_fieldforge_search_posts', array( 'FieldForge_Field_Post_Object', 'ajax_search' ) );
		add_action( 'wp_ajax_fieldforge_search_users', array( 'FieldForge_Field_User', 'ajax_search' ) );
		add_action( 'wp_ajax_fieldforge_search_terms', array( 'FieldForge_Field_Taxonomy', 'ajax_search' ) );

		if ( is_admin() ) {
			new FieldForge_Field_Group_Editor( $this->registry );
			new FieldForge_Meta_Box_Renderer( $this->registry );
			new FieldForge_Settings_Page();
		}

		$this->registry->register_core_fields();
		$this->load_textdomain();
	}

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function activate(): void {
		FieldForge_Field_Group::register_cpt();
		update_option( 'fieldforge_version', FIELDFORGE_VERSION );
		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'fieldforge_local_json_sync' );
		delete_transient( 'fieldforge_field_groups' );
		flush_rewrite_rules();
	}

	private function load_textdomain(): void {
		load_plugin_textdomain( 'fieldforge', false, dirname( plugin_basename( FIELDFORGE_PLUGIN_FILE ) ) . '/languages' );
	}
}
