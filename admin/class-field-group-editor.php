<?php
/**
 * Admin UI for creating and editing field groups.
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Field_Group_Editor {

	/** @var FieldForge_Field_Registry */
	private FieldForge_Field_Registry $registry;

	public function __construct( FieldForge_Field_Registry $registry ) {
		$this->registry = $registry;

		add_action( 'add_meta_boxes_' . FieldForge_Field_Group::CPT, array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . FieldForge_Field_Group::CPT, array( $this, 'save_group' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_menu', array( $this, 'add_tools_menu' ) );
		add_action( 'wp_ajax_fieldforge_export_group', array( $this, 'ajax_export_group' ) );
	}

	public function add_meta_boxes(): void {
		add_meta_box(
			'fieldforge_fields',
			__( 'Fields', 'fieldforge' ),
			array( $this, 'render_fields_meta_box' ),
			FieldForge_Field_Group::CPT,
			'normal',
			'high'
		);

		add_meta_box(
			'fieldforge_location',
			__( 'Location Rules', 'fieldforge' ),
			array( $this, 'render_location_meta_box' ),
			FieldForge_Field_Group::CPT,
			'normal',
			'default'
		);

		add_meta_box(
			'fieldforge_settings',
			__( 'Settings', 'fieldforge' ),
			array( $this, 'render_settings_meta_box' ),
			FieldForge_Field_Group::CPT,
			'side',
			'default'
		);
	}

	// ------------------------------------------------------------------
	// Meta box renderers
	// ------------------------------------------------------------------

	public function render_fields_meta_box( WP_Post $post ): void {
		wp_nonce_field( 'fieldforge_save_group', 'fieldforge_group_nonce' );

		$fields      = get_post_meta( $post->ID, '_fieldforge_fields', true );
		$fields      = is_array( $fields ) ? $fields : array();
		$types       = $this->registry->get_all_types();
		$type_labels = $this->get_type_labels();

		echo '<div id="fieldforge-fields-editor" data-types="' . esc_attr( wp_json_encode( $type_labels ) ) . '">';
		echo '<div class="fieldforge-fields-list" id="fieldforge-fields-list">';

		if ( empty( $fields ) ) {
			echo '<div class="fieldforge-empty-state">';
			echo '<span class="dashicons dashicons-editor-table fieldforge-empty-state__icon"></span>';
			echo '<p class="fieldforge-empty-state__title">' . esc_html__( 'No fields yet', 'fieldforge' ) . '</p>';
			echo '<p class="fieldforge-empty-state__desc">' . esc_html__( 'Click + Add Field to define the first field in this group.', 'fieldforge' ) . '</p>';
			echo '</div>';
		} else {
			foreach ( $fields as $index => $field ) {
				$this->render_field_row( $field, $index, $type_labels );
			}
		}

		echo '</div>'; // .fieldforge-fields-list

		echo '<div class="fieldforge-add-field-wrap">';
		echo '<button type="button" class="button fieldforge-add-field">';
		echo '<span class="dashicons dashicons-plus-alt2"></span> ';
		echo esc_html__( 'Add Field', 'fieldforge' );
		echo '</button>';
		echo '</div>'; // .fieldforge-add-field-wrap
		echo '</div>'; // #fieldforge-fields-editor
	}

	private function render_field_row( array $field, int $index, array $type_labels ): void {
		$type  = $field['type'] ?? 'text';
		$label = $field['label'] ?? '';
		$name  = $field['name'] ?? '';
		$key   = $field['key'] ?? 'field_' . uniqid();
		$p     = 'fieldforge_fields[' . $index . ']';
		$cat   = $this->get_type_category( $type );
		$icon  = $this->get_type_icon( $type );

		echo '<div class="fieldforge-field-row" data-index="' . esc_attr( (string) $index ) . '" data-category="' . esc_attr( $cat ) . '">';
		echo '<div class="fieldforge-field-row-header">';
		echo '<span class="fieldforge-drag-handle" title="' . esc_attr__( 'Drag to reorder', 'fieldforge' ) . '"><span class="dashicons dashicons-menu"></span></span>';
		echo '<span class="fieldforge-field-type-icon ff-type-icon--' . esc_attr( $cat ) . '"><span class="dashicons ' . esc_attr( $icon ) . '"></span></span>';
		echo '<span class="fieldforge-field-label-preview">' . esc_html( $label ? $label : __( 'New Field', 'fieldforge' ) ) . '</span>';
		if ( $name ) {
			echo '<span class="fieldforge-field-name-preview ff-muted">(' . esc_html( $name ) . ')</span>';
		}
		echo '<span class="fieldforge-field-type-badge ff-badge--' . esc_attr( $cat ) . '">' . esc_html( $type_labels[ $type ] ?? $type ) . '</span>';
		echo '<div class="fieldforge-field-row-actions">';
		echo '<button type="button" class="ff-btn-icon fieldforge-duplicate-field" title="' . esc_attr__( 'Duplicate', 'fieldforge' ) . '"><span class="dashicons dashicons-admin-page"></span></button>';
		echo '<button type="button" class="ff-btn-icon fieldforge-toggle-field" title="' . esc_attr__( 'Edit / Collapse', 'fieldforge' ) . '"><span class="dashicons dashicons-arrow-down-alt2"></span></button>';
		echo '<button type="button" class="ff-btn-icon ff-btn-icon--danger fieldforge-remove-field" title="' . esc_attr__( 'Delete', 'fieldforge' ) . '"><span class="dashicons dashicons-trash"></span></button>';
		echo '</div>'; // .fieldforge-field-row-actions
		echo '</div>'; // .fieldforge-field-row-header

		echo '<div class="fieldforge-field-row-body" style="display:none">';
		echo '<input type="hidden" name="' . esc_attr( $p ) . '[key]" value="' . esc_attr( $key ) . '" />';

		$this->render_common_field_settings( $p, $field, $type_labels, $cat, $icon );
		$this->render_type_specific_settings( $p, $field );
		$this->render_conditional_logic_section( $p, $field );

		echo '</div>'; // .fieldforge-field-row-body
		echo '</div>'; // .fieldforge-field-row
	}

	private function render_common_field_settings( string $prefix, array $field, array $type_labels, string $cat = '', string $icon = '' ): void {
		$type = $field['type'] ?? 'text';
		if ( ! $cat ) {
			$cat = $this->get_type_category( $type );
		}
		if ( ! $icon ) {
			$icon = $this->get_type_icon( $type );
		}
		?>
		<div class="ff-field-settings-grid">
			<div class="ff-field-setting">
				<label><?php esc_html_e( 'Field Label', 'fieldforge' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $prefix ); ?>[label]" value="<?php echo esc_attr( $field['label'] ?? '' ); ?>" class="widefat fieldforge-field-label-input" />
			</div>
			<div class="ff-field-setting">
				<label><?php esc_html_e( 'Field Name', 'fieldforge' ); ?> <span class="ff-muted">(slug)</span></label>
				<input type="text" name="<?php echo esc_attr( $prefix ); ?>[name]" value="<?php echo esc_attr( $field['name'] ?? '' ); ?>" class="widefat fieldforge-field-name-input" />
				<p class="ff-field-setting-help ff-name-error" style="display:none;color:#d63638"></p>
			</div>
			<div class="ff-field-setting">
				<label><?php esc_html_e( 'Field Type', 'fieldforge' ); ?></label>
				<div class="ff-type-picker-trigger">
					<span class="ff-type-picker-icon dashicons <?php echo esc_attr( $icon ); ?>"></span>
					<span class="ff-type-picker-label"><?php echo esc_html( $type_labels[ $type ] ?? $type ); ?></span>
					<span class="dashicons dashicons-arrow-down-alt2"></span>
					<input type="hidden" name="<?php echo esc_attr( $prefix ); ?>[type]" value="<?php echo esc_attr( $type ); ?>" class="fieldforge-type-hidden" />
				</div>
			</div>
			<div class="ff-field-setting">
				<label><?php esc_html_e( 'Instructions', 'fieldforge' ); ?></label>
				<textarea name="<?php echo esc_attr( $prefix ); ?>[instructions]" rows="2" class="widefat"><?php echo esc_textarea( $field['instructions'] ?? '' ); ?></textarea>
			</div>
			<div class="ff-field-setting ff-field-setting--inline">
				<label>
					<input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[required]" value="1"<?php checked( ! empty( $field['required'] ) ); ?> />
					<?php esc_html_e( 'Required field', 'fieldforge' ); ?>
				</label>
			</div>
			<div class="ff-field-setting">
				<label><?php esc_html_e( 'Default Value', 'fieldforge' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $prefix ); ?>[default_value]" value="<?php echo esc_attr( $field['default_value'] ?? '' ); ?>" class="widefat" />
			</div>
			<div class="ff-field-setting">
				<label><?php esc_html_e( 'Placeholder', 'fieldforge' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $prefix ); ?>[placeholder]" value="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>" class="widefat" />
			</div>
		</div>
		<?php
	}

	private function render_type_specific_settings( string $prefix, array $field ): void {
		$type = $field['type'] ?? 'text';

		switch ( $type ) {
			case 'checkbox':
			case 'radio':
				$this->render_choices_setting( $prefix, $field );
				break;

			case 'select':
				$this->render_choices_setting( $prefix, $field );
				?>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Allow Multiple', 'fieldforge' ); ?></th>
						<td><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[multiple]" value="1"<?php checked( ! empty( $field['multiple'] ) ); ?> /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Allow Null', 'fieldforge' ); ?></th>
						<td><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[allow_null]" value="1"<?php checked( ! empty( $field['allow_null'] ) ); ?> /></td>
					</tr>
				</table>
				<?php
				break;

			case 'number':
				?>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Min', 'fieldforge' ); ?></th>
						<td><input type="number" name="<?php echo esc_attr( $prefix ); ?>[min]" value="<?php echo esc_attr( (string) ( $field['min'] ?? '' ) ); ?>" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Max', 'fieldforge' ); ?></th>
						<td><input type="number" name="<?php echo esc_attr( $prefix ); ?>[max]" value="<?php echo esc_attr( (string) ( $field['max'] ?? '' ) ); ?>" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Step', 'fieldforge' ); ?></th>
						<td><input type="number" step="any" name="<?php echo esc_attr( $prefix ); ?>[step]" value="<?php echo esc_attr( (string) ( $field['step'] ?? '' ) ); ?>" /></td>
					</tr>
				</table>
				<?php
				break;

			case 'image':
			case 'file':
				?>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Return Format', 'fieldforge' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( $prefix ); ?>[return_format]">
								<option value="id"<?php selected( $field['return_format'] ?? 'id', 'id' ); ?>><?php esc_html_e( 'ID', 'fieldforge' ); ?></option>
								<option value="url"<?php selected( $field['return_format'] ?? 'id', 'url' ); ?>><?php esc_html_e( 'URL', 'fieldforge' ); ?></option>
								<option value="array"<?php selected( $field['return_format'] ?? 'id', 'array' ); ?>><?php esc_html_e( 'Array', 'fieldforge' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
				<?php
				break;

			case 'gallery':
				?>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Return Format', 'fieldforge' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( $prefix ); ?>[return_format]">
								<option value="id"<?php selected( $field['return_format'] ?? 'id', 'id' ); ?>><?php esc_html_e( 'IDs', 'fieldforge' ); ?></option>
								<option value="url"<?php selected( $field['return_format'] ?? 'id', 'url' ); ?>><?php esc_html_e( 'URLs', 'fieldforge' ); ?></option>
								<option value="array"<?php selected( $field['return_format'] ?? 'id', 'array' ); ?>><?php esc_html_e( 'Array', 'fieldforge' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
				<?php
				break;

			case 'wysiwyg':
				?>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Tabs', 'fieldforge' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( $prefix ); ?>[tabs]">
								<option value="all"<?php selected( $field['tabs'] ?? 'all', 'all' ); ?>><?php esc_html_e( 'Visual & Text', 'fieldforge' ); ?></option>
								<option value="visual"<?php selected( $field['tabs'] ?? 'all', 'visual' ); ?>><?php esc_html_e( 'Visual Only', 'fieldforge' ); ?></option>
								<option value="text"<?php selected( $field['tabs'] ?? 'all', 'text' ); ?>><?php esc_html_e( 'Text Only', 'fieldforge' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Toolbar', 'fieldforge' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( $prefix ); ?>[toolbar]">
								<option value="full"<?php selected( $field['toolbar'] ?? 'full', 'full' ); ?>><?php esc_html_e( 'Full', 'fieldforge' ); ?></option>
								<option value="basic"<?php selected( $field['toolbar'] ?? 'full', 'basic' ); ?>><?php esc_html_e( 'Basic', 'fieldforge' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Media Upload', 'fieldforge' ); ?></th>
						<td><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[media_upload]" value="1"<?php checked( $field['media_upload'] ?? true ); ?> /></td>
					</tr>
				</table>
				<?php
				break;

			case 'post_object':
				?>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Return Format', 'fieldforge' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( $prefix ); ?>[return_format]">
								<option value="object"<?php selected( $field['return_format'] ?? 'object', 'object' ); ?>><?php esc_html_e( 'Post Object', 'fieldforge' ); ?></option>
								<option value="id"<?php selected( $field['return_format'] ?? 'object', 'id' ); ?>><?php esc_html_e( 'ID', 'fieldforge' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Allow Multiple', 'fieldforge' ); ?></th>
						<td><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[multiple]" value="1"<?php checked( ! empty( $field['multiple'] ) ); ?> /></td>
					</tr>
				</table>
				<?php
				break;

			case 'taxonomy':
				?>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Taxonomy', 'fieldforge' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( $prefix ); ?>[taxonomy]">
								<?php foreach ( get_taxonomies( array( 'public' => true ), 'objects' ) as $tax ) : ?>
									<option value="<?php echo esc_attr( $tax->name ); ?>"<?php selected( $field['taxonomy'] ?? 'category', $tax->name ); ?>><?php echo esc_html( $tax->label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Field Type', 'fieldforge' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( $prefix ); ?>[field_type]">
								<option value="checkbox"<?php selected( $field['field_type'] ?? 'checkbox', 'checkbox' ); ?>><?php esc_html_e( 'Checkbox', 'fieldforge' ); ?></option>
								<option value="multi_select"<?php selected( $field['field_type'] ?? 'checkbox', 'multi_select' ); ?>><?php esc_html_e( 'Multi Select', 'fieldforge' ); ?></option>
								<option value="radio"<?php selected( $field['field_type'] ?? 'checkbox', 'radio' ); ?>><?php esc_html_e( 'Radio', 'fieldforge' ); ?></option>
								<option value="select"<?php selected( $field['field_type'] ?? 'checkbox', 'select' ); ?>><?php esc_html_e( 'Select', 'fieldforge' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Return Format', 'fieldforge' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( $prefix ); ?>[return_format]">
								<option value="id"<?php selected( $field['return_format'] ?? 'id', 'id' ); ?>><?php esc_html_e( 'ID', 'fieldforge' ); ?></option>
								<option value="name"<?php selected( $field['return_format'] ?? 'id', 'name' ); ?>><?php esc_html_e( 'Name', 'fieldforge' ); ?></option>
								<option value="slug"<?php selected( $field['return_format'] ?? 'id', 'slug' ); ?>><?php esc_html_e( 'Slug', 'fieldforge' ); ?></option>
								<option value="object"<?php selected( $field['return_format'] ?? 'id', 'object' ); ?>><?php esc_html_e( 'Term Object', 'fieldforge' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
				<?php
				break;

			case 'user':
				?>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Return Format', 'fieldforge' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( $prefix ); ?>[return_format]">
								<option value="id"<?php selected( $field['return_format'] ?? 'id', 'id' ); ?>><?php esc_html_e( 'ID', 'fieldforge' ); ?></option>
								<option value="object"<?php selected( $field['return_format'] ?? 'id', 'object' ); ?>><?php esc_html_e( 'User Object', 'fieldforge' ); ?></option>
								<option value="array"<?php selected( $field['return_format'] ?? 'id', 'array' ); ?>><?php esc_html_e( 'Array', 'fieldforge' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Allow Multiple', 'fieldforge' ); ?></th>
						<td><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[multiple]" value="1"<?php checked( ! empty( $field['multiple'] ) ); ?> /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Filter by Role', 'fieldforge' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( $prefix ); ?>[role]">
								<option value=""><?php esc_html_e( '— Any Role —', 'fieldforge' ); ?></option>
								<?php foreach ( wp_roles()->role_names as $role_slug => $role_name ) : ?>
									<option value="<?php echo esc_attr( $role_slug ); ?>"<?php selected( $field['role'] ?? '', $role_slug ); ?>><?php echo esc_html( $role_name ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>
				<?php
				break;

			case 'repeater':
				$sub_fields = $field['sub_fields'] ?? array();
				$sp_base    = $prefix . '[sub_fields]';
				?>
				<div class="fieldforge-sub-fields-editor" data-name-prefix="<?php echo esc_attr( $sp_base ); ?>">
					<h4><?php esc_html_e( 'Sub Fields', 'fieldforge' ); ?></h4>
					<div class="fieldforge-sub-fields-list">
					<?php foreach ( $sub_fields as $si => $sub ) : ?>
						<?php $this->render_sub_field_row( $sp_base . '[' . $si . ']', $sub, $si ); ?>
					<?php endforeach; ?>
					</div>
					<button type="button" class="button fieldforge-add-sub-field" data-name-prefix="<?php echo esc_attr( $sp_base ); ?>">
						<?php esc_html_e( '+ Add Sub Field', 'fieldforge' ); ?>
					</button>
					<table class="form-table" style="margin-top:12px">
						<tr>
							<th><?php esc_html_e( 'Min Rows', 'fieldforge' ); ?></th>
							<td><input type="number" min="0" name="<?php echo esc_attr( $prefix ); ?>[min]" value="<?php echo esc_attr( (string) ( $field['min'] ?? 0 ) ); ?>" class="small-text" /></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Max Rows', 'fieldforge' ); ?></th>
							<td><input type="number" min="0" name="<?php echo esc_attr( $prefix ); ?>[max]" value="<?php echo esc_attr( (string) ( $field['max'] ?? 0 ) ); ?>" class="small-text" /></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Add Row Label', 'fieldforge' ); ?></th>
							<td><input type="text" name="<?php echo esc_attr( $prefix ); ?>[button_label]" value="<?php echo esc_attr( $field['button_label'] ?? __( 'Add Row', 'fieldforge' ) ); ?>" class="regular-text" /></td>
						</tr>
					</table>
				</div>
				<?php
				break;

			case 'flexible_content':
				$layouts = $field['layouts'] ?? array();
				$lp_base = $prefix . '[layouts]';
				?>
				<div class="fieldforge-layouts-editor" data-name-prefix="<?php echo esc_attr( $lp_base ); ?>">
					<h4><?php esc_html_e( 'Layouts', 'fieldforge' ); ?></h4>
					<div class="fieldforge-layouts-list">
					<?php foreach ( $layouts as $li => $layout ) : ?>
						<?php $this->render_layout_row( $lp_base . '[' . $li . ']', $layout, $li ); ?>
					<?php endforeach; ?>
					</div>
					<button type="button" class="button fieldforge-add-layout" data-name-prefix="<?php echo esc_attr( $lp_base ); ?>">
						<?php esc_html_e( '+ Add Layout', 'fieldforge' ); ?>
					</button>
					<table class="form-table" style="margin-top:12px">
						<tr>
							<th><?php esc_html_e( 'Min Layouts', 'fieldforge' ); ?></th>
							<td><input type="number" min="0" name="<?php echo esc_attr( $prefix ); ?>[min]" value="<?php echo esc_attr( (string) ( $field['min'] ?? 0 ) ); ?>" class="small-text" /></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Max Layouts', 'fieldforge' ); ?></th>
							<td><input type="number" min="0" name="<?php echo esc_attr( $prefix ); ?>[max]" value="<?php echo esc_attr( (string) ( $field['max'] ?? 0 ) ); ?>" class="small-text" /></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Add Button Label', 'fieldforge' ); ?></th>
							<td><input type="text" name="<?php echo esc_attr( $prefix ); ?>[button_label]" value="<?php echo esc_attr( $field['button_label'] ?? __( 'Add Layout', 'fieldforge' ) ); ?>" class="regular-text" /></td>
						</tr>
					</table>
				</div>
				<?php
				break;

			case 'text':
				?>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Max Length', 'fieldforge' ); ?></th>
						<td><input type="number" min="0" name="<?php echo esc_attr( $prefix ); ?>[maxlength]" value="<?php echo esc_attr( (string) ( $field['maxlength'] ?? '' ) ); ?>" class="small-text" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Prepend', 'fieldforge' ); ?></th>
						<td><input type="text" name="<?php echo esc_attr( $prefix ); ?>[prepend]" value="<?php echo esc_attr( $field['prepend'] ?? '' ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Append', 'fieldforge' ); ?></th>
						<td><input type="text" name="<?php echo esc_attr( $prefix ); ?>[append]" value="<?php echo esc_attr( $field['append'] ?? '' ); ?>" class="regular-text" /></td>
					</tr>
				</table>
				<?php
				break;

			case 'textarea':
				?>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Rows', 'fieldforge' ); ?></th>
						<td><input type="number" min="1" name="<?php echo esc_attr( $prefix ); ?>[rows]" value="<?php echo esc_attr( (string) ( $field['rows'] ?? 4 ) ); ?>" class="small-text" /></td>
					</tr>
				</table>
				<?php
				break;

			case 'date_picker':
				?>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Display Format', 'fieldforge' ); ?></th>
						<td>
							<input type="text" name="<?php echo esc_attr( $prefix ); ?>[display_format]" value="<?php echo esc_attr( $field['display_format'] ?? 'd/m/Y' ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'PHP date() format for admin display.', 'fieldforge' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Return Format', 'fieldforge' ); ?></th>
						<td>
							<input type="text" name="<?php echo esc_attr( $prefix ); ?>[return_format]" value="<?php echo esc_attr( $field['return_format'] ?? 'Ymd' ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'PHP date() format returned by fieldforge_get().', 'fieldforge' ); ?></p>
						</td>
					</tr>
				</table>
				<?php
				break;

			case 'time_picker':
				?>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Display Format', 'fieldforge' ); ?></th>
						<td>
							<input type="text" name="<?php echo esc_attr( $prefix ); ?>[display_format]" value="<?php echo esc_attr( $field['display_format'] ?? 'g:i a' ); ?>" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Return Format', 'fieldforge' ); ?></th>
						<td>
							<input type="text" name="<?php echo esc_attr( $prefix ); ?>[return_format]" value="<?php echo esc_attr( $field['return_format'] ?? 'H:i:s' ); ?>" class="regular-text" />
						</td>
					</tr>
				</table>
				<?php
				break;

			case 'true_false':
				?>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Message', 'fieldforge' ); ?></th>
						<td>
							<input type="text" name="<?php echo esc_attr( $prefix ); ?>[message]" value="<?php echo esc_attr( $field['message'] ?? '' ); ?>" class="widefat" />
							<p class="description"><?php esc_html_e( 'Text shown beside the checkbox.', 'fieldforge' ); ?></p>
						</td>
					</tr>
				</table>
				<?php
				break;

			case 'link':
				?>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Return Format', 'fieldforge' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( $prefix ); ?>[return_format]">
								<option value="array"<?php selected( $field['return_format'] ?? 'array', 'array' ); ?>><?php esc_html_e( 'Array (url, title, target)', 'fieldforge' ); ?></option>
								<option value="url"<?php selected( $field['return_format'] ?? 'array', 'url' ); ?>><?php esc_html_e( 'URL string only', 'fieldforge' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
				<?php
				break;

			case 'message':
				?>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Message', 'fieldforge' ); ?></th>
						<td>
							<textarea name="<?php echo esc_attr( $prefix ); ?>[message_content]" rows="4" class="widefat"><?php echo esc_textarea( $field['message_content'] ?? '' ); ?></textarea>
							<p class="description"><?php esc_html_e( 'HTML allowed. This text is shown to editors but not stored.', 'fieldforge' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'New Lines', 'fieldforge' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( $prefix ); ?>[new_lines]">
								<option value="wpautop"<?php selected( $field['new_lines'] ?? 'wpautop', 'wpautop' ); ?>><?php esc_html_e( 'Automatically add paragraphs', 'fieldforge' ); ?></option>
								<option value="br"<?php selected( $field['new_lines'] ?? 'wpautop', 'br' ); ?>><?php esc_html_e( 'Automatically add &lt;br&gt;', 'fieldforge' ); ?></option>
								<option value=""<?php selected( $field['new_lines'] ?? 'wpautop', '' ); ?>><?php esc_html_e( 'No formatting', 'fieldforge' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
				<?php
				break;
		}
	}

	/**
	 * Render the conditional logic builder panel for a PHP-rendered field row.
	 * Mirrors the HTML produced by buildFieldRow() in admin.js so the same JS
	 * event handlers work for both saved (PHP) and newly-added (JS) rows.
	 *
	 * @param string $prefix  Form prefix, e.g. fieldforge_fields[0]
	 * @param array  $field   Field config array.
	 */
	private function render_conditional_logic_section( string $prefix, array $field ): void {
		$enabled = ! empty( $field['conditional_logic'] ) ? 1 : 0;
		$rules   = $field['conditional_logic_rules'] ?? array();
		$operators = array(
			'=='         => __( 'is equal to', 'fieldforge' ),
			'!='         => __( 'is not equal to', 'fieldforge' ),
			'>'          => __( 'is greater than', 'fieldforge' ),
			'<'          => __( 'is less than', 'fieldforge' ),
			'>='         => __( 'is greater than or equal to', 'fieldforge' ),
			'<='         => __( 'is less than or equal to', 'fieldforge' ),
			'==empty'    => __( 'has no value', 'fieldforge' ),
			'!=empty'    => __( 'has any value', 'fieldforge' ),
			'==contains' => __( 'contains', 'fieldforge' ),
			'!=contains' => __( 'does not contain', 'fieldforge' ),
		);
		?>
		<div class="ff-conditional-logic-builder" data-prefix="<?php echo esc_attr( $prefix ); ?>">
			<div class="ff-cl-toggle">
				<label class="ff-toggle-label">
					<input type="checkbox" class="ff-cl-enable"
						name="<?php echo esc_attr( $prefix ); ?>[conditional_logic]"
						value="1"<?php checked( $enabled, 1 ); ?> />
					<span class="ff-toggle-track"></span>
					<span class="ff-toggle-text"><?php esc_html_e( 'Conditional Logic', 'fieldforge' ); ?></span>
				</label>
			</div>
			<div class="ff-cl-rules"<?php echo $enabled ? '' : ' style="display:none"'; ?>>
				<p class="ff-cl-intro"><?php esc_html_e( 'Show this field if', 'fieldforge' ); ?></p>
				<div class="ff-cl-rules-list">
					<?php foreach ( $rules as $ri => $rule ) : ?>
						<div class="ff-cl-rule">
							<input type="text" class="ff-cl-field-input"
								name="<?php echo esc_attr( $prefix ); ?>[cl_rules][<?php echo (int) $ri; ?>][field]"
								value="<?php echo esc_attr( $rule['field'] ?? '' ); ?>"
								placeholder="<?php esc_attr_e( 'Field name', 'fieldforge' ); ?>" />
							<select name="<?php echo esc_attr( $prefix ); ?>[cl_rules][<?php echo (int) $ri; ?>][operator]"
								class="ff-cl-operator-select">
								<?php foreach ( $operators as $op_val => $op_label ) : ?>
									<option value="<?php echo esc_attr( $op_val ); ?>"<?php selected( $rule['operator'] ?? '==', $op_val ); ?>><?php echo esc_html( $op_label ); ?></option>
								<?php endforeach; ?>
							</select>
							<input type="text" class="ff-cl-value-input"
								name="<?php echo esc_attr( $prefix ); ?>[cl_rules][<?php echo (int) $ri; ?>][value]"
								value="<?php echo esc_attr( $rule['value'] ?? '' ); ?>"
								placeholder="<?php esc_attr_e( 'Value', 'fieldforge' ); ?>" />
							<button type="button" class="button button-link-delete ff-cl-remove-rule"
								title="<?php esc_attr_e( 'Remove rule', 'fieldforge' ); ?>">✕</button>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button ff-btn ff-btn--sm ff-cl-add-rule">
					<?php esc_html_e( '+ Add Rule', 'fieldforge' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	private function render_choices_setting( string $prefix, array $field ): void {
		$choices = $field['choices'] ?? array();
		$lines   = array();
		foreach ( $choices as $val => $label ) {
			$lines[] = $val . ' : ' . $label;
		}
		?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Choices', 'fieldforge' ); ?></th>
				<td>
					<textarea name="<?php echo esc_attr( $prefix ); ?>[choices_raw]" rows="6" class="widefat"
						placeholder="<?php esc_attr_e( 'red : Red\nblue : Blue', 'fieldforge' ); ?>"
					><?php echo esc_textarea( implode( "\n", $lines ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One per line. Format: value : Label', 'fieldforge' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	public function render_location_meta_box( WP_Post $post ): void {
		$location = get_post_meta( $post->ID, '_fieldforge_location', true );
		$location = is_array( $location ) ? $location : array();

		echo '<div id="fieldforge-location-editor">';
		echo '<p class="description">' . esc_html__( 'Show this field group when:', 'fieldforge' ) . '</p>';

		if ( empty( $location ) ) {
			$location = array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'post',
					),
				),
			);
		}

		foreach ( $location as $group_index => $or_group ) :
			echo '<div class="ff-location-group-wrap">';
			echo '<div class="fieldforge-location-group" data-group="' . esc_attr( (string) $group_index ) . '">';
			echo '<div class="fieldforge-location-rules">';
			foreach ( $or_group as $rule_index => $rule ) :
				$p = 'fieldforge_location[' . $group_index . '][' . $rule_index . ']';
				echo '<div class="fieldforge-location-rule">';
				// Param.
				echo '<select class="ff-location-param" name="' . esc_attr( $p ) . '[param]">';
				foreach ( $this->get_location_params() as $param_val => $param_label ) {
					echo '<option value="' . esc_attr( $param_val ) . '"' . selected( $rule['param'] ?? '', $param_val, false ) . '>' . esc_html( $param_label ) . '</option>';
				}
				echo '</select>';
				// Operator.
				echo '<select name="' . esc_attr( $p ) . '[operator]">';
				echo '<option value="=="' . selected( $rule['operator'] ?? '==', '==', false ) . '>' . esc_html__( 'is equal to', 'fieldforge' ) . '</option>';
				echo '<option value="!="' . selected( $rule['operator'] ?? '==', '!=', false ) . '>' . esc_html__( 'is not equal to', 'fieldforge' ) . '</option>';
				echo '</select>';
				// Value — dynamic widget based on param.
				echo $this->render_location_value_widget( $p . '[value]', $rule['param'] ?? 'post_type', $rule['value'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped
				echo '<button type="button" class="ff-btn-icon ff-btn-icon--danger fieldforge-remove-rule" title="' . esc_attr__( 'Remove rule', 'fieldforge' ) . '"><span class="dashicons dashicons-minus"></span></button>';
				echo '</div>'; // .fieldforge-location-rule
			endforeach;
			echo '</div>'; // .fieldforge-location-rules
			echo '<div class="ff-location-group-footer">';
			echo '<button type="button" class="ff-btn ff-btn--sm fieldforge-add-rule">+ ' . esc_html__( 'and', 'fieldforge' ) . '</button>';
			echo '<button type="button" class="ff-btn ff-btn--sm ff-btn--danger fieldforge-remove-group">' . esc_html__( 'Remove Group', 'fieldforge' ) . '</button>';
			echo '</div>'; // .ff-location-group-footer
			echo '</div>'; // .fieldforge-location-group
			if ( $group_index < count( $location ) - 1 ) {
				echo '<div class="fieldforge-location-or"><span>' . esc_html__( 'or', 'fieldforge' ) . '</span></div>';
			}
			echo '</div>'; // .ff-location-group-wrap
		endforeach;

		echo '<button type="button" class="ff-btn ff-btn--sm fieldforge-add-location-group"><span class="dashicons dashicons-plus-alt2"></span> ' . esc_html__( 'Add OR Group', 'fieldforge' ) . '</button>';
		echo '</div>'; // #fieldforge-location-editor
	}

	public function render_settings_meta_box( WP_Post $post ): void {
		$pos         = get_post_meta( $post->ID, '_fieldforge_position', true );
		$position    = $pos ? $pos : 'normal';
		$desc        = get_post_meta( $post->ID, '_fieldforge_description', true );
		$description = $desc ? $desc : '';
		?>
		<table class="form-table">
			<tr>
				<th><label for="fieldforge_description"><?php esc_html_e( 'Description', 'fieldforge' ); ?></label></th>
				<td>
					<textarea id="fieldforge_description" name="fieldforge_description" rows="3" class="widefat"><?php echo esc_textarea( $description ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Optional. Visible in the field group list.', 'fieldforge' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Position', 'fieldforge' ); ?></th>
				<td>
					<select name="fieldforge_position">
						<option value="normal"<?php selected( $position, 'normal' ); ?>><?php esc_html_e( 'Normal', 'fieldforge' ); ?></option>
						<option value="side"<?php selected( $position, 'side' ); ?>><?php esc_html_e( 'Side', 'fieldforge' ); ?></option>
						<option value="acf_after_title"<?php selected( $position, 'acf_after_title' ); ?>><?php esc_html_e( 'After Title', 'fieldforge' ); ?></option>
					</select>
				</td>
			</tr>
		</table>
		<?php
		// Export button.
		if ( $post->ID ) {
			echo '<hr />';
			echo '<button type="button" class="button fieldforge-export-group" data-id="' . esc_attr( $post->ID ) . '">' . esc_html__( 'Export JSON', 'fieldforge' ) . '</button>';
		}
	}

	// ------------------------------------------------------------------
	// Save
	// ------------------------------------------------------------------

	public function save_group( int $post_id, WP_Post $post ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['fieldforge_group_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['fieldforge_group_nonce'] ), 'fieldforge_save_group' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Fields.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified above; passed through sanitize_fields().
		$raw_fields = isset( $_POST['fieldforge_fields'] ) ? wp_unslash( $_POST['fieldforge_fields'] ) : array();
		$fields     = $this->sanitize_fields( (array) $raw_fields );
		update_post_meta( $post_id, '_fieldforge_fields', $fields );

		// Location.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified above; passed through sanitize_location().
		$raw_location = isset( $_POST['fieldforge_location'] ) ? wp_unslash( $_POST['fieldforge_location'] ) : array();
		$location     = $this->sanitize_location( (array) $raw_location );
		update_post_meta( $post_id, '_fieldforge_location', $location );

		// Settings.
		$position    = sanitize_text_field( wp_unslash( $_POST['fieldforge_position'] ?? 'normal' ) );
		$description = sanitize_textarea_field( wp_unslash( $_POST['fieldforge_description'] ?? '' ) );
		update_post_meta( $post_id, '_fieldforge_position', $position );
		update_post_meta( $post_id, '_fieldforge_description', $description );
	}

	// ------------------------------------------------------------------
	// Sanitization helpers
	// ------------------------------------------------------------------

	private function sanitize_fields( array $raw ): array {
		$fields = array();
		foreach ( $raw as $f ) {
			if ( ! is_array( $f ) ) {
				continue;
			}
			$type = sanitize_key( $f['type'] ?? 'text' );

			$field = array(
				'key'           => sanitize_key( $f['key'] ?? 'field_' . uniqid() ),
				'label'         => sanitize_text_field( $f['label'] ?? '' ),
				'name'          => sanitize_key( $f['name'] ?? '' ),
				'type'          => $type,
				'instructions'  => wp_kses_post( $f['instructions'] ?? '' ),
				'required'      => ! empty( $f['required'] ) ? 1 : 0,
				'default_value' => sanitize_text_field( $f['default_value'] ?? '' ),
				'placeholder'   => sanitize_text_field( $f['placeholder'] ?? '' ),
			);

			// Parse choices from raw textarea.
			if ( isset( $f['choices_raw'] ) ) {
				$field['choices'] = $this->parse_choices( $f['choices_raw'] );
			} elseif ( isset( $f['choices'] ) && is_array( $f['choices'] ) ) {
				$field['choices'] = array_map( 'sanitize_text_field', $f['choices'] );
			}

			if ( 'number' === $type ) {
				$field['min']  = '' !== ( $f['min'] ?? '' ) ? (float) $f['min'] : '';
				$field['max']  = '' !== ( $f['max'] ?? '' ) ? (float) $f['max'] : '';
				$field['step'] = '' !== ( $f['step'] ?? '' ) ? (float) $f['step'] : '';
			}

			if ( 'wysiwyg' === $type ) {
				$field['tabs']         = sanitize_text_field( $f['tabs'] ?? 'all' );
				$field['toolbar']      = sanitize_text_field( $f['toolbar'] ?? 'full' );
				$field['media_upload'] = ! empty( $f['media_upload'] ) ? 1 : 0;
			}

			if ( in_array( $type, array( 'image', 'file', 'gallery', 'post_object', 'taxonomy', 'user' ), true ) ) {
				$field['return_format'] = sanitize_text_field( $f['return_format'] ?? '' );
			}

			if ( in_array( $type, array( 'post_object', 'user' ), true ) ) {
				$field['multiple'] = ! empty( $f['multiple'] ) ? 1 : 0;
			}

			if ( 'post_object' === $type ) {
				$field['post_type'] = array_map( 'sanitize_key', (array) ( $f['post_type'] ?? array() ) );
			}

			if ( 'taxonomy' === $type ) {
				$field['taxonomy']   = sanitize_key( $f['taxonomy'] ?? 'category' );
				$field['field_type'] = sanitize_text_field( $f['field_type'] ?? 'checkbox' );
			}

			if ( 'user' === $type ) {
				$field['role'] = sanitize_text_field( $f['role'] ?? '' );
			}

			if ( 'repeater' === $type ) {
				$field['sub_fields']   = ! empty( $f['sub_fields'] ) ? $this->sanitize_fields( (array) $f['sub_fields'] ) : array();
				$field['min']          = absint( $f['min'] ?? 0 );
				$field['max']          = absint( $f['max'] ?? 0 );
				$field['layout']       = sanitize_text_field( $f['layout'] ?? 'table' );
				$field['button_label'] = sanitize_text_field( $f['button_label'] ?? __( 'Add Row', 'fieldforge' ) );
			}

			if ( 'flexible_content' === $type && ! empty( $f['layouts'] ) ) {
				$field['layouts'] = $this->sanitize_layouts( (array) $f['layouts'] );
			}

			// Text-specific settings.
			if ( 'text' === $type ) {
				$field['maxlength'] = '' !== ( $f['maxlength'] ?? '' ) ? absint( $f['maxlength'] ) : '';
				$field['prepend']   = sanitize_text_field( $f['prepend'] ?? '' );
				$field['append']    = sanitize_text_field( $f['append'] ?? '' );
			}

			// Textarea-specific.
			if ( 'textarea' === $type ) {
				$field['rows'] = '' !== ( $f['rows'] ?? '' ) ? absint( $f['rows'] ) : 4;
			}

			// Select-specific.
			if ( 'select' === $type ) {
				$field['multiple']      = ! empty( $f['multiple'] ) ? 1 : 0;
				$field['allow_null']    = ! empty( $f['allow_null'] ) ? 1 : 0;
				$field['return_format'] = sanitize_text_field( $f['return_format'] ?? 'value' );
			}

			// Date/time return format.
			if ( in_array( $type, array( 'date_picker', 'time_picker' ), true ) ) {
				$field['display_format'] = sanitize_text_field( $f['display_format'] ?? '' );
				$field['return_format']  = sanitize_text_field( $f['return_format'] ?? '' );
			}

			// True / false.
			if ( 'true_false' === $type ) {
				$field['message']     = sanitize_text_field( $f['message'] ?? '' );
				$field['ui_on_text']  = sanitize_text_field( $f['ui_on_text'] ?? '' );
				$field['ui_off_text'] = sanitize_text_field( $f['ui_off_text'] ?? '' );
			}

			// Link.
			if ( 'link' === $type ) {
				$field['return_format'] = in_array( $f['return_format'] ?? 'array', array( 'array', 'url' ), true ) ? $f['return_format'] : 'array';
			}

			// Message.
			if ( 'message' === $type ) {
				$field['message_content'] = wp_kses_post( $f['message_content'] ?? '' );
				$field['new_lines']       = in_array( $f['new_lines'] ?? 'wpautop', array( 'wpautop', 'br', '' ), true ) ? $f['new_lines'] : 'wpautop';
				$field['esc_html']        = ! empty( $f['esc_html'] ) ? 1 : 0;
			}

			// Post object — allow_null + post_type list.
			if ( 'post_object' === $type ) {
				$field['allow_null'] = ! empty( $f['allow_null'] ) ? 1 : 0;
				$field['post_type']  = array_map( 'sanitize_key', (array) ( $f['post_type'] ?? array() ) );
			}

			// Conditional logic rules.
			$field['conditional_logic'] = ! empty( $f['conditional_logic'] ) ? 1 : 0;
			if ( $field['conditional_logic'] && ! empty( $f['cl_rules'] ) && is_array( $f['cl_rules'] ) ) {
				$cl_rules = array();
				foreach ( $f['cl_rules'] as $rule ) {
					if ( ! is_array( $rule ) || empty( $rule['field'] ) ) {
						continue;
					}
					$cl_rules[] = array(
						'field'    => sanitize_key( $rule['field'] ),
						'operator' => sanitize_text_field( $rule['operator'] ?? '==' ),
						'value'    => sanitize_text_field( $rule['value'] ?? '' ),
					);
				}
				$field['conditional_logic_rules'] = $cl_rules;
			}

			if ( $field['name'] ) {
				$fields[] = $field;
			}
		}
		return $fields;
	}

	private function parse_choices( string $raw ): array {
		$choices = array();
		foreach ( explode( "\n", $raw ) as $line ) {
			$line = trim( $line );
			if ( ! $line ) {
				continue;
			}
			if ( strpos( $line, ':' ) !== false ) {
				list( $val, $label )                            = explode( ':', $line, 2 );
				$choices[ trim( sanitize_text_field( $val ) ) ] = trim( sanitize_text_field( $label ) );
			} else {
				$choices[ sanitize_text_field( $line ) ] = sanitize_text_field( $line );
			}
		}
		return $choices;
	}

	private function sanitize_location( array $raw ): array {
		$location = array();
		foreach ( $raw as $or_group ) {
			$rules = array();
			foreach ( (array) $or_group as $rule ) {
				$rules[] = array(
					'param'    => sanitize_key( $rule['param'] ?? 'post_type' ),
					'operator' => in_array( $rule['operator'] ?? '', array( '==', '!=' ), true ) ? $rule['operator'] : '==',
					'value'    => sanitize_text_field( $rule['value'] ?? '' ),
				);
			}
			if ( $rules ) {
				$location[] = $rules;
			}
		}
		return $location;
	}

	private function sanitize_layouts( array $raw ): array {
		$layouts = array();
		foreach ( $raw as $l ) {
			if ( ! is_array( $l ) ) {
				continue;
			}
			$name = sanitize_key( $l['name'] ?? '' );
			if ( ! $name ) {
				continue;
			}
			$layouts[] = array(
				'name'       => $name,
				'label'      => sanitize_text_field( $l['label'] ?? '' ),
				'sub_fields' => ! empty( $l['sub_fields'] ) ? $this->sanitize_fields( (array) $l['sub_fields'] ) : array(),
			);
		}
		return $layouts;
	}

	/**
	 * Render a single sub-field row inside a Repeater editor.
	 *
	 * @param string $prefix  Full input name prefix, e.g. `fieldforge_fields[0][sub_fields][0]`.
	 * @param array  $sub     Sub-field config.
	 * @param int    $index   Sub-field index.
	 */
	private function render_sub_field_row( string $prefix, array $sub, int $index ): void {
		$type        = $sub['type'] ?? 'text';
		$type_labels = $this->get_type_labels();
		?>
		<?php $sub_cat = $this->get_type_category( $type ); ?>
		<div class="fieldforge-sub-field-row" data-index="<?php echo esc_attr( (string) $index ); ?>" data-category="<?php echo esc_attr( $sub_cat ); ?>">
			<div class="fieldforge-sub-field-header">
				<span class="fieldforge-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'fieldforge' ); ?>"><span class="dashicons dashicons-menu"></span></span>
				<span class="fieldforge-field-type-icon ff-type-icon--<?php echo esc_attr( $sub_cat ); ?>"><span class="dashicons <?php echo esc_attr( $this->get_type_icon( $type ) ); ?>"></span></span>
				<strong class="ff-sub-label-preview"><?php echo esc_html( $sub['label'] ?? __( '(sub field)', 'fieldforge' ) ); ?></strong>
				<span class="fieldforge-field-type-badge ff-badge--<?php echo esc_attr( $sub_cat ); ?>"><?php echo esc_html( $type_labels[ $type ] ?? $type ); ?></span>
				<div class="fieldforge-field-row-actions">
					<button type="button" class="ff-btn-icon fieldforge-toggle-sub-field" title="<?php esc_attr_e( 'Edit / Collapse', 'fieldforge' ); ?>"><span class="dashicons dashicons-arrow-down-alt2"></span></button>
					<button type="button" class="ff-btn-icon ff-btn-icon--danger fieldforge-remove-sub-field" title="<?php esc_attr_e( 'Delete', 'fieldforge' ); ?>"><span class="dashicons dashicons-trash"></span></button>
				</div>
			</div>
			<div class="fieldforge-sub-field-body" style="display:none">
				<input type="hidden" name="<?php echo esc_attr( $prefix ); ?>[key]" value="<?php echo esc_attr( $sub['key'] ?? 'field_' . uniqid() ); ?>" />
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Label', 'fieldforge' ); ?></th>
						<td><input type="text" name="<?php echo esc_attr( $prefix ); ?>[label]" value="<?php echo esc_attr( $sub['label'] ?? '' ); ?>" class="widefat fieldforge-sub-label-input" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Name', 'fieldforge' ); ?></th>
						<td><input type="text" name="<?php echo esc_attr( $prefix ); ?>[name]" value="<?php echo esc_attr( $sub['name'] ?? '' ); ?>" class="widefat fieldforge-sub-name-input" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Type', 'fieldforge' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( $prefix ); ?>[type]" class="fieldforge-sub-type-select">
								<?php foreach ( $type_labels as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>"<?php selected( $type, $slug ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Required', 'fieldforge' ); ?></th>
						<td><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[required]" value="1"<?php checked( ! empty( $sub['required'] ) ); ?> /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Instructions', 'fieldforge' ); ?></th>
						<td><textarea name="<?php echo esc_attr( $prefix ); ?>[instructions]" rows="2" class="widefat"><?php echo esc_textarea( $sub['instructions'] ?? '' ); ?></textarea></td>
					</tr>
				</table>
				<?php $this->render_type_specific_settings( $prefix, $sub ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a single layout row inside a Flexible Content editor.
	 *
	 * @param string $prefix  Full input name prefix.
	 * @param array  $layout  Layout config.
	 * @param int    $index   Layout index.
	 */
	private function render_layout_row( string $prefix, array $layout, int $index ): void {
		$sf_base = $prefix . '[sub_fields]';
		?>
		<div class="fieldforge-layout-row" data-index="<?php echo esc_attr( (string) $index ); ?>">
			<div class="fieldforge-layout-header">
				<span class="fieldforge-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'fieldforge' ); ?>"><span class="dashicons dashicons-menu"></span></span>
				<span class="fieldforge-field-type-icon ff-type-icon--layout"><span class="dashicons dashicons-layout"></span></span>
				<strong class="ff-layout-label-preview"><?php echo esc_html( $layout['label'] ?? __( '(layout)', 'fieldforge' ) ); ?></strong>
				<?php if ( ! empty( $layout['name'] ) ) : ?>
					<span class="fieldforge-field-name-preview ff-muted"><?php echo esc_html( '(' . $layout['name'] . ')' ); ?></span>
				<?php endif; ?>
				<div class="fieldforge-field-row-actions">
					<button type="button" class="ff-btn-icon fieldforge-toggle-layout" title="<?php esc_attr_e( 'Edit / Collapse', 'fieldforge' ); ?>"><span class="dashicons dashicons-arrow-down-alt2"></span></button>
					<button type="button" class="ff-btn-icon ff-btn-icon--danger fieldforge-remove-layout" title="<?php esc_attr_e( 'Delete', 'fieldforge' ); ?>"><span class="dashicons dashicons-trash"></span></button>
				</div>
			</div>
			<div class="fieldforge-layout-body" style="display:none">
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Label', 'fieldforge' ); ?></th>
						<td><input type="text" name="<?php echo esc_attr( $prefix ); ?>[label]" value="<?php echo esc_attr( $layout['label'] ?? '' ); ?>" class="widefat fieldforge-layout-label-input" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Name', 'fieldforge' ); ?></th>
						<td><input type="text" name="<?php echo esc_attr( $prefix ); ?>[name]" value="<?php echo esc_attr( $layout['name'] ?? '' ); ?>" class="widefat fieldforge-layout-name-input" /></td>
					</tr>
				</table>
				<div class="fieldforge-sub-fields-editor fieldforge-layout-subfields" data-name-prefix="<?php echo esc_attr( $sf_base ); ?>">
					<h5><?php esc_html_e( 'Sub Fields', 'fieldforge' ); ?></h5>
					<div class="fieldforge-sub-fields-list">
					<?php foreach ( $layout['sub_fields'] ?? array() as $si => $sf ) : ?>
						<?php $this->render_sub_field_row( $sf_base . '[' . $si . ']', $sf, $si ); ?>
					<?php endforeach; ?>
					</div>
					<div class="fieldforge-add-field-wrap" style="margin-top:8px">
						<button type="button" class="button fieldforge-add-sub-field" data-name-prefix="<?php echo esc_attr( $sf_base ); ?>">
							<span class="dashicons dashicons-plus-alt2"></span>
							<?php esc_html_e( 'Add Sub Field', 'fieldforge' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	// ------------------------------------------------------------------
	// Tools page (import/export)
	// ------------------------------------------------------------------

	public function add_tools_menu(): void {
		add_submenu_page(
			'edit.php?post_type=' . FieldForge_Field_Group::CPT,
			__( 'Import / Export', 'fieldforge' ),
			__( 'Import / Export', 'fieldforge' ),
			'manage_options',
			'fieldforge-tools',
			array( $this, 'render_tools_page' )
		);

		add_action( 'admin_init', array( $this, 'handle_download_export' ) );
	}

	/**
	 * Handle JSON file download request (admin_init).
	 */
	public function handle_download_export(): void {
		if ( ! isset( $_GET['fieldforge_download'] ) || '1' !== $_GET['fieldforge_download'] ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'fieldforge' ) );
		}
		if ( ! isset( $_GET['fieldforge_dl_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['fieldforge_dl_nonce'] ), 'fieldforge_download_export' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'fieldforge' ) );
		}

		$ff       = FieldForge::get_instance();
		$group_id = isset( $_GET['fieldforge_dl_id'] ) ? absint( $_GET['fieldforge_dl_id'] ) : 0;

		if ( $group_id > 0 ) {
			$json     = $ff->field_group->export_json( $group_id );
			$filename = 'fieldforge-group-' . $group_id . '.json';
		} else {
			// Export all groups.
			$all_groups = $ff->field_group->get_all_groups();
			$json       = wp_json_encode( $all_groups, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
			$filename   = 'fieldforge-all-groups.json';
		}

		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
		header( 'Content-Length: ' . strlen( $json ) );
		header( 'Cache-Control: no-cache' );
		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	public function render_tools_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'fieldforge' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'FieldForge — Import / Export', 'fieldforge' ); ?></h1>

			<h2><?php esc_html_e( 'Import Field Group JSON', 'fieldforge' ); ?></h2>
			<p><?php esc_html_e( 'Paste a field group JSON export below to import it into FieldForge.', 'fieldforge' ); ?></p>
			<textarea id="fieldforge-import-json" rows="12" style="width:100%;font-family:monospace"></textarea>
			<br /><br />
			<button type="button" class="button button-primary" id="fieldforge-do-import"><?php esc_html_e( 'Import', 'fieldforge' ); ?></button>
			<span id="fieldforge-import-result" style="margin-left:10px"></span>

			<hr />

			<h2><?php esc_html_e( 'Export Field Group', 'fieldforge' ); ?></h2>
			<?php
			$groups = get_posts(
				array(
					'post_type'      => FieldForge_Field_Group::CPT,
					'post_status'    => 'any',
					'posts_per_page' => -1,
				)
			);
			if ( $groups ) :
				echo '<select id="fieldforge-export-select">';
				foreach ( $groups as $g ) {
					echo '<option value="' . esc_attr( $g->ID ) . '">' . esc_html( $g->post_title ) . '</option>';
				}
				echo '</select> ';
				echo '<button type="button" class="button" id="fieldforge-do-export">' . esc_html__( 'Preview JSON', 'fieldforge' ) . '</button>';

				$dl_nonce = wp_create_nonce( 'fieldforge_download_export' );
				echo ' <a class="button button-primary" id="fieldforge-download-one" href="' . esc_url(
					add_query_arg(
						array(
							'page'                => 'fieldforge-tools',
							'post_type'           => FieldForge_Field_Group::CPT,
							'fieldforge_download' => '1',
							'fieldforge_dl_nonce' => $dl_nonce,
							'fieldforge_dl_id'    => $groups[0]->ID,
						),
						admin_url( 'edit.php' )
					)
				) . '">' . esc_html__( 'Download JSON', 'fieldforge' ) . '</a>';

				echo '<pre id="fieldforge-export-result" style="margin-top:10px;background:#f6f6f6;padding:10px;display:none"></pre>';

				echo '<hr />';
				echo '<h3>' . esc_html__( 'Export All Field Groups', 'fieldforge' ) . '</h3>';
				echo '<p>' . esc_html__( 'Download all field groups as a single JSON file.', 'fieldforge' ) . '</p>';
				echo '<a class="button button-primary" href="' . esc_url(
					add_query_arg(
						array(
							'page'                => 'fieldforge-tools',
							'post_type'           => FieldForge_Field_Group::CPT,
							'fieldforge_download' => '1',
							'fieldforge_dl_nonce' => $dl_nonce,
							'fieldforge_dl_id'    => '0',
						),
						admin_url( 'edit.php' )
					)
				) . '">' . esc_html__( 'Download All Groups JSON', 'fieldforge' ) . '</a>';
			else :
				echo '<p>' . esc_html__( 'No field groups found.', 'fieldforge' ) . '</p>';
			endif;
			?>
		</div>
		<?php
	}

	public function ajax_export_group(): void {
		check_ajax_referer( 'fieldforge_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
		$post_id = absint( $_POST['id'] ?? 0 );
		$ff      = FieldForge::get_instance();
		$json    = $ff->field_group->export_json( $post_id );
		wp_send_json_success( array( 'json' => $json ) );
	}

	// ------------------------------------------------------------------
	// Helpers
	// ------------------------------------------------------------------

	public function enqueue_assets( string $hook ): void {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}
		$is_group_edit = in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && FieldForge_Field_Group::CPT === $screen->post_type;
		$is_tools      = 'fieldforge_group_page_fieldforge-tools' === $screen->id;

		if ( ! $is_group_edit && ! $is_tools ) {
			return;
		}

		wp_enqueue_style(
			'fieldforge-admin',
			FIELDFORGE_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			FIELDFORGE_VERSION
		);
		wp_enqueue_media();
		wp_enqueue_script(
			'fieldforge-admin',
			FIELDFORGE_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'jquery-ui-sortable', 'wp-util' ),
			FIELDFORGE_VERSION,
			true
		);
		wp_localize_script(
			'fieldforge-admin',
			'fieldforgeData',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'fieldforge_admin' ),
				'postTypes' => $this->get_post_type_options(),
				'i18n'      => array(
					'addRow'       => __( 'Add Row', 'fieldforge' ),
					'removeRow'    => __( 'Remove Row', 'fieldforge' ),
					'noRows'       => __( 'No rows yet.', 'fieldforge' ),
					'confirm'      => __( 'Are you sure?', 'fieldforge' ),
					/* translators: %s: field label */
					'requiredMsg'  => __( 'Required: %s', 'fieldforge' ),
					'requiredFail' => __( 'Please fill in all required fields before saving.', 'fieldforge' ),
					'maxRows'      => __( 'Maximum number of rows reached.', 'fieldforge' ),
					'minRows'      => __( 'Minimum number of rows reached.', 'fieldforge' ),
				),
			)
		);
	}

	/**
	 * Return the HTML for the location-rule value widget based on param type.
	 *
	 * @param string $input_name  The HTML name attribute for the widget.
	 * @param string $param       The location param type.
	 * @param string $current     The currently-selected value.
	 * @return string
	 */
	private function render_location_value_widget( string $input_name, string $param, string $current ): string {
		$sel = static function ( string $val ) use ( $current ): string {
			return selected( $current, $val, false );
		};

		switch ( $param ) {
			case 'post_type':
				$opts = '';
				foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $pt ) {
					$opts .= '<option value="' . esc_attr( $pt->name ) . '"' . $sel( $pt->name ) . '>' . esc_html( $pt->label ) . '</option>';
				}
				return '<select name="' . esc_attr( $input_name ) . '" class="ff-location-value-select">' . $opts . '</select>';

			case 'post_status':
				$statuses = get_post_stati( array( 'internal' => false ), 'objects' );
				$opts     = '';
				foreach ( $statuses as $status ) {
					$opts .= '<option value="' . esc_attr( $status->name ) . '"' . $sel( $status->name ) . '>' . esc_html( $status->label ) . '</option>';
				}
				return '<select name="' . esc_attr( $input_name ) . '" class="ff-location-value-select">' . $opts . '</select>';

			case 'user_role':
				$opts = '';
				foreach ( wp_roles()->role_names as $slug => $name ) {
					$opts .= '<option value="' . esc_attr( $slug ) . '"' . $sel( $slug ) . '>' . esc_html( $name ) . '</option>';
				}
				return '<select name="' . esc_attr( $input_name ) . '" class="ff-location-value-select">' . $opts . '</select>';

			case 'page_template':
				$opts = '<option value="default"' . $sel( 'default' ) . '>' . esc_html__( 'Default Template', 'fieldforge' ) . '</option>';
				foreach ( get_page_templates() as $template_name => $template_file ) {
					$opts .= '<option value="' . esc_attr( $template_file ) . '"' . $sel( $template_file ) . '>' . esc_html( $template_name ) . '</option>';
				}
				return '<select name="' . esc_attr( $input_name ) . '" class="ff-location-value-select">' . $opts . '</select>';

			case 'page_parent':
				$pages = get_posts(
					array(
						'post_type'   => 'page',
						'post_status' => 'any',
						'numberposts' => 200,
					)
				);
				$opts  = '<option value="0"' . $sel( '0' ) . '>' . esc_html__( '— No Parent —', 'fieldforge' ) . '</option>';
				foreach ( $pages as $page ) {
					$opts .= '<option value="' . esc_attr( $page->ID ) . '"' . $sel( (string) $page->ID ) . '>' . esc_html( $page->post_title ) . '</option>';
				}
				return '<select name="' . esc_attr( $input_name ) . '" class="ff-location-value-select">' . $opts . '</select>';

			default:
				return '<input type="text" name="' . esc_attr( $input_name ) . '" value="' . esc_attr( $current ) . '" class="regular-text ff-location-value-text" />';
		}
	}

	private function get_type_labels(): array {
		return array(
			'text'         => __( 'Text', 'fieldforge' ),
			'textarea'     => __( 'Textarea', 'fieldforge' ),
			'number'       => __( 'Number', 'fieldforge' ),
			'select'       => __( 'Select', 'fieldforge' ),
			'checkbox'     => __( 'Checkbox', 'fieldforge' ),
			'radio'        => __( 'Radio', 'fieldforge' ),
			'true_false'   => __( 'True / False', 'fieldforge' ),
			'date_picker'  => __( 'Date Picker', 'fieldforge' ),
			'time_picker'  => __( 'Time Picker', 'fieldforge' ),
			'color_picker' => __( 'Color Picker', 'fieldforge' ),
			'url'          => __( 'URL', 'fieldforge' ),
			'email'        => __( 'Email', 'fieldforge' ),
			'password'     => __( 'Password', 'fieldforge' ),
			'file'         => __( 'File', 'fieldforge' ),
			'image'        => __( 'Image', 'fieldforge' ),
			'gallery'      => __( 'Gallery', 'fieldforge' ),
			'post_object'  => __( 'Post Object', 'fieldforge' ),
			'taxonomy'     => __( 'Taxonomy', 'fieldforge' ),
			'user'         => __( 'User', 'fieldforge' ),
			'link'         => __( 'Link', 'fieldforge' ),
			'wysiwyg'      => __( 'WYSIWYG', 'fieldforge' ),
			'message'      => __( 'Message', 'fieldforge' ),
			'tab'          => __( 'Tab', 'fieldforge' ),
			'accordion'    => __( 'Accordion', 'fieldforge' ),
			'repeater'         => __( 'Repeater', 'fieldforge' ),
			'flexible_content' => __( 'Flexible Content', 'fieldforge' ),
		);
	}

	private function get_location_params(): array {
		return array(
			'post_type'     => __( 'Post Type', 'fieldforge' ),
			'post_status'   => __( 'Post Status', 'fieldforge' ),
			'page_template' => __( 'Page Template', 'fieldforge' ),
			'page_parent'   => __( 'Page Parent', 'fieldforge' ),
			'user_role'     => __( 'User Role', 'fieldforge' ),
			'post_taxonomy' => __( 'Post Taxonomy', 'fieldforge' ),
			'post_format'   => __( 'Post Format', 'fieldforge' ),
			'attachment'    => __( 'Attachment', 'fieldforge' ),
			'options_page'  => __( 'Options Page', 'fieldforge' ),
		);
	}

	private function get_post_type_options(): array {
		$pts    = get_post_types( array( 'public' => true ), 'objects' );
		$result = array();
		foreach ( $pts as $pt ) {
			$result[] = array(
				'value' => $pt->name,
				'label' => $pt->label,
			);
		}
		return $result;
	}

	/**
	 * Return the category slug for a field type — mirrors JS getCategoryForType().
	 *
	 * @param string $type Field type slug.
	 * @return string Category slug: text|choice|media|relation|content|layout.
	 */
	private function get_type_category( string $type ): string {
		$map = array(
			'text'     => array( 'text', 'textarea', 'number', 'email', 'url', 'password', 'wysiwyg' ),
			'choice'   => array( 'select', 'checkbox', 'radio', 'true_false' ),
			'media'    => array( 'image', 'file', 'gallery' ),
			'relation' => array( 'post_object', 'taxonomy', 'user', 'link' ),
			'content'  => array( 'date_picker', 'time_picker', 'color_picker', 'message', 'tab', 'accordion' ),
			'layout'   => array( 'repeater', 'flexible_content' ),
		);
		foreach ( $map as $cat => $types ) {
			if ( in_array( $type, $types, true ) ) {
				return $cat;
			}
		}
		return 'content';
	}

	/**
	 * Return the dashicon class for a field type — mirrors JS TYPE_ICONS.
	 *
	 * @param string $type Field type slug.
	 * @return string Dashicon class string.
	 */
	private function get_type_icon( string $type ): string {
		$icons = array(
			'text'             => 'dashicons-editor-paragraph',
			'textarea'         => 'dashicons-text',
			'number'           => 'dashicons-calculator',
			'email'            => 'dashicons-email-alt',
			'url'              => 'dashicons-admin-links',
			'password'         => 'dashicons-lock',
			'wysiwyg'          => 'dashicons-editor-kitchensink',
			'select'           => 'dashicons-arrow-down-alt2',
			'checkbox'         => 'dashicons-yes',
			'radio'            => 'dashicons-marker',
			'true_false'       => 'dashicons-controls-repeat',
			'image'            => 'dashicons-format-image',
			'file'             => 'dashicons-media-default',
			'gallery'          => 'dashicons-format-gallery',
			'post_object'      => 'dashicons-admin-post',
			'taxonomy'         => 'dashicons-tag',
			'user'             => 'dashicons-admin-users',
			'link'             => 'dashicons-admin-links',
			'date_picker'      => 'dashicons-calendar-alt',
			'time_picker'      => 'dashicons-clock',
			'color_picker'     => 'dashicons-art',
			'message'          => 'dashicons-info',
			'tab'              => 'dashicons-category',
			'accordion'        => 'dashicons-arrow-down-alt2',
			'repeater'         => 'dashicons-editor-table',
			'flexible_content' => 'dashicons-layout',
		);
		return $icons[ $type ] ?? 'dashicons-admin-generic';
	}
}
