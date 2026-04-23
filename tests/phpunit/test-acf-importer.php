<?php
/**
 * Tests for the ACF JSON importer.
 *
 * @package FieldForge
 */

class FieldForge_Test_ACF_Importer extends WP_UnitTestCase {

	/** @var FieldForge_ACF_Importer */
	private FieldForge_ACF_Importer $importer;

	public function setUp(): void {
		parent::setUp();
		$this->importer = new FieldForge_ACF_Importer( FieldForge::get_instance()->field_group );
	}

	public function test_import_single_group(): void {
		$json = wp_json_encode( $this->acf_group_fixture() );
		$ids  = $this->importer->import( $json );

		$this->assertIsArray( $ids );
		$this->assertCount( 1, $ids );
		$this->assertGreaterThan( 0, $ids[0] );
	}

	public function test_imported_group_has_correct_title(): void {
		$fixture = $this->acf_group_fixture();
		$json    = wp_json_encode( $fixture );
		$ids     = $this->importer->import( $json );

		$post = get_post( $ids[0] );
		$this->assertSame( 'Test ACF Group', $post->post_title );
	}

	public function test_imported_group_has_fields(): void {
		$json = wp_json_encode( $this->acf_group_fixture() );
		$ids  = $this->importer->import( $json );

		$fields = get_post_meta( $ids[0], '_fieldforge_fields', true );
		$this->assertIsArray( $fields );
		$this->assertNotEmpty( $fields );
	}

	public function test_import_location_rules(): void {
		$json    = wp_json_encode( $this->acf_group_fixture() );
		$ids     = $this->importer->import( $json );
		$location = get_post_meta( $ids[0], '_fieldforge_location', true );

		$this->assertIsArray( $location );
		$this->assertNotEmpty( $location );
		$this->assertSame( 'post_type', $location[0][0]['param'] );
		$this->assertSame( '==', $location[0][0]['operator'] );
		$this->assertSame( 'post', $location[0][0]['value'] );
	}

	public function test_import_repeater_sub_fields(): void {
		$fixture           = $this->acf_group_fixture();
		$fixture['fields'] = array(
			array(
				'key'        => 'field_rep',
				'label'      => 'My Repeater',
				'name'       => 'my_repeater',
				'type'       => 'repeater',
				'sub_fields' => array(
					array( 'key' => 'field_sub1', 'label' => 'Sub Title', 'name' => 'sub_title', 'type' => 'text' ),
					array( 'key' => 'field_sub2', 'label' => 'Sub Count', 'name' => 'sub_count', 'type' => 'number' ),
				),
			),
		);

		$json = wp_json_encode( $fixture );
		$ids  = $this->importer->import( $json );

		$fields = get_post_meta( $ids[0], '_fieldforge_fields', true );
		$this->assertSame( 'repeater', $fields[0]['type'] );
		$this->assertCount( 2, $fields[0]['sub_fields'] );
	}

	public function test_import_invalid_json_returns_wp_error(): void {
		$result = $this->importer->import( '{not valid json' );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_import_array_of_groups(): void {
		$json = wp_json_encode( array( $this->acf_group_fixture(), $this->acf_group_fixture( 'group_002', 'Second Group' ) ) );
		$ids  = $this->importer->import( $json );
		$this->assertCount( 2, $ids );
	}

	// ------------------------------------------------------------------
	// Fixtures
	// ------------------------------------------------------------------

	private function acf_group_fixture( string $key = 'group_001', string $title = 'Test ACF Group' ): array {
		return array(
			'key'      => $key,
			'title'    => $title,
			'fields'   => array(
				array( 'key' => 'field_001', 'label' => 'Hero Title', 'name' => 'hero_title', 'type' => 'text' ),
				array( 'key' => 'field_002', 'label' => 'Body',       'name' => 'body',        'type' => 'textarea' ),
				array( 'key' => 'field_003', 'label' => 'Thumbnail',  'name' => 'thumbnail',   'type' => 'image' ),
			),
			'location' => array(
				array(
					array( 'param' => 'post_type', 'operator' => '==', 'value' => 'post' ),
				),
			),
			'active'   => true,
		);
	}
}
