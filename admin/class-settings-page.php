<?php
/**
 * FieldForge plugin settings page.
 *
 * Provides a Settings submenu under the Field Groups menu for configuring:
 *   - Local JSON sync path
 *   - Debug logging toggle
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Settings_Page {

	/** Option key that stores all plugin settings. */
	const OPTION_KEY = 'fieldforge_settings';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function add_menu(): void {
		add_submenu_page(
			'edit.php?post_type=' . FieldForge_Field_Group::CPT,
			__( 'FieldForge Settings', 'fieldforge' ),
			__( 'Settings', 'fieldforge' ),
			'manage_options',
			'fieldforge-settings',
			array( $this, 'render' )
		);
	}

	public function register_settings(): void {
		register_setting(
			'fieldforge_settings_group',
			self::OPTION_KEY,
			array(
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => array(),
			)
		);

		add_settings_section( 'fieldforge_local_json', __( 'Local JSON', 'fieldforge' ), '__return_false', 'fieldforge-settings' );
		add_settings_section( 'fieldforge_debug', __( 'Debug', 'fieldforge' ), '__return_false', 'fieldforge-settings' );

		add_settings_field(
			'local_json_path',
			__( 'JSON Save Path', 'fieldforge' ),
			array( $this, 'render_local_json_path' ),
			'fieldforge-settings',
			'fieldforge_local_json'
		);

		add_settings_field(
			'local_json_load',
			__( 'JSON Load Path', 'fieldforge' ),
			array( $this, 'render_local_json_load' ),
			'fieldforge-settings',
			'fieldforge_local_json'
		);

		add_settings_field(
			'debug_log',
			__( 'Enable Debug Logging', 'fieldforge' ),
			array( $this, 'render_debug_log' ),
			'fieldforge-settings',
			'fieldforge_debug'
		);
	}

	public function sanitize( $input ): array {
		$clean = array();
		if ( ! is_array( $input ) ) {
			return $clean;
		}
		if ( isset( $input['local_json_path'] ) ) {
			$clean['local_json_path'] = sanitize_text_field( $input['local_json_path'] );
		}
		if ( isset( $input['local_json_load'] ) ) {
			$clean['local_json_load'] = sanitize_text_field( $input['local_json_load'] );
		}
		$clean['debug_log'] = ! empty( $input['debug_log'] ) ? 1 : 0;
		return $clean;
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'fieldforge' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'FieldForge Settings', 'fieldforge' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'fieldforge_settings_group' );
				do_settings_sections( 'fieldforge-settings' );
				submit_button();
				?>
			</form>

			<hr />

			<h2><?php esc_html_e( 'Local JSON Sync', 'fieldforge' ); ?></h2>
			<p><?php esc_html_e( 'Import all field groups from the configured JSON load path into the database.', 'fieldforge' ); ?></p>
			<button type="button" class="button" id="fieldforge-sync-json"><?php esc_html_e( 'Sync from JSON', 'fieldforge' ); ?></button>
			<span id="fieldforge-sync-result" style="margin-left:10px"></span>
		</div>
		<?php
	}

	public function render_local_json_path(): void {
		$opts = $this->get_settings();
		$val  = $opts['local_json_path'] ?? '';
		echo '<input type="text" name="' . esc_attr( self::OPTION_KEY ) . '[local_json_path]" value="' . esc_attr( $val ) . '" class="regular-text" />';
		echo '<p class="description">' . esc_html__( 'Absolute path where FieldForge writes JSON files. Leave empty to use the default (fieldforge-json/ in your theme).', 'fieldforge' ) . '</p>';
	}

	public function render_local_json_load(): void {
		$opts = $this->get_settings();
		$val  = $opts['local_json_load'] ?? '';
		echo '<input type="text" name="' . esc_attr( self::OPTION_KEY ) . '[local_json_load]" value="' . esc_attr( $val ) . '" class="regular-text" />';
		echo '<p class="description">' . esc_html__( 'Absolute path to load JSON files from. Leave empty to use the same as the save path.', 'fieldforge' ) . '</p>';
	}

	public function render_debug_log(): void {
		$opts = $this->get_settings();
		$val  = ! empty( $opts['debug_log'] );
		echo '<label><input type="checkbox" name="' . esc_attr( self::OPTION_KEY ) . '[debug_log]" value="1"' . checked( $val, true, false ) . ' /> ';
		echo esc_html__( 'Log FieldForge activity to the PHP error log.', 'fieldforge' ) . '</label>';
	}

	/**
	 * Return current settings, with defaults merged in.
	 */
	public static function get_settings(): array {
		$defaults = array(
			'local_json_path' => '',
			'local_json_load' => '',
			'debug_log'       => 0,
		);
		$saved = get_option( self::OPTION_KEY, array() );
		return is_array( $saved ) ? array_merge( $defaults, $saved ) : $defaults;
	}
}
