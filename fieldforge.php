<?php
/**
 * Plugin Name: FieldForge
 * Plugin URI:  https://github.com/arunrajiah/fieldforge
 * Description: Open-source, GPL alternative to Advanced Custom Fields (ACF) Pro with native Repeater and Flexible Content fields.
 * Version:     0.1.0-dev
 * Author:      FieldForge Contributors
 * Author URI:  https://github.com/arunrajiah/fieldforge
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: fieldforge
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP:      7.4
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FIELDFORGE_VERSION', '0.1.0-dev' );
define( 'FIELDFORGE_PLUGIN_FILE', __FILE__ );
define( 'FIELDFORGE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FIELDFORGE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// --- Field base (must load before all field types) ---
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-base.php';

// --- Field types ---
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-text.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-textarea.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-number.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-email.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-url.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-password.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-select.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-checkbox.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-radio.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-true-false.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-date.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-time.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-color.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-message.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-image.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-file.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-gallery.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-post-object.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-taxonomy.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-user.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-link.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-wysiwyg.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-repeater.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/fields/class-field-flexible-content.php';

// --- Core subsystems ---
require_once FIELDFORGE_PLUGIN_DIR . 'includes/class-field-registry.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/class-field-group.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/class-meta-handler.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/class-field-renderer.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/class-template-helpers.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/class-acf-importer.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/class-options-page.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/class-local-json.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/class-rest-api.php';
require_once FIELDFORGE_PLUGIN_DIR . 'includes/class-conditional-logic.php';

// --- Admin UI ---
require_once FIELDFORGE_PLUGIN_DIR . 'admin/class-meta-box-renderer.php';
require_once FIELDFORGE_PLUGIN_DIR . 'admin/class-field-group-editor.php';

// --- Main singleton (must be last — depends on everything above) ---
require_once FIELDFORGE_PLUGIN_DIR . 'includes/class-fieldforge.php';

// Activation / deactivation hooks must be registered before plugins_loaded.
register_activation_hook( __FILE__, array( 'FieldForge', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'FieldForge', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'FieldForge', 'get_instance' ) );
