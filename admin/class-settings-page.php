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

			<?php $this->render_attribution_strip(); ?>

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

	/**
	 * Render the attribution / sponsor strip shown at the top of the settings page.
	 */
	private function render_attribution_strip(): void {
		?>
		<div class="fieldforge-attribution-strip">
			<div class="fieldforge-attribution-strip__inner">
				<div class="fieldforge-attribution-strip__logo">
					<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
						<rect width="28" height="28" rx="6" fill="#2563EB"/>
						<path d="M7 14.5L11.5 19L21 9" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					<span class="fieldforge-attribution-strip__name">FieldForge</span>
				</div>
				<div class="fieldforge-attribution-strip__text">
					<p class="fieldforge-attribution-strip__tagline">
						<?php
						printf(
							/* translators: %s: linked author name */
							esc_html__( 'FieldForge is a free plugin developed and maintained by %s.', 'fieldforge' ),
							'<a href="https://github.com/arunrajiah" target="_blank" rel="noopener noreferrer">arunrajiah</a>'
						);
						?>
					</p>
					<p class="fieldforge-attribution-strip__sponsor">
						<?php
						printf(
							/* translators: %s: linked sponsor CTA */
							esc_html__( 'If you find it useful, please consider %s — it helps keep the project alive and growing.', 'fieldforge' ),
							'<a href="https://github.com/sponsors/arunrajiah" target="_blank" rel="noopener noreferrer" class="fieldforge-attribution-strip__sponsor-link">'
							. '<svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="vertical-align:-2px;margin-right:3px"><path d="M8 13.7C7.6 13.4.5 9 .5 5.2.5 3 2.2 1.3 4.3 1.3c1.1 0 2.2.5 3 1.3.8-.8 1.9-1.3 3-1.3C12.4 1.3 14 3 14 5.2c0 3.9-7.1 8.2-6 8.5z"/></svg>'
							. esc_html__( 'becoming a sponsor on GitHub', 'fieldforge' )
							. '</a>'
						);
						?>
					</p>
				</div>
			</div>
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

	/**
	 * Write a message to the PHP error log, but only when debug logging is enabled
	 * in the FieldForge Settings page.
	 *
	 * @param string $message Log message (should include 'FieldForge: ' prefix).
	 */
	public static function debug_log( string $message ): void {
		$settings = self::get_settings();
		if ( ! empty( $settings['debug_log'] ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( $message );
		}
	}
}
