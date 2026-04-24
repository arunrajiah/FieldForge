<?php
/**
 * Tests for FieldForge_Conditional_Logic::field_is_visible().
 *
 * Operators: ==, !=, >, <, >=, <=, ==empty, !=empty, ==contains, !=contains
 * Rules reference fields by key; key_to_name() resolves via registered groups.
 * We bypass key resolution by registering a temporary field group.
 *
 * @package FieldForge
 */

class FieldForge_Test_Conditional_Logic extends WP_UnitTestCase {

	/** @var int */
	protected static int $post_id;

	/** @var int saved group ID for cleanup */
	private static int $group_id = 0;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$post_id = self::factory()->post->create();

		// Register a field group so key_to_name() can resolve 'field_cond_test'.
		$ff             = FieldForge::get_instance();
		self::$group_id = $ff->field_group->save_group(
			array(
				'title'    => 'Conditional Test Group',
				'fields'   => array(
					array( 'key' => 'field_cond_test', 'name' => 'cond_test', 'type' => 'text' ),
					array( 'key' => 'field_cond_num',  'name' => 'cond_num',  'type' => 'number' ),
					array( 'key' => 'field_cond_bio',  'name' => 'cond_bio',  'type' => 'text' ),
				),
				'location' => array(),
				'active'   => true,
			)
		);
	}

	public static function tearDownAfterClass(): void {
		if ( self::$group_id ) {
			wp_delete_post( self::$group_id, true );
		}
		parent::tearDownAfterClass();
	}

	private function field_config( string $key, array $rules ): array {
		return array(
			'key'               => $key,
			'name'              => 'dummy',
			'type'              => 'text',
			'conditional_logic' => $rules,
		);
	}

	// ------------------------------------------------------------------
	// No rules → always visible
	// ------------------------------------------------------------------

	public function test_no_rules_always_visible(): void {
		$this->assertTrue( FieldForge_Conditional_Logic::field_is_visible( array( 'key' => 'f', 'name' => 'x', 'type' => 'text' ), self::$post_id ) );
	}

	// ------------------------------------------------------------------
	// == operator
	// ------------------------------------------------------------------

	public function test_equals_match_shows_field(): void {
		update_post_meta( self::$post_id, 'cond_test', 'yes' );

		$config = $this->field_config( 'field_x', array(
			array( array( 'field' => 'field_cond_test', 'operator' => '==', 'value' => 'yes' ) ),
		) );
		$this->assertTrue( FieldForge_Conditional_Logic::field_is_visible( $config, self::$post_id ) );
	}

	public function test_equals_no_match_hides_field(): void {
		update_post_meta( self::$post_id, 'cond_test', 'no' );

		$config = $this->field_config( 'field_x', array(
			array( array( 'field' => 'field_cond_test', 'operator' => '==', 'value' => 'yes' ) ),
		) );
		$this->assertFalse( FieldForge_Conditional_Logic::field_is_visible( $config, self::$post_id ) );
	}

	// ------------------------------------------------------------------
	// != operator
	// ------------------------------------------------------------------

	public function test_not_equals_shows_field(): void {
		update_post_meta( self::$post_id, 'cond_test', 'red' );

		$config = $this->field_config( 'field_x', array(
			array( array( 'field' => 'field_cond_test', 'operator' => '!=', 'value' => 'blue' ) ),
		) );
		$this->assertTrue( FieldForge_Conditional_Logic::field_is_visible( $config, self::$post_id ) );
	}

	// ------------------------------------------------------------------
	// Numeric operators
	// ------------------------------------------------------------------

	public function test_greater_than(): void {
		update_post_meta( self::$post_id, 'cond_num', '10' );

		$config = $this->field_config( 'field_x', array(
			array( array( 'field' => 'field_cond_num', 'operator' => '>', 'value' => '5' ) ),
		) );
		$this->assertTrue( FieldForge_Conditional_Logic::field_is_visible( $config, self::$post_id ) );
	}

	public function test_less_than(): void {
		update_post_meta( self::$post_id, 'cond_num', '2' );

		$config = $this->field_config( 'field_x', array(
			array( array( 'field' => 'field_cond_num', 'operator' => '<', 'value' => '5' ) ),
		) );
		$this->assertTrue( FieldForge_Conditional_Logic::field_is_visible( $config, self::$post_id ) );
	}

	// ------------------------------------------------------------------
	// ==empty / !=empty
	// ------------------------------------------------------------------

	public function test_empty_operator(): void {
		update_post_meta( self::$post_id, 'cond_test', '' );

		$config = $this->field_config( 'field_x', array(
			array( array( 'field' => 'field_cond_test', 'operator' => '==empty', 'value' => '' ) ),
		) );
		$this->assertTrue( FieldForge_Conditional_Logic::field_is_visible( $config, self::$post_id ) );
	}

	public function test_not_empty_operator(): void {
		update_post_meta( self::$post_id, 'cond_test', 'something' );

		$config = $this->field_config( 'field_x', array(
			array( array( 'field' => 'field_cond_test', 'operator' => '!=empty', 'value' => '' ) ),
		) );
		$this->assertTrue( FieldForge_Conditional_Logic::field_is_visible( $config, self::$post_id ) );
	}

	// ------------------------------------------------------------------
	// ==contains / !=contains
	// ------------------------------------------------------------------

	public function test_contains_operator(): void {
		update_post_meta( self::$post_id, 'cond_bio', 'WordPress developer' );

		$config = $this->field_config( 'field_x', array(
			array( array( 'field' => 'field_cond_bio', 'operator' => '==contains', 'value' => 'developer' ) ),
		) );
		$this->assertTrue( FieldForge_Conditional_Logic::field_is_visible( $config, self::$post_id ) );
	}

	public function test_not_contains_operator(): void {
		update_post_meta( self::$post_id, 'cond_bio', 'WordPress developer' );

		$config = $this->field_config( 'field_x', array(
			array( array( 'field' => 'field_cond_bio', 'operator' => '!=contains', 'value' => 'designer' ) ),
		) );
		$this->assertTrue( FieldForge_Conditional_Logic::field_is_visible( $config, self::$post_id ) );
	}

	// ------------------------------------------------------------------
	// OR logic: first group fails, second passes
	// ------------------------------------------------------------------

	public function test_or_logic_second_group_passes(): void {
		update_post_meta( self::$post_id, 'cond_test', 'nope' );
		update_post_meta( self::$post_id, 'cond_num', '99' );

		$config = $this->field_config( 'field_x', array(
			// Group 1: will fail (cond_test != 'yes').
			array( array( 'field' => 'field_cond_test', 'operator' => '==', 'value' => 'yes' ) ),
			// Group 2: will pass (cond_num > 50).
			array( array( 'field' => 'field_cond_num', 'operator' => '>', 'value' => '50' ) ),
		) );
		$this->assertTrue( FieldForge_Conditional_Logic::field_is_visible( $config, self::$post_id ) );
	}
}
