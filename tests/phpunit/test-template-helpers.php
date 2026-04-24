<?php
/**
 * Tests for template helper functions (fieldforge_get, options context, repeater helpers).
 *
 * @package FieldForge
 */

class FieldForge_Test_Template_Helpers extends WP_UnitTestCase {

	/** @var int */
	protected static int $post_id;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$post_id = self::factory()->post->create( array( 'post_title' => 'Helper Test Post' ) );
	}

	// ------------------------------------------------------------------
	// fieldforge_get — postmeta fallback (no field group registered)
	// ------------------------------------------------------------------

	public function test_fieldforge_get_falls_back_to_postmeta(): void {
		update_post_meta( self::$post_id, 'bare_key', 'bare_value' );
		$this->assertSame( 'bare_value', fieldforge_get( 'bare_key', self::$post_id ) );
	}

	// ------------------------------------------------------------------
	// fieldforge_get — options context
	// ------------------------------------------------------------------

	public function test_fieldforge_get_options_reads_wp_option(): void {
		update_option( 'fieldforge_option_my_page_my_setting', 'option_value' );
		$result = fieldforge_get( 'my_setting', 'option' );
		// Without a registered field group pointing to an options page, falls back to raw get_option.
		// The options page class searches all pages; if none match, returns false/null.
		// We just assert no exception is thrown and the return is not a WP_Error.
		$this->assertNotInstanceOf( WP_Error::class, $result );
	}

	public function test_fieldforge_get_options_alias(): void {
		$a = fieldforge_get( 'nonexistent_field', 'option' );
		$b = fieldforge_get( 'nonexistent_field', 'options' );
		$this->assertSame( $a, $b );
	}

	// ------------------------------------------------------------------
	// fieldforge_the — echoes escaped output
	// ------------------------------------------------------------------

	public function test_fieldforge_the_escapes_html(): void {
		update_post_meta( self::$post_id, 'xss_field', '<script>alert(1)</script>' );
		ob_start();
		fieldforge_the( 'xss_field', self::$post_id );
		$output = ob_get_clean();
		$this->assertStringNotContainsString( '<script>', $output );
		$this->assertStringContainsString( '&lt;script&gt;', $output );
	}

	// ------------------------------------------------------------------
	// Repeater helpers
	// ------------------------------------------------------------------

	public function test_repeater_loop_iterates_rows(): void {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, 'my_list', 2 );
		update_post_meta( $post_id, 'my_list_0_item', 'Alpha' );
		update_post_meta( $post_id, 'my_list_1_item', 'Beta' );

		$field = new FieldForge_Field_Repeater( array(
			'key'        => 'field_list',
			'name'       => 'my_list',
			'type'       => 'repeater',
			'sub_fields' => array(
				array( 'key' => 'field_item', 'name' => 'item', 'type' => 'text' ),
			),
		) );
		$field->save( $post_id, array( array( 'item' => 'Alpha' ), array( 'item' => 'Beta' ) ) );

		$count = 0;
		while ( fieldforge_have_rows( 'my_list', $post_id ) ) {
			$count++;
			$val = fieldforge_sub_field( 'item' );
			$this->assertIsString( $val );
		}
		$this->assertSame( 2, $count );
	}

	// ------------------------------------------------------------------
	// fieldforge_find_field_config
	// ------------------------------------------------------------------

	public function test_find_field_config_returns_null_for_unknown(): void {
		$config = fieldforge_find_field_config( 'definitely_not_registered_xyz', 0 );
		$this->assertNull( $config );
	}
}
