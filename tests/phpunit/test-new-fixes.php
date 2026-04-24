<?php
/**
 * Tests covering fixes from the second gap analysis:
 *   - format_value() for repeater and flexible_content
 *   - validate() base class behaviour
 *   - ACF importer flexible_content conversion
 *   - Tab and Accordion field types (layout-only, save nothing)
 *
 * @package FieldForge
 */

class FieldForge_Test_New_Fixes extends WP_UnitTestCase {

	/** @var int */
	protected static int $post_id;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$post_id = self::factory()->post->create();
	}

	// ------------------------------------------------------------------
	// Repeater format_value
	// ------------------------------------------------------------------

	public function test_repeater_format_value_passes_through_sub_field_values(): void {
		$field = new FieldForge_Field_Repeater( array(
			'key'        => 'field_rep',
			'name'       => 'ff_rep',
			'type'       => 'repeater',
			'sub_fields' => array(
				array( 'key' => 'field_s1', 'name' => 'title', 'type' => 'text' ),
				array( 'key' => 'field_s2', 'name' => 'count', 'type' => 'number' ),
			),
		) );

		$raw    = array(
			array( 'title' => 'Hello', 'count' => '5' ),
			array( 'title' => 'World', 'count' => '10' ),
		);
		$result = $field->format_value( $raw, self::$post_id );

		$this->assertCount( 2, $result );
		$this->assertSame( 'Hello', $result[0]['title'] );
		$this->assertSame( '5', $result[0]['count'] );
	}

	public function test_repeater_format_value_empty_returns_empty_array(): void {
		$field  = new FieldForge_Field_Repeater( array( 'key' => 'f', 'name' => 'r', 'type' => 'repeater', 'sub_fields' => array() ) );
		$this->assertSame( array(), $field->format_value( 'not-array', self::$post_id ) );
	}

	// ------------------------------------------------------------------
	// Flexible content format_value
	// ------------------------------------------------------------------

	public function test_fc_format_value_preserves_layout_name(): void {
		$field = new FieldForge_Field_Flexible_Content( array(
			'key'     => 'field_fc',
			'name'    => 'ff_fc',
			'type'    => 'flexible_content',
			'layouts' => array(
				array(
					'name'       => 'hero',
					'label'      => 'Hero',
					'sub_fields' => array(
						array( 'key' => 'field_ht', 'name' => 'headline', 'type' => 'text' ),
					),
				),
			),
		) );

		$raw    = array( array( 'acf_fc_layout' => 'hero', 'headline' => 'Big Title' ) );
		$result = $field->format_value( $raw, self::$post_id );

		$this->assertCount( 1, $result );
		$this->assertSame( 'hero', $result[0]['acf_fc_layout'] );
		$this->assertSame( 'Big Title', $result[0]['headline'] );
	}

	public function test_fc_format_value_empty_returns_empty_array(): void {
		$field  = new FieldForge_Field_Flexible_Content( array( 'key' => 'f', 'name' => 'fc', 'type' => 'flexible_content', 'layouts' => array() ) );
		$this->assertSame( array(), $field->format_value( null, self::$post_id ) );
	}

	// ------------------------------------------------------------------
	// validate() — base class
	// ------------------------------------------------------------------

	public function test_validate_required_fails_on_empty(): void {
		$field  = new FieldForge_Field_Text( array( 'key' => 'f', 'name' => 'req', 'type' => 'text', 'required' => 1, 'label' => 'Name' ) );
		$result = $field->validate( '' );
		$this->assertIsString( $result );
		$this->assertStringContainsString( 'Name', $result );
	}

	public function test_validate_required_passes_on_non_empty(): void {
		$field  = new FieldForge_Field_Text( array( 'key' => 'f', 'name' => 'req', 'type' => 'text', 'required' => 1 ) );
		$this->assertTrue( $field->validate( 'hello' ) );
	}

	public function test_validate_optional_always_passes_on_empty(): void {
		$field  = new FieldForge_Field_Text( array( 'key' => 'f', 'name' => 'opt', 'type' => 'text', 'required' => 0 ) );
		$this->assertTrue( $field->validate( '' ) );
	}

	// ------------------------------------------------------------------
	// ACF importer — flexible_content
	// ------------------------------------------------------------------

	public function test_acf_importer_converts_flexible_content_type(): void {
		$ff       = FieldForge::get_instance();
		$importer = new FieldForge_ACF_Importer( $ff->field_group );

		$acf_group = array(
			'key'      => 'group_fc_test',
			'title'    => 'FC Import Test',
			'fields'   => array(
				array(
					'key'     => 'field_fc1',
					'label'   => 'Page Builder',
					'name'    => 'page_builder',
					'type'    => 'flexible_content',
					'layouts' => array(
						array(
							'name'       => 'hero',
							'label'      => 'Hero',
							'sub_fields' => array(
								array( 'key' => 'field_ht', 'label' => 'Headline', 'name' => 'headline', 'type' => 'text' ),
							),
						),
					),
				),
			),
			'location' => array(),
		);

		$ids = $importer->import( wp_json_encode( $acf_group ) );
		$this->assertIsArray( $ids );
		$this->assertGreaterThan( 0, $ids[0] );

		$fields = get_post_meta( $ids[0], '_fieldforge_fields', true );
		$this->assertSame( 'flexible_content', $fields[0]['type'] );
		$this->assertCount( 1, $fields[0]['layouts'] );
		$this->assertSame( 'hero', $fields[0]['layouts'][0]['name'] );
	}

	// ------------------------------------------------------------------
	// Tab field — saves nothing
	// ------------------------------------------------------------------

	public function test_tab_field_save_is_noop(): void {
		$field = new FieldForge_Field_Tab( array( 'key' => 'f', 'name' => 'my_tab', 'type' => 'tab', 'label' => 'Details' ) );
		$field->save( self::$post_id, 'anything' );
		$this->assertSame( '', get_post_meta( self::$post_id, 'my_tab', true ) );
	}

	public function test_tab_field_load_returns_empty(): void {
		$field = new FieldForge_Field_Tab( array( 'key' => 'f', 'name' => 'my_tab', 'type' => 'tab' ) );
		$this->assertSame( '', $field->load( self::$post_id ) );
	}

	// ------------------------------------------------------------------
	// Accordion field — saves nothing
	// ------------------------------------------------------------------

	public function test_accordion_field_save_is_noop(): void {
		$field = new FieldForge_Field_Accordion( array( 'key' => 'f', 'name' => 'my_acc', 'type' => 'accordion', 'label' => 'Extra' ) );
		$field->save( self::$post_id, 'something' );
		$this->assertSame( '', get_post_meta( self::$post_id, 'my_acc', true ) );
	}
}
