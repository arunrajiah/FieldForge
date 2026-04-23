<?php
/**
 * Local JSON — automatically saves field groups as JSON files for version control.
 *
 * Drop a directory called `fieldforge-json` inside your theme or plugin directory.
 * FieldForge will auto-save every field group there on publish/update, and load
 * groups from disk on boot so field groups work even before the DB is seeded.
 *
 * Filter the save path:
 *   add_filter( 'fieldforge/local_json/save_path', function( $path ) {
 *       return get_stylesheet_directory() . '/acf-json';  // share with ACF tooling
 *   } );
 *
 * Filter the load paths (array so multiple directories can be scanned):
 *   add_filter( 'fieldforge/local_json/load_paths', function( $paths ) {
 *       $paths[] = get_stylesheet_directory() . '/fieldforge-json';
 *       return $paths;
 *   } );
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Local_JSON {

	/** @var string Option key tracking which JSON file hashes are synced. */
	const SYNC_OPTION = 'fieldforge_local_json_sync';

	public function __construct() {
		// Save JSON when a field group is saved.
		add_action( 'save_post_' . FieldForge_Field_Group::CPT, array( $this, 'on_group_save' ), 20, 2 );

		// Delete JSON when a field group is trashed/deleted.
		add_action( 'trashed_post',   array( $this, 'on_group_delete' ) );
		add_action( 'before_delete_post', array( $this, 'on_group_delete' ) );

		// Admin notice when files are out of sync.
		add_action( 'admin_notices', array( $this, 'sync_notice' ) );

		// AJAX: sync all JSON files into the database.
		add_action( 'wp_ajax_fieldforge_sync_json', array( $this, 'ajax_sync' ) );
	}

	// ------------------------------------------------------------------
	// Save path / load paths
	// ------------------------------------------------------------------

	/**
	 * Directory where FieldForge writes JSON files.
	 */
	public function get_save_path(): string {
		$default = get_stylesheet_directory() . '/fieldforge-json';
		return apply_filters( 'fieldforge/local_json/save_path', $default );
	}

	/**
	 * Directories FieldForge scans for JSON files to load.
	 *
	 * @return string[]
	 */
	public function get_load_paths(): array {
		$default = array( $this->get_save_path() );
		return (array) apply_filters( 'fieldforge/local_json/load_paths', $default );
	}

	// ------------------------------------------------------------------
	// Auto-save
	// ------------------------------------------------------------------

	/**
	 * Hook: save_post — write the field group to a JSON file.
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
	public function on_group_save( int $post_id, WP_Post $post ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( 'publish' !== $post->post_status && 'draft' !== $post->post_status ) {
			return;
		}

		$save_path = $this->get_save_path();
		if ( ! $this->maybe_create_dir( $save_path ) ) {
			return;
		}

		$ff    = FieldForge::get_instance();
		$group = $ff->field_group->get_group( $post_id );
		if ( ! $group ) {
			return;
		}

		$group['modified'] = time();
		$file              = $save_path . '/' . sanitize_file_name( 'group_' . $post_id ) . '.json';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $file, wp_json_encode( $group, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
	}

	/**
	 * Hook: delete/trash post — remove the corresponding JSON file.
	 *
	 * @param int $post_id
	 */
	public function on_group_delete( int $post_id ): void {
		if ( FieldForge_Field_Group::CPT !== get_post_type( $post_id ) ) {
			return;
		}

		foreach ( $this->get_load_paths() as $path ) {
			$file = $path . '/' . sanitize_file_name( 'group_' . $post_id ) . '.json';
			if ( file_exists( $file ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				unlink( $file );
			}
		}
	}

	// ------------------------------------------------------------------
	// Sync: JSON → DB
	// ------------------------------------------------------------------

	/**
	 * Scan load paths and return all JSON group files found.
	 *
	 * @return array[] Each entry: ['file' => path, 'group' => array, 'modified' => int]
	 */
	public function get_json_files(): array {
		$files = array();
		foreach ( $this->get_load_paths() as $path ) {
			if ( ! is_dir( $path ) ) {
				continue;
			}
			foreach ( glob( $path . '/group_*.json' ) ?: array() as $file ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				$raw = file_get_contents( $file );
				if ( ! $raw ) {
					continue;
				}
				$group = json_decode( $raw, true );
				if ( is_array( $group ) && ! empty( $group['title'] ) ) {
					$files[] = array(
						'file'     => $file,
						'group'    => $group,
						'modified' => $group['modified'] ?? filemtime( $file ),
					);
				}
			}
		}
		return $files;
	}

	/**
	 * Return JSON files that are newer than their database counterpart.
	 *
	 * @return array[]
	 */
	public function get_pending_sync(): array {
		$pending = array();
		foreach ( $this->get_json_files() as $entry ) {
			$group   = $entry['group'];
			$post_id = (int) ( $group['ID'] ?? 0 );
			if ( ! $post_id ) {
				$pending[] = $entry;
				continue;
			}
			$post = get_post( $post_id );
			if ( ! $post ) {
				$pending[] = $entry;
				continue;
			}
			$db_modified = strtotime( $post->post_modified_gmt );
			if ( $entry['modified'] > $db_modified ) {
				$pending[] = $entry;
			}
		}
		return $pending;
	}

	/**
	 * Import a JSON group file into the database.
	 *
	 * @param array $entry From get_json_files().
	 * @return int Post ID.
	 */
	public function sync_file( array $entry ): int {
		$ff = FieldForge::get_instance();
		return $ff->field_group->save_group( $entry['group'] );
	}

	// ------------------------------------------------------------------
	// Admin notice
	// ------------------------------------------------------------------

	/**
	 * Show a notice on the Field Groups screen when JSON files are out of sync.
	 */
	public function sync_notice(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'edit-' . FieldForge_Field_Group::CPT !== $screen->id ) {
			return;
		}

		$pending = $this->get_pending_sync();
		if ( empty( $pending ) ) {
			return;
		}

		$count = count( $pending );
		?>
		<div class="notice notice-warning">
			<p>
				<?php
				printf(
					/* translators: %d: number of out-of-sync JSON files */
					esc_html( _n(
						'FieldForge: %d field group JSON file is newer than the database.',
						'FieldForge: %d field group JSON files are newer than the database.',
						$count,
						'fieldforge'
					) ),
					(int) $count
				);
				?>
				<button type="button" class="button button-small fieldforge-sync-json" style="margin-left:8px"
					data-nonce="<?php echo esc_attr( wp_create_nonce( 'fieldforge_sync_json' ) ); ?>">
					<?php esc_html_e( 'Sync from JSON', 'fieldforge' ); ?>
				</button>
				<span class="fieldforge-sync-result" style="margin-left:8px"></span>
			</p>
		</div>
		<?php
	}

	// ------------------------------------------------------------------
	// AJAX
	// ------------------------------------------------------------------

	/**
	 * AJAX handler: sync all pending JSON files into the DB.
	 */
	public function ajax_sync(): void {
		check_ajax_referer( 'fieldforge_sync_json', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}

		$synced = 0;
		foreach ( $this->get_pending_sync() as $entry ) {
			if ( $this->sync_file( $entry ) ) {
				$synced++;
			}
		}

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: %d: number of groups synced */
				_n( '%d group synced.', '%d groups synced.', $synced, 'fieldforge' ),
				$synced
			),
		) );
	}

	// ------------------------------------------------------------------
	// Helpers
	// ------------------------------------------------------------------

	private function maybe_create_dir( string $path ): bool {
		if ( is_dir( $path ) ) {
			return true;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
		return mkdir( $path, 0755, true );
	}
}
