<?php
/**
 * Conditional Logic — show/hide fields in meta boxes based on the values of other fields.
 *
 * Rules are stored in each field config under a `conditional_logic` key:
 *
 *   [
 *     // OR group 1 (all rules in a group must match — AND)
 *     [
 *       ['field' => 'field_key', 'operator' => '==', 'value' => 'yes'],
 *       ['field' => 'field_key2', 'operator' => '!=', 'value' => ''],
 *     ],
 *     // OR group 2
 *     [
 *       ['field' => 'field_key3', 'operator' => '==empty'],
 *     ],
 *   ]
 *
 * Supported operators: ==, !=, >, <, >=, <=, ==empty, !=empty, ==contains, !=contains
 *
 * The logic is evaluated client-side in JS for instant feedback and server-side
 * during save (fields that don't pass their conditions are not saved).
 *
 * @package FieldForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FieldForge_Conditional_Logic {

	public function __construct() {
		// Inject conditional logic data into the meta box page.
		add_action( 'admin_footer', array( $this, 'print_logic_data' ) );
	}

	/**
	 * Evaluate whether a field should be shown for the current post.
	 *
	 * Used server-side to skip saving hidden fields.
	 *
	 * @param array $field_config  Field configuration array.
	 * @param int   $post_id
	 * @return bool True if the field should be visible (and saved).
	 */
	public static function field_is_visible( array $field_config, int $post_id ): bool {
		$rules = $field_config['conditional_logic'] ?? array();

		// No rules → always visible.
		if ( empty( $rules ) || ! is_array( $rules ) ) {
			return true;
		}

		// OR across groups; AND within each group.
		foreach ( $rules as $or_group ) {
			if ( ! is_array( $or_group ) || empty( $or_group ) ) {
				continue;
			}
			$group_passes = true;
			foreach ( $or_group as $rule ) {
				if ( ! self::evaluate_rule( $rule, $post_id ) ) {
					$group_passes = false;
					break;
				}
			}
			if ( $group_passes ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Evaluate a single conditional rule against the current post's saved values.
	 *
	 * @param array $rule     Rule with keys: field (key), operator, value.
	 * @param int   $post_id
	 * @return bool
	 */
	private static function evaluate_rule( array $rule, int $post_id ): bool {
		$field_key = $rule['field'] ?? '';
		$operator  = $rule['operator'] ?? '==';
		$target    = $rule['value'] ?? '';

		// Resolve the field name from the field key.
		$field_name = self::key_to_name( $field_key );
		$actual     = '' !== $field_name ? get_post_meta( $post_id, $field_name, true ) : '';

		switch ( $operator ) {
			case '==':
				return (string) $actual === (string) $target;
			case '!=':
				return (string) $actual !== (string) $target;
			case '>':
				return is_numeric( $actual ) && is_numeric( $target ) && (float) $actual > (float) $target;
			case '<':
				return is_numeric( $actual ) && is_numeric( $target ) && (float) $actual < (float) $target;
			case '>=':
				return is_numeric( $actual ) && is_numeric( $target ) && (float) $actual >= (float) $target;
			case '<=':
				return is_numeric( $actual ) && is_numeric( $target ) && (float) $actual <= (float) $target;
			case '==empty':
				return '' === $actual || array() === $actual || null === $actual;
			case '!=empty':
				return '' !== $actual && array() !== $actual && null !== $actual;
			case '==contains':
				return is_string( $actual ) && false !== strpos( $actual, (string) $target );
			case '!=contains':
				return is_string( $actual ) && false === strpos( $actual, (string) $target );
		}
		return false;
	}

	/**
	 * Resolve a field key (e.g. `field_abc123`) to its field name via postmeta.
	 *
	 * Falls back to searching all field groups.
	 *
	 * @param string $field_key
	 * @return string Field name, or empty string if not found.
	 */
	private static function key_to_name( string $field_key ): string {
		$ff     = FieldForge::get_instance();
		$groups = $ff->field_group->get_all_groups();
		foreach ( $groups as $group ) {
			foreach ( $group['fields'] as $field ) {
				if ( ( $field['key'] ?? '' ) === $field_key ) {
					return $field['name'] ?? '';
				}
			}
		}
		return '';
	}

	// ------------------------------------------------------------------
	// Client-side data
	// ------------------------------------------------------------------

	/**
	 * Print a JSON payload consumed by admin.js to run conditional logic in-browser.
	 */
	public function print_logic_data(): void {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->base, array( 'post', 'post-new' ), true ) ) {
			return;
		}
		if ( FieldForge_Field_Group::CPT === $screen->post_type ) {
			return;
		}

		$ff       = FieldForge::get_instance();
		$groups   = $ff->field_group->get_all_groups();
		$logic    = array();

		foreach ( $groups as $group ) {
			foreach ( $group['fields'] as $field ) {
				$rules = $field['conditional_logic'] ?? array();
				if ( ! empty( $rules ) ) {
					$logic[ $field['key'] ?? $field['name'] ] = array(
						'name'  => $field['name'] ?? '',
						'rules' => $rules,
					);
				}
			}
		}

		if ( empty( $logic ) ) {
			return;
		}
		?>
		<script type="text/javascript">
		window.fieldforgeConditionalLogic = <?php echo wp_json_encode( $logic ); ?>;
		</script>
		<?php
	}
}
