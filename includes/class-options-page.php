<?php
/**
 * Options Pages — global wp_options-backed admin pages with FieldForge fields.
 *
 * Usage:
 *   fieldforge_register_options_page( array(
 *       'page_title'  => 'Site Options',
 *       'menu_title'  => 'Site Options',
 *       'menu_slug'   => 'my-site-options',
 *       'capability'  => 'manage_options',
 *       'parent_slug' => '',          // empty = top-level; or e.g. 'options-general.php'
 *       'icon_url'    => '',
 *       'position'    => null,
 *       'autoload'    => false,        // whether to autoload wp_options values
 *   ) );
 *
 * Retrieve a value:
 *   fieldforge_get( 'my_field', 'option' );      // string 'option' → global option
 *   fieldforge_get( 'my_field', 'my-site-options' ); // slug-based lookup
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register an options page.
 *
 * @param array $args {
 *     @type string      $page_title  Page <title> text.
 *     @type string      $menu_title  Sidebar menu label.
 *     @type string      $menu_slug   Unique slug for the page.
 *     @type string      $capability  WordPress capability required. Default 'manage_options'.
 *     @type string      $parent_slug Parent menu slug, or empty for top-level.
 *     @type string      $icon_url    Dashicon or URL for top-level menu icon.
 *     @type int|null    $position    Menu position.
 *     @type bool        $autoload    Whether to autoload option values.
 * }
 */
function fieldforge_register_options_page( array $args ): void {
	FieldForge_Options_Page::register( $args );
}

/**
 * Manages all registered FieldForge options pages.
 */
class FieldForge_Options_Page {

	/** @var array[] Registered page definitions. */
	private static array $pages = array();

	/**
	 * Register a page definition and hook into admin_menu.
	 *
	 * @param array $args Options page args (see fieldforge_register_options_page).
	 */
	public static function register( array $args ): void {
		$defaults = array(
			'page_title'  => __( 'Options', 'fieldforge' ),
			'menu_title'  => __( 'Options', 'fieldforge' ),
			'menu_slug'   => 'fieldforge-options',
			'capability'  => 'manage_options',
			'parent_slug' => '',
			'icon_url'    => 'dashicons-admin-settings',
			'position'    => null,
			'autoload'    => false,
		);

		$page = array_merge( $defaults, $args );
		$page['menu_slug'] = sanitize_key( $page['menu_slug'] );

		self::$pages[ $page['menu_slug'] ] = $page;

		// Hook admin_menu once per registration call.
		add_action( 'admin_menu', function () use ( $page ) {
			self::add_menu_page( $page );
		} );

		// Handle form saves.
		add_action( 'admin_post_fieldforge_save_options_' . $page['menu_slug'], array( __CLASS__, 'handle_save' ) );
	}

	/**
	 * Return all registered page definitions.
	 *
	 * @return array[]
	 */
	public static function get_pages(): array {
		return self::$pages;
	}

	/**
	 * Return a single page definition by slug, or null.
	 *
	 * @param string $slug
	 * @return array|null
	 */
	public static function get_page( string $slug ): ?array {
		return self::$pages[ $slug ] ?? null;
	}

	/**
	 * Register the WordPress admin menu entry.
	 */
	private static function add_menu_page( array $page ): void {
		$callback = function () use ( $page ) {
			self::render_page( $page );
		};

		if ( $page['parent_slug'] ) {
			add_submenu_page(
				$page['parent_slug'],
				$page['page_title'],
				$page['menu_title'],
				$page['capability'],
				$page['menu_slug'],
				$callback
			);
		} else {
			add_menu_page(
				$page['page_title'],
				$page['menu_title'],
				$page['capability'],
				$page['menu_slug'],
				$callback,
				$page['icon_url'],
				$page['position']
			);
		}
	}

	/**
	 * Render an options page with all matching field groups.
	 */
	private static function render_page( array $page ): void {
		if ( ! current_user_can( $page['capability'] ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'fieldforge' ) );
		}

		$ff     = FieldForge::get_instance();
		$groups = self::get_groups_for_page( $page['menu_slug'] );

		$saved    = false;
		$messages = array();
		if ( isset( $_GET['fieldforge-saved'] ) && '1' === $_GET['fieldforge-saved'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$saved = true;
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( $page['page_title'] ); ?></h1>

			<?php if ( $saved ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Options saved.', 'fieldforge' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( empty( $groups ) ) : ?>
				<p><?php esc_html_e( 'No field groups are assigned to this options page.', 'fieldforge' ); ?></p>
				<p>
					<?php
					printf(
						/* translators: %s: link to add new field group */
						esc_html__( 'Go to %s and set the location rule to "Options Page is equal to %s".', 'fieldforge' ),
						'<a href="' . esc_url( admin_url( 'edit.php?post_type=fieldforge_group' ) ) . '">' . esc_html__( 'Field Groups', 'fieldforge' ) . '</a>',
						'<code>' . esc_html( $page['menu_slug'] ) . '</code>'
					);
					?>
				</p>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="fieldforge_save_options_<?php echo esc_attr( $page['menu_slug'] ); ?>" />
					<input type="hidden" name="fieldforge_options_page" value="<?php echo esc_attr( $page['menu_slug'] ); ?>" />
					<?php wp_nonce_field( 'fieldforge_save_options_' . $page['menu_slug'], 'fieldforge_options_nonce' ); ?>

					<?php foreach ( $groups as $group ) : ?>
						<div class="fieldforge-options-group postbox" style="padding:12px 16px;margin-bottom:16px;">
							<h2 class="hndle"><?php echo esc_html( $group['title'] ); ?></h2>
							<div class="fieldforge-meta-box">
								<?php
								$registry = $ff->registry;
								foreach ( $group['fields'] as $field_config ) {
									$field = $registry->make_field( $field_config );
									if ( $field ) {
										// Load from wp_options instead of postmeta.
										$field->render_with_value(
											self::get_option( $page['menu_slug'], $field_config['name'] ?? '' )
										);
									}
								}
								?>
							</div>
						</div>
					<?php endforeach; ?>

					<?php submit_button( __( 'Save Options', 'fieldforge' ) ); ?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Handle admin-post.php form submission.
	 */
	public static function handle_save(): void {
		$slug = isset( $_POST['fieldforge_options_page'] ) ? sanitize_key( wp_unslash( $_POST['fieldforge_options_page'] ) ) : '';

		if ( ! $slug ) {
			wp_die( esc_html__( 'Invalid request.', 'fieldforge' ) );
		}

		$page = self::get_page( $slug );
		if ( ! $page || ! current_user_can( $page['capability'] ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'fieldforge' ) );
		}

		if ( ! isset( $_POST['fieldforge_options_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['fieldforge_options_nonce'] ) ), 'fieldforge_save_options_' . $slug ) ) {
			wp_die( esc_html__( 'Nonce verification failed.', 'fieldforge' ) );
		}

		$ff       = FieldForge::get_instance();
		$groups   = self::get_groups_for_page( $slug );
		$registry = $ff->registry;

		foreach ( $groups as $group ) {
			foreach ( $group['fields'] as $field_config ) {
				$field = $registry->make_field( $field_config );
				if ( ! $field ) {
					continue;
				}
				$name  = $field_config['name'] ?? '';
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above.
				$raw   = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : $field->get_empty_value();
				$clean = $field->sanitize( $raw );
				self::update_option( $slug, $name, $clean, $page['autoload'] );
			}
		}

		$redirect = add_query_arg(
			array(
				'page'             => $slug,
				'fieldforge-saved' => '1',
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	// ------------------------------------------------------------------
	// Storage helpers
	// ------------------------------------------------------------------

	/**
	 * Build the wp_options key for a field on a given options page.
	 *
	 * @param string $page_slug
	 * @param string $field_name
	 * @return string
	 */
	public static function option_key( string $page_slug, string $field_name ): string {
		return 'fieldforge_' . $page_slug . '_' . $field_name;
	}

	/**
	 * Get a field value from wp_options.
	 *
	 * @param string $page_slug  Options page slug, or 'option'/'options' for any page.
	 * @param string $field_name
	 * @return mixed
	 */
	public static function get_option( string $page_slug, string $field_name ) {
		if ( in_array( $page_slug, array( 'option', 'options' ), true ) ) {
			// Search across all registered pages.
			foreach ( array_keys( self::$pages ) as $slug ) {
				$val = get_option( self::option_key( $slug, $field_name ), null );
				if ( null !== $val ) {
					return $val;
				}
			}
			return '';
		}
		return get_option( self::option_key( $page_slug, $field_name ), '' );
	}

	/**
	 * Update a field value in wp_options.
	 *
	 * @param string $page_slug
	 * @param string $field_name
	 * @param mixed  $value
	 * @param bool   $autoload
	 */
	public static function update_option( string $page_slug, string $field_name, $value, bool $autoload = false ): void {
		update_option( self::option_key( $page_slug, $field_name ), $value, $autoload );
	}

	/**
	 * Get all field groups whose location rules target this options page.
	 *
	 * @param string $page_slug
	 * @return array[]
	 */
	private static function get_groups_for_page( string $page_slug ): array {
		$ff     = FieldForge::get_instance();
		$all    = $ff->field_group->get_all_groups();
		$result = array();

		foreach ( $all as $group ) {
			foreach ( $group['location'] as $or_group ) {
				foreach ( $or_group as $rule ) {
					if ( 'options_page' === ( $rule['param'] ?? '' )
						&& '==' === ( $rule['operator'] ?? '' )
						&& $page_slug === ( $rule['value'] ?? '' ) ) {
						$result[] = $group;
						break 2;
					}
				}
			}
		}
		return $result;
	}
}
